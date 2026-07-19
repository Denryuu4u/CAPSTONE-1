<?php
if (!isset($active_page)) $active_page = '';
?>

<aside class="sidebar">
  <a class="sidebar-brand" href="admin-dashboard.php">
  <img src="../style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
  Vast Solutions
</a>

  <nav class="sidebar-nav">
    <a href="admin-dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid"></i>
      <span>Dashboard</span>
    </a>

    <a href="project-requests.php" class="nav-item <?= $active_page === 'project_requests' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-plus"></i>
      <span>Project Requests</span>
    </a>

    <a href="quotations.php" class="nav-item <?= $active_page === 'quotations' ? 'active' : '' ?>">
      <i class="bi bi-file-text"></i>
      <span>Quotations</span>
    </a>

    <a href="summarization.php" class="nav-item <?= $active_page === 'summarization' ? 'active' : '' ?>">
      <i class="bi bi-box-seam"></i>
      <span>Summarization</span>
    </a>

    <a href="monitoring.php" class="nav-item <?= $active_page === 'monitoring' ? 'active' : '' ?>">
      <i class="bi bi-activity"></i>
      <span>Monitoring</span>
    </a>

    <a href="customers.php" class="nav-item <?= $active_page === 'customers' ? 'active' : '' ?>">
      <i class="bi bi-people"></i>
      <span>Customers</span>
    </a>

    <a href="user-management.php" class="nav-item <?= $active_page === 'user_management' ? 'active' : '' ?>">
      <i class="bi bi-person-gear"></i>
      <span>User Management</span>
    </a>

    <a href="reports.php" class="nav-item <?= $active_page === 'reports' ? 'active' : '' ?>">
      <i class="bi bi-bar-chart"></i>
      <span>Reports</span>
    </a>

    <a href="audit.php" class="nav-item <?= $active_page === 'audit_logs' ? 'active' : '' ?>">
      <i class="bi bi-journal-text"></i>
      <span>Audit Logs</span>
    </a>

    <a href="settings.php" class="nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i>
      <span>Settings</span>
    </a>
    <a href="archive.php" class="nav-item <?= $active_page === 'archive' ? 'active' : '' ?>">
      <i class="bi bi-archive"></i>
      <span>Archives</span>
    </a>
  </nav>

  <div class="sidebar-footer">
    <a href="/CAPSTONE-1/index.php" class="logout-btn">
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