<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'my_projects';

/**
 * 'status' is the project's monitoring phase (see includes/project_status.php) and is
 * the only project status — it drives the table badge, the modal badge and the step
 * tracker alike. 'quote_status' is separate: it tracks the quotation document
 * (Pending / Approved / Rejected), not the project.
 */
require_once __DIR__ . '/../includes/helpers.php';

// Build the current client's projects from the database, in the shape the page expects.
$clientId  = current_user()['id'] ?? 0;
$statusMap = ['Sent' => 'Pending', 'Accepted' => 'Accepted', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
$projects = [];
$updatesByProject = [];
$materialsByProject = [];

$__rows = db()->prepare(
    "SELECT p.*, c.name AS customer_name,
            q.id AS quotation_id, q.quote_code, q.status AS quote_status_raw,
            q.date_created AS quote_issued, q.valid_until AS quote_valid,
            q.total_amount AS quote_total, q.notes AS quote_notes, r.id AS req_id
       FROM projects p
       JOIN customers c ON c.id = p.customer_id
       LEFT JOIN quotations q ON q.id = (SELECT id FROM quotations WHERE project_id = p.id ORDER BY id DESC LIMIT 1)
       LEFT JOIN project_requests r ON r.id = p.request_id
      WHERE c.user_id = ?
      ORDER BY p.created_at DESC, p.id DESC"
);
$__rows->execute([$clientId]);
foreach ($__rows->fetchAll() as $r) {
    $pid = (int) $r['id'];

    $ups = db()->prepare("SELECT author_name, update_text, attachment_path, created_at FROM project_updates WHERE project_id = ? ORDER BY created_at DESC, id DESC");
    $ups->execute([$pid]);
    $activity = []; $updatesJs = [];
    foreach ($ups as $u) {
        $img = $u['attachment_path'] ? '../' . $u['attachment_path'] : null;
        $activity[]  = ['text' => $u['update_text'], 'date' => date('Y-m-d', strtotime($u['created_at'])), 'by' => $u['author_name'] ?: 'Vast Solutions', 'dot' => 'blue', 'image' => $img];
        $updatesJs[] = ['author' => $u['author_name'] ?: 'Vast Solutions', 'initials' => strtoupper(mb_substr($u['author_name'] ?: 'V', 0, 1)), 'time' => date('M d, Y · g:i A', strtotime($u['created_at'])), 'text' => $u['update_text'], 'image' => $img, 'attachments' => []];
    }
    if ($updatesJs) $updatesByProject[(string) $pid] = $updatesJs;

    $mats = db()->prepare("SELECT material, specification, qty, unit, status FROM project_materials WHERE project_id = ? ORDER BY sort_order, id");
    $mats->execute([$pid]);
    $mArr = [];
    foreach ($mats as $m) $mArr[] = ['name' => $m['material'], 'spec' => $m['specification'], 'qty' => (float) $m['qty'], 'unit' => $m['unit'], 'status' => $m['status']];
    if ($mArr) $materialsByProject[(string) $pid] = $mArr;

    $files = [];
    if ($r['req_id']) {
        $fs = db()->prepare("SELECT file_name FROM request_files WHERE request_id = ?");
        $fs->execute([$r['req_id']]);
        $files = array_column($fs->fetchAll(), 'file_name');
    }

    $qstatus    = $r['quote_status_raw'] ? ($statusMap[$r['quote_status_raw']] ?? $r['quote_status_raw']) : '';
    $qtot       = (float) ($r['quote_total'] ?? 0);
    // Client sees only the final total (one summary line), not the internal materials.
    $quoteItems = $r['quotation_id'] ? [['desc' => 'Custom cabinetry works — ' . $r['project_name'], 'qty' => 1, 'unit' => $qtot, 'amount' => $qtot]] : [];

    $projects[] = [
        'id' => $pid, 'name' => $r['project_name'], 'category' => $r['category'] ?? '',
        'status' => $r['status'], 'submitted' => date('M d, Y', strtotime($r['created_at'])),
        'updated' => time_ago($r['updated_at']), 'notes' => $r['description'] ?? '',
        'files' => $files, 'activity' => $activity,
        'quote_id' => $r['quote_code'] ?? '—', 'quotation_pk' => (int) ($r['quotation_id'] ?? 0),
        'quote_status' => $qstatus ?: 'Pending', 'quote_prepared_for' => $r['customer_name'],
        'quote_issued' => $r['quote_issued'] ? date('Y-m-d', strtotime($r['quote_issued'])) : '',
        'quote_valid' => $r['quote_valid'] ? date('Y-m-d', strtotime($r['quote_valid'])) : '',
        'quote_items' => $quoteItems, 'quote_notes' => $r['quote_notes'] ?? '',
        'code' => $r['project_code'], 'customer' => $r['customer_name'],
        'target' => $r['target_completion'] ? date('M d, Y', strtotime($r['target_completion'])) : '—',
        'start' => $r['start_date'] ?? '', 'progress' => (int) $r['progress'], 'approver' => $r['approver'] ?? '',
        'details' => $r['description'] ?? '', 'materials_key' => (string) $pid, 'updates_key' => (string) $pid,
    ];
}

$__ignore = [
  ['id'=>1,
   'name'=>'Kitchen Cabinets - Unit 4B',  'category'=>'Kitchen Cabinets',
   'status'=>'production',                 'submitted'=>'2026-03-01', 'updated'=>'2 hours ago',
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
   'materials_key'=>'042', 'updates_key'=>'042'],

  ['id'=>2,
   'name'=>'Office Built-ins - Floor 3',  'category'=>'Office Built-ins',
   'status'=>'approved',                   'submitted'=>'2026-03-10', 'updated'=>'1 day ago',
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
   'materials_key'=>'041', 'updates_key'=>'041'],

  ['id'=>3,
   'name'=>'Bathroom Vanity - Residence', 'category'=>'Bathroom Vanity',
   'status'=>'quote_submitted',           'submitted'=>'2026-02-15', 'updated'=>'3 days ago',
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
   'materials_key'=>'', 'updates_key'=>''],

  ['id'=>4,
   'name'=>'Custom Shelving - Library',   'category'=>'Custom Furniture',
   'status'=>'completed',                  'submitted'=>'2026-01-20', 'updated'=>'1 week ago',
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
   'materials_key'=>'039', 'updates_key'=>'039'],

  ['id'=>5,
   'name'=>'Reception Desk - Lobby',      'category'=>'Custom Furniture',
   'status'=>'production',                 'submitted'=>'2026-03-08', 'updated'=>'5 hours ago',
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
   'materials_key'=>'037', 'updates_key'=>'037'],
];

/** A quote is awaiting the client's decision once it has been issued and not yet acted on. */
function awaitingClientDecision(array $p): bool {
  return $p['quote_status'] === 'Pending' && $p['quote_issued'] !== '';
}

// peso() is provided by includes/helpers.php
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
    .vm-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:#0d1b2a; margin:0; }
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
    .vm-company-name { font-family:'Syne',sans-serif; font-weight:700; font-size:1.05rem; color:#111827; }
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
    .pvm-title { font-family:'Syne',sans-serif; font-size:1rem; font-weight:700; color:#0d1b2a; margin:0; line-height:1.2; }
    .pvm-code  { font-size:0.7rem; color:#6b7280; margin-top:3px; }
    /* Step tracker */
    .pvm-step-tracker { margin-top:12px; overflow-x:auto; padding-bottom:4px; }
    .pvm-steps { display:flex; align-items:flex-start; min-width:580px; }
    .pvm-step { display:flex; flex-direction:column; align-items:center; flex:1; min-width:0; }
    .pvm-step-dot { width:22px; height:22px; border-radius:50%; background:#e5e7eb; color:#9ca3af; display:flex; align-items:center; justify-content:center; font-size:0.58rem; font-weight:700; flex-shrink:0; }
    .pvm-step.done .pvm-step-dot { background:#0D9676; color:#fff; }
    .pvm-step.active .pvm-step-dot { background:#0D9676; color:#fff; box-shadow:0 0 0 4px rgba(13,150,118,.18); }
    .pvm-step-label { font-size:0.56rem; text-align:center; color:#9ca3af; margin-top:5px; line-height:1.3; padding:0 2px; }
    .pvm-step.done .pvm-step-label, .pvm-step.active .pvm-step-label { color:#0D9676; font-weight:600; }
    .pvm-step-connector { flex:1; height:2px; background:#e5e7eb; margin-top:11px; align-self:flex-start; min-width:6px; }
    .pvm-step-connector.done { background:#0D9676; }

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
    .pvm-update-image-link { display:inline-block; margin:2px 0 6px; }
    .pvm-update-image  { max-width:240px; width:100%; max-height:200px; object-fit:cover;
                         border-radius:8px; border:1px solid #e5e7eb; display:block; }
    .pvm-attachments   { display:flex; gap:6px; flex-wrap:wrap; }
    .pvm-attachment-chip { padding:3px 10px; border-radius:6px; font-size:.64rem; font-weight:600; }
    .chip-green  { background:#dcfce7; color:#15803d; }
    .chip-blue   { background:#dbeafe; color:#1d4ed8; }
    .chip-yellow { background:#fef9c3; color:#b45309; }
    .chip-purple { background:#ede9fe; color:#6d28d9; }
    .chip-pink   { background:#fce7f3; color:#be185d; }
    .pvm-empty-updates { text-align:center; padding:22px 0; color:#9ca3af; font-size:.75rem; }
    .pvm-empty-updates i { font-size:1.4rem; display:block; margin-bottom:6px; opacity:.4; }

    /* Status badge colours live in dashboard.css (.status-*), paired with .badge-status. */

    /* Materials modal */
    #materialsModal .modal-header { background:#0d1b2a; color:#fff; border-radius:12px 12px 0 0; }
    #materialsModal .modal-header .btn-close { filter:invert(1) grayscale(1); }
    .mat-table thead th { background:#f8fafc; font-size:.6rem; font-weight:700; color:#9ca3af; letter-spacing:.04em; text-transform:uppercase; border-bottom:1px solid #e5e7eb; padding:9px 14px; }
    .mat-table tbody td { font-size:.74rem; color:#374151; padding:9px 14px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
    .mat-table tbody tr:last-child td { border-bottom:none; }

    /* Verify btn style */
    .btn-verify {
      display: inline-flex; align-items: center; gap: 4px;
  font-size: .75rem; font-weight: 700; color: #fff;
  background: #0D9676; border: none; border-radius: 999px;  /* pill shape */
  padding: 5px 14px; cursor: pointer; text-decoration: none;
  transition: background .18s, box-shadow .18s;
  font-family: 'Inter', sans-serif;
  letter-spacing: 0.01em;
    }
    .btn-verify:hover { background:#0a7a60; color:#fff; }
    .btn-verify svg { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }
    .btn-view {
  display: inline-flex; align-items: center; gap: 4px;
  font-size: .75rem; font-weight: 600; color: #0D9676;
  background: transparent; border: 1.5px solid #0D9676;
  border-radius: 999px;                                      /* pill shape */
  padding: 5px 14px; cursor: pointer; text-decoration: none;
  transition: background .18s, color .18s, box-shadow .18s;
  font-family: 'Inter', sans-serif;
}
.btn-view:hover {
  background: #0D9676; color: #fff;
  box-shadow: 0 2px 8px rgba(13,150,118,.25);
}

/* ══ RESPONSIVE (My Projects) ══════════════════════ */
.mp-scroll { overflow-x: auto; -webkit-overflow-scrolling: touch; }
@media (max-width: 640px) {
  /* Real table kept with a min-width; the wrapper handles the sideways scroll */
  .projects-table { display: table !important; white-space: nowrap; min-width: 680px; margin: 0; }
  .projects-table th, .projects-table td { white-space: nowrap; }
}
@media (max-width: 600px) {
  /* Verify / View modals — quotation paper reflows on small screens */
  #verifyModal .modal-body, #viewModal .modal-body { padding-left: 12px; padding-right: 12px; }
  .vmp-header { flex-direction: column; gap: 12px; }
  .vmp-header > div:last-child { text-align: left !important; min-width: 0 !important; }
  .vmp-billship { grid-template-columns: 1fr !important; gap: 12px; }
  .vmp-items { overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .vmp-items table { min-width: 440px; }
  .vm-action-bar { flex-direction: column; align-items: stretch; gap: 8px; }
  .vm-action-btns { display: flex; }
  .vm-action-btns .vm-accept-btn, .vm-action-btns .vm-reject-btn { flex: 1; justify-content: center; }
  .vm-action-hint { display: none; }
}
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Projects</span>
    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
  </div>

  <div class="page-content">
    <h1 class="page-title">My Projects</h1>

    <div class="section-card" style="padding:1.4rem 1.6rem;">
      <div class="table-header-row">
        <div class="section-card-title mb-0">All Projects</div>
        <a href="request_quote.php" class="btn-new">+ New Quote Request</a>
      </div>

      <div class="mp-scroll">
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
            <td><?= project_status_badge($p['status'], 'badge-status') ?></td>
            <td class="muted"><?= $p['submitted'] ?></td>
            <td class="muted"><?= $p['updated'] ?></td>
            <td style="text-align:right">
              <div style="display:inline-flex;gap:8px;align-items:center;">

                <?php if (!empty($p['quotation_pk'])): $awaiting = awaitingClientDecision($p); ?>
                <!-- QUOTE — "Verify" while awaiting a decision, else "View Quote" (always available) -->
                <button class="btn-verify verify-btn"
                  data-id="<?= $p['id'] ?>"
                  data-awaiting="<?= $awaiting ? '1' : '0' ?>"
                  data-quotation-pk="<?= (int) ($p['quotation_pk'] ?? 0) ?>"
                  data-name="<?= htmlspecialchars($p['name']) ?>"
                  data-code="<?= htmlspecialchars($p['code']) ?>"
                  data-category="<?= htmlspecialchars($p['category']) ?>"
                  data-submitted="<?= $p['submitted'] ?>"
                  data-updated="<?= htmlspecialchars($p['updated']) ?>"
                  data-status="<?= project_status_key($p['status']) ?>"
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
                  <?= $awaiting ? 'Verify' : 'View Quote' ?>
                </button>
                <?php endif; ?>

                <!-- VIEW — always shown -->
                <button class="btn-view view-btn"
                  data-id="<?= $p['id'] ?>"
                  data-name="<?= htmlspecialchars($p['name']) ?>"
                  data-code="<?= htmlspecialchars($p['code']) ?>"
                  data-status="<?= project_status_key($p['status']) ?>"
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
      </div><!-- /mp-scroll -->
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

        <!-- ── VAST Sales Quotation Document ── -->
        <div id="vmPaper" style="background:#fff;border:1px solid #ddd;border-radius:8px;overflow:hidden;margin-bottom:14px;">

          <!-- Top accent -->
          <div style="height:6px;background:linear-gradient(90deg,#2e4a45,#4a7c72);"></div>

          <!-- Logo + Company + Title -->
          <div class="vmp-header" style="display:flex;justify-content:space-between;align-items:flex-start;padding:20px 26px 12px;">
            <div style="display:flex;align-items:flex-start;gap:12px;">
              <div style="width:58px;height:58px;background:#1a2e2a;border-radius:6px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <img src="../style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:40px; height:40px; object-fit:contain;">
              </div>
              <div style="margin-top:3px;">
                <div style="font-family:'Syne',sans-serif;font-size:1.25rem;font-weight:800;color:#1a2e2a;line-height:1;">VAST</div>
                <div style="font-size:.63rem;color:#6b7280;margin-top:3px;">B34 L1, Hibiscus St. Ceris 1, Calamba, Laguna</div>
                <div style="font-size:.63rem;color:#6b7280;">+639178850408</div>
                <div style="font-size:.63rem;color:#6b7280;">inquiries@vastsolutionsmanila.com</div>
              </div>
            </div>
            <div style="text-align:right;min-width:200px;">
              <div style="font-family:'Syne',sans-serif;font-size:1rem;font-weight:700;color:#6b7280;letter-spacing:.14em;margin-bottom:10px;">SALES QUOTATION</div>
              <table style="margin-left:auto;border-collapse:collapse;width:100%;">
                <tr>
                  <td style="font-size:.62rem;font-weight:700;color:#6b7280;padding:2px 6px 2px 0;text-align:left;">Date:</td>
                  <td style="font-size:.68rem;font-weight:700;color:#1a2e2a;border-bottom:1px solid #ccc;min-width:120px;" id="vmPaperIssued">—</td>
                </tr>
                <tr>
                  <td style="font-size:.62rem;font-weight:700;color:#6b7280;padding:2px 6px 2px 0;text-align:left;">Reference #:</td>
                  <td style="font-size:.68rem;font-weight:700;color:#1a2e2a;border-bottom:1px solid #ccc;" id="vmPaperQId">—</td>
                </tr>
                <tr>
                  <td style="font-size:.62rem;font-weight:700;color:#6b7280;padding:2px 6px 2px 0;text-align:left;">Valid Until:</td>
                  <td style="font-size:.68rem;font-weight:700;color:#1a2e2a;border-bottom:1px solid #ccc;" id="vmPaperValid">—</td>
                </tr>
              </table>
            </div>
          </div>

          <!-- Bill To / Ship To -->
          <div class="vmp-billship" style="display:grid;grid-template-columns:1fr 1fr;padding:0 26px 14px;border-bottom:1px solid #e5e7eb;">
            <div>
              <div style="font-size:.63rem;font-weight:700;color:#2e4a45;letter-spacing:.08em;border-bottom:1.5px solid #2e4a45;padding-bottom:2px;margin-bottom:7px;display:inline-block;">BILL TO</div>
              <div style="font-size:.73rem;font-weight:600;color:#1a2e2a;" id="vmPaperFor">—</div>
              <div style="font-size:.68rem;color:#6b7280;" id="vmPaperProject">—</div>
            </div>
            <div>
              <div style="font-size:.63rem;font-weight:700;color:#2e4a45;letter-spacing:.08em;border-bottom:1.5px solid #2e4a45;padding-bottom:2px;margin-bottom:7px;display:inline-block;">SHIP TO</div>
              <div style="font-size:.73rem;font-weight:600;color:#1a2e2a;" id="vmShipName">—</div>
              <div style="font-size:.68rem;color:#6b7280;" id="vmShipAddr">—</div>
            </div>
          </div>

          <!-- Line items table -->
          <div class="vmp-items" style="padding:0 26px;">
            <table style="width:100%;border-collapse:collapse;">
              <thead>
                <tr style="background:#2e4a45;">
                  <th style="font-size:.58rem;font-weight:700;letter-spacing:.08em;color:#fff;padding:8px 10px;text-align:left;">DESCRIPTION</th>
                  <th style="font-size:.58rem;font-weight:700;letter-spacing:.08em;color:#fff;padding:8px 10px;text-align:center;width:60px;">QTY</th>
                  <th style="font-size:.58rem;font-weight:700;letter-spacing:.08em;color:#fff;padding:8px 10px;text-align:right;width:110px;">UNIT PRICE</th>
                  <th style="font-size:.58rem;font-weight:700;letter-spacing:.08em;color:#fff;padding:8px 10px;text-align:right;width:110px;">TOTAL</th>
                </tr>
              </thead>
              <tbody id="vmPaperItems"></tbody>
              <tfoot>
                <tr>
                  <td colspan="3" style="text-align:right;padding:9px 10px;font-size:.7rem;font-weight:700;color:#1a2e2a;border-top:1.5px solid #2e4a45;">Quote Total</td>
                  <td style="padding:9px 10px;font-size:.8rem;font-weight:800;color:#1a2e2a;text-align:right;border-top:1.5px solid #2e4a45;background:#e8f0ee;" id="vmPaperTotal">—</td>
                </tr>
              </tfoot>
            </table>
          </div>

          <!-- Terms & Conditions -->
          <div style="padding:16px 26px 10px;">
            <div style="font-size:.68rem;font-weight:700;color:#1a2e2a;margin-bottom:7px;">Terms and Conditions:</div>
            <div style="font-size:.63rem;color:#4b5563;line-height:1.85;">
              <div><strong>1. Terms of payment:</strong></div>
              <div style="padding-left:18px;">50% downpayment is due upon acceptance of quote</div>
              <div style="padding-left:18px;">25% is due upon delivery and installation</div>
              <div style="padding-left:18px;">15% is due after installation</div>
              <div style="padding-left:18px;">10% is due after punchlist and turnover</div>
              <div>2. All prices are VAT EXCLUSIVE.</div>
              <div>3. Cancellation is not allowed once production has started.</div>
              <div>4. Change order will be subject to separate quotation.</div>
              <div>5. Production leadtime is 5-6 weeks.</div>
              <div>6. This quote is only valid for 30 days.</div>
              <div>7. Warranty period of six months is provided for boards and hardware following the turnover.</div>
              <div>8. Warranty does not extend coverage of water damage or normal wear and tear.</div>
            </div>
          </div>

          <!-- Notes (conditionally shown) -->
          <div class="vm-paper-notes" id="vmPaperNotesWrap" style="margin:0 26px 14px;">
            <div class="vm-paper-notes-label">Notes</div>
            <div id="vmPaperNotes">—</div>
          </div>

          <!-- Conforme -->
          <div style="padding:10px 26px 22px;">
            <div style="font-size:.65rem;font-style:italic;color:#6b7280;margin-bottom:28px;">Conforme:</div>
            <div style="width:200px;border-top:1.5px solid #1a2e2a;padding-top:4px;">
              <div style="font-size:.6rem;color:#6b7280;">Signature over Printed Name / Date</div>
            </div>
          </div>

          <!-- Bottom accent -->
          <div style="height:6px;background:linear-gradient(90deg,#2e4a45,#4a7c72);"></div>

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
        <div class="pvm-step-tracker">
          <div style="margin-bottom:8px;"><span id="viewStatusBadge">—</span></div>
          <div class="pvm-steps" id="viewStepTracker"></div>
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
<?= project_status_js() ?>
<script>
// ── MATERIALS DATA ─────────────────────────────────
// Materials + updates come from the database, keyed by project id.
const MATERIALS = <?= json_encode($materialsByProject, JSON_UNESCAPED_UNICODE) ?: '{}' ?>;
const SEED_UPDATES = <?= json_encode($updatesByProject, JSON_UNESCAPED_UNICODE) ?: '{}' ?>;

const CHIPS=['chip-green','chip-blue','chip-yellow','chip-purple','chip-pink'];

function fmtDate(iso){
  if(!iso) return '—';
  return new Date(iso+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
}

function peso(n){ return '₱'+Number(n).toLocaleString('en-PH',{minimumFractionDigits:2}); }

// ══ VERIFY MODAL ═══════════════════════════════════
let currentVerifyId = null;
let currentQuotationPk = 0;

document.querySelectorAll('.verify-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    currentVerifyId = this.dataset.id;
    currentQuotationPk = this.dataset.quotationPk || 0;
    const d = this.dataset;

    // Header
    document.getElementById('vmTitle').textContent = d.name;
    document.getElementById('vmCode').textContent  = d.code;

    // Status badge in detail card
    const stBadge = document.getElementById('vmStatus');
    stBadge.textContent = statusLabel(d.status);
    stBadge.className   = 'badge-status ' + statusClass(d.status);

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
    document.getElementById('vmActivity').innerHTML = activity.map(a=>{
      const cap = (a.image && a.text === '(image)') ? '' : a.text;
      const img = a.image ? `<a href="${a.image}" target="_blank" class="pvm-update-image-link"><img src="${a.image}" alt="Project update photo" class="pvm-update-image"></a>` : '';
      return `
      <div class="vm-activity-item">
        <div class="vm-activity-dot"></div>
        <div>
          ${cap?`<div class="vm-activity-text">${cap}</div>`:''}
          ${img}
          <div class="vm-activity-date">${a.date} · ${a.by}</div>
        </div>
      </div>`;}).join('');

    // Quotation bar
    document.getElementById('vmQId').textContent = d.quoteId;
    const qBadge = document.getElementById('vmQBadge');
    qBadge.textContent = d.quoteStatusLabel;
    qBadge.className   = 'vm-q-badge ' + (d.quoteStatus || 'pending');

    // Quotation paper
    document.getElementById('vmPaperQId').textContent      = d.quoteId;
    document.getElementById('vmPaperFor').textContent      = d.quoteFor;
    document.getElementById('vmPaperProject').textContent  = d.name;
    document.getElementById('vmShipName').textContent      = d.quoteFor;
    document.getElementById('vmShipAddr').textContent      = d.name;
    document.getElementById('vmPaperIssued').textContent   = d.quoteIssued  || '—';
    document.getElementById('vmPaperValid').textContent    = d.quoteValid   || '—';
    document.getElementById('vmPaperNotes').textContent    = d.quoteNotes   || '—';
    document.getElementById('vmPaperTotal').textContent    = d.quoteTotal   || '—';

    const items = JSON.parse(d.quoteItems || '[]');
    document.getElementById('vmPaperItems').innerHTML = items.length
      ? items.map(i=>`<tr>
          <td style="padding:9px 10px;font-size:.72rem;color:#374151;border-bottom:1px solid #f0f0f0;">${i.desc}</td>
          <td style="padding:9px 10px;font-size:.72rem;color:#374151;border-bottom:1px solid #f0f0f0;text-align:center;">${i.qty}</td>
          <td style="padding:9px 10px;font-size:.72rem;color:#374151;border-bottom:1px solid #f0f0f0;text-align:right;">${peso(i.unit)}</td>
          <td style="padding:9px 10px;font-size:.72rem;color:#374151;border-bottom:1px solid #f0f0f0;text-align:right;">${peso(i.amount)}</td>
        </tr>`).join('')
      : '<tr><td colspan="4" style="text-align:center;color:#9ca3af;padding:16px;font-size:.72rem;">No items listed.</td></tr>';

    // Hide paper notes wrap if empty
    document.getElementById('vmPaperNotesWrap').style.display = d.quoteNotes ? '' : 'none';

    // Accept/Reject bar only while the client still needs to decide.
    const awaiting = d.awaiting === '1';
    const actionBar = document.querySelector('#verifyModal .vm-action-bar');
    if (actionBar) actionBar.style.display = awaiting ? '' : 'none';

    new bootstrap.Modal(document.getElementById('verifyModal')).show();
  });
});

function postQuoteDecision(endpoint){
  if(!currentQuotationPk){ alert('No quotation to act on.'); return; }
  const f=document.createElement('form');
  f.method='POST'; f.action=endpoint;
  f.innerHTML='<input type="hidden" name="quotation_id" value="'+currentQuotationPk+'">';
  document.body.appendChild(f); f.submit();
}
document.getElementById('btnAcceptQuote').addEventListener('click', function(){
  postQuoteDecision('accept_quote.php');
});
document.getElementById('btnRejectQuote').addEventListener('click', function(){
  if(confirm('Are you sure you want to reject this quote?')) postQuoteDecision('reject_quote.php');
});

document.getElementById('vmDownloadBtn').addEventListener('click', function(){
  if(currentQuotationPk){ window.open('<?= BASE_URL ?>/download_quote.php?id='+currentQuotationPk,'_blank'); }
  else window.print();
});

// ══ VIEW MODAL ═════════════════════════════════════
// PROJECT_STEPS / getStepIdx / statusLabel / statusClass come from project_status_js().
// An off-track status (On Hold, Rejected) yields stepIdx -1: no step is marked active.
function renderStepTracker(containerId,stepIdx){
  let html='';
  PROJECT_STEPS.forEach(function(label,i){
    if(i>0) html+='<div class="pvm-step-connector'+(i<=stepIdx?' done':'')+'"></div>';
    const cls=i<stepIdx?'done':i===stepIdx?'active':'';
    const dot=i<stepIdx?'<i class="bi bi-check2" style="font-size:0.6rem"></i>':(i+1);
    html+='<div class="pvm-step '+cls+'">'
      +'<div class="pvm-step-dot">'+dot+'</div>'
      +'<div class="pvm-step-label">'+label+'</div>'
      +'</div>';
  });
  document.getElementById(containerId).innerHTML=html;
}

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
    const caption = (u.image && u.text === '(image)') ? '' : u.text;
    const imgHtml = u.image
      ? `<a href="${u.image}" target="_blank" class="pvm-update-image-link"><img src="${u.image}" alt="Project update photo" class="pvm-update-image"></a>`
      : '';
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
        ${caption?`<div class="pvm-update-text">${caption}</div>`:''}
        ${imgHtml}
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
    badge.textContent = statusLabel(d.status);
    badge.className   = 'badge-status ' + statusClass(d.status);

    renderStepTracker('viewStepTracker', getStepIdx(d.status));

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

// Deep link: my_projects.php?view=<projectId> opens that project's detail modal
// (used by notifications so the client lands on the project details here).
(function(){
  const params = new URLSearchParams(location.search);
  const viewId = params.get('view');
  if(!viewId) return;
  const target = document.querySelector('.view-btn[data-id="'+CSS.escape(viewId)+'"]');
  if(target){ target.click(); }
})();

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