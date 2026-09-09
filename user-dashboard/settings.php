<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

$active_page = 'settings';

// Real profile for the signed-in client (session only carries id/full_name/role).
$uid = current_user()['id'] ?? 0;
$user = ['full_name' => '', 'email' => '', 'phone' => '', 'avatar' => '', 'location' => ''];
$accountName = '';
$address = '';
if ($uid) {
    $stmt = db()->prepare("SELECT full_name, email, phone, avatar, location FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    if ($row) $user = array_merge($user, $row);

    $c = db()->prepare("SELECT name, address FROM customers WHERE user_id = ? ORDER BY id LIMIT 1");
    $c->execute([$uid]);
    $cust = $c->fetch() ?: [];
    $accountName = (string) ($cust['name'] ?? '');
    // The address shown on the quotation lives on the customer record; fall back
    // to the user's own saved location for clients without a customer row yet.
    $address = (string) ($cust['address'] ?? '') ?: (string) ($user['location'] ?? '');
}
$initials  = strtoupper(mb_substr(trim($user['full_name']) ?: 'U', 0, 1));
$avatarUrl = $user['avatar'] ? (BASE_URL . '/' . $user['avatar']) : '';
$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES);
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
    .avatar { overflow: hidden; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .settings-readonly { background:#f3f4f6; color:#6b7280; cursor:not-allowed; }
    /* Highlight fields the client was sent here to complete (from Request Quote). */
    .field-highlight {
      border-color: #f59e0b !important;
      box-shadow: 0 0 0 3px rgba(245,158,11,.28) !important;
      background: #fffbeb;
      animation: fieldPulse 1s ease-in-out 2;
    }
    @keyframes fieldPulse {
      0%,100% { box-shadow: 0 0 0 3px rgba(245,158,11,.28); }
      50%     { box-shadow: 0 0 0 6px rgba(245,158,11,.15); }
    }
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

        <div class="avatar-wrap">
          <div class="avatar" id="avatarBox">
            <?php if ($avatarUrl): ?><img id="avatarImg" src="<?= $e($avatarUrl) ?>" alt="Profile photo"><?php else: ?><span id="avatarInitials"><?= $e($initials) ?></span><?php endif; ?>
          </div>
          <button class="btn-change-photo" type="button" id="changePhotoBtn">Change Photo</button>
          <input type="file" id="avatarInput" accept=".jpg,.jpeg,.png,.gif,.webp" hidden>
        </div>

        <form id="profileForm">
          <div class="mb-2">
            <label class="settings-label">Full Name</label>
            <input type="text" name="full_name" id="pfName" class="settings-input" value="<?= $e($user['full_name']) ?>" required/>
          </div>
          <div class="mb-2">
            <label class="settings-label">Email</label>
            <input type="email" name="email" id="pfEmail" class="settings-input" value="<?= $e($user['email']) ?>" required/>
          </div>
          <div class="mb-2">
            <label class="settings-label">Phone</label>
            <input type="text" name="phone" id="pfPhone" class="settings-input" value="<?= $e($user['phone']) ?>"/>
          </div>
          <div class="mb-2">
            <label class="settings-label">Installation Address</label>
            <textarea name="address" id="pfAddress" class="settings-input" rows="2" placeholder="Where the cabinetry will be installed — this appears on your quotation."><?= $e($address) ?></textarea>
          </div>
          <?php if ($accountName !== ''): ?>
          <div class="mb-3">
            <label class="settings-label">Account Name</label>
            <input type="text" class="settings-input settings-readonly" value="<?= $e($accountName) ?>" readonly/>
          </div>
          <?php endif; ?>

          <button type="submit" class="settings-save-btn" id="profileSaveBtn">
            <i class="bi bi-check2"></i> <span>Save Changes</span>
          </button>
        </form>
      </div>

      <!-- CHANGE PASSWORD -->
      <div class="settings-card">
        <div class="settings-card-title">Change Password</div>
        <p class="settings-card-sub">Use at least 8 characters. You'll stay signed in after updating.</p>

        <form id="pwForm">
          <div class="mb-2">
            <label class="settings-label">Current Password</label>
            <div class="settings-input-group">
              <i class="bi bi-lock"></i>
              <input type="password" name="current_password" class="settings-input has-toggle" id="currentPassword" placeholder="Enter current password" required>
              <button type="button" class="settings-pw-toggle" data-target="currentPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="mb-2">
            <label class="settings-label">New Password</label>
            <div class="settings-input-group">
              <i class="bi bi-key"></i>
              <input type="password" name="new_password" class="settings-input has-toggle" id="newPassword" placeholder="Enter new password" required minlength="8">
              <button type="button" class="settings-pw-toggle" data-target="newPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div class="mb-2">
            <label class="settings-label">Confirm New Password</label>
            <div class="settings-input-group">
              <i class="bi bi-key"></i>
              <input type="password" name="confirm_password" class="settings-input has-toggle" id="confirmPassword" placeholder="Re-enter new password" required>
              <button type="button" class="settings-pw-toggle" data-target="confirmPassword" aria-label="Show password"><i class="bi bi-eye"></i></button>
            </div>
          </div>

          <div id="pw-mismatch" style="display:none; font-size:0.72rem; color:#dc2626; margin-bottom:0.6rem;">Passwords do not match.</div>
          <button type="submit" class="settings-save-btn" id="pwSaveBtn">
            <i class="bi bi-shield-lock"></i> <span>Update Password</span>
          </button>
        </form>
      </div>

    </div>
  </div>
</div>

<script>
  // ── Profile save ──
  document.getElementById('profileForm').addEventListener('submit', function (e) {
    e.preventDefault();
    vsConfirm('Save these changes to your profile?', { title: 'Save changes', okText: 'Save' }).then(function (ok) {
      if (!ok) return;
      const btn = document.getElementById('profileSaveBtn');
      const body = new URLSearchParams({
        full_name: document.getElementById('pfName').value,
        email:     document.getElementById('pfEmail').value,
        phone:     document.getElementById('pfPhone').value,
        address:   document.getElementById('pfAddress').value,
      });
      btn.disabled = true;
      fetch('save_profile.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Save failed'); return d; })
        .then(() => {
          vsToast('Profile updated.');
          const ini = document.getElementById('avatarInitials');
          if (ini) ini.textContent = (document.getElementById('pfName').value.trim()[0] || 'U').toUpperCase();
        })
        .catch(err => vsToast(err.message, { type: 'error' }))
        .finally(() => { btn.disabled = false; });
    });
  });

  // ── Change photo ──
  const avatarInput = document.getElementById('avatarInput');
  document.getElementById('changePhotoBtn').addEventListener('click', () => avatarInput.click());
  avatarInput.addEventListener('change', function () {
    if (!this.files.length) return;
    const fd = new FormData();
    fd.append('avatar', this.files[0]);
    this.value = '';
    fetch('save_profile.php', { method: 'POST', body: fd })
      .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Upload failed'); return d; })
      .then(d => {
        const box = document.getElementById('avatarBox');
        box.innerHTML = '<img id="avatarImg" src="' + d.avatar + '?t=' + Date.now() + '" alt="Profile photo">';
        vsToast('Profile photo updated.');
      })
      .catch(err => vsToast(err.message, { type: 'error' }));
  });

  // ── Password change ──
  document.getElementById('pwForm').addEventListener('submit', function (e) {
    e.preventDefault();
    const msg = document.getElementById('pw-mismatch');
    const nw = document.getElementById('newPassword').value;
    const cf = document.getElementById('confirmPassword').value;
    if (nw !== cf) { msg.style.display = 'block'; return; }
    msg.style.display = 'none';
    vsConfirm('Update your password?', { title: 'Change password', okText: 'Update' }).then(function (ok) {
      if (!ok) return;
      const btn = document.getElementById('pwSaveBtn');
      btn.disabled = true;
      const body = new URLSearchParams({
        current_password: document.getElementById('currentPassword').value,
        new_password: nw,
      });
      fetch('change_password.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
        .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
        .then(() => {
          vsToast('Password updated.');
          document.getElementById('pwForm').reset();
        })
        .catch(err => vsToast(err.message, { type: 'error' }))
        .finally(() => { btn.disabled = false; });
    });
  });

  // ── Highlight fields the client came here to complete (Request Quote → Fill now) ──
  (function () {
    const focus = new URLSearchParams(location.search).get('focus');
    if (!focus) return;
    const map = { phone: 'pfPhone', address: 'pfAddress' };
    let first = null;
    focus.split(',').forEach(function (key) {
      const el = document.getElementById(map[key.trim()]);
      // Only highlight when the field is still empty (nothing entered yet).
      if (el && el.value.trim() === '') {
        el.classList.add('field-highlight');
        if (!first) first = el;
        // Clear the highlight once the client starts typing.
        el.addEventListener('input', function () { el.classList.remove('field-highlight'); }, { once: true });
      }
    });
    if (first) {
      first.scrollIntoView({ behavior: 'smooth', block: 'center' });
      setTimeout(function () { first.focus({ preventScroll: true }); }, 300);
    }
  })();

  // ── Show/hide password ──
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
