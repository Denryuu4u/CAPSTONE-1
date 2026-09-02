<?php
/**
 * Create a walk-in project + its cost quotation directly (admin "Create New
 * Project" modal). Unlike create_quotation.php this starts from scratch (no
 * prior client request): it creates the project shell AND the quotation.
 *
 * POST (application/x-www-form-urlencoded):
 *   customer_id, project_name, category, address, target_completion,
 *   items (JSON: [{description, qty, unit_cost}]),
 *   markup_pct, contingency_pct, service_pct, protection_pct,
 *   out_of_town_pct, substrate, special_works, accessories
 *
 * Labor is derived server-side as 50% of the material total.
 * Returns JSON { ok, project_code, quote_code, total }.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('project_requests', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

function sp_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') sp_fail('POST required.', 405);

$pdo         = db();
$customerId  = (int) ($_POST['customer_id'] ?? 0);
$projectName = trim((string) ($_POST['project_name'] ?? ''));
$category    = trim((string) ($_POST['category'] ?? ''));
$address     = trim((string) ($_POST['address'] ?? ''));
$targetDate  = trim((string) ($_POST['target_completion'] ?? ''));
if ($targetDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $targetDate)) $targetDate = '';

if ($customerId <= 0) sp_fail('Select a customer.');
if ($projectName === '') sp_fail('Enter a project name.');

// Confirm the customer exists.
$cust = $pdo->prepare("SELECT id FROM customers WHERE id = ?");
$cust->execute([$customerId]);
if (!$cust->fetchColumn()) sp_fail('Customer not found.');

$num = fn($k, $d = 0) => is_numeric($_POST[$k] ?? null) ? (float) $_POST[$k] : (float) $d;
$markup       = $num('markup_pct', 15);
$conting      = $num('contingency_pct', 5);
$service      = $num('service_pct', 10);
$protect      = $num('protection_pct', 3);
$outOfTownPct = $num('out_of_town_pct');
$substrate    = $num('substrate');
$special      = $num('special_works');
$access       = $num('accessories');

$items = json_decode($_POST['items'] ?? '[]', true);
if (!is_array($items) || !count($items)) sp_fail('Add at least one costing line item.');

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
if (!count($clean)) sp_fail('Add at least one valid line item.');

$labor        = round($materialTotal * 0.5, 2);   // labor is 50% of the material total
$markupAmt    = $materialTotal * $markup  / 100;
$contAmt      = $materialTotal * $conting / 100;
$serviceAmt   = $materialTotal * $service / 100;
$protectAmt   = $materialTotal * $protect / 100;
$outOfTownAmt = $materialTotal * $outOfTownPct / 100;
$total = round($materialTotal + $labor + $markupAmt + $contAmt + $serviceAmt + $protectAmt
              + $substrate + $outOfTownAmt + $special + $access, 2);

try {
    $pdo->beginTransaction();

    // 1) Project shell (walk-in: no client request). Enters at quote_submitted.
    $prjCode = next_code('PRJ');
    $pdo->prepare(
        "INSERT INTO projects
            (project_code, customer_id, project_name, category, description,
             installation_address, target_completion, status, progress)
         VALUES (?,?,?,?,?,?,?, 'quote_submitted', 0)"
    )->execute([
        $prjCode, $customerId, $projectName, ($category ?: null), ($address ?: null),
        ($address ?: null), ($targetDate ?: null),
    ]);
    $projectId = (int) $pdo->lastInsertId();

    // 2) Cost quotation (Sent).
    $quoteCode  = next_code('QT');
    $validUntil = date('Y-m-d', strtotime('+30 days'));
    $stmt = $pdo->prepare(
        "INSERT INTO quotations
           (quote_code, customer_id, request_id, project_id, project_name, category,
            installation_address, date_created, valid_until, status,
            markup_pct, contingency_pct, service_pct, protection_pct,
            labor_cost, substrate, out_of_town_pct, special_works, accessories,
            material_total, total_amount, created_by)
         VALUES
           (:code,:cust,NULL,:proj,:pname,:cat,
            :addr,CURDATE(),:valid,'Sent',
            :markup,:cont,:service,:protect,
            :labor,:substrate,:outoftown,:special,:access,
            :mattot,:total,:by)"
    );
    $stmt->execute([
        ':code' => $quoteCode, ':cust' => $customerId, ':proj' => $projectId,
        ':pname' => $projectName, ':cat' => ($category ?: null), ':addr' => ($address ?: null),
        ':valid' => $validUntil,
        ':markup' => $markup, ':cont' => $conting, ':service' => $service, ':protect' => $protect,
        ':labor' => $labor, ':substrate' => $substrate, ':outoftown' => $outOfTownPct,
        ':special' => $special, ':access' => $access,
        ':mattot' => round($materialTotal, 2), ':total' => $total,
        ':by' => current_user()['id'] ?? null,
    ]);
    $quoteId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        "INSERT INTO quotation_items (quotation_id, item, description, qty, unit_cost, line_total, sort_order)
         VALUES (?,?,?,?,?,?,?)"
    );
    foreach ($clean as $i => $c) {
        $itemStmt->execute([$quoteId, $c['description'], $c['description'], $c['qty'], $c['unit_cost'], $c['line_total'], $i]);
    }

    $pdo->commit();

    // Notify the client if this customer is linked to a user account.
    $clientId = project_client_user_id($projectId);
    if ($clientId) {
        notify([
            'user_id'      => $clientId,
            'type'         => 'quote_decision',
            'title'        => 'Quotation ready for your review',
            'message'      => "{$projectName} — " . peso($total),
            'link'         => "my_projects.php",
            'severity'     => 'warning',
            'project_id'   => $projectId,
            'quotation_id' => $quoteId,
        ]);
    }
    log_audit('Project Requests', "Created walk-in project {$prjCode} + quotation {$quoteCode}", "{$projectName} — " . peso($total));

    echo json_encode(['ok' => true, 'project_code' => $prjCode, 'quote_code' => $quoteCode, 'total' => $total]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    sp_fail('Server error: ' . $e->getMessage(), 500);
}
