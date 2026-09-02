<?php
/**
 * Email verification (signup OTP). GET shows the code form; POST verifies or
 * resends. On success the client's account is marked verified and signed in.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

$email  = trim((string) ($_POST['email'] ?? $_GET['email'] ?? ''));
$error  = '';
$notice = '';

// Look up the pending (unverified) client for this email.
function pending_user(string $email): ?array
{
    if ($email === '') return null;
    $s = db()->prepare("SELECT id, full_name, role, email_verified FROM users WHERE email = ? LIMIT 1");
    $s->execute([$email]);
    $u = $s->fetch();
    return $u ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'verify';
    $u = pending_user($email);

    if (!$u) {
        $error = 'We could not find that account. Please sign up again.';
    } elseif ((int) $u['email_verified'] === 1) {
        header('Location: login.php?verified=1'); exit;
    } elseif ($action === 'resend') {
        $code = create_otp((int) $u['id'], $email, 'signup');
        send_otp_email($email, $code, $u['full_name']);
        $notice = 'A new code has been sent to your email.';
    } else { // verify
        $code = trim((string) ($_POST['code'] ?? ''));
        if ($code === '') {
            $error = 'Enter the 6-digit code from your email.';
        } elseif (verify_otp($email, $code, 'signup')) {
            db()->prepare("UPDATE users SET email_verified = 1, verified_at = NOW() WHERE id = ?")
                ->execute([(int) $u['id']]);
            // Sign the client in.
            auth_set_user(['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']]);
            $_SESSION['real_login'] = true;
            log_audit('Auth', 'Email verified + signed in', $email);
            header('Location: ' . role_home($u['role'])); exit;
        } else {
            $error = 'That code is invalid or has expired. Request a new one.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Verify Email – Vast Solutions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style/signup.css" />
  <style>
    .otp-input { letter-spacing: 12px; text-align: center; font-size: 1.4rem; font-weight: 700; }
    .verify-alert { font-size: .82rem; padding: .6rem .8rem; border-radius: 8px; margin-bottom: 1rem; }
    .verify-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
    .verify-alert.ok  { background:#f0fdf9; color:#0a7a60; border:1px solid #6ee7d0; }
    .resend-form { display:inline; }
    .resend-btn { background:none;border:none;color:#0D9676;font-size:.82rem;cursor:pointer;padding:0;text-decoration:underline; }
  </style>
</head>
<body>
  <div class="left-panel">
    <a class="brand" href="index.php">
      <img src="style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
      Vast Solutions
    </a>
    <h2 class="panel-title">Verify your email</h2>
    <p class="panel-desc">We sent a 6-digit code to your email address. Enter it below to activate your account.</p>
  </div>

  <div class="right-panel">
    <div class="form-box">
      <a href="signup.php" class="back-link">&#8592; Back to sign up</a>
      <h1 class="form-title">Enter your code</h1>
      <p class="form-subtitle">Sent to <strong><?= htmlspecialchars($email) ?></strong></p>

      <?php if ($error): ?><div class="verify-alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <?php if ($notice): ?><div class="verify-alert ok"><?= htmlspecialchars($notice) ?></div><?php endif; ?>

      <form action="verify.php" method="POST">
        <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
        <input type="hidden" name="action" value="verify">
        <div class="mb-3">
          <label class="form-label">Verification Code</label>
          <input type="text" name="code" class="form-control otp-input" maxlength="6" inputmode="numeric"
                 pattern="[0-9]{6}" placeholder="______" autocomplete="one-time-code" required autofocus />
        </div>
        <button type="submit" class="btn-submit">Verify &amp; Continue</button>
      </form>

      <div class="form-footer">
        Didn't get a code?
        <form action="verify.php" method="POST" class="resend-form">
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
