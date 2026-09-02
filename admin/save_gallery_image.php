<?php
/**
 * Upload one or more design-gallery images (admin Settings → Design Gallery).
 * These appear on index.php and user-dashboard/request_quote.php.
 *
 * POST (multipart): gallery_files[]  (jpg/jpeg/png/gif/webp, <= 8MB each)
 * Returns JSON { ok, images: [{id, file_path, label}] }.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true); // Super Admin / Admin only
header('Content-Type: application/json; charset=utf-8');

function gi_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (empty($_FILES['gallery_files']) || !is_array($_FILES['gallery_files']['name'])) {
    gi_fail('No files uploaded.');
}

$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$destDir = __DIR__ . '/../uploads/gallery';
if (!is_dir($destDir)) @mkdir($destDir, 0777, true);

$pdo = db();
$maxOrder = (int) $pdo->query("SELECT COALESCE(MAX(sort_order),0) FROM gallery_images")->fetchColumn();

$added = [];
$names = $_FILES['gallery_files']['name'];
foreach ($names as $i => $origName) {
    if (($_FILES['gallery_files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) continue;
    if ((int) ($_FILES['gallery_files']['size'][$i] ?? 0) > 8 * 1024 * 1024) continue;

    $safe = 'g_' . uniqid() . '.' . $ext;
    $tmp  = $_FILES['gallery_files']['tmp_name'][$i];
    if (!move_uploaded_file($tmp, $destDir . '/' . $safe)) continue;

    $path  = 'uploads/gallery/' . $safe;
    $label = pathinfo($origName, PATHINFO_FILENAME);
    $maxOrder++;
    $pdo->prepare("INSERT INTO gallery_images (file_path, label, sort_order) VALUES (?,?,?)")
        ->execute([$path, mb_substr($label, 0, 150), $maxOrder]);
    $added[] = ['id' => (int) $pdo->lastInsertId(), 'file_path' => $path, 'label' => $label];
}

if (!count($added)) gi_fail('No valid images were uploaded (allowed: JPG, PNG, GIF, WEBP up to 8MB).');

log_audit('Settings', 'Added ' . count($added) . ' gallery image(s)');
echo json_encode(['ok' => true, 'images' => $added]);
