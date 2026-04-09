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
  </div>

  <div class="page-content">
    <h1 class="page-title">Settings</h1>

    <div class="settings-grid">

      <!-- PROFILE INFO -->
      <div class="section-card" style="padding: 1.5rem 1.6rem;">
        <div class="section-card-title">Profile Information</div>

        <?php if ($success_profile): ?>
        <div class="alert alert-success">Profile updated successfully.</div>
        <?php endif; ?>

        <div class="avatar-wrap">
          <div class="avatar"><?= $initials ?></div>
          <button class="btn-change-photo" type="button">Change Photo</button>
        </div>

        <form method="POST" action="save_profile.php">
          <div class="form-row mb-3">
            <div class="form-group mb-0">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required/>
            </div>
            <div class="form-group mb-0">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required/>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required/>
          </div>
          <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>"/>
          </div>
          <div class="form-group">
            <label class="form-label">Company</label>
            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($user['company']) ?>"/>
          </div>

          <button type="submit" class="btn-save">Save Changes</button>
        </form>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="section-card" style="padding: 1.5rem 1.6rem;">
        <div class="section-card-title">Change Password</div>

        <?php if ($success_password): ?>
        <div class="alert alert-success">Password changed successfully.</div>
        <?php elseif ($error_password): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error_password) ?></div>
        <?php endif; ?>

        <form method="POST" action="change_password.php" id="pwForm">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-control" required/>
          </div>
          <div class="form-group">
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" id="newPw" class="form-control" required minlength="8"/>
          </div>
          <div class="form-group">
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirmPw" class="form-control" required/>
          </div>
          <div id="pw-mismatch" style="display:none; font-size:0.75rem; color:#dc2626; margin-bottom:0.6rem;">Passwords do not match.</div>
          <button type="submit" class="btn-save">Save Changes</button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
  document.getElementById('pwForm').addEventListener('submit', function(e) {
    const msg = document.getElementById('pw-mismatch');
    if (document.getElementById('newPw').value !== document.getElementById('confirmPw').value) {
      e.preventDefault();
      msg.style.display = 'block';
    } else {
      msg.style.display = 'none';
    }
  });
</script>

</body>
</html>