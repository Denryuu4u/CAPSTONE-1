<?php
/** Revoke (delete) an unused revert code (Super Admin / Admin, from Settings). */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true);
header('Content-Type: application/json; charset=utf-8');

ensure_revert_codes_table();

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Bad request.']); exit; }

try {
    $st = db()->prepare("DELETE FROM revert_codes WHERE id = ?");
    $st->execute([$id]);
    echo json_encode(['ok' => true, 'removed' => $st->rowCount()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
