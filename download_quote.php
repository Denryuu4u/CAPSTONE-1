<?php
/**
 * Printable, client-facing quotation document (View / Download PDF via print).
 * Shows the project and the final quoted total — NOT the internal material
 * line-item breakdown (that is the admin's internal costing).
 *
 * GET: id
 */
require_once __DIR__ . '/includes/helpers.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) { http_response_code(400); exit('Invalid quotation.'); }

$pdo = db();
$stmt = $pdo->prepare(
    "SELECT q.*, c.name AS customer_name, c.address AS customer_address, c.user_id AS client_user_id
       FROM quotations q
       LEFT JOIN customers c ON c.id = q.customer_id
      WHERE q.id = ?"
);
$stmt->execute([$id]);
$q = $stmt->fetch();
if (!$q) { http_response_code(404); exit('Quotation not found.'); }

// Access: any back-office user (Super Admin/Admin/Staff), or the client who owns it.
$u = current_user();
$isStaff = in_array($u['role'] ?? '', BACKOFFICE_ROLES, true);
if (!$isStaff && (int) ($q['client_user_id'] ?? 0) !== (int) ($u['id'] ?? -1)) {
    http_response_code(403); exit('Access denied.');
}

$company = $pdo->query("SELECT * FROM company_settings WHERE id = 1")->fetch() ?: [];
$dateStr  = $q['date_created'] ? date('F d, Y', strtotime($q['date_created'])) : '';
$validStr = $q['valid_until']  ? date('F d, Y', strtotime($q['valid_until']))  : '';
$e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Quotation <?= $e($q['quote_code']) ?></title>
<style>
  * { box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #1a2e2a; margin: 0; background: #f3f4f2; }
  .sheet { max-width: 800px; margin: 24px auto; background: #fff; border: 1px solid #ccc; }
  .bar { height: 10px; background: #2e4a45; }
  .pad { padding: 26px 30px; }
  .row { display: flex; justify-content: space-between; align-items: flex-start; }
  .co-name { font-size: 22px; font-weight: 800; letter-spacing: 1px; }
  .muted { color: #555; font-size: 13px; }
  h1 { font-size: 18px; letter-spacing: 2px; margin: 0; }
  .box { border: 1px solid #ccc; border-radius: 6px; padding: 14px 16px; }
  table { width: 100%; border-collapse: collapse; margin-top: 8px; }
  th { background: #2e4a45; color: #fff; text-align: left; padding: 10px; font-size: 13px; }
  td { padding: 12px 10px; border-top: 1px solid #eee; font-size: 14px; }
  .total-row td { font-weight: 800; font-size: 16px; background: #dfe8dc; }
  .terms { font-size: 12px; color: #444; line-height: 1.6; }
  .print-btn { position: fixed; top: 16px; right: 16px; background: #2e4a45; color: #fff;
    border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-size: 14px; }
  @media print { .print-btn { display: none; } body { background: #fff; } .sheet { border: none; margin: 0; } }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">⬇ Print / Save PDF</button>
<div class="sheet">
  <div class="bar"></div>
  <div class="pad row">
    <div>
      <div class="co-name"><?= $e($company['company_name'] ?? 'VAST') ?></div>
      <div class="muted"><?= $e($company['address'] ?? '') ?></div>
      <div class="muted"><?= $e($company['contact_number'] ?? '') ?></div>
      <div class="muted"><?= $e($company['email'] ?? '') ?></div>
    </div>
    <div style="text-align:right;">
      <h1>SALES QUOTATION</h1>
      <div class="muted" style="margin-top:10px;">
        Date: <b><?= $e($dateStr) ?></b><br>
        Reference #: <b><?= $e($q['quote_code']) ?></b><br>
        Valid Until: <b><?= $e($validStr) ?></b><br>
        Status: <b><?= $e($q['status']) ?></b>
      </div>
    </div>
  </div>

  <div class="pad" style="padding-top:0;">
    <div class="box">
      <div style="font-weight:700;border-bottom:2px solid #ccc;margin-bottom:8px;padding-bottom:4px;">BILL TO</div>
      <div><?= $e($q['customer_name'] ?? '') ?></div>
      <div class="muted"><?= $e($q['customer_address'] ?? '') ?></div>
    </div>
  </div>

  <div class="pad" style="padding-top:0;">
    <table>
      <thead>
        <tr><th>DESCRIPTION</th><th style="text-align:right;width:180px;">AMOUNT</th></tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <b><?= $e($q['project_name']) ?></b><br>
            <span class="muted">Custom cabinetry works — design, fabrication, and installation as specified.</span>
          </td>
          <td style="text-align:right;"><?= peso($q['total_amount']) ?></td>
        </tr>
        <tr class="total-row">
          <td style="text-align:right;">QUOTE TOTAL</td>
          <td style="text-align:right;"><?= peso($q['total_amount']) ?></td>
        </tr>
      </tbody>
    </table>
    <?php if (!empty($q['notes'])): ?>
      <p class="muted" style="margin-top:10px;"><b>Notes:</b> <?= $e($q['notes']) ?></p>
    <?php endif; ?>
  </div>

  <div class="pad terms" style="padding-top:0;">
    <b>Terms and Conditions:</b><br>
    1. 50% downpayment upon acceptance, 25% upon delivery/installation, 15% after installation, 10% after punchlist and turnover.<br>
    2. All prices are VAT exclusive. &nbsp; 3. Cancellation not allowed once production has started.<br>
    4. Change orders subject to separate quotation. &nbsp; 5. Production lead time 5–6 weeks.<br>
    6. This quote is valid for 30 days. &nbsp; 7. Six-month warranty (excludes water damage / wear and tear).
  </div>
  <div class="bar"></div>
</div>
</body>
</html>
