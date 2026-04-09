<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'my_projects';



// Example: get project by ID from DB
// $id = (int)($_GET['id'] ?? 0);
// $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? AND user_id = ?");
// $stmt->execute([$id, $_SESSION['user_id']]);
// $project = $stmt->fetch(PDO::FETCH_ASSOC);
// if (!$project) { header('Location: my_projects.php'); exit; }

// Placeholder
$project = [
  'name'      => 'Kitchen Cabinets - Unit 4B',
  'category'  => 'Kitchen Cabinets',
  'status'    => 'Quote Received',
  'submitted' => '2026-03-01',
  'notes'     => 'Modern shaker-style, soft-close hinges, matte white finish.',
  'files'     => ['kitchen-layout-v2.pdf', 'material-specs.pdf'],
];
$quotation = [
  'id'          => 'QT-2026-041',
  'status'      => 'Pending',
  'date_issued' => '2026-03-11',
  'valid_until' => '2026-03-25',
  'client'      => 'John Doe',
  'items'       => [
    ['desc'=>'Upper Cabinets (8 units)', 'qty'=>8,  'unit'=>450.00,  'amount'=>3600.00],
    ['desc'=>'Lower Cabinets (6 units)', 'qty'=>6,  'unit'=>620.00,  'amount'=>3720.00],
    ['desc'=>'Island Unit',              'qty'=>1,  'unit'=>2890.00, 'amount'=>2890.00],
    ['desc'=>'Hardware & Accessories',   'qty'=>1,  'unit'=>1230.00, 'amount'=>1230.00],
    ['desc'=>'Installation Labour',      'qty'=>1,  'unit'=>1100.00, 'amount'=>1100.00],
  ],
  'total'       => 12450.00,
  'notes'       => 'Price includes delivery within 30km radius. Installation scheduled for 2 days.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($project['name']) ?> – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main" style="padding-bottom: 70px;">

  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <a href="my_projects.php">Kitchen Cabinets - Unit 4B</a>
  </div>

  <div class="page-content">
    <h1 class="page-title"><?= htmlspecialchars($project['name']) ?></h1>
    <a href="my_projects.php" class="back-link">
      <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"/></svg>
      Back to Projects
    </a>

    <!-- Detail + Activity -->
    <div class="detail-grid">
      <div class="detail-card">
        <div class="detail-card-title">Project Details</div>
        <div class="detail-fields">
          <div>
            <div class="detail-field-label">Category</div>
            <div class="detail-field-value"><?= htmlspecialchars($project['category']) ?></div>
          </div>
          <div>
            <div class="detail-field-label">Status</div>
            <div class="detail-field-value">
              <span class="badge-status quote-received"><?= htmlspecialchars($project['status']) ?></span>
            </div>
          </div>
          <div>
            <div class="detail-field-label">Submitted</div>
            <div class="detail-field-value"><?= $project['submitted'] ?></div>
          </div>
          <div>
            <div class="detail-field-label">Files</div>
            <div class="detail-field-value" style="display:flex; flex-direction:column; gap:0.3rem;">
              <?php foreach ($project['files'] as $f): ?>
              <a href="uploads/<?= htmlspecialchars($f) ?>" class="file-link" target="_blank">
                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                <?= htmlspecialchars($f) ?>
              </a>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="detail-field full">
            <div class="detail-field-label">Notes</div>
            <div class="detail-field-value"><?= htmlspecialchars($project['notes']) ?></div>
          </div>
        </div>
      </div>

      <!-- Activity -->
      <div class="detail-card">
        <div class="detail-card-title">Activity</div>
        <div class="activity-list">
          <div class="activity-item">
            <div class="activity-dot blue"></div>
            <div>
              <div class="activity-text">Quotation sent to client</div>
              <div class="activity-date">2026-03-11 · Vast Solutions</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="activity-dot blue"></div>
            <div>
              <div class="activity-text">Design review completed</div>
              <div class="activity-date">2026-03-05 · Vast Solutions</div>
            </div>
          </div>
          <div class="activity-item">
            <div class="activity-dot blue"></div>
            <div>
              <div class="activity-text">Quote request submitted</div>
              <div class="activity-date">2026-03-01 · John Doe</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quotation bar -->
    <div class="quotation-bar">
      <div class="quotation-bar-left">
        <span class="q-id">Quotation <?= $quotation['id'] ?></span>
        <span class="badge-status pending"><?= $quotation['status'] ?></span>
      </div>
      <a href="download_quote.php?id=<?= $quotation['id'] ?>" class="btn-download">
        <svg viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        Download PDF
      </a>
    </div>

    <!-- Quotation paper -->
    <div class="quotation-paper">
      <div class="q-paper-header">
        <div>
          <div class="q-company-name">VAST SOLUTIONS</div>
          <div class="q-company-sub">Custom Joinery &amp; Fitouts</div>
        </div>
        <div class="q-label-right">
          <div class="q-word">QUOTATION</div>
          <div class="q-number"><?= $quotation['id'] ?></div>
        </div>
      </div>

      <div class="q-meta">
        <div>
          <div class="q-meta-label">Prepared For</div>
          <div class="q-meta-value"><?= htmlspecialchars($quotation['client']) ?></div>
        </div>
        <div style="text-align:right">
          <div class="q-meta-label">Date Issued</div>
          <div class="q-meta-value"><?= $quotation['date_issued'] ?></div>
        </div>
        <div>
          <div class="q-meta-label">Project</div>
          <div class="q-meta-value"><?= htmlspecialchars($project['name']) ?></div>
        </div>
        <div style="text-align:right">
          <div class="q-meta-label">Valid Until</div>
          <div class="q-meta-value"><?= $quotation['valid_until'] ?></div>
        </div>
      </div>

      <table class="q-table">
        <thead>
          <tr>
            <th>Description</th>
            <th>QTY</th>
            <th>Unit Price</th>
            <th>Amount</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quotation['items'] as $item): ?>
          <tr>
            <td><?= htmlspecialchars($item['desc']) ?></td>
            <td><?= $item['qty'] ?></td>
            <td>$<?= number_format($item['unit'], 2) ?></td>
            <td>$<?= number_format($item['amount'], 2) ?></td>
          </tr>
          <?php endforeach; ?>
          <tr class="q-total-row">
            <td colspan="3" style="text-align:right; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.07em;">Total</td>
            <td>$<?= number_format($quotation['total'], 2) ?></td>
          </tr>
        </tbody>
      </table>

      <div class="q-notes">
        <div class="q-notes-label">Notes</div>
        <?= htmlspecialchars($quotation['notes']) ?>
      </div>

      <div class="q-footer-paper">
        Vast Solutions &nbsp;·&nbsp; info@vastsolutions.com &nbsp;·&nbsp; +27 123 456 789
      </div>
    </div>

  </div>
</div>

<!-- ACTION BAR -->
<div class="action-bar">
  <span>Valid until: <?= $quotation['valid_until'] ?></span>
  <div class="action-bar-btns">
    <form method="POST" action="reject_quote.php" style="margin:0">
      <input type="hidden" name="quotation_id" value="<?= $quotation['id'] ?>"/>
      <button type="submit" class="btn-reject">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Reject
      </button>
    </form>
    <form method="POST" action="accept_quote.php" style="margin:0">
      <input type="hidden" name="quotation_id" value="<?= $quotation['id'] ?>"/>
      <button type="submit" class="btn-accept">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Accept Quote
      </button>
    </form>
  </div>
</div>

</body>
</html>