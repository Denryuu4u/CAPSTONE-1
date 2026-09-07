<?php
/**
 * Upsert a project's cost-tracking ledger (from the Cost Tracking editor in
 * monitoring.php).
 *
 * POST:
 *   project_id, project_amount, notes,
 *   expenses (JSON: [{label, amount}]),
 *   labor    (JSON: [{phase:'assembly'|'installation', work_date, manpower, rate, gas_toll}])
 *
 * Replaces the child rows wholesale inside a transaction. Returns JSON with the
 * recomputed totals.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tracking.php';
require_page('monitoring', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

function st_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') st_fail('POST required.', 405);

ensure_tracking_tables();
$pdo       = db();
$projectId = (int) ($_POST['project_id'] ?? 0);
$amount    = is_numeric($_POST['project_amount'] ?? null) ? (float) $_POST['project_amount'] : 0.0;
$notes     = trim((string) ($_POST['notes'] ?? ''));

if ($projectId <= 0) st_fail('Missing project.');

$proj = $pdo->prepare("SELECT project_code, project_name FROM projects WHERE id = ?");
$proj->execute([$projectId]);
$projRow = $proj->fetch();
if (!$projRow) st_fail('Project not found.', 404);

$expensesIn = json_decode($_POST['expenses'] ?? '[]', true);
$laborIn    = json_decode($_POST['labor'] ?? '[]', true);
if (!is_array($expensesIn)) $expensesIn = [];
if (!is_array($laborIn))    $laborIn = [];

// Clean + validate.
$expenses = [];
foreach ($expensesIn as $e) {
    $label = trim((string) ($e['label'] ?? ''));
    $amt   = (float) ($e['amount'] ?? 0);
    if ($label === '' && $amt == 0) continue;
    $expenses[] = ['label' => ($label !== '' ? $label : 'Expense'), 'amount' => $amt];
}
$labor = [];
foreach ($laborIn as $l) {
    $phase = (($l['phase'] ?? 'assembly') === 'installation') ? 'installation' : 'assembly';
    $man   = trim((string) ($l['manpower'] ?? ''));
    $rate  = (float) ($l['rate'] ?? 0);
    $gas   = (float) ($l['gas_toll'] ?? 0);
    $date  = trim((string) ($l['work_date'] ?? ''));
    if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = '';
    if ($man === '' && $rate == 0 && $gas == 0) continue;
    $labor[] = ['phase' => $phase, 'work_date' => ($date !== '' ? $date : null),
                'manpower' => ($man !== '' ? $man : '—'), 'rate' => $rate, 'gas_toll' => $gas];
}

try {
    $pdo->beginTransaction();

    // Upsert the header.
    $ts = $pdo->prepare("SELECT id FROM project_tracking WHERE project_id = ?");
    $ts->execute([$projectId]);
    $trackingId = (int) $ts->fetchColumn();

    if ($trackingId) {
        $pdo->prepare("UPDATE project_tracking SET project_amount = ?, notes = ? WHERE id = ?")
            ->execute([$amount, ($notes !== '' ? $notes : null), $trackingId]);
        $pdo->prepare("DELETE FROM tracking_expenses WHERE tracking_id = ?")->execute([$trackingId]);
        $pdo->prepare("DELETE FROM tracking_labor WHERE tracking_id = ?")->execute([$trackingId]);
    } else {
        $pdo->prepare("INSERT INTO project_tracking (project_id, project_amount, notes, created_by) VALUES (?,?,?,?)")
            ->execute([$projectId, $amount, ($notes !== '' ? $notes : null), current_user()['id'] ?? null]);
        $trackingId = (int) $pdo->lastInsertId();
    }

    $ex = $pdo->prepare("INSERT INTO tracking_expenses (tracking_id, label, amount, sort_order) VALUES (?,?,?,?)");
    foreach ($expenses as $i => $e) $ex->execute([$trackingId, $e['label'], $e['amount'], $i]);

    $lb = $pdo->prepare("INSERT INTO tracking_labor (tracking_id, phase, work_date, manpower, rate, gas_toll, sort_order) VALUES (?,?,?,?,?,?,?)");
    foreach ($labor as $i => $l) $lb->execute([$trackingId, $l['phase'], $l['work_date'], $l['manpower'], $l['rate'], $l['gas_toll'], $i]);

    $pdo->commit();

    $totals = tracking_compute($amount, $expenses, $labor);
    log_audit('Monitoring', "Updated cost tracking for {$projRow['project_code']}",
        $projRow['project_name'] . ' — cost ' . peso($totals['project_cost']));

    echo json_encode(['ok' => true, 'totals' => $totals]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    st_fail('Server error: ' . $e->getMessage(), 500);
}
