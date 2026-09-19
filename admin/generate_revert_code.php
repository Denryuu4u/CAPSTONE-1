<?php
/** Generate a single-use revert authorization code (Super Admin / Admin, from Settings). */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true); // Super Admin / Admin only
header('Content-Type: application/json; charset=utf-8');

ensure_revert_codes_table();

try {
    $code = generate_revert_code();
    db()->prepare("INSERT INTO revert_codes (code, created_by) VALUES (?, ?)")
        ->execute([$code, current_user()['id'] ?? null]);
    log_audit('Settings', 'Generated a revert authorization code', $code);
    echo json_encode(['ok' => true, 'code' => $code, 'id' => (int) db()->lastInsertId()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
