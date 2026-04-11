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
                                <td class="quotation-amount">$12,450.00</td>
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
                                                data-total="$2,191.18">
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
                                <td class="quotation-amount">$8,920.00</td>
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
                                <td class="quotation-amount">$5,380.00</td>
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
                                <td class="quotation-amount">$18,750.00</td>
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
                                <td class="quotation-amount">$7,200.00</td>
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
        <div class="modal-content quotation-view-modal">

            <!-- HEADER -->
            <div class="modal-header quotation-view-header">
                <div>
                    <h5 class="quotation-view-title" id="viewQuotationProject">
                        Kitchen Reno Phase 1
                    </h5>
                    <div class="quotation-view-sub">
                        Quotation #: <span id="viewQuotationCode">QT-2026-001</span>
                    </div>
                </div>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body quotation-view-body">

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="q-label">Customer</div>
                        <div class="q-value" id="viewQuotationCustomer">Rivera Kitchens</div>
                    </div>
                    <div class="col-md-6">
                        <div class="q-label">Date</div>
                        <div class="q-value" id="viewQuotationDate">Mar 15, 2026</div>
                    </div>
                </div>

                <!-- COST TABLE -->
                <div class="table-responsive mb-3">
                    <table class="table align-middle quotation-preview-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Unit Cost</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody id="quotationItems">
                            <tr>
                                <td>Panel - Base Cabinet</td>
                                <td>18mm Melamine White</td>
                                <td>12</td>
                                <td>$45.00</td>
                                <td>$540.00</td>
                            </tr>
                            <tr>
                                <td>Hinge - Soft Close</td>
                                <td>Blum 110°</td>
                                <td>24</td>
                                <td>$8.75</td>
                                <td>$210.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- SUMMARY -->
                <div class="text-end quotation-summary">
                    <div>Material Total: $1,647.50</div>
                    <div>Markup (15%): $247.13</div>
                    <div>Contingency (5%): $82.38</div>
                    <div>Service (10%): $164.75</div>
                    <div>Protection (3%): $49.43</div>
                    <div class="q-total">Total: <span id="viewQuotationTotal">$2,191.18</span></div>
                </div>

            </div>

            <!-- FOOTER -->
            <div class="modal-footer quotation-view-footer">
                <button class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-success">
                    <i class="bi bi-download me-1"></i> Download PDF
                </button>
            </div>

        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
document.addEventListener("DOMContentLoaded", function () {

    document.querySelectorAll(".view-quotation-btn").forEach(btn => {
        btn.addEventListener("click", function () {

            document.getElementById("viewQuotationCode").textContent = this.dataset.code;
            document.getElementById("viewQuotationCustomer").textContent = this.dataset.customer;
            document.getElementById("viewQuotationProject").textContent = this.dataset.project;
            document.getElementById("viewQuotationDate").textContent = this.dataset.date;
            document.getElementById("viewQuotationTotal").textContent = this.dataset.total;

        });
    });

});
</script>
</body>

</html>