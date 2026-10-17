<?php
/**
 * Client counter-offers on a quotation (from my_projects.php) instead of
 * outright accepting or rejecting. Records a proposed amount + optional comment,
 * sets the quotation to 'Countered', and notifies the admin so they can revise
 * and resend. The client can accept/reject the revised quote afterwards.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_client(); // customers only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: my_projects.php'); exit; }
ensure_counter_offer_columns();

$quotationId = (int) ($_POST['quotation_id'] ?? 0);
$amountRaw   = trim((string) ($_POST['counter_amount'] ?? ''));
$comment     = trim((string) ($_POST['counter_comment'] ?? ''));
$back        = 'my_projects.php';

if ($quotationId <= 0) { header("Location: {$back}"); exit; }

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

// Only the owning client may counter, and only while the quote is still 'Sent'.
if (!$q || (int) $q['client_user_id'] !== (int) $user['id'] || $q['status'] !== 'Sent') {
    header("Location: {$back}?counter=err");
    exit;
}

require_project_writable((int) $q['project_id'], false);
// A revision may have changed the price while we waited for the project lock.
$stmt->execute([$quotationId]); $q = $stmt->fetch();
if (!$q || (int) $q['client_user_id'] !== (int) $user['id'] || $q['status'] !== 'Sent') {
    header("Location: {$back}?counter=err"); exit;
}

$amountText = str_replace(',', '', $amountRaw);
if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $amountText)) { header("Location: {$back}?counter=amount"); exit; }
$amount = (float) $amountText;
$quoteCents = (int) round((float) $q['total_amount'] * 100);
if (!is_finite($amount) || $amount <= 0 || $amount >= (float) $q['total_amount']) { header("Location: {$back}?counter=limit"); exit; }
$amountCents = (int) round($amount * 100);
if ($amountCents >= $quoteCents || $quoteCents - $amountCents > MAX_COUNTER_REDUCTION_CENTS) {
    header("Location: {$back}?counter=limit"); exit;
}
$comment = mb_substr($comment, 0, 1000);
if ($amount <= 0) { header("Location: {$back}?counter=amount"); exit; }

$update = $pdo->prepare(
    "UPDATE quotations
        SET status = 'Countered', counter_amount = ?, counter_comment = NULLIF(?, ''), counter_at = NOW()
      WHERE id = ? AND status = 'Sent' AND total_amount = ?"
);
$update->execute([$amount, $comment, $quotationId, $q['total_amount']]);
if ($update->rowCount() !== 1) { header("Location: {$back}?counter=err"); exit; }

if ($q['project_id']) {
    add_project_update(
        (int) $q['project_id'],
        'Client submitted a counter-offer of ' . peso($amount)
            . ($comment !== '' ? ' — "' . $comment . '"' : ''),
        false // don't notify the client of their own action
    );
}

notify([
    'target_role'  => 'Admin',
    'type'         => 'quote_countered',
    'title'        => 'Counter-offer from client',
    'message'      => "{$q['project_name']} ({$q['quote_code']}) — " . peso($amount),
    'link'         => '../admin/quotations.php',
    'severity'     => 'warning',
    'project_id'   => $q['project_id'],
    'quotation_id' => $quotationId,
]);
log_audit('Quotations', "Counter-offer on {$q['quote_code']} — " . peso($amount), $q['project_name']);

header("Location: {$back}?countered=1");
exit;
