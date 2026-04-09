<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'my_projects';

// Example: fetch from DB
// $stmt = $pdo->prepare("SELECT * FROM projects WHERE user_id = ? ORDER BY submitted_at DESC");
// $stmt->execute([$_SESSION['user_id']]);
// $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Placeholder data
$projects = [
  ['id'=>1, 'name'=>'Kitchen Cabinets - Unit 4B',  'category'=>'Kitchen Cabinets',  'status'=>'Quote Received', 'submitted'=>'2026-03-01', 'updated'=>'2 hours ago'],
  ['id'=>2, 'name'=>'Office Built-ins - Floor 3',   'category'=>'Office Built-ins',  'status'=>'Pending Quote',  'submitted'=>'2026-03-10', 'updated'=>'1 day ago'],
  ['id'=>3, 'name'=>'Bathroom Vanity - Residence',  'category'=>'Bathroom Vanity',   'status'=>'In Progress',    'submitted'=>'2026-02-15', 'updated'=>'3 days ago'],
  ['id'=>4, 'name'=>'Custom Shelving - Library',    'category'=>'Custom Furniture',  'status'=>'Completed',      'submitted'=>'2026-01-20', 'updated'=>'1 week ago'],
  ['id'=>5, 'name'=>'Reception Desk - Lobby',       'category'=>'Custom Furniture',  'status'=>'Quote Received', 'submitted'=>'2026-03-08', 'updated'=>'5 hours ago'],
];

function statusClass($s) {
  return match($s) {
    'In Progress'    => 'in-progress',
    'Pending Quote'  => 'pending',
    'Completed'      => 'completed',
    'Quote Received' => 'quote-received',
    default          => ''
  };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Projects – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Projects</span>
  </div>

  <div class="page-content">
    <h1 class="page-title">My Projects</h1>

    <div class="section-card" style="padding: 1.4rem 1.6rem;">
      <div class="table-header-row">
        <div class="section-card-title mb-0">All Projects</div>
        <a href="request_quote.php" class="btn-new">+ New Quote Request</a>
      </div>

      <table class="projects-table">
        <thead>
          <tr>
            <th>Project Name</th>
            <th>Category</th>
            <th>Status</th>
            <th>Submitted</th>
            <th>Last Updated</th>
            <th style="text-align:right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($projects as $p): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars($p['name']) ?></td>
            <td class="muted"><?= htmlspecialchars($p['category']) ?></td>
            <td><span class="badge-status <?= statusClass($p['status']) ?>"><?= htmlspecialchars($p['status']) ?></span></td>
            <td class="muted"><?= $p['submitted'] ?></td>
            <td class="muted"><?= $p['updated'] ?></td>
            <td style="text-align:right">
              <a href="project_detail.php?id=<?= $p['id'] ?>" class="btn-view">
                <svg viewBox="0 0 24 24"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/><circle cx="12" cy="12" r="3"/></svg>
                View
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>