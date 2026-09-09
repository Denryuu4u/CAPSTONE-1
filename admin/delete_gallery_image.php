<?php
/**
 * Remove one or more design-gallery images (admin Settings → Design Gallery).
 * POST: id (single) OR ids (comma-separated, for multi-select deletion)
 * Removes the DB row(s); deletes the physical file only for admin-uploaded
 * files (under uploads/gallery/), leaving seeded assets on disk.
 * Returns JSON { ok, deleted }.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings', true); // Super Admin / Admin only
header('Content-Type: application/json; charset=utf-8');

// Accept a single id or a comma-separated list of ids.
$ids = [];
if (isset($_POST['ids'])) {
    foreach (explode(',', (string) $_POST['ids']) as $v) {
        $n = (int) trim($v);
        if ($n > 0) $ids[$n] = true;
    }
} elseif (isset($_POST['id'])) {
    $n = (int) $_POST['id'];
    if ($n > 0) $ids[$n] = true;
}
$ids = array_keys($ids);
if (!$ids) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'No images selected.']); exit; }

$pdo = db();
$in  = implode(',', array_fill(0, count($ids), '?'));

try {
    // Fetch paths first so we can clean up admin-uploaded files after deleting.
    $sel = $pdo->prepare("SELECT id, file_path FROM gallery_images WHERE id IN ($in)");
    $sel->execute($ids);
    $rows = $sel->fetchAll();
    if (!$rows) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Image(s) not found.']); exit; }

    $foundIds = array_column($rows, 'id');
    $delIn    = implode(',', array_fill(0, count($foundIds), '?'));
    $pdo->prepare("DELETE FROM gallery_images WHERE id IN ($delIn)")->execute($foundIds);

    // Only unlink admin-uploaded files (never the seeded catalog assets).
    foreach ($rows as $r) {
        if (strpos((string) $r['file_path'], 'uploads/gallery/') === 0) {
            $abs = __DIR__ . '/../' . $r['file_path'];
            if (is_file($abs)) @unlink($abs);
        }
    }

    $count = count($foundIds);
    log_audit('Settings', "Removed {$count} gallery image(s)", implode(',', $foundIds));
    echo json_encode(['ok' => true, 'deleted' => $count]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
