<?php
session_start();

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'dashboard';
$user_name = $_SESSION['full_name'] ?? 'Admin User';

// Mirrors the rows in monitoring.php — same codes, same canonical statuses.
$recent_projects = [
  ['code'=>'PRJ-2026-042', 'customer'=>'Rivera Kitchens',   'status'=>'production',      'target'=>'Mar 15, 2026'],
  ['code'=>'PRJ-2026-041', 'customer'=>'Mendoza Interiors', 'status'=>'approved',        'target'=>'Mar 12, 2026'],
  ['code'=>'PRJ-2026-040', 'customer'=>'Kim Design Studio', 'status'=>'quote_submitted', 'target'=>'Mar 10, 2026'],
  ['code'=>'PRJ-2026-039', 'customer'=>'Park Residences',   'status'=>'completed',       'target'=>'Mar 08, 2026'],
  ['code'=>'PRJ-2026-038', 'customer'=>'Lee Custom Homes',  'status'=>'rejected',        'target'=>'Mar 05, 2026'],
];
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
              <h3 class="stat-number mb-1">24</h3>
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
              <h3 class="stat-number mb-1">8</h3>
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
              <h3 class="stat-number mb-1">12</h3>
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
              <h3 class="stat-number mb-1">7</h3>
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

              <div class="pipeline-bars d-flex gap-2 mb-3">
                <div class="pipeline-line bg-warning rounded-pill flex-fill"></div>
                <div class="pipeline-line bg-primary rounded-pill flex-fill"></div>
                <div class="pipeline-line bg-orange rounded-pill flex-fill"></div>
                <div class="pipeline-line bg-success rounded-pill flex-fill"></div>
              </div>

              <div class="row text-center g-2 pipeline-stats">
                <div class="col-6 col-md-3">
                  <div class="fw-bold">5</div>
                  <div class="text-muted small">Quote Submitted</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="fw-bold">8</div>
                  <div class="text-muted small">Approved</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="fw-bold">12</div>
                  <div class="text-muted small">Production in Progress</div>
                </div>
                <div class="col-6 col-md-3">
                  <div class="fw-bold">7</div>
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