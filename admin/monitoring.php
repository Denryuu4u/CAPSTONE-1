<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

require_once __DIR__ . '/../includes/project_status.php';
require_once __DIR__ . '/../includes/helpers.php';

$active_page = 'monitoring';
require_page($active_page); // role gate
$user_name = $_SESSION['full_name'] ?? 'Admin User';
$user_initial = strtoupper(substr($user_name, 0, 1));

// Auto-confirm any completed projects the client left unconfirmed past the deadline.
auto_confirm_completions();

// Live projects (exclude rejected). materials_key / updates_key are the project id.
$monitor_projects = [];
foreach (db()->query(
    "SELECT p.id, p.project_code, p.project_name, c.name AS customer, p.status,
            p.target_completion, p.start_date, p.progress, p.approver, p.description,
            p.completion_notified_at, p.client_confirmed_at,
            q.id AS quotation_id, q.quote_code, q.total_amount AS quote_total, q.status AS quote_status
       FROM projects p
       LEFT JOIN customers c ON c.id = p.customer_id
       LEFT JOIN quotations q ON q.id = (
            SELECT id FROM quotations qq WHERE qq.project_id = p.id
             ORDER BY (qq.status='Approved') DESC, qq.id DESC LIMIT 1)
      WHERE p.status <> 'rejected'
      ORDER BY p.created_at DESC, p.id DESC"
) as $r) {
    $monitor_projects[] = [
        'id'       => (int) $r['id'],
        'code'     => $r['project_code'],
        'project'  => $r['project_name'],
        'customer' => $r['customer'] ?? '—',
        'status'   => $r['status'],
        'target'   => $r['target_completion'] ? date('M d, Y', strtotime($r['target_completion'])) : '—',
        'start'    => $r['start_date'] ?? '',
        'progress' => (int) $r['progress'],
        'approver' => $r['approver'] ?? '',
        'details'  => $r['description'] ?? '',
        'confirmed'     => $r['client_confirmed_at'] ? date('M d, Y', strtotime($r['client_confirmed_at'])) : '',
        'awaiting_conf' => ($r['status'] === 'completed' && empty($r['client_confirmed_at'])) ? '1' : '0',
        'quote_id'      => (int) ($r['quotation_id'] ?? 0),
        'quote_code'    => $r['quote_code'] ?? '',
        'quote_total'   => $r['quote_total'] !== null ? peso((float) $r['quote_total']) : '',
        'quote_status'  => $r['quote_status'] ?? '',
        'materials_key' => (string) $r['id'],
        'updates_key'   => (string) $r['id'],
    ];
}

// Materials + updates per project id, for the modal JS.
$materialsByProject = [];
foreach (db()->query("SELECT project_id, material, specification, qty, unit, status FROM project_materials ORDER BY sort_order, id") as $m) {
    $materialsByProject[(string) $m['project_id']][] = [
        'name' => $m['material'], 'spec' => $m['specification'], 'qty' => (float) $m['qty'],
        'unit' => $m['unit'], 'status' => $m['status'],
    ];
}
$updatesByProject = [];
foreach (db()->query("SELECT project_id, author_name, update_text, attachment_path, created_at FROM project_updates ORDER BY created_at DESC, id DESC") as $u) {
    $updatesByProject[(string) $u['project_id']][] = [
        'author'   => $u['author_name'] ?: 'Vast Solutions',
        'initials' => strtoupper(mb_substr($u['author_name'] ?: 'V', 0, 1)),
        'time'     => date('M d, Y · g:i A', strtotime($u['created_at'])),
        'text'     => $u['update_text'],
        'image'    => $u['attachment_path'] ? '../' . $u['attachment_path'] : null,
        'attachments' => [],
    ];
}
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
                            // "Complete" is only offered at the final phase before completion,
                            // so a project can't jump straight to Completed from mid-flow.
                            $can_complete = project_status_key($p['status']) === 'final_approval';
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
                                            data-confirmed="<?= htmlspecialchars($p['confirmed']) ?>"
                                            data-awaiting-conf="<?= $p['awaiting_conf'] ?>"
                                            data-quote-id="<?= $p['quote_id'] ?>"
                                            data-quote-code="<?= htmlspecialchars($p['quote_code']) ?>"
                                            data-quote-total="<?= htmlspecialchars($p['quote_total']) ?>"
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
                        <select class="pvm-state-select" id="pvmPhaseSelect" title="Update project phase">
                            <?php foreach (project_phases() as $key => $label): ?>
                            <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
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
                        <div id="wrapClientConfirm" style="display:none;">
                            <div class="pvm-field-label">Client Confirmation</div>
                            <div class="pvm-field-value" id="viewProjectConfirm">—</div>
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
                        <div id="wrapQuotation">
                            <div class="pvm-field-label">Quotation <span id="pvmQuoteMeta" style="color:#6b7280;font-weight:500;"></span></div>
                            <a class="btn-materials" id="btnViewQuotation" target="_blank" style="border-color:#2563eb;color:#2563eb;background:#eff6ff;">
                                <i class="bi bi-file-earmark-text"></i> View Quotation
                            </a>
                        </div>
                        <div id="wrapTracking">
                            <div class="pvm-field-label">Cost Tracking</div>
                            <button class="btn-materials" id="btnOpenTracking" style="border-color:#7c3aed;color:#7c3aed;background:#f5f3ff;">
                                <i class="bi bi-cash-coin"></i> Open Cost Tracking
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
                            <input type="file" id="pvmImageInput" accept="image/*" style="display:none;">
                            <div class="pvm-composer-footer">
                                <button class="pvm-attach-btn" type="button" id="pvmAttachBtn">
                                    <i class="bi bi-image"></i> <span id="pvmAttachLabel">Attach Image</span>
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


<!-- COST TRACKING MODAL -->
<div class="modal fade" id="trackingModal" tabindex="-1" aria-hidden="true" style="z-index:1075;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" style="border-radius:14px;border:none;">
      <div class="modal-header" style="background:#0d1b2a;color:#fff;border-radius:14px 14px 0 0;">
        <h6 class="modal-title fw-semibold">
          <i class="bi bi-cash-coin me-2"></i>Project Tracking — <span id="trkProjectName">—</span>
          <span id="trkProjectCode" class="ms-2" style="font-size:.72rem;opacity:.7;"></span>
        </h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="background:#f4f5f7;">

        <!-- Summary strip -->
        <div class="trk-summary">
          <div class="trk-sum-card">
            <div class="trk-sum-label">Project Amount</div>
            <div class="trk-amount-wrap">₱ <input type="number" step="0.01" id="trkAmount" class="trk-amount-input" placeholder="0.00"></div>
          </div>
          <div class="trk-sum-card"><div class="trk-sum-label">Project Cost</div><div class="trk-sum-num" id="trkCost">₱0.00</div></div>
          <div class="trk-sum-card" id="trkProfitCard"><div class="trk-sum-label">Profit / Loss</div><div class="trk-sum-num" id="trkProfit">₱0.00</div></div>
        </div>

        <!-- Expenses -->
        <div class="trk-section">
          <div class="trk-section-head">
            <span><i class="bi bi-box-seam me-1"></i>Expenses (materials / hardware)</span>
            <button type="button" class="trk-add-btn" data-add="expense"><i class="bi bi-plus-lg"></i> Add expense</button>
          </div>
          <div class="table-responsive">
            <table class="trk-table">
              <thead><tr><th style="width:60%">Item</th><th class="text-end">Amount</th><th style="width:44px"></th></tr></thead>
              <tbody id="trkExpenseBody"></tbody>
              <tfoot><tr><td class="fw-bold">Total Expenses</td><td class="text-end fw-bold" id="trkExpenseTotal">₱0.00</td><td></td></tr></tfoot>
            </table>
          </div>
        </div>

        <!-- Labor: Assembly -->
        <div class="trk-section">
          <div class="trk-section-head">
            <span><i class="bi bi-tools me-1"></i>Labor — Assembly</span>
            <button type="button" class="trk-add-btn" data-add="assembly"><i class="bi bi-plus-lg"></i> Add row</button>
          </div>
          <div class="table-responsive">
            <table class="trk-table">
              <thead><tr><th>Date</th><th>Manpower</th><th class="text-end">Rate</th><th class="text-end">Gas &amp; Toll</th><th class="text-end">Line Total</th><th style="width:44px"></th></tr></thead>
              <tbody id="trkAssemblyBody"></tbody>
              <tfoot><tr><td colspan="4" class="fw-bold">Total Assembly</td><td class="text-end fw-bold" id="trkAssemblyTotal">₱0.00</td><td></td></tr></tfoot>
            </table>
          </div>
        </div>

        <!-- Labor: Installation -->
        <div class="trk-section">
          <div class="trk-section-head">
            <span><i class="bi bi-truck me-1"></i>Labor — Installation</span>
            <button type="button" class="trk-add-btn" data-add="installation"><i class="bi bi-plus-lg"></i> Add row</button>
          </div>
          <div class="table-responsive">
            <table class="trk-table">
              <thead><tr><th>Date</th><th>Manpower</th><th class="text-end">Rate</th><th class="text-end">Gas &amp; Toll</th><th class="text-end">Line Total</th><th style="width:44px"></th></tr></thead>
              <tbody id="trkInstallBody"></tbody>
              <tfoot><tr><td colspan="4" class="fw-bold">Total Installation</td><td class="text-end fw-bold" id="trkInstallTotal">₱0.00</td><td></td></tr></tfoot>
            </table>
          </div>
        </div>

        <div class="trk-section">
          <div class="trk-section-head"><span><i class="bi bi-sticky me-1"></i>Notes</span></div>
          <textarea id="trkNotes" class="form-control" rows="2" placeholder="Optional notes for this tracking sheet..."></textarea>
        </div>
      </div>
      <div class="modal-footer" style="background:#fff;border-top:1px solid #e5e7eb;border-radius:0 0 14px 14px;gap:8px;">
        <a href="#" class="btn btn-sm btn-outline-secondary" id="trkDownloadPdf" target="_blank"><i class="bi bi-printer me-1"></i>PDF</a>
        <a href="#" class="btn btn-sm btn-outline-success" id="trkDownloadXlsx"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
        <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-sm btn-success" id="trkSaveBtn"><i class="bi bi-check-lg me-1"></i>Save Tracking</button>
      </div>
    </div>
  </div>
</div>

<style>
  .trk-summary{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px;}
  .trk-sum-card{flex:1 1 160px;background:#fff;border:1px solid #e5e7eb;border-radius:10px;padding:12px 16px;}
  .trk-sum-label{font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#9ca3af;margin-bottom:4px;}
  .trk-sum-num{font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;color:#0d1b2a;}
  #trkProfitCard.profit .trk-sum-num{color:#0a7a60;} #trkProfitCard.loss .trk-sum-num{color:#dc2626;}
  .trk-amount-wrap{font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:#0d1b2a;display:flex;align-items:center;gap:4px;}
  .trk-amount-input{border:1px solid #e5e7eb;border-radius:6px;padding:3px 8px;font-size:1rem;font-weight:700;width:130px;font-family:'Inter',sans-serif;}
  .trk-amount-input:focus{outline:none;border-color:#0D9676;}
  .trk-section{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px 16px;margin-bottom:14px;}
  .trk-section-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;font-family:'Syne',sans-serif;font-size:.82rem;font-weight:700;color:#0d1b2a;}
  .trk-add-btn{border:1px solid #0D9676;color:#0D9676;background:#f0fdf9;border-radius:7px;padding:4px 10px;font-size:.72rem;font-weight:600;cursor:pointer;}
  .trk-add-btn:hover{background:#0D9676;color:#fff;}
  .trk-table{width:100%;border-collapse:collapse;font-size:.78rem;}
  .trk-table thead th{background:#f8fafc;font-size:.6rem;font-weight:700;color:#9ca3af;letter-spacing:.04em;text-transform:uppercase;padding:8px 10px;border-bottom:1px solid #e5e7eb;}
  .trk-table tbody td{padding:6px 8px;border-bottom:1px solid #f0f0f0;vertical-align:middle;}
  .trk-table tfoot td{padding:9px 10px;border-top:2px solid #e5e7eb;font-size:.82rem;}
  .trk-table input{border:1px solid #e5e7eb;border-radius:6px;padding:5px 8px;font-size:.76rem;width:100%;font-family:'Inter',sans-serif;}
  .trk-table input:focus{outline:none;border-color:#0D9676;}
  .trk-table input.trk-num{text-align:right;}
  .trk-line-total{text-align:right;font-weight:600;color:#374151;white-space:nowrap;}
  .trk-del{border:none;background:transparent;color:#dc2626;cursor:pointer;font-size:1rem;line-height:1;}
  .trk-del:hover{color:#b91c1c;}
  .trk-empty td{color:#9ca3af;text-align:center;padding:12px;font-size:.74rem;}
</style>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= project_status_js() ?>
<script>
// Materials + updates come from the database, keyed by project id.
const MATERIALS = <?= json_encode($materialsByProject, JSON_UNESCAPED_UNICODE) ?: '{}' ?>;
const SEED_UPDATES = <?= json_encode($updatesByProject, JSON_UNESCAPED_UNICODE) ?: '{}' ?>;

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
                ${u.image?`<a href="${u.image}" target="_blank"><img src="${u.image}" alt="attachment" style="max-width:220px;max-height:160px;border-radius:8px;margin-top:6px;border:1px solid #e5e7eb;display:block;object-fit:cover;"></a>`:''}
                ${chips?`<div class="pvm-attachments">${chips}</div>`:''}
            </div>
        </div>`;
    }).join('');
}

// ── ATTACH IMAGE ──
document.getElementById('pvmAttachBtn').addEventListener('click',function(){
    document.getElementById('pvmImageInput').click();
});
document.getElementById('pvmImageInput').addEventListener('change',function(){
    document.getElementById('pvmAttachLabel').textContent = this.files.length ? this.files[0].name : 'Attach Image';
});

// ── POST UPDATE (persists to the database + notifies the client) ──
let currentProjectId = 0;
let currentRow = null; // the monitoring-table <tr> currently open in the modal
document.getElementById('pvmPostBtn').addEventListener('click',function(){
    const input=document.getElementById('pvmUpdateInput');
    const imgInput=document.getElementById('pvmImageInput');
    const text=input.value.trim();
    if((!text && !imgInput.files.length) || !currentProjectId) return;
    const btn=this; btn.disabled=true;
    const fd=new FormData();
    fd.append('project_id',currentProjectId);
    fd.append('text',text);
    if(imgInput.files.length) fd.append('image',imgInput.files[0]);
    fetch('post_update.php',{method:'POST',body:fd})
        .then(async r=>{const d=await r.json().catch(()=>({ok:false})); if(!r.ok||!d.ok) throw new Error(d.error||'Failed'); return d;})
        .then(d=>{
            if(!liveUpdates[currentUpdatesKey]) liveUpdates[currentUpdatesKey]=[];
            liveUpdates[currentUpdatesKey].unshift({author:d.update.author,initials:d.update.initials,time:d.update.time,text:d.update.text,image:d.update.image,attachments:[]});
            input.value='';
            imgInput.value='';
            document.getElementById('pvmAttachLabel').textContent='Attach Image';
            renderFeed(currentUpdatesKey);
            vsToast('Update posted.');
        })
        .catch(e=>alert(e.message))
        .finally(()=>btn.disabled=false);
});

// ── UPDATE PHASE ──
// Tracks the phase the currently-open project is at, so we can lock backward moves.
let currentPhaseIdx = -1;

function applyPhase(status, label){
    const badge=document.getElementById('viewProjectStatus');
    badge.textContent=label; badge.className='monitor-badge '+statusClass(status);
    renderStepTracker('pvmStepTracker', getStepIdx(status));
    currentPhaseIdx = getStepIdx(status);
    lockPhaseOptions(currentPhaseIdx);
    // Reflect the change on the underlying table row immediately.
    if(currentRow){
        currentRow.dataset.status = status;
        const cell = currentRow.querySelector('td:nth-child(4)');
        if(cell) cell.innerHTML = '<span class="monitor-badge '+statusClass(status)+'">'+label+'</span>';
        updateCompleteButton(currentRow, status);
    }
}

// The "Complete" quick action only exists at Final Approval — add/remove it live
// as a project's phase changes, so no page refresh is needed.
function updateCompleteButton(row, status){
    const left = row.querySelector('.action-left');
    if(!left) return;
    const existing = left.querySelector('.monitor-action.complete');
    if(statusKey(status)==='final_approval'){
        if(!existing){
            left.innerHTML = '<a href="#" class="monitor-action complete"><i class="bi bi-check-circle"></i><span>Complete</span></a>';
        }
    } else if(existing){
        existing.remove();
    }
}

// Disable phase options that come before the project's current phase.
function lockPhaseOptions(idx){
    const sel=document.getElementById('pvmPhaseSelect');
    if(!sel) return;
    [...sel.options].forEach(o=>{ o.disabled = getStepIdx(o.value) < idx; });
}

document.getElementById('pvmPhaseSelect').addEventListener('change',function(){
    const sel=this;
    const status=sel.value;
    const label=sel.options[sel.selectedIndex].text;
    if(!currentProjectId){ return; }
    const targetIdx=getStepIdx(status);
    // Guard against moving backward (also enforced server-side).
    if(currentPhaseIdx>=0 && targetIdx>=0 && targetIdx<currentPhaseIdx){
        sel.value = statusKeyForIdx(currentPhaseIdx);
        vsAlert('This project is already at a later phase and can\'t be moved back.', {title:'Phase locked'});
        return;
    }
    const isCompleting = (status==='completed' && currentPhaseIdx!==getStepIdx('completed'));
    const msg = isCompleting
        ? 'Mark this project as Completed? The client will be notified to confirm they received it.'
        : 'Update this project\'s phase to "'+label+'"?';
    vsConfirm(msg, {title: isCompleting ? 'Complete project' : 'Update phase', okText: isCompleting ? 'Mark completed' : 'Update'}).then(function(ok){
        if(!ok){ sel.value = statusKeyForIdx(currentPhaseIdx); return; }
        fetch('update_project_status.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:new URLSearchParams({project_id:currentProjectId,status})})
            .then(async r=>{const d=await r.json().catch(()=>({ok:false})); if(!r.ok||!d.ok) throw new Error(d.error||'Failed'); return d;})
            .then(d=>{
                applyPhase(status, d.label);
                if(d.completing){
                    document.getElementById('wrapClientConfirm').style.display='';
                    document.getElementById('viewProjectConfirm').innerHTML='<span style="color:#d97706;">Awaiting client confirmation</span>';
                    vsToast('Project marked completed. The client has been notified to confirm.');
                } else {
                    vsToast('Project phase updated to "'+d.label+'".');
                }
            })
            .catch(e=>{ sel.value = statusKeyForIdx(currentPhaseIdx); alert(e.message); });
    });
});

// Map a step index back to its status key (for reverting the select).
function statusKeyForIdx(idx){
    const keys=Object.keys(PROJECT_STATUS.phases);
    return keys[idx] || keys[0];
}
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
    currentRow = btn.closest ? btn.closest('tr') : null;
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
    currentPhaseIdx = getStepIdx(d.status);
    lockPhaseOptions(currentPhaseIdx);

    // Client confirmation (only meaningful once completed)
    const confWrap=document.getElementById('wrapClientConfirm');
    const confVal=document.getElementById('viewProjectConfirm');
    if(statusKey(d.status)==='completed'){
        confWrap.style.display='';
        if(d.confirmed){ confVal.innerHTML='<span style="color:#0a7a60;">Confirmed on '+d.confirmed+'</span>'; }
        else if(d.awaitingConf==='1'){ confVal.innerHTML='<span style="color:#d97706;">Awaiting client confirmation</span>'; }
        else { confVal.textContent='—'; }
    } else {
        confWrap.style.display='none';
    }

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

    // Quotation (view the quote made for this project)
    const wrapQ=document.getElementById('wrapQuotation');
    const qid=parseInt(d.quoteId||0)||0;
    if(qid){
        wrapQ.style.display='';
        document.getElementById('btnViewQuotation').href='<?= BASE_URL ?>/download_quote.php?id='+qid;
        const meta=(d.quoteCode?d.quoteCode:'')+(d.quoteTotal?' · '+d.quoteTotal:'');
        document.getElementById('pvmQuoteMeta').textContent=meta?'· '+meta:'';
    } else {
        wrapQ.style.display='none';
    }

    // Updates + current project id (for posting updates / phase changes)
    currentUpdatesKey=d.updatesKey||'';
    currentProjectId=parseInt(d.updatesKey||0)||0;
    const phaseSel=document.getElementById('pvmPhaseSelect');
    if(phaseSel){ const k=statusKey(d.status); if([...phaseSel.options].some(o=>o.value===k)) phaseSel.value=k; }
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

    // "Complete" quick action — delegated so buttons added live (when a project
    // reaches Final Approval in the modal) work without a page refresh.
    document.addEventListener('click',function(e){
        const link = e.target.closest ? e.target.closest('.monitor-action.complete') : null;
        if(!link) return;
        e.preventDefault();
        const row=link.closest('tr');
        if(!row) return;
        const pid=parseInt(row.querySelector('.view-project-btn')?.dataset.updatesKey||0)||0;
        if(!pid) return;
        vsConfirm('Mark this project as Completed? The client will be notified to confirm they received it.',
            {title:'Complete project', okText:'Mark completed'}).then(function(ok){
            if(!ok) return;
            fetch('update_project_status.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
                body:new URLSearchParams({project_id:pid,status:'completed'})})
                .then(async r=>{const d=await r.json().catch(()=>({ok:false})); if(!r.ok||!d.ok) throw new Error(d.error||'Failed'); return d;})
                .then(d=>{
                    // Reload so the table + any modal reflect the new status consistently.
                    vsToastFlash('Project marked completed. The client has been notified to confirm.');
                    location.reload();
                })
                .catch(e=>alert(e.message));
        });
    });

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

// ════════════════════════════════════════════════════
//  COST TRACKING EDITOR
// ════════════════════════════════════════════════════
(function(){
  const pesoT = n => '₱'+Number(n||0).toLocaleString('en-PH',{minimumFractionDigits:2,maximumFractionDigits:2});
  const $ = id => document.getElementById(id);
  let trkProjectId = 0;
  const esc = s => String(s==null?'':s).replace(/"/g,'&quot;');

  function expenseRow(e){ e=e||{};
    const tr=document.createElement('tr'); tr.className='trk-exp-row';
    tr.innerHTML =
      '<td><input type="text" class="trk-label" placeholder="e.g. World Class" value="'+esc(e.label)+'"></td>'+
      '<td><input type="number" step="0.01" class="trk-num trk-amt" placeholder="0.00" value="'+(e.amount!=null?e.amount:'')+'"></td>'+
      '<td class="text-center"><button type="button" class="trk-del" title="Remove">&times;</button></td>';
    return tr;
  }
  function laborRow(l){ l=l||{};
    const tr=document.createElement('tr'); tr.className='trk-lab-row';
    tr.innerHTML=
      '<td><input type="date" class="trk-date" value="'+esc(l.work_date)+'"></td>'+
      '<td><input type="text" class="trk-man" placeholder="Name" value="'+esc(l.manpower)+'"></td>'+
      '<td><input type="number" step="0.01" class="trk-num trk-rate" placeholder="0.00" value="'+(l.rate!=null?l.rate:'')+'"></td>'+
      '<td><input type="number" step="0.01" class="trk-num trk-gas" placeholder="0.00" value="'+(l.gas_toll!=null?l.gas_toll:'')+'"></td>'+
      '<td class="trk-line-total">₱0.00</td>'+
      '<td class="text-center"><button type="button" class="trk-del" title="Remove">&times;</button></td>';
    return tr;
  }

  function recompute(){
    let exp=0;
    $('trkExpenseBody').querySelectorAll('.trk-exp-row').forEach(r=>{ exp+=parseFloat(r.querySelector('.trk-amt').value)||0; });
    $('trkExpenseTotal').textContent=pesoT(exp);
    const sumBody=id=>{ let t=0; $(id).querySelectorAll('.trk-lab-row').forEach(r=>{
        const rate=parseFloat(r.querySelector('.trk-rate').value)||0, gas=parseFloat(r.querySelector('.trk-gas').value)||0;
        r.querySelector('.trk-line-total').textContent=pesoT(rate+gas); t+=rate+gas; }); return t; };
    const asm=sumBody('trkAssemblyBody'), ins=sumBody('trkInstallBody');
    $('trkAssemblyTotal').textContent=pesoT(asm);
    $('trkInstallTotal').textContent=pesoT(ins);
    const cost=exp+asm+ins, amount=parseFloat($('trkAmount').value)||0, profit=amount-cost;
    $('trkCost').textContent=pesoT(cost);
    $('trkProfit').textContent=pesoT(profit);
    const card=$('trkProfitCard'); card.classList.toggle('profit',profit>=0); card.classList.toggle('loss',profit<0);
  }

  const modal=$('trackingModal');
  modal.addEventListener('input',recompute);
  modal.addEventListener('click',function(e){
    const del=e.target.closest('.trk-del'); if(del){ del.closest('tr').remove(); recompute(); return; }
    const add=e.target.closest('.trk-add-btn');
    if(add){ const w=add.dataset.add;
      if(w==='expense') $('trkExpenseBody').appendChild(expenseRow());
      else if(w==='assembly') $('trkAssemblyBody').appendChild(laborRow());
      else if(w==='installation') $('trkInstallBody').appendChild(laborRow());
      recompute();
    }
  });

  function collect(){
    const expenses=[]; $('trkExpenseBody').querySelectorAll('.trk-exp-row').forEach(r=>{
      const label=r.querySelector('.trk-label').value.trim(), amount=parseFloat(r.querySelector('.trk-amt').value)||0;
      if(label||amount) expenses.push({label,amount});
    });
    const labor=[]; const grab=(id,phase)=>$(id).querySelectorAll('.trk-lab-row').forEach(r=>{
      const man=r.querySelector('.trk-man').value.trim(), rate=parseFloat(r.querySelector('.trk-rate').value)||0,
            gas=parseFloat(r.querySelector('.trk-gas').value)||0, date=r.querySelector('.trk-date').value;
      if(man||rate||gas) labor.push({phase,work_date:date,manpower:man,rate,gas_toll:gas});
    });
    grab('trkAssemblyBody','assembly'); grab('trkInstallBody','installation');
    return {expenses,labor};
  }

  window.openTracking=function(projectId,name,code){
    trkProjectId=projectId;
    $('trkProjectName').textContent=name||'—';
    $('trkProjectCode').textContent=code||'';
    $('trkExpenseBody').innerHTML=''; $('trkAssemblyBody').innerHTML=''; $('trkInstallBody').innerHTML='';
    $('trkAmount').value=''; $('trkNotes').value='';
    $('trkDownloadXlsx').href='export_tracking_xlsx.php?project_id='+projectId;
    $('trkDownloadPdf').href='tracking_print.php?project_id='+projectId;
    fetch('tracking_data.php?project_id='+projectId).then(r=>r.json()).then(d=>{
      if(!d.ok) throw new Error(d.error||'Failed to load');
      $('trkAmount').value=d.amount||''; $('trkNotes').value=d.notes||'';
      (d.expenses||[]).forEach(e=>$('trkExpenseBody').appendChild(expenseRow(e)));
      (d.labor||[]).forEach(l=>{ (l.phase==='installation'?$('trkInstallBody'):$('trkAssemblyBody')).appendChild(laborRow(l)); });
      if(!$('trkExpenseBody').children.length) $('trkExpenseBody').appendChild(expenseRow());
      if(!$('trkAssemblyBody').children.length) $('trkAssemblyBody').appendChild(laborRow());
      if(!$('trkInstallBody').children.length) $('trkInstallBody').appendChild(laborRow());
      recompute();
    }).catch(e=>alert(e.message));
    new bootstrap.Modal(modal).show();
  };

  $('trkSaveBtn').addEventListener('click',function(){
    const {expenses,labor}=collect(); const btn=this; btn.disabled=true;
    fetch('save_tracking.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({project_id:trkProjectId, project_amount:parseFloat($('trkAmount').value)||0,
        notes:$('trkNotes').value, expenses:JSON.stringify(expenses), labor:JSON.stringify(labor)})})
      .then(async r=>{const d=await r.json().catch(()=>({ok:false})); if(!r.ok||!d.ok) throw new Error(d.error||'Failed'); return d;})
      .then(()=>vsToast('Cost tracking saved.'))
      .catch(e=>alert(e.message)).finally(()=>btn.disabled=false);
  });

  $('btnOpenTracking').addEventListener('click',function(){
    if(!currentProjectId){ vsAlert('Open a project first.'); return; }
    openTracking(currentProjectId, currentProjectName || $('viewProjectModalLabel').textContent, $('viewProjectCode').textContent);
  });
})();
</script>
</body>
</html>