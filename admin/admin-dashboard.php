<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'dashboard';
require_page($active_page); // role gate (Super Admin / Admin / Staff)
$user_name = $_SESSION['full_name'] ?? 'Admin User';

require_once __DIR__ . '/../includes/helpers.php';
$pdo = db();

// Project counts by status.
$statusCounts = [];
foreach ($pdo->query("SELECT status, COUNT(*) c FROM projects GROUP BY status") as $r) {
    $statusCounts[$r['status']] = (int) $r['c'];
}
$cnt = fn($s) => $statusCounts[$s] ?? 0;

$totalProjects      = array_sum($statusCounts);
$pendingQuotes      = (int) $pdo->query("SELECT COUNT(*) FROM quotations WHERE status IN ('Sent','Accepted')")->fetchColumn();
$inProduction       = $cnt('production');
$completedThisMonth = (int) $pdo->query("SELECT COUNT(*) FROM projects WHERE status='completed' AND MONTH(updated_at)=MONTH(CURDATE()) AND YEAR(updated_at)=YEAR(CURDATE())")->fetchColumn();

// Pipeline buckets.
$pipeQuote    = $cnt('quote_submitted');
$pipeApproved = $cnt('approved');
$pipeProd     = $cnt('production') + $cnt('mockup') + $cnt('delivery') + $cnt('installation')
              + $cnt('quality_check') + $cnt('punchlist') + $cnt('final_approval');
$pipeDone     = $cnt('completed');

// Recent projects.
$recent_projects = [];
foreach ($pdo->query(
    "SELECT p.project_code AS code, c.name AS customer, p.status, p.target_completion
       FROM projects p LEFT JOIN customers c ON c.id = p.customer_id
      ORDER BY p.created_at DESC, p.id DESC LIMIT 6"
) as $r) {
    $recent_projects[] = [
        'code' => $r['code'], 'customer' => $r['customer'] ?? '—', 'status' => $r['status'],
        'target' => $r['target_completion'] ? date('M d, Y', strtotime($r['target_completion'])) : '—',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard – Vast Solutions</title>

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
          <span>Dashboard</span>
        </div>

        <div class="d-flex align-items-center gap-3">

          <?php include __DIR__ . '/../includes/notif_bell.php'; ?>

          <div class="d-flex align-items-center gap-2">
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
    </div>

    <div class="page-content container-fluid py-4 px-4">
      <h1 class="page-title mb-4">Dashboard</h1>

      <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
          <div class="card dashboard-stat h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon-wrap icon-blue">
                  <i class="bi bi-folder"></i>
                </div>
                <span class="text-muted small">↗</span>
              </div>
              <h3 class="stat-number mb-1"><?= $totalProjects ?></h3>
              <div class="stat-label">Active Projects</div>
              <div class="stat-sub text-primary small mt-1">+3 this week</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="card dashboard-stat h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon-wrap icon-amber">
                  <i class="bi bi-clock"></i>
                </div>
                <span class="text-muted small">↗</span>
              </div>
              <h3 class="stat-number mb-1"><?= $pendingQuotes ?></h3>
              <div class="stat-label">Pending Quotations</div>
              <div class="stat-sub text-warning small mt-1">2 urgent</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="card dashboard-stat h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon-wrap icon-orange">
                  <i class="bi bi-tools"></i>
                </div>
                <span class="text-muted small">↗</span>
              </div>
              <h3 class="stat-number mb-1"><?= $inProduction ?></h3>
              <div class="stat-label">In Production</div>
              <div class="stat-sub text-orange small mt-1">On schedule</div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="card dashboard-stat h-100 border-0 shadow-sm">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="stat-icon-wrap icon-green">
                  <i class="bi bi-check-circle"></i>
                </div>
                <span class="text-muted small">↗</span>
              </div>
              <h3 class="stat-number mb-1"><?= $completedThisMonth ?></h3>
              <div class="stat-label">Completed This Month</div>
              <div class="stat-sub text-success small mt-1">+15% vs last month</div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-lg-8">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
              <h6 class="fw-semibold mb-3">Status Pipeline</h6>

              <div class="row text-center g-3 pipeline-stats">
                <div class="col-6 col-md-3">
                  <div class="pipeline-line bg-warning rounded-pill mb-2"></div>
                  <div class="fw-bold"><?= $pipeQuote ?></div>
                  <div class="text-muted small">Quote Submitted</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="pipeline-line bg-primary rounded-pill mb-2"></div>
                  <div class="fw-bold"><?= $pipeApproved ?></div>
                  <div class="text-muted small">Approved</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="pipeline-line bg-orange rounded-pill mb-2"></div>
                  <div class="fw-bold"><?= $pipeProd ?></div>
                  <div class="text-muted small">Production in Progress</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="pipeline-line bg-success rounded-pill mb-2"></div>
                  <div class="fw-bold"><?= $pipeDone ?></div>
                  <div class="text-muted small">Completed</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-4">
          <div class="card border-0 shadow-sm h-100">
            <div class="card-body d-flex flex-column gap-2">
              <a href="project-requests.php?open=create" class="btn btn-success text-start">+ New Project</a>
              <a href="customers.php?open=create" class="btn btn-light border text-start">+ New Customer</a>
              <a href="reports.php" class="btn btn-light border text-start"><i class="bi bi-eye"></i> View Reports</a>
            </div>
          </div>
        </div>
      </div>

      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="fw-semibold mb-0">Recent Projects</h6>
            <a href="monitoring.php" class="small text-decoration-none text-success">View all</a>
          </div>

          <div class="table-responsive">
            <table class="table align-middle mb-0 dashboard-table">
              <thead>
                <tr>
                  <th>PROJECT CODE</th>
                  <th>CUSTOMER</th>
                  <th>STATUS</th>
                  <th>TARGET DATE</th>
                  <th class="text-end">ACTIONS</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent_projects as $p): ?>
                <tr>
                  <td><?= $p['code'] ?></td>
                  <td><?= htmlspecialchars($p['customer']) ?></td>
                  <td><?= project_status_badge($p['status'], 'badge rounded-pill') ?></td>
                  <td><?= htmlspecialchars($p['target']) ?></td>
                  <td class="text-end">
                    <a href="monitoring.php?project=<?= $p['code'] ?>&open=view" class="table-link">View</a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>