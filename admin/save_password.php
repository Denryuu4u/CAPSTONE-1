<?php
/**
 * Change the signed-in user's password (from the Change Password card in settings.php).
 * POST: current_password, new_password
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings_self', true); // any back-office user changes their own password
header('Content-Type: application/json; charset=utf-8');

function pw_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$me = current_user();
if (empty($me['id'])) pw_fail('Not signed in.', 401);

$current = (string) ($_POST['current_password'] ?? '');
$new     = (string) ($_POST['new_password'] ?? '');

if ($current === '' || $new === '') pw_fail('Enter your current and new password.');
if (strlen($new) < 8)               pw_fail('New password must be at least 8 characters.');

$pdo = db();
$row = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
$row->execute([(int) $me['id']]);
$hash = $row->fetchColumn();

if (!$hash || !password_verify($current, $hash)) {
    pw_fail('Current password is incorrect.');
}

try {
    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
        ->execute([$newHash, (int) $me['id']]);
    log_audit('Settings', 'Changed account password');
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    pw_fail('Server error: ' . $e->getMessage(), 500);
}
