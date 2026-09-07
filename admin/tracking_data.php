<?php
/**
 * Return a project's cost-tracking ledger as JSON.
 * GET: project_id
 * Used by the Cost Tracking editor (monitoring.php) and the Reports viewer.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tracking.php';
require_page('monitoring', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

$projectId = (int) ($_GET['project_id'] ?? $_POST['project_id'] ?? 0);
if ($projectId <= 0) { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Missing project.']); exit; }

$data = get_project_tracking($projectId);
if ($data === null) { http_response_code(404); echo json_encode(['ok' => false, 'error' => 'Project not found.']); exit; }

echo json_encode(['ok' => true] + $data, JSON_UNESCAPED_UNICODE);
