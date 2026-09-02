<?php
/**
 * Admin approves or rejects a quotation (from quotations.php).
 *
 * POST: id, action = approve | reject
 * approve -> quotation 'Approved', project 'approved' (greenlit for production)
 * reject  -> quotation 'Rejected', project 'rejected'
 * Returns JSON.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('quotations', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

$id     = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

function uq_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if ($id <= 0 || !in_array($action, ['approve', 'reject'], true)) uq_fail('Bad request.');

$pdo = db();
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) uq_fail('Quotation not found.', 404);

$projectId = (int) $q['project_id'];
$admin = current_user();

try {
    $pdo->beginTransaction();

    if ($action === 'approve') {
        $pdo->prepare("UPDATE quotations SET status = 'Approved' WHERE id = ?")->execute([$id]);
        if ($projectId) {
            $pdo->prepare(
                "UPDATE projects
                    SET status = 'approved',
                        start_date = COALESCE(start_date, CURDATE()),
                        approver = ?
                  WHERE id = ?"
            )->execute([$admin['full_name'] ?? 'Vast Solutions', $projectId]);
        }
    } else { // reject
        $pdo->prepare("UPDATE quotations SET status = 'Rejected' WHERE id = ?")->execute([$id]);
        if ($projectId) {
            $pdo->prepare("UPDATE projects SET status = 'rejected' WHERE id = ?")->execute([$projectId]);
        }
    }

    $pdo->commit();

    if ($projectId) {
        $msg = $action === 'approve'
            ? 'Quotation approved — your project is greenlit for production.'
            : 'Quotation was not approved.';
        add_project_update($projectId, $msg); // notifies the client
    }
    log_audit('Quotations', ucfirst($action) . "d quotation {$q['quote_code']}", $q['project_name']);

    echo json_encode(['ok' => true, 'status' => $action === 'approve' ? 'Approved' : 'Rejected']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    uq_fail('Server error: ' . $e->getMessage(), 500);
}
