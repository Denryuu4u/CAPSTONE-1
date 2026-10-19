<?php
/** Serve request references and attachments only to staff or the owning customer. */
require_once __DIR__ . '/includes/helpers.php';
if (!current_user()) { http_response_code(401); exit('Sign in to view the design.'); }
$id = (int) ($_GET['request_id'] ?? 0);
$st = db()->prepare('SELECT r.reference_design, c.user_id FROM project_requests r JOIN customers c ON c.id=r.customer_id WHERE r.id=?');
$st->execute([$id]);
$request = $st->fetch();
function request_design_missing(): void { http_response_code(404); exit('This design file is no longer available.'); }
if (!$request || (!in_array(current_role(), BACKOFFICE_ROLES, true) && !(current_role() === 'Client' && (int)$request['user_id'] === (int)current_user()['id']))) request_design_missing();
$path = ''; $name = '';
if (isset($_GET['file_id'])) {
    $st = db()->prepare('SELECT file_path, file_name FROM request_files WHERE id=? AND request_id=?');
    $st->execute([(int)$_GET['file_id'], $id]);
    $file = $st->fetch();
    if (!$file || !preg_match('~^uploads/[a-zA-Z0-9_.-]+\.(pdf|dwg|skp|jpg|jpeg|png)$~iD', $file['file_path'])) request_design_missing();
    $path = $file['file_path']; $name = $file['file_name'];
} else {
    $reference = (string)($request['reference_design'] ?? '');
    if ($reference === '' || basename($reference) !== $reference) request_design_missing();
    foreach (db()->query('SELECT file_path FROM gallery_images') as $image) {
        if (basename($image['file_path']) === $reference && preg_match('~^(uploads/gallery|cabinet_image)/[a-zA-Z0-9_.-]+\.(jpg|jpeg|png|gif|webp)$~iD', $image['file_path'])) {
            $path = $image['file_path']; break;
        }
    }
    // Requests created before the editable gallery used the bundled sample images.
    if (!$path && preg_match('/^cabinet_image[0-9]+\.jpg$/D', $reference)) $path = 'cabinet_image/' . $reference;
    $name = $reference;
}
$absolute = $path ? realpath(__DIR__ . '/' . $path) : false;
$expectedDirectory = $path ? realpath(__DIR__ . '/' . dirname($path)) : false;
if (!$absolute || !$expectedDirectory || dirname($absolute) !== $expectedDirectory || !is_file($absolute)) request_design_missing();
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($absolute);
$imageTypes = ['jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif','webp'=>'image/webp'];
$inline = isset($imageTypes[$extension]) && $mime === $imageTypes[$extension];
if (isset($imageTypes[$extension]) && !$inline) request_design_missing();
if ($extension === 'pdf' && $mime === 'application/pdf') $inline = true;
header('Content-Type: ' . ($inline ? $mime : 'application/octet-stream'));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
header("Content-Disposition: " . ($inline ? 'inline' : 'attachment') . "; filename*=UTF-8''" . rawurlencode($name));
readfile($absolute);
