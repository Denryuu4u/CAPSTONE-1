<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

$active_page = 'quotations';
require_page($active_page); // role gate
$user_name = $_SESSION['full_name'] ?? 'Admin User';

require_once __DIR__ . '/../includes/helpers.php';

$quotes = db()->query(
    "SELECT q.*, c.name AS customer_name, c.address AS customer_address
       FROM quotations q
       LEFT JOIN customers c ON c.id = q.customer_id
      ORDER BY q.date_created DESC, q.id DESC"
)->fetchAll();

// Items grouped by quotation (for the admin view modal — internal breakdown).
$itemsByQuote = [];
foreach (db()->query("SELECT quotation_id, description, qty, unit_cost, line_total FROM quotation_items ORDER BY sort_order, id") as $it) {
    $itemsByQuote[$it['quotation_id']][] = $it;
}

// quotations.status -> [badge class, label, admin-can-act]
$quoteBadge = [
    'Sent'     => ['badge-waiting-approval', 'Sent to Client', false],
    'Accepted' => ['badge-waiting-approval', 'Awaiting Approval', true],
    'Approved' => ['badge-approved-soft',    'Approved', false],
    'Rejected' => ['badge-rejected-soft',    'Rejected', false],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Quotations – Vast Solutions</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="admin.css">
    <style>
        /* Quotation preview — responsive overrides for the inline-styled document */
        .qd-table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .qd-table-wrap table { min-width: 480px; }
        @media (max-width: 640px) {
            #viewQuotationModal .modal-body { padding: 12px !important; }
            .qd-row-header { flex-direction: column !important; gap: 14px !important; }
            .qd-row-header > div:last-child { text-align: left !important; }
            .qd-billship { flex-direction: column !important; gap: 16px !important; }
            .qd-sign { width: 100% !important; }
        }
    </style>
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <!-- TOPBAR -->
        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center w-100">

                <div class="d-flex align-items-center gap-2">
                    <a href="#">Portal</a>
                    <span class="sep">›</span>
                    <span>Quotations</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
                    <div class="user-avatar-sm">
                        <?= strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="lh-sm">
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($user_name); ?></div>
                        <div class="text-muted" style="font-size: 12px;">Administrator</div>
                    </div>
                </div>

            </div>
        </div>

        <div class="page-content container-fluid py-4 px-4">

            <!-- PAGE HEADER -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <h1 class="page-title mb-0">Quotations</h1>
            </div>

            <!-- FILTERS + SEARCH -->
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
                <div class="quotation-filters">
                    <a href="#" class="quotation-pill active">All</a>
                    <a href="#" class="quotation-pill">Waiting</a>
                    <a href="#" class="quotation-pill">Approved</a>
                    <a href="#" class="quotation-pill">Rejected</a>
                </div>

                <div class="quotation-search-wrap">
                    <i class="bi bi-search quotation-search-icon"></i>
                    <input type="text" class="form-control quotation-search" placeholder="Search quotations...">
                </div>
            </div>

            <!-- TABLE -->
            <div class="card border-0 shadow-sm quotation-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 quotation-table">
                        <thead>
                            <tr>
                                <th>QUOTE #</th>
                                <th>CUSTOMER</th>
                                <th>PROJECT</th>
                                <th>DATE</th>
                                <th>AMOUNT</th>
                                <th>STATUS</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($quotes)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">No quotations yet.</td></tr>
                            <?php else: foreach ($quotes as $q):
                                [$bcls, $blabel, $canAct] = $quoteBadge[$q['status']] ?? ['badge-waiting-approval', $q['status'], false];
                                $items = $itemsByQuote[$q['id']] ?? [];
                                $itemsJson = htmlspecialchars(json_encode(array_map(fn($i) => [
                                    'description' => $i['description'], 'qty' => (float) $i['qty'],
                                    'unit_cost' => (float) $i['unit_cost'], 'line_total' => (float) $i['line_total'],
                                ], $items)), ENT_QUOTES);
                                $dateStr  = $q['date_created'] ? date('M d, Y', strtotime($q['date_created'])) : '';
                                $validStr = $q['valid_until']  ? date('M d, Y', strtotime($q['valid_until']))  : '';
                            ?>
                            <tr data-status="<?= htmlspecialchars($q['status']) ?>">
                                <td><?= htmlspecialchars($q['quote_code']) ?></td>
                                <td><?= htmlspecialchars($q['customer_name'] ?? '—') ?></td>
                                <td class="quotation-project"><?= htmlspecialchars($q['project_name']) ?></td>
                                <td><?= $dateStr ?: '—' ?></td>
                                <td class="quotation-amount"><?= peso($q['total_amount']) ?></td>
                                <td><span class="badge-status <?= $bcls ?>"><?= htmlspecialchars($blabel) ?></span></td>
                                <td class="text-center">
                                    <div class="quotation-actions">
                                        <div class="action-left">
                                            <?php if ($canAct): ?>
                                            <a href="#" class="quotation-action approve quote-act" data-id="<?= (int) $q['id'] ?>" data-do="approve" title="Approve"><i class="bi bi-check-lg"></i></a>
                                            <a href="#" class="quotation-action reject quote-act" data-id="<?= (int) $q['id'] ?>" data-do="reject" title="Reject"><i class="bi bi-x-lg"></i></a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="action-right">
                                            <a href="#" class="quotation-action view-quotation-btn"
                                               data-bs-toggle="modal" data-bs-target="#viewQuotationModal"
                                               data-id="<?= (int) $q['id'] ?>"
                                               data-code="<?= htmlspecialchars($q['quote_code']) ?>"
                                               data-customer="<?= htmlspecialchars($q['customer_name'] ?? '') ?>"
                                               data-address="<?= htmlspecialchars($q['customer_address'] ?? '') ?>"
                                               data-project="<?= htmlspecialchars($q['project_name']) ?>"
                                               data-date="<?= $dateStr ?>" data-valid="<?= $validStr ?>"
                                               data-total="<?= peso($q['total_amount']) ?>"
                                               data-items='<?= $itemsJson ?>'>
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/download_quote.php?id=<?= (int) $q['id'] ?>" target="_blank" class="quotation-action" title="Download"><i class="bi bi-download"></i></a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade" id="viewQuotationModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content" style="border:none;border-radius:8px;overflow:hidden;font-family:'Syne', sans-serif;">

            <!-- TOP GREEN BAR -->
            <div style="height:10px;background:#2e4a45;"></div>

            <div class="modal-body" style="background:#f3f4f2;padding:20px;font-family:'Syne', sans-serif;">

                <div style="background:#fff;border:1px solid #ccc;font-family:'Syne', sans-serif;">

                    <!-- HEADER -->
                    <div class="qd-row-header" style="display:flex;justify-content:space-between;padding:20px;border-bottom:1px solid #ccc;">

                        <!-- LEFT -->
                        <div style="display:flex;gap:15px;">
                            <div style="width:80px;height:80px;background:#2e4a45;color:#fff;
                                display:flex;align-items:center;justify-content:center;font-weight:bold;">
                                <img src="../style/assets/logo.jpg" style="width:60px;height:60px;object-fit:contain;">
                            </div>

                            <div style="font-family:'Syne', sans-serif;">
                                <div style="font-size:22px;font-weight:800;">VAST</div>
                                <div style="font-size:13px;">B34 L1, Hibiscus St. Ceris 1, Calamba, Laguna</div>
                                <div style="font-size:13px;">+639178850408</div>
                                <div style="font-size:13px;">inquiries@vastsolutionsmanila.com</div>
                            </div>
                        </div>

                        <!-- RIGHT -->
                        <div style="text-align:right;font-family:'Syne', sans-serif;">
                            <div style="font-size:18px;letter-spacing:2px;font-weight:700;">SALES QUOTATION</div>

                            <div style="margin-top:10px;font-size:13px;">
                                Date: <b id="vqDate">--</b><br>
                                Reference #: <b id="vqCode">--</b><br>
                                Valid Until: <b id="vqValid">--</b>
                            </div>
                        </div>

                    </div>

                    <!-- BILL / SHIP -->
                    <div class="qd-billship" style="display:flex;gap:40px;padding:20px;border-bottom:1px solid #ccc;font-family:'Syne', sans-serif;">

                        <div style="flex:1;">
                            <div style="font-weight:700;border-bottom:2px solid #ccc;margin-bottom:10px;">BILL TO</div>
                            <div id="vqCustomer">Client Name</div>
                            <div id="vqAddress">Client Address</div>
                        </div>

                        <div style="flex:1;">
                            <div style="font-weight:700;border-bottom:2px solid #ccc;margin-bottom:10px;">SHIP TO</div>
                            <div id="vqShip">Client Name</div>
                            <div id="vqShipAddr">Client Address</div>
                        </div>

                    </div>

                    <!-- TABLE -->
                    <div class="qd-table-wrap" style="padding:20px;font-family:'Syne', sans-serif;">

                        <table style="width:100%;border-collapse:collapse;border:2px solid #1f2f2b;">

                            <thead style="background:#2e4a45;color:#fff;font-weight:700;">
                                <tr>
                                    <th style="padding:10px;text-align:left;">DESCRIPTION</th>
                                    <th style="padding:10px;width:80px;">QTY</th>
                                    <th style="padding:10px;width:120px;text-align:right;">UNIT PRICE</th>
                                    <th style="padding:10px;width:120px;text-align:right;">TOTAL</th>
                                </tr>
                            </thead>

                            <tbody id="vqItems">
                                <tr>
                                    <td style="padding:10px;border-top:1px solid #ccc;">
                                        <i>Description</i><br><br>

                                        <b>Finishes:</b><br>
                                        Carcass:<br>
                                        Doors:<br><br>

                                        <b>Inclusions:</b><br>
                                        • Item name
                                    </td>

                                    <td style="text-align:center;border-top:1px solid #ccc;">1</td>
                                    <td style="text-align:right;border-top:1px solid #ccc;">₱0.00</td>
                                    <td style="text-align:right;border-top:1px solid #ccc;">₱0.00</td>
                                </tr>
                            </tbody>

                        </table>

                        <!-- TOTAL -->
                        <div style="display:flex;justify-content:flex-end;margin-top:5px;">
                            <div style="background:#dfe8dc;padding:10px 20px;font-weight:700;">
                                Quote Total: <span id="vqTotal">₱0.00</span>
                            </div>
                        </div>

                    </div>

                    <!-- TERMS -->
                    <div style="padding:20px;font-size:13px;font-family:'Syne', sans-serif;">

                        <b>Terms and Conditions:</b><br><br>

                        1. Terms of payment:
                        <div style="margin-left:20px;">
                            50% downpayment is due upon acceptance of quote<br>
                            25% is due upon delivery and installation<br>
                            15% is due after installation<br>
                            10% is due after punchlist and turnover
                        </div><br>

                        2. All prices are VAT EXCLUSIVE.<br>
                        3. Cancellation is not allowed once production has started.<br>
                        4. Change order will be subject to separate quotation.<br>
                        5. Production leadtime is 5-6 weeks.<br>
                        6. This quote is only valid for 30 days.<br>
                        7. Warranty period of six months is provided.<br>
                        8. Warranty does not cover water damage or wear and tear.<br>

                    </div>

                    <!-- SIGNATURE -->
                    <div style="padding:20px;font-family:'Syne', sans-serif;">
                        <div style="margin-bottom:40px;font-style:italic;">Conforme:</div>

                        <div class="qd-sign" style="width:300px;border-top:2px solid #000;">
                            <small>Signature over Printed Name / Date</small>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER -->
            <div style="height:10px;background:#2e4a45;"></div>

            <div class="modal-footer" style="font-family:'Syne', sans-serif;">
                <button class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success" id="vqDownloadBtn">
                    <i class="bi bi-download"></i> Download PDF
                </button>
            </div>

        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            const pesoFmt = (n) => '₱' + (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const esc = (s) => String(s ?? '').replace(/[&<>"]/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[c]));

            // Tracks which quotation the view modal is currently showing.
            let currentQuotationId = 0;

            document.querySelectorAll(".view-quotation-btn").forEach(btn => {
                btn.addEventListener("click", function () {
                    currentQuotationId = parseInt(this.dataset.id || 0);
                    document.getElementById("vqCode").textContent = this.dataset.code;
                    document.getElementById("vqCustomer").textContent = this.dataset.customer;
                    document.getElementById("vqShip").textContent = this.dataset.customer;
                    if (document.getElementById("vqAddress")) document.getElementById("vqAddress").textContent = this.dataset.address || '';
                    if (document.getElementById("vqShipAddr")) document.getElementById("vqShipAddr").textContent = this.dataset.address || '';
                    document.getElementById("vqDate").textContent = this.dataset.date;
                    document.getElementById("vqValid").textContent = this.dataset.valid || '';
                    document.getElementById("vqTotal").textContent = this.dataset.total;

                    // Render the internal itemised breakdown (admin view).
                    let items = [];
                    try { items = JSON.parse(this.dataset.items || "[]"); } catch (e) {}
                    const body = document.getElementById("vqItems");
                    body.innerHTML = items.length ? items.map(it => `
                        <tr>
                            <td style="padding:10px;border-top:1px solid #ccc;">${esc(it.description)}</td>
                            <td style="text-align:center;border-top:1px solid #ccc;">${(+it.qty).toLocaleString('en-PH')}</td>
                            <td style="text-align:right;border-top:1px solid #ccc;">${pesoFmt(it.unit_cost)}</td>
                            <td style="text-align:right;border-top:1px solid #ccc;">${pesoFmt(it.line_total)}</td>
                        </tr>`).join('') :
                        '<tr><td colspan="4" style="padding:10px;text-align:center;color:#888;">No line items.</td></tr>';
                });
            });

            // Download the quotation from the view modal.
            const vqDownloadBtn = document.getElementById("vqDownloadBtn");
            if (vqDownloadBtn) {
                vqDownloadBtn.addEventListener("click", function () {
                    if (!currentQuotationId) { alert("No quotation selected."); return; }
                    window.open('<?= BASE_URL ?>/download_quote.php?id=' + currentQuotationId, '_blank');
                });
            }

            // Approve / reject a quotation (client-accepted ones).
            document.querySelectorAll(".quote-act").forEach(a => {
                a.addEventListener("click", function (e) {
                    e.preventDefault();
                    const action = this.dataset.do, id = this.dataset.id;
                    if (!confirm(`${action === 'approve' ? 'Approve' : 'Reject'} this quotation?`)) return;
                    const body = new URLSearchParams({ id, action });
                    fetch('update_quotation.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                        .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                        .then(() => location.reload())
                        .catch(err => alert(err.message));
                });
            });

            // Status filter pills.
            document.querySelectorAll('.quotation-pill').forEach(pill => {
                pill.addEventListener('click', function (e) {
                    e.preventDefault();
                    document.querySelectorAll('.quotation-pill').forEach(p => p.classList.remove('active'));
                    this.classList.add('active');
                    const want = this.textContent.trim().toLowerCase();
                    document.querySelectorAll('.quotation-table tbody tr').forEach(row => {
                        const st = (row.dataset.status || '').toLowerCase();
                        let show = want === 'all'
                            || (want === 'waiting'  && (st === 'sent' || st === 'accepted'))
                            || (want === 'approved' && st === 'approved')
                            || (want === 'rejected' && st === 'rejected');
                        row.style.display = show ? '' : 'none';
                    });
                });
            });

        });
    </script>
</body>

</html>