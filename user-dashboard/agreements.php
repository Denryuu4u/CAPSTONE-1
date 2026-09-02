<?php
/**
 * Client agreement gate. Shown when a client has legal documents to accept —
 * on first sign-up-less visit, or after a Super Admin updates a document.
 * The client must read and accept before returning to the portal.
 */
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../includes/legal.php';

$u = current_user();
// Back-office users never see this — send them home.
if (!$u || ($u['role'] ?? '') !== 'Client') {
    header('Location: ' . (function_exists('role_home') ? role_home() : '../index.php'));
    exit;
}

$pending = legal_pending((int) $u['id']);
if (!$pending) {
    // Nothing to accept — go where they were headed (or the dashboard).
    $next = basename((string) ($_GET['next'] ?? 'dashboard.php')) ?: 'dashboard.php';
    header('Location: ' . $next);
    exit;
}

$next = basename((string) ($_GET['next'] ?? 'dashboard.php')) ?: 'dashboard.php';
$user_name = $u['full_name'] ?? 'there';
$company = db()->query("SELECT company_name FROM company_settings WHERE id=1")->fetchColumn() ?: 'Vast Solutions';
$onlyKeys = array_column($pending, 'doc_key');
$updatedExisting = count($pending) < count(legal_docs()); // some already accepted before → this is an update
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Review our policies – <?= htmlspecialchars($company) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <style>
    :root { --brand:#0D9676; }
    body { font-family:'Inter',system-ui,sans-serif; background:#f3f4f6; color:#1f2937; margin:0; }
    .wrap { max-width:760px; margin:0 auto; padding:32px 18px 60px; }
    .intro h1 { font-family:'Syne',sans-serif; font-weight:800; font-size:1.5rem; margin:0 0 6px; }
    .intro p { color:#4b5563; font-size:.92rem; margin:0 0 20px; }
    .doc { background:#fff; border:1px solid #e5e7eb; border-radius:14px; margin-bottom:16px; overflow:hidden; }
    .doc-head { padding:16px 22px; border-bottom:1px solid #eef0f2; display:flex; align-items:center; justify-content:space-between; gap:10px; }
    .doc-head h2 { font-size:1.05rem; font-weight:700; margin:0; }
    .doc-head .ver { color:#6b7280; font-size:.78rem; }
    .badge-upd { background:#fef3c7; color:#92400e; font-size:.68rem; font-weight:700; padding:3px 8px; border-radius:999px; text-transform:uppercase; letter-spacing:.03em; }
    .doc-body { max-height:320px; overflow-y:auto; padding:18px 22px; }
    .legal-h { font-size:.98rem; font-weight:700; margin:18px 0 6px; color:#111827; }
    .doc-body p { font-size:.9rem; line-height:1.6; margin:0 0 10px; }
    .doc-body ul { margin:0 0 12px; padding-left:20px; }
    .doc-body li { font-size:.9rem; line-height:1.55; margin-bottom:4px; }
    .accept-bar { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:20px 22px; position:sticky; bottom:14px; box-shadow:0 6px 24px rgba(0,0,0,.06); }
    .form-check-label { font-size:.92rem; }
    .btn-accept { background:var(--brand); border:none; color:#fff; font-weight:600; padding:11px 22px; border-radius:10px; font-size:.92rem; }
    .btn-accept:disabled { opacity:.5; cursor:not-allowed; }
    .logout-link { color:#6b7280; font-size:.85rem; text-decoration:none; }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="intro">
      <h1>Hi <?= htmlspecialchars(explode(' ', $user_name)[0]) ?> — please review our policies</h1>
      <p>
        <?php if ($updatedExisting): ?>
          We've updated the document<?= count($pending) > 1 ? 's' : '' ?> below. Please read and accept the latest version to continue using your account.
        <?php else: ?>
          Before you continue, please read and accept the following.
        <?php endif; ?>
      </p>
    </div>

    <form action="accept_agreements.php" method="POST" id="acceptForm">
      <input type="hidden" name="next" value="<?= htmlspecialchars($next, ENT_QUOTES) ?>">

      <?php foreach ($pending as $d): ?>
      <div class="doc">
        <div class="doc-head">
          <h2><?= htmlspecialchars($d['title']) ?></h2>
          <span class="ver">
            Version <?= (int) $d['version'] ?>
            <?php if ((int) $d['version'] > 1): ?><span class="badge-upd ms-1">Updated</span><?php endif; ?>
          </span>
        </div>
        <div class="doc-body"><?= legal_render($d['body']) ?></div>
      </div>
      <?php endforeach; ?>

      <div class="accept-bar">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="agreeChk" name="agree" value="1">
          <label class="form-check-label" for="agreeChk">
            I have read and agree to the
            <?= count($pending) > 1 ? implode(' and ', array_map(fn($d) => htmlspecialchars($d['title']), $pending)) : htmlspecialchars($pending[0]['title']) ?>.
          </label>
        </div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <a href="../logout.php" class="logout-link"><i class="bi bi-box-arrow-left"></i> Sign out instead</a>
          <button type="submit" class="btn-accept" id="acceptBtn" disabled>Accept &amp; Continue</button>
        </div>
      </div>
    </form>
  </div>

  <script>
    const chk = document.getElementById('agreeChk');
    const btn = document.getElementById('acceptBtn');
    chk.addEventListener('change', () => { btn.disabled = !chk.checked; });
  </script>
</body>
</html>
