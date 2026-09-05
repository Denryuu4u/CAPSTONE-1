<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

$active_page = 'project_requests';
require_page($active_page); // role gate
$user_name = $_SESSION['full_name'] ?? 'Admin User';

require_once __DIR__ . '/../includes/helpers.php';

// All quote requests, newest first, with customer + linked project.
$requests = db()->query(
    "SELECT r.*, c.name AS customer_name, c.address AS customer_address,
            p.id AS project_id, p.project_code,
            (SELECT id FROM quotations q WHERE q.project_id = p.id ORDER BY id DESC LIMIT 1) AS quotation_id
       FROM project_requests r
       LEFT JOIN customers c ON c.id = r.customer_id
       LEFT JOIN projects  p ON p.request_id = r.id
      ORDER BY r.date_submitted DESC, r.id DESC"
)->fetchAll();

// Uploaded files grouped by request.
$filesByReq = [];
foreach (db()->query("SELECT request_id, file_name, file_path FROM request_files") as $f) {
    $filesByReq[$f['request_id']][] = $f;
}

// Customers for the "Create New Project" (walk-in) modal dropdown.
$allCustomers = db()->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();

$reqBadge = [
    'Requesting Quotation' => 'badge-requesting',
    'Quotation Sent'       => 'badge-sent',
    'Closed'               => 'badge-sent',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Requests – Vast Solutions</title>

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

        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center w-100">

                <div class="d-flex align-items-center gap-2">
                    <a href="#">Portal</a>
                    <span class="sep">›</span>
                    <span>Project Requests</span>
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
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h1 class="page-title mb-0">Project Requests</h1>

                <button type="button" class="btn btn-success btn-sm px-3 request-btn" data-bs-toggle="modal" data-bs-target="#createProjectModal">
                    <i class="bi bi-plus-lg me-1"></i>
                    Create New Project
                </button>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <div class="request-search-wrap">
                    <i class="bi bi-search request-search-icon"></i>
                    <input type="text" class="form-control request-search" placeholder="Search requests...">
                </div>
            </div>

            <div class="card border-0 shadow-sm request-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 request-table">
                        <thead>
                            <tr>
                                <th>REQUEST #</th>
                                <th>CUSTOMER</th>
                                <th>PROJECT</th>
                                <th>DATE SUBMITTED</th>
                                <th>STATUS</th>
                                <th class="text-center">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($requests)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No quote requests yet.</td></tr>
                            <?php else: foreach ($requests as $r):
                                $files = $filesByReq[$r['id']] ?? [];
                                $filesJson = htmlspecialchars(json_encode(array_map(fn($f) => [
                                    'name' => $f['file_name'], 'path' => '../' . $f['file_path'],
                                ], $files)), ENT_QUOTES);
                                $attrs = [
                                    'data-request-id="' . htmlspecialchars($r['request_code']) . '"',
                                    'data-request-pk="' . (int) $r['id'] . '"',
                                    'data-project-id="' . (int) ($r['project_id'] ?? 0) . '"',
                                    'data-customer="' . htmlspecialchars($r['customer_name'] ?? '') . '"',
                                    'data-customer-id="' . (int) $r['customer_id'] . '"',
                                    'data-project="' . htmlspecialchars($r['project_name']) . '"',
                                    'data-category="' . htmlspecialchars($r['category'] ?? '') . '"',
                                    'data-date-submitted="' . date('M d, Y', strtotime($r['date_submitted'])) . '"',
                                    'data-target-completion="' . htmlspecialchars($r['target_completion'] ?? '') . '"',
                                    'data-address="' . htmlspecialchars($r['customer_address'] ?? '') . '"',
                                    'data-notes="' . htmlspecialchars($r['notes'] ?? '') . '"',
                                    "data-files='" . $filesJson . "'",
                                ];
                                $attrStr = implode(' ', $attrs);
                                $badge = $reqBadge[$r['status']] ?? 'badge-requesting';
                                $canQuote = $r['status'] === 'Requesting Quotation';
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($r['request_code']) ?></td>
                                <td><?= htmlspecialchars($r['customer_name'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($r['project_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($r['date_submitted'])) ?></td>
                                <td><span class="badge rounded-pill <?= $badge ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                                <td class="text-center">
                                    <a href="#" class="action-icon me-2 view-request-btn" title="View Request"
                                       data-bs-toggle="modal" data-bs-target="#viewRequestModal" <?= $attrStr ?>>
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($canQuote): ?>
                                    <a href="#" class="action-icon create-quotation-btn" title="Create Cost Quotation"
                                       data-bs-toggle="modal" data-bs-target="#createQuotationModal" <?= $attrStr ?>>
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                    <?php elseif (!empty($r['quotation_id'])): ?>
                                    <a href="<?= BASE_URL ?>/download_quote.php?id=<?= (int) $r['quotation_id'] ?>" target="_blank"
                                       class="action-icon me-2" title="View Quotation">
                                        <i class="bi bi-receipt"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/download_costing.php?quotation_id=<?= (int) $r['quotation_id'] ?>"
                                       class="action-icon" title="Download Costing">
                                        <i class="bi bi-download"></i>
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
    <div class="modal fade request-details-modal" id="viewRequestModal" tabindex="-1" aria-labelledby="viewRequestModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header request-details-header">
                    <h5 class="modal-title request-details-title" id="viewRequestModalLabel">
                        Request Details — <span id="viewRequestId">REQ-2026-015</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body request-details-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="request-detail-label">Customer Name</div>
                            <div class="request-detail-value" id="viewCustomer">Rivera Kitchens</div>
                        </div>
                        <div class="col-md-6">
                            <div class="request-detail-label">Project Name</div>
                            <div class="request-detail-value" id="viewProject">Kitchen Reno Phase 1</div>
                        </div>

                        <div class="col-md-6">
                            <div class="request-detail-label">Category</div>
                            <div class="request-detail-value" id="viewCategory">Kitchen Cabinets</div>
                        </div>
                        <div class="col-md-6">
                            <div class="request-detail-label">Date Submitted</div>
                            <div class="request-detail-value" id="viewDateSubmitted">Mar 15, 2026</div>
                        </div>

                        <div class="col-md-6">
                            <div class="request-detail-label">Target Completion</div>
                            <div class="request-detail-value" id="viewTargetCompletion">Apr 30, 2026</div>
                        </div>

                        <div class="col-12">
                            <div class="request-detail-label">Installation Address</div>
                            <div class="request-detail-value" id="viewAddress">123 Main St, Metro Manila</div>
                        </div>

                        <div class="col-12">
                            <div class="request-detail-label">Notes</div>
                            <div class="request-detail-value" id="viewNotes">Customer prefers soft-close hinges and white melamine finish.</div>
                        </div>

                        <div class="col-12">
                            <div class="request-files-title">Uploaded Files</div>
                            <div class="request-file-list" id="viewFileList"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer request-details-footer">
                    <button type="button" class="btn request-close-btn" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn request-quotation-btn" id="openCreateQuotationFromView">
                        Create Cost Quotation
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="createQuotationModal" tabindex="-1" aria-labelledby="createQuotationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content project-modal">

                <div class="modal-header project-modal-header">
                    <h5 class="modal-title" id="createQuotationModalLabel">Create Cost Quotation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body project-modal-body">

                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Customer Selection</h6>

                        <div class="mb-3">
                            <label class="form-label project-label">Customer</label>
                            <input type="text" class="form-control project-input" id="quotationCustomer" readonly>
                        </div>
                    </div>

                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Project Information</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Project Name</label>
                                <input type="text" class="form-control project-input" id="quotationProject" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Project Code</label>
                                <input type="text" class="form-control project-input" value="QT-2026-001" readonly>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Category</label>
                                <input type="text" class="form-control project-input" id="quotationCategory" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Date Submitted</label>
                                <input type="text" class="form-control project-input" id="quotationDateSubmitted" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label project-label">Installation Address</label>
                            <textarea class="form-control project-input" rows="3" id="quotationAddress" readonly></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Target Completion Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control project-input" id="quotationTargetCompletion" required>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label project-label">Notes</label>
                            <textarea class="form-control project-input" rows="3" id="quotationNotes" readonly></textarea>
                        </div>
                    </div>

                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Material Totals Import <span class="text-muted" style="font-weight:400;font-size:.78rem;">(Cabinet Vision .xls)</span></h6>

                        <label for="quotationBomFile" class="bom-upload-box">
                            <input type="file" id="quotationBomFile" accept=".xls,.csv" hidden>
                            <div class="bom-upload-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <div class="bom-upload-text" id="qBomText">Drop material totals file here or click to browse</div>
                        </label>
                        <div id="qImportError" class="summ-upload-error" style="display:none;margin-top:.6rem;"></div>
                    </div>

                    <div class="project-section-card mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="project-section-title mb-0">Costing Preview</h6>
                            <button type="button" class="btn btn-sm btn-light border" id="qAddRow"><i class="bi bi-plus-lg"></i> Add row</button>
                        </div>

                        <div class="table-responsive mt-2">
                            <table class="table align-middle mb-0 costing-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th>UNIT</th>
                                        <th style="width:90px">QTY</th>
                                        <th style="width:120px">UNIT COST</th>
                                        <th style="width:120px">TOTAL</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody id="qCostBody">
                                    <tr><td colspan="6" class="text-center text-muted py-3">Import a material totals file, or add rows manually.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Substrate</label>
                                <input type="number" step="any" class="form-control project-input qc-recalc" id="qSubstrate" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Out of Town %</label>
                                <input type="number" step="any" class="form-control project-input qc-recalc" id="qOutOfTown" value="0">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label project-label">Markup %</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qMarkup" value="15">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Contingency %</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qContingency" value="5">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Service %</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qService" value="10">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Protection %</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qProtection" value="3">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label project-label">Special Works</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qSpecial" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Accessories</label>
                                <input type="number" class="form-control project-input qc-recalc" id="qAccess" value="0">
                            </div>
                        </div>

                        <hr class="project-divider">

                        <div class="d-flex justify-content-end">
                            <div class="cost-summary text-end">
                                <div>Material Total: <span id="qSumMaterial">₱0.00</span></div>
                                <div class="summary-service">Labor (50%): <span id="qSumLabor">₱0.00</span></div>
                                <div class="summary-markup">Markup (<span id="qLblMarkup">15</span>%): <span id="qSumMarkup">₱0.00</span></div>
                                <div class="summary-contingency">Contingency (<span id="qLblCont">5</span>%): <span id="qSumCont">₱0.00</span></div>
                                <div class="summary-service">Service (<span id="qLblService">10</span>%): <span id="qSumService">₱0.00</span></div>
                                <div class="summary-protection">Protection (<span id="qLblProtect">3</span>%): <span id="qSumProtect">₱0.00</span></div>
                                <div>Substrate: <span id="qSumSubstrate">₱0.00</span></div>
                                <div class="summary-service">Out of Town (<span id="qLblOutOfTown">0</span>%): <span id="qSumOutOfTown">₱0.00</span></div>
                                <div>Special Works: <span id="qSumSpecialLine">₱0.00</span></div>
                                <div>Accessories: <span id="qSumAccessLine">₱0.00</span></div>
                                <div class="summary-total">Total: <span id="qSumTotal">₱0.00</span></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer project-modal-footer">
                    <button type="button" class="btn btn-light border download-btn" id="qDownloadCostingBtn">
                        <i class="bi bi-download me-2"></i>
                        Download Costing
                    </button>
                    <button type="button" class="btn btn-success save-btn" id="qSendBtn">
                        <i class="bi bi-send me-2"></i>
                        Send Quotation
                    </button>
                </div>

            </div>
        </div>
    </div>
    <!-- NEW PRJECT MODAL -->
    <div class="modal fade" id="createProjectModal" tabindex="-1" aria-labelledby="createProjectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content project-modal">

                <div class="modal-header project-modal-header">
                    <h5 class="modal-title" id="createProjectModalLabel">Create New Project (Walk-in Customer)</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body project-modal-body">

                    <!-- Customer Selection -->
                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Customer Selection</h6>

                        <div class="mb-3">
                            <label class="form-label project-label">Select Customer</label>
                            <div class="row g-2">
                                <div class="col-md-10">
                                    <select class="form-select project-input" id="cpCustomer">
                                        <option value="" selected disabled>-- Select Customer --</option>
                                        <?php foreach ($allCustomers as $cust): ?>
                                        <option value="<?= (int) $cust['id'] ?>"><?= htmlspecialchars($cust['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-light border w-100 add-new-btn" data-bs-toggle="modal" data-bs-target="#addCustomerModal">
                                        + Add New
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Project Information -->
                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Project Information</h6>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Project Name</label>
                                <input type="text" class="form-control project-input" id="cpProjectName" placeholder="e.g. Kitchen Renovation - Phase 1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Category</label>
                                <input type="text" class="form-control project-input" id="cpCategory" placeholder="e.g. Kitchen Cabinets">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label project-label">Installation Address</label>
                            <textarea class="form-control project-input" id="cpAddress" rows="3" placeholder="Full installation address"></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Target Completion Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control project-input" id="cpTarget" required value="<?= date('Y-m-d', strtotime('+2 months')) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Project Code</label>
                                <input type="text" class="form-control project-input" value="Auto-generated on save" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Material Totals Import -->
                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Material Totals Import <span class="text-muted" style="font-weight:400;font-size:.78rem;">(Cabinet Vision .xls)</span></h6>

                        <label for="cpBomFile" class="bom-upload-box">
                            <input type="file" id="cpBomFile" accept=".xls" hidden>
                            <div class="bom-upload-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <div class="bom-upload-text" id="cpBomText">Drop material totals file here or click to browse</div>
                        </label>
                        <div class="text-danger small mt-2" id="cpImportError" style="display:none;"></div>
                    </div>

                    <!-- Costing Preview -->
                    <div class="project-section-card mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="project-section-title mb-0">Costing Preview</h6>
                            <button type="button" class="btn btn-sm btn-light border" id="cpAddRow"><i class="bi bi-plus-lg"></i> Add Row</button>
                        </div>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0 costing-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th>UNIT</th>
                                        <th>QTY</th>
                                        <th>UNIT COST</th>
                                        <th>TOTAL</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="cpCostBody">
                                    <tr><td colspan="6" class="text-center text-muted py-3">Import a material totals file, or add rows manually.</td></tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Substrate</label>
                                <input type="number" step="any" class="form-control project-input cp-recalc" id="cpSubstrate" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Out of Town</label>
                                <select class="form-select project-input cp-recalc" id="cpOutOfTown">
                                    <option selected>0%</option>
                                    <option>5%</option>
                                    <option>10%</option>
                                    <option>15%</option>
                                    <option>20%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label project-label">Markup</label>
                                <select class="form-select project-input cp-recalc" id="cpMarkup">
                                    <option selected>15%</option>
                                    <option>10%</option>
                                    <option>20%</option>
                                    <option>25%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Contingency</label>
                                <select class="form-select project-input cp-recalc" id="cpContingency">
                                    <option selected>5%</option>
                                    <option>3%</option>
                                    <option>10%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Service</label>
                                <select class="form-select project-input cp-recalc" id="cpService">
                                    <option selected>10%</option>
                                    <option>5%</option>
                                    <option>15%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Protection</label>
                                <select class="form-select project-input cp-recalc" id="cpProtection">
                                    <option selected>3%</option>
                                    <option>2%</option>
                                    <option>5%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label project-label">Special Works</label>
                                <input type="number" class="form-control project-input cp-recalc" id="cpSpecial" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Accessories</label>
                                <input type="number" class="form-control project-input cp-recalc" id="cpAccess" value="0">
                            </div>
                        </div>

                        <hr class="project-divider">

                        <div class="d-flex justify-content-end">
                            <div class="cost-summary text-end">
                                <div>Material Total: <span id="cpSumMaterial">₱0.00</span></div>
                                <div class="summary-service">Labor (50%): <span id="cpSumLabor">₱0.00</span></div>
                                <div class="summary-markup">Markup (<span id="cpLblMarkup">15</span>%): <span id="cpSumMarkup">₱0.00</span></div>
                                <div class="summary-contingency">Contingency (<span id="cpLblCont">5</span>%): <span id="cpSumCont">₱0.00</span></div>
                                <div class="summary-service">Service (<span id="cpLblService">10</span>%): <span id="cpSumService">₱0.00</span></div>
                                <div class="summary-protection">Protection (<span id="cpLblProtect">3</span>%): <span id="cpSumProtect">₱0.00</span></div>
                                <div>Substrate: <span id="cpSumSubstrate">₱0.00</span></div>
                                <div class="summary-service">Out of Town (<span id="cpLblOutOfTown">0</span>%): <span id="cpSumOutOfTown">₱0.00</span></div>
                                <div>Special Works: <span id="cpSumSpecialLine">₱0.00</span></div>
                                <div>Accessories: <span id="cpSumAccessLine">₱0.00</span></div>
                                <div class="summary-total">Total: <span id="cpSumTotal">₱0.00</span></div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer project-modal-footer">
                    <div class="text-danger small me-auto" id="cpSaveError" style="display:none;"></div>
                    <button type="button" class="btn btn-light border download-btn" id="cpDownloadCostingBtn">
                        <i class="bi bi-download me-2"></i>
                        Download Costing
                    </button>
                    <button type="button" class="btn btn-success save-btn" id="cpSaveBtn">
                        <i class="bi bi-floppy me-2"></i>
                        Save Project
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="addCustomerModal" tabindex="-1" aria-labelledby="addCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content add-customer-modal">

            <div class="modal-header add-customer-header">
                <h5 class="modal-title" id="addCustomerModalLabel">Add New Customer</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body add-customer-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label add-customer-label">Customer Name</label>
                        <input type="text" class="form-control add-customer-input" id="newCustomerName" placeholder="Enter customer name">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label add-customer-label">Email</label>
                        <input type="email" class="form-control add-customer-input" id="newCustomerEmail" placeholder="Enter email">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label add-customer-label">Phone Number</label>
                        <input type="text" class="form-control add-customer-input" id="newCustomerPhone" placeholder="Enter phone number">
                    </div>

                    <div class="col-12">
                        <label class="form-label add-customer-label">Address</label>
                        <textarea class="form-control add-customer-input" id="newCustomerAddress" rows="3" placeholder="Enter full address"></textarea>
                    </div>
                </div>
            </div>

            <div class="modal-footer add-customer-footer">
                <button type="button" class="btn btn-light border add-customer-cancel" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success add-customer-save" id="saveNewCustomerBtn">Save Customer</button>
            </div>

        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);

            if (urlParams.get("open") === "create") {
                const modal = new bootstrap.Modal(document.getElementById('createProjectModal'));
                modal.show();
            }

            let currentRequestData = {};

            document.querySelectorAll(".view-request-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    currentRequestData = {
                        requestId: this.dataset.requestId,
                        requestPk: this.dataset.requestPk,
                        projectId: this.dataset.projectId,
                        customerId: this.dataset.customerId,
                        customer: this.dataset.customer,
                        project: this.dataset.project,
                        category: this.dataset.category,
                        dateSubmitted: this.dataset.dateSubmitted,
                        targetCompletion: this.dataset.targetCompletion,
                        address: this.dataset.address,
                        notes: this.dataset.notes
                    };

                    document.getElementById("viewRequestId").textContent = currentRequestData.requestId;
                    document.getElementById("viewCustomer").textContent = currentRequestData.customer;
                    document.getElementById("viewProject").textContent = currentRequestData.project;
                    document.getElementById("viewCategory").textContent = currentRequestData.category;
                    document.getElementById("viewDateSubmitted").textContent = currentRequestData.dateSubmitted;
                    document.getElementById("viewTargetCompletion").textContent = currentRequestData.targetCompletion;
                    document.getElementById("viewAddress").textContent = currentRequestData.address;
                    document.getElementById("viewNotes").textContent = currentRequestData.notes;

                    // Render uploaded files from data-files JSON.
                    const fileList = document.getElementById("viewFileList");
                    let files = [];
                    try { files = JSON.parse(this.dataset.files || "[]"); } catch (e) {}
                    if (!files.length) {
                        fileList.innerHTML = '<div class="text-muted" style="font-size:.8rem;">No files uploaded.</div>';
                    } else {
                        fileList.innerHTML = files.map(f => `
                            <a href="${f.path}" target="_blank" class="request-file-item">
                                <div class="request-file-left">
                                    <i class="bi bi-file-earmark-text"></i>
                                    <span>${f.name}</span>
                                </div>
                                <i class="bi bi-download"></i>
                            </a>`).join('');
                    }
                });
            });

            document.querySelectorAll(".create-quotation-btn").forEach(btn => {
                btn.addEventListener("click", function() {
                    fillQuotationModal(this.dataset);
                });
            });

            document.getElementById("openCreateQuotationFromView").addEventListener("click", function() {
                const viewModalEl = document.getElementById("viewRequestModal");
                const viewModal = bootstrap.Modal.getInstance(viewModalEl);
                viewModal.hide();

                fillQuotationModal(currentRequestData);

                setTimeout(() => {
                    const quotationModal = new bootstrap.Modal(document.getElementById("createQuotationModal"));
                    quotationModal.show();
                }, 300);
            });

            // Holds the ids for the quotation currently being built.
            const qState = { projectId: 0, customerId: 0, requestPk: '' };

            function fillQuotationModal(data) {
                document.getElementById("quotationCustomer").value = data.customer || "";
                document.getElementById("quotationProject").value = data.project || "";
                document.getElementById("quotationCategory").value = data.category || "";
                document.getElementById("quotationDateSubmitted").value = data.dateSubmitted || "";
                document.getElementById("quotationTargetCompletion").value = data.targetCompletion || "<?= date('Y-m-d', strtotime('+2 months')) ?>";
                document.getElementById("quotationAddress").value = data.address || "";
                document.getElementById("quotationNotes").value = data.notes || "";

                qState.projectId  = parseInt(data.projectId || 0);
                qState.customerId = parseInt(data.customerId || 0);
                qState.requestPk  = data.requestPk || '';

                // Reset costing preview for the new quotation.
                document.getElementById("qCostBody").innerHTML =
                    '<tr><td colspan="6" class="text-center text-muted py-3">Import a material totals file, or add rows manually.</td></tr>';
                document.getElementById("qBomText").textContent = 'Drop material totals file here or click to browse';
                document.getElementById("qImportError").style.display = 'none';
                recomputeCosting();
            }

            // ── Costing preview logic ────────────────────────────────
            const pesoFmt = (n) => '₱' + (Number(n) || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const escAttr = (s) => String(s ?? '').replace(/"/g, '&quot;').replace(/</g, '&lt;');

            function costRow(item = '', unit = '', qty = 1, cost = 0) {
                return `<tr class="qc-row">
                    <td><input class="form-control form-control-sm qc-item" value="${escAttr(item)}"></td>
                    <td><input class="form-control form-control-sm qc-unit" value="${escAttr(unit)}" style="width:80px"></td>
                    <td><input type="number" step="any" class="form-control form-control-sm qc-qty" value="${qty}"></td>
                    <td><input type="number" step="any" class="form-control form-control-sm qc-cost" value="${cost}"></td>
                    <td class="qc-total text-end fw-semibold">₱0.00</td>
                    <td class="text-end"><button type="button" class="btn btn-sm text-danger qc-del" title="Remove"><i class="bi bi-x-lg"></i></button></td>
                </tr>`;
            }

            function costRows() { return Array.from(document.querySelectorAll('#qCostBody .qc-row')); }

            function recomputeCosting() {
                let material = 0;
                costRows().forEach(row => {
                    const q = parseFloat(row.querySelector('.qc-qty').value) || 0;
                    const c = parseFloat(row.querySelector('.qc-cost').value) || 0;
                    const line = q * c;
                    row.querySelector('.qc-total').textContent = pesoFmt(line);
                    material += line;
                });
                const pct = (id) => parseFloat(document.getElementById(id).value) || 0;
                const num = (id) => parseFloat(document.getElementById(id).value) || 0;
                const mk = pct('qMarkup'), co = pct('qContingency'), sv = pct('qService'), pr = pct('qProtection');
                const special   = num('qSpecial');
                const access    = num('qAccess');
                const substrate = num('qSubstrate');
                const oot       = pct('qOutOfTown'); // out of town is a % of material
                const labor = material * 0.5; // labor is 50% of the material total
                const mkA = material * mk / 100, coA = material * co / 100, svA = material * sv / 100, prA = material * pr / 100;
                const ootA = material * oot / 100;
                const total = material + labor + mkA + coA + svA + prA + substrate + ootA + special + access;

                document.getElementById('qSumMaterial').textContent = pesoFmt(material);
                document.getElementById('qSumLabor').textContent = pesoFmt(labor);
                document.getElementById('qLblMarkup').textContent = mk;   document.getElementById('qSumMarkup').textContent = pesoFmt(mkA);
                document.getElementById('qLblCont').textContent = co;     document.getElementById('qSumCont').textContent = pesoFmt(coA);
                document.getElementById('qLblService').textContent = sv;  document.getElementById('qSumService').textContent = pesoFmt(svA);
                document.getElementById('qLblProtect').textContent = pr;  document.getElementById('qSumProtect').textContent = pesoFmt(prA);
                document.getElementById('qSumSubstrate').textContent = pesoFmt(substrate);
                document.getElementById('qLblOutOfTown').textContent = oot;
                document.getElementById('qSumOutOfTown').textContent = pesoFmt(ootA);
                document.getElementById('qSumSpecialLine').textContent = pesoFmt(special);
                document.getElementById('qSumAccessLine').textContent = pesoFmt(access);
                document.getElementById('qSumTotal').textContent = pesoFmt(total);
            }

            function renderCostRows(items) {
                const body = document.getElementById('qCostBody');
                if (!items.length) {
                    body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No rows.</td></tr>';
                } else {
                    body.innerHTML = items.map(it => costRow(it.material, it.unit, it.qty, it.unit_cost)).join('');
                }
                recomputeCosting();
            }

            // Recalc on any input in the costing area.
            document.getElementById('createQuotationModal').addEventListener('input', function (e) {
                if (e.target.closest('#qCostBody') || e.target.classList.contains('qc-recalc')) recomputeCosting();
            });
            // Remove a row.
            document.getElementById('qCostBody').addEventListener('click', function (e) {
                const del = e.target.closest('.qc-del');
                if (del) { del.closest('tr').remove(); if (!costRows().length) renderCostRows([]); recomputeCosting(); }
            });
            // Add a blank row.
            document.getElementById('qAddRow').addEventListener('click', function () {
                if (!costRows().length) document.getElementById('qCostBody').innerHTML = '';
                document.getElementById('qCostBody').insertAdjacentHTML('beforeend', costRow());
                recomputeCosting();
            });

            // Import a material totals file.
            document.getElementById('quotationBomFile').addEventListener('change', function () {
                if (!this.files.length) return;
                const file = this.files[0];
                const err = document.getElementById('qImportError');
                err.style.display = 'none';
                document.getElementById('qBomText').textContent = 'Reading ' + file.name + '…';
                const fd = new FormData();
                fd.append('totals_file', file);
                fetch('import_material_totals.php', { method: 'POST', body: fd })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Import failed'); return d; })
                    .then(d => {
                        document.getElementById('qBomText').textContent = file.name + ' — ' + d.items.length + ' items imported';
                        renderCostRows(d.items);
                    })
                    .catch(e => { err.textContent = e.message; err.style.display = 'block'; document.getElementById('qBomText').textContent = 'Import failed — try again'; });
            });

            // Send quotation.
            document.getElementById('qSendBtn').addEventListener('click', function () {
                const err = document.getElementById('qImportError');
                err.style.display = 'none';
                const items = costRows().map(row => ({
                    description: (row.querySelector('.qc-item').value.trim() +
                        (row.querySelector('.qc-unit').value.trim() ? ' (' + row.querySelector('.qc-unit').value.trim() + ')' : '')),
                    qty: parseFloat(row.querySelector('.qc-qty').value) || 0,
                    unit_cost: parseFloat(row.querySelector('.qc-cost').value) || 0,
                })).filter(i => i.description);
                if (!items.length) { err.textContent = 'Add at least one line item before sending.'; err.style.display = 'block'; return; }
                if (!qState.projectId) { err.textContent = 'No project linked to this request.'; err.style.display = 'block'; return; }
                if (!document.getElementById('quotationTargetCompletion').value) { err.textContent = 'Set a target completion date.'; err.style.display = 'block'; return; }

                const btn = this; btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Sending…';
                const body = new URLSearchParams();
                body.append('project_id', qState.projectId);
                body.append('customer_id', qState.customerId);
                body.append('request_id', qState.requestPk);
                body.append('target_completion', document.getElementById('quotationTargetCompletion').value || '');
                body.append('items', JSON.stringify(items));
                ['qMarkup:markup_pct','qContingency:contingency_pct','qService:service_pct','qProtection:protection_pct',
                 'qSubstrate:substrate','qOutOfTown:out_of_town_pct','qSpecial:special_works','qAccess:accessories'].forEach(pair => {
                    const [id, key] = pair.split(':');
                    body.append(key, document.getElementById(id).value || 0);
                });
                fetch('create_quotation.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(d => { alert('Quotation ' + d.quote_code + ' sent — ' + pesoFmt(d.total)); location.reload(); })
                    .catch(e => { err.textContent = e.message; err.style.display = 'block'; btn.disabled = false; btn.innerHTML = '<i class="bi bi-send me-2"></i>Send Quotation'; });
            });

            // Download Costing (preview) — exports the current costing table as a CSV (opens in Excel).
            document.getElementById('qDownloadCostingBtn').addEventListener('click', function () {
                const rows = costRows();
                if (!rows.length) { alert('Nothing to export — add costing rows first.'); return; }
                const esc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                let csv = '﻿ITEM,UNIT,QTY,UNIT COST,TOTAL\r\n';
                rows.forEach(r => {
                    const item = r.querySelector('.qc-item').value, unit = r.querySelector('.qc-unit').value;
                    const q = parseFloat(r.querySelector('.qc-qty').value) || 0, c = parseFloat(r.querySelector('.qc-cost').value) || 0;
                    csv += [esc(item), esc(unit), q, c.toFixed(2), (q * c).toFixed(2)].join(',') + '\r\n';
                });
                const gv = id => document.getElementById(id).textContent;
                csv += '\r\n' + [',,,', esc('Material Total'), esc(gv('qSumMaterial'))].join(',') + '\r\n';
                csv += [',,,', esc('Labor (50%)'), esc(gv('qSumLabor'))].join(',') + '\r\n';
                csv += [',,,', esc('Markup'), esc(gv('qSumMarkup'))].join(',') + '\r\n';
                csv += [',,,', esc('Contingency'), esc(gv('qSumCont'))].join(',') + '\r\n';
                csv += [',,,', esc('Service'), esc(gv('qSumService'))].join(',') + '\r\n';
                csv += [',,,', esc('Protection'), esc(gv('qSumProtect'))].join(',') + '\r\n';
                csv += [',,,', esc('Substrate'), esc(gv('qSumSubstrate'))].join(',') + '\r\n';
                csv += [',,,', esc('Out of Town (' + gv('qLblOutOfTown') + '%)'), esc(gv('qSumOutOfTown'))].join(',') + '\r\n';
                csv += [',,,', esc('Special Works'), esc(gv('qSumSpecialLine'))].join(',') + '\r\n';
                csv += [',,,', esc('Accessories'), esc(gv('qSumAccessLine'))].join(',') + '\r\n';
                csv += [',,,', esc('TOTAL'), esc(gv('qSumTotal'))].join(',') + '\r\n';
                const name = (document.getElementById('quotationProject').value || 'costing').toLowerCase().replace(/[^a-z0-9]+/g, '-');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
                a.download = 'costing - ' + name + '.csv';
                document.body.appendChild(a); a.click(); a.remove();
            });

            // ══════════════════════════════════════════════════════════
            //  CREATE NEW PROJECT (walk-in) — functional costing + save
            // ══════════════════════════════════════════════════════════
            const cpBody = () => document.getElementById('cpCostBody');
            const cpRows = () => Array.from(document.querySelectorAll('#cpCostBody .qc-row'));
            const cpPct  = id => parseFloat(document.getElementById(id).value) || 0; // "15%" -> 15

            function cpRenderRows(items) {
                if (!items.length) {
                    cpBody().innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No rows.</td></tr>';
                } else {
                    cpBody().innerHTML = items.map(it => costRow(it.material, it.unit, it.qty, it.unit_cost)).join('');
                }
                cpRecompute();
            }

            function cpRecompute() {
                let material = 0;
                cpRows().forEach(row => {
                    const q = parseFloat(row.querySelector('.qc-qty').value) || 0;
                    const c = parseFloat(row.querySelector('.qc-cost').value) || 0;
                    const line = q * c;
                    row.querySelector('.qc-total').textContent = pesoFmt(line);
                    material += line;
                });
                const mk = cpPct('cpMarkup'), co = cpPct('cpContingency'), sv = cpPct('cpService'),
                      pr = cpPct('cpProtection'), oot = cpPct('cpOutOfTown');
                const substrate = parseFloat(document.getElementById('cpSubstrate').value) || 0;
                const special   = parseFloat(document.getElementById('cpSpecial').value) || 0;
                const access    = parseFloat(document.getElementById('cpAccess').value) || 0;
                const labor = material * 0.5;
                const mkA = material*mk/100, coA = material*co/100, svA = material*sv/100,
                      prA = material*pr/100, ootA = material*oot/100;
                const total = material + labor + mkA + coA + svA + prA + substrate + ootA + special + access;
                const set = (id, v) => document.getElementById(id).textContent = v;
                set('cpSumMaterial', pesoFmt(material));
                set('cpSumLabor', pesoFmt(labor));
                set('cpLblMarkup', mk);  set('cpSumMarkup', pesoFmt(mkA));
                set('cpLblCont', co);    set('cpSumCont', pesoFmt(coA));
                set('cpLblService', sv); set('cpSumService', pesoFmt(svA));
                set('cpLblProtect', pr); set('cpSumProtect', pesoFmt(prA));
                set('cpSumSubstrate', pesoFmt(substrate));
                set('cpLblOutOfTown', oot); set('cpSumOutOfTown', pesoFmt(ootA));
                set('cpSumSpecialLine', pesoFmt(special));
                set('cpSumAccessLine', pesoFmt(access));
                set('cpSumTotal', pesoFmt(total));
            }

            document.getElementById('createProjectModal').addEventListener('input', function (e) {
                if (e.target.closest('#cpCostBody') || e.target.classList.contains('cp-recalc')) cpRecompute();
            });
            document.getElementById('createProjectModal').addEventListener('change', function (e) {
                if (e.target.classList.contains('cp-recalc')) cpRecompute();
            });
            cpBody().addEventListener('click', function (e) {
                const del = e.target.closest('.qc-del');
                if (del) { del.closest('tr').remove(); if (!cpRows().length) cpRenderRows([]); cpRecompute(); }
            });
            document.getElementById('cpAddRow').addEventListener('click', function () {
                if (!cpRows().length) cpBody().innerHTML = '';
                cpBody().insertAdjacentHTML('beforeend', costRow());
                cpRecompute();
            });

            // Import material totals into the walk-in costing table.
            document.getElementById('cpBomFile').addEventListener('change', function () {
                if (!this.files.length) return;
                const file = this.files[0];
                const err = document.getElementById('cpImportError'); err.style.display = 'none';
                document.getElementById('cpBomText').textContent = 'Reading ' + file.name + '…';
                const fd = new FormData(); fd.append('totals_file', file);
                fetch('import_material_totals.php', { method: 'POST', body: fd })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Import failed'); return d; })
                    .then(d => { document.getElementById('cpBomText').textContent = file.name + ' — ' + d.items.length + ' items imported'; cpRenderRows(d.items); })
                    .catch(e => { err.textContent = e.message; err.style.display = 'block'; document.getElementById('cpBomText').textContent = 'Import failed — try again'; });
            });

            // Save the walk-in project + quotation.
            document.getElementById('cpSaveBtn').addEventListener('click', function () {
                const err = document.getElementById('cpSaveError'); err.style.display = 'none';
                const customerId = document.getElementById('cpCustomer').value;
                const projectName = document.getElementById('cpProjectName').value.trim();
                if (!customerId) { err.textContent = 'Select a customer.'; err.style.display = 'block'; return; }
                if (!projectName) { err.textContent = 'Enter a project name.'; err.style.display = 'block'; return; }
                const items = cpRows().map(row => ({
                    description: (row.querySelector('.qc-item').value.trim() +
                        (row.querySelector('.qc-unit').value.trim() ? ' (' + row.querySelector('.qc-unit').value.trim() + ')' : '')),
                    qty: parseFloat(row.querySelector('.qc-qty').value) || 0,
                    unit_cost: parseFloat(row.querySelector('.qc-cost').value) || 0,
                })).filter(i => i.description);
                if (!items.length) { err.textContent = 'Add at least one costing line item.'; err.style.display = 'block'; return; }
                if (!document.getElementById('cpTarget').value) { err.textContent = 'Set a target completion date.'; err.style.display = 'block'; return; }

                const btn = this; btn.disabled = true; btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Saving…';
                const body = new URLSearchParams();
                body.append('customer_id', customerId);
                body.append('project_name', projectName);
                body.append('category', document.getElementById('cpCategory').value.trim());
                body.append('address', document.getElementById('cpAddress').value.trim());
                body.append('target_completion', document.getElementById('cpTarget').value || '');
                body.append('items', JSON.stringify(items));
                body.append('markup_pct', cpPct('cpMarkup'));
                body.append('contingency_pct', cpPct('cpContingency'));
                body.append('service_pct', cpPct('cpService'));
                body.append('protection_pct', cpPct('cpProtection'));
                body.append('out_of_town_pct', cpPct('cpOutOfTown'));
                body.append('substrate', parseFloat(document.getElementById('cpSubstrate').value) || 0);
                body.append('special_works', parseFloat(document.getElementById('cpSpecial').value) || 0);
                body.append('accessories', parseFloat(document.getElementById('cpAccess').value) || 0);
                fetch('save_project.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(d => { alert('Project ' + d.project_code + ' created with quotation ' + d.quote_code + ' — ' + pesoFmt(d.total)); location.reload(); })
                    .catch(e => { err.textContent = e.message; err.style.display = 'block'; btn.disabled = false; btn.innerHTML = '<i class="bi bi-floppy me-2"></i>Save Project'; });
            });

            // Download the walk-in costing preview as CSV.
            document.getElementById('cpDownloadCostingBtn').addEventListener('click', function () {
                const rows = cpRows();
                if (!rows.length) { alert('Nothing to export — add costing rows first.'); return; }
                const esc = v => '"' + String(v ?? '').replace(/"/g, '""') + '"';
                let csv = '﻿ITEM,UNIT,QTY,UNIT COST,TOTAL\r\n';
                rows.forEach(r => {
                    const item = r.querySelector('.qc-item').value, unit = r.querySelector('.qc-unit').value;
                    const q = parseFloat(r.querySelector('.qc-qty').value) || 0, c = parseFloat(r.querySelector('.qc-cost').value) || 0;
                    csv += [esc(item), esc(unit), q, c.toFixed(2), (q * c).toFixed(2)].join(',') + '\r\n';
                });
                const gv = id => document.getElementById(id).textContent;
                csv += '\r\n' + [',,,', esc('Material Total'), esc(gv('cpSumMaterial'))].join(',') + '\r\n';
                csv += [',,,', esc('Labor (50%)'), esc(gv('cpSumLabor'))].join(',') + '\r\n';
                csv += [',,,', esc('Markup'), esc(gv('cpSumMarkup'))].join(',') + '\r\n';
                csv += [',,,', esc('Contingency'), esc(gv('cpSumCont'))].join(',') + '\r\n';
                csv += [',,,', esc('Service'), esc(gv('cpSumService'))].join(',') + '\r\n';
                csv += [',,,', esc('Protection'), esc(gv('cpSumProtect'))].join(',') + '\r\n';
                csv += [',,,', esc('Substrate'), esc(gv('cpSumSubstrate'))].join(',') + '\r\n';
                csv += [',,,', esc('Out of Town (' + gv('cpLblOutOfTown') + '%)'), esc(gv('cpSumOutOfTown'))].join(',') + '\r\n';
                csv += [',,,', esc('Special Works'), esc(gv('cpSumSpecialLine'))].join(',') + '\r\n';
                csv += [',,,', esc('Accessories'), esc(gv('cpSumAccessLine'))].join(',') + '\r\n';
                csv += [',,,', esc('TOTAL'), esc(gv('cpSumTotal'))].join(',') + '\r\n';
                const name = (document.getElementById('cpProjectName').value || 'costing').toLowerCase().replace(/[^a-z0-9]+/g, '-');
                const a = document.createElement('a');
                a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv' }));
                a.download = 'costing - ' + name + '.csv';
                document.body.appendChild(a); a.click(); a.remove();
            });

            // Add New Customer (from the walk-in modal) → save + append to dropdown.
            document.getElementById('saveNewCustomerBtn').addEventListener('click', function () {
                const name = document.getElementById('newCustomerName').value.trim();
                if (!name) { alert('Customer name is required.'); return; }
                const body = new URLSearchParams({
                    name,
                    email: document.getElementById('newCustomerEmail').value.trim(),
                    phone: document.getElementById('newCustomerPhone').value.trim(),
                    address: document.getElementById('newCustomerAddress').value.trim(),
                });
                const btn = this; btn.disabled = true;
                fetch('save_customer.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(d => {
                        const sel = document.getElementById('cpCustomer');
                        const opt = document.createElement('option');
                        opt.value = d.id; opt.textContent = name; opt.selected = true;
                        sel.appendChild(opt);
                        bootstrap.Modal.getInstance(document.getElementById('addCustomerModal')).hide();
                        ['newCustomerName', 'newCustomerEmail', 'newCustomerPhone', 'newCustomerAddress'].forEach(id => document.getElementById(id).value = '');
                    })
                    .catch(e => alert(e.message))
                    .finally(() => btn.disabled = false);
            });

            cpRecompute(); // initialise the walk-in summary
        });

    </script>
</body>

</html>