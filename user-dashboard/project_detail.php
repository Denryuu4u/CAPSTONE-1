<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

require_once __DIR__ . '/../includes/project_status.php';

$active_page = 'my_projects';



require_once __DIR__ . '/../includes/helpers.php';

$id = (int) ($_GET['id'] ?? 0);

// Project details now live inside My Projects (view modal). Send any direct
// link (dashboard, older notifications, bookmarks) there with the modal open.
header('Location: my_projects.php' . ($id ? '?view=' . $id : ''));
exit;

$stmt = db()->prepare(
    "SELECT p.*, c.name AS customer_name, c.user_id AS client_user_id, r.id AS req_id
       FROM projects p
       JOIN customers c ON c.id = p.customer_id
       LEFT JOIN project_requests r ON r.id = p.request_id
      WHERE p.id = ?"
);
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) { header('Location: my_projects.php'); exit; }

$files = [];
if ($row['req_id']) {
    $fs = db()->prepare("SELECT file_name FROM request_files WHERE request_id = ?");
    $fs->execute([$row['req_id']]);
    $files = array_column($fs->fetchAll(), 'file_name');
}
$project = [
    'name'      => $row['project_name'],
    'category'  => $row['category'] ?? '',
    'status'    => $row['status'],
    'submitted' => date('Y-m-d', strtotime($row['created_at'])),
    'notes'     => $row['description'] ?? '',
    'files'     => $files,
];

$qs = db()->prepare("SELECT * FROM quotations WHERE project_id = ? ORDER BY id DESC LIMIT 1");
$qs->execute([$id]);
$qrow = $qs->fetch();
$statusMap = ['Sent' => 'Pending', 'Accepted' => 'Accepted', 'Approved' => 'Approved', 'Rejected' => 'Rejected'];
$quotation = $qrow ? [
    'pk'          => (int) $qrow['id'],
    'id'          => $qrow['quote_code'],
    'status'      => $statusMap[$qrow['status']] ?? $qrow['status'],
    'raw_status'  => $qrow['status'],
    'date_issued' => $qrow['date_created'] ? date('Y-m-d', strtotime($qrow['date_created'])) : '',
    'valid_until' => $qrow['valid_until']  ? date('Y-m-d', strtotime($qrow['valid_until']))  : '',
    'client'      => $row['customer_name'],
    // Client sees only the final total, not the internal materials.
    'items'       => [['desc' => 'Custom cabinetry works — ' . $row['project_name'], 'qty' => 1, 'unit' => (float) $qrow['total_amount'], 'amount' => (float) $qrow['total_amount']]],
    'total'       => (float) $qrow['total_amount'],
    'notes'       => $qrow['notes'] ?? '',
] : [
    'pk' => 0, 'id' => '—', 'status' => '', 'raw_status' => '', 'date_issued' => '', 'valid_until' => '',
    'client' => $row['customer_name'], 'items' => [], 'total' => 0, 'notes' => '',
];

$acts = db()->prepare("SELECT author_name, update_text, attachment_path, created_at FROM project_updates WHERE project_id = ? ORDER BY created_at DESC, id DESC");
$acts->execute([$id]);
$activity = [];
foreach ($acts as $a) {
    $activity[] = [
        'text'  => $a['update_text'],
        'date'  => date('Y-m-d g:i A', strtotime($a['created_at'])),
        'by'    => $a['author_name'] ?: 'Vast Solutions',
        'image' => $a['attachment_path'] ? '../' . $a['attachment_path'] : null,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($project['name']) ?> – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main" style="padding-bottom: 70px;">

  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <a href="my_projects.php">Kitchen Cabinets - Unit 4B</a>
    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
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
              <?= project_status_badge($project['status'], 'badge-status') ?>
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
          <?php if (empty($activity)): ?>
            <div class="activity-item"><div class="activity-dot blue"></div><div><div class="activity-text">No updates yet.</div></div></div>
          <?php else: foreach ($activity as $a): ?>
          <div class="activity-item">
            <div class="activity-dot blue"></div>
            <div>
              <?php $caption = ($a['image'] && $a['text'] === '(image)') ? '' : $a['text']; ?>
              <?php if ($caption !== ''): ?>
              <div class="activity-text"><?= htmlspecialchars($caption) ?></div>
              <?php endif; ?>
              <?php if (!empty($a['image'])): ?>
              <a href="<?= htmlspecialchars($a['image']) ?>" target="_blank" class="activity-image-link">
                <img src="<?= htmlspecialchars($a['image']) ?>" alt="Project update photo" class="activity-image">
              </a>
              <?php endif; ?>
              <div class="activity-date"><?= htmlspecialchars($a['date']) ?> · <?= htmlspecialchars($a['by']) ?></div>
            </div>
          </div>
          <?php endforeach; endif; ?>
        </div>
      </div>
    </div>

    <!-- Quotation bar -->
    <div class="quotation-bar">
      <div class="quotation-bar-left">
        <span class="q-id">Quotation <?= $quotation['id'] ?></span>
        <span class="badge-status pending"><?= $quotation['status'] ?></span>
      </div>
      <a href="<?= BASE_URL ?>/download_quote.php?id=<?= (int) $quotation['pk'] ?>" target="_blank" class="btn-download">
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

<!-- ACTION BAR — only while an issued quote is awaiting the client's decision -->
<?php if (($quotation['raw_status'] ?? '') === 'Sent'): ?>
<div class="action-bar">
  <span>Valid until: <?= $quotation['valid_until'] ?></span>
  <div class="action-bar-btns">
    <form method="POST" action="reject_quote.php" style="margin:0">
      <input type="hidden" name="quotation_id" value="<?= (int) $quotation['pk'] ?>"/>
      <button type="submit" class="btn-reject">
        <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        Reject
      </button>
    </form>
    <form method="POST" action="accept_quote.php" style="margin:0">
      <input type="hidden" name="quotation_id" value="<?= (int) $quotation['pk'] ?>"/>
      <button type="submit" class="btn-accept">
        <svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>
        Accept Quote
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

</body>
</html>