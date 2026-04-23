<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'my_projects';

$projects = [
  ['id'=>1,
   'name'=>'Kitchen Cabinets - Unit 4B',  'category'=>'Kitchen Cabinets',
   'status'=>'Quote Received',             'submitted'=>'2026-03-01', 'updated'=>'2 hours ago',
   'notes'=>'Modern shaker-style, soft-close hinges, matte white finish.',
   'files'=>['kitchen-layout-v2.pdf','material-specs.pdf'],
   'activity'=>[
     ['text'=>'Quotation sent to client',    'date'=>'2026-03-11', 'by'=>'Vast Solutions', 'dot'=>'blue'],
     ['text'=>'Design review completed',     'date'=>'2026-03-05', 'by'=>'Vast Solutions', 'dot'=>'blue'],
     ['text'=>'Quote request submitted',     'date'=>'2026-03-01', 'by'=>'John Doe',       'dot'=>'blue'],
   ],
   'quote_id'=>'QT-2026-041', 'quote_status'=>'Pending',
   'quote_prepared_for'=>'John Doe', 'quote_issued'=>'2026-03-11', 'quote_valid'=>'2026-03-25',
   'quote_items'=>[
     ['desc'=>'Upper Cabinets (8 units)',  'qty'=>8, 'unit'=>450.00,  'amount'=>3600.00],
     ['desc'=>'Lower Cabinets (6 units)',  'qty'=>6, 'unit'=>620.00,  'amount'=>3720.00],
     ['desc'=>'Island Unit',               'qty'=>1, 'unit'=>2890.00, 'amount'=>2890.00],
     ['desc'=>'Hardware & Accessories',    'qty'=>1, 'unit'=>1230.00, 'amount'=>1230.00],
     ['desc'=>'Installation Labour',       'qty'=>1, 'unit'=>1100.00, 'amount'=>1100.00],
   ],
   'quote_notes'=>'Price includes delivery within 30km radius. Installation scheduled for 2 days.',
   /* monitoring fields */
   'code'=>'PRJ-2026-042', 'customer'=>'Rivera Kitchens', 'target'=>'Mar 15, 2026',
   'start'=>'2026-02-01', 'progress'=>65, 'approver'=>'Engr. Marco Reyes',
   'details'=>'Custom kitchen cabinetry with soft-close hinges, melamine panels, and PVC edge banding.',
   'materials_key'=>'042', 'updates_key'=>'042',
   'status_class'=>'badge-fabrication', 'status_label'=>'Fabrication'],

  ['id'=>2,
   'name'=>'Office Built-ins - Floor 3',  'category'=>'Office Built-ins',
   'status'=>'Pending Quote',              'submitted'=>'2026-03-10', 'updated'=>'1 day ago',
   'notes'=>'Ceiling-height, white laminate, lockable lower cabinets.',
   'files'=>['office-floor-plan.pdf'],
   'activity'=>[
     ['text'=>'Quote request submitted','date'=>'2026-03-10','by'=>'John Doe','dot'=>'blue'],
   ],
   'quote_id'=>'QT-2026-042', 'quote_status'=>'Pending',
   'quote_prepared_for'=>'John Doe', 'quote_issued'=>'', 'quote_valid'=>'',
   'quote_items'=>[], 'quote_notes'=>'',
   'code'=>'PRJ-2026-041', 'customer'=>'Mendoza Interiors', 'target'=>'Mar 12, 2026',
   'start'=>'2026-02-10', 'progress'=>20, 'approver'=>'Engr. Marco Reyes',
   'details'=>'Floor-to-ceiling office cabinetry in white laminate finish.',
   'materials_key'=>'041', 'updates_key'=>'041',
   'status_class'=>'badge-approved-soft', 'status_label'=>'Approved'],

  ['id'=>3,
   'name'=>'Bathroom Vanity - Residence', 'category'=>'Bathroom Vanity',
   'status'=>'In Progress',               'submitted'=>'2026-02-15', 'updated'=>'3 days ago',
   'notes'=>'Freestanding with integrated sink, mirror cabinet, chrome fittings.',
   'files'=>['vanity-sketch.pdf'],
   'activity'=>[
     ['text'=>'Fabrication started',    'date'=>'2026-02-20','by'=>'Vast Solutions','dot'=>'blue'],
     ['text'=>'Quote request submitted','date'=>'2026-02-15','by'=>'John Doe',      'dot'=>'blue'],
   ],
   'quote_id'=>'QT-2026-043', 'quote_status'=>'Approved',
   'quote_prepared_for'=>'John Doe', 'quote_issued'=>'2026-02-18', 'quote_valid'=>'2026-03-05',
   'quote_items'=>[
     ['desc'=>'Vanity Unit',      'qty'=>1,'unit'=>4200.00,'amount'=>4200.00],
     ['desc'=>'Mirror Cabinet',   'qty'=>1,'unit'=>1100.00,'amount'=>1100.00],
     ['desc'=>'Chrome Fittings',  'qty'=>1,'unit'=>650.00, 'amount'=>650.00],
     ['desc'=>'Installation',     'qty'=>1,'unit'=>800.00, 'amount'=>800.00],
   ],
   'quote_notes'=>'Delivery and installation included.',
   'code'=>'PRJ-2026-040', 'customer'=>'Kim Design Studio', 'target'=>'Mar 10, 2026',
   'start'=>'', 'progress'=>0, 'approver'=>'',
   'details'=>'Moisture-resistant MDF with integrated sink and mirror cabinet.',
   'materials_key'=>'', 'updates_key'=>'',
   'status_class'=>'badge-waiting-soft', 'status_label'=>'Waiting Approval'],

  ['id'=>4,
   'name'=>'Custom Shelving - Library',   'category'=>'Custom Furniture',
   'status'=>'Completed',                  'submitted'=>'2026-01-20', 'updated'=>'1 week ago',
   'notes'=>'Back-lit acrylic panels, tempered glass shelves, matte black steel frame.',
   'files'=>['shelving-plan.pdf','material-list.pdf'],
   'activity'=>[
     ['text'=>'Project completed',      'date'=>'2026-02-28','by'=>'Vast Solutions','dot'=>'blue'],
     ['text'=>'Installation done',      'date'=>'2026-02-25','by'=>'Vast Solutions','dot'=>'blue'],
     ['text'=>'Quote request submitted','date'=>'2026-01-20','by'=>'John Doe',      'dot'=>'blue'],
   ],
   'quote_id'=>'QT-2026-039', 'quote_status'=>'Approved',
   'quote_prepared_for'=>'John Doe', 'quote_issued'=>'2026-01-22', 'quote_valid'=>'2026-02-10',
   'quote_items'=>[
     ['desc'=>'Steel Frame (custom)',  'qty'=>1,'unit'=>5800.00,'amount'=>5800.00],
     ['desc'=>'Tempered Glass Shelves','qty'=>8,'unit'=>420.00, 'amount'=>3360.00],
     ['desc'=>'Acrylic Panels',        'qty'=>4,'unit'=>380.00, 'amount'=>1520.00],
     ['desc'=>'LED Lighting',          'qty'=>1,'unit'=>920.00, 'amount'=>920.00],
     ['desc'=>'Installation',          'qty'=>1,'unit'=>1400.00,'amount'=>1400.00],
   ],
   'quote_notes'=>'Lead time 3 weeks from approval. Delivery included.',
   'code'=>'PRJ-2026-039', 'customer'=>'Park Residences', 'target'=>'Mar 08, 2026',
   'start'=>'2026-01-15', 'progress'=>100, 'approver'=>'Engr. Sofia Lim',
   'details'=>'Custom lobby display unit with back-lit acrylic panels and tempered glass shelves.',
   'materials_key'=>'039', 'updates_key'=>'039',
   'status_class'=>'badge-completed-soft', 'status_label'=>'Completed'],

  ['id'=>5,
   'name'=>'Reception Desk - Lobby',      'category'=>'Custom Furniture',
   'status'=>'Quote Received',             'submitted'=>'2026-03-08', 'updated'=>'5 hours ago',
   'notes'=>'L-shaped reception desk, engineered stone top, white body.',
   'files'=>['reception-layout.pdf'],
   'activity'=>[
     ['text'=>'Quotation sent to client', 'date'=>'2026-03-10','by'=>'Vast Solutions','dot'=>'blue'],
     ['text'=>'Site visit completed',     'date'=>'2026-03-09','by'=>'Vast Solutions','dot'=>'blue'],
     ['text'=>'Quote request submitted',  'date'=>'2026-03-08','by'=>'John Doe',      'dot'=>'blue'],
   ],
   'quote_id'=>'QT-2026-037', 'quote_status'=>'Pending',
   'quote_prepared_for'=>'John Doe', 'quote_issued'=>'2026-03-10', 'quote_valid'=>'2026-03-25',
   'quote_items'=>[
     ['desc'=>'Desk Body (L-shape)',    'qty'=>1,'unit'=>6200.00,'amount'=>6200.00],
     ['desc'=>'Engineered Stone Top',   'qty'=>1,'unit'=>3400.00,'amount'=>3400.00],
     ['desc'=>'Under-desk Storage',     'qty'=>2,'unit'=>850.00, 'amount'=>1700.00],
     ['desc'=>'Installation Labour',    'qty'=>1,'unit'=>1200.00,'amount'=>1200.00],
   ],
   'quote_notes'=>'Stone top lead time 10 working days. Installation 1 day.',
   'code'=>'PRJ-2026-037', 'customer'=>'Garcia Build Co', 'target'=>'Mar 01, 2026',
   'start'=>'2026-01-20', 'progress'=>85, 'approver'=>'Engr. Marco Reyes',
   'details'=>'Full-height pantry cabinets with pull-out shelves and ventilation slats.',
   'materials_key'=>'037', 'updates_key'=>'037',
   'status_class'=>'badge-fabrication', 'status_label'=>'Fabrication'],
];

function statusClass($s) {
  return match($s) {
    'In Progress'    => 'in-progress',
    'Pending Quote'  => 'pending',
    'Completed'      => 'completed',
    'Quote Received' => 'quote-received',
    default          => ''
  };
}

function peso($n) { return '₱'.number_format($n,2); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Projects – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    /* ══ MODAL SHELLS ══════════════════════════════ */
    #verifyModal .modal-dialog { max-width: 820px; }
    #viewModal   .modal-dialog { max-width: 700px; }
    #materialsModal .modal-dialog { max-width: 640px; }

    #verifyModal .modal-content,
    #viewModal   .modal-content,
    #materialsModal .modal-content { border-radius: 14px; border: none; overflow: hidden; }

    #verifyModal   { z-index: 1055; }
    #viewModal     { z-index: 1065; }
    #materialsModal{ z-index: 1075; }

    /* ══ VERIFY MODAL ══════════════════════════════ */
    /* Header */
    .vm-header {
      display: flex; align-items: flex-start; justify-content: space-between;
      padding: 18px 22px 14px; border-bottom: 1px solid #e5e7eb; background: #fff;
    }
    .vm-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:800; color:#0d1b2a; margin:0; }
    .vm-subtitle { font-size:0.7rem; color:#6b7280; margin-top:2px; }

    /* Scrollable body */
    #verifyModal .modal-body { padding: 20px 22px 6px; max-height: 72vh; overflow-y: auto; background:#f4f5f7; }

    /* Two-column project details block (matches Image 1 top section) */
    .vm-detail-grid {
      display: grid;
      grid-template-columns: 1fr 280px;
      gap: 14px;
      margin-bottom: 14px;
    }
    @media(max-width:600px){ .vm-detail-grid { grid-template-columns: 1fr; } }

    .vm-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 18px 20px;
    }
    .vm-card-title {
      font-weight: 700; font-size: 0.88rem; color: #111827; margin-bottom: 14px;
    }

    /* Fields inside detail card */
    .vm-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 20px; }
    .vm-field-label { font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; margin-bottom:3px; }
    .vm-field-value { font-size:0.82rem; font-weight:500; color:#111827; }

    /* File links */
    .vm-file-list { display:flex; flex-direction:column; gap:5px; margin-top:2px; }
    .vm-file-link {
      display:inline-flex; align-items:center; gap:5px;
      font-size:0.78rem; color:#0D9676; text-decoration:none;
      transition: color .15s;
    }
    .vm-file-link:hover { color:#0a7a60; text-decoration:underline; }
    .vm-file-link i { font-size:0.8rem; }

    /* Notes full-width */
    .vm-field.full { grid-column: 1 / -1; }

    /* Activity card */
    .vm-activity-list { display:flex; flex-direction:column; gap:14px; }
    .vm-activity-item { display:flex; gap:10px; align-items:flex-start; }
    .vm-activity-dot {
      width:9px; height:9px; border-radius:50%;
      background:#3b82f6; margin-top:3px; flex-shrink:0;
    }
    .vm-activity-text { font-size:0.78rem; font-weight:600; color:#111827; }
    .vm-activity-date { font-size:0.68rem; color:#9ca3af; margin-top:2px; }

    /* Quotation bar (Image 1 bottom row) */
    .vm-quotation-bar {
      background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
      padding: 14px 20px; display: flex; align-items: center;
      justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;
    }
    .vm-q-left { display:flex; align-items:center; gap:12px; }
    .vm-q-id   { font-weight:700; font-size:0.9rem; color:#111827; }

    /* Quotation-status badge */
    .vm-q-badge {
      display:inline-flex; align-items:center; gap:5px;
      font-size:0.72rem; font-weight:600; padding:3px 10px; border-radius:99px;
    }
    .vm-q-badge::before { content:''; width:6px; height:6px; border-radius:50%; background:currentColor; display:inline-block; }
    .vm-q-badge.pending  { background:rgba(245,158,11,.12); color:#d97706; }
    .vm-q-badge.approved { background:rgba(34,197,94,.12);  color:#16a34a; }
    .vm-q-badge.rejected { background:rgba(239,68,68,.12);  color:#dc2626; }

    .vm-download-btn {
      display:inline-flex; align-items:center; gap:5px;
      font-size:0.75rem; font-weight:600; color:#6b7280;
      background:#fff; border:1.5px solid #e5e7eb; border-radius:7px;
      padding:6px 14px; cursor:pointer; text-decoration:none;
      transition:border-color .18s, color .18s;
    }
    .vm-download-btn:hover { border-color:#0D9676; color:#0D9676; }

    /* Invoice / quotation paper (Image 2) */
    .vm-paper {
      background:#fff; border:1px solid #e0e0e0;
      border-radius:10px; padding:2.2rem 2.4rem; margin-bottom:14px;
    }
    .vm-paper-header { display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:1.8rem; }
    .vm-company-name { font-family:'Syne',sans-serif; font-weight:800; font-size:1.05rem; color:#111827; }
    .vm-company-sub  { font-size:0.7rem; color:#6b7280; margin-top:2px; }
    .vm-word         { font-weight:700; font-size:0.88rem; letter-spacing:.1em; color:#111827; text-align:right; }
    .vm-q-number     { font-size:0.75rem; color:#6b7280; text-align:right; margin-top:2px; }

    .vm-paper-meta { display:grid; grid-template-columns:1fr 1fr; gap:.25rem 0; margin-bottom:1.4rem; }
    .vm-meta-label { font-size:.62rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; color:#9ca3af; }
    .vm-meta-value { font-size:.78rem; font-weight:600; color:#111827; margin-bottom:.35rem; }
    .vm-meta-right  { text-align:right; }

    .vm-q-table { width:100%; border-collapse:collapse; margin-bottom:1.2rem; }
    .vm-q-table th {
      font-size:.62rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase;
      color:#6b7280; border-bottom:1.5px solid #e5e7eb; padding:.5rem .4rem; text-align:left;
    }
    .vm-q-table th:not(:first-child) { text-align:right; }
    .vm-q-table td { font-size:.78rem; padding:.55rem .4rem; border-bottom:1px solid #f0f0f0; color:#374151; }
    .vm-q-table td:not(:first-child) { text-align:right; }
    .vm-q-table tr:last-child td { border-bottom:none; }
    .vm-q-total td { font-weight:700; border-top:2px solid #e5e7eb; border-bottom:none; padding-top:.7rem; }
    .vm-q-total td:last-child { font-size:1rem; color:#111827; }

    .vm-paper-notes {
      background:#f9fafb; border:1px solid #e5e7eb; border-radius:7px;
      padding:.75rem 1rem; font-size:.72rem; color:#6b7280; line-height:1.6; margin-bottom:1.4rem;
    }
    .vm-paper-notes-label { font-weight:700; font-size:.62rem; text-transform:uppercase; letter-spacing:.07em; color:#9ca3af; margin-bottom:.3rem; }
    .vm-paper-footer { text-align:center; font-size:.62rem; color:#9ca3af; border-top:1px solid #e5e7eb; padding-top:.75rem; }

    /* ── Sticky Accept / Reject bar at the very bottom ── */
    .vm-action-bar {
      position: sticky; bottom: 0;
      background: #fff; border-top: 1px solid #e5e7eb;
      padding: 12px 22px; display: flex;
      justify-content: space-between; align-items: center;
      gap: 10px;
    }
    .vm-action-hint { font-size:0.72rem; color:#9ca3af; }
    .vm-action-btns { display:flex; gap:10px; }

    .vm-accept-btn {
      display:inline-flex; align-items:center; gap:6px;
      background:#0D9676; color:#fff; border:none; border-radius:8px;
      padding:8px 20px; font-size:0.82rem; font-weight:700; cursor:pointer;
      font-family:'Inter',sans-serif; transition:background .18s;
    }
    .vm-accept-btn:hover { background:#0a7a60; }

    .vm-reject-btn {
      display:inline-flex; align-items:center; gap:6px;
      background:rgba(239,68,68,.08); color:#ef4444;
      border:1.5px solid rgba(239,68,68,.25); border-radius:8px;
      padding:8px 20px; font-size:0.82rem; font-weight:700; cursor:pointer;
      font-family:'Inter',sans-serif; transition:background .18s;
    }
    .vm-reject-btn:hover { background:rgba(239,68,68,.15); }

    /* ══ VIEW MODAL (read-only monitoring) ════════ */
    .pvm-hero { background:#fff; padding:18px 22px 13px; border-bottom:1px solid #e5e7eb; }
    .pvm-hero-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; }
    .pvm-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:800; color:#0d1b2a; margin:0; line-height:1.2; }
    .pvm-code  { font-size:0.7rem; color:#6b7280; margin-top:3px; }
    .pvm-progress-wrap { margin-top:11px; }
    .pvm-progress-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:5px; }
    .pvm-progress-pct { font-family:'Syne',sans-serif; font-size:1.3rem; font-weight:800; color:#0D9676; }
    .pvm-progress-track { height:10px; background:#e5e7eb; border-radius:99px; overflow:hidden; }
    .pvm-progress-fill  { height:100%; border-radius:99px; background:linear-gradient(90deg,#0D9676,#1dc89a); transition:width .5s ease; }

    #viewModal .modal-body { padding:0; max-height:72vh; overflow-y:auto; }

    .pvm-details-section { padding:14px 22px; border-bottom:1px solid #f0f0f0; }
    .pvm-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:12px 20px; }
    @media(max-width:480px){ .pvm-grid-2{grid-template-columns:1fr;} }
    .pvm-field-label { font-size:.6rem; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#9ca3af; margin-bottom:3px; }
    .pvm-field-value { font-size:.8rem; font-weight:500; color:#1f2937; }

    .pv-approver { display:inline-flex; align-items:center; gap:5px; background:#f0fdf9; border:1px solid #6ee7d0; border-radius:7px; padding:3px 9px; font-size:.72rem; font-weight:500; color:#0a7a60; }
    .btn-materials-ro { display:inline-flex; align-items:center; gap:5px; font-size:.7rem; font-weight:600; padding:4px 11px; border-radius:6px; border:1px solid #0D9676; color:#0D9676; background:#f0fdf9; cursor:pointer; text-decoration:none; transition:background .18s,color .18s; }
    .btn-materials-ro:hover { background:#0D9676; color:#fff; }

    .pvm-updates-section { padding:14px 22px 20px; }
    .pvm-section-title { font-family:'Syne',sans-serif; font-size:.8rem; font-weight:700; color:#0d1b2a; display:flex; align-items:center; gap:6px; margin-bottom:12px; }
    .pvm-section-title i { color:#0D9676; }
    .pvm-readonly-notice { display:flex; align-items:center; gap:7px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:7px; padding:7px 11px; font-size:.7rem; color:#6b7280; margin-bottom:12px; }
    .pvm-readonly-notice i { color:#9ca3af; flex-shrink:0; }

    .pvm-update-item { display:flex; gap:9px; margin-bottom:14px; }
    .pvm-update-item:last-child { margin-bottom:0; }
    .pvm-update-dot-col { display:flex; flex-direction:column; align-items:center; flex-shrink:0; }
    .pvm-update-dot { width:9px; height:9px; border-radius:50%; background:#0D9676; border:2px solid #fff; box-shadow:0 0 0 2px #0D9676; margin-top:4px; }
    .pvm-update-dot-line { width:2px; flex:1; background:#e5e7eb; margin-top:4px; min-height:16px; }
    .pvm-update-content { flex:1; min-width:0; }
    .pvm-update-meta { display:flex; align-items:center; gap:5px; margin-bottom:3px; flex-wrap:wrap; }
    .pvm-update-avatar { width:20px; height:20px; border-radius:50%; background:#dbeafe; color:#1d4ed8; display:flex; align-items:center; justify-content:center; font-size:.56rem; font-weight:700; flex-shrink:0; }
    .pvm-update-author { font-size:.72rem; font-weight:600; color:#111827; }
    .pvm-update-time   { font-size:.65rem; color:#9ca3af; }
    .pvm-update-text   { font-size:.75rem; color:#374151; line-height:1.55; margin-bottom:6px; }
    .pvm-attachments   { display:flex; gap:6px; flex-wrap:wrap; }
    .pvm-attachment-chip { padding:3px 10px; border-radius:6px; font-size:.64rem; font-weight:600; }
    .chip-green  { background:#dcfce7; color:#15803d; }
    .chip-blue   { background:#dbeafe; color:#1d4ed8; }
    .chip-yellow { background:#fef9c3; color:#b45309; }
    .chip-purple { background:#ede9fe; color:#6d28d9; }
    .chip-pink   { background:#fce7f3; color:#be185d; }
    .pvm-empty-updates { text-align:center; padding:22px 0; color:#9ca3af; font-size:.75rem; }
    .pvm-empty-updates i { font-size:1.4rem; display:block; margin-bottom:6px; opacity:.4; }

    /* Shared badge aliases */
    .badge-fabrication    { background:rgba(249,115,22,.12); color:#ea580c; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:999px; display:inline-block; }
    .badge-approved-soft  { background:rgba(59,130,246,.12);  color:#2563eb; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:999px; display:inline-block; }
    .badge-waiting-soft   { background:rgba(245,158,11,.12);  color:#d97706; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:999px; display:inline-block; }
    .badge-completed-soft { background:rgba(34,197,94,.12);   color:#16a34a; font-size:.62rem; font-weight:700; padding:3px 9px; border-radius:999px; display:inline-block; }

    /* Materials modal */
    #materialsModal .modal-header { background:#0d1b2a; color:#fff; border-radius:12px 12px 0 0; }
    #materialsModal .modal-header .btn-close { filter:invert(1) grayscale(1); }
    .mat-table thead th { background:#f8fafc; font-size:.6rem; font-weight:700; color:#9ca3af; letter-spacing:.04em; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding:9px 14px; }
    .mat-table tbody td { font-size:.74rem; color:#374151; padding:9px 14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
    .mat-table tbody tr:last-child td { border-bottom:none; }

    /* Verify btn style */
    .btn-verify {
      display:inline-flex; align-items:center; gap:4px;
      font-size:.8rem; font-weight:600; color:#fff;
      background:#0D9676; border:none; border-radius:7px;
      padding:5px 12px; cursor:pointer; text-decoration:none;
      transition:background .18s; font-family:'Inter',sans-serif;
    }
    .btn-verify:hover { background:#0a7a60; color:#fff; }
    .btn-verify svg { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Projects</span>
  </div>

  <div class="page-content">
    <h1 class="page-title">My Projects</h1>

    <div class="section-card" style="padding:1.4rem 1.6rem;">
      <div class="table-header-row">
        <div class="section-card-title mb-0">All Projects</div>
        <a href="request_quote.php" class="btn-new">+ New Quote Request</a>
      </div>

      <table class="projects-table">
        <thead>
          <tr>
            <th>Project Name</th><th>Category</th><th>Status</th>
            <th>Submitted</th><th>Last Updated</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $p): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
            <td class="muted"><?= htmlspecialchars($p['category']) ?></td>
            <td><span class="badge-status <?= statusClass($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
            <td class="muted"><?= $p['submitted'] ?></td>
            <td class="muted"><?= $p['updated'] ?></td>
            <td style="text-align:right">
              <div style="display:inline-flex;gap:8px;align-items:center;">

                <?php if ($p['status'] === 'Quote Received'): ?>
                <!-- VERIFY — only for Quote Received rows -->
                <button class="btn-verify verify-btn"
                  data-id="<?= $p['id'] ?>"
                  data-name="<?= htmlspecialchars($p['name']) ?>"
                  data-code="<?= htmlspecialchars($p['code']) ?>"
                  data-category="<?= htmlspecialchars($p['category']) ?>"
                  data-submitted="<?= $p['submitted'] ?>"
                  data-updated="<?= htmlspecialchars($p['updated']) ?>"
                  data-status="<?= htmlspecialchars($p['status']) ?>"
                  data-notes="<?= htmlspecialchars($p['notes']) ?>"
                  data-files="<?= htmlspecialchars(json_encode($p['files'])) ?>"
                  data-activity="<?= htmlspecialchars(json_encode($p['activity'])) ?>"
                  data-quote-id="<?= htmlspecialchars($p['quote_id']) ?>"
                  data-quote-status="<?= htmlspecialchars(strtolower($p['quote_status'])) ?>"
                  data-quote-status-label="<?= htmlspecialchars($p['quote_status']) ?>"
                  data-quote-for="<?= htmlspecialchars($p['quote_prepared_for']) ?>"
                  data-quote-issued="<?= htmlspecialchars($p['quote_issued']) ?>"
                  data-quote-valid="<?= htmlspecialchars($p['quote_valid']) ?>"
                  data-quote-items="<?= htmlspecialchars(json_encode($p['quote_items'])) ?>"
                  data-quote-notes="<?= htmlspecialchars($p['quote_notes']) ?>"
                  data-quote-total="<?= htmlspecialchars(peso(array_sum(array_column($p['quote_items'],'amount')))) ?>">
                  <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
                  Verify
                </button>
                <?php endif; ?>

                <!-- VIEW — always shown -->
                <button class="btn-view view-btn"
                  data-id="<?= $p['id'] ?>"
                  data-name="<?= htmlspecialchars($p['name']) ?>"
                  data-code="<?= htmlspecialchars($p['code']) ?>"
                  data-status="<?= htmlspecialchars($p['status_label']) ?>"
                  data-status-class="<?= htmlspecialchars($p['status_class']) ?>"
                  data-customer="<?= htmlspecialchars($p['customer']) ?>"
                  data-target="<?= htmlspecialchars($p['target']) ?>"
                  data-start="<?= $p['start'] ?>"
                  data-progress="<?= $p['progress'] ?>"
                  data-approver="<?= htmlspecialchars($p['approver']) ?>"
                  data-details="<?= htmlspecialchars($p['details']) ?>"
                  data-materials-key="<?= $p['materials_key'] ?>"
                  data-updates-key="<?= $p['updates_key'] ?>">
                  <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                  View
                </button>

              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════
     VERIFY MODAL
══════════════════════════════════════════════ -->
<div class="modal fade" id="verifyModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <!-- Header -->
      <div class="vm-header">
        <div>
          <div class="vm-title" id="vmTitle">—</div>
          <div class="vm-subtitle" id="vmCode">—</div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <!-- Scrollable body -->
      <div class="modal-body">

        <!-- ── Top grid: Project Details + Activity ── -->
        <div class="vm-detail-grid">

          <!-- Project Details card -->
          <div class="vm-card">
            <div class="vm-card-title">Project Details</div>
            <div class="vm-fields">

              <div>
                <div class="vm-field-label">Category</div>
                <div class="vm-field-value" id="vmCategory">—</div>
              </div>

              <div>
                <div class="vm-field-label">Status</div>
                <div><span class="badge-status" id="vmStatus">—</span></div>
              </div>

              <div>
                <div class="vm-field-label">Submitted</div>
                <div class="vm-field-value" id="vmSubmitted">—</div>
              </div>

              <div>
                <div class="vm-field-label">Files</div>
                <div class="vm-file-list" id="vmFiles">—</div>
              </div>

              <div class="vm-field full">
                <div class="vm-field-label">Notes</div>
                <div class="vm-field-value" id="vmNotes" style="font-weight:400;color:#4b5563;line-height:1.6;">—</div>
              </div>

            </div>
          </div>

          <!-- Activity card -->
          <div class="vm-card">
            <div class="vm-card-title">Activity</div>
            <div class="vm-activity-list" id="vmActivity"></div>
          </div>

        </div>

        <!-- ── Quotation bar ── -->
        <div class="vm-quotation-bar">
          <div class="vm-q-left">
            <span class="vm-q-id" id="vmQId">—</span>
            <span class="vm-q-badge pending" id="vmQBadge">—</span>
          </div>
          <button class="vm-download-btn" id="vmDownloadBtn">
            <i class="bi bi-download"></i> Download PDF
          </button>
        </div>

        <!-- ── Quotation paper (invoice) ── -->
        <div class="vm-paper" id="vmPaper">

          <div class="vm-paper-header">
            <div>
              <div class="vm-company-name">VAST SOLUTIONS</div>
              <div class="vm-company-sub">Custom Joinery &amp; Fitouts</div>
            </div>
            <div>
              <div class="vm-word">QUOTATION</div>
              <div class="vm-q-number" id="vmPaperQId">—</div>
            </div>
          </div>

          <div class="vm-paper-meta">
            <div>
              <div class="vm-meta-label">Prepared For</div>
              <div class="vm-meta-value" id="vmPaperFor">—</div>
              <div class="vm-meta-label">Project</div>
              <div class="vm-meta-value" id="vmPaperProject">—</div>
            </div>
            <div class="vm-meta-right">
              <div class="vm-meta-label">Date Issued</div>
              <div class="vm-meta-value" id="vmPaperIssued">—</div>
              <div class="vm-meta-label">Valid Until</div>
              <div class="vm-meta-value" id="vmPaperValid">—</div>
            </div>
          </div>

          <table class="vm-q-table">
            <thead>
              <tr>
                <th>Description</th>
                <th>Qty</th>
                <th>Unit Price</th>
                <th>Amount</th>
              </tr>
            </thead>
            <tbody id="vmPaperItems"></tbody>
            <tfoot>
              <tr class="vm-q-total">
                <td colspan="3" style="text-align:right;">TOTAL</td>
                <td id="vmPaperTotal">—</td>
              </tr>
            </tfoot>
          </table>

          <div class="vm-paper-notes" id="vmPaperNotesWrap">
            <div class="vm-paper-notes-label">Notes</div>
            <div id="vmPaperNotes">—</div>
          </div>

          <div class="vm-paper-footer">
            Vast Solutions &nbsp;·&nbsp; info@vastsolutions.com &nbsp;·&nbsp; +63 912 345 6789
          </div>

        </div><!-- /vm-paper -->

      </div><!-- /modal-body -->

      <!-- ── Sticky Accept / Reject bar ── -->
      <div class="vm-action-bar">
        <span class="vm-action-hint">Review the quotation above before responding.</span>
        <div class="vm-action-btns">
          <button class="vm-reject-btn" id="btnRejectQuote">
            <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            Reject Quote
          </button>
          <button class="vm-accept-btn" id="btnAcceptQuote">
            <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
            Accept Quote
          </button>
        </div>
      </div>

    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════
     VIEW MODAL  (read-only monitoring details)
══════════════════════════════════════════════ -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="pvm-hero">
        <div class="pvm-hero-top">
          <div>
            <div class="pvm-title" id="viewTitle">—</div>
            <div class="pvm-code"  id="viewCode">—</div>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="pvm-progress-wrap">
          <div class="pvm-progress-header">
            <span class="pvm-progress-pct" id="viewProgressPct">0%</span>
            <span id="viewStatusBadge">—</span>
          </div>
          <div class="pvm-progress-track">
            <div class="pvm-progress-fill" id="viewProgressFill" style="width:0%"></div>
          </div>
        </div>
      </div>

      <div class="modal-body">
        <div class="pvm-details-section">
          <div class="pvm-grid-2">
            <div>
              <div class="pvm-field-label">Customer</div>
              <div class="pvm-field-value" id="viewCustomer">—</div>
            </div>
            <div>
              <div class="pvm-field-label">Target Date</div>
              <div class="pvm-field-value" id="viewTarget">—</div>
            </div>
            <div id="viewWrapStart">
              <div class="pvm-field-label">Start Date (Approved)</div>
              <div class="pvm-field-value" id="viewStart">—</div>
            </div>
            <div id="viewWrapApprover">
              <div class="pvm-field-label">Approved By</div>
              <div class="pv-approver" id="viewApprover">
                <i class="bi bi-person-check-fill"></i><span>—</span>
              </div>
            </div>
            <div id="viewWrapMaterials">
              <div class="pvm-field-label">Materials Used</div>
              <button class="btn-materials-ro" id="btnViewMaterials">
                <i class="bi bi-box-seam"></i> View Materials List
              </button>
            </div>
          </div>
          <div style="margin-top:12px;">
            <div class="pvm-field-label">Project Details</div>
            <div class="pvm-field-value" id="viewDetails" style="font-weight:400;color:#4b5563;line-height:1.6;font-size:0.78rem;">—</div>
          </div>
        </div>

        <div class="pvm-updates-section">
          <div class="pvm-section-title"><i class="bi bi-activity"></i> Project Updates</div>
          <div class="pvm-readonly-notice">
            <i class="bi bi-lock-fill"></i>
            Updates are posted by the Vast Solutions team. You will be notified of new activity.
          </div>
          <div id="viewUpdatesFeed"></div>
        </div>
      </div>

    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════
     MATERIALS NESTED MODAL
══════════════════════════════════════════════ -->
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
<script>
// ── MATERIALS DATA ─────────────────────────────────
const MATERIALS = {
  '042': [
    {name:'Melamine Board 18mm',spec:'White, 4x8 ft',qty:24,unit:'sheets'},
    {name:'PVC Edge Banding',spec:'White, 22mm width',qty:150,unit:'meters'},
    {name:'Soft-Close Hinge',spec:'35mm cup, full overlay',qty:96,unit:'pcs'},
    {name:'Cabinet Handle',spec:'Stainless, 128mm hole',qty:48,unit:'pcs'},
    {name:'Drawer Slide 400mm',spec:'Full extension, 40kg',qty:24,unit:'pairs'},
    {name:'Dowel Pin 8x35mm',spec:'Hardwood',qty:500,unit:'pcs'},
    {name:'Wood Glue',spec:'PVA, 1L bottle',qty:6,unit:'bottles'},
  ],
  '041': [
    {name:'MDF Board 18mm',spec:'E1 grade, 4x8 ft',qty:18,unit:'sheets'},
    {name:'White Laminate',spec:'Hi-gloss, 1.22x2.44m',qty:18,unit:'sheets'},
    {name:'Soft-Close Hinge',spec:'35mm, half overlay',qty:72,unit:'pcs'},
    {name:'Cam Lock 15mm',spec:'Zinc alloy',qty:200,unit:'pcs'},
    {name:'Adjustable Shelf Pin',spec:'5mm, chrome',qty:120,unit:'pcs'},
  ],
  '039': [
    {name:'Tempered Glass 10mm',spec:'Clear, custom cut',qty:8,unit:'panels'},
    {name:'Acrylic Panel 6mm',spec:'Frosted white, back-lit',qty:4,unit:'panels'},
    {name:'Steel Flat Bar 40x5',spec:'Mild steel, 6m length',qty:12,unit:'lengths'},
    {name:'Powder Coat Paint',spec:'Matte black, RAL 9005',qty:4,unit:'liters'},
    {name:'LED Strip Light',spec:'Warm white, 12V IP20',qty:10,unit:'meters'},
    {name:'Glass Shelf Bracket',spec:'Chrome, 150mm',qty:16,unit:'pcs'},
  ],
  '037': [
    {name:'Particle Board 18mm',spec:'E1 grade, 4x8 ft',qty:30,unit:'sheets'},
    {name:'PVC Edge Banding',spec:'Beige, 22mm width',qty:200,unit:'meters'},
    {name:'Pull-Out Shelf Slide',spec:'450mm, 25kg',qty:20,unit:'pairs'},
    {name:'Soft-Close Hinge',spec:'35mm, full overlay',qty:80,unit:'pcs'},
    {name:'Ventilation Grille',spec:'PVC, 150x100mm',qty:10,unit:'pcs'},
    {name:'Cam Lock 15mm',spec:'Zinc alloy',qty:250,unit:'pcs'},
  ],
};

// ── SEED UPDATES ───────────────────────────────────
const SEED_UPDATES = {
  '042': [
    {author:'Marco Reyes',initials:'MR',time:'Mar 14, 2026 · 3:00 PM',
     text:'Cabinet boxes assembled and sanded. Starting laminate application tomorrow.',
     attachments:[{label:'Cabinet Frame',cls:'chip-blue'},{label:'Sanding Detail',cls:'chip-yellow'}]},
    {author:'Ana Cruz',initials:'AC',time:'Mar 12, 2026 · 10:30 AM',
     text:'All custom hinges installed and tested. Soft-close function confirmed on all doors.',
     attachments:[{label:'Hinge Test',cls:'chip-green'}]},
    {author:'Marco Reyes',initials:'MR',time:'Mar 10, 2026 · 9:00 AM',
     text:'Materials delivered and staged on site.',attachments:[]},
  ],
  '041': [
    {author:'Lena Tan',initials:'LT',time:'Mar 11, 2026 · 2:00 PM',
     text:'Site measurements confirmed. Drawings sent for final sign-off.',
     attachments:[{label:'Revised Plan',cls:'chip-purple'}]},
  ],
  '039': [
    {author:'Sofia Lim',initials:'SL',time:'Feb 28, 2026 · 4:00 PM',
     text:'Final installation complete. Handover document signed.',
     attachments:[{label:'Handover Doc',cls:'chip-green'},{label:'Site Photos',cls:'chip-blue'}]},
    {author:'Jose Reyes',initials:'JR',time:'Feb 25, 2026 · 11:00 AM',
     text:'LED strip lights wired and tested.',attachments:[{label:'LED Test',cls:'chip-yellow'}]},
  ],
  '037': [
    {author:'Marco Reyes',initials:'MR',time:'Mar 13, 2026 · 1:00 PM',
     text:'Pull-out shelf slides installed on all units.',
     attachments:[{label:'Slide Install',cls:'chip-green'}]},
    {author:'Ben Garcia',initials:'BG',time:'Mar 10, 2026 · 8:30 AM',
     text:'Edge banding applied. Additional PVC order placed.',attachments:[]},
  ],
};

const CHIPS=['chip-green','chip-blue','chip-yellow','chip-purple','chip-pink'];

function fmtDate(iso){
  if(!iso) return '—';
  return new Date(iso+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
}

function peso(n){ return '₱'+Number(n).toLocaleString('en-PH',{minimumFractionDigits:2}); }

// ══ VERIFY MODAL ═══════════════════════════════════
let currentVerifyId = null;

document.querySelectorAll('.verify-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    currentVerifyId = this.dataset.id;
    const d = this.dataset;

    // Header
    document.getElementById('vmTitle').textContent = d.name;
    document.getElementById('vmCode').textContent  = d.code;

    // Status badge in detail card
    const stBadge = document.getElementById('vmStatus');
    stBadge.textContent = d.status;
    stBadge.className   = 'badge-status ' + (d.status==='Quote Received'?'quote-received':'');

    // Fields
    document.getElementById('vmCategory').textContent  = d.category;
    document.getElementById('vmSubmitted').textContent = d.submitted;
    document.getElementById('vmNotes').textContent     = d.notes;

    // Files
    const files = JSON.parse(d.files || '[]');
    document.getElementById('vmFiles').innerHTML = files.length
      ? files.map(f=>`<a href="#" class="vm-file-link"><i class="bi bi-file-earmark-text"></i>${f}</a>`).join('')
      : '<span style="color:#9ca3af;font-size:.78rem">No files</span>';

    // Activity
    const activity = JSON.parse(d.activity || '[]');
    document.getElementById('vmActivity').innerHTML = activity.map(a=>`
      <div class="vm-activity-item">
        <div class="vm-activity-dot"></div>
        <div>
          <div class="vm-activity-text">${a.text}</div>
          <div class="vm-activity-date">${a.date} · ${a.by}</div>
        </div>
      </div>`).join('');

    // Quotation bar
    document.getElementById('vmQId').textContent = d.quoteId;
    const qBadge = document.getElementById('vmQBadge');
    qBadge.textContent = d.quoteStatusLabel;
    qBadge.className   = 'vm-q-badge ' + (d.quoteStatus || 'pending');

    // Quotation paper
    document.getElementById('vmPaperQId').textContent      = d.quoteId;
    document.getElementById('vmPaperFor').textContent      = d.quoteFor;
    document.getElementById('vmPaperProject').textContent  = d.name;
    document.getElementById('vmPaperIssued').textContent   = d.quoteIssued  || '—';
    document.getElementById('vmPaperValid').textContent    = d.quoteValid   || '—';
    document.getElementById('vmPaperNotes').textContent    = d.quoteNotes   || '—';
    document.getElementById('vmPaperTotal').textContent    = d.quoteTotal   || '—';

    const items = JSON.parse(d.quoteItems || '[]');
    document.getElementById('vmPaperItems').innerHTML = items.length
      ? items.map(i=>`<tr>
          <td>${i.desc}</td>
          <td>${i.qty}</td>
          <td>${peso(i.unit)}</td>
          <td>${peso(i.amount)}</td>
        </tr>`).join('')
      : '<tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:16px;">No items listed.</td></tr>';

    // Hide paper notes wrap if empty
    document.getElementById('vmPaperNotesWrap').style.display = d.quoteNotes ? '' : 'none';

    new bootstrap.Modal(document.getElementById('verifyModal')).show();
  });
});

document.getElementById('btnAcceptQuote').addEventListener('click', function(){
  alert('Quote accepted! (ID: '+currentVerifyId+')');
  bootstrap.Modal.getInstance(document.getElementById('verifyModal')).hide();
});

document.getElementById('btnRejectQuote').addEventListener('click', function(){
  if(confirm('Are you sure you want to reject this quote?')){
    alert('Quote rejected. (ID: '+currentVerifyId+')');
    bootstrap.Modal.getInstance(document.getElementById('verifyModal')).hide();
  }
});

document.getElementById('vmDownloadBtn').addEventListener('click', function(){
  window.print(); // wire to real PDF endpoint
});

// ══ VIEW MODAL ═════════════════════════════════════
let currentMatsKey='', currentMatName='';

function renderFeed(key){
  const feed = document.getElementById('viewUpdatesFeed');
  const all  = SEED_UPDATES[key] || [];
  if(!all.length){
    feed.innerHTML=`<div class="pvm-empty-updates"><i class="bi bi-chat-left-dots"></i>No updates posted yet.</div>`;
    return;
  }
  feed.innerHTML = all.map((u,idx)=>{
    const isLast = idx===all.length-1;
    const chips  = (u.attachments||[]).map((a,ci)=>
      `<span class="pvm-attachment-chip ${a.cls||CHIPS[ci%CHIPS.length]}">${a.label}</span>`
    ).join('');
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

document.querySelectorAll('.view-btn').forEach(btn=>{
  btn.addEventListener('click', function(){
    const d = this.dataset;
    document.getElementById('viewTitle').textContent = d.name;
    document.getElementById('viewCode').textContent  = d.code;

    const badge = document.getElementById('viewStatusBadge');
    badge.textContent = d.status;
    badge.className   = d.statusClass;

    const pct = parseInt(d.progress)||0;
    document.getElementById('viewProgressFill').style.width = pct+'%';
    document.getElementById('viewProgressPct').textContent  = pct+'%';

    document.getElementById('viewCustomer').textContent = d.customer;
    document.getElementById('viewTarget').textContent   = d.target;
    document.getElementById('viewDetails').textContent  = d.details;
    document.getElementById('viewStart').textContent    = d.start ? fmtDate(d.start) : '—';

    const wApp = document.getElementById('viewWrapApprover');
    const aSpan= document.querySelector('#viewApprover span');
    if(d.approver){ aSpan.textContent=d.approver; wApp.style.display=''; }
    else wApp.style.display='none';

    currentMatsKey = d.materialsKey||'';
    currentMatName = d.name||'';
    document.getElementById('viewWrapMaterials').style.display = currentMatsKey?'':'none';

    renderFeed(d.updatesKey||'');
    new bootstrap.Modal(document.getElementById('viewModal')).show();
  });
});

document.getElementById('btnViewMaterials').addEventListener('click', function(){
  const rows = MATERIALS[currentMatsKey]||[];
  document.getElementById('matProjectName').textContent = currentMatName;
  document.getElementById('matTableBody').innerHTML = rows.length
    ? rows.map((m,i)=>`<tr>
        <td class="text-muted">${i+1}</td>
        <td><strong>${m.name}</strong></td>
        <td class="text-muted" style="font-size:.7rem">${m.spec}</td>
        <td>${m.qty}</td>
        <td class="text-muted">${m.unit}</td>
      </tr>`).join('')
    : `<tr><td colspan="5" class="text-center text-muted py-4">No materials listed.</td></tr>`;
  document.getElementById('matSummary').textContent = `${rows.length} material${rows.length!==1?'s':''} listed`;
  new bootstrap.Modal(document.getElementById('materialsModal')).show();
});
</script>

</body>
</html>