<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'quotations';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
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
                            <tr>
                                <td>QT-2026-042</td>
                                <td>Rivera Kitchens</td>
                                <td class="quotation-project">Kitchen Reno Phase 1</td>
                                <td>Mar 15, 2026</td>
                                <td class="quotation-amount">₱12,450.00</td>
                                <td>
                                    <span class="badge-status badge-waiting-approval">Waiting Approval</span>
                                </td>
                                <td class="text-center">
                                    <div class="quotation-actions">

                                        <!-- LEFT SIDE (Approve / Reject) -->
                                        <div class="action-left">
                                            <a href="#" class="quotation-action approve" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <a href="#" class="quotation-action reject" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        </div>

                                        <!-- RIGHT SIDE (View / Download) -->
                                        <div class="action-right">
                                            <a href="#"
                                                class="quotation-action view-quotation-btn"
                                                data-bs-toggle="modal"
                                                data-bs-target="#viewQuotationModal"
                                                data-code="QT-2026-001"
                                                data-customer="Rivera Kitchens"
                                                data-project="Kitchen Reno Phase 1"
                                                data-date="Mar 15, 2026"
                                                data-total="₱2,191.18">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" class="quotation-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>

                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>QT-2026-041</td>
                                <td>Mendoza Interiors</td>
                                <td class="quotation-project">Office Cabinets</td>
                                <td>Mar 12, 2026</td>
                                <td class="quotation-amount">₱8,920.00</td>
                                <td>
                                    <span class="badge-status badge-approved-soft">Approved</span>
                                </td>
                                <td class="text-center">
                                    <div class="quotation-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="quotation-action" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" class="quotation-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>QT-2026-040</td>
                                <td>Kim Design Studio</td>
                                <td class="quotation-project">Bathroom Vanity Set</td>
                                <td>Mar 10, 2026</td>
                                <td class="quotation-amount">₱5,380.00</td>
                                <td>
                                    <span class="badge-status badge-waiting-approval">Waiting Approval</span>
                                </td>
                                <td class="text-center">
                                    <div class="quotation-actions">

                                        <!-- LEFT SIDE (Approve / Reject) -->
                                        <div class="action-left">
                                            <a href="#" class="quotation-action approve" title="Approve">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <a href="#" class="quotation-action reject" title="Reject">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        </div>

                                        <!-- RIGHT SIDE (View / Download) -->
                                        <div class="action-right">
                                            <a href="#" class="quotation-action" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" class="quotation-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>

                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>QT-2026-039</td>
                                <td>Park Residences</td>
                                <td class="quotation-project">Lobby Display Unit</td>
                                <td>Mar 08, 2026</td>
                                <td class="quotation-amount">₱18,750.00</td>
                                <td>
                                    <span class="badge-status badge-approved-soft">Approved</span>
                                </td>
                                <td class="text-center">
                                    <div class="quotation-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="quotation-action" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" class="quotation-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <td>QT-2026-038</td>
                                <td>Lee Custom Homes</td>
                                <td class="quotation-project">Master Closet System</td>
                                <td>Mar 05, 2026</td>
                                <td class="quotation-amount">₱7,200.00</td>
                                <td>
                                    <span class="badge-status badge-rejected-soft">Rejected</span>
                                </td>
                                <td class="text-center">
                                    <div class="quotation-actions">
                                        <div class="action-left"></div>

                                        <div class="action-right">
                                            <a href="#" class="quotation-action" title="View">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="#" class="quotation-action" title="Download">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
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
                    <div style="display:flex;justify-content:space-between;padding:20px;border-bottom:1px solid #ccc;">

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
                    <div style="display:flex;gap:40px;padding:20px;border-bottom:1px solid #ccc;font-family:'Syne', sans-serif;">

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
                    <div style="padding:20px;font-family:'Syne', sans-serif;">

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

                        <div style="width:300px;border-top:2px solid #000;">
                            <small>Signature over Printed Name / Date</small>
                        </div>
                    </div>

                </div>
            </div>

            <!-- FOOTER -->
            <div style="height:10px;background:#2e4a45;"></div>

            <div class="modal-footer" style="font-family:'Syne', sans-serif;">
                <button class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success">
                    <i class="bi bi-download"></i> Download PDF
                </button>
            </div>

        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            document.querySelectorAll(".view-quotation-btn").forEach(btn => {
    btn.addEventListener("click", function () {

        const today = new Date();
        const valid = new Date();
        valid.setDate(today.getDate() + 30);

        function format(d){
            return d.toLocaleDateString('en-PH');
        }

        document.getElementById("vqCode").textContent = this.dataset.code;
        document.getElementById("vqCustomer").textContent = this.dataset.customer;
        document.getElementById("vqShip").textContent = this.dataset.customer;
        document.getElementById("vqDate").textContent = this.dataset.date;
        document.getElementById("vqValid").textContent = format(valid);
        document.getElementById("vqTotal").textContent = this.dataset.total;

    });
});

        });
    </script>
</body>

</html>