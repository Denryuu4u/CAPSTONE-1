<?php
/**
 * Client posts a message/comment on one of their own projects. It's added to the
 * project timeline (marked as a client message) and the back office is notified
 * so they can read it in Monitoring. POST: project_id, body
 */
require_once __DIR__ . '/../includes/helpers.php';
require_client(true);
header('Content-Type: application/json; charset=utf-8');

$user = current_user();
$uid  = $user['id'] ?? 0;
$pid  = (int) ($_POST['project_id'] ?? 0);
$body = trim((string) ($_POST['body'] ?? ''));

if (!$uid) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Not signed in.']); exit; }
if ($pid <= 0 || $body === '') { echo json_encode(['ok' => false, 'error' => 'Message is empty.']); exit; }
if (function_exists('mb_substr')) $body = mb_substr($body, 0, 1000);

// The project must belong to this client.
$own = db()->prepare(
    "SELECT p.project_name, p.project_code, p.status FROM projects p
       JOIN customers c ON c.id = p.customer_id
      WHERE p.id = ? AND c.user_id = ? LIMIT 1"
);
$own->execute([$pid, $uid]);
$proj = $own->fetch();
if (!$proj) { http_response_code(403); echo json_encode(['ok' => false, 'error' => 'No access to this project.']); exit; }
$projName = $proj['project_name'];
$projCode = $proj['project_code'];

// Messaging closes once the project is finished.
if (in_array($proj['status'], ['completed', 'rejected'], true)) {
    echo json_encode(['ok' => false, 'error' => 'This project is closed — messaging is no longer available.']);
    exit;
}

ensure_client_comment_column();

// Rate limit (anti-spam): a short cooldown between messages + a rolling daily cap
// per project. All time math is done in MySQL so it's timezone-consistent.
$COOLDOWN_SECONDS = 20;
$DAILY_CAP        = 10;
$cd = db()->prepare("SELECT COUNT(*) FROM project_updates WHERE project_id=? AND is_client=1 AND author_id=? AND created_at > (NOW() - INTERVAL ? SECOND)");
$cd->execute([$pid, $uid, $COOLDOWN_SECONDS]);
if ((int) $cd->fetchColumn() > 0) {
    echo json_encode(['ok' => false, 'error' => 'Please wait a few seconds before sending another message.']);
    exit;
}
$dc = db()->prepare("SELECT COUNT(*) FROM project_updates WHERE project_id=? AND is_client=1 AND author_id=? AND created_at >= (NOW() - INTERVAL 1 DAY)");
$dc->execute([$pid, $uid]);
if ((int) $dc->fetchColumn() >= $DAILY_CAP) {
    echo json_encode(['ok' => false, 'error' => "You've reached the message limit for this project ({$DAILY_CAP} per day). Please try again later."]);
    exit;
}

try {
    $name = trim((string) ($user['full_name'] ?? '')) ?: 'Client';
    db()->prepare(
        "INSERT INTO project_updates (project_id, author_id, author_name, update_text, is_client)
         VALUES (?,?,?,?,1)"
    )->execute([$pid, $uid, $name, $body]);

    // Let the back office know a client left a message.
    notify([
        'target_role' => 'Admin',
        'type'        => 'system',
        'title'       => 'New message from ' . $name,
        'message'     => $projName . ': ' . mb_substr($body, 0, 80),
        'link'        => 'monitoring.php?project=' . urlencode($projCode) . '&open=view',
        'severity'    => 'info',
        'project_id'  => $pid,
    ]);

    log_audit('Monitoring', "Client message on project #{$pid}", $name);
    echo json_encode([
        'ok'   => true,
        'author'   => $name,
        'initials' => strtoupper(mb_substr($name, 0, 1)),
        'time'     => date('M d, Y · g:i A'),
        'text'     => $body,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
