<?php
/**
 * Remove a design-gallery image (admin Settings → Design Gallery).
 * POST: id
 * Removes the DB row; deletes the physical file only for admin-uploaded
 * files (under uploads/gallery/), leaving seeded assets on disk.
 * Returns JSON { ok }.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true); // Super Admin / Admin only
header('Content-Type: application/json; charset=utf-8');

$id = (int) ($_POST['id'] ?? 0);
if ($id <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Invalid id.']); exit; }

$pdo = db();
$row = $pdo->prepare("SELECT file_path FROM gallery_images WHERE id = ?");
$row->execute([$id]);
$path = $row->fetchColumn();
if ($path === false) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Image not found.']); exit; }

try {
    $pdo->prepare("DELETE FROM gallery_images WHERE id = ?")->execute([$id]);

    // Only unlink admin-uploaded files (never the seeded catalog assets).
    if (strpos($path, 'uploads/gallery/') === 0) {
        $abs = __DIR__ . '/../' . $path;
        if (is_file($abs)) @unlink($abs);
    }

    log_audit('Settings', "Removed gallery image #{$id}", $path);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
