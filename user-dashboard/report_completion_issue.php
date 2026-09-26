<?php
/**
 * Client reports that a completed project wasn't done properly (a dispute).
 * Instead of confirming completion, they describe the problem. This:
 *   - marks the project disputed (projects.completion_issue_at), which pauses the
 *     7-day auto-confirm so it won't be force-confirmed while the issue is open;
 *   - records the description on the timeline as a client message (is_client=1),
 *     so the back office sees it in Monitoring;
 *   - notifies the team, linking to that project's monitoring view.
 * POST: project_id, body   →   JSON { ok }
 */
require_once __DIR__ . '/../includes/helpers.php';
require_login();
header('Content-Type: application/json; charset=utf-8');

function rci_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') rci_fail('POST required.', 405);

$user = current_user();
$uid  = (int) ($user['id'] ?? 0);
$pid  = (int) ($_POST['project_id'] ?? 0);
$body = trim((string) ($_POST['body'] ?? ''));

if (!$uid) rci_fail('Not signed in.', 401);
if ($pid <= 0) rci_fail('Missing project.');
if ($body === '') rci_fail('Please describe what went wrong.');
if (function_exists('mb_substr')) $body = mb_substr($body, 0, 1000);

ensure_completion_columns();
ensure_client_comment_column();

$pdo = db();

// The project must belong to this client, be completed, and not yet confirmed.
$stmt = $pdo->prepare(
    "SELECT p.project_name, p.project_code, p.status, p.client_confirmed_at, p.completion_issue_at
       FROM projects p
       JOIN customers c ON c.id = p.customer_id
      WHERE p.id = ? AND c.user_id = ? LIMIT 1"
);
$stmt->execute([$pid, $uid]);
$p = $stmt->fetch();

if (!$p) rci_fail('No access to this project.', 403);
if ($p['status'] !== 'completed') rci_fail('This project is not marked completed.');
if (!empty($p['client_confirmed_at'])) rci_fail('You already confirmed this project as completed.');
if (!empty($p['completion_issue_at'])) rci_fail('You have already reported an issue for this project. Our team is reviewing it.');

try {
    $name = trim((string) ($user['full_name'] ?? '')) ?: 'Client';

    // A reported issue takes the project OUT of "Completed" and back to Final Approval
    // (its pre-completion phase) so it's under review — this also re-enables the admin's
    // "Complete" action, letting them fix it and re-submit completion to the client.
    $pdo->prepare("UPDATE projects SET completion_issue_at = NOW(), status = 'final_approval', prev_status = NULL, progress = 97 WHERE id = ?")
        ->execute([$pid]);

    // Log the report as a client message so it shows in Monitoring's message thread.
    $pdo->prepare(
        "INSERT INTO project_updates (project_id, author_id, author_name, update_text, is_client)
         VALUES (?,?,?,?,1)"
    )->execute([$pid, $uid, $name, "Reported an issue with the completed project: " . $body]);

    // System note so the admin timeline shows the project moved back for review.
    $pdo->prepare(
        "INSERT INTO project_updates (project_id, author_id, author_name, update_text)
         VALUES (?, NULL, 'System', 'Project returned to Final Approval for review after a client-reported issue.')"
    )->execute([$pid]);

    notify([
        'target_role' => 'Admin',
        'type'        => 'status_update',
        'title'       => 'Client reported a completion issue',
        'message'     => $p['project_name'] . ' (' . $p['project_code'] . '): ' . mb_substr($body, 0, 80),
        'link'        => 'monitoring.php?project=' . urlencode($p['project_code']) . '&open=view',
        'severity'    => 'warning',
        'project_id'  => $pid,
    ]);
    log_audit('Monitoring', "Client reported a completion issue on {$p['project_code']}", $name);

    echo json_encode(['ok' => true, 'reported' => date('M d, Y')]);
} catch (Throwable $e) {
    rci_fail('Server error: ' . $e->getMessage(), 500);
}
