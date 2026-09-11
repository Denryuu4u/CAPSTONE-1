<?php
/**
 * Post a project update from the monitoring composer.
 * POST: project_id, text
 * Returns JSON with the created update (for optimistic rendering).
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('monitoring', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

$projectId = (int) ($_POST['project_id'] ?? 0);
$text      = trim((string) ($_POST['text'] ?? ''));

function upd_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

// Optional image attachment. When a file IS provided but can't be accepted, we
// report exactly why instead of silently dropping it (which looked like the
// attach feature was "not working").
$attachment = null;
$imgErr = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
$hasFile = !empty($_FILES['image']) && $imgErr !== UPLOAD_ERR_NO_FILE;

if ($hasFile) {
    if ($imgErr === UPLOAD_ERR_INI_SIZE || $imgErr === UPLOAD_ERR_FORM_SIZE) {
        upd_fail('That image is too large for the server to accept. Please use a smaller photo (under 8 MB).');
    }
    if ($imgErr !== UPLOAD_ERR_OK) {
        upd_fail('The image upload did not complete. Please try again.');
    }

    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    // Fall back to the browser-reported MIME type for extension-less names.
    if (!in_array($ext, $allowedExt, true)) {
        $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext = $mimeMap[strtolower((string) ($_FILES['image']['type'] ?? ''))] ?? $ext;
    }
    if (!in_array($ext, $allowedExt, true)) {
        upd_fail('That file type isn\'t supported. Please attach a JPG, PNG, GIF, or WEBP image. (iPhone HEIC photos must be exported as JPG first.)');
    }
    if ((int) $_FILES['image']['size'] > 8 * 1024 * 1024) {
        upd_fail('That image is too large. Please use a photo under 8 MB.');
    }

    $destDir = __DIR__ . '/../uploads';
    if (!is_dir($destDir)) @mkdir($destDir, 0775, true);
    $safe = 'upd' . $projectId . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $destDir . '/' . $safe)) {
        upd_fail('The image could not be saved on the server. Please try again.', 500);
    }
    $attachment = 'uploads/' . $safe;
}

if ($projectId <= 0 || ($text === '' && !$attachment)) {
    upd_fail('Add a message or an image.');
}

try {
    $id = add_project_update($projectId, $text !== '' ? $text : '(image)', true, $attachment);
    $u  = current_user();
    $initials = strtoupper(mb_substr($u['full_name'] ?? 'V', 0, 1));
    log_audit('Monitoring', "Posted an update on project #{$projectId}", mb_strimwidth($text, 0, 80, '…'));

    echo json_encode([
        'ok'     => true,
        'update' => [
            'id'       => $id,
            'author'   => $u['full_name'] ?? 'Vast Solutions',
            'initials' => $initials,
            'time'     => date('M d, Y · g:i A'),
            'text'     => $text !== '' ? $text : '(image)',
            'image'    => $attachment ? '../' . $attachment : null,
        ],
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
