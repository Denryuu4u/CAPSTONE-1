<?php
/**
 * Client confirms a completed project ("Order received" style).
 * POST: project_id
 * Sets projects.client_confirmed_at, logs a timeline entry, notifies the team.
 * Returns JSON { ok } (called via fetch from my_projects.php).
 */
require_once __DIR__ . '/../includes/helpers.php';
header('Content-Type: application/json; charset=utf-8');

function cc_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') cc_fail('POST required.', 405);

ensure_completion_columns();

$projectId = (int) ($_POST['project_id'] ?? 0);
if ($projectId <= 0) cc_fail('Missing project.');

$pdo  = db();
$user = current_user();

// The project must belong to this client, be completed, and not yet confirmed.
$stmt = $pdo->prepare(
    "SELECT p.*, c.user_id AS client_user_id
       FROM projects p
       JOIN customers c ON c.id = p.customer_id
      WHERE p.id = ?"
);
$stmt->execute([$projectId]);
$p = $stmt->fetch();

if (!$p) cc_fail('Project not found.', 404);
if ((int) $p['client_user_id'] !== (int) ($user['id'] ?? 0)) cc_fail('Not your project.', 403);
if ($p['status'] !== 'completed') cc_fail('This project is not marked completed yet.');
if (!empty($p['client_confirmed_at'])) { echo json_encode(['ok' => true, 'already' => true]); exit; }

try {
    $pdo->prepare("UPDATE projects SET client_confirmed_at = NOW() WHERE id = ?")->execute([$projectId]);

    // Timeline entry (no client self-notification) + notify the back office.
    add_project_update($projectId, 'Client confirmed the project is completed.', false);
    notify([
        'target_role' => 'Admin',
        'type'        => 'status_update',
        'title'       => 'Client confirmed completion',
        'message'     => "{$p['project_name']} ({$p['project_code']})",
        'link'        => 'monitoring.php',
        'severity'    => 'info',
        'project_id'  => $projectId,
    ]);
    log_audit('Monitoring', "Client confirmed completion of {$p['project_code']}", $p['project_name']);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    cc_fail('Server error: ' . $e->getMessage(), 500);
}
