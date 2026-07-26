<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'archive';
$user_name = $_SESSION['full_name'] ?? 'Admin User';
$user_initial = strtoupper(substr($user_name, 0, 1));

// ── ARCHIVED USERS (system accounts that have been deactivated/archived)
$archived_users = [
  ['id'=>'USR-008',
   'name'=>'Maria',
   'email'=>'maria@email.com',
   'role'=>'Client',
   'phone'=>'+63 917 234 5678',
   'joined'=>'2024-06-12',
   'archived'=>'2026-02-15',
   'archived_by'=>'Admin User',
   'reason'=>'Account inactive for over 6 months. User requested deactivation.',
   'projects'=>3,
   'avatar'=>'MS'],

  ['id'=>'USR-014',
   'name'=>'Ysabelle',
   'email'=>'ysabelle@rcdev.ph',
   'role'=>'Client',
   'phone'=>'+63 919 876 5432',
   'joined'=>'2024-09-01',
   'archived'=>'2026-01-20',
   'archived_by'=>'Admin User',
   'reason'=>'Duplicate account. User re-registered under a new email.',
   'projects'=>1,
   'avatar'=>'RC'],

  ['id'=>'USR-021',
   'name'=>'Queen',
   'email'=>'queen@outlook.com',
   'role'=>'Client',
   'phone'=>'+63 920 111 2233',
   'joined'=>'2024-11-05',
   'archived'=>'2026-03-01',
   'archived_by'=>'Admin User',
   'reason'=>'Client company dissolved. No further projects expected.',
   'projects'=>5,
   'avatar'=>'JL'],

  ['id'=>'USR-033',
   'name'=>'Aarold',
   'email'=>'aarold@interiors.ph',
   'role'=>'Staff',
   'phone'=>'+63 915 444 7788',
   'joined'=>'2023-03-20',
   'archived'=>'2025-12-10',
   'archived_by'=>'Admin User',
   'reason'=>'Employee resigned. Account archived per offboarding procedure.',
   'projects'=>0,
   'avatar'=>'PR'],

  ['id'=>'USR-040',
   'name'=>'Angel',
   'email'=>'angel@gmail.com',
   'role'=>'Client',
   'phone'=>'+63 918 322 9900',
   'joined'=>'2025-01-10',
   'archived'=>'2026-02-28',
   'archived_by'=>'Admin User',
   'reason'=>'Account flagged for inactivity. User confirmed no future projects.',
   'projects'=>2,
   'avatar'=>'GT'],
];

// ── ARCHIVED CUSTOMERS (business/company client records)
$archived_customers = [
  ['id'=>'CUS-011',
   'name'=>'Meridian Build Corp.',
   'contact'=>'Kevin Uy',
   'email'=>'kevin@meridianbuild.ph',
   'phone'=>'+63 2 8888 1234',
   'address'=>'Ortigas Center, Pasig City',
   'industry'=>'Real Estate',
   'joined'=>'2023-07-15',
   'archived'=>'2026-01-05',
   'archived_by'=>'Admin User',
   'reason'=>'Company acquired. All projects completed and handed over.',
   'total_projects'=>8,
   'total_value'=>'₱842,000.00',
   'avatar'=>'MB'],

  ['id'=>'CUS-019',
   'name'=>'Skyline Interiors',
   'contact'=>'Anna Villanueva',
   'email'=>'anna@skylineinteriors.com',
   'phone'=>'+63 917 500 6677',
   'address'=>'BGC, Taguig City',
   'industry'=>'Interior Design',
   'joined'=>'2024-01-08',
   'archived'=>'2025-11-30',
   'archived_by'=>'Admin User',
   'reason'=>'Business closed. No outstanding balance.',
   'total_projects'=>3,
   'total_value'=>'₱214,500.00',
   'avatar'=>'SI'],

  ['id'=>'CUS-027',
   'name'=>'Prime Hospitality Group',
   'contact'=>'Marco Fontaine',
   'email'=>'m.fontaine@primehg.com',
   'phone'=>'+63 2 7777 3344',
   'address'=>'Makati CBD, Makati City',
   'industry'=>'Hospitality',
   'joined'=>'2023-03-01',
   'archived'=>'2026-03-10',
   'archived_by'=>'Admin User',
   'reason'=>'Long-term contract ended. Customer opted not to renew.',
   'total_projects'=>12,
   'total_value'=>'₱1,380,000.00',
   'avatar'=>'PH'],

  ['id'=>'CUS-035',
   'name'=>'Nova Design Studio',
   'contact'=>'Lia Bautista',
   'email'=>'lia@novadesign.ph',
   'phone'=>'+63 920 888 4455',
   'address'=>'Quezon City',
   'industry'=>'Architecture',
   'joined'=>'2024-05-20',
   'archived'=>'2026-02-01',
   'archived_by'=>'Admin User',
   'reason'=>'Studio relocated abroad. Projects transferred to local partner.',
   'total_projects'=>4,
   'total_value'=>'₱376,200.00',
   'avatar'=>'ND'],
];

function roleClass($r){
  return match($r){
    'Admin'  => 'role-admin',
    'Staff'  => 'role-staff',
    'Client' => 'role-client',
    default  => ''
  };
}
function industryIcon($i){
  return match($i){
    'Real Estate'     => 'bi-building',
    'Interior Design' => 'bi-palette',
    'Hospitality'     => 'bi-stars',
    'Architecture'    => 'bi-rulers',
    default           => 'bi-briefcase'
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Archive – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="admin.css">
  <style>

    /* ══ TOPBAR ═════════════════════════════════════════════════════ */
    .topbar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 1.6rem;
      height: 56px;
      border-bottom: 1px solid #f0f0f0;
      background: #fff;
    }
    .topbar-left {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: .82rem;
      color: #9ca3af;
    }
    .topbar-left a { color: #9ca3af; text-decoration: none; }
    .topbar-left a:hover { color: #0D9676; }
    .topbar-left .sep { color: #d1d5db; }

    /* Top-right user widget */
    .topbar-user {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 5px 10px 5px 6px;
      border-radius: 10px;
      cursor: pointer;
      transition: background .15s;
      border: 1px solid transparent;
    }
    .topbar-user:hover {
      background: #f9fafb;
      border-color: #e5e7eb;
    }
    .topbar-avatar {
      width: 34px; height: 34px;
      border-radius: 50%;
      background: #d1fae5;
      color: #065f46;
      font-family: 'Syne', sans-serif;
      font-size: .72rem;
      font-weight: 700;
      display: flex; align-items: center; justify-content: center;
      flex-shrink: 0;
      border: 2px solid #a7f3d0;
    }
    .topbar-user-info { line-height: 1.25; } 
    .topbar-user-name { font-size: .78rem; font-weight: 700; color: #111827; }
    .topbar-user-role { font-size: .62rem; color: #9ca3af; font-weight: 500; }
    .topbar-chevron   { font-size: .6rem; color: #9ca3af; margin-left: 2px; }

    /* ══ ARCH TAB SWITCHER ══════════════════════════════════════════ */
    .arch-tabs { display: flex; gap: 6px; margin-bottom: 20px;}
    .arch-tab-btn {
      display: inline-flex; align-items: center; gap: 7px;
      padding: 8px 20px; border-radius: 999px;
      font-family: 'Inter', sans-serif; font-size: .8rem; font-weight: 600;
      cursor: pointer; border: 1.5px solid #e5e7eb;
      background: #fff; color: #6b7280; transition: all .18s;
    }
    .arch-tab-btn i { font-size: .85rem; }
    .arch-tab-btn:hover { border-color: #0D9676; color: #0D9676; }
    .arch-tab-btn.active {
      background: #0D9676; border-color: #0D9676; color: #fff;
      box-shadow: 0 2px 8px rgba(13,150,118,.28);
    }
    .arch-tab-count {
      display: inline-flex; align-items: center; justify-content: center;
      width: 20px; height: 20px; border-radius: 50%;
      font-size: .65rem; font-weight: 700;
    }
    .arch-tab-btn.active .arch-tab-count { background: rgba(255,255,255,.25); color: #fff; }
    .arch-tab-btn:not(.active) .arch-tab-count { background: #f3f4f6; color: #374151; }

    /* ══ TOOLBAR ════════════════════════════════════════════════════ */
    .arch-toolbar { display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
    .arch-search-wrap { position: relative; flex: 1; min-width: 200px; }
    .arch-search-wrap i {
      position: absolute; left: 11px; top: 50%;
      transform: translateY(-50%); color: #9ca3af; font-size: .82rem; pointer-events: none;
    }
    .arch-search-input {
      width: 100%; padding: 7px 12px 7px 32px;
      border: 1.5px solid #e5e7eb; border-radius: 999px;
      font-family: 'Inter', sans-serif; font-size: .78rem; color: #374151;
      outline: none; transition: border-color .18s; background: #fff;
    }
    .arch-search-input:focus { border-color: #0D9676; }
    .arch-filter-select {
      padding: 7px 14px; border: 1.5px solid #e5e7eb; border-radius: 999px;
      font-family: 'Inter', sans-serif; font-size: .78rem; color: #374151;
      outline: none; background: #fff; cursor: pointer; transition: border-color .18s;
    }
    .arch-filter-select:focus { border-color: #0D9676; }

    /* ══ STATS CHIPS ════════════════════════════════════════════════ */
    .arch-stats { display: flex; gap: 12px; margin-bottom: 16px; flex-wrap: wrap; }
    .arch-stat-chip {
      display: inline-flex; align-items: center; gap: 7px;
      background: #fff; border: 1px solid #e5e7eb; border-radius: 10px;
      padding: 8px 14px; font-size: .72rem; font-weight: 600; color: #374151;
    }
    .arch-stat-chip span {
      font-family: 'Inter', sans-serif; font-size: .95rem;
      font-weight: 800; color: #0D9676; letter-spacing: -0.03em;
    }

    /* ══ ROLE BADGES ════════════════════════════════════════════════ */
    .role-admin  { background: rgba(139,92,246,.12); color: #7c3aed; font-size:.62rem; font-weight:700; padding:2px 9px; border-radius:999px; display:inline-block; }
    .role-staff  { background: rgba(59,130,246,.12);  color: #2563eb; font-size:.62rem; font-weight:700; padding:2px 9px; border-radius:999px; display:inline-block; }
    .role-client { background: rgba(13,150,118,.12);  color: #0D9676; font-size:.62rem; font-weight:700; padding:2px 9px; border-radius:999px; display:inline-block; }

    /* ══ TABLE ══════════════════════════════════════════════════════ */
    .arch-table { width:100%; border-collapse:collapse; }
    .arch-table thead tr { border-bottom: 1px solid #f0f0f0; }
    .arch-table thead th {
      font-size: .62rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .07em; color: #9ca3af;
      padding: 10px 14px; text-align: left; background: #f8fafc;
    }
    .arch-table thead th:last-child { text-align: right; }
    .arch-table tbody tr { border-bottom: 1px solid #f8f8f8; transition: background .12s; }
    .arch-table tbody tr:hover { background: #f8fdf9; }
    .arch-table tbody tr:last-child { border-bottom: none; }
    .arch-table tbody td { padding: 13px 14px; font-size: .8rem; color: #374151; vertical-align: middle; }
    .arch-table tbody td:last-child { text-align: right; }
    .arch-table .fw-semibold { font-weight: 600; color: #111827; }
    .arch-table .muted { color: #9ca3af; font-size: .75rem; }

    /* Avatar + name cell */
    .tbl-name-cell { display: flex; align-items: center; gap: 10px; }
    .tbl-avatar {
      width: 34px; height: 34px; border-radius: 50%;
      background: #d1fae5; color: #065f46;
      font-family: 'Inter', sans-serif; font-size: .65rem; font-weight: 800;
      display: inline-flex; align-items: center; justify-content: center;
      flex-shrink: 0; border: 2px solid #a7f3d0;
    }
    .tbl-avatar.cust { background: #dbeafe; color: #1d4ed8; border-color: #bfdbfe; }

    /* ══ BUTTONS ════════════════════════════════════════════════════ */
    .btn-arch-view {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: .75rem; font-weight: 600; color: #0D9676;
      background: transparent; border: 1.5px solid #0D9676;
      border-radius: 999px; padding: 5px 14px; cursor: pointer;
      text-decoration: none; transition: background .18s, color .18s, box-shadow .18s;
      font-family: 'Inter', sans-serif;
    }
    .btn-arch-view:hover {
      background: #0D9676; color: #fff;
      box-shadow: 0 2px 8px rgba(13,150,118,.25);
    }
    .btn-arch-view svg { width:13px; height:13px; fill:none; stroke:currentColor; stroke-width:2.2; stroke-linecap:round; stroke-linejoin:round; }

    .btn-arch-restore {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: .75rem; font-weight: 600; color: #6b7280;
      background: transparent; border: 1.5px solid #e5e7eb;
      border-radius: 999px; padding: 5px 14px; cursor: pointer;
      transition: border-color .18s, color .18s;
      font-family: 'Inter', sans-serif;
    }
    .btn-arch-restore:hover { border-color: #9ca3af; color: #374151; }
    .btn-arch-restore i { font-size: .75rem; }

    /* ══ PANEL VISIBILITY ═══════════════════════════════════════════ */
    .arch-panel { display: none; }
    .arch-panel.active { display: block; }

    /* ══ EMPTY STATE ════════════════════════════════════════════════ */
    .arch-empty { text-align: center; padding: 52px 20px; color: #9ca3af; }
    .arch-empty i { font-size: 2.2rem; display: block; margin-bottom: 10px; opacity: .35; }
    .arch-empty p { font-size: .82rem; margin: 0; }

    /* ══ VIEW MODAL ═════════════════════════════════════════════════ */
    #archViewModal .modal-dialog { max-width: 580px; }
    #archViewModal .modal-content { border-radius: 14px; border: none; overflow: hidden; }

    .avm-hero {
      background: #0d1b2a;
      padding: 22px 24px 18px;
      position: relative;
    }
    .avm-hero-close {
      position: absolute; top: 16px; right: 18px;
      background: rgba(255,255,255,.12); border: none; border-radius: 50%;
      width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;
      cursor: pointer; transition: background .18s;
    }
    .avm-hero-close:hover { background: rgba(255,255,255,.22); }
    .avm-hero-close svg { width:14px;height:14px;fill:none;stroke:#fff;stroke-width:2.5;stroke-linecap:round; }

    .avm-profile-row { display: flex; align-items: center; gap: 14px; }
    .avm-big-avatar {
      width: 50px; height: 50px; border-radius: 50%;
      background: #d1fae5; color: #065f46;
      font-family: 'Inter', sans-serif; font-size: .95rem; font-weight: 800;
      display: flex; align-items: center; justify-content: center;
      border: 2px solid rgba(167,243,208,.45); flex-shrink: 0;
    }
    .avm-big-avatar.cust { background: #dbeafe; color: #1d4ed8; border-color: rgba(191,219,254,.45); }

    .avm-label { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: rgba(255,255,255,.4); margin-bottom: 3px; }
    .avm-title { font-family: 'Syne', sans-serif; font-size: 1rem; font-weight: 800; color: #fff; margin: 0 0 2px; }
    .avm-sub   { font-size: .7rem; color: rgba(255,255,255,.5); }
    .avm-badges { display: flex; gap: 7px; margin-top: 11px; flex-wrap: wrap; }

    .avm-pill {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: .62rem; font-weight: 700; padding: 3px 10px; border-radius: 999px;
    }
    .avm-pill-user     { background: rgba(139,92,246,.22); color: #c4b5fd; }
    .avm-pill-client   { background: rgba(13,150,118,.22); color: #6ee7b7; }
    .avm-pill-staff    { background: rgba(59,130,246,.22); color: #93c5fd; }
    .avm-pill-industry { background: rgba(255,255,255,.1); color: rgba(255,255,255,.65); }
    .avm-pill-archived { background: rgba(239,68,68,.18); color: #fca5a5; }

    #archViewModal .modal-body { padding: 0; max-height: 68vh; overflow-y: auto; background: #f4f5f7; }

    .avm-section { padding: 16px 22px 4px; }
    .avm-card {
      background: #fff; border: 1px solid #e5e7eb; border-radius: 12px;
      padding: 18px 20px; margin-bottom: 14px;
    }
    .avm-card-title {
      font-family: 'Syne', sans-serif; font-size: .8rem; font-weight: 700;
      color: #0d1b2a; display: flex; align-items: center; gap: 6px; margin-bottom: 14px;
    }
    .avm-card-title i { color: #0D9676; }

    .avm-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px 22px; }
    @media(max-width:480px){ .avm-grid-2 { grid-template-columns: 1fr; } }
    .avm-full { grid-column: 1 / -1; }
    .avm-field-label { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: #9ca3af; margin-bottom: 3px; }
    .avm-field-value { font-size: .8rem; font-weight: 500; color: #111827; }
    .avm-field-note  { font-size: .75rem; color: #6b7280; line-height: 1.6; }

    .avm-reason-box {
      display: flex; gap: 10px; align-items: flex-start;
      background: rgba(239,68,68,.05); border: 1px solid rgba(239,68,68,.18);
      border-radius: 9px; padding: 11px 14px;
    }
    .avm-reason-box i { color: #ef4444; flex-shrink: 0; margin-top: 1px; }
    .avm-reason-text { font-size: .75rem; color: #7f1d1d; line-height: 1.6; }

    .avm-footer {
      background: #fff; border-top: 1px solid #e5e7eb;
      padding: 12px 22px; display: flex; justify-content: space-between; align-items: center; gap: 10px;
    }
    .avm-footer-hint { font-size: .7rem; color: #9ca3af; }
    .avm-footer-btns { display: flex; gap: 8px; }

    .btn-restore-lg {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .8rem; font-weight: 600; color: #0D9676;
      background: #f0fdf9; border: 1.5px solid #0D9676; border-radius: 999px;
      padding: 7px 18px; cursor: pointer; font-family: 'Inter', sans-serif;
      transition: background .18s, color .18s;
    }
    .btn-restore-lg:hover { background: #0D9676; color: #fff; }

    .btn-close-lg {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: .8rem; font-weight: 600; color: #6b7280;
      background: #fff; border: 1.5px solid #e5e7eb; border-radius: 999px;
      padding: 7px 18px; cursor: pointer; font-family: 'Inter', sans-serif;
      transition: border-color .18s, color .18s;
    }
    .btn-close-lg:hover { border-color: #9ca3af; color: #374151; }

    /* ══ RESPONSIVE ══════════════════════════════════════════════ */
    @media (max-width: 768px) {
      .arch-tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
      .arch-tab-btn { flex-shrink: 0; white-space: nowrap; }
      .arch-toolbar { flex-direction: column; align-items: stretch; }
      .arch-search-wrap { width: 100%; }
      .arch-filter-select { width: 100%; }
      .arch-stats { flex-wrap: wrap; }
      /* Wide tables scroll sideways instead of overflowing the page */
      .arch-table { display: block; overflow-x: auto; white-space: nowrap; }
      .avm-footer { flex-direction: column; align-items: stretch; }
      .avm-footer-btns { justify-content: stretch; }
      .avm-footer-btns .btn-restore-lg, .avm-footer-btns .btn-close-lg { flex: 1; justify-content: center; }
    }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">

  <!-- ── TOPBAR ── -->
  <div class="topbar">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div class="d-flex align-items-center gap-2">
                <a href="#">Portal</a><span class="sep">›</span><span>Archives</span>
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
                <h1 class="page-title mb-0">Archives</h1>
            </div>

    <!-- Tabs -->
    <div class="arch-tabs">
      <button class="arch-tab-btn active" data-target="panel-users">
        <i class="bi bi-person-x"></i>
        Archived Users
        <span class="arch-tab-count"><?= count($archived_users) ?></span>
      </button>
      <button class="arch-tab-btn" data-target="panel-customers">
        <i class="bi bi-building-x"></i>
        Archived Customers
        <span class="arch-tab-count"><?= count($archived_customers) ?></span>
      </button>
    </div>

    <!-- ══ PANEL: ARCHIVED USERS ══════════════════════════════════ -->
    <div class="arch-panel active" id="panel-users">
      <div class="section-card" style="padding:1.4rem 1.6rem;">

        <div class="table-header-row" style="margin-bottom:16px;">
          <div class="section-card-title mb-0">Archived Users</div>
        </div>

        <div class="arch-stats">
          <?php
            $uClients = count(array_filter($archived_users, fn($u)=>$u['role']==='Client'));
            $uStaff   = count(array_filter($archived_users, fn($u)=>$u['role']==='Staff'));
          ?>
          <div class="arch-stat-chip"><span><?= count($archived_users) ?></span> Total</div>
          <div class="arch-stat-chip"><span><?= $uClients ?></span> Clients</div>
          <div class="arch-stat-chip"><span><?= $uStaff ?></span> Staff</div>
        </div>

        <div class="arch-toolbar">
          <div class="arch-search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="arch-search-input" id="userSearchInput" placeholder="Search archived users…">
          </div>
          <select class="arch-filter-select" id="userRoleFilter">
            <option value="">All Roles</option>
            <option value="Client">Client</option>
            <option value="Staff">Staff</option>
            <option value="Admin">Admin</option>
          </select>
        </div>

        <table class="arch-table" id="userArchTable">
          <thead>
            <tr>
              <th>User</th>
              <th>Role</th>
              <th>Email</th>
              <th>Projects</th>
              <th>Date Archived</th>
              <th>Archived By</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($archived_users as $u): ?>
            <tr data-name="<?= htmlspecialchars(strtolower($u['name'].' '.$u['email'])) ?>"
                data-role="<?= $u['role'] ?>">
              <td>
                <div class="tbl-name-cell">
                  <div class="tbl-avatar"><?= $u['avatar'] ?></div>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($u['name']) ?></div>
                    <div class="muted"><?= $u['id'] ?></div>
                  </div>
                </div>
              </td>
              <td><span class="<?= roleClass($u['role']) ?>"><?= $u['role'] ?></span></td>
              <td class="muted"><?= htmlspecialchars($u['email']) ?></td>
              <td style="font-size:.8rem;font-weight:600;color:#374151;"><?= $u['projects'] ?></td>
              <td class="muted"><?= $u['archived'] ?></td>
              <td class="muted"><?= htmlspecialchars($u['archived_by']) ?></td>
              <td>
                <div style="display:inline-flex;gap:6px;align-items:center;">
                  <button class="btn-arch-restore arch-restore-btn"
                    data-name="<?= htmlspecialchars($u['name']) ?>">
                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                  </button>
                  <button class="btn-arch-view arch-user-view-btn"
                    data-id="<?= $u['id'] ?>"
                    data-name="<?= htmlspecialchars($u['name']) ?>"
                    data-email="<?= htmlspecialchars($u['email']) ?>"
                    data-role="<?= $u['role'] ?>"
                    data-phone="<?= htmlspecialchars($u['phone']) ?>"
                    data-joined="<?= $u['joined'] ?>"
                    data-archived="<?= $u['archived'] ?>"
                    data-archived-by="<?= htmlspecialchars($u['archived_by']) ?>"
                    data-reason="<?= htmlspecialchars($u['reason']) ?>"
                    data-projects="<?= $u['projects'] ?>"
                    data-avatar="<?= $u['avatar'] ?>">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="arch-empty" id="userEmptyState" style="display:none;">
          <i class="bi bi-person-x"></i>
          <p>No archived users match your search.</p>
        </div>
      </div>
    </div><!-- /panel-users -->


    <!-- ══ PANEL: ARCHIVED CUSTOMERS ═════════════════════════════ -->
    <div class="arch-panel" id="panel-customers">
      <div class="section-card" style="padding:1.4rem 1.6rem;">

        <div class="table-header-row" style="margin-bottom:16px;">
          <div class="section-card-title mb-0">Archived Customers</div>
        </div>

        <div class="arch-stats">
          <?php
            $totalVal  = array_sum(array_map(fn($c)=>(float)str_replace(['₱',','],'',$c['total_value']),$archived_customers));
            $totalProj = array_sum(array_column($archived_customers,'total_projects'));
          ?>
          <div class="arch-stat-chip"><span><?= count($archived_customers) ?></span> Customers</div>
          <div class="arch-stat-chip"><span><?= $totalProj ?></span> Total Projects</div>
          <div class="arch-stat-chip"><span>₱<?= number_format($totalVal,0) ?></span> Combined Value</div>
        </div>

        <div class="arch-toolbar">
          <div class="arch-search-wrap">
            <i class="bi bi-search"></i>
            <input type="text" class="arch-search-input" id="custSearchInput" placeholder="Search archived customers…">
          </div>
          <select class="arch-filter-select" id="custIndustryFilter">
            <option value="">All Industries</option>
            <option value="Real Estate">Real Estate</option>
            <option value="Interior Design">Interior Design</option>
            <option value="Hospitality">Hospitality</option>
            <option value="Architecture">Architecture</option>
          </select>
        </div>

        <table class="arch-table" id="custArchTable">
          <thead>
            <tr>
              <th>Company</th>
              <th>Contact Person</th>
              <th>Industry</th>
              <th>Projects</th>
              <th>Total Value</th>
              <th>Date Archived</th>
              <th style="text-align:right">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($archived_customers as $c): ?>
            <tr data-name="<?= htmlspecialchars(strtolower($c['name'].' '.$c['contact'].' '.$c['email'])) ?>"
                data-industry="<?= $c['industry'] ?>">
              <td>
                <div class="tbl-name-cell">
                  <div class="tbl-avatar cust"><?= $c['avatar'] ?></div>
                  <div>
                    <div class="fw-semibold"><?= htmlspecialchars($c['name']) ?></div>
                    <div class="muted"><?= $c['id'] ?></div>
                  </div>
                </div>
              </td>
              <td>
                <div style="font-size:.8rem;font-weight:500;color:#374151;"><?= htmlspecialchars($c['contact']) ?></div>
                <div class="muted"><?= htmlspecialchars($c['email']) ?></div>
              </td>
              <td class="muted">
                <i class="bi <?= industryIcon($c['industry']) ?>" style="margin-right:4px;color:#9ca3af;"></i>
                <?= $c['industry'] ?>
              </td>
              <td style="font-size:.8rem;font-weight:600;color:#374151;"><?= $c['total_projects'] ?></td>
              <td style="font-size:.8rem;font-weight:600;color:#111827;"><?= $c['total_value'] ?></td>
              <td class="muted"><?= $c['archived'] ?></td>
              <td>
                <div style="display:inline-flex;gap:6px;align-items:center;">
                  <button class="btn-arch-restore arch-restore-btn"
                    data-name="<?= htmlspecialchars($c['name']) ?>">
                    <i class="bi bi-arrow-counterclockwise"></i> Restore
                  </button>
                  <button class="btn-arch-view arch-cust-view-btn"
                    data-id="<?= $c['id'] ?>"
                    data-name="<?= htmlspecialchars($c['name']) ?>"
                    data-contact="<?= htmlspecialchars($c['contact']) ?>"
                    data-email="<?= htmlspecialchars($c['email']) ?>"
                    data-phone="<?= htmlspecialchars($c['phone']) ?>"
                    data-address="<?= htmlspecialchars($c['address']) ?>"
                    data-industry="<?= htmlspecialchars($c['industry']) ?>"
                    data-joined="<?= $c['joined'] ?>"
                    data-archived="<?= $c['archived'] ?>"
                    data-archived-by="<?= htmlspecialchars($c['archived_by']) ?>"
                    data-reason="<?= htmlspecialchars($c['reason']) ?>"
                    data-total-projects="<?= $c['total_projects'] ?>"
                    data-total-value="<?= htmlspecialchars($c['total_value']) ?>"
                    data-avatar="<?= $c['avatar'] ?>">
                    <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                    View
                  </button>
                </div>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <div class="arch-empty" id="custEmptyState" style="display:none;">
          <i class="bi bi-building-x"></i>
          <p>No archived customers match your search.</p>
        </div>
      </div>
    </div><!-- /panel-customers -->

  </div><!-- /page-content -->
</div><!-- /main -->


<!-- ══ ARCHIVE VIEW MODAL ════════════════════════════════════════════ -->
<div class="modal fade" id="archViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="avm-hero">
        <button class="avm-hero-close" data-bs-dismiss="modal">
          <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
        <div class="avm-profile-row">
          <div class="avm-big-avatar" id="avmBigAvatar">—</div>
          <div>
            <div class="avm-label" id="avmLabel">ARCHIVED PROFILE</div>
            <div class="avm-title" id="avmTitle">—</div>
            <div class="avm-sub"   id="avmSub">—</div>
          </div>
        </div>
        <div class="avm-badges" id="avmBadges"></div>
      </div>

      <div class="modal-body">
        <div class="avm-section">

          <div class="avm-card">
            <div class="avm-card-title"><i class="bi bi-person-lines-fill"></i> Profile Information</div>
            <div class="avm-grid-2" id="avmInfoGrid"></div>
          </div>

          <div class="avm-card">
            <div class="avm-card-title"><i class="bi bi-archive-fill"></i> Archive Details</div>
            <div class="avm-grid-2" id="avmArchGrid"></div>
          </div>

          <div class="avm-card">
            <div class="avm-card-title">
              <i class="bi bi-exclamation-triangle-fill" style="color:#ef4444;"></i> Reason for Archiving
            </div>
            <div class="avm-reason-box">
              <i class="bi bi-info-circle-fill"></i>
              <div class="avm-reason-text" id="avmReasonText">—</div>
            </div>
          </div>

        </div>
      </div>

      <div class="avm-footer">
        <span class="avm-footer-hint">Archived records are read-only.</span>
        <div class="avm-footer-btns">
          <button class="btn-close-lg" data-bs-dismiss="modal">
            <i class="bi bi-x-circle"></i> Close
          </button>
          <button class="btn-restore-lg" id="avmRestoreBtn">
            <i class="bi bi-arrow-counterclockwise"></i> Restore
          </button>
        </div>
      </div>

    </div>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── TAB SWITCHER ────────────────────────────────────────────────────
document.querySelectorAll('.arch-tab-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    document.querySelectorAll('.arch-tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.arch-panel').forEach(p => p.classList.remove('active'));
    this.classList.add('active');
    document.getElementById(this.dataset.target).classList.add('active');
  });
});

// ── TABLE FILTERING ──────────────────────────────────────────────────
function filterTable(tableId, searchId, filterId, filterAttr, emptyId){
  const tbl    = document.getElementById(tableId);
  const search = document.getElementById(searchId);
  const filter = document.getElementById(filterId);
  const empty  = document.getElementById(emptyId);
  function run(){
    const q  = search.value.toLowerCase();
    const fv = filter.value;
    let vis  = 0;
    tbl.querySelectorAll('tbody tr').forEach(row => {
      const ok = row.dataset.name.includes(q) && (!fv || row.dataset[filterAttr] === fv);
      row.style.display = ok ? '' : 'none';
      if(ok) vis++;
    });
    empty.style.display = vis ? 'none' : '';
  }
  search.addEventListener('input', run);
  filter.addEventListener('change', run);
}
filterTable('userArchTable','userSearchInput','userRoleFilter','role','userEmptyState');
filterTable('custArchTable','custSearchInput','custIndustryFilter','industry','custEmptyState');

// ── HELPERS ──────────────────────────────────────────────────────────
function fmtDate(iso){
  if(!iso) return '—';
  return new Date(iso+'T00:00:00').toLocaleDateString('en-PH',{year:'numeric',month:'long',day:'numeric'});
}
function field(label, val){
  return `<div>
    <div class="avm-field-label">${label}</div>
    <div class="avm-field-value">${val||'—'}</div>
  </div>`;
}
function fieldFull(label, val){
  return `<div class="avm-full">
    <div class="avm-field-label">${label}</div>
    <div class="avm-field-note">${val||'—'}</div>
  </div>`;
}

// ── RESTORE (row buttons) ────────────────────────────────────────────
document.querySelectorAll('.arch-restore-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const name = this.dataset.name;
    if(confirm(`Restore "${name}"? They will be moved back to active records.`)){
      alert(`"${name}" has been restored.`);
    }
  });
});

// ── MODAL OPEN ───────────────────────────────────────────────────────
let currentName = '';

document.querySelectorAll('.arch-user-view-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const d = this.dataset;
    currentName = d.name;

    // Hero
    const av = document.getElementById('avmBigAvatar');
    av.textContent = d.avatar; av.className = 'avm-big-avatar';
    document.getElementById('avmLabel').textContent = 'ARCHIVED USER';
    document.getElementById('avmTitle').textContent = d.name;
    document.getElementById('avmSub').textContent   = d.email;

    // Badges
    const roleCls = {'Admin':'avm-pill-user','Staff':'avm-pill-staff','Client':'avm-pill-client'}[d.role]||'avm-pill-client';
    document.getElementById('avmBadges').innerHTML =
      `<span class="avm-pill ${roleCls}">${d.role}</span>` +
      `<span class="avm-pill avm-pill-archived"><i class="bi bi-archive" style="font-size:.58rem;margin-right:3px;"></i>Archived</span>`;

    // Info
    document.getElementById('avmInfoGrid').innerHTML =
      field('User ID',     d.id) +
      field('Role',        d.role) +
      field('Phone',       d.phone) +
      field('Projects',    d.projects) +
      field('Date Joined', fmtDate(d.joined)) +
      field('Email',       d.email);

    // Archive
    document.getElementById('avmArchGrid').innerHTML =
      field('Date Archived', fmtDate(d.archived)) +
      field('Archived By',   d.archivedBy);

    document.getElementById('avmReasonText').textContent = d.reason || 'No reason provided.';
    new bootstrap.Modal(document.getElementById('archViewModal')).show();
  });
});

document.querySelectorAll('.arch-cust-view-btn').forEach(btn => {
  btn.addEventListener('click', function(){
    const d = this.dataset;
    currentName = d.name;

    // Hero
    const av = document.getElementById('avmBigAvatar');
    av.textContent = d.avatar; av.className = 'avm-big-avatar cust';
    document.getElementById('avmLabel').textContent = 'ARCHIVED CUSTOMER';
    document.getElementById('avmTitle').textContent = d.name;
    document.getElementById('avmSub').textContent   = d.contact + ' · ' + d.email;

    // Badges
    document.getElementById('avmBadges').innerHTML =
      `<span class="avm-pill avm-pill-industry">${d.industry}</span>` +
      `<span class="avm-pill avm-pill-archived"><i class="bi bi-archive" style="font-size:.58rem;margin-right:3px;"></i>Archived</span>`;

    // Info
    document.getElementById('avmInfoGrid').innerHTML =
      field('Customer ID',    d.id) +
      field('Industry',       d.industry) +
      field('Contact Person', d.contact) +
      field('Phone',          d.phone) +
      field('Total Projects', d.totalProjects) +
      field('Total Value',    d.totalValue) +
      field('Member Since',   fmtDate(d.joined)) +
      fieldFull('Address',    d.address);

    // Archive
    document.getElementById('avmArchGrid').innerHTML =
      field('Date Archived', fmtDate(d.archived)) +
      field('Archived By',   d.archivedBy);

    document.getElementById('avmReasonText').textContent = d.reason || 'No reason provided.';
    new bootstrap.Modal(document.getElementById('archViewModal')).show();
  });
});

// Modal restore
document.getElementById('avmRestoreBtn').addEventListener('click', function(){
  if(confirm(`Restore "${currentName}"? They will be moved back to active records.`)){
    bootstrap.Modal.getInstance(document.getElementById('archViewModal')).hide();
    alert(`"${currentName}" has been restored.`);
  }
});
</script>

</body>
</html>