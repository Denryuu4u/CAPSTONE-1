<?php
session_start();
// if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$active_page = 'settings';


// Example: fetch user data from DB
// $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
// $stmt->execute([$_SESSION['user_id']]);
// $user = $stmt->fetch(PDO::FETCH_ASSOC);

// Placeholder
$user = [
  'first_name' => 'John',
  'last_name'  => 'Doe',
  'email'      => 'john@company.com',
  'phone'      => '+1 (555) 123-4567',
  'company'    => 'Doe Interiors',
];
$initials = strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1));

$success_profile  = isset($_GET['profile_saved']);
$success_password = isset($_GET['password_saved']);
$error_password   = isset($_GET['pw_error']) ? urldecode($_GET['pw_error']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Settings – Vast Solutions</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="dashboard.css"/>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    .alert {
      padding: 0.6rem 1rem; border-radius: 8px; font-size: 0.78rem; font-weight: 500; margin-bottom: 0.8rem;
    }
    .alert-success { background: rgba(34,197,94,0.1); color: #16a34a; border: 1px solid rgba(34,197,94,0.25); }
    .alert-error   { background: rgba(239,68,68,0.1);  color: #dc2626; border: 1px solid rgba(239,68,68,0.25); }
  </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main">
  <div class="topbar">
    <a href="dashboard.php">Portal</a>
    <span class="sep">›</span>
    <span>Settings</span>
    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
  </div>

  <div class="page-content">
    <h1 class="page-title">Settings</h1>

    <div class="settings-grid">

      <!-- PROFILE INFO -->
      <div class="settings-card">
        <div class="settings-card-title">Profile Information</div>

        <?php if ($success_profile): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
        <?php endif; ?>

        <div class="avatar-wrap">
          <div class="avatar"><?= $initials ?></div>
          <button class="btn-change-photo" type="button">Change Photo</button>
        </div>

        <form method="POST" action="save_profile.php">
          <div class="form-row mb-2">
            <div class="mb-0">
              <label class="settings-label">First Name</label>
              <input type="text" name="first_name" class="settings-input" value="<?= htmlspecialchars($user['first_name']) ?>" required/>
            </div>
            <div class="mb-0">
              <label class="settings-label">Last Name</label>
              <input type="text" name="last_name" class="settings-input" value="<?= htmlspecialchars($user['last_name']) ?>" required/>
            </div>
          </div>

          <div class="mb-2">
            <label class="settings-label">Email</label>
            <input type="email" name="email" class="settings-input" value="<?= htmlspecialchars($user['email']) ?>" required/>
          </div>
          <div class="mb-2">
            <label class="settings-label">Phone</label>
            <input type="text" name="phone" class="settings-input" value="<?= htmlspecialchars($user['phone']) ?>"/>
          </div>
          <div class="mb-3">
            <label class="settings-label">Company</label>
            <input type="text" name="company" class="settings-input" value="<?= htmlspecialchars($user['company']) ?>"/>
          </div>

          <button type="submit" class="settings-save-btn">Save Changes</button>
        </form>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="settings-card">
        <div class="settings-card-title">Change Password</div>
        <p class="settings-card-sub">Use at least 8 characters. You'll stay signed in after updating.</p>

        <?php if ($success_password): ?>
        <div class="alert alert-success">Password changed successfully.</div>
        <?php elseif ($error_password): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_password) ?></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" id="pwForm">
          <div class="mb-2">
            <label class="settings-label">Current Password</label>
            <div class="settings-input-group">
              <i class="bi bi-lock"></i>
              <input type="password" name="current_password" class="settings-input has-toggle" id="currentPassword" placeholder="Enter current password" required>
              <button type="button" class="settings-pw-toggle" data-target="currentPassword" aria-label="Show password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-2">
            <label class="settings-label">New Password</label>
            <div class="settings-input-group">
              <i class="bi bi-key"></i>
              <input type="password" name="new_password" class="settings-input has-toggle" id="newPassword" placeholder="Enter new password" required minlength="8">
              <button type="button" class="settings-pw-toggle" data-target="newPassword" aria-label="Show password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div class="mb-2">
            <label class="settings-label">Confirm New Password</label>
            <div class="settings-input-group">
              <i class="bi bi-key"></i>
              <input type="password" name="confirm_password" class="settings-input has-toggle" id="confirmPassword" placeholder="Re-enter new password" required>
              <button type="button" class="settings-pw-toggle" data-target="confirmPassword" aria-label="Show password">
                <i class="bi bi-eye"></i>
              </button>
            </div>
          </div>

          <div id="pw-mismatch" style="display:none; font-size:0.72rem; color:#dc2626; margin-bottom:0.6rem;">Passwords do not match.</div>
          <button type="submit" class="settings-save-btn">
            <i class="bi bi-shield-lock"></i>
            <span>Update Password</span>
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
  // Password mismatch guard
  document.getElementById('pwForm').addEventListener('submit', function(e) {
    const msg = document.getElementById('pw-mismatch');
    if (document.getElementById('newPassword').value !== document.getElementById('confirmPassword').value) {
      e.preventDefault();
      msg.style.display = 'block';
    } else {
      msg.style.display = 'none';
    }
  });

  // Show/hide password fields
  document.querySelectorAll('.settings-pw-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var input = document.getElementById(btn.dataset.target);
      var icon = btn.querySelector('i');
      if (!input) return;
      var show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      icon.classList.toggle('bi-eye', !show);
      icon.classList.toggle('bi-eye-slash', show);
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
    });
  });
</script>

</body>
</html>