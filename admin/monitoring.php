<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'monitoring';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
$user_initial = strtoupper(substr($user_name, 0, 1));

$monitor_projects = [
    ['code'=>'PRJ-2026-042', 'project'=>'Kitchen Reno Phase 1', 'customer'=>'Rivera Kitchens',
     'status'=>'production', 'target'=>'Mar 15, 2026', 'start'=>'2026-02-01', 'progress'=>65,
     'approver'=>'Engr. Marco Reyes', 'materials_key'=>'042', 'updates_key'=>'042',
     'details'=>'Custom kitchen cabinetry with soft-close hinges, melamine panels, and PVC edge banding. Includes island countertop support structure.'],

    ['code'=>'PRJ-2026-041', 'project'=>'Office Cabinets', 'customer'=>'Mendoza Interiors',
     'status'=>'approved', 'target'=>'Mar 12, 2026', 'start'=>'2026-02-10', 'progress'=>20,
     'approver'=>'Engr. Marco Reyes', 'materials_key'=>'041', 'updates_key'=>'041',
     'details'=>'Floor-to-ceiling office cabinetry in white laminate finish. Includes adjustable shelving and lockable lower cabinets.'],

    ['code'=>'PRJ-2026-040', 'project'=>'Bathroom Vanity Set', 'customer'=>'Kim Design Studio',
     'status'=>'quote_submitted', 'target'=>'Mar 10, 2026', 'start'=>'', 'progress'=>0,
     'approver'=>'', 'materials_key'=>'', 'updates_key'=>'',
     'details'=>'Freestanding bathroom vanity with integrated sink, mirror cabinet, and chrome fittings. Material: moisture-resistant MDF.'],

    ['code'=>'PRJ-2026-039', 'project'=>'Lobby Display Unit', 'customer'=>'Park Residences',
     'status'=>'completed', 'target'=>'Mar 08, 2026', 'start'=>'2026-01-15', 'progress'=>100,
     'approver'=>'Engr. Sofia Lim', 'materials_key'=>'039', 'updates_key'=>'039',
     'details'=>'Custom lobby display unit with back-lit acrylic panels and tempered glass shelves. Powder-coated steel frame in matte black.'],

    ['code'=>'PRJ-2026-037', 'project'=>'Pantry Cabinets', 'customer'=>'Garcia Build Co',
     'status'=>'production', 'target'=>'Mar 01, 2026', 'start'=>'2026-01-20', 'progress'=>85,
     'approver'=>'Engr. Marco Reyes', 'materials_key'=>'037', 'updates_key'=>'037',
     'details'=>'Full-height pantry cabinets with pull-out shelves, soft-close doors, and ventilation slats. Material: E1-grade particle board.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Project Monitoring – Vast Solutions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin.css">
    <style>
        #viewProjectModal { z-index: 1055; }
        #materialsModal   { z-index: 1065; }
        #viewProjectModal .modal-dialog { max-width: 760px; }
        #viewProjectModal .modal-content { border-radius: 14px; border: none; overflow: hidden; }

        /* Hero header */
        .pvm-hero { background:#fff; padding:22px 26px 16px; border-bottom:1px solid #e5e7eb; }
        .pvm-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:12px; flex-wrap:wrap; }
        .pvm-title { font-family:'Syne',sans-serif; font-size:1.15rem; font-weight:700; color:#0d1b2a; line-height:1.2; margin:0; }
        .pvm-code  { font-size:0.72rem; color:#6b7280; margin-top:3px; }
        .pvm-hero-actions { display:flex; align-items:center; gap:8px; flex-shrink:0; }

        /* State dropdown */
        .pvm-state-select {
            appearance:none; -webkit-appearance:none;
            border:1px solid #e5e7eb; border-radius:7px;
            padding:5px 28px 5px 10px; font-size:0.72rem; font-weight:600; color:#374151;
            background:#fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 9px center;
            cursor:pointer; transition:border-color .18s; min-width:100px;
        }
        .pvm-state-select:focus { outline:none; border-color:#0D9676; }
        .pvm-state-select.state-active { border-color:#0D9676; color:#0D9676; background-color:#f0fdf9; }
        .pvm-state-select.state-paused { border-color:#f59e0b; color:#d97706; background-color:#fffbeb; }

        /* Edit button */
        .pvm-edit-btn {
            display:inline-flex; align-items:center; gap:5px;
            font-size:0.72rem; font-weight:600; padding:5px 12px;
            border-radius:7px; border:1px solid #e5e7eb; background:#fff; color:#374151;
            cursor:pointer; text-decoration:none; transition:border-color .18s,color .18s;
        }
        .pvm-edit-btn:hover { border-color:#0D9676; color:#0D9676; }

        /* Step tracker */
        .pvm-step-tracker { margin-top:14px; overflow-x:auto; padding-bottom:4px; }
        .pvm-steps { display:flex; align-items:flex-start; min-width:600px; }
        .pvm-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:0; }
        .pvm-step-dot { width:24px; height:24px; border-radius:50%; background:#e5e7eb; color:#9ca3af; display:flex; align-items:center; justify-content:center; font-size:0.6rem; font-weight:700; flex-shrink:0; }
        .pvm-step.done .pvm-step-dot { background:#0D9676; color:#fff; }
        .pvm-step.active .pvm-step-dot { background:#0D9676; color:#fff; box-shadow:0 0 0 4px rgba(13,150,118,.18); }
        .pvm-step-label { font-size:0.56rem; text-align:center; color:#9ca3af; margin-top:5px; line-height:1.3; padding:0 2px; }
        .pvm-step.done .pvm-step-label, .pvm-step.active .pvm-step-label { color:#0D9676; font-weight:600; }
        .pvm-step-connector { flex:1; height:2px; background:#e5e7eb; margin-top:12px; align-self:flex-start; min-width:6px; }
        .pvm-step-connector.done { background:#0D9676; }

        /* Body */
        #viewProjectModal .modal-body { padding:0; max-height:72vh; overflow-y:auto; }

        /* Detail section */
        .pvm-details-section { padding:18px 26px; border-bottom:1px solid #f0f0f0; }
        .pvm-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px 20px; }
        @media(max-width:540px){ .pvm-grid{grid-template-columns:1fr;} }
        .pvm-field-label { font-size:0.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; margin-bottom:3px; }
        .pvm-field-value { font-size:0.82rem; font-weight:500; color:#1f2937; }

        /* Approver pill */
        .pv-approver { display:inline-flex; align-items:center; gap:6px; background:#f0fdf9; border:1px solid #6ee7d0; border-radius:8px; padding:4px 10px; font-size:0.75rem; font-weight:500; color:#0a7a60; }

        /* Materials btn */
        .btn-materials { display:inline-flex; align-items:center; gap:6px; font-size:0.72rem; font-weight:600; padding:5px 12px; border-radius:6px; border:1px solid #0D9676; color:#0D9676; background:#f0fdf9; cursor:pointer; transition:background .18s,color .18s; text-decoration:none; }
        .btn-materials:hover { background:#0D9676; color:#fff; }

        /* Updates section */
        .pvm-updates-section { padding:18px 26px 24px; }
        .pvm-section-title { font-family:'Syne',sans-serif; font-size:0.82rem; font-weight:700; color:#0d1b2a; display:flex; align-items:center; gap:6px; margin-bottom:14px; }
        .pvm-section-title i { color:#0D9676; }

        /* Composer */
        .pvm-composer { display:flex; gap:10px; align-items:flex-start; margin-bottom:20px; }
        .pvm-composer-avatar { width:32px; height:32px; border-radius:50%; background:#d1fae5; color:#0f766e; display:flex; align-items:center; justify-content:center; font-size:0.72rem; font-weight:700; flex-shrink:0; margin-top:2px; }
        .pvm-composer-box { flex:1; }
        .pvm-composer-textarea { width:100%; border:1px solid #e5e7eb; border-radius:8px; padding:9px 12px; font-size:0.78rem; color:#374151; resize:none; font-family:'Inter',sans-serif; transition:border-color .18s; line-height:1.5; }
        .pvm-composer-textarea:focus { outline:none; border-color:#0D9676; box-shadow:0 0 0 3px rgba(13,150,118,.08); }
        .pvm-composer-footer { display:flex; align-items:center; justify-content:space-between; margin-top:8px; }
        .pvm-attach-btn { display:inline-flex; align-items:center; gap:5px; font-size:0.7rem; font-weight:500; color:#6b7280; border:1px solid #e5e7eb; border-radius:6px; padding:4px 10px; background:#fff; cursor:pointer; transition:border-color .18s,color .18s; }
        .pvm-attach-btn:hover { border-color:#0D9676; color:#0D9676; }
        .pvm-post-btn { display:inline-flex; align-items:center; gap:5px; font-size:0.72rem; font-weight:600; color:#fff; background:#0D9676; border:none; border-radius:7px; padding:6px 14px; cursor:pointer; transition:background .18s; }
        .pvm-post-btn:hover { background:#0a7a60; }

        /* Feed */
        .pvm-update-item { display:flex; gap:10px; margin-bottom:18px; }
        .pvm-update-item:last-child { margin-bottom:0; }
        .pvm-update-dot-col { display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
        .pvm-update-dot { width:10px; height:10px; border-radius:50%; background:#0D9676; border:2px solid #fff; box-shadow:0 0 0 2px #0D9676; margin-top:4px; flex-shrink:0; }
        .pvm-update-dot-line { width:2px; flex:1; background:#e5e7eb; margin-top:4px; min-height:20px; }
        .pvm-update-content { flex:1; min-width:0; }
        .pvm-update-meta { display:flex; align-items:center; gap:6px; margin-bottom:5px; flex-wrap:wrap; }
        .pvm-update-avatar { width:22px; height:22px; border-radius:50%; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:0.58rem; font-weight:700; flex-shrink:0; }
        .pvm-update-author { font-size:0.75rem; font-weight:600; color:#111827; }
        .pvm-update-time   { font-size:0.68rem; color:#9ca3af; }
        .pvm-update-text   { font-size:0.78rem; color:#374151; line-height:1.55; margin-bottom:8px; }
        .pvm-attachments   { display:flex; gap:8px; flex-wrap:wrap; }
        .pvm-attachment-chip { padding:5px 12px; border-radius:6px; font-size:0.68rem; font-weight:600; cursor:pointer; transition:opacity .15s; }
        .pvm-attachment-chip:hover { opacity:.8; }
        .chip-green  { background:#dcfce7; color:#15803d; }
        .chip-blue   { background:#dbeafe; color:#1d4ed8; }
        .chip-yellow { background:#fef9c3; color:#b45309; }
        .chip-purple { background:#ede9fe; color:#6d28d9; }
        .chip-pink   { background:#fce7f3; color:#be185d; }
        .pvm-empty-updates { text-align:center; padding:28px 0 10px; color:#9ca3af; font-size:0.78rem; }
        .pvm-empty-updates i { font-size:1.6rem; display:block; margin-bottom:8px; opacity:.4; }

        /* Materials modal */
        #materialsModal .modal-header { background:#0d1b2a; color:#fff; border-radius:12px 12px 0 0; }
        #materialsModal .modal-header .btn-close { filter:invert(1) grayscale(1); }
        #materialsModal .modal-content { border-radius:12px; border:none; }
        .mat-table thead th { background:#f8fafc; font-size:0.62rem; font-weight:700; color:#9ca3af; letter-spacing:.04em; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding:10px 14px; }
        .mat-table tbody td { font-size:0.75rem; color:#374151; padding:10px 14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
        .mat-table tbody tr:last-child td { border-bottom:none; }
        .mat-badge { display:inline-block; padding:2px 8px; border-radius:99px; font-size:0.6rem; font-weight:600; }
        .mat-badge-available { background:rgba(34,197,94,.12); color:#16a34a; }
        .mat-badge-ordered   { background:rgba(245,158,11,.12); color:#d97706; }
        .mat-badge-low       { background:rgba(239,68,68,.12);  color:#dc2626; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center gap-2">
                <a href="#">Portal</a><span class="sep">›</span><span>Project Monitoring</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
                    <div class="user-avatar-sm"><?= strtoupper(substr($user_name,0,1)); ?></div>
                <div class="lh-sm">
                    <div class="fw-semibold small text-dark"><?= htmlspecialchars($user_name); ?></div>
                    <div class="text-muted" style="font-size:12px;">Administrator</div>
                </div>
            </div>
        </div>
    </div>

    <div class="page-content container-fluid py-4 px-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h1 class="page-title mb-0">Project Monitoring</h1>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
            <div class="monitor-filters">
                <a href="#" class="monitor-pill active" data-filter="all">All</a>
                <?php foreach (project_statuses() as $key => $label): ?>
                <a href="#" class="monitor-pill" data-filter="<?= $key ?>"><?= htmlspecialchars($label) ?></a>
                <?php endforeach; ?>
            </div>
            <div class="monitor-search-wrap">
                <i class="bi bi-search monitor-search-icon"></i>
                <input type="text" class="form-control monitor-search" placeholder="Search projects...">
            </div>
        </div>

        <div class="monitor-card">
            <div class="table-responsive">
                <table class="table align-middle mb-0 monitor-table">
                    <thead>
                        <tr>
                            <th>CODE</th><th>PROJECT</th><th>CUSTOMER</th>
                            <th>STATUS</th><th>TARGET</th><th class="text-center">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monitor_projects as $p):
                            $step = project_step_index($p['status']);
                            // "Complete" is offered once a project is actually in production.
                            $can_complete = $step !== null && $step >= 2 && $p['status'] !== 'completed';
                        ?>
                        <tr data-project="<?= $p['code'] ?>" data-status="<?= project_status_key($p['status']) ?>">
                            <td><?= $p['code'] ?></td>
                            <td class="monitor-project"><?= htmlspecialchars($p['project']) ?></td>
                            <td class="monitor-customer"><?= htmlspecialchars($p['customer']) ?></td>
                            <td><?= project_status_badge($p['status']) ?></td>
                            <td class="monitor-target"><?= htmlspecialchars($p['target']) ?></td>
                            <td class="text-center">
                                <div class="monitor-actions">
                                    <div class="action-left">
                                        <?php if ($can_complete): ?>
                                        <a href="#" class="monitor-action complete"><i class="bi bi-check-circle"></i><span>Complete</span></a>
                                        <?php endif; ?>
                                    </div>
                                    <div class="action-right">
                                        <a href="#" class="monitor-action view-project-btn"
                                            data-code="<?= $p['code'] ?>"
                                            data-project="<?= htmlspecialchars($p['project']) ?>"
                                            data-customer="<?= htmlspecialchars($p['customer']) ?>"
                                            data-target="<?= htmlspecialchars($p['target']) ?>"
                                            data-status="<?= project_status_key($p['status']) ?>"
                                            data-details="<?= htmlspecialchars($p['details']) ?>"
                                            data-start="<?= $p['start'] ?>" data-progress="<?= $p['progress'] ?>"
                                            data-approver="<?= htmlspecialchars($p['approver']) ?>"
                                            data-materials-key="<?= $p['materials_key'] ?>"
                                            data-updates-key="<?= $p['updates_key'] ?>">
                                            <i class="bi bi-eye"></i><span>View</span>
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


<!-- VIEW PROJECT MODAL -->
<div class="modal fade" id="viewProjectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">

            <!-- HERO HEADER -->
            <div class="pvm-hero">
                <div class="pvm-hero-top">
                    <div>
                        <div class="pvm-title" id="viewProjectModalLabel">—</div>
                        <div class="pvm-code"  id="viewProjectCode">—</div>
                    </div>
                    <div class="pvm-hero-actions">
                        <select class="pvm-state-select state-active" id="pvmStateSelect">
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                        </select>
                        <a href="#" class="pvm-edit-btn" id="pvmEditBtn">
                            <i class="bi bi-pencil"></i> Edit Project
                        </a>
                        <button type="button" class="btn-close ms-1" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <!-- Step tracker -->
                <div class="pvm-step-tracker">
                    <div style="margin-bottom:8px;"><span class="monitor-badge" id="viewProjectStatus">—</span></div>
                    <div class="pvm-steps" id="pvmStepTracker"></div>
                </div>
            </div>

            <!-- BODY -->
            <div class="modal-body">

                <!-- Detail grid -->
                <div class="pvm-details-section">
                    <div class="pvm-grid">
                        <div>
                            <div class="pvm-field-label">Customer</div>
                            <div class="pvm-field-value" id="viewProjectCustomer">—</div>
                        </div>
                        <div>
                            <div class="pvm-field-label">Target Date</div>
                            <div class="pvm-field-value" id="viewProjectTarget">—</div>
                        </div>
                        <div id="wrapStartDate">
                            <div class="pvm-field-label">Start Date (Approved)</div>
                            <div class="pvm-field-value" id="viewProjectStart">—</div>
                        </div>
                        <div id="wrapApprover">
                            <div class="pvm-field-label">Approved By</div>
                            <div class="pv-approver" id="viewProjectApprover">
                                <i class="bi bi-person-check-fill"></i><span>—</span>
                            </div>
                        </div>
                        <div id="wrapMaterials">
                            <div class="pvm-field-label">Materials Used</div>
                            <button class="btn-materials" id="btnOpenMaterials">
                                <i class="bi bi-box-seam"></i> View Materials List
                            </button>
                        </div>
                    </div>
                    <div style="margin-top:14px;">
                        <div class="pvm-field-label">Project Details</div>
                        <div class="pvm-field-value" id="viewProjectDetails" style="font-weight:400;color:#4b5563;line-height:1.6;">—</div>
                    </div>
                </div>

                <!-- Project Updates -->
                <div class="pvm-updates-section">
                    <div class="pvm-section-title">
                        <i class="bi bi-activity"></i> Project Updates
                    </div>
                    <!-- Composer -->
                    <div class="pvm-composer">
                        <div class="pvm-composer-avatar"><?= $user_initial ?></div>
                        <div class="pvm-composer-box">
                            <textarea class="pvm-composer-textarea" id="pvmUpdateInput" rows="2"
                                placeholder="Post an update about this project..."></textarea>
                            <div class="pvm-composer-footer">
                                <button class="pvm-attach-btn" type="button">
                                    <i class="bi bi-image"></i> Attach Image
                                </button>
                                <button class="pvm-post-btn" id="pvmPostBtn" type="button">
                                    <i class="bi bi-send"></i> Post Update
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Feed -->
                    <div id="pvmUpdatesFeed"></div>
                </div>

            </div>
        </div>
    </div>
</div>


<!-- MATERIALS MODAL -->
<div class="modal fade" id="materialsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-white fw-semibold">
                    <i class="bi bi-box-seam me-2"></i>Materials List — <span id="matProjectName">—</span>
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <table class="table mb-0 mat-table">
                    <thead><tr><th>#</th><th>Material</th><th>Specification</th><th>Qty</th><th>Unit</th></tr></thead>
                    <tbody id="matTableBody"></tbody>
                </table>
            </div>
            <div class="modal-footer" style="background:#f8fafc;border-top:1px solid #e5e7eb;border-radius:0 0 12px 12px;">
                <small class="text-muted me-auto" id="matSummary"></small>
                <button class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= project_status_js() ?>
<script>
const MATERIALS = {
    '042': [
        {name:'Melamine Board 18mm',spec:'White, 4x8 ft',qty:24,unit:'sheets',status:'available'},
        {name:'PVC Edge Banding',spec:'White, 22mm width',qty:150,unit:'meters',status:'available'},
        {name:'Soft-Close Hinge',spec:'35mm cup, full overlay',qty:96,unit:'pcs',status:'available'},
        {name:'Cabinet Handle',spec:'Stainless, 128mm hole',qty:48,unit:'pcs',status:'ordered'},
        {name:'Drawer Slide 400mm',spec:'Full extension, 40kg',qty:24,unit:'pairs',status:'available'},
        {name:'Dowel Pin 8x35mm',spec:'Hardwood',qty:500,unit:'pcs',status:'available'},
        {name:'Wood Glue',spec:'PVA, 1L bottle',qty:6,unit:'bottles',status:'low'},
    ],
    '041': [
        {name:'MDF Board 18mm',spec:'E1 grade, 4x8 ft',qty:18,unit:'sheets',status:'available'},
        {name:'White Laminate',spec:'Hi-gloss, 1.22x2.44m',qty:18,unit:'sheets',status:'ordered'},
        {name:'Soft-Close Hinge',spec:'35mm, half overlay',qty:72,unit:'pcs',status:'available'},
        {name:'Cam Lock 15mm',spec:'Zinc alloy',qty:200,unit:'pcs',status:'available'},
        {name:'Adjustable Shelf Pin',spec:'5mm, chrome',qty:120,unit:'pcs',status:'available'},
    ],
    '039': [
        {name:'Tempered Glass 10mm',spec:'Clear, custom cut',qty:8,unit:'panels',status:'available'},
        {name:'Acrylic Panel 6mm',spec:'Frosted white, back-lit',qty:4,unit:'panels',status:'available'},
        {name:'Steel Flat Bar 40x5',spec:'Mild steel, 6m length',qty:12,unit:'lengths',status:'available'},
        {name:'Powder Coat Paint',spec:'Matte black, RAL 9005',qty:4,unit:'liters',status:'available'},
        {name:'LED Strip Light',spec:'Warm white, 12V IP20',qty:10,unit:'meters',status:'available'},
        {name:'Glass Shelf Bracket',spec:'Chrome, 150mm',qty:16,unit:'pcs',status:'available'},
    ],
    '037': [
        {name:'Particle Board 18mm',spec:'E1 grade, 4x8 ft',qty:30,unit:'sheets',status:'available'},
        {name:'PVC Edge Banding',spec:'Beige, 22mm width',qty:200,unit:'meters',status:'low'},
        {name:'Pull-Out Shelf Slide',spec:'450mm, 25kg',qty:20,unit:'pairs',status:'ordered'},
        {name:'Soft-Close Hinge',spec:'35mm, full overlay',qty:80,unit:'pcs',status:'available'},
        {name:'Ventilation Grille',spec:'PVC, 150x100mm',qty:10,unit:'pcs',status:'available'},
        {name:'Cam Lock 15mm',spec:'Zinc alloy',qty:250,unit:'pcs',status:'available'},
    ],
};

const SEED_UPDATES = {
    '042': [
        {author:'Marco Reyes',initials:'MR',time:'Mar 14, 2026 · 3:00 PM',
         text:'Cabinet boxes assembled and sanded. Starting laminate application tomorrow.',
         attachments:[{label:'Cabinet Frame',cls:'chip-blue'},{label:'Sanding Detail',cls:'chip-yellow'}]},
        {author:'Ana Cruz',initials:'AC',time:'Mar 12, 2026 · 10:30 AM',
         text:'All custom hinges installed and tested. Soft-close function confirmed on all doors.',
         attachments:[{label:'Hinge Test',cls:'chip-green'}]},
        {author:'Marco Reyes',initials:'MR',time:'Mar 10, 2026 · 9:00 AM',
         text:'Materials delivered and staged on site. Cutting list verified against purchase order.',
         attachments:[]},
    ],
    '041': [
        {author:'Lena Tan',initials:'LT',time:'Mar 11, 2026 · 2:00 PM',
         text:'Site measurements confirmed. Drawings updated and sent for final sign-off.',
         attachments:[{label:'Revised Plan',cls:'chip-purple'}]},
    ],
    '039': [
        {author:'Sofia Lim',initials:'SL',time:'Feb 28, 2026 · 4:00 PM',
         text:'Final installation complete. Handover document signed by Park Residences representative.',
         attachments:[{label:'Handover Doc',cls:'chip-green'},{label:'Site Photos',cls:'chip-blue'}]},
        {author:'Jose Reyes',initials:'JR',time:'Feb 25, 2026 · 11:00 AM',
         text:'LED strip lights wired and tested. All glass shelves levelled and secured.',
         attachments:[{label:'LED Test',cls:'chip-yellow'}]},
    ],
    '037': [
        {author:'Marco Reyes',initials:'MR',time:'Mar 13, 2026 · 1:00 PM',
         text:'Pull-out shelf slides installed on all units. Smooth operation confirmed.',
         attachments:[{label:'Slide Install',cls:'chip-green'}]},
        {author:'Ben Garcia',initials:'BG',time:'Mar 10, 2026 · 8:30 AM',
         text:'Edge banding applied. Low stock on beige PVC noted — additional order placed.',
         attachments:[]},
    ],
};

// PROJECT_STEPS / getStepIdx / statusLabel / statusClass come from project_status_js().
// An off-track status (On Hold, Rejected) yields stepIdx -1: no step is marked active.
function renderStepTracker(containerId,stepIdx){
    let html='';
    PROJECT_STEPS.forEach(function(label,i){
        if(i>0) html+='<div class="pvm-step-connector'+(i<=stepIdx?' done':'')+'"></div>';
        const cls=i<stepIdx?'done':i===stepIdx?'active':'';
        const dot=i<stepIdx?'<i class="bi bi-check2" style="font-size:0.62rem"></i>':(i+1);
        html+='<div class="pvm-step '+cls+'">'
            +'<div class="pvm-step-dot">'+dot+'</div>'
            +'<div class="pvm-step-label">'+label+'</div>'
            +'</div>';
    });
    document.getElementById(containerId).innerHTML=html;
}

const liveUpdates = {};
const CHIPS = ['chip-green','chip-blue','chip-yellow','chip-purple','chip-pink'];

function fmtDate(iso){
    if(!iso) return '—';
    return new Date(iso+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
}
function fmtNow(){
    return new Date().toLocaleString('en-PH',{month:'short',day:'numeric',year:'numeric',hour:'2-digit',minute:'2-digit'});
}

// ── RENDER FEED ──
let currentUpdatesKey='';
function renderFeed(key){
    const feed=document.getElementById('pvmUpdatesFeed');
    const all=[...(liveUpdates[key]||[]),...(SEED_UPDATES[key]||[])];
    if(!all.length){
        feed.innerHTML=`<div class="pvm-empty-updates"><i class="bi bi-chat-left-dots"></i>No updates yet. Be the first to post one.</div>`;
        return;
    }
    feed.innerHTML=all.map((u,idx)=>{
        const isLast=idx===all.length-1;
        const chips=(u.attachments||[]).map((a,ci)=>`<span class="pvm-attachment-chip ${a.cls||CHIPS[ci%CHIPS.length]}">${a.label}</span>`).join('');
        return `<div class="pvm-update-item">
            <div class="pvm-update-dot-col">
                <div class="pvm-update-dot"></div>
                ${!isLast?'<div class="pvm-update-dot-line"></div>':''}
            </div>
            <div class="pvm-update-content">
                <div class="pvm-update-meta">
                    <div class="pvm-update-avatar">${u.initials}</div>
                    <span class="pvm-update-author">${u.author}</span>
                    <span class="pvm-update-time">· ${u.time}</span>
                </div>
                <div class="pvm-update-text">${u.text}</div>
                ${chips?`<div class="pvm-attachments">${chips}</div>`:''}
            </div>
        </div>`;
    }).join('');
}

// ── POST UPDATE ──
document.getElementById('pvmPostBtn').addEventListener('click',function(){
    const input=document.getElementById('pvmUpdateInput');
    const text=input.value.trim();
    if(!text) return;
    if(!liveUpdates[currentUpdatesKey]) liveUpdates[currentUpdatesKey]=[];
    liveUpdates[currentUpdatesKey].unshift({
        author:'<?= htmlspecialchars($user_name) ?>',
        initials:'<?= $user_initial ?>',
        time:fmtNow(), text:text, attachments:[]
    });
    input.value='';
    renderFeed(currentUpdatesKey);
});
document.getElementById('pvmUpdateInput').addEventListener('keydown',function(e){
    if(e.key==='Enter'&&e.ctrlKey) document.getElementById('pvmPostBtn').click();
});

// ── STATE DROPDOWN ──
document.getElementById('pvmStateSelect').addEventListener('change',function(){
    this.className='pvm-state-select '+(this.value==='active'?'state-active':'state-paused');
});

// ── FILL MODAL ──
let currentMaterialsKey='', currentProjectName='';
function fillProjectModal(btn){
    const d=btn.dataset;
    document.getElementById('viewProjectModalLabel').textContent = d.project||'—';
    document.getElementById('viewProjectCode').textContent       = d.code||'—';

    // An on-hold project shows as Paused; everything else is Active.
    const paused=statusKey(d.status)==='on_hold';
    const sel=document.getElementById('pvmStateSelect');
    sel.value=paused?'paused':'active';
    sel.className='pvm-state-select '+(paused?'state-paused':'state-active');

    // Status + step tracker
    const badge=document.getElementById('viewProjectStatus');
    badge.textContent=statusLabel(d.status);
    badge.className='monitor-badge '+statusClass(d.status);
    renderStepTracker('pvmStepTracker', getStepIdx(d.status));

    // Fields
    document.getElementById('viewProjectCustomer').textContent = d.customer||'—';
    document.getElementById('viewProjectTarget').textContent   = d.target||'—';
    document.getElementById('viewProjectDetails').textContent  = d.details||'—';
    document.getElementById('viewProjectStart').textContent    = d.start?fmtDate(d.start):'—';

    // Approver
    const approverWrap=document.getElementById('wrapApprover');
    const approverSpan=document.querySelector('#viewProjectApprover span');
    if(d.approver){ approverSpan.textContent=d.approver; approverWrap.style.display=''; }
    else approverWrap.style.display='none';

    // Materials
    currentMaterialsKey=d.materialsKey||'';
    currentProjectName=d.project||'';
    document.getElementById('wrapMaterials').style.display=currentMaterialsKey?'':'none';

    // Updates
    currentUpdatesKey=d.updatesKey||'';
    renderFeed(currentUpdatesKey);
}

// ── MATERIALS MODAL ──
function openMaterialsModal(){
    const rows=MATERIALS[currentMaterialsKey]||[];
    document.getElementById('matProjectName').textContent=currentProjectName;
    document.getElementById('matTableBody').innerHTML=rows.length
        ?rows.map((m,i)=>`<tr><td class="text-muted">${i+1}</td><td><strong>${m.name}</strong></td><td class="text-muted" style="font-size:.7rem">${m.spec}</td><td>${m.qty}</td><td class="text-muted">${m.unit}</td></tr>`).join('')
        :`<tr><td colspan="5" class="text-center text-muted py-4">No materials listed.</td></tr>`;
    const total=rows.length, low=rows.filter(r=>r.status==='low').length;
    document.getElementById('matSummary').textContent=`${total} material${total!==1?'s':''} listed`+(low?` · ${low} low stock`:'');
    new bootstrap.Modal(document.getElementById('materialsModal')).show();
}

// ── INIT ──
document.addEventListener('DOMContentLoaded',function(){
    document.querySelectorAll('.view-project-btn').forEach(btn=>{
        btn.addEventListener('click',function(e){
            e.preventDefault();
            fillProjectModal(this);
            new bootstrap.Modal(document.getElementById('viewProjectModal')).show();
        });
    });
    document.getElementById('btnOpenMaterials').addEventListener('click',openMaterialsModal);

    // Status filter pills — match on the row's canonical status key.
    document.querySelectorAll('.monitor-pill').forEach(pill=>{
        pill.addEventListener('click',function(e){
            e.preventDefault();
            document.querySelectorAll('.monitor-pill').forEach(p=>p.classList.remove('active'));
            this.classList.add('active');
            const want=this.dataset.filter;
            document.querySelectorAll('.monitor-table tbody tr').forEach(row=>{
                row.style.display=(want==='all'||row.dataset.status===want)?'':'none';
            });
        });
    });

    const params=new URLSearchParams(window.location.search);
    const projectId=params.get('project'), open=params.get('open');
    if(projectId){
        const row=document.querySelector(`[data-project="${projectId}"]`);
        if(row){
            row.scrollIntoView({behavior:'smooth',block:'center'});
            row.style.backgroundColor='#f0fdfa';
            setTimeout(()=>row.style.backgroundColor='',3000);
            if(open==='view'){
                const viewBtn=row.querySelector('.view-project-btn');
                if(viewBtn){
                    fillProjectModal(viewBtn);
                    new bootstrap.Modal(document.getElementById('viewProjectModal')).show();
                    window.history.replaceState({},document.title,'monitoring.php');
                }
            }
        }
    }
});
</script>
</body>
</html>