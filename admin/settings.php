<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(); // enforced only when DEV_MODE is false

$active_page = 'settings';
require_page('settings_self'); // any back-office may open Settings (profile + password)
$user_name = $_SESSION['full_name'] ?? 'Admin User';

// Company / website / costing / gallery sections are Super Admin + Admin only.
$canAdminSettings = role_can('settings');

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/legal.php';
$settings = db()->query("SELECT * FROM company_settings WHERE id = 1")->fetch() ?: [];

// Legal documents (Terms & Conditions, Privacy Policy) — editable by Super Admin only.
$isSuperAdmin = (current_role() === 'Super Admin');
$legalDocs = [];
foreach (legal_docs() as $__d) { $legalDocs[$__d['doc_key']] = $__d; }
$sv = fn($k, $d = '') => htmlspecialchars((string) ($settings[$k] ?? $d), ENT_QUOTES);

// Current user (for the Profile Information card) — full row, since current_user()
// only carries id/full_name/role.
$me = [];
$__uid = current_user()['id'] ?? 0;
if ($__uid) {
    $__stmt = db()->prepare("SELECT id, full_name, email, phone FROM users WHERE id = ?");
    $__stmt->execute([$__uid]);
    $me = $__stmt->fetch() ?: [];
}
$mv = fn($k, $d = '') => htmlspecialchars((string) ($me[$k] ?? $d), ENT_QUOTES);

// Design gallery images (shown on index.php + request_quote.php).
$galleryImages = db()->query("SELECT id, file_path, label FROM gallery_images ORDER BY sort_order, id")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Settings – Vast Solutions</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;700;800&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <link rel="stylesheet" href="admin.css">
</head>

<body>

    <?php include 'sidebar.php'; ?>

    <div class="main">

        <div class="topbar">
            <div class="d-flex justify-content-between align-items-center w-100">

                <div class="d-flex align-items-center gap-2">
                    <a href="#">Portal</a>
                    <span class="sep">›</span>
                    <span>Settings</span>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <?php include __DIR__ . '/../includes/notif_bell.php'; ?>
                    <div class="user-avatar-sm">
                        <?= strtoupper(substr($user_name, 0, 1)); ?>
                    </div>
                    <div class="lh-sm">
                        <div class="fw-semibold small text-dark"><?= htmlspecialchars($user_name); ?></div>
                        <div class="text-muted" style="font-size: 12px;">Administrator</div>
                    </div>
                </div>

            </div>
        </div>

        <div class="page-content container-fluid py-4 px-4">

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h1 class="page-title mb-0">Settings</h1>
            </div>

            <div class="row g-3">
                <div class="col-lg-6">
                    <?php if ($canAdminSettings): ?>
                    <div class="settings-card mb-3">
                        <div class="settings-card-title">Company Information</div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="settings-label">Company Name</label>
                                <input type="text" class="settings-input" id="setCompanyName" value="<?= $sv('company_name', 'Vast Solutions') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Email</label>
                                <input type="email" class="settings-input" id="setEmail" value="<?= $sv('email') ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Address</label>
                            <input type="text" class="settings-input" id="setAddress" value="<?= $sv('address') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Contact Number</label>
                            <input type="text" class="settings-input" id="setContact" value="<?= $sv('contact_number') ?>">
                        </div>

                        <hr class="settings-divider">

                        <div>
                            <label class="settings-label">Company Logo</label>
                            <a href="#" class="settings-upload-btn">
                                <i class="bi bi-upload"></i>
                                <span>Upload Logo</span>
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="settings-card">
                        <div class="settings-card-title">Profile Information</div>

                        <div class="profile-top">
                            <div class="profile-avatar"><?= strtoupper(substr($me['full_name'] ?? 'A', 0, 1)) ?></div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Full Name</label>
                            <input type="text" class="settings-input" id="setProfileName" value="<?= $mv('full_name') ?>">
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Email</label>
                            <input type="email" class="settings-input" id="setProfileEmail" value="<?= $mv('email') ?>">
                        </div>

                        <div>
                            <label class="settings-label">Phone</label>
                            <input type="text" class="settings-input" id="setProfilePhone" value="<?= $mv('phone') ?>">
                        </div>
                    </div>

                    <div class="settings-card mt-3">
                        <div class="settings-card-title">Change Password</div>
                        <p class="settings-card-sub">Use at least 8 characters. You'll stay signed in after updating.</p>

                        <div class="mb-2">
                            <label class="settings-label">Current Password</label>
                            <div class="settings-input-group">
                                <i class="bi bi-lock"></i>
                                <input type="password" class="settings-input has-toggle" id="currentPassword" placeholder="Enter current password">
                                <button type="button" class="settings-pw-toggle" data-target="currentPassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">New Password</label>
                            <div class="settings-input-group">
                                <i class="bi bi-key"></i>
                                <input type="password" class="settings-input has-toggle" id="newPassword" placeholder="Enter new password">
                                <button type="button" class="settings-pw-toggle" data-target="newPassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="settings-label">Confirm New Password</label>
                            <div class="settings-input-group">
                                <i class="bi bi-key"></i>
                                <input type="password" class="settings-input has-toggle" id="confirmPassword" placeholder="Re-enter new password">
                                <button type="button" class="settings-pw-toggle" data-target="confirmPassword" aria-label="Show password">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <button type="button" class="settings-save-btn" id="updatePasswordBtn">
                            <i class="bi bi-shield-lock"></i>
                            <span>Update Password</span>
                        </button>
                    </div>
                </div>

                <?php if ($canAdminSettings): ?>
                <div class="col-lg-6">
                    <div class="settings-card mb-3">
                        <div class="settings-card-title">Website Contact Information</div>
                        <p class="settings-card-sub">These details appear in the “Contact Us” section of the public landing page.</p>

                        <div class="mb-2">
                            <label class="settings-label">Email</label>
                            <div class="settings-input-group">
                                <i class="bi bi-envelope"></i>
                                <input type="email" class="settings-input" id="setWebEmail" value="<?= $sv('web_email') ?>">
                            </div>
                        </div>

                        <div class="mb-2">
                            <label class="settings-label">Phone</label>
                            <div class="settings-input-group">
                                <i class="bi bi-telephone"></i>
                                <input type="text" class="settings-input" id="setWebPhone" value="<?= $sv('web_phone') ?>">
                            </div>
                        </div>

                        <div>
                            <label class="settings-label">Location</label>
                            <div class="settings-input-group">
                                <i class="bi bi-geo-alt"></i>
                                <input type="text" class="settings-input" id="setWebLocation" value="<?= $sv('web_location') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="settings-card">
                        <div class="settings-card-title">Default Costing Settings</div>

                        <div class="row g-2 mb-2">
                            <div class="col-md-6">
                                <label class="settings-label">Default Markup %</label>
                                <input type="number" step="any" class="settings-input" id="setMarkup" value="<?= $sv('default_markup_pct', '15') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Default Contingency %</label>
                                <input type="number" step="any" class="settings-input" id="setContingency" value="<?= $sv('default_contingency_pct', '5') ?>">
                            </div>
                        </div>

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="settings-label">Default Service %</label>
                                <input type="number" step="any" class="settings-input" id="setService" value="<?= $sv('default_service_pct', '10') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="settings-label">Default Protection %</label>
                                <input type="number" step="any" class="settings-input" id="setProtection" value="<?= $sv('default_protection_pct', '3') ?>">
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($canAdminSettings): ?>
            <!-- Design Gallery -->
            <div class="settings-card mt-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                    <div class="settings-card-title mb-0">Design Gallery</div>
                    <label class="settings-upload-btn mb-0" style="cursor:pointer;">
                        <i class="bi bi-upload"></i>
                        <span>Add Images</span>
                        <input type="file" id="galleryUploadInput" accept=".jpg,.jpeg,.png,.gif,.webp" multiple hidden>
                    </label>
                </div>
                <p class="settings-card-sub">These images appear in the public landing page gallery and in the client's “Browse Designs” reference picker.</p>
                <div class="gallery-manage-grid" id="galleryGrid">
                    <?php if (empty($galleryImages)): ?>
                    <div class="text-muted small" id="galleryEmpty">No images yet — add some with the button above.</div>
                    <?php else: foreach ($galleryImages as $g): ?>
                    <div class="gallery-manage-item" data-id="<?= (int) $g['id'] ?>">
                        <img src="../<?= htmlspecialchars($g['file_path']) ?>" alt="<?= htmlspecialchars($g['label'] ?? '') ?>" loading="lazy">
                        <button type="button" class="gallery-remove-btn" title="Remove" data-id="<?= (int) $g['id'] ?>">&times;</button>
                    </div>
                    <?php endforeach; endif; ?>
                </div>
                <div class="text-danger small mt-2" id="galleryError" style="display:none;"></div>
            </div>
            <?php endif; // $canAdminSettings (Design Gallery) ?>

            <style>
                .gallery-manage-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(120px,1fr)); gap:12px; margin-top:10px; }
                .gallery-manage-item { position:relative; border-radius:8px; overflow:hidden; border:1px solid #e5e7eb; aspect-ratio:4/3; background:#f9fafb; }
                .gallery-manage-item img { width:100%; height:100%; object-fit:cover; display:block; }
                .gallery-remove-btn {
                    position:absolute; top:6px; right:6px; width:26px; height:26px; border:none; border-radius:50%;
                    background:rgba(220,38,38,.92); color:#fff; font-size:18px; line-height:1; cursor:pointer;
                    display:flex; align-items:center; justify-content:center; box-shadow:0 1px 4px rgba(0,0,0,.25);
                }
                .gallery-remove-btn:hover { background:#b91c1c; }
            </style>

            <?php if ($isSuperAdmin): ?>
            <!-- Legal Documents (Super Admin only) -->
            <div class="settings-card mt-3" id="legalCard">
                <div class="settings-card-title">Legal Documents</div>
                <p class="settings-card-sub">
                    Edit your Terms &amp; Conditions and Privacy Policy. Saving a change bumps the document
                    version, so every client is asked to review and accept it again on their next visit.
                </p>

                <div class="legal-list">
                    <?php foreach (['terms' => 'Terms & Conditions', 'privacy' => 'Privacy Policy'] as $__k => $__label):
                        $__doc = $legalDocs[$__k] ?? ['title' => '', 'body' => '', 'version' => 1, 'updated_at' => null]; ?>
                    <div class="legal-row" data-key="<?= $__k ?>">
                        <div class="legal-row-main">
                            <div class="legal-row-icon"><i class="bi bi-file-earmark-text"></i></div>
                            <div>
                                <div class="legal-row-title"><?= htmlspecialchars($__label) ?></div>
                                <div class="legal-row-meta">
                                    Version <span class="legal-version"><?= (int) $__doc['version'] ?></span>
                                    · updated <span class="legal-updated"><?= htmlspecialchars($__doc['updated_at'] ? date('M d, Y', strtotime($__doc['updated_at'])) : '—') ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="legal-row-actions">
                            <a href="../legal.php?doc=<?= $__k ?>" target="_blank" class="legal-preview-link">
                                <i class="bi bi-box-arrow-up-right"></i> Preview
                            </a>
                            <button type="button" class="settings-save-btn legal-edit-btn" style="width:auto;">
                                <i class="bi bi-pencil-square"></i> <span>Edit</span>
                            </button>
                        </div>
                        <!-- current values, read when opening the modal -->
                        <input type="hidden" class="legal-store-title" value="<?= htmlspecialchars((string) $__doc['title'], ENT_QUOTES) ?>">
                        <textarea class="legal-store-body d-none"><?= htmlspecialchars((string) $__doc['body']) ?></textarea>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Large editor modal -->
            <div class="modal fade" id="legalModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="legalModalHeading">Edit document</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted mb-3" style="font-size:.82rem;">
                                Formatting: <code>#</code> starts a heading, <code>-</code> a bullet, <code>--</code> a sub-bullet.
                                A blank line starts a new paragraph.
                            </p>
                            <label class="settings-label">Title</label>
                            <input type="text" class="settings-input mb-3" id="legalModalTitle">
                            <label class="settings-label">Content</label>
                            <textarea class="settings-input" id="legalModalBody" spellcheck="false"
                                style="min-height:58vh; font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:13px;line-height:1.6;resize:vertical;"></textarea>
                            <div class="small mt-2" id="legalModalStatus"></div>
                        </div>
                        <div class="modal-footer justify-content-between">
                            <a href="#" target="_blank" id="legalModalPreview" class="legal-preview-link">
                                <i class="bi bi-box-arrow-up-right"></i> Preview current version
                            </a>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="settings-save-btn" id="legalModalSave" style="width:auto;">
                                    <i class="bi bi-floppy"></i> <span>Save changes</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                .legal-list { display:flex; flex-direction:column; gap:10px; margin-top:6px; }
                .legal-row { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
                    border:1px solid #e5e7eb; border-radius:10px; padding:14px 16px; background:#fff; }
                .legal-row-main { display:flex; align-items:center; gap:12px; min-width:0; }
                .legal-row-icon { width:38px; height:38px; border-radius:9px; background:#e7f7f2; color:#0D9676;
                    display:flex; align-items:center; justify-content:center; font-size:1.05rem; flex:none; }
                .legal-row-title { font-weight:600; color:#111827; font-size:.95rem; }
                .legal-row-meta { color:#6b7280; font-size:.78rem; margin-top:1px; }
                .legal-row-actions { display:flex; align-items:center; gap:14px; }
                .legal-preview-link { color:#0D9676; text-decoration:none; font-size:.83rem; font-weight:500; }
                .legal-preview-link:hover { text-decoration:underline; }
            </style>

            <script>
            (function () {
                let modalInstance = null;
                let currentRow    = null;
                const modalEl   = document.getElementById('legalModal');
                const heading   = document.getElementById('legalModalHeading');
                const titleIn   = document.getElementById('legalModalTitle');
                const bodyIn    = document.getElementById('legalModalBody');
                const status    = document.getElementById('legalModalStatus');
                const preview   = document.getElementById('legalModalPreview');
                const saveBtn   = document.getElementById('legalModalSave');

                document.querySelectorAll('.legal-edit-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        currentRow = btn.closest('.legal-row');
                        const key   = currentRow.dataset.key;
                        heading.textContent = 'Edit ' + currentRow.querySelector('.legal-row-title').textContent.trim();
                        titleIn.value = currentRow.querySelector('.legal-store-title').value;
                        bodyIn.value  = currentRow.querySelector('.legal-store-body').value;
                        preview.href  = '../legal.php?doc=' + key;
                        status.textContent = '';
                        if (!modalInstance) modalInstance = new bootstrap.Modal(modalEl);
                        modalInstance.show();
                    });
                });

                saveBtn.addEventListener('click', function () {
                    if (!currentRow) return;
                    const key   = currentRow.dataset.key;
                    const title = titleIn.value.trim();
                    const body  = bodyIn.value;
                    if (!title || !body.trim()) {
                        status.className = 'small mt-2 text-danger';
                        status.textContent = 'Title and content are required.';
                        return;
                    }
                    const span = saveBtn.querySelector('span'); span.textContent = 'Saving…'; saveBtn.disabled = true;
                    fetch('save_legal.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({ doc_key: key, title, body })
                    })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Save failed'); return d; })
                    .then(d => {
                        // reflect saved state back into the row so a re-open shows the latest
                        currentRow.querySelector('.legal-version').textContent = d.version;
                        currentRow.querySelector('.legal-store-title').value = title;
                        currentRow.querySelector('.legal-store-body').value = body;
                        span.textContent = 'Save changes'; saveBtn.disabled = false;
                        status.className = 'small mt-2 text-success';
                        status.textContent = d.changed
                            ? ('Saved. Version bumped to v' + d.version + ' — clients will be asked to re-accept.')
                            : 'Saved (no changes to content).';
                    })
                    .catch(e => {
                        span.textContent = 'Save changes'; saveBtn.disabled = false;
                        status.className = 'small mt-2 text-danger'; status.textContent = e.message;
                    });
                });
            })();
            </script>
            <?php endif; ?>

            <div class="settings-save-bar">
                <a href="#" class="settings-save-btn" id="saveSettingsBtn">
                    <i class="bi bi-floppy"></i>
                    <span>Save Changes</span>
                </a>
            </div>
            <script>
            document.getElementById('saveSettingsBtn').addEventListener('click', function (e) {
                e.preventDefault();
                // Only send fields that exist on the page — Staff sees profile fields only.
                const body = new URLSearchParams();
                const put = (key, id) => { const el = document.getElementById(id); if (el) body.append(key, el.value); };
                put('company_name', 'setCompanyName');
                put('email', 'setEmail');
                put('address', 'setAddress');
                put('contact_number', 'setContact');
                put('web_email', 'setWebEmail');
                put('web_phone', 'setWebPhone');
                put('web_location', 'setWebLocation');
                put('profile_name', 'setProfileName');
                put('profile_email', 'setProfileEmail');
                put('profile_phone', 'setProfilePhone');
                put('default_markup_pct', 'setMarkup');
                put('default_contingency_pct', 'setContingency');
                put('default_service_pct', 'setService');
                put('default_protection_pct', 'setProtection');
                fetch('save_settings.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(() => { const el = this.querySelector('span'); el.textContent = 'Saved ✓'; setTimeout(() => el.textContent = 'Save Changes', 1500); })
                    .catch(err => alert(err.message));
            });

            // Change password
            document.getElementById('updatePasswordBtn').addEventListener('click', function () {
                const cur = document.getElementById('currentPassword').value;
                const nw  = document.getElementById('newPassword').value;
                const cf  = document.getElementById('confirmPassword').value;
                if (!cur || !nw) { alert('Enter your current and new password.'); return; }
                if (nw.length < 8) { alert('New password must be at least 8 characters.'); return; }
                if (nw !== cf) { alert('New password and confirmation do not match.'); return; }
                const btn = this, span = btn.querySelector('span');
                const body = new URLSearchParams({ current_password: cur, new_password: nw });
                fetch('save_password.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(() => {
                        span.textContent = 'Updated ✓';
                        document.getElementById('currentPassword').value = '';
                        document.getElementById('newPassword').value = '';
                        document.getElementById('confirmPassword').value = '';
                        setTimeout(() => span.textContent = 'Update Password', 1500);
                    })
                    .catch(err => alert(err.message));
            });

            // ── Design Gallery: add / remove images (admin only; skip if absent) ──
            const galleryGrid  = document.getElementById('galleryGrid');
            const galleryError = document.getElementById('galleryError');
            if (galleryGrid) {

            function galleryCard(img) {
                const div = document.createElement('div');
                div.className = 'gallery-manage-item';
                div.dataset.id = img.id;
                div.innerHTML = '<img src="../' + img.file_path + '" alt="' + (img.label || '') + '" loading="lazy">' +
                    '<button type="button" class="gallery-remove-btn" title="Remove" data-id="' + img.id + '">&times;</button>';
                return div;
            }

            document.getElementById('galleryUploadInput').addEventListener('change', function () {
                if (!this.files.length) return;
                galleryError.style.display = 'none';
                const fd = new FormData();
                for (const f of this.files) fd.append('gallery_files[]', f);
                this.value = '';
                fetch('save_gallery_image.php', { method: 'POST', body: fd })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false, error: 'Bad response' })); if (!r.ok || !d.ok) throw new Error(d.error || 'Upload failed'); return d; })
                    .then(d => {
                        const empty = document.getElementById('galleryEmpty');
                        if (empty) empty.remove();
                        d.images.forEach(img => galleryGrid.appendChild(galleryCard(img)));
                    })
                    .catch(e => { galleryError.textContent = e.message; galleryError.style.display = 'block'; });
            });

            galleryGrid.addEventListener('click', function (e) {
                const btn = e.target.closest('.gallery-remove-btn');
                if (!btn) return;
                const item = btn.closest('.gallery-manage-item');
                if (!confirm('Remove this image from the gallery?')) return;
                fetch('delete_gallery_image.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ id: btn.dataset.id }) })
                    .then(async r => { const d = await r.json().catch(() => ({ ok: false })); if (!r.ok || !d.ok) throw new Error(d.error || 'Failed'); return d; })
                    .then(() => {
                        item.remove();
                        if (!galleryGrid.querySelector('.gallery-manage-item')) {
                            galleryGrid.innerHTML = '<div class="text-muted small" id="galleryEmpty">No images yet — add some with the button above.</div>';
                        }
                    })
                    .catch(e => { galleryError.textContent = e.message; galleryError.style.display = 'block'; });
            });

            } // end if (galleryGrid)
            </script>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Show/hide password fields in the Change Password card
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