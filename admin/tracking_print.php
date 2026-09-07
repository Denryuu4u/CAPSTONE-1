<?php
/**
 * Printable (Save-as-PDF) view of a project's cost-tracking ledger.
 * GET: project_id  — auto-opens the print dialog on load.
 */
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/tracking.php';
require_page('monitoring', true); // back-office only

$projectId = (int) ($_GET['project_id'] ?? 0);
if ($projectId <= 0) { http_response_code(400); exit('Missing project.'); }
$d = get_project_tracking($projectId);
if ($d === null) { http_response_code(404); exit('Project not found.'); }
$t = $d['totals'];
$fmtDate = fn($iso) => $iso ? date('M d, Y', strtotime($iso)) : '';
$laborRows = function (string $phase) use ($d, $fmtDate) {
    $out = '';
    foreach ($d['labor'] as $l) {
        if ($l['phase'] !== $phase) continue;
        $line = (float) $l['rate'] + (float) $l['gas_toll'];
        $out .= '<tr><td>' . htmlspecialchars($fmtDate($l['work_date'])) . '</td><td>' . htmlspecialchars($l['manpower'])
              . '</td><td class="r">' . peso((float) $l['rate']) . '</td><td class="r">' . peso((float) $l['gas_toll'])
              . '</td><td class="r">' . peso($line) . '</td></tr>';
    }
    return $out ?: '<tr><td colspan="5" class="muted">No labor rows.</td></tr>';
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Project Tracking — <?= htmlspecialchars($d['project']['code']) ?></title>
<style>
  *{box-sizing:border-box;} body{font-family:Arial,Helvetica,sans-serif;color:#1f2937;margin:24px;font-size:13px;}
  .head{display:flex;align-items:center;gap:14px;border-bottom:3px solid #0D9676;padding-bottom:14px;margin-bottom:18px;}
  .head h1{font-size:20px;margin:0;color:#0d1b2a;} .head .sub{font-size:11px;color:#6b7280;margin-top:2px;}
  .kpis{display:flex;gap:12px;margin-bottom:18px;flex-wrap:wrap;}
  .kpi{flex:1 1 150px;border:1px solid #e5e7eb;border-radius:8px;padding:10px 14px;}
  .kpi .l{font-size:10px;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;font-weight:700;}
  .kpi .v{font-size:17px;font-weight:700;color:#0d1b2a;margin-top:3px;}
  .kpi.profit .v{color:#0a7a60;} .kpi.loss .v{color:#dc2626;}
  h2{font-size:13px;color:#0d1b2a;margin:18px 0 6px;}
  table{width:100%;border-collapse:collapse;margin-bottom:6px;}
  th{background:#0d1b2a;color:#fff;font-size:10px;text-transform:uppercase;letter-spacing:.03em;padding:7px 9px;text-align:left;}
  td{padding:6px 9px;border-bottom:1px solid #eee;} .r{text-align:right;} .muted{color:#9ca3af;text-align:center;}
  tfoot td{font-weight:700;border-top:2px solid #d1d5db;}
  .grand{margin-top:14px;padding:12px 16px;background:#f0fdf9;border:1px solid #6ee7d0;border-radius:8px;display:flex;justify-content:space-between;font-weight:700;color:#0a7a60;font-size:15px;}
  @media print{ .noprint{display:none;} body{margin:10mm;} }
  .noprint{margin-bottom:14px;} .btn{padding:7px 14px;border:none;border-radius:6px;background:#0D9676;color:#fff;font-weight:600;cursor:pointer;}
</style>
</head>
<body>
<div class="noprint"><button class="btn" onclick="window.print()">Print / Save as PDF</button></div>
<div class="head">
  <div>
    <h1>Project Tracking</h1>
    <div class="sub"><?= htmlspecialchars($d['project']['name']) ?> · <?= htmlspecialchars($d['project']['code']) ?> · <?= htmlspecialchars($d['project']['customer']) ?></div>
    <div class="sub">Generated <?= date('M d, Y g:i A') ?></div>
  </div>
</div>

<div class="kpis">
  <div class="kpi"><div class="l">Project Amount</div><div class="v"><?= peso($d['amount']) ?></div></div>
  <div class="kpi"><div class="l">Project Cost</div><div class="v"><?= peso($t['project_cost']) ?></div></div>
  <div class="kpi <?= $t['profit_loss'] >= 0 ? 'profit' : 'loss' ?>"><div class="l">Profit / Loss</div><div class="v"><?= peso($t['profit_loss']) ?></div></div>
</div>

<h2>Expenses</h2>
<table>
  <thead><tr><th>Item</th><th class="r">Amount</th></tr></thead>
  <tbody>
  <?php if ($d['expenses']): foreach ($d['expenses'] as $e): ?>
    <tr><td><?= htmlspecialchars($e['label']) ?></td><td class="r"><?= peso((float) $e['amount']) ?></td></tr>
  <?php endforeach; else: ?>
    <tr><td colspan="2" class="muted">No expenses.</td></tr>
  <?php endif; ?>
  </tbody>
  <tfoot><tr><td>Total Expenses</td><td class="r"><?= peso($t['expenses_total']) ?></td></tr></tfoot>
</table>

<h2>Labor — Assembly</h2>
<table>
  <thead><tr><th>Date</th><th>Manpower</th><th class="r">Rate</th><th class="r">Gas &amp; Toll</th><th class="r">Line Total</th></tr></thead>
  <tbody><?= $laborRows('assembly') ?></tbody>
  <tfoot><tr><td colspan="4">Total Assembly</td><td class="r"><?= peso($t['assembly_total']) ?></td></tr></tfoot>
</table>

<h2>Labor — Installation</h2>
<table>
  <thead><tr><th>Date</th><th>Manpower</th><th class="r">Rate</th><th class="r">Gas &amp; Toll</th><th class="r">Line Total</th></tr></thead>
  <tbody><?= $laborRows('installation') ?></tbody>
  <tfoot><tr><td colspan="4">Total Installation</td><td class="r"><?= peso($t['installation_total']) ?></td></tr></tfoot>
</table>

<div class="grand"><span>Grand Total (Project Cost)</span><span><?= peso($t['project_cost']) ?></span></div>

<?php if (trim((string) $d['notes']) !== ''): ?>
<h2>Notes</h2>
<div style="white-space:pre-wrap;font-size:12px;color:#374151;"><?= htmlspecialchars($d['notes']) ?></div>
<?php endif; ?>

<script>window.addEventListener('load',function(){ setTimeout(function(){ window.print(); }, 350); });</script>
</body>
</html>
