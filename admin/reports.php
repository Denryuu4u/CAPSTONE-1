<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'reports';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reports – Vast Solutions</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin.css">

    <style>
        /* ── print ── */
        @media print {
            body * { visibility: hidden !important; }
            #reportPreviewModal, #reportPreviewModal * { visibility: visible !important; }
            #reportPreviewModal { position: absolute; left:0; top:0; width:100%; }
            .modal-dialog { max-width:100% !important; margin:0 !important; }
            .modal-content { border:none !important; box-shadow:none !important; border-radius:0 !important; }
            .rp-modal-actions, .modal-header { display:none !important; }
        }

        /* ── preview modal ── */
        #reportPreviewModal .modal-dialog { max-width:860px; }
        #reportPreviewModal .modal-content { border-radius:12px; border:none; }
        #reportPreviewModal .modal-header  { background:var(--navy); color:#fff; border-radius:12px 12px 0 0; padding:.9rem 1.25rem; }
        #reportPreviewModal .modal-header .btn-close { filter:invert(1) grayscale(1); }

        /* ── report document ── */
        .rp-doc { font-family:'Inter',sans-serif; padding:32px 36px; background:#fff; }
        .rp-header { display:flex; align-items:center; gap:18px; padding-bottom:18px; border-bottom:3px solid #0D9676; margin-bottom:22px; }
        .rp-logo-box { width:52px; height:52px; background:#0d1b2a; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
        .rp-logo-box i { color:#0D9676; font-size:24px; }
        .rp-company-name { font-family:'Syne',sans-serif; font-size:1.25rem; font-weight:700; color:#0d1b2a; line-height:1.1; }
        .rp-company-sub  { font-size:0.68rem; color:#6b7280; margin-top:2px; }
        .rp-title-block  { margin-left:auto; text-align:right; }
        .rp-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:#0D9676; text-transform:uppercase; letter-spacing:.04em; }
        .rp-meta  { font-size:0.7rem; color:#6b7280; margin-top:4px; line-height:1.6; }
        .rp-meta strong { color:#374151; }
        .rp-table { width:100%; border-collapse:collapse; margin-top:14px; font-size:0.72rem; }
        .rp-table thead th { background:#0d1b2a; color:#fff; padding:9px 12px; font-weight:600; font-size:0.65rem; letter-spacing:.03em; text-transform:uppercase; }
        .rp-table tbody td { padding:9px 12px; border-bottom:1px solid #e5e7eb; color:#374151; vertical-align:middle; }
        .rp-table tbody tr:last-child td { border-bottom:none; }
        .rp-table tbody tr:nth-child(even) td { background:#f9fafb; }
        .rp-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.6rem; font-weight:700; }
        /* Quotation statuses only — project statuses use .status-* from admin.css. */
        .rp-badge-pending    { background:rgba(245,158,11,.12); color:#d97706; }
        .rp-badge-approved   { background:rgba(59,130,246,.12);  color:#2563eb; }
        .rp-badge-rejected   { background:rgba(239,68,68,.12);   color:#dc2626; }
        .rp-summary       { margin-top:22px; background:#f0fdf9; border:1px solid #6ee7d0; border-radius:8px; padding:14px 18px; }
        .rp-summary-title { font-family:'Syne',sans-serif; font-size:0.78rem; font-weight:700; color:#0a7a60; margin-bottom:10px; text-transform:uppercase; letter-spacing:.04em; }
        .rp-summary-grid  { display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:10px; }
        .rp-sum-item  { background:#fff; border-radius:6px; padding:10px 12px; border:1px solid #d1fae5; }
        .rp-sum-num   { font-family:'Syne',sans-serif; font-size:1.2rem; font-weight:700; color:#0D9676; }
        .rp-sum-label { font-size:0.62rem; color:#6b7280; margin-top:2px; }
        .rp-modal-actions { padding:.9rem 1.25rem; background:#f8fafc; border-top:1px solid #e5e7eb; display:flex; justify-content:flex-end; gap:10px; border-radius:0 0 12px 12px; }

        /* ── tab / period active ── */
        .report-tab.active    { background:var(--teal); color:#fff; border-color:var(--teal); }
        .report-period.active { background:var(--teal); color:#fff !important; border-color:var(--teal); }

        /* ── summary strip ── */
        .summary-strip { display:none; gap:12px; flex-wrap:wrap; margin-top:16px; }
        .summary-strip.active { display:flex; }
        .sum-card { background:#fff; border:1px solid #e5e7eb; border-radius:10px; padding:12px 18px; flex:1 1 120px; box-shadow:0 1px 3px rgba(15,23,42,.04); }
        .sum-card-num   { font-family:'Syne',sans-serif; font-size:1.4rem; font-weight:700; color:#0D9676; }
        .sum-card-label { font-size:0.65rem; color:#6b7280; margin-top:2px; }

        /* ── chart panels ── */
        .chart-panel { display:none; gap:16px; flex-wrap:wrap; margin-top:16px; }
        .chart-panel.active { display:flex; }
        .chart-card { background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:18px 20px; flex:1 1 300px; min-width:0; box-shadow:0 1px 3px rgba(15,23,42,.05); }
        .chart-card-title { font-family:'Syne',sans-serif; font-size:0.82rem; font-weight:700; color:#111827; margin-bottom:14px; }
        .chart-canvas-wrap { position:relative; height:220px; }

        /* ── inline data table ── */
        .inline-table-wrap { background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; margin-top:16px; box-shadow:0 1px 3px rgba(15,23,42,.04); display:none; }
        .inline-table-wrap.active { display:block; }
        .inline-table-title { font-family:'Syne',sans-serif; font-size:0.82rem; font-weight:700; color:#111827; padding:14px 18px 10px; border-bottom:1px solid #f0f0f0; }
        .inline-tbl { width:100%; border-collapse:collapse; font-size:0.72rem; }
        .inline-tbl thead th { background:#fafafa; color:#9ca3af; font-size:0.6rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:9px 16px; border-bottom:1px solid #ececec; white-space:nowrap; }
        .inline-tbl tbody td { padding:9px 16px; border-bottom:1px solid #f5f5f5; color:#374151; vertical-align:middle; }
        .inline-tbl tbody tr:last-child td { border-bottom:none; }
        .it-badge { display:inline-block; padding:2px 8px; border-radius:999px; font-size:0.6rem; font-weight:700; }
        /* Quotation statuses only — project statuses use .status-* from admin.css. */
        .it-pending    { background:rgba(245,158,11,.12); color:#d97706; }
        .it-approved   { background:rgba(59,130,246,.12);  color:#2563eb; }
        .it-rejected   { background:rgba(239,68,68,.12);   color:#dc2626; }
        .it-cat        { background:#f0f9ff; color:#0369a1; }

        /* ── responsive: report preview document ── */
        #reportPreviewBody { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        @media (max-width: 768px) {
            .rp-doc { padding: 18px 16px; }
            .rp-header { flex-wrap: wrap; gap: 12px; }
            .rp-title-block { margin-left: 0; text-align: left; width: 100%; }
            .rp-table { font-size: 0.66rem; min-width: 520px; }
            .rp-summary-grid { grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); }
            .chart-card { flex: 1 1 100%; }
        }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
    <div class="topbar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center gap-2">
                <a href="#">Portal</a><span class="sep">›</span><span>Reports</span>
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
            <h1 class="page-title mb-0">Reports</h1>
        </div>

        <!-- TABS -->
        <div class="report-tabs-wrap mb-3">
            <div class="report-tabs">
                <a href="#" class="report-tab active" data-tab="project">Project Reports</a>
                <a href="#" class="report-tab" data-tab="quotation">Quotation Reports</a>
                <a href="#" class="report-tab" data-tab="cutting">Cutting List Summary</a>
                <a href="#" class="report-tab" data-tab="costing">Costing Reports</a>
            </div>
        </div>

        <!-- FILTER ROW -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 report-filter-row">
            <div class="report-date-wrap">
                <div class="report-date-group">
                    <i class="bi bi-calendar3 report-date-icon"></i>
                    <input type="date" id="dateFrom" class="form-control report-date-input">
                </div>
                <span class="report-to-text">to</span>
                <div class="report-date-group">
                    <i class="bi bi-calendar3 report-date-icon"></i>
                    <input type="date" id="dateTo" class="form-control report-date-input">
                </div>
            </div>
            <button class="report-generate-btn" id="openGenerateModal">
                <i class="bi bi-file-earmark-text"></i> Generate Report
            </button>
        </div>

        <!-- PERIOD PILLS -->
        <div class="report-periods">
            <a href="#" class="report-period" data-period="weekly">Weekly</a>
            <a href="#" class="report-period" data-period="monthly">Monthly</a>
            <a href="#" class="report-period" data-period="yearly">Yearly</a>
        </div>

        <!-- ════ SUMMARY STRIPS ════ -->
        <!-- Per-phase cards are appended by renderProject() — one per status actually present. -->
        <div class="summary-strip active" id="strip-project">
            <div class="sum-card"><div class="sum-card-num" id="sc-prj-total">0</div><div class="sum-card-label">Total Projects</div></div>
        </div>

        <div class="summary-strip" id="strip-quotation">
            <div class="sum-card"><div class="sum-card-num" id="sc-qt-total">0</div><div class="sum-card-label">Total Quotations</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-qt-approved">0</div><div class="sum-card-label">Approved</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-qt-pending">0</div><div class="sum-card-label">Pending</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-qt-rejected">0</div><div class="sum-card-label">Rejected</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-qt-revenue" style="font-size:1rem;">₱0</div><div class="sum-card-label">Est. Revenue (Approved)</div></div>
        </div>

        <div class="summary-strip" id="strip-cutting">
            <div class="sum-card"><div class="sum-card-num" id="sc-ct-types">0</div><div class="sum-card-label">Material Types</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-ct-aluminum">0</div><div class="sum-card-label">Aluminum (qty)</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-ct-glass">0</div><div class="sum-card-label">Glass (qty)</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-ct-steel">0</div><div class="sum-card-label">Steel (qty)</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-ct-accessories">0</div><div class="sum-card-label">Accessories (qty)</div></div>
        </div>

        <div class="summary-strip" id="strip-costing">
            <div class="sum-card"><div class="sum-card-num" id="sc-co-projects">0</div><div class="sum-card-label">Projects Costed</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-co-materials" style="font-size:1rem;">₱0</div><div class="sum-card-label">Total Materials</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-co-labor" style="font-size:1rem;">₱0</div><div class="sum-card-label">Total Labor</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-co-grand" style="font-size:1rem;">₱0</div><div class="sum-card-label">Grand Total</div></div>
            <div class="sum-card"><div class="sum-card-num" id="sc-co-avg" style="font-size:1rem;">₱0</div><div class="sum-card-label">Avg Cost / Project</div></div>
        </div>

        <!-- ════ CHART PANELS ════ -->
        <div class="chart-panel active" id="charts-project">
            <div class="chart-card">
                <div class="chart-card-title">Projects by Status</div>
                <div class="chart-canvas-wrap"><canvas id="chart-prj-status"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Projects Submitted per Month</div>
                <div class="chart-canvas-wrap"><canvas id="chart-prj-month"></canvas></div>
            </div>
        </div>

        <div class="chart-panel" id="charts-quotation">
            <div class="chart-card">
                <div class="chart-card-title">Quotation Status Breakdown</div>
                <div class="chart-canvas-wrap"><canvas id="chart-qt-status"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Estimated Revenue (Approved Quotations)</div>
                <div class="chart-canvas-wrap"><canvas id="chart-qt-revenue"></canvas></div>
            </div>
        </div>

        <div class="chart-panel" id="charts-cutting">
            <div class="chart-card">
                <div class="chart-card-title">Quantity by Category</div>
                <div class="chart-canvas-wrap"><canvas id="chart-ct-cat"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Quantity per Material</div>
                <div class="chart-canvas-wrap"><canvas id="chart-ct-mat"></canvas></div>
            </div>
        </div>

        <div class="chart-panel" id="charts-costing">
            <div class="chart-card">
                <div class="chart-card-title">Cost Breakdown per Project</div>
                <div class="chart-canvas-wrap"><canvas id="chart-co-breakdown"></canvas></div>
            </div>
            <div class="chart-card">
                <div class="chart-card-title">Total Cost per Project</div>
                <div class="chart-canvas-wrap"><canvas id="chart-co-total"></canvas></div>
            </div>
        </div>

        <!-- ════ INLINE TABLES ════ -->
        <div class="inline-table-wrap active" id="tbl-wrap-project">
            <div class="inline-table-title">Project List</div>
            <div class="table-responsive">
                <table class="inline-tbl">
                    <thead><tr><th>Code</th><th>Customer</th><th>Project Name</th><th>Date Submitted</th><th>Request Date</th><th>Status</th></tr></thead>
                    <tbody id="tbl-project"></tbody>
                </table>
            </div>
        </div>

        <div class="inline-table-wrap" id="tbl-wrap-quotation">
            <div class="inline-table-title">Quotation List</div>
            <div class="table-responsive">
                <table class="inline-tbl">
                    <thead><tr><th>Code</th><th>Customer</th><th>Project Name</th><th>Date Created</th><th>Status</th><th>Est. Amount</th></tr></thead>
                    <tbody id="tbl-quotation"></tbody>
                </table>
            </div>
        </div>

        <div class="inline-table-wrap" id="tbl-wrap-cutting">
            <div class="inline-table-title">Cutting List</div>
            <div class="table-responsive">
                <table class="inline-tbl">
                    <thead><tr><th>Material</th><th>Length (mm)</th><th>Width (mm)</th><th>Qty</th><th>Grouped</th><th>Category</th></tr></thead>
                    <tbody id="tbl-cutting"></tbody>
                </table>
            </div>
        </div>

        <div class="inline-table-wrap" id="tbl-wrap-costing">
            <div class="inline-table-title">Costing List</div>
            <div class="table-responsive">
                <table class="inline-tbl">
                    <thead><tr><th>Project Name</th><th>Materials Cost</th><th>Labor Cost</th><th>Other Cost</th><th>Total Cost</th></tr></thead>
                    <tbody id="tbl-costing"></tbody>
                </table>
            </div>
        </div>

    </div><!-- /page-content -->
</div><!-- /main -->

<!-- ════ GENERATE MODAL ════ -->
<div class="modal fade" id="generateReportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content report-modal">
            <div class="modal-header report-modal-header">
                <h5 class="modal-title">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body report-modal-body">
                <div class="mb-3">
                    <div class="report-modal-label">Date Range</div>
                    <div class="report-modal-subtext" id="modalDateDisplay">No date range selected — showing all data.</div>
                </div>
                <div class="mb-3">
                    <div class="report-modal-label">Report Type</div>
                    <div class="report-checkbox-group">
                        <label class="report-check"><input type="checkbox" id="chkAll"><span>All Reports</span></label>
                        <label class="report-check"><input type="checkbox" class="report-type-chk" value="project"><span>Project Reports</span></label>
                        <label class="report-check"><input type="checkbox" class="report-type-chk" value="quotation"><span>Quotation Reports</span></label>
                        <label class="report-check"><input type="checkbox" class="report-type-chk" value="cutting"><span>Cutting List Summary</span></label>
                        <label class="report-check"><input type="checkbox" class="report-type-chk" value="costing"><span>Costing Reports</span></label>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="report-modal-label">Format</div>
                    <div class="report-radio-group">
                        <label class="report-radio"><input type="radio" name="format" value="pdf" checked><span>PDF (Print)</span></label>
                        <label class="report-radio"><input type="radio" name="format" value="excel"><span>Excel (CSV Download)</span></label>
                    </div>
                </div>
            </div>
            <div class="modal-footer report-modal-footer">
                <button class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-success" id="btnGenerate">
                    <i class="bi bi-file-earmark-arrow-down me-1"></i>Generate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ════ PREVIEW MODAL ════ -->
<div class="modal fade" id="reportPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title text-white fw-semibold" id="previewModalTitle">
                    <i class="bi bi-file-earmark-text me-2"></i>Report Preview
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0" id="reportPreviewBody"></div>
            <div class="rp-modal-actions">
                <button class="btn btn-light border btn-sm" data-bs-dismiss="modal"><i class="bi bi-x me-1"></i>Close</button>
                <button class="btn btn-sm" id="btnDownloadCSV" style="background:#16a34a;color:#fff;display:none;"><i class="bi bi-download me-1"></i>Download CSV</button>
                <button class="btn btn-sm" id="btnPrint" style="background:#0D9676;color:#fff;"><i class="bi bi-printer me-1"></i>Print / Save as PDF</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<?= project_status_js() ?>
<script>
// ════════════════════════════════════════════════════
//  DATA  — swap with PHP-injected JSON / fetch later
// ════════════════════════════════════════════════════
const SAMPLE = {
    project: [
        { code:'PRJ-2026-041', customer:'Juan dela Cruz',  project:'Aluminum Window Frame', submitted:'2026-01-10', request:'2026-01-15', status:'completed'       },
        { code:'PRJ-2026-042', customer:'Maria Santos',    project:'Glass Curtain Wall',    submitted:'2026-02-03', request:'2026-02-10', status:'production'      },
        { code:'PRJ-2026-043', customer:'Carlos Reyes',    project:'Steel Door Set',        submitted:'2026-02-18', request:'2026-02-25', status:'approved'        },
        { code:'PRJ-2026-044', customer:'Ana Lim',         project:'Sliding Door System',   submitted:'2026-03-05', request:'2026-03-12', status:'quote_submitted' },
        { code:'PRJ-2026-045', customer:'Pedro Bautista',  project:'Window Grille Install', submitted:'2026-03-20', request:'2026-03-28', status:'installation'    },
        { code:'PRJ-2026-046', customer:'Rosa Villanueva', project:'Shopfront Facade',      submitted:'2026-04-02', request:'2026-04-10', status:'quality_check'   },
    ],
    quotation: [
        { code:'QT-2026-041', customer:'Juan dela Cruz',  project:'Aluminum Window Frame', created:'2026-01-08', status:'approved', amount:85000  },
        { code:'QT-2026-042', customer:'Maria Santos',    project:'Glass Curtain Wall',    created:'2026-02-01', status:'approved', amount:320000 },
        { code:'QT-2026-043', customer:'Carlos Reyes',    project:'Steel Door Set',        created:'2026-02-15', status:'rejected', amount:45000  },
        { code:'QT-2026-044', customer:'Ana Lim',         project:'Sliding Door System',   created:'2026-03-03', status:'pending',  amount:62000  },
        { code:'QT-2026-045', customer:'Pedro Bautista',  project:'Window Grille Install', created:'2026-03-18', status:'approved', amount:27500  },
        { code:'QT-2026-046', customer:'Rosa Villanueva', project:'Shopfront Facade',      created:'2026-04-01', status:'pending',  amount:150000 },
    ],
    cutting: [
        { material:'Aluminum Section', length:6000, width:45,  qty:24, grouped:8,  category:'Aluminum'    },
        { material:'Float Glass 6mm',  length:1200, width:900, qty:12, grouped:4,  category:'Glass'       },
        { material:'Aluminum Sill',    length:3000, width:60,  qty:16, grouped:6,  category:'Aluminum'    },
        { material:'Tinted Glass 8mm', length:800,  width:600, qty:8,  grouped:2,  category:'Glass'       },
        { material:'Steel Bar 20mm',   length:4500, width:20,  qty:30, grouped:10, category:'Steel'       },
        { material:'EPDM Gasket',      length:5000, width:12,  qty:50, grouped:15, category:'Accessories' },
    ],
    costing: [
        { project:'Aluminum Window Frame', materials:42000,  labor:18000, other:5000  },
        { project:'Glass Curtain Wall',    materials:185000, labor:72000, other:15000 },
        { project:'Steel Door Set',        materials:22000,  labor:9500,  other:3500  },
        { project:'Sliding Door System',   materials:31000,  labor:14000, other:4500  },
        { project:'Window Grille Install', materials:12000,  labor:7500,  other:2000  },
    ],
};

// ════════════════════════════════════════════════════
//  HELPERS
// ════════════════════════════════════════════════════
const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const C = {
    pending:'rgba(245,158,11,.8)', approved:'rgba(59,130,246,.8)',
    fabrication:'rgba(249,115,22,.8)', completed:'rgba(34,197,94,.8)', rejected:'rgba(239,68,68,.8)',
    teal:'#0D9676', navy:'#0d1b2a',
    aluminum:'rgba(99,102,241,.8)', glass:'rgba(14,165,233,.8)',
    steel:'rgba(156,163,175,.8)', accessories:'rgba(251,146,60,.8)',
    materials:'rgba(13,150,118,.8)', labor:'rgba(99,102,241,.8)', other:'rgba(251,146,60,.8)',
};

const peso = n => '₱'+Number(n).toLocaleString('en-PH',{minimumFractionDigits:2});
const fmtDate = d => d ? new Date(d+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}) : '—';
const fmtDT   = () => new Date().toLocaleString('en-PH',{year:'numeric',month:'long',day:'numeric',hour:'2-digit',minute:'2-digit'});
const fmtMM   = n => n.toLocaleString()+' mm';

function getDateRange(){
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    if(from && to) return fmtDate(from)+' – '+fmtDate(to);
    if(from) return 'From '+fmtDate(from);
    if(to)   return 'Up to '+fmtDate(to);
    return 'All Dates';
}

function filterDate(rows, field){
    const from = document.getElementById('dateFrom').value;
    const to   = document.getElementById('dateTo').value;
    return rows.filter(r=>{
        const d = r[field]; if(!d) return true;
        if(from && d < from) return false;
        if(to   && d > to)   return false;
        return true;
    });
}

// Project badges use the canonical phase vocabulary (statusLabel/statusClass).
// Quotation badges are a separate vocabulary — a quote is Pending/Approved/Rejected,
// which is NOT a project phase, so it must not be routed through statusKey().
function itProjBadge(s){ return `<span class="it-badge ${statusClass(s)}">${statusLabel(s)}</span>`; }
function rpProjBadge(s){ return `<span class="rp-badge ${statusClass(s)}">${statusLabel(s)}</span>`; }

const QUOTE_LABELS = {pending:'Pending', approved:'Approved', rejected:'Rejected'};
function itQuoteBadge(s){
    const m={pending:'it-pending',approved:'it-approved',rejected:'it-rejected'};
    return `<span class="it-badge ${m[s]||''}">${QUOTE_LABELS[s]||s}</span>`;
}
function rpQuoteBadge(s){
    const m={pending:'rp-badge-pending',approved:'rp-badge-approved',rejected:'rp-badge-rejected'};
    return `<span class="rp-badge ${m[s]||''}">${QUOTE_LABELS[s]||s}</span>`;
}

/** Chart colours, keyed by canonical status. Mirrors the .status-* CSS. */
const STATUS_COLORS = {
    quote_submitted:'rgba(245,158,11,.8)', approved:'rgba(59,130,246,.8)',
    production:'rgba(249,115,22,.8)',      mockup:'rgba(139,92,246,.8)',
    delivery:'rgba(14,165,233,.8)',        installation:'rgba(99,102,241,.8)',
    quality_check:'rgba(13,150,118,.8)',   punchlist:'rgba(236,72,153,.8)',
    final_approval:'rgba(132,204,22,.8)',  completed:'rgba(34,197,94,.8)',
    on_hold:'rgba(100,116,139,.8)',        rejected:'rgba(239,68,68,.8)',
};

const _charts = {};
function makeChart(id, cfg){
    if(_charts[id]) _charts[id].destroy();
    const el = document.getElementById(id);
    if(el) _charts[id] = new Chart(el, cfg);
}

// ════════════════════════════════════════════════════
//  RENDER — PROJECT
// ════════════════════════════════════════════════════
function renderProject(){
    const rows = filterDate(SAMPLE.project, 'submitted');
    const cnt  = {};
    const byM  = {};
    rows.forEach(r=>{
        cnt[statusKey(r.status)] = (cnt[statusKey(r.status)]||0)+1;
        const m = MONTHS[new Date(r.submitted+'T00:00:00').getMonth()];
        byM[m] = (byM[m]||0)+1;
    });

    // summary strip — total, then one card per status actually present
    document.getElementById('sc-prj-total').textContent = rows.length;
    const strip = document.getElementById('strip-project');
    strip.querySelectorAll('.sum-card[data-status]').forEach(el=>el.remove());
    const present = Object.keys(PROJECT_ALL).filter(k=>cnt[k]);
    present.forEach(k=>{
        const card=document.createElement('div');
        card.className='sum-card';
        card.dataset.status=k;
        card.innerHTML=`<div class="sum-card-num">${cnt[k]}</div><div class="sum-card-label">${PROJECT_ALL[k]}</div>`;
        strip.appendChild(card);
    });

    // donut chart — distribution across the phases in play
    makeChart('chart-prj-status',{
        type:'doughnut',
        data:{
            labels: present.map(k=>PROJECT_ALL[k]),
            datasets:[{data:present.map(k=>cnt[k]),
                backgroundColor:present.map(k=>STATUS_COLORS[k]),
                borderWidth:2, borderColor:'#fff'}]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:14}}}}
    });

    // bar chart — submitted per month
    const mLabels = Object.keys(byM);
    makeChart('chart-prj-month',{
        type:'bar',
        data:{
            labels: mLabels,
            datasets:[{label:'Projects Submitted',data:mLabels.map(m=>byM[m]),
                backgroundColor:C.teal, borderRadius:5, borderSkipped:false}]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{y:{beginAtZero:true,ticks:{stepSize:1},grid:{color:'rgba(0,0,0,.05)'}},
                    x:{grid:{display:false}}}}
    });

    // inline table
    document.getElementById('tbl-project').innerHTML = rows.length
        ? rows.map(r=>`<tr>
            <td><strong>${r.code}</strong></td><td>${r.customer}</td><td>${r.project}</td>
            <td>${fmtDate(r.submitted)}</td><td>${fmtDate(r.request)}</td>
            <td>${itProjBadge(r.status)}</td></tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-3">No data for selected range.</td></tr>';
}

// ════════════════════════════════════════════════════
//  RENDER — QUOTATION
// ════════════════════════════════════════════════════
function renderQuotation(){
    const rows     = filterDate(SAMPLE.quotation,'created');
    const approved = rows.filter(r=>r.status==='approved');
    const rejected = rows.filter(r=>r.status==='rejected');
    const pending  = rows.filter(r=>r.status==='pending');
    const revenue  = approved.reduce((s,r)=>s+r.amount,0);

    document.getElementById('sc-qt-total').textContent    = rows.length;
    document.getElementById('sc-qt-approved').textContent = approved.length;
    document.getElementById('sc-qt-pending').textContent  = pending.length;
    document.getElementById('sc-qt-rejected').textContent = rejected.length;
    document.getElementById('sc-qt-revenue').textContent  = peso(revenue);

    // donut — status
    makeChart('chart-qt-status',{
        type:'doughnut',
        data:{
            labels:['Approved','Pending','Rejected'],
            datasets:[{data:[approved.length,pending.length,rejected.length],
                backgroundColor:[C.approved,C.pending,C.rejected],
                borderWidth:2,borderColor:'#fff'}]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:14}}}}
    });

    // bar — revenue per approved quotation
    makeChart('chart-qt-revenue',{
        type:'bar',
        data:{
            labels:approved.map(r=>r.code),
            datasets:[{label:'Est. Amount',data:approved.map(r=>r.amount),
                backgroundColor:C.teal,borderRadius:5,borderSkipped:false}]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},
                tooltip:{callbacks:{label:ctx=>' '+peso(ctx.parsed.y)}}},
            scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.05)'},
                ticks:{callback:v=>'₱'+Number(v/1000).toFixed(0)+'k'}},
                x:{grid:{display:false}}}}
    });

    document.getElementById('tbl-quotation').innerHTML = rows.length
        ? rows.map(r=>`<tr>
            <td><strong>${r.code}</strong></td><td>${r.customer}</td><td>${r.project}</td>
            <td>${fmtDate(r.created)}</td><td>${itQuoteBadge(r.status)}</td>
            <td>${peso(r.amount)}</td></tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-3">No data for selected range.</td></tr>';
}

// ════════════════════════════════════════════════════
//  RENDER — CUTTING
// ════════════════════════════════════════════════════
function renderCutting(){
    const rows = SAMPLE.cutting;  // no date field
    const cats = {};
    rows.forEach(r=> cats[r.category]=(cats[r.category]||0)+r.qty);

    document.getElementById('sc-ct-types').textContent       = rows.length;
    document.getElementById('sc-ct-aluminum').textContent    = cats['Aluminum']    || 0;
    document.getElementById('sc-ct-glass').textContent       = cats['Glass']       || 0;
    document.getElementById('sc-ct-steel').textContent       = cats['Steel']       || 0;
    document.getElementById('sc-ct-accessories').textContent = cats['Accessories'] || 0;

    const catLabels = Object.keys(cats);
    const catColors = {Aluminum:C.aluminum,Glass:C.glass,Steel:C.steel,Accessories:C.accessories};
    const colArr    = catLabels.map(c=>catColors[c]||C.teal);

    // pie — qty by category
    makeChart('chart-ct-cat',{
        type:'pie',
        data:{labels:catLabels,
            datasets:[{data:catLabels.map(c=>cats[c]),backgroundColor:colArr,borderWidth:2,borderColor:'#fff'}]},
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:14}}}}
    });

    // horizontal bar — qty per material
    makeChart('chart-ct-mat',{
        type:'bar',
        data:{
            labels:rows.map(r=>r.material),
            datasets:[{label:'Quantity',data:rows.map(r=>r.qty),
                backgroundColor:rows.map(r=>catColors[r.category]||C.teal),
                borderRadius:4,borderSkipped:false}]
        },
        options:{indexAxis:'y',responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false}},
            scales:{x:{beginAtZero:true,grid:{color:'rgba(0,0,0,.05)'}},
                    y:{grid:{display:false},ticks:{font:{size:10}}}}}
    });

    document.getElementById('tbl-cutting').innerHTML = rows.length
        ? rows.map(r=>`<tr>
            <td><strong>${r.material}</strong></td><td>${fmtMM(r.length)}</td>
            <td>${fmtMM(r.width)}</td><td>${r.qty}</td><td>${r.grouped}</td>
            <td><span class="it-badge it-cat">${r.category}</span></td></tr>`).join('')
        : '<tr><td colspan="6" class="text-center text-muted py-3">No data.</td></tr>';
}

// ════════════════════════════════════════════════════
//  RENDER — COSTING
// ════════════════════════════════════════════════════
function renderCosting(){
    const rows  = SAMPLE.costing.map(r=>({...r,total:r.materials+r.labor+r.other}));
    const gMat  = rows.reduce((s,r)=>s+r.materials,0);
    const gLab  = rows.reduce((s,r)=>s+r.labor,0);
    const gTot  = rows.reduce((s,r)=>s+r.total,0);
    const avg   = gTot/rows.length;

    document.getElementById('sc-co-projects').textContent  = rows.length;
    document.getElementById('sc-co-materials').textContent = peso(gMat);
    document.getElementById('sc-co-labor').textContent     = peso(gLab);
    document.getElementById('sc-co-grand').textContent     = peso(gTot);
    document.getElementById('sc-co-avg').textContent       = peso(avg);

    const shortLabel = p => p.length>16 ? p.slice(0,14)+'…' : p;

    // stacked bar — materials / labor / other per project
    makeChart('chart-co-breakdown',{
        type:'bar',
        data:{
            labels:rows.map(r=>shortLabel(r.project)),
            datasets:[
                {label:'Materials',data:rows.map(r=>r.materials),backgroundColor:C.materials,borderRadius:4,borderSkipped:false,stack:'s'},
                {label:'Labor',    data:rows.map(r=>r.labor),    backgroundColor:C.labor,    borderRadius:0, stack:'s'},
                {label:'Other',    data:rows.map(r=>r.other),    backgroundColor:C.other,    borderRadius:0, stack:'s'},
            ]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{position:'bottom',labels:{font:{size:11},padding:10}},
                tooltip:{callbacks:{label:ctx=>' '+peso(ctx.parsed.y)}}},
            scales:{x:{stacked:true,grid:{display:false},ticks:{font:{size:10}}},
                    y:{stacked:true,beginAtZero:true,grid:{color:'rgba(0,0,0,.05)'},
                        ticks:{callback:v=>'₱'+Number(v/1000).toFixed(0)+'k'}}}}
    });

    // bar — total cost per project
    makeChart('chart-co-total',{
        type:'bar',
        data:{
            labels:rows.map(r=>shortLabel(r.project)),
            datasets:[{label:'Total Cost',data:rows.map(r=>r.total),
                backgroundColor:C.navy,borderRadius:5,borderSkipped:false}]
        },
        options:{responsive:true,maintainAspectRatio:false,
            plugins:{legend:{display:false},tooltip:{callbacks:{label:ctx=>' '+peso(ctx.parsed.y)}}},
            scales:{y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.05)'},
                ticks:{callback:v=>'₱'+Number(v/1000).toFixed(0)+'k'}},
                x:{grid:{display:false},ticks:{font:{size:10}}}}}
    });

    document.getElementById('tbl-costing').innerHTML = rows.length
        ? rows.map(r=>`<tr>
            <td><strong>${r.project}</strong></td><td>${peso(r.materials)}</td>
            <td>${peso(r.labor)}</td><td>${peso(r.other)}</td>
            <td><strong>${peso(r.total)}</strong></td></tr>`).join('')
        : '<tr><td colspan="5" class="text-center text-muted py-3">No data.</td></tr>';
}

// ════════════════════════════════════════════════════
//  PRINTABLE REPORT BUILDERS  (same data, styled for print)
// ════════════════════════════════════════════════════
function rpHeader(title){
    return `<div class="rp-header">
        <div class="rp-logo-box"><img src="../style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:40px; height:40px; object-fit:contain;"></div>
        <div>
            <div class="rp-company-name">Vast Solutions</div>
            <div class="rp-company-sub">Aluminum &amp; Glass Fabrication Specialists</div>
            <div class="rp-company-sub">Majayjay, Laguna &nbsp;·&nbsp; vastsolutions@email.com</div>
        </div>
        <div class="rp-title-block">
            <div class="rp-title">${title}</div>
            <div class="rp-meta">
                <strong>Date Generated:</strong> ${fmtDT()}<br>
                <strong>Period:</strong> ${getDateRange()}<br>
                <strong>Generated by:</strong> <?= htmlspecialchars($user_name) ?>
            </div>
        </div>
    </div>`;
}

function buildProject(){
    const rows=filterDate(SAMPLE.project,'submitted');
    const cnt={};rows.forEach(r=>cnt[statusKey(r.status)]=(cnt[statusKey(r.status)]||0)+1);
    const trs=rows.map(r=>`<tr><td><strong>${r.code}</strong></td><td>${r.customer}</td><td>${r.project}</td><td>${fmtDate(r.submitted)}</td><td>${fmtDate(r.request)}</td><td>${rpProjBadge(r.status)}</td></tr>`).join('');
    let sum=`<div class="rp-sum-item"><div class="rp-sum-num">${rows.length}</div><div class="rp-sum-label">Total Projects</div></div>`;
    Object.keys(PROJECT_ALL).filter(k=>cnt[k]).forEach(k=>sum+=`<div class="rp-sum-item"><div class="rp-sum-num">${cnt[k]}</div><div class="rp-sum-label">${PROJECT_ALL[k]}</div></div>`);
    return `<div class="rp-doc">${rpHeader('Project Report')}
        <table class="rp-table"><thead><tr><th>Project Code</th><th>Customer Name</th><th>Project Name</th><th>Date Submitted</th><th>Request Date</th><th>Status</th></tr></thead><tbody>${trs||'<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:16px">No records</td></tr>'}</tbody></table>
        <div class="rp-summary"><div class="rp-summary-title">Summary</div><div class="rp-summary-grid">${sum}</div></div></div>`;
}

function buildQuotation(){
    const rows=filterDate(SAMPLE.quotation,'created');
    const app=rows.filter(r=>r.status==='approved'),rej=rows.filter(r=>r.status==='rejected');
    const rev=app.reduce((s,r)=>s+r.amount,0);
    const trs=rows.map(r=>`<tr><td><strong>${r.code}</strong></td><td>${r.customer}</td><td>${r.project}</td><td>${fmtDate(r.created)}</td><td>${rpQuoteBadge(r.status)}</td><td>${peso(r.amount)}</td></tr>`).join('');
    const sum=`<div class="rp-sum-item"><div class="rp-sum-num">${rows.length}</div><div class="rp-sum-label">Total Quotations</div></div>
        <div class="rp-sum-item"><div class="rp-sum-num">${app.length}</div><div class="rp-sum-label">Approved</div></div>
        <div class="rp-sum-item"><div class="rp-sum-num">${rej.length}</div><div class="rp-sum-label">Rejected</div></div>
        <div class="rp-sum-item"><div class="rp-sum-num" style="font-size:.9rem">${peso(rev)}</div><div class="rp-sum-label">Est. Revenue</div></div>`;
    return `<div class="rp-doc">${rpHeader('Quotation Report')}
        <table class="rp-table"><thead><tr><th>Quotation Code</th><th>Customer Name</th><th>Project Name</th><th>Date Created</th><th>Status</th><th>Est. Amount</th></tr></thead><tbody>${trs||'<tr><td colspan="6" style="text-align:center;color:#9ca3af;padding:16px">No records</td></tr>'}</tbody></table>
        <div class="rp-summary"><div class="rp-summary-title">Summary</div><div class="rp-summary-grid">${sum}</div></div></div>`;
}

function buildCutting(){
    const rows=SAMPLE.cutting;
    const cats={};rows.forEach(r=>cats[r.category]=(cats[r.category]||0)+r.qty);
    const trs=rows.map(r=>`<tr><td><strong>${r.material}</strong></td><td>${fmtMM(r.length)}</td><td>${fmtMM(r.width)}</td><td>${r.qty}</td><td>${r.grouped}</td><td><span class="rp-badge" style="background:#f0f9ff;color:#0369a1">${r.category}</span></td></tr>`).join('');
    let sum=`<div class="rp-sum-item"><div class="rp-sum-num">${rows.length}</div><div class="rp-sum-label">Material Types</div></div>`;
    Object.entries(cats).forEach(([c,q])=>sum+=`<div class="rp-sum-item"><div class="rp-sum-num">${q}</div><div class="rp-sum-label">${c}</div></div>`);
    return `<div class="rp-doc">${rpHeader('Cutting List Summary')}
        <table class="rp-table"><thead><tr><th>Material Name</th><th>Length</th><th>Width</th><th>Quantity</th><th>Grouped</th><th>Category</th></tr></thead><tbody>${trs}</tbody></table>
        <div class="rp-summary"><div class="rp-summary-title">Summary – Total per Category</div><div class="rp-summary-grid">${sum}</div></div></div>`;
}

function buildCosting(){
    const rows=SAMPLE.costing.map(r=>({...r,total:r.materials+r.labor+r.other}));
    const grand=rows.reduce((s,r)=>s+r.total,0),avg=grand/rows.length;
    const trs=rows.map(r=>`<tr><td><strong>${r.project}</strong></td><td>${peso(r.materials)}</td><td>${peso(r.labor)}</td><td>${peso(r.other)}</td><td><strong>${peso(r.total)}</strong></td></tr>`).join('');
    const sum=`<div class="rp-sum-item"><div class="rp-sum-num" style="font-size:.9rem">${peso(grand)}</div><div class="rp-sum-label">Grand Total</div></div>
        <div class="rp-sum-item"><div class="rp-sum-num" style="font-size:.9rem">${peso(avg)}</div><div class="rp-sum-label">Avg Cost / Project</div></div>
        <div class="rp-sum-item"><div class="rp-sum-num">${rows.length}</div><div class="rp-sum-label">Projects Costed</div></div>`;
    return `<div class="rp-doc">${rpHeader('Costing Report')}
        <table class="rp-table"><thead><tr><th>Project Name</th><th>Materials Cost</th><th>Labor Cost</th><th>Other Cost</th><th>Total Cost</th></tr></thead><tbody>${trs}</tbody></table>
        <div class="rp-summary"><div class="rp-summary-title">Summary</div><div class="rp-summary-grid">${sum}</div></div></div>`;
}

const BUILDERS = {project:buildProject,quotation:buildQuotation,cutting:buildCutting,costing:buildCosting};
const TITLES   = {project:'Project Report',quotation:'Quotation Report',cutting:'Cutting List Summary',costing:'Costing Report'};

// ════════════════════════════════════════════════════
//  CSV
// ════════════════════════════════════════════════════
function toCSV(type){
    const r=SAMPLE[type];
    if(type==='project')   return ['Project Code,Customer Name,Project Name,Date Submitted,Request Date,Status',...r.map(x=>[x.code,x.customer,x.project,x.submitted,x.request,x.status].join(','))].join('\n');
    if(type==='quotation') return ['Quotation Code,Customer Name,Project Name,Date Created,Status,Est. Amount',...r.map(x=>[x.code,x.customer,x.project,x.created,x.status,x.amount].join(','))].join('\n');
    if(type==='cutting')   return ['Material Name,Length (mm),Width (mm),Quantity,Grouped,Category',...r.map(x=>[x.material,x.length,x.width,x.qty,x.grouped,x.category].join(','))].join('\n');
    if(type==='costing')   return ['Project Name,Materials Cost,Labor Cost,Other Cost,Total Cost',...r.map(x=>[x.project,x.materials,x.labor,x.other,x.materials+x.labor+x.other].join(','))].join('\n');
}
function dlCSV(type){
    const b=new Blob([toCSV(type)],{type:'text/csv'});
    const a=document.createElement('a');a.href=URL.createObjectURL(b);
    a.download=TITLES[type].replace(/ /g,'_')+'_'+new Date().toISOString().slice(0,10)+'.csv';a.click();
}

// ════════════════════════════════════════════════════
//  TAB SWITCHING
// ════════════════════════════════════════════════════
let activeTab='project';
const RENDER={project:renderProject,quotation:renderQuotation,cutting:renderCutting,costing:renderCosting};
const TABS=['project','quotation','cutting','costing'];

function switchTab(tab){
    activeTab=tab;
    TABS.forEach(t=>{
        const on=t===tab;
        document.getElementById('charts-'+t).classList.toggle('active',on);
        document.getElementById('strip-'+t).classList.toggle('active',on);
        document.getElementById('tbl-wrap-'+t).classList.toggle('active',on);
    });
    RENDER[tab]();
}

document.querySelectorAll('.report-tab').forEach(el=>el.addEventListener('click',e=>{
    e.preventDefault();
    document.querySelectorAll('.report-tab').forEach(x=>x.classList.remove('active'));
    el.classList.add('active');
    switchTab(el.dataset.tab);
}));

// ════════════════════════════════════════════════════
//  PERIOD SHORTCUTS
// ════════════════════════════════════════════════════
document.querySelectorAll('.report-period').forEach(p=>p.addEventListener('click',e=>{
    e.preventDefault();
    document.querySelectorAll('.report-period').forEach(x=>x.classList.remove('active'));
    p.classList.add('active');
    const today=new Date(), from=new Date(today), to=new Date(today);
    if(p.dataset.period==='weekly')       from.setDate(today.getDate()-7);
    else if(p.dataset.period==='monthly') from.setDate(1);
    else                                   from.setMonth(0,1);
    document.getElementById('dateFrom').value=from.toISOString().slice(0,10);
    document.getElementById('dateTo').value  =to.toISOString().slice(0,10);
    RENDER[activeTab]();
}));

['dateFrom','dateTo'].forEach(id=>document.getElementById(id).addEventListener('change',()=>RENDER[activeTab]()));

// ════════════════════════════════════════════════════
//  GENERATE → PREVIEW
// ════════════════════════════════════════════════════
document.getElementById('openGenerateModal').addEventListener('click',()=>{
    const f=document.getElementById('dateFrom').value, t=document.getElementById('dateTo').value;
    document.getElementById('modalDateDisplay').textContent=(f&&t)?`${fmtDate(f)} to ${fmtDate(t)}`:'No date range selected — showing all data.';
    document.querySelectorAll('.report-type-chk').forEach(c=>c.checked=c.value===activeTab);
    document.getElementById('chkAll').checked=false;
    new bootstrap.Modal(document.getElementById('generateReportModal')).show();
});

document.getElementById('chkAll').addEventListener('change',function(){
    document.querySelectorAll('.report-type-chk').forEach(c=>c.checked=this.checked);
});

document.getElementById('btnGenerate').addEventListener('click',()=>{
    const checked=[...document.querySelectorAll('.report-type-chk:checked')].map(c=>c.value);
    const fmtSel=document.querySelector('input[name="format"]:checked').value;
    if(!checked.length){alert('Please select at least one report type.');return;}
    bootstrap.Modal.getInstance(document.getElementById('generateReportModal')).hide();

    document.getElementById('reportPreviewBody').innerHTML=
        checked.map(t=>BUILDERS[t]()).join('<hr style="border:none;border-top:2px dashed #e5e7eb;margin:0">');
    document.getElementById('previewModalTitle').innerHTML=
        `<i class="bi bi-file-earmark-text me-2"></i>${checked.length>1?'Combined Report':TITLES[checked[0]]}`;

    const csvBtn=document.getElementById('btnDownloadCSV');
    if(fmtSel==='excel'){csvBtn.style.display='inline-flex';csvBtn.onclick=()=>checked.forEach(dlCSV);}
    else csvBtn.style.display='none';

    setTimeout(()=>new bootstrap.Modal(document.getElementById('reportPreviewModal')).show(),200);
});

document.getElementById('btnPrint').addEventListener('click',()=>window.print());

// ════════════════════════════════════════════════════
//  INIT
// ════════════════════════════════════════════════════
renderProject();
</script>
</body>
</html>