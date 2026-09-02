<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'dashboard';

$user_name = $_SESSION['full_name'] ?? 'John Doe';

require_once __DIR__ . '/../includes/helpers.php';
$__cid = current_user()['id'] ?? 0;
$__one = function (string $sql) use ($__cid) {
    $st = db()->prepare($sql);
    $st->execute([$__cid]);
    return (int) $st->fetchColumn();
};
$dashActive    = $__one("SELECT COUNT(*) FROM projects p JOIN customers c ON c.id=p.customer_id WHERE c.user_id=? AND p.status NOT IN ('completed','rejected')");
$dashPending   = $__one("SELECT COUNT(*) FROM quotations q JOIN customers c ON c.id=q.customer_id WHERE c.user_id=? AND q.status='Sent'");
$dashCompleted = $__one("SELECT COUNT(*) FROM projects p JOIN customers c ON c.id=p.customer_id WHERE c.user_id=? AND p.status='completed'");
$dashDocs      = $__one("SELECT COUNT(*) FROM request_files f JOIN project_requests r ON r.id=f.request_id JOIN customers c ON c.id=r.customer_id WHERE c.user_id=?");

$__rp = db()->prepare(
    "SELECT p.id, p.project_name, p.status, p.updated_at
       FROM projects p JOIN customers c ON c.id=p.customer_id
      WHERE c.user_id=? ORDER BY p.updated_at DESC, p.id DESC LIMIT 5"
);
$__rp->execute([$__cid]);
$dashRecent = $__rp->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <a href="#">Portal</a>
    <span class="sep">›</span>
    <span>Dashboard</span>
    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
  </div>

  <div class="page-content">
    <h1 class="page-title">Dashboard</h1>

    <!-- STAT CARDS -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon blue">
          <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        </div>
        <div>
          <div class="stat-num"><?= $dashActive ?></div>
          <div class="stat-label">Active Projects</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon amber">
          <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
          <div class="stat-num"><?= $dashPending ?></div>
          <div class="stat-label">Pending Quotes</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
        </div>
        <div>
          <div class="stat-num"><?= $dashCompleted ?></div>
          <div class="stat-label">Completed</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon teal">
          <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
        </div>
        <div>
          <div class="stat-num"><?= $dashDocs ?></div>
          <div class="stat-label">Documents</div>
        </div>
      </div>
    </div>

    <!-- RECENT PROJECTS -->
    <div class="section-card">
      <div class="section-card-title">Recent Projects</div>
      <div class="project-list">
        <?php if (empty($dashRecent)): ?>
        <div class="project-row"><div><div class="project-name text-muted">No projects yet.</div></div></div>
        <?php else: foreach ($dashRecent as $rp): ?>
        <div class="project-row">
          <div>
            <div class="project-name"><a href="my_projects.php?view=<?= (int) $rp['id'] ?>" style="text-decoration:none;color:inherit;"><?= htmlspecialchars($rp['project_name']) ?></a></div>
            <div class="project-time"><?= time_ago($rp['updated_at']) ?></div>
          </div>
          <?= project_status_badge($rp['status'], 'badge-status') ?>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>

  </div><!-- /page-content -->
</div><!-- /main -->

</body>
</html>