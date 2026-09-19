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

// Broadcast buckets this user receives. Back-office notifications are all
// addressed to target_role='Admin', so every back-office role (Super Admin /
// Admin / Staff) must match that bucket — otherwise a Super Admin's bell is
// empty. Clients only receive 'Client' broadcasts.
$__roleSet = in_array($__role, ['Super Admin', 'Admin', 'Staff'], true)
    ? ['Admin', 'Super Admin', 'Staff']
    : ($__role !== '' ? [$__role] : ['__none__']);
$__ph = implode(',', array_fill(0, count($__roleSet), '?'));

$__items = [];
$__count = 0;
try {
    $stmt = db()->prepare(
        "SELECT * FROM notifications
          WHERE user_id = ? OR (user_id IS NULL AND target_role IN ($__ph))
          ORDER BY is_read ASC, created_at DESC
          LIMIT 12"
    );
    $stmt->execute(array_merge([$__uid], $__roleSet));
    $__items = $stmt->fetchAll();

    $cnt = db()->prepare(
        "SELECT COUNT(*) FROM notifications
          WHERE (user_id = ? OR (user_id IS NULL AND target_role IN ($__ph))) AND is_read = 0"
    );
    $cnt->execute(array_merge([$__uid], $__roleSet));
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
    <div class="notif-list">
    <?php if (empty($__items)): ?>
      <div class="notif-item"><span class="notif-item-body"><span class="notif-item-sub">You're all caught up.</span></span></div>
    <?php else: foreach ($__items as $n):
        $dot = $__sevDot[$n['severity']] ?? '#0D9676';
        $link = $n['link'] ?: $__allLink;
    ?>
    <a href="<?= htmlspecialchars($link) ?>" class="notif-item<?= $n['is_read'] ? ' is-read' : '' ?>" data-id="<?= (int) $n['id'] ?>">
      <span class="notif-dot" style="background:<?= $dot ?>;box-shadow:0 0 0 2px <?= $dot ?>33;"></span>
      <span class="notif-item-body">
        <span class="notif-item-title"><?= htmlspecialchars($n['title']) ?></span>
        <span class="notif-item-sub"><?= htmlspecialchars($n['message'] ?? '') ?></span>
      </span>
    </a>
    <?php endforeach; endif; ?>
    </div>
    <a href="#" class="notif-foot" onclick="markNotifsRead(event)">Mark all as read</a>
  </div>
</div>
<script>
(function () {
  if (window.__notifBound) return;
  window.__notifBound = true;

  var markUrl = location.pathname.indexOf('/admin/') !== -1
    ? 'mark_notifications_read.php'
    : '../admin/mark_notifications_read.php';

  document.addEventListener('click', function (e) {
    document.querySelectorAll('.notif-dropdown.open').forEach(function (d) {
      if (!d.contains(e.target)) d.classList.remove('open');
    });
  });

  function decBadges() {
    document.querySelectorAll('.notif-badge').forEach(function (b) {
      var n = parseInt(b.textContent, 10) - 1;
      if (n > 0) b.textContent = n; else b.remove();
    });
    document.querySelectorAll('.notif-head-count').forEach(function (c) {
      var m = (c.textContent.match(/\d+/) || ['0'])[0];
      var n = Math.max(0, parseInt(m, 10) - 1);
      c.textContent = n + ' unread';
    });
  }

  // Clicking an unread notification: mark it read reliably, THEN follow its link.
  // We intercept the navigation and only leave once the mark request has been
  // sent (with a short safety timeout), so a page reload always shows it read.
  document.addEventListener('click', function (e) {
    var item = e.target.closest ? e.target.closest('.notif-item[data-id]') : null;
    if (!item) return;
    var id   = item.getAttribute('data-id');
    var href = item.getAttribute('href') || '';
    if (!id || item.classList.contains('is-read')) return; // read items navigate normally
    e.preventDefault();

    item.classList.add('is-read');
    decBadges();

    var navigated = false;
    var go = function () {
      if (navigated) return; navigated = true;
      if (href && href !== '#') location.href = href;
    };
    try {
      fetch(markUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id),
        keepalive: true
      }).then(go, go);
    } catch (err) { go(); }
    setTimeout(go, 700); // safety net so navigation never hangs
  });

  window.markNotifsRead = function (ev) {
    ev.preventDefault();
    fetch(markUrl, { method: 'POST' }).then(function () {
      if (window.vsToastFlash) vsToastFlash('All notifications marked as read.');
      location.reload();
    });
  };
})();
</script>
