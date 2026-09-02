<?php
/**
 * Public, read-only viewer for the Terms & Conditions and Privacy Policy.
 * Linked from sign-up / login and the client portal. No login required.
 *   legal.php?doc=terms   |   legal.php?doc=privacy
 */
require_once __DIR__ . '/includes/legal.php';

$key = ($_GET['doc'] ?? 'terms') === 'privacy' ? 'privacy' : 'terms';
$doc = legal_doc($key);
$company = db()->query("SELECT company_name FROM company_settings WHERE id=1")->fetchColumn() ?: 'Vast Solutions';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($doc['title'] ?? 'Legal') ?> – <?= htmlspecialchars($company) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root { --brand:#0D9676; }
    body { font-family:'Inter',system-ui,sans-serif; background:#f3f4f6; color:#1f2937; margin:0; }
    .legal-wrap { max-width:820px; margin:0 auto; padding:32px 20px 64px; }
    .legal-head { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:18px; }
    .legal-tabs a { display:inline-block; padding:7px 14px; border-radius:999px; text-decoration:none; font-size:.85rem; font-weight:600; color:#374151; background:#fff; border:1px solid #e5e7eb; }
    .legal-tabs a.active { background:var(--brand); color:#fff; border-color:var(--brand); }
    .legal-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:34px 40px; }
    .legal-card h1 { font-family:'Syne',sans-serif; font-weight:800; font-size:1.6rem; margin:0 0 4px; }
    .legal-updated { color:#6b7280; font-size:.82rem; margin-bottom:20px; }
    .legal-h { font-size:1.02rem; font-weight:700; margin:22px 0 6px; color:#111827; }
    .legal-card p { font-size:.92rem; line-height:1.65; margin:0 0 10px; }
    .legal-card ul { margin:0 0 12px; padding-left:22px; }
    .legal-card li { font-size:.92rem; line-height:1.6; margin-bottom:4px; }
    .legal-card ul ul { margin:4px 0 6px; }
    .back-link { color:var(--brand); text-decoration:none; font-size:.85rem; font-weight:600; }
    @media (max-width:576px){ .legal-card{ padding:24px 20px; } }
  </style>
</head>
<body>
  <div class="legal-wrap">
    <div class="legal-head">
      <a href="index.php" class="back-link">&#8592; Back to home</a>
      <div class="legal-tabs">
        <a href="legal.php?doc=terms"   class="<?= $key === 'terms'   ? 'active' : '' ?>">Terms &amp; Conditions</a>
        <a href="legal.php?doc=privacy" class="<?= $key === 'privacy' ? 'active' : '' ?>">Privacy Policy</a>
      </div>
    </div>

    <div class="legal-card">
      <?php if (!$doc): ?>
        <h1>Not found</h1>
        <p>This document is not available.</p>
      <?php else: ?>
        <h1><?= htmlspecialchars($doc['title']) ?></h1>
        <div class="legal-updated">
          <?= htmlspecialchars($company) ?> · Version <?= (int) $doc['version'] ?>
          · Last updated <?= htmlspecialchars(date('F d, Y', strtotime($doc['updated_at']))) ?>
        </div>
        <?= legal_render($doc['body']) ?>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
