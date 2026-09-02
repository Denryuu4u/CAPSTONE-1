<?php
/**
 * Mark the current user's notifications as read (all, or one by ?id / POST id).
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json; charset=utf-8');

$u    = current_user();
$uid  = $u['id']   ?? 0;
$role = $u['role'] ?? '';
$id   = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);

try {
    if ($id > 0) {
        db()->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND (user_id = ? OR user_id IS NULL)")
            ->execute([$id, $uid]);
    } else {
        db()->prepare(
            "UPDATE notifications SET is_read = 1, read_at = NOW()
              WHERE is_read = 0 AND (user_id = :uid OR (user_id IS NULL AND target_role = :role))"
        )->execute([':uid' => $uid, ':role' => $role]);
    }
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
