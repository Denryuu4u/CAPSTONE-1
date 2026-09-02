<?php
/**
 * Internal costing sheet for a saved quotation (admin view) — shows the full
 * line-item breakdown + markup/contingency/service/protection, unlike the
 * client-facing download_quote.php which shows only the total.
 *
 * GET: quotation_id  → streams an .xls (HTML-spreadsheet, dependency-free).
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('quotations', true); // back-office only

$id = (int) ($_GET['quotation_id'] ?? $_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Invalid quotation.'); }

$pdo = db();
$qs = $pdo->prepare("SELECT q.*, c.name AS customer_name FROM quotations q LEFT JOIN customers c ON c.id=q.customer_id WHERE q.id=?");
$qs->execute([$id]);
$q = $qs->fetch();
if (!$q) { http_response_code(404); exit('Quotation not found.'); }

$items = $pdo->prepare("SELECT description, qty, unit_cost, line_total FROM quotation_items WHERE quotation_id=? ORDER BY sort_order, id");
$items->execute([$id]);
$items = $items->fetchAll();

$mat = (float) $q['material_total'];
$rows = '';
foreach ($items as $it) {
    $rows .= '<tr>'
        . '<td style="mso-number-format:\'\@\';">' . htmlspecialchars($it['description']) . '</td>'
        . '<td style="text-align:right;">' . (float) $it['qty'] . '</td>'
        . '<td style="text-align:right;">' . number_format($it['unit_cost'], 2) . '</td>'
        . '<td style="text-align:right;">' . number_format($it['line_total'], 2) . '</td>'
        . '</tr>';
}
// Labor is 50% of material (fall back to a computed value for older rows saved
// before the column existed).
$labor = isset($q['labor_cost']) && $q['labor_cost'] !== null && (float) $q['labor_cost'] > 0
    ? (float) $q['labor_cost'] : round($mat * 0.5, 2);
$breakdown = [
    ['Material Total', $mat],
    ['Labor (50%)', $labor],
    ['Markup (' . rtrim(rtrim($q['markup_pct'], '0'), '.') . '%)', $mat * $q['markup_pct'] / 100],
    ['Contingency (' . rtrim(rtrim($q['contingency_pct'], '0'), '.') . '%)', $mat * $q['contingency_pct'] / 100],
    ['Service (' . rtrim(rtrim($q['service_pct'], '0'), '.') . '%)', $mat * $q['service_pct'] / 100],
    ['Protection (' . rtrim(rtrim($q['protection_pct'], '0'), '.') . '%)', $mat * $q['protection_pct'] / 100],
    ['Substrate', (float) ($q['substrate'] ?? 0)],
    ['Out of Town (' . rtrim(rtrim((string) ($q['out_of_town_pct'] ?? '0'), '0'), '.') . '%)', $mat * (float) ($q['out_of_town_pct'] ?? 0) / 100],
    ['Special Works', (float) $q['special_works']],
    ['Accessories', (float) $q['accessories']],
    ['GRAND TOTAL', (float) $q['total_amount']],
];
$sumRows = '';
foreach ($breakdown as [$label, $val]) {
    $bold = $label === 'GRAND TOTAL' ? 'font-weight:bold;background:#dfe8dc;' : '';
    $sumRows .= '<tr><td colspan="3" style="text-align:right;' . $bold . '">' . htmlspecialchars($label) . '</td>'
        . '<td style="text-align:right;' . $bold . '">' . number_format($val, 2) . '</td></tr>';
}

$base = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $q['quote_code']));
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="costing - ' . $base . '.xls"');
header('Pragma: no-cache');
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>
<body>
<table border="0" cellspacing="0" cellpadding="4">
  <tr><td colspan="4" style="font-size:16px;font-weight:bold;">Costing Sheet — <?= htmlspecialchars($q['quote_code']) ?></td></tr>
  <tr><td colspan="4">Project: <?= htmlspecialchars($q['project_name']) ?> &nbsp; | &nbsp; Customer: <?= htmlspecialchars($q['customer_name'] ?? '') ?></td></tr>
  <tr><td colspan="4">&nbsp;</td></tr>
</table>
<table border="1" cellspacing="0" cellpadding="6">
  <thead><tr style="background:#2e4a45;color:#fff;font-weight:bold;">
    <th>ITEM</th><th>QTY</th><th>UNIT COST</th><th>TOTAL</th>
  </tr></thead>
  <tbody>
    <?= $rows ?>
    <?= $sumRows ?>
  </tbody>
</table>
</body>
</html>
