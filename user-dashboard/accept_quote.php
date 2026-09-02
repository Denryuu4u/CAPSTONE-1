<?php
/**
 * Client accepts a quotation (from my_projects.php / project_detail.php).
 * Sets the quotation to 'Accepted' (awaiting admin approval) and notifies admins.
 */
require_once __DIR__ . '/../includes/helpers.php';

$quotationId = (int) ($_POST['quotation_id'] ?? $_GET['quotation_id'] ?? 0);
$back = $_SERVER['HTTP_REFERER'] ?? 'my_projects.php';

if ($quotationId <= 0) { header("Location: {$back}"); exit; }

$pdo  = db();
$user = current_user();

// Load the quote and confirm it belongs to this client and is still pending.
$stmt = $pdo->prepare(
    "SELECT q.*, c.user_id AS client_user_id
       FROM quotations q
       JOIN customers c ON c.id = q.customer_id
      WHERE q.id = ?"
);
$stmt->execute([$quotationId]);
$q = $stmt->fetch();

if ($q && $q['status'] === 'Sent' && (int) $q['client_user_id'] === (int) $user['id']) {
    $pdo->prepare("UPDATE quotations SET status = 'Accepted' WHERE id = ?")->execute([$quotationId]);

    if ($q['project_id']) {
        add_project_update((int) $q['project_id'], 'Client accepted the quotation. Awaiting final approval.', false);
    }
    notify([
        'target_role'  => 'Admin',
        'type'         => 'quote_decision',
        'title'        => 'Quotation accepted by client',
        'message'      => "{$q['project_name']} ({$q['quote_code']})",
        'link'         => '../admin/quotations.php',
        'severity'     => 'info',
        'project_id'   => $q['project_id'],
        'quotation_id' => $quotationId,
    ]);
    log_audit('Quotations', "Accepted quotation {$q['quote_code']}", $q['project_name']);
}

header("Location: my_projects.php?accepted=1");
exit;
