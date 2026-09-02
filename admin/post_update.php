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

// Optional image attachment.
$attachment = null;
if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) && $_FILES['image']['size'] <= 8 * 1024 * 1024) {
        $safe = 'upd' . $projectId . '_' . uniqid() . '.' . $ext;
        if (move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../uploads/' . $safe)) {
            $attachment = 'uploads/' . $safe;
        }
    }
}

if ($projectId <= 0 || ($text === '' && !$attachment)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Add a message or an image.']);
    exit;
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
