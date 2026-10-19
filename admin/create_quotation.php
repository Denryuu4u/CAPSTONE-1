<?php
/**
 * Create + send a cost quotation for a project (from the Create Cost Quotation
 * modal in project-requests.php).
 *
 * POST (application/x-www-form-urlencoded or FormData):
 *   project_id, customer_id, request_id (optional),
 *   items (JSON: [{description, qty, unit_cost}]),
 *   markup_pct, contingency_pct, service_pct, protection_pct,
 *   substrate, out_of_town_pct, special_works, accessories, notes
 *   (labor is derived server-side as 50% of the material total;
 *    out_of_town_pct is applied as a % of the material total)
 *
 * Inserts quotations (status 'Sent') + quotation_items, marks the request
 * 'Quotation Sent', notifies the client, logs audit. Returns JSON.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/quotation_design.php';
require_page('project_requests', true); // back-office only (blocks clients)
header('Content-Type: application/json; charset=utf-8');

function q_fail(string $msg, int $code = 400, array $details = []): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg] + $details);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') q_fail('POST required.', 405);

require_project_csrf();
ensure_project_features();
$designPath = null;
$quoteCommitted = false;
$pdo        = db();
$projectId  = (int) ($_POST['project_id'] ?? 0);
$customerId = (int) ($_POST['customer_id'] ?? 0);
$requestId  = ($_POST['request_id'] ?? '') !== '' ? (int) $_POST['request_id'] : null;
$notesInput = $_POST['notes'] ?? '';
if (!is_string($notesInput)) q_fail('Enter text for Notes/Price justification.');
$notes = trim($notesInput);
if (mb_strlen($notes) > 4000) q_fail('Notes/Price justification must be 4,000 characters or fewer.');

// Target completion date (YYYY-MM-DD) — required.
$targetDate = trim((string) ($_POST['target_completion'] ?? ''));
if ($targetDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) {
    q_fail('A valid target completion date is required.');
}

$num = fn($k, $d = 0) => is_numeric($_POST[$k] ?? null) ? (float) $_POST[$k] : (float) $d;
$markup   = $num('markup_pct', 15);
$conting  = $num('contingency_pct', 5);
$service  = $num('service_pct', 10);
$protect  = $num('protection_pct', 3);
$outOfTownPct = $num('out_of_town_pct');
$substrate = $num('substrate');
$special  = $num('special_works');
$access   = $num('accessories');

$items = json_decode($_POST['items'] ?? '[]', true);
if (!is_array($items) || !count($items)) q_fail('Add at least one costing line item.');

if ($projectId <= 0) q_fail('Missing project.');

require_project_writable($projectId);

// Resolve customer + project name from the project if not supplied.
$proj = $pdo->prepare("SELECT project_name, customer_id, request_id, installation_address FROM projects WHERE id = ?");
$proj->execute([$projectId]);
$projRow = $proj->fetch();
if (!$projRow) q_fail('Project not found.');
$customerId = (int) $projRow['customer_id'];
$requestId = $projRow['request_id'] ? (int) $projRow['request_id'] : null;
$projectName = $projRow['project_name'];

// Recompute totals server-side (never trust the client).
$materialTotal = 0.0;
$clean = [];
foreach ($items as $it) {
    $desc = trim((string) ($it['description'] ?? ''));
    if ($desc === '') continue;
    $qty  = (float) ($it['qty'] ?? 0);
    $unit = (float) ($it['unit_cost'] ?? 0);
    $line = round($qty * $unit, 2);
    $materialTotal += $line;
    $clean[] = ['description' => $desc, 'qty' => $qty, 'unit_cost' => $unit, 'line_total' => $line];
}
if (!count($clean)) q_fail('Add at least one valid line item.');

$labor      = round($materialTotal * 0.5, 2);  // labor is 50% of the material total
$markupAmt  = $materialTotal * $markup  / 100;
$contAmt    = $materialTotal * $conting / 100;
$serviceAmt = $materialTotal * $service / 100;
$protectAmt = $materialTotal * $protect / 100;
$outOfTownAmt = $materialTotal * $outOfTownPct / 100;  // out of town is a % of material
$total = round($materialTotal + $labor + $markupAmt + $contAmt + $serviceAmt + $protectAmt
              + $substrate + $outOfTownAmt + $special + $access, 2);

// Use the linked request's saved budget, never an amount supplied by the browser.
if ($requestId) {
    $budgetQuery = $pdo->prepare('SELECT budget FROM project_requests WHERE id=?');
    $budgetQuery->execute([$requestId]);
    $budget = $budgetQuery->fetchColumn();
    if ($budget !== false && $budget !== null && $budget !== '' && (float)$budget > 0) {
        $excessCents = (int)round($total * 100) - (int)round((float)$budget * 100);
        if ($excessCents > 0 && $notes === '') {
            q_fail('This quotation is ' . peso($excessCents / 100) . ' above the customer budget. Enter Notes/Price justification before sending.', 422, ['field' => 'notes']);
        }
        if ($excessCents > 500000 && ($_POST['confirm_over_budget'] ?? '') !== '1') {
            q_fail("The created cost quotation is way above the customer budget.\n\nCustomer budget: " . peso((float)$budget)
                . "\nQuotation total: " . peso($total)
                . "\nAmount over budget: " . peso($excessCents / 100)
                . "\n\nSend anyway?", 409, ['confirm_over_budget' => true]);
        }
    }
}

try {
    $designPath = save_quotation_design_upload($_FILES['design_image'] ?? []);
    $pdo->beginTransaction();

    $quoteCode = next_code('QT');
    $validUntil = date('Y-m-d', strtotime('+30 days'));

    $stmt = $pdo->prepare(
        "INSERT INTO quotations
           (quote_code, customer_id, request_id, project_id, project_name, date_created, valid_until,
            status, markup_pct, contingency_pct, service_pct, protection_pct,
            labor_cost, substrate, out_of_town_pct, special_works, accessories,
            material_total, total_amount, notes, installation_address, created_by)
         VALUES
           (:code,:cust,:req,:proj,:pname,CURDATE(),:valid,
            'Sent',:markup,:cont,:service,:protect,
            :labor,:substrate,:outoftown,:special,:access,
            :mattot,:total,:notes,:instaddr,:by)"
    );
    $stmt->execute([
        ':code' => $quoteCode, ':cust' => $customerId, ':req' => $requestId, ':proj' => $projectId,
        ':pname' => $projectName, ':valid' => $validUntil,
        ':markup' => $markup, ':cont' => $conting, ':service' => $service, ':protect' => $protect,
        ':labor' => $labor, ':substrate' => $substrate, ':outoftown' => $outOfTownPct,
        ':special' => $special, ':access' => $access,
        ':mattot' => round($materialTotal, 2), ':total' => $total, ':notes' => ($notes !== '' ? $notes : null),
        ':instaddr' => $projRow['installation_address'] ?? null, // where the job is installed
        ':by' => current_user()['id'] ?? null,
    ]);
    $quoteId = (int) $pdo->lastInsertId();
    if ($designPath) $pdo->prepare('UPDATE quotations SET design_path=? WHERE id=?')->execute([$designPath, $quoteId]);

    $itemStmt = $pdo->prepare(
        "INSERT INTO quotation_items (quotation_id, item, description, qty, unit_cost, line_total, sort_order)
         VALUES (?,?,?,?,?,?,?)"
    );
    foreach ($clean as $i => $c) {
        $itemStmt->execute([$quoteId, $c['description'], $c['description'], $c['qty'], $c['unit_cost'], $c['line_total'], $i]);
    }

    // Mark the request as quoted.
    if ($requestId) {
        $pdo->prepare("UPDATE project_requests SET status = 'Quotation Sent' WHERE id = ?")->execute([$requestId]);
    }

    // Persist the (possibly admin-set/adjusted) target completion date.
    if ($targetDate !== '') {
        $pdo->prepare("UPDATE projects SET target_completion = ? WHERE id = ?")->execute([$targetDate, $projectId]);
        if ($requestId) {
            $pdo->prepare("UPDATE project_requests SET target_completion = ? WHERE id = ?")->execute([$targetDate, $requestId]);
        }
    }

    $pdo->commit();
    $quoteCommitted = true;

    // Notify the client + audit.
    $clientId = project_client_user_id($projectId);
    if ($clientId) {
        notify([
            'user_id'      => $clientId,
            'type'         => 'quote_decision',
            'title'        => 'Quotation ready for your review',
            'message'      => "{$projectName} — " . peso($total),
            'link'         => "my_projects.php?view={$projectId}",
            'severity'     => 'warning',
            'project_id'   => $projectId,
            'quotation_id' => $quoteId,
        ]);
    }
    log_audit('Quotations', "Sent quotation {$quoteCode}", "{$projectName} — " . peso($total));

    echo json_encode(['ok' => true, 'quote_code' => $quoteCode, 'total' => $total]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    if (!$quoteCommitted) remove_unsaved_design($designPath);
    q_fail($e instanceof InvalidArgumentException ? $e->getMessage() : app_error_message($e, 'The quotation could not be saved. Please try again.'), $e instanceof InvalidArgumentException ? 400 : 500);
}
