<?php
/**
 * Forgot password — step 2. Enter the 6-digit code emailed by
 * forgot_password.php plus a new password. On success the password is updated
 * and the user is sent to the login page (not auto-signed-in).
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

$email  = trim((string) ($_POST['email'] ?? $_GET['email'] ?? ''));
$error  = '';
$notice = '';

// Mail problems handed over from forgot_password.php (escaped when displayed).
if (isset($_GET['mailerr']) && $_GET['mailerr'] !== '') {
    $error = 'Email could not be sent: ' . $_GET['mailerr'];
} elseif (isset($_GET['demo'])) {
    $error = 'Email is not configured on the server (demo mode) — no code was sent. '
           . 'Set BREVO_API_KEY and MAIL_FROM_EMAIL in the app service.';
}

function reset_user(string $email): ?array
{
    if ($email === '') return null;
    $s = db()->prepare("SELECT id, full_name, is_archived, status FROM users WHERE email = ? LIMIT 1");
    $s->execute([$email]);
    return $s->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';
    $u = reset_user($email);

    if (!$u) {
        $error = 'We could not find that account. Start over from the login page.';
    } elseif ((int) $u['is_archived'] === 1 || $u['status'] !== 'Active') {
        $error = 'This account is inactive. Please contact an administrator.';
    } elseif ($action === 'resend') {
        $code = create_otp((int) $u['id'], $email, 'reset');
        $mail = send_otp_email($email, $code, $u['full_name'], 'reset');
        if (empty($mail['ok'])) {
            $error = 'Email could not be sent: ' . ($mail['error'] ?? 'unknown error');
        } elseif (!empty($mail['demo'])) {
            $error = 'Email is not configured on the server (demo mode). '
                   . 'Set BREVO_API_KEY and MAIL_FROM_EMAIL in the app service.';
        } else {
            $notice = 'A new code has been sent to your email.';
        }
    } else { // verify + set new password
        $code    = trim((string) ($_POST['code'] ?? ''));
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');
        if ($code === '' || $new === '' || $confirm === '') {
            $error = 'Enter the code and your new password.';
        } elseif (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New password and confirmation do not match.';
        } elseif (!verify_otp($email, $code, 'reset')) {
            $error = 'That code is invalid or has expired. Request a new one.';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            db()->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, (int) $u['id']]);
            log_audit('Auth', 'Reset password via email code', $email);
            header('Location: login.php?reset=1&email=' . urlencode($email));
            exit;
        }
    }
}

// Editable branding (Settings → Branding & Identity), with safe fallbacks.
$brandName = function_exists('company_name') ? company_name() : 'Vast Solutions';
$brandLogo = function_exists('company_logo_url') ? company_logo_url() : 'style/assets/logo.jpg';
$be = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password – <?= $be($brandName) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style/signup.css" />
  <style>
    .otp-input { letter-spacing: 12px; text-align: center; font-size: 1.4rem; font-weight: 700; }
    .verify-alert { font-size:.82rem; padding:.6rem .8rem; border-radius:8px; margin-bottom:1rem; }
    .verify-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .verify-alert.ok  { background:#f0fdf9; color:#0a7a60; border:1px solid #6ee7d0; }
    .resend-form { display:inline; }
    .resend-btn { background:none;border:none;color:#0D9676;font-size:.82rem;cursor:pointer;padding:0;text-decoration:underline; }
  </style>
</head>
<body>
  <div class="left-panel">
    <a class="brand" href="index.php">
      <img src="<?= $be($brandLogo) ?>" alt="<?= $be($brandName) ?> Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
      <?= $be($brandName) ?>
    </a>
    <h2 class="panel-title">Set a new password</h2>
    <p class="panel-desc">Enter the 6-digit code we emailed you, then choose a new password.</p>
  </div>

  <div class="right-panel">
    <div class="form-box">
      <a href="forgot_password.php" class="back-link">&#8592; Start over</a>
      <h1 class="form-title">Reset password</h1>
      <p class="form-subtitle">Code sent to <strong><?= htmlspecialchars($email) ?></strong></p>

      <?php if ($error): ?><div class="verify-alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($notice): ?><div class="verify-alert ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

      <form action="reset_password.php" method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="action" value="verify">
        <div class="mb-3">
          <label class="form-label">Verification Code</label>
          <input type="text" name="code" class="form-control otp-input" maxlength="6" inputmode="numeric"
                 pattern="[0-9]{6}" placeholder="______" autocomplete="one-time-code" required autofocus />
        </div>
        <div class="mb-3">
          <label class="form-label">New Password</label>
          <input type="password" name="new_password" class="form-control" placeholder="At least 8 characters" minlength="8" required />
        </div>
        <div class="mb-3">
          <label class="form-label">Confirm New Password</label>
          <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" minlength="8" required />
        </div>
        <button type="submit" class="btn-submit">Reset Password</button>
      </form>

      <div class="form-footer">
        Didn't get a code?
        <form action="reset_password.php" method="POST" class="resend-form">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
          <input type="hidden" name="action" value="resend">
          <button type="submit" class="resend-btn">Resend code</button>
        </form>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
