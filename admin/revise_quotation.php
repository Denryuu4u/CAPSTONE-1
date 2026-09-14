<?php
/**
 * Admin revises a quotation in response to a client counter-offer (from
 * quotations.php) and re-sends it. Updates the total, clears the counter,
 * sets the status back to 'Sent', and notifies the client so they can
 * accept / reject / counter the revised quote. This keeps the normal
 * quote -> accept -> approve flow going after a negotiation.
 *
 * POST: id, new_total (required), note (optional). Returns JSON.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('quotations', true); // back-office only
header('Content-Type: application/json; charset=utf-8');
ensure_counter_offer_columns();

function rv_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$id          = (int) ($_POST['id'] ?? 0);
$newTotalRaw = trim((string) ($_POST['new_total'] ?? ''));
$note        = mb_substr(trim((string) ($_POST['note'] ?? '')), 0, 1000);

if ($id <= 0) rv_fail('Bad request.');
$newTotal = (float) preg_replace('/[^0-9.]/', '', $newTotalRaw);
if ($newTotal <= 0) rv_fail('Enter a valid revised total.');

$pdo  = db();
$stmt = $pdo->prepare("SELECT * FROM quotations WHERE id = ?");
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) rv_fail('Quotation not found.', 404);
if (!in_array($q['status'], ['Countered', 'Sent', 'Accepted'], true)) {
    rv_fail('This quotation can no longer be revised.');
}

$projectId = (int) $q['project_id'];

try {
    $pdo->beginTransaction();
    $pdo->prepare(
        "UPDATE quotations
            SET total_amount = ?,
                status       = 'Sent',
                valid_until  = DATE_ADD(CURDATE(), INTERVAL 30 DAY),
                notes        = TRIM(CONCAT(COALESCE(notes, ''), ?)),
                counter_amount = NULL, counter_comment = NULL, counter_at = NULL
          WHERE id = ?"
    )->execute([$newTotal, ($note !== '' ? "\nRevised: " . $note : ''), $id]);

    // If the project had been marked rejected, bring it back into the quote stage.
    if ($projectId) {
        $pdo->prepare("UPDATE projects SET status = 'quote_submitted' WHERE id = ? AND status = 'rejected'")
            ->execute([$projectId]);
    }
    $pdo->commit();

    if ($projectId) {
        add_project_update(
            $projectId,
            'Revised quotation sent: ' . peso($newTotal) . ($note !== '' ? ' — ' . $note : ''),
            true // notify the client
        );
    }
    log_audit('Quotations', "Revised & resent {$q['quote_code']} — " . peso($newTotal), $q['project_name']);

    echo json_encode(['ok' => true, 'total' => peso($newTotal)]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    rv_fail('Server error: ' . $e->getMessage(), 500);
}
