<?php
/** Project chat endpoint (client). GET ?project_id= → messages; POST body → send.
 *  A client may only access the chat of their own projects. */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$pid  = (int) ($_POST['project_id'] ?? $_GET['project_id'] ?? 0);

if (!chat_can_access($pid, $user)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'No access to this project.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = (string) ($_POST['body'] ?? '');
    if (trim($body) === '') { echo json_encode(['ok' => false, 'error' => 'Message is empty.']); exit; }
    chat_send($pid, $user, $body);

    // Notify the back office of the new client message — name the sender (and project).
    try {
        $senderName = trim((string) ($user['full_name'] ?? '')) ?: 'A client';
        $projName = '';
        $ps = db()->prepare("SELECT project_name FROM projects WHERE id = ?");
        $ps->execute([$pid]);
        $projName = (string) $ps->fetchColumn();
        notify([
            'target_role' => 'Admin',
            'type'        => 'system',
            'title'       => 'New message from ' . $senderName,
            'message'     => ($projName !== '' ? $projName . ': ' : '') . mb_substr(trim($body), 0, 80),
            'link'        => 'monitoring.php',
            'severity'    => 'info',
            'project_id'  => $pid,
        ]);
    } catch (Throwable $e) { /* non-fatal */ }

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => true, 'messages' => chat_fetch($pid)]);
