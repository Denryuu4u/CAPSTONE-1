<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'project_requests';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
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
                            <tr>
                                <td>REQ-2026-015</td>
                                <td>Rivera Kitchens</td>
                                <td>Kitchen Reno Phase 1</td>
                                <td>Mar 15, 2026</td>
                                <td>
                                    <span class="badge rounded-pill badge-requesting">Requesting Quotation</span>
                                </td>
                                <td class="text-center">
                                    <a href="#"
                                        class="action-icon me-2 view-request-btn"
                                        title="View"
                                        data-bs-toggle="modal"
                                        data-bs-target="#viewRequestModal"
                                        data-request-id="REQ-2026-015"
                                        data-customer="Rivera Kitchens"
                                        data-project="Kitchen Reno Phase 1"
                                        data-category="Kitchen Cabinets"
                                        data-date-submitted="Mar 15, 2026"
                                        data-target-completion="2026-04-30"
                                        data-address="123 Main St, Metro Manila"
                                        data-notes="Customer prefers soft-close hinges and white melamine finish.">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <a href="#"
                                        class="action-icon create-quotation-btn"
                                        title="Create Quotation"
                                        data-bs-toggle="modal"
                                        data-bs-target="#createQuotationModal"
                                        data-request-id="REQ-2026-015"
                                        data-customer="Rivera Kitchens"
                                        data-project="Kitchen Reno Phase 1"
                                        data-category="Kitchen Cabinets"
                                        data-date-submitted="Mar 15, 2026"
                                        data-target-completion="2026-04-30"
                                        data-address="123 Main St, Metro Manila"
                                        data-notes="Customer prefers soft-close hinges and white melamine finish.">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </a>
                                </td>
                            </tr>

                            <tr>
                                <td>REQ-2026-014</td>
                                <td>Mendoza Interiors</td>
                                <td>Office Cabinets</td>
                                <td>Mar 12, 2026</td>
                                <td>
                                    <span class="badge rounded-pill badge-requesting">Requesting Quotation</span>
                                </td>
                                <td class="text-center">
                                    <a href="#" class="action-icon me-2" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="#" class="action-icon" title="Create Quotation"><i class="bi bi-file-earmark-text"></i></a>
                                </td>
                            </tr>

                            <tr>
                                <td>REQ-2026-013</td>
                                <td>Kim Design Studio</td>
                                <td>Bathroom Vanity Set</td>
                                <td>Mar 10, 2026</td>
                                <td>
                                    <span class="badge rounded-pill badge-requesting">Requesting Quotation</span>
                                </td>
                                <td class="text-center">
                                    <a href="#" class="action-icon me-2" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="#" class="action-icon" title="Create Quotation"><i class="bi bi-file-earmark-text"></i></a>
                                </td>
                            </tr>

                            <tr>
                                <td>REQ-2026-012</td>
                                <td>Park Residences</td>
                                <td>Lobby Display Unit</td>
                                <td>Mar 08, 2026</td>
                                <td>
                                    <span class="badge rounded-pill badge-sent">Quotation Sent</span>
                                </td>
                                <td class="text-center">
                                    <a href="#" class="action-icon me-2" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="#" class="action-icon" title="Create Quotation"><i class="bi bi-file-earmark-text"></i></a>
                                </td>
                            </tr>
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
                            <div class="request-file-list">
                                <a href="#" class="request-file-item">
                                    <div class="request-file-left">
                                        <i class="bi bi-file-earmark-pdf"></i>
                                        <span>floor_plan.pdf</span>
                                    </div>
                                    <i class="bi bi-download"></i>
                                </a>

                                <a href="#" class="request-file-item">
                                    <div class="request-file-left">
                                        <i class="bi bi-file-earmark-zip"></i>
                                        <span>reference_photos.zip</span>
                                    </div>
                                    <i class="bi bi-download"></i>
                                </a>
                            </div>
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
                                <label class="form-label project-label">Target Completion Date</label>
                                <input type="date" class="form-control project-input" id="quotationTargetCompletion" readonly>
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label project-label">Notes</label>
                            <textarea class="form-control project-input" rows="3" id="quotationNotes" readonly></textarea>
                        </div>
                    </div>

                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">BOM File Import</h6>

                        <label for="quotationBomFile" class="bom-upload-box">
                            <input type="file" id="quotationBomFile" hidden>
                            <div class="bom-upload-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <div class="bom-upload-text">Drop BOM file here or click to browse</div>
                        </label>
                    </div>

                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Costing Preview</h6>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0 costing-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th>DESCRIPTION</th>
                                        <th>QTY</th>
                                        <th>UNIT COST</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Panel - Base Cabinet</td>
                                        <td>18mm Melamine White</td>
                                        <td>12</td>
                                        <td>₱45.00</td>
                                        <td>₱540.00</td>
                                    </tr>
                                    <tr>
                                        <td>Hinge - Soft Close</td>
                                        <td>Blum 110°</td>
                                        <td>24</td>
                                        <td>₱8.75</td>
                                        <td>₱210.00</td>
                                    </tr>
                                    <tr>
                                        <td>Handle - Bar Pull</td>
                                        <td>Stainless 160mm</td>
                                        <td>18</td>
                                        <td>₱12.00</td>
                                        <td>₱216.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Qty of Boards</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Qty of Glass</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label project-label">Markup</label>
                                <select class="form-select project-input">
                                    <option selected>15%</option>
                                    <option>10%</option>
                                    <option>20%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Contingency</label>
                                <select class="form-select project-input">
                                    <option selected>5%</option>
                                    <option>3%</option>
                                    <option>10%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Service</label>
                                <select class="form-select project-input">
                                    <option selected>10%</option>
                                    <option>5%</option>
                                    <option>15%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Protection</label>
                                <select class="form-select project-input">
                                    <option selected>3%</option>
                                    <option>2%</option>
                                    <option>5%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label project-label">Special Works</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Accessories</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                        </div>

                        <hr class="project-divider">

                        <div class="d-flex justify-content-end">
                            <div class="cost-summary text-end">
                                <div>Material Total: ₱1,647.50</div>
                                <div class="summary-markup">Markup (15%): ₱247.13</div>
                                <div class="summary-contingency">Contingency (5%): ₱82.38</div>
                                <div class="summary-service">Service (10%): ₱164.75</div>
                                <div class="summary-protection">Protection (3%): ₱49.43</div>
                                <div class="summary-total">Total: ₱2,191.18</div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer project-modal-footer">
                    <button type="button" class="btn btn-light border download-btn">
                        <i class="bi bi-download me-2"></i>
                        Download Costing
                    </button>
                    <button type="button" class="btn btn-success save-btn">
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
                                    <select class="form-select project-input">
                                        <option selected disabled>-- Select Customer --</option>
                                        <option>Rivera Kitchens</option>
                                        <option>Mendoza Interiors</option>
                                        <option>Kim Design Studio</option>
                                        <option>Park Residences</option>
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
                                <input type="text" class="form-control project-input" value="Kitchen Renovation - Phase 1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Project Code</label>
                                <input type="text" class="form-control project-input" value="PRJ-2026-043">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label project-label">Installation Address</label>
                            <textarea class="form-control project-input" rows="3" placeholder="Full installation address"></textarea>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Target Completion Date</label>
                                <input type="date" class="form-control project-input">
                            </div>
                        </div>
                    </div>

                    <!-- BOM Import -->
                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">BOM File Import</h6>

                        <label for="bomFile" class="bom-upload-box">
                            <input type="file" id="bomFile" hidden>
                            <div class="bom-upload-icon">
                                <i class="bi bi-upload"></i>
                            </div>
                            <div class="bom-upload-text">Drop BOM file here or click to browse</div>
                        </label>
                    </div>

                    <!-- Costing Preview -->
                    <div class="project-section-card mb-3">
                        <h6 class="project-section-title">Costing Preview</h6>

                        <div class="table-responsive">
                            <table class="table align-middle mb-0 costing-table">
                                <thead>
                                    <tr>
                                        <th>ITEM</th>
                                        <th>DESCRIPTION</th>
                                        <th>QTY</th>
                                        <th>UNIT COST</th>
                                        <th>TOTAL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Panel - Base Cabinet</td>
                                        <td>18mm Melamine White</td>
                                        <td>12</td>
                                        <td>₱45.00</td>
                                        <td>₱540.00</td>
                                    </tr>
                                    <tr>
                                        <td>Panel - Wall Cabinet</td>
                                        <td>18mm Melamine White</td>
                                        <td>8</td>
                                        <td>₱38.00</td>
                                        <td>₱304.00</td>
                                    </tr>
                                    <tr>
                                        <td>Drawer Box</td>
                                        <td>Plywood 12mm</td>
                                        <td>6</td>
                                        <td>₱22.50</td>
                                        <td>₱135.00</td>
                                    </tr>
                                    <tr>
                                        <td>Hinge - Soft Close</td>
                                        <td>Blum 110°</td>
                                        <td>24</td>
                                        <td>₱8.75</td>
                                        <td>₱210.00</td>
                                    </tr>
                                    <tr>
                                        <td>Drawer Slide</td>
                                        <td>Full Extension 18"</td>
                                        <td>12</td>
                                        <td>₱15.00</td>
                                        <td>₱180.00</td>
                                    </tr>
                                    <tr>
                                        <td>Edge Band</td>
                                        <td>PVC 2mm White</td>
                                        <td>50</td>
                                        <td>₱1.25</td>
                                        <td>₱62.50</td>
                                    </tr>
                                    <tr>
                                        <td>Handle - Bar Pull</td>
                                        <td>Stainless 160mm</td>
                                        <td>18</td>
                                        <td>₱12.00</td>
                                        <td>₱216.00</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="row g-3 mt-3">
                            <div class="col-md-6">
                                <label class="form-label project-label">Qty of Boards</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Qty of Glass</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-3">
                                <label class="form-label project-label">Markup</label>
                                <select class="form-select project-input">
                                    <option selected>15%</option>
                                    <option>10%</option>
                                    <option>20%</option>
                                    <option>25%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Contingency</label>
                                <select class="form-select project-input">
                                    <option selected>5%</option>
                                    <option>3%</option>
                                    <option>10%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Service</label>
                                <select class="form-select project-input">
                                    <option selected>10%</option>
                                    <option>5%</option>
                                    <option>15%</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label project-label">Protection</label>
                                <select class="form-select project-input">
                                    <option selected>3%</option>
                                    <option>2%</option>
                                    <option>5%</option>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label project-label">Special Works</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label project-label">Accessories</label>
                                <input type="number" class="form-control project-input" value="0">
                            </div>
                        </div>

                        <hr class="project-divider">

                        <div class="d-flex justify-content-end">
                            <div class="cost-summary text-end">
                                <div>Material Total: ₱1,647.50</div>
                                <div class="summary-markup">Markup (15%): ₱247.13</div>
                                <div class="summary-contingency">Contingency (5%): ₱82.38</div>
                                <div class="summary-service">Service (10%): ₱164.75</div>
                                <div class="summary-protection">Protection (3%): ₱49.43</div>
                                <div class="summary-total">Total: ₱2,191.18</div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="modal-footer project-modal-footer">
                    <button type="button" class="btn btn-light border download-btn">
                        <i class="bi bi-download me-2"></i>
                        Download Costing (PDF)
                    </button>
                    <button type="button" class="btn btn-success save-btn">
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

            function fillQuotationModal(data) {
                document.getElementById("quotationCustomer").value = data.customer || "";
                document.getElementById("quotationProject").value = data.project || "";
                document.getElementById("quotationCategory").value = data.category || "";
                document.getElementById("quotationDateSubmitted").value = data.dateSubmitted || "";
                document.getElementById("quotationTargetCompletion").value = data.targetCompletion || "";
                document.getElementById("quotationAddress").value = data.address || "";
                document.getElementById("quotationNotes").value = data.notes || "";
            }
        });
       
    </script>
</body>

</html>