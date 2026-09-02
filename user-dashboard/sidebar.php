<?php
if (!isset($active_page)) $active_page = '';
?>

<aside class="sidebar">
  <a class="sidebar-brand" href="admin-dashboard.php">
  <img src="../style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
  Vast Solutions
</a>

  <nav class="sidebar-nav">

    <a href="dashboard.php" class="nav-item <?= $active_page === 'dashboard' ? 'active' : '' ?>">
      <i class="bi bi-grid"></i>
      Dashboard
    </a>

    <a href="request_quote.php" class="nav-item <?= $active_page === 'request_quote' ? 'active' : '' ?>">
      <i class="bi bi-file-earmark-plus"></i>
      Request Quote
    </a>

    <a href="my_projects.php" class="nav-item <?= $active_page === 'my_projects' ? 'active' : '' ?>">
      <i class="bi bi-folder"></i>
      My Projects
    </a>

    <a href="settings.php" class="nav-item <?= $active_page === 'settings' ? 'active' : '' ?>">
      <i class="bi bi-gear"></i>
      Settings
    </a>

  </nav>

  <div class="sidebar-footer">
    <?php if (defined('DEV_MODE') && DEV_MODE): ?>
    <div class="dev-switch">
      <div class="dev-switch-label">
        <i class="bi bi-wrench-adjustable-circle"></i>
        DEV · viewing as <?= htmlspecialchars($_SESSION['role'] ?? 'Client') ?>
      </div>
      <div class="dev-switch-btns">
        <a href="../admin/admin-dashboard.php?dev_user=admin"
           class="dev-switch-btn <?= (($_SESSION['dev_active'] ?? '') === 'admin') ? 'active' : '' ?>">Admin</a>
        <a href="dashboard.php?dev_user=client"
           class="dev-switch-btn <?= (($_SESSION['dev_active'] ?? 'client') === 'client') ? 'active' : '' ?>">Client</a>
      </div>
    </div>
    <?php endif; ?>
    <a href="<?= BASE_URL ?>/logout.php" class="logout-btn">
      <i class="bi bi-box-arrow-left"></i>
      Logout
    </a>
  </div>
</aside>

<!-- Mobile navigation controls (shown on small screens via CSS) -->
<button class="sidebar-toggle" type="button" aria-label="Toggle menu"
        onclick="document.body.classList.toggle('sidebar-open')">
  <i class="bi bi-list"></i>
</button>
<div class="sidebar-overlay" onclick="document.body.classList.remove('sidebar-open')"></div>