<?php
/** Project chat endpoint (back office). GET ?project_id= → messages; POST body → send. */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/chat.php';
require_page('monitoring', true); // back-office roles only (JSON 403 otherwise)
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

    // Notify the project's client of the new message.
    try {
        $cu = db()->prepare("SELECT c.user_id FROM projects p JOIN customers c ON c.id = p.customer_id WHERE p.id = ?");
        $cu->execute([$pid]);
        $clientUid = (int) $cu->fetchColumn();
        if ($clientUid) {
            notify([
                'user_id'  => $clientUid,
                'type'     => 'system',
                'title'    => 'New message from ' . company_name(),
                'message'  => mb_substr(trim($body), 0, 90),
                'link'     => 'my_projects.php',
                'severity' => 'info',
                'project_id' => $pid,
            ]);
        }
    } catch (Throwable $e) { /* non-fatal */ }

    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => true, 'messages' => chat_fetch($pid)]);
