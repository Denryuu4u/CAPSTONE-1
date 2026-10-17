<?php
require_once __DIR__ . '/../includes/helpers.php';
require_page('monitoring', true);
require_project_csrf();
if (!in_array(current_role(), ['Admin', 'Super Admin'], true)) project_control_fail('Only Admin and Super Admin can pause or resume projects.', 403);
// Re-check the account so an old session cannot retain administrative privileges.
$actor = db()->prepare('SELECT role, status, is_archived FROM users WHERE id = ?');
$actor->execute([current_user()['id']]); $actor = $actor->fetch();
if (!$actor || !in_array($actor['role'], ['Admin', 'Super Admin'], true) || $actor['status'] !== 'Active' || $actor['is_archived']) project_control_fail('An active administrator account is required.', 403);
$id = (int) ($_POST['project_id'] ?? 0);
$state = $_POST['state'] ?? '';
if ($id <= 0 || !in_array($state, ['active', 'paused'], true)) project_control_fail('Invalid project state.', 400);
ensure_project_features();
ensure_prev_status_column();
lock_project($id);
$pdo = db();
// Acquire capacity before starting the transaction so the count is current even
// when another project for this customer has just changed phase.
$capacityRow = $pdo->prepare('SELECT status, paused_status, prev_status FROM projects WHERE id=?');
$capacityRow->execute([$id]); $capacityProject = $capacityRow->fetch();
if ($capacityProject) {
    $capacityTarget = $capacityProject['status'];
    if ($state === 'paused' && !in_array(project_status_key($capacityTarget), ['completed', 'rejected'], true)) $capacityTarget = 'on_hold';
    elseif ($state === 'active' && project_status_key($capacityTarget) === 'on_hold') $capacityTarget = $capacityProject['paused_status'] ?: $capacityProject['prev_status'] ?: $capacityTarget;
    enforce_project_capacity($id, $capacityTarget);
}
try {
    $pdo->beginTransaction();
    $st = $pdo->prepare('SELECT * FROM projects WHERE id = ? FOR UPDATE'); $st->execute([$id]); $p = $st->fetch();
    if (!$p) { $pdo->rollBack(); project_control_fail('Project not found.', 404); }
    $paused = project_status_key($p['status']) === 'on_hold';
    if (($state === 'paused') === $paused) { $pdo->commit(); echo json_encode(['ok' => true]); exit; }
    if ($state === 'paused') {
        if (in_array(project_status_key($p['status']), ['completed', 'rejected'], true)) {
            $pdo->rollBack(); project_control_fail('A closed project cannot be paused.');
        }
        $pdo->prepare("UPDATE projects SET paused_status = status, status = 'on_hold' WHERE id = ?")->execute([$id]);
        $message = 'Project paused by an administrator. Changes are frozen until it is resumed.';
    } else {
        $phase = $p['paused_status'] ?: $p['prev_status'];
        if (!array_key_exists((string) $phase, project_phases())) {
            $pdo->rollBack(); project_control_fail('The previous phase is unknown. Please have the project record reviewed before resuming.');
        }
        $pdo->prepare('UPDATE projects SET status = ?, paused_status = NULL WHERE id = ?')->execute([$phase, $id]);
        $message = 'Project resumed at ' . project_status_label($phase) . '.';
    }
    add_project_update($id, $message);
    log_audit('Monitoring', $message, $p['project_code']);
    $pdo->commit();
    header('Content-Type: application/json'); echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    project_control_fail(app_error_message($e, 'The project state could not be saved. Please try again.'), 500);
}
