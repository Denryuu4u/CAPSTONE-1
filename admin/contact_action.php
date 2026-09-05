<?php
/**
 * Actions on contact messages from the Messages tab in customers.php.
 * POST: id, action = read | unread | delete
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/contact.php';
require_page('customers', true); // same gate as the Customers page
header('Content-Type: application/json; charset=utf-8');

$id     = (int) ($_POST['id'] ?? 0);
$action = (string) ($_POST['action'] ?? '');

if ($id <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Invalid message.']); exit; }

try {
    ensure_contact_messages_table();
    if ($action === 'read') {
        db()->prepare("UPDATE contact_messages SET is_read = 1 WHERE id = ?")->execute([$id]);
    } elseif ($action === 'unread') {
        db()->prepare("UPDATE contact_messages SET is_read = 0 WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete') {
        db()->prepare("DELETE FROM contact_messages WHERE id = ?")->execute([$id]);
    } else {
        http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Unknown action.']); exit;
    }
    log_audit('Contact', ucfirst($action) . ' contact message #' . $id);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
