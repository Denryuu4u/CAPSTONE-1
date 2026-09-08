<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

$active_page = 'audit_logs';
require_page($active_page); // role gate (Super Admin only)
$user_name = $_SESSION['full_name'] ?? 'Admin User';

require_once __DIR__ . '/../includes/helpers.php';
$auditLogs = db()->query("SELECT * FROM audit_logs ORDER BY created_at DESC, id DESC LIMIT 200")->fetchAll();

// Filter options built from the actual log rows (not hard-coded names).
$auditUsers = $auditModules = [];
foreach ($auditLogs as $__l) {
    $u = trim((string) ($__l['user_name'] ?? ''));
    $m = trim((string) ($__l['module'] ?? ''));
    if ($u !== '') $auditUsers[$u] = true;
    if ($m !== '') $auditModules[$m] = true;
}
$auditUsers = array_keys($auditUsers);   sort($auditUsers);
$auditModules = array_keys($auditModules); sort($auditModules);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Audit Logs – Vast Solutions</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="admin.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center w-100">

                <div class="d-flex align-items-center gap-2">
                    <a href="#">Portal</a>
                    <span class="sep">›</span>
                    <span>Audit Logs</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
                    <div class="user-avatar-sm">
                        <?= strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="lh-sm">
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($user_name); ?></div>
                        <div class="text-muted" style="font-size: 12px;">Administrator</div>
                    </div>
                </div>

            </div>
        </div>

        <div class="page-content container-fluid py-4 px-4">

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="page-title mb-0">Audit Logs</h1>
            </div>

            <div class="audit-filter-grid">
                <div class="audit-filter-item">
                    <label for="dateFrom">Date From</label>
                    <input type="date" id="dateFrom" class="form-control audit-input">
                </div>

                <div class="audit-filter-item">
                    <label for="dateTo">Date To</label>
                    <input type="date" id="dateTo" class="form-control audit-input">
                </div>

                <div class="audit-filter-item">
                    <label for="userFilter">User</label>
                    <select id="userFilter" class="form-select audit-select">
                        <option value="">All</option>
                        <?php foreach ($auditUsers as $u): ?>
                        <option value="<?= htmlspecialchars($u) ?>"><?= htmlspecialchars($u) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="audit-filter-item">
                    <label for="moduleFilter">Module</label>
                    <select id="moduleFilter" class="form-select audit-select">
                        <option value="">All</option>
                        <?php foreach ($auditModules as $m): ?>
                        <option value="<?= htmlspecialchars($m) ?>"><?= htmlspecialchars($m) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="audit-table-card">
                <div class="table-responsive">
                    <table class="table align-middle mb-0 audit-table">
                        <thead>
                            <tr>
                                <th>Timestamp</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($auditLogs)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">No activity logged yet.</td></tr>
                            <?php else: foreach ($auditLogs as $log):
                                $roleCls = in_array($log['role'], BACKOFFICE_ROLES, true) ? 'badge-admin' : 'badge-customer';
                            ?>
                            <tr data-role="<?= htmlspecialchars($log['role'] ?? '') ?>" data-module="<?= htmlspecialchars($log['module'] ?? '') ?>"
                                data-user="<?= htmlspecialchars($log['user_name'] ?? '') ?>" data-date="<?= substr($log['created_at'], 0, 10) ?>">
                                <td class="audit-timestamp"><?= date('Y-m-d H:i', strtotime($log['created_at'])) ?></td>
                                <td class="audit-user"><?= htmlspecialchars($log['user_name'] ?? 'System') ?></td>
                                <td><span class="audit-badge <?= $roleCls ?>"><?= htmlspecialchars($log['role'] ?? '—') ?></span></td>
                                <td class="audit-action"><?= htmlspecialchars($log['action']) ?></td>
                                <td><span class="audit-badge badge-module"><?= htmlspecialchars($log['module'] ?? '') ?></span></td>
                                <td class="audit-details"><?= htmlspecialchars($log['details'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    (function () {
        var dateFrom = document.getElementById('dateFrom');
        var dateTo   = document.getElementById('dateTo');
        var userSel  = document.getElementById('userFilter');
        var modSel   = document.getElementById('moduleFilter');
        var tbody    = document.querySelector('.audit-table tbody');
        if (!tbody) return;

        var rows = Array.prototype.filter.call(tbody.querySelectorAll('tr'), function (tr) {
            return !tr.querySelector('td[colspan]');
        });
        if (!rows.length) return; // nothing logged yet

        var noRow = document.createElement('tr');
        noRow.innerHTML = '<td colspan="6" class="text-center text-muted py-4">No activity matches these filters.</td>';
        noRow.style.display = 'none';
        tbody.appendChild(noRow);

        function run() {
            var from = dateFrom.value, to = dateTo.value;
            var u = userSel.value, m = modSel.value, visible = 0;
            rows.forEach(function (tr) {
                var d = tr.dataset.date || '';
                var ok = (!from || d >= from) && (!to || d <= to)
                      && (!u || tr.dataset.user === u)
                      && (!m || tr.dataset.module === m);
                tr.style.display = ok ? '' : 'none';
                if (ok) visible++;
            });
            noRow.style.display = visible === 0 ? '' : 'none';
        }
        [dateFrom, dateTo].forEach(function (el) { el.addEventListener('change', run); el.addEventListener('input', run); });
        [userSel, modSel].forEach(function (el) { el.addEventListener('change', run); });
    })();
    </script>
</body>

</html>