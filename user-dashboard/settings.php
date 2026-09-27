<?php
require_once __DIR__ . '/../includes/auth.php';
require_client(); // customers only — staff are sent to their own dashboard
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/legal.php';
require_agreements(); // clients must accept the latest Terms & Privacy first

$active_page = 'settings';

// Real profile for the signed-in client (session only carries id/full_name/role).
$uid = current_user()['id'] ?? 0;
$user = ['full_name' => '', 'email' => '', 'phone' => '', 'avatar' => '', 'location' => ''];
$accountName = '';
$addresses = [];
if ($uid) {
    $stmt = db()->prepare("SELECT full_name, email, phone, avatar, location FROM users WHERE id = ?");
    $stmt->execute([$uid]);
    $row = $stmt->fetch();
    if ($row) $user = array_merge($user, $row);

    $c = db()->prepare("SELECT name FROM customers WHERE user_id = ? ORDER BY id LIMIT 1");
    $c->execute([$uid]);
    $accountName = (string) ($c->fetchColumn() ?: '');
    // Saved installation addresses (default first) — picked per quote request.
    $addresses = client_addresses((int) $uid);
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
  <title>Settings – <?= function_exists('company_name') ? htmlspecialchars(company_name()) : 'Vast Solutions' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
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

    /* ── Saved addresses ── */
    .addr-card { grid-column: 1 / -1; }
    .addr-list { display: flex; flex-direction: column; gap: .55rem; margin-bottom: .8rem; }
    .addr-item {
      display: flex; align-items: flex-start; gap: .75rem;
      border: 1px solid #e5e7eb; border-radius: 8px; padding: .7rem .8rem; background: #fff;
    }
    .addr-item.is-default { border-color: #6ee7d0; background: #f0fdf9; }
    .addr-item > i { color: var(--teal); font-size: 1rem; margin-top: 1px; }
    .addr-body { flex: 1; min-width: 0; }
    .addr-top { display: flex; align-items: center; gap: .45rem; flex-wrap: wrap; }
    .addr-label { font-size: .76rem; font-weight: 600; color: #1f2937; }
    .addr-badge { font-size: .6rem; font-weight: 700; color: #0a7a60; background: #ccfbef; padding: .1rem .45rem; border-radius: 999px; }
    .addr-text { font-size: .72rem; color: #4b5563; line-height: 1.45; margin-top: .15rem; word-break: break-word; }
    .addr-actions { display: flex; gap: .35rem; flex-shrink: 0; }
    .addr-act {
      border: 1px solid #e5e7eb; background: #fff; color: #4b5563; border-radius: 6px;
      font-size: .66rem; font-weight: 600; padding: .25rem .55rem; cursor: pointer; white-space: nowrap;
    }
    .addr-act:hover { border-color: var(--teal); color: var(--teal); }
    .addr-act.danger:hover { border-color: #dc2626; color: #dc2626; }
    .addr-empty { font-size: .74rem; color: #6b7280; padding: .8rem; border: 1px dashed #d1d5db; border-radius: 8px; text-align: center; }
    .addr-form { border: 1px solid #e5e7eb; border-radius: 8px; padding: .8rem; background: #f9fafb; margin-bottom: .8rem; }
    .addr-form-title { font-size: .76rem; font-weight: 600; color: #1f2937; margin-bottom: .6rem; }
    .addr-optional { font-weight: 400; color: #9ca3af; }
    .addr-textarea { height: auto; min-height: 54px; padding-top: .4rem; resize: vertical; }
    .addr-default-check { display: flex; align-items: center; gap: .4rem; font-size: .7rem; color: #374151; margin: .2rem 0 .7rem; cursor: pointer; }
    .addr-form-actions { display: flex; gap: .5rem; align-items: center; }
    .addr-cancel-btn { border: 1px solid #e5e7eb; background: #fff; color: #4b5563; font-size: .72rem; font-weight: 600; border-radius: 6px; padding: .38rem .8rem; cursor: pointer; }
    .addr-add-btn {
      border: 1px dashed var(--teal); background: #fff; color: var(--teal); font-size: .72rem; font-weight: 600;
      border-radius: 6px; padding: .45rem .9rem; cursor: pointer; display: inline-flex; align-items: center; gap: .35rem;
    }
    .addr-add-btn:hover { background: #f0fdf9; }
    @media (max-width: 560px) {
      .addr-item { flex-wrap: wrap; }
      .addr-actions { width: 100%; justify-content: flex-end; }
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
    <div class="topbar-user">
      <span class="topbar-user-avatar"><?= strtoupper(mb_substr($_SESSION['full_name'] ?? 'C', 0, 1)) ?></span>
      <span class="topbar-user-name"><?= htmlspecialchars($_SESSION['full_name'] ?? 'Client') ?></span>
    </div>
  </div>

  <div class="page-content">
    <h1 class="page-title">Settings</h1>

    <div class="settings-grid">

      <!-- PROFILE INFO -->
      <div class="settings-card">
        <div class="settings-card-title">Profile Information</div>

        <div class="avatar-wrap">
          <div class="avatar" id="avatarBox">
            <span id="avatarInitials"><?= $e($initials) ?></span>
          </div>
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

      <!-- SAVED ADDRESSES -->
      <div class="settings-card addr-card" id="addressCard">
        <div class="settings-card-title">Saved Addresses</div>
        <p class="settings-card-sub">Installation addresses you can choose from when requesting a quote. Your default is preselected.</p>

        <div id="addrList" class="addr-list"></div>

        <div id="addrForm" class="addr-form" style="display:none;">
          <div class="addr-form-title" id="addrFormTitle">Add address</div>
          <div class="mb-2">
            <label class="settings-label" for="addrLabel">Label <span class="addr-optional">(optional)</span></label>
            <input type="text" id="addrLabel" class="settings-input" maxlength="60" placeholder="e.g. Home, Condo Unit, Office"/>
          </div>
          <div class="mb-2">
            <label class="settings-label" for="addrText">Full address</label>
            <textarea id="addrText" class="settings-input addr-textarea" rows="2" maxlength="255" placeholder="House/unit no., street, barangay, city, province"></textarea>
          </div>
          <label class="addr-default-check" id="addrDefaultWrap">
            <input type="checkbox" id="addrDefault"/> Set as default
          </label>
          <div class="addr-form-actions">
            <button type="button" class="settings-save-btn" id="addrSaveBtn"><i class="bi bi-check2"></i> <span>Save Address</span></button>
            <button type="button" class="addr-cancel-btn" id="addrCancelBtn">Cancel</button>
          </div>
        </div>

        <button type="button" class="addr-add-btn" id="addrAddBtn"><i class="bi bi-plus-lg"></i> Add address</button>
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

  // ── Saved addresses ──
  let ADDRESSES = <?= json_encode($addresses, JSON_UNESCAPED_UNICODE) ?>;
  const addrList = document.getElementById('addrList');
  const addrForm = document.getElementById('addrForm');
  const addrAdd  = document.getElementById('addrAddBtn');
  let editingId  = 0;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  function renderAddresses() {
    if (!ADDRESSES.length) {
      addrList.innerHTML = '<div class="addr-empty">No saved addresses yet. Add one to use it when requesting a quote.</div>';
      return;
    }
    addrList.innerHTML = ADDRESSES.map(function (a) {
      const def = String(a.is_default) === '1';
      return '<div class="addr-item' + (def ? ' is-default' : '') + '">' +
        '<i class="bi bi-geo-alt"></i>' +
        '<div class="addr-body"><div class="addr-top"><span class="addr-label">' + escHtml(a.label || 'Address') + '</span>' +
          (def ? '<span class="addr-badge">Default</span>' : '') + '</div>' +
          '<div class="addr-text">' + escHtml(a.address) + '</div></div>' +
        '<div class="addr-actions">' +
          (def ? '' : '<button type="button" class="addr-act" data-act="default" data-id="' + a.id + '">Set default</button>') +
          '<button type="button" class="addr-act" data-act="edit" data-id="' + a.id + '">Edit</button>' +
          '<button type="button" class="addr-act danger" data-act="delete" data-id="' + a.id + '">Delete</button>' +
        '</div></div>';
    }).join('');
  }

  function addrPost(data) {
    return fetch('save_address.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(data) })
      .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Could not save the address.'); return d; })
      .then(d => { ADDRESSES = d.addresses || []; renderAddresses(); return d; });
  }

  function openAddrForm(addr) {
    editingId = addr ? Number(addr.id) : 0;
    document.getElementById('addrFormTitle').textContent = addr ? 'Edit address' : 'Add address';
    document.getElementById('addrLabel').value = addr ? (addr.label || '') : '';
    document.getElementById('addrText').value  = addr ? addr.address : '';
    // "Set as default" only applies when adding (existing ones use "Set default").
    document.getElementById('addrDefaultWrap').style.display = (addr || !ADDRESSES.length) ? 'none' : '';
    document.getElementById('addrDefault').checked = false;
    addrForm.style.display = '';
    addrAdd.style.display = 'none';
    document.getElementById('addrText').focus();
  }
  function closeAddrForm() { addrForm.style.display = 'none'; addrAdd.style.display = ''; editingId = 0; }

  addrAdd.addEventListener('click', function () { openAddrForm(null); });
  document.getElementById('addrCancelBtn').addEventListener('click', closeAddrForm);

  document.getElementById('addrSaveBtn').addEventListener('click', function () {
    const btn = this;
    const address = document.getElementById('addrText').value.trim();
    if (address.length < 5) { vsToast('Enter the full address.', { type: 'error' }); return; }
    btn.disabled = true;
    addrPost({
      action: editingId ? 'update' : 'add',
      id: editingId,
      label: document.getElementById('addrLabel').value.trim(),
      address: address,
      make_default: document.getElementById('addrDefault').checked ? '1' : '',
    })
      .then(() => { vsToast(editingId ? 'Address updated.' : 'Address saved.'); closeAddrForm(); })
      .catch(err => vsToast(err.message, { type: 'error' }))
      .finally(() => { btn.disabled = false; });
  });

  addrList.addEventListener('click', function (e) {
    const btn = e.target.closest('.addr-act');
    if (!btn) return;
    const id = Number(btn.dataset.id);
    const addr = ADDRESSES.find(a => Number(a.id) === id);
    if (!addr) return;
    if (btn.dataset.act === 'edit') { openAddrForm(addr); return; }
    if (btn.dataset.act === 'default') {
      addrPost({ action: 'default', id: id }).then(() => vsToast('Default address updated.')).catch(err => vsToast(err.message, { type: 'error' }));
      return;
    }
    vsConfirm('Delete this address?\n' + addr.address, { title: 'Delete address', okText: 'Delete', tone: 'danger' }).then(function (ok) {
      if (!ok) return;
      addrPost({ action: 'delete', id: id }).then(() => vsToast('Address deleted.')).catch(err => vsToast(err.message, { type: 'error' }));
    });
  });

  renderAddresses();

  // ── Highlight fields the client came here to complete (Request Quote → Fill now) ──
  (function () {
    const focus = new URLSearchParams(location.search).get('focus');
    if (!focus) return;
    // No saved address yet → open the add-address form for them.
    if (focus.split(',').map(s => s.trim()).includes('address') && !ADDRESSES.length) {
      openAddrForm(null);
      document.getElementById('addrText').classList.add('field-highlight');
    }
    const map = { phone: 'pfPhone', address: 'addrText' };
    let first = null;
    focus.split(',').forEach(function (key) {
      const el = document.getElementById(map[key.trim()]);
      // Only highlight visible fields that are still empty (nothing entered yet).
      if (el && el.offsetParent !== null && el.value.trim() === '') {
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
