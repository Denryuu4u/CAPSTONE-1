<?php
/**
 * Archive or restore a customer or user (soft-delete via is_archived).
 * POST: type = customer|user, id, action = archive|restore, reason (optional)
 */
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

$type   = $_POST['type'] ?? '';
$id     = (int) ($_POST['id'] ?? 0);
$action = $_POST['action'] ?? 'archive';
$reason = trim((string) ($_POST['reason'] ?? ''));

// Archiving users is Super Admin only; everything else is back-office.
require_page($type === 'user' ? 'user_management' : 'customers', true);

// Safeguard: don't let a user archive their own account.
if ($type === 'user' && $id === (int) (current_user()['id'] ?? 0) && $action === 'archive') {
    http_response_code(400); echo json_encode(['ok' => false, 'error' => 'You cannot archive your own account.']); exit;
}

$table = $type === 'customer' ? 'customers' : ($type === 'user' ? 'users' : null);
if (!$table || $id <= 0 || !in_array($action, ['archive', 'restore'], true)) {
    http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Bad request.']); exit;
}

try {
    if ($action === 'archive') {
        db()->prepare("UPDATE {$table} SET is_archived=1, archived_at=NOW(), archived_by=?, archive_reason=? WHERE id=?")
            ->execute([current_user()['id'] ?? null, $reason ?: null, $id]);
    } else {
        db()->prepare("UPDATE {$table} SET is_archived=0, archived_at=NULL, archived_by=NULL, archive_reason=NULL WHERE id=?")
            ->execute([$id]);
    }
    log_audit(ucfirst($type) . 's', ucfirst($action) . "d {$type} #{$id}", $reason ?: null);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
