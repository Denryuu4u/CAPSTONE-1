<?php
/**
 * Download a project's cost-tracking ledger as a real .xlsx, laid out like the
 * business's Project Tracking sheet.
 * GET: project_id
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tracking.php';
require_once __DIR__ . '/../includes/xlsx_writer.php';
require_page('monitoring', true); // back-office only

$projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) { http_response_code(400); exit('Missing project.'); }

$d = get_project_tracking($projectId);
if ($d === null) { http_response_code(404); exit('Project not found.'); }

$t   = $d['totals'];
$fmtDate = fn($iso) => $iso ? date('M d, Y', strtotime($iso)) : '';

$rows = [];
$rows[] = [['v' => 'Project Tracking', 's' => XLSX_TITLE]];
$rows[] = [];
$rows[] = ['', '', ['v' => 'PROJECT AMOUNT', 's' => XLSX_HEAD], ['v' => 'PROJECT COST', 's' => XLSX_HEAD], ['v' => 'PROFIT OR LOSS', 's' => XLSX_HEAD]];
$rows[] = [
    ['v' => 'Project Name', 's' => XLSX_BOLD],
    ['v' => $d['project']['name'], 's' => XLSX_BOLD],
    ['v' => $d['amount'], 's' => XLSX_MONEY],
    ['v' => $t['project_cost'], 's' => XLSX_MONEY],
    ['v' => $t['profit_loss'], 's' => XLSX_BOLDMON],
];
$rows[] = [['v' => 'Customer', 's' => XLSX_BOLD], $d['project']['customer'], '', '', ['v' => $d['project']['code']]];
$rows[] = [];

// ── Expenses ──
$rows[] = [['v' => 'Expenses', 's' => XLSX_SUBHEAD]];
$rows[] = [['v' => 'Item', 's' => XLSX_HEAD], ['v' => 'Amount', 's' => XLSX_HEAD]];
if ($d['expenses']) {
    foreach ($d['expenses'] as $e) {
        $rows[] = [$e['label'], ['v' => (float) $e['amount'], 's' => XLSX_MONEY]];
    }
} else {
    $rows[] = ['(none)'];
}
$rows[] = [['v' => 'Total', 's' => XLSX_BOLD], ['v' => $t['expenses_total'], 's' => XLSX_BOLDMON]];
$rows[] = [];

// ── Labor (Assembly then Installation) ──
foreach ([['assembly', 'Labor Assembly', $t['assembly_total']], ['installation', 'Labor Installation', $t['installation_total']]] as $ph) {
    [$phaseKey, $phaseLabel, $phaseTotal] = $ph;
    $rows[] = [['v' => $phaseLabel, 's' => XLSX_SUBHEAD]];
    $rows[] = [
        ['v' => 'Date', 's' => XLSX_HEAD], ['v' => 'Manpower', 's' => XLSX_HEAD],
        ['v' => 'Rate', 's' => XLSX_HEAD], ['v' => 'Gas and Toll', 's' => XLSX_HEAD],
        ['v' => 'Total', 's' => XLSX_HEAD],
    ];
    $any = false;
    foreach ($d['labor'] as $l) {
        if ($l['phase'] !== $phaseKey) continue;
        $any = true;
        $lineTotal = (float) $l['rate'] + (float) $l['gas_toll'];
        $rows[] = [
            $fmtDate($l['work_date']),
            $l['manpower'],
            ['v' => (float) $l['rate'], 's' => XLSX_MONEY],
            ['v' => (float) $l['gas_toll'], 's' => XLSX_MONEY],
            ['v' => round($lineTotal, 2), 's' => XLSX_MONEY],
        ];
    }
    if (!$any) $rows[] = ['(none)'];
    $rows[] = [['v' => 'Total ' . ($phaseKey === 'assembly' ? 'Assembly' : 'Installation'), 's' => XLSX_BOLD], '', '', '', ['v' => $phaseTotal, 's' => XLSX_BOLDMON]];
    $rows[] = [];
}

// ── Grand total ──
$rows[] = [['v' => 'Grand Total (Project Cost)', 's' => XLSX_BOLD], '', '', '', ['v' => $t['project_cost'], 's' => XLSX_BOLDMON]];

$safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $d['project']['code'] ?: ('project_' . $projectId));
xlsx_stream('Project_Tracking_' . $safe . '.xlsx', $rows, [16, 26, 14, 14, 16]);
