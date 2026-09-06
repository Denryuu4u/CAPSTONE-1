<?php
/**
 * Advance / change a project's phase (from the monitoring view modal).
 * POST: project_id, status (a valid phase key)
 * Updates status + progress, posts an auto update, notifies the client.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('monitoring', true); // back-office only
require_once __DIR__ . '/../includes/project_status.php';
header('Content-Type: application/json; charset=utf-8');

function ups_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$projectId = (int) ($_POST['project_id'] ?? 0);
$status    = trim((string) ($_POST['status'] ?? ''));

$valid = array_keys(project_statuses());          // 10 phases + on_hold/rejected
if ($projectId <= 0 || !in_array($status, $valid, true)) ups_fail('Invalid request.');

// Progress per phase (off-track statuses keep the current value).
$progressMap = [
    'quote_submitted' => 0, 'approved' => 10, 'production' => 30, 'mockup' => 45,
    'delivery' => 60, 'installation' => 75, 'quality_check' => 85, 'punchlist' => 92,
    'final_approval' => 97, 'completed' => 100,
];

$pdo = db();
ensure_completion_columns();
$row = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$row->execute([$projectId]);
$project = $row->fetch();
if (!$project) ups_fail('Project not found.', 404);

// ── Phase lock: a project can only move forward through the ordered phases.
// Once advanced it can't be sent back (e.g. Approved → Quote Submitted).
// Off-track statuses (on_hold/rejected) have no index and are exempt.
$currentIdx = project_step_index($project['status']);
$newIdx     = project_step_index($status);
if ($currentIdx !== null && $newIdx !== null && $newIdx < $currentIdx) {
    ups_fail('This project is already at a later phase and can\'t be moved back.');
}

// Is this transition the one that marks the project completed?
$isCompleting = ($status === 'completed' && $project['status'] !== 'completed');

try {
    if (isset($progressMap[$status])) {
        $pdo->prepare("UPDATE projects SET status = ?, progress = ? WHERE id = ?")
            ->execute([$status, $progressMap[$status], $projectId]);
    } else {
        $pdo->prepare("UPDATE projects SET status = ? WHERE id = ?")->execute([$status, $projectId]);
    }

    $label = project_status_label($status);
    // On completion, skip the generic client notice — we send a tailored
    // "please confirm" notice below instead.
    add_project_update($projectId, "Project status updated to \"{$label}\".", !$isCompleting);
    log_audit('Monitoring', "Updated {$project['project_code']} status to {$label}", $project['project_name']);

    if ($isCompleting) {
        // Open the confirmation window and reset any prior confirmation.
        $pdo->prepare("UPDATE projects SET completion_notified_at = NOW(), client_confirmed_at = NULL WHERE id = ?")
            ->execute([$projectId]);
        $clientId = project_client_user_id($projectId);
        if ($clientId) {
            notify([
                'user_id'    => $clientId,
                'type'       => 'status_update',
                'title'      => 'Project completed — please confirm',
                'message'    => "{$project['project_name']} is marked completed. Please confirm you've received it.",
                'link'       => "my_projects.php?view={$projectId}",
                'severity'   => 'warning',
                'project_id' => $projectId,
            ]);
        }
    }

    echo json_encode(['ok' => true, 'status' => $status, 'label' => $label, 'completing' => $isCompleting]);
} catch (Throwable $e) {
    ups_fail('Server error: ' . $e->getMessage(), 500);
}
