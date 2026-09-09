<?php
/**
 * Forgot password — step 1. Enter your email; if an account exists we email a
 * 6-digit reset code and send you to reset_password.php to set a new password.
 * Uses the same OTP infrastructure as signup (purpose = 'reset').
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/mailer.php';

$email  = trim((string) ($_POST['email'] ?? $_GET['email'] ?? ''));
$error  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $s = db()->prepare("SELECT id, full_name, is_archived, status FROM users WHERE email = ? LIMIT 1");
        $s->execute([$email]);
        $u = $s->fetch();

        if (!$u) {
            $error = 'No account found with that email address.';
        } elseif ((int) $u['is_archived'] === 1 || $u['status'] !== 'Active') {
            $error = 'This account is inactive. Please contact an administrator.';
        } else {
            $code = create_otp((int) $u['id'], $email, 'reset');
            $mail = send_otp_email($email, $code, $u['full_name'], 'reset');
            $q = 'reset_password.php?email=' . urlencode($email);
            if (empty($mail['ok'])) {
                $q .= '&mailerr=' . urlencode($mail['error'] ?? 'unknown');
            } elseif (!empty($mail['demo'])) {
                $q .= '&demo=1';
            }
            header('Location: ' . $q);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password – Vast Solutions</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="style/signup.css" />
  <style>
    .verify-alert { font-size:.82rem; padding:.6rem .8rem; border-radius:8px; margin-bottom:1rem; }
    .verify-alert.err { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
  </style>
</head>
<body>
  <div class="left-panel">
    <a class="brand" href="index.php">
      <img src="style/assets/logo.jpg" alt="Vast Solutions Logo" style="width:28px; height:28px; object-fit:contain; margin-right:10px;">
      Vast Solutions
    </a>
    <h2 class="panel-title">Reset your password</h2>
    <p class="panel-desc">Enter the email linked to your account and we'll send you a 6-digit code to reset your password.</p>
  </div>

  <div class="right-panel">
    <div class="form-box">
      <a href="login.php" class="back-link">&#8592; Back to login</a>
      <h1 class="form-title">Forgot password?</h1>
      <p class="form-subtitle">We'll email you a reset code.</p>

      <?php if ($error): ?><div class="verify-alert err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <form action="forgot_password.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" placeholder="you@company.com"
                 value="<?= htmlspecialchars($email) ?>" required autofocus />
        </div>
        <button type="submit" class="btn-submit">Send reset code</button>
      </form>

      <div class="form-footer">
        Remembered it? <a href="login.php">Sign in</a>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
