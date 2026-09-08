<?php
/**
 * Upload the company logo (Settings → Company Information → Upload Logo).
 * POST multipart: logo  → JSON {ok, logo: <url>}
 * Super Admin + Admin only.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true);
header('Content-Type: application/json; charset=utf-8');

function logo_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (empty($_FILES['logo']['name'])) logo_fail('No file selected.');
$f = $_FILES['logo'];
if ($f['error'] !== UPLOAD_ERR_OK) logo_fail('Upload failed. Please try again.');
if ($f['size'] > 5 * 1024 * 1024)  logo_fail('Logo is too large (max 5 MB).');

$ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
    logo_fail('Use a JPG, PNG, GIF, WEBP or SVG image.');
}
if ($ext !== 'svg' && @getimagesize($f['tmp_name']) === false) {
    logo_fail('That file is not a valid image.');
}

$destDir = __DIR__ . '/../uploads/logo';
if (!is_dir($destDir)) @mkdir($destDir, 0777, true);
$safe = 'logo_' . bin2hex(random_bytes(4)) . '.' . $ext;
if (!move_uploaded_file($f['tmp_name'], $destDir . '/' . $safe)) {
    logo_fail('Could not save the logo on the server.', 500);
}
$relPath = 'uploads/logo/' . $safe;

try {
    $pdo = db();
    $pdo->exec("INSERT IGNORE INTO company_settings (id, company_name) VALUES (1, 'Vast Solutions')");
    $prev = (string) $pdo->query("SELECT logo_path FROM company_settings WHERE id = 1")->fetchColumn();
    if ($prev && strpos($prev, 'uploads/logo/') === 0) @unlink(__DIR__ . '/../' . $prev);
    $pdo->prepare("UPDATE company_settings SET logo_path = ? WHERE id = 1")->execute([$relPath]);
    log_audit('Settings', 'Updated company logo');
    echo json_encode(['ok' => true, 'logo' => BASE_URL . '/' . $relPath]);
} catch (Throwable $e) {
    logo_fail('Server error: ' . $e->getMessage(), 500);
}
