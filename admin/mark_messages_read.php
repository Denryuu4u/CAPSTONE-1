<?php
/** Mark a project's client messages as read by the back office (clears the View dot). */
require_once __DIR__ . '/../includes/helpers.php';
require_page('monitoring', true);
header('Content-Type: application/json; charset=utf-8');

ensure_client_comment_column();
$pid = (int) ($_POST['project_id'] ?? 0);
if ($pid <= 0) { echo json_encode(['ok' => false]); exit; }

try {
    db()->prepare("UPDATE project_updates SET client_read_at = NOW() WHERE project_id = ? AND is_client = 1 AND client_read_at IS NULL")
        ->execute([$pid]);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
