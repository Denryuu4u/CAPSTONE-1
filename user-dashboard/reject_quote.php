<?php
/**
 * Client rejects a quotation (from my_projects.php / project_detail.php).
 * Sets the quotation to 'Rejected' and the project to 'rejected'.
 */
require_once __DIR__ . '/../includes/helpers.php';

$quotationId = (int) ($_POST['quotation_id'] ?? $_GET['quotation_id'] ?? 0);
if ($quotationId <= 0) { header('Location: my_projects.php'); exit; }

$pdo  = db();
$user = current_user();

$stmt = $pdo->prepare(
    "SELECT q.*, c.user_id AS client_user_id
       FROM quotations q
       JOIN customers c ON c.id = q.customer_id
      WHERE q.id = ?"
);
$stmt->execute([$quotationId]);
$q = $stmt->fetch();

if ($q && in_array($q['status'], ['Sent', 'Accepted'], true) && (int) $q['client_user_id'] === (int) $user['id']) {
    $pdo->beginTransaction();
    $pdo->prepare("UPDATE quotations SET status = 'Rejected' WHERE id = ?")->execute([$quotationId]);
    if ($q['project_id']) {
        $pdo->prepare("UPDATE projects SET status = 'rejected' WHERE id = ?")->execute([$q['project_id']]);
    }
    $pdo->commit();

    if ($q['project_id']) {
        add_project_update((int) $q['project_id'], 'Client rejected the quotation.', false);
    }
    notify([
        'target_role'  => 'Admin',
        'type'         => 'quote_rejected',
        'title'        => 'Quotation rejected by client',
        'message'      => "{$q['project_name']} ({$q['quote_code']})",
        'link'         => '../admin/quotations.php',
        'severity'     => 'danger',
        'project_id'   => $q['project_id'],
        'quotation_id' => $quotationId,
    ]);
    log_audit('Quotations', "Rejected quotation {$q['quote_code']}", $q['project_name']);
}

header('Location: my_projects.php?rejected=1');
exit;
