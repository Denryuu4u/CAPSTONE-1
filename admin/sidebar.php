<?php
if (!isset($active_page)) $active_page = '';
?>

<aside class="sidebar">
  <a class="sidebar-brand" href="admin-dashboard.php">
  <img src="../style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
  Vast Solutions
</a>

  <nav class="sidebar-nav">
    <?php $rc = function_exists('role_can') ? 'role_can' : fn($k) => true; // links visible per role ?>
    <?php if ($rc('dashboard')): ?>
    <a href="admin-dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('project_requests')): ?>
    <a href="project-requests.php" class="nav-item <?= $active_page === 'project_requests' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-plus"></i>
      <span>Project Requests</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('quotations')): ?>
    <a href="quotations.php" class="nav-item <?= $active_page === 'quotations' ? 'active' : '' ?>">
      <i class="bi bi-file-text"></i>
      <span>Quotations</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('summarization')): ?>
    <a href="summarization.php" class="nav-item <?= $active_page === 'summarization' ? 'active' : '' ?>">
      <i class="bi bi-box-seam"></i>
      <span>Summarization</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('monitoring')): ?>
    <a href="monitoring.php" class="nav-item <?= $active_page === 'monitoring' ? 'active' : '' ?>">
      <i class="bi bi-activity"></i>
      <span>Monitoring</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('customers')): ?>
    <a href="customers.php" class="nav-item <?= $active_page === 'customers' ? 'active' : '' ?>">
      <i class="bi bi-people"></i>
      <span>Customers</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('user_management')): ?>
    <a href="user-management.php" class="nav-item <?= $active_page === 'user_management' ? 'active' : '' ?>">
      <i class="bi bi-person-gear"></i>
      <span>User Management</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('reports')): ?>
    <a href="reports.php" class="nav-item <?= $active_page === 'reports' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart"></i>
      <span>Reports</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('audit_logs')): ?>
    <a href="audit.php" class="nav-item <?= $active_page === 'audit_logs' ? 'active' : '' ?>">
      <i class="bi bi-journal-text"></i>
      <span>Audit Logs</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('settings_self')): ?>
    <a href="settings.php" class="nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i>
      <span>Settings</span>
    </a>
    <?php endif; ?>

    <?php if ($rc('archive')): ?>
    <a href="archive.php" class="nav-item <?= $active_page === 'archive' ? 'active' : '' ?>">
      <i class="bi bi-archive"></i>
      <span>Archives</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <?php if (defined('DEV_MODE') && DEV_MODE && empty($_SESSION['real_login'])): $dv = $_SESSION['dev_active'] ?? 'superadmin'; ?>
    <div class="dev-switch">
      <div class="dev-switch-label">
        <i class="bi bi-wrench-adjustable-circle"></i>
        DEV · viewing as <?= htmlspecialchars($_SESSION['role'] ?? 'Super Admin') ?>
      </div>
      <div class="dev-switch-btns">
        <a href="admin-dashboard.php?dev_user=superadmin"
           class="dev-switch-btn <?= $dv === 'superadmin' ? 'active' : '' ?>">Super</a>
        <a href="admin-dashboard.php?dev_user=admin"
           class="dev-switch-btn <?= $dv === 'admin' ? 'active' : '' ?>">Admin</a>
        <a href="admin-dashboard.php?dev_user=staff"
           class="dev-switch-btn <?= $dv === 'staff' ? 'active' : '' ?>">Staff</a>
        <a href="../user-dashboard/dashboard.php?dev_user=client"
           class="dev-switch-btn <?= $dv === 'client' ? 'active' : '' ?>">Client</a>
      </div>
    </div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
      <i class="bi bi-box-arrow-left"></i>
      <span>Logout</span>
    </a>
  </div>
</aside>

<!-- Mobile navigation controls (shown on small screens via CSS) -->
<button class="sidebar-toggle" type="button" aria-label="Toggle menu"
        onclick="document.body.classList.toggle('sidebar-open')">
  <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" onclick="document.body.classList.remove('sidebar-open')"></div>