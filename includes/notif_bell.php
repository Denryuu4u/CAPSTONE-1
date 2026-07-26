<?php
/**
 * Shared delay-notification bell for topbars (design-only sample data).
 * Auto-detects admin vs. customer context from the running script path,
 * and uses a self-contained toggle so it works without Bootstrap's JS.
 */
$__isAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
$__allLink = $__isAdmin ? 'monitoring.php' : 'my_projects.php';
$__items = $__isAdmin
  ? [
      ['t' => 'PRJ-2026-037 · Pantry Cabinets', 's' => 'Overdue — target was Mar 01, 2026 (Garcia Build Co)', 'l' => 'monitoring.php?project=PRJ-2026-037&open=view'],
      ['t' => 'PRJ-2026-041 · Office Cabinets',  's' => 'At risk — only 20% done, target Mar 12, 2026',        'l' => 'monitoring.php?project=PRJ-2026-041&open=view'],
    ]
  : [
      ['t' => 'Reception Desk - Lobby',      's' => 'Production is running behind the Mar 01 target date.',      'l' => 'my_projects.php'],
      ['t' => 'Kitchen Cabinets - Unit 4B',  's' => 'Awaiting your quote decision before work can continue.',     'l' => 'my_projects.php'],
    ];
$__count = count($__items);
?>
<div class="notif-dropdown">
  <button class="notif-bell" type="button" aria-label="Delay notifications"
          onclick="this.parentNode.classList.toggle('open')">
    <i class="bi bi-bell"></i>
    <span class="notif-badge"><?= $__count ?></span>
  </button>
  <div class="notif-menu">
    <div class="notif-head">
      <span>Delay Alerts</span>
      <span class="notif-head-count"><?= $__count ?> delayed</span>
    </div>
    <?php foreach ($__items as $__n): ?>
    <a href="<?= $__n['l'] ?>" class="notif-item">
      <span class="notif-dot"></span>
      <span class="notif-item-body">
        <span class="notif-item-title"><?= htmlspecialchars($__n['t']) ?></span>
        <span class="notif-item-sub"><?= htmlspecialchars($__n['s']) ?></span>
      </span>
    </a>
    <?php endforeach; ?>
    <a href="<?= $__allLink ?>" class="notif-foot">View all in <?= $__isAdmin ? 'Monitoring' : 'My Projects' ?></a>
  </div>
</div>
<script>
(function () {
  if (window.__notifBound) return;
  window.__notifBound = true;
  document.addEventListener('click', function (e) {
    document.querySelectorAll('.notif-dropdown.open').forEach(function (d) {
      if (!d.contains(e.target)) d.classList.remove('open');
    });
  });
})();
</script>
