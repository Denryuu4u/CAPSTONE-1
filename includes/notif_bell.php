<?php
/**
 * Shared notification bell for topbars — reads the `notifications` table for
 * the current user (personal + role-broadcast). Badge shows the unread count.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

$__u    = current_user();
$__uid  = $__u['id']   ?? 0;
$__role = $__u['role'] ?? '';
$__isAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;

$__items = [];
$__count = 0;
try {
    $stmt = db()->prepare(
        "SELECT * FROM notifications
          WHERE user_id = :uid OR (user_id IS NULL AND target_role = :role)
          ORDER BY is_read ASC, created_at DESC
          LIMIT 12"
    );
    $stmt->execute([':uid' => $__uid, ':role' => $__role]);
    $__items = $stmt->fetchAll();

    $cnt = db()->prepare(
        "SELECT COUNT(*) FROM notifications
          WHERE (user_id = :uid OR (user_id IS NULL AND target_role = :role)) AND is_read = 0"
    );
    $cnt->execute([':uid' => $__uid, ':role' => $__role]);
    $__count = (int) $cnt->fetchColumn();
} catch (Throwable $e) {
    $__items = [];
}

$__sevDot = ['danger' => '#dc2626', 'warning' => '#d97706', 'info' => '#0D9676'];
$__allLink = $__isAdmin ? 'monitoring.php' : 'my_projects.php';
?>
<div class="notif-dropdown">
  <button class="notif-bell" type="button" aria-label="Notifications"
          onclick="this.parentNode.classList.toggle('open')">
    <i class="bi bi-bell"></i>
    <?php if ($__count > 0): ?><span class="notif-badge"><?= $__count ?></span><?php endif; ?>
  </button>
  <div class="notif-menu">
    <div class="notif-head">
      <span>Notifications</span>
      <span class="notif-head-count"><?= $__count ?> unread</span>
    </div>
    <?php if (empty($__items)): ?>
      <div class="notif-item"><span class="notif-item-body"><span class="notif-item-sub">You're all caught up.</span></span></div>
    <?php else: foreach ($__items as $n):
        $dot = $__sevDot[$n['severity']] ?? '#0D9676';
        $link = $n['link'] ?: $__allLink;
    ?>
    <a href="<?= htmlspecialchars($link) ?>" class="notif-item" style="<?= $n['is_read'] ? 'opacity:.6;' : '' ?>">
      <span class="notif-dot" style="background:<?= $dot ?>;box-shadow:0 0 0 2px <?= $dot ?>33;"></span>
      <span class="notif-item-body">
        <span class="notif-item-title"><?= htmlspecialchars($n['title']) ?></span>
        <span class="notif-item-sub"><?= htmlspecialchars($n['message'] ?? '') ?></span>
      </span>
    </a>
    <?php endforeach; endif; ?>
    <a href="#" class="notif-foot" onclick="markNotifsRead(event)">Mark all as read</a>
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
  window.markNotifsRead = function (ev) {
    ev.preventDefault();
    var base = location.pathname.indexOf('/admin/') !== -1 ? 'mark_notifications_read.php' : '../admin/mark_notifications_read.php';
    fetch(base, { method: 'POST' }).then(function () { location.reload(); });
  };
})();
</script>
