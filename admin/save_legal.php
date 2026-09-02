<?php
/**
 * Save a legal document (Terms & Conditions / Privacy Policy) from Settings.
 * Super Admin only. When the content actually changes, the version is bumped —
 * which forces every client to re-accept on their next visit.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/legal.php';
header('Content-Type: application/json; charset=utf-8');

// Editing the legal documents is restricted to Super Admin (tighter than the
// Settings page itself, which Admins may also open). The DEV switcher sets a
// real role in session, so this holds in DEV_MODE too.
if (current_role() !== 'Super Admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only a Super Admin can edit legal documents.']);
    exit;
}

$key   = trim((string) ($_POST['doc_key'] ?? ''));
$title = trim((string) ($_POST['title'] ?? ''));
$body  = trim((string) ($_POST['body'] ?? ''));

if (!in_array($key, LEGAL_KEYS, true)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Unknown document.']);
    exit;
}
if ($title === '' || $body === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Title and content are required.']);
    exit;
}

try {
    $cur     = legal_doc($key);
    $changed = !$cur || $cur['title'] !== $title || $cur['body'] !== $body;
    $version = (int) ($cur['version'] ?? 0) + ($changed ? 1 : 0);

    db()->prepare(
        "UPDATE legal_documents
            SET title = ?, body = ?, version = ?, updated_by = ?, updated_at = NOW()
          WHERE doc_key = ?"
    )->execute([$title, $body, $version, current_user()['id'] ?? null, $key]);

    if ($changed) {
        log_audit('Settings', "Updated {$title} (now v{$version})", 'All clients must re-accept.');
        // Nudge every client to review the updated document.
        notify([
            'target_role' => 'Client',
            'type'        => 'system',
            'title'       => "Updated {$title}",
            'message'     => "Please review and accept our updated {$title}.",
            'link'        => 'agreements.php',
            'severity'    => 'info',
        ]);
    }

    echo json_encode(['ok' => true, 'version' => $version, 'changed' => $changed]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
