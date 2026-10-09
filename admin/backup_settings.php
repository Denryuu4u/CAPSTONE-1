<?php
// Included only in Settings. Direct requests cannot reveal backup information.
if (!isset($isSuperAdmin) || !$isSuperAdmin) { http_response_code(403); exit; }
require_once __DIR__ . '/../includes/backup.php';
$_SESSION['backup_csrf'] = $_SESSION['backup_csrf'] ?? bin2hex(random_bytes(32));
$backupToken = htmlspecialchars($_SESSION['backup_csrf'], ENT_QUOTES);
$backupCopies = [];
$backupError = '';
try { $backupCopies = system_backup()->copies(); }
catch (Throwable $ex) { $backupError = $ex->getMessage(); }
$backupSize = function ($bytes) { return $bytes >= 1048576 ? number_format($bytes / 1048576, 1) . ' MB' : number_format($bytes / 1024, 1) . ' KB'; };
?>
<section class="settings-card mt-3" id="backupRestoreCard" aria-labelledby="backupTitle">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
    <div>
      <div class="settings-card-title" id="backupTitle"><i class="bi bi-database-check me-2"></i>Backup &amp; Restore</div>
      <p class="settings-card-sub mb-0">Save your system records and uploaded files. Only the Super Admin can use these controls.</p>
    </div>
    <form action="backup_action.php" method="post" data-backup-form>
      <input type="hidden" name="csrf" value="<?= $backupToken ?>">
      <input type="hidden" name="action" value="create">
      <button class="settings-save-btn" type="submit" style="width:auto" <?= $backupError ? 'disabled' : '' ?>><i class="bi bi-plus-circle"></i> Create backup now</button>
    </form>
  </div>
  <div id="backupFeedback" class="alert <?= $backupError ? 'alert-danger' : 'd-none' ?>" role="status" aria-live="polite"><?= $e($backupError) ?></div>
  <div class="row g-3 mb-4">
    <div class="col-md-4"><div class="backup-summary"><div class="text-muted small mb-2">LAST SAVED COPY</div><strong><?= $backupCopies ? $e(date('M j, Y g:i A', strtotime($backupCopies[0]['created_at']))) : 'No saved copies yet' ?></strong><div class="small text-muted mt-1">Philippine time</div></div></div>
    <div class="col-md-4"><div class="backup-summary"><div class="text-muted small mb-2">BACKUP METHOD</div><strong>Manual backup</strong><div class="small text-muted mt-1">Create a copy whenever you need one.</div></div></div>
    <div class="col-md-4"><div class="backup-summary"><div class="text-muted small mb-2">COPIES SAVED ON THE WEBSITE</div><strong><?= count($backupCopies) ?> <?= count($backupCopies) === 1 ? 'copy' : 'copies' ?></strong><div class="small text-muted mt-1">Copies stay until you delete them.</div></div></div>
  </div>
  <h2 class="h6 mb-3">Saved copies</h2>
  <?php if (!$backupCopies): ?>
    <div class="border rounded-3 p-4 text-center text-muted mb-3"><i class="bi bi-database-add fs-3 d-block mb-2"></i>Create your first backup to save the current system data.</div>
  <?php else: ?>
  <div class="backup-copy-list mb-3">
    <?php foreach ($backupCopies as $copy): $copyDate = date('M j, Y g:i A', strtotime($copy['created_at'])); ?>
    <div class="backup-copy">
      <div>
        <div class="fw-semibold"><?= $e($copyDate) ?></div>
        <div class="small text-muted mt-1"><?= $e($backupSize($copy['size'])) ?> &middot; <?= (int) $copy['tables'] ?> tables &middot; <?= (int) $copy['files'] ?> uploaded files</div>
        <div class="small mt-2"><span class="badge text-bg-light border"><?= $copy['kind'] === 'before_restore' ? 'Recovery copy before restore' : 'Manual backup' ?></span><span class="text-muted ms-2"><?= $e($copy['by']) ?></span></div>
      </div>
      <div class="d-flex flex-wrap gap-2">
        <button type="button" class="btn btn-outline-success btn-sm" data-restore-id="<?= $e($copy['id']) ?>" data-restore-date="<?= $e($copyDate) ?>"><i class="bi bi-clock-history me-1"></i> Restore this copy</button>
        <form action="backup_action.php" method="post">
          <input type="hidden" name="csrf" value="<?= $backupToken ?>"><input type="hidden" name="action" value="download"><input type="hidden" name="id" value="<?= $e($copy['id']) ?>">
          <button type="submit" class="btn btn-outline-secondary btn-sm"><i class="bi bi-download me-1"></i> Save to computer</button>
        </form>
        <form action="backup_action.php" method="post" data-backup-form data-delete-copy>
          <input type="hidden" name="csrf" value="<?= $backupToken ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $e($copy['id']) ?>">
          <button type="submit" class="btn btn-outline-danger btn-sm" aria-label="Delete copy from <?= $e($copyDate) ?>"><i class="bi bi-trash"></i></button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
  <div class="row g-3">
    <div class="col-md-6"><div class="backup-summary"><h3 class="h6"><i class="bi bi-download me-2"></i>Keep a copy away from the website</h3><p class="small text-muted mb-0">Use <strong>Save to computer</strong> after creating a backup. Store it somewhere private: saved copies contain account and customer information. Website copies share the server's storage.</p></div></div>
    <div class="col-md-6"><div class="backup-summary"><h3 class="h6"><i class="bi bi-upload me-2"></i>Bring back a saved file</h3><p class="small text-muted">Choose a ZIP backup previously downloaded from this system.</p><button type="button" class="btn btn-outline-success btn-sm" id="backupUploadButton" <?= $backupError ? 'disabled' : '' ?>><i class="bi bi-upload me-1"></i> Choose a saved file</button></div></div>
  </div>
  <p class="small text-muted mt-3 mb-0">Includes database records, project attachments, gallery images and uploaded logos. Application code, server settings and diagnostic logs are excluded. Restores require a matching system version. The system briefly pauses other requests while saving or restoring.</p>
</section>

<dialog id="backupRestoreDialog" aria-labelledby="restoreDialogTitle">
  <form action="backup_action.php" method="post" enctype="multipart/form-data" data-backup-form id="backupRestoreForm">
    <input type="hidden" name="csrf" value="<?= $backupToken ?>"><input type="hidden" name="action" value="restore"><input type="hidden" name="source" id="restoreSource"><input type="hidden" name="id" id="restoreCopyId">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0" id="restoreDialogTitle">Restore a saved copy</h2><button type="button" class="btn-close" id="restoreDialogClose" aria-label="Close"></button></div>
    <p id="restoreCopyLabel" class="fw-semibold"></p>
    <div id="restoreUploadField" class="mb-3">
      <label for="restoreFile" class="form-label">Saved backup file</label><input type="file" class="form-control" name="backup_file" id="restoreFile" accept=".zip,application/zip">
      <div class="form-text">Server upload limit: <?= $e(ini_get('upload_max_filesize')) ?> per file / <?= $e(ini_get('post_max_size')) ?> per request. Maximum expanded backup: 512 MB.</div>
    </div>
    <div class="alert alert-warning small">This replaces current records and uploaded files with the selected copy. Changes made after that copy will be removed. A recovery copy is saved first. Everyone will need to sign in again using the restored account details.</div>
    <div class="mb-3"><label for="restorePassword" class="form-label">Your current Super Admin password</label><input type="password" class="form-control" id="restorePassword" name="current_password" autocomplete="current-password" required></div>
    <div class="mb-3"><label for="restoreConfirm" class="form-label">Type <strong>RESTORE</strong> to confirm</label><input type="text" class="form-control" id="restoreConfirm" name="confirm_text" pattern="RESTORE" autocomplete="off" required></div>
    <div id="restoreFeedback" class="alert alert-danger d-none" role="alert"></div>
    <div class="d-flex justify-content-end gap-2"><button type="button" class="btn btn-outline-secondary" id="restoreDialogCancel">Cancel</button><button type="submit" class="btn btn-danger">Restore copy</button></div>
  </form>
</dialog>
<style>
  #backupRestoreCard .backup-summary{border:1px solid #e1e8e5;background:#f8fbfa;border-radius:10px;padding:18px;height:100%}
  #backupRestoreCard .backup-copy{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px;border:1px solid #e1e8e5;border-radius:10px;margin-bottom:10px}
  #backupRestoreCard form{margin:0}
  #backupRestoreDialog{border:0;border-radius:14px;padding:24px;width:min(560px,calc(100% - 28px));max-height:90vh;overflow:auto;box-shadow:0 20px 60px #0003}
  #backupRestoreDialog::backdrop{background:#0d1b2a88}
  @media(max-width:767px){#backupRestoreCard .backup-copy{align-items:flex-start;flex-direction:column}}
</style>
<script>
(() => {
  const dialog = document.getElementById('backupRestoreDialog');
  const form = document.getElementById('backupRestoreForm');
  const feedback = document.getElementById('backupFeedback');
  const restoreFeedback = document.getElementById('restoreFeedback');
  let busy = false;
  const close = () => { if (!busy) dialog.close(); };
  document.getElementById('restoreDialogClose').addEventListener('click', close);
  document.getElementById('restoreDialogCancel').addEventListener('click', close);
  dialog.addEventListener('cancel', event => { if (busy) event.preventDefault(); });
  function openRestore(id, date) {
    form.reset();
    restoreFeedback.classList.add('d-none');
    document.getElementById('restoreSource').value = id ? 'saved' : 'upload';
    document.getElementById('restoreCopyId').value = id || '';
    document.getElementById('restoreCopyLabel').textContent = id ? 'Saved on ' + date + ' (Philippine time)' : 'Restore from your computer';
    document.getElementById('restoreUploadField').hidden = !!id;
    document.getElementById('restoreFile').disabled = !!id;
    document.getElementById('restoreFile').required = !id;
    dialog.showModal();
  }
  document.querySelectorAll('[data-restore-id]').forEach(button => button.addEventListener('click', () => openRestore(button.dataset.restoreId, button.dataset.restoreDate)));
  document.getElementById('backupUploadButton').addEventListener('click', () => openRestore());
  document.querySelectorAll('[data-backup-form]').forEach(current => current.addEventListener('submit', async event => {
    event.preventDefault();
    if (busy) return;
    if (current.hasAttribute('data-delete-copy') && !await vsConfirm('Delete this saved copy? Any copy already downloaded to your computer will remain available.', {title:'Delete saved copy', okText:'Delete copy', tone:'danger'})) return;
    const data = new FormData(current);
    const restoring = data.get('action') === 'restore';
    const status = restoring ? restoreFeedback : feedback;
    busy = true;
    const buttons = [...document.querySelectorAll('#backupRestoreCard button, #backupRestoreDialog button')];
    buttons.forEach(button => button.disabled = true);
    status.className = 'alert alert-info';
    status.textContent = restoring ? 'Validating the copy and restoring your data. Please keep this page open…' : 'Working on your saved copy…';
    try {
      const response = await fetch(current.getAttribute('action'), {method:'POST', body:data, credentials:'same-origin'});
      let result;
      try { result = await response.json(); } catch (_) { throw new Error('The server did not return a result. Refresh Settings to check the saved copies before trying again.'); }
      if (!response.ok || !result.ok) throw new Error(result.error || 'The operation could not be completed.');
      status.className = 'alert alert-success';
      status.textContent = result.message;
      if (result.restored) {
        dialog.close();
        await vsAlert(result.message, {title:'Restore completed', okText:'Go to sign in'});
        location.href = result.redirect;
      } else {
        sessionStorage.setItem('backupNotice', result.message);
        location.hash = 'backupRestoreCard';
        location.reload();
      }
    } catch (error) {
      status.className = 'alert alert-danger';
      status.textContent = error.message;
      busy = false;
      buttons.forEach(button => button.disabled = false);
    }
  }));
  const notice = sessionStorage.getItem('backupNotice');
  if (notice) { feedback.className = 'alert alert-success'; feedback.textContent = notice; sessionStorage.removeItem('backupNotice'); }
})();
</script>
