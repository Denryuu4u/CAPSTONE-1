<?php
require_once __DIR__ . '/../includes/helpers.php';
require_page('settings_self', true);
header('Cache-Control: no-store');

function backup_error(string $message, int $status = 400): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

if (current_role() !== 'Super Admin') backup_error('Only the Super Admin can manage backups.', 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') backup_error('POST required.', 405);
if (empty($_POST) && !empty($_SERVER['CONTENT_LENGTH'])) backup_error('The upload is too large for this server. Choose a smaller backup or increase the PHP upload and POST limits.', 413);
if (!isset($_SESSION['backup_csrf'], $_POST['csrf']) || !is_string($_POST['csrf']) || !hash_equals($_SESSION['backup_csrf'], $_POST['csrf'])) backup_error('Your session has expired. Refresh Settings and try again.', 403);

$action = (string) ($_POST['action'] ?? '');
if (!in_array($action, ['create', 'download', 'delete', 'restore'], true)) backup_error('Unknown backup action.');
$pdo = db();
$stmt = $pdo->prepare('SELECT id, full_name, role, status, is_archived, password_hash FROM users WHERE id = ?');
$stmt->execute([current_user()['id']]);
$actor = $stmt->fetch();
if (!$actor || $actor['role'] !== 'Super Admin' || $actor['status'] !== 'Active' || $actor['is_archived']) backup_error('An active Super Admin account is required.', 403);

$temporary = null;
try {
    require_once __DIR__ . '/../includes/backup.php';
    if ($action === 'restore') {
        if (($_POST['confirm_text'] ?? '') !== 'RESTORE') backup_error('Type RESTORE to confirm replacing the current data.');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $actor['password_hash'])) backup_error('The current Super Admin password is incorrect.', 403);
    }
    // Wait for in-flight app requests, then keep the data stable until exit.
    system_data_lock(true);
    if (($_SESSION['restore_epoch'] ?? '') !== system_restore_epoch()) backup_error('The system was restored while this request was waiting. Sign in again.', 409);
    $stmt->execute([current_user()['id']]);
    $actor = $stmt->fetch();
    if (!$actor || $actor['role'] !== 'Super Admin' || $actor['status'] !== 'Active' || $actor['is_archived']) backup_error('An active Super Admin account is required.', 403);
    if ($action === 'restore' && !password_verify((string) ($_POST['current_password'] ?? ''), $actor['password_hash'])) backup_error('The current Super Admin password is incorrect.', 403);
    @set_time_limit(300);
    ignore_user_abort(true);
    $backup = system_backup();
    $id = (string) ($_POST['id'] ?? '');
    if ($action === 'download') {
        $path = $backup->path($id);
        log_audit('Backup & Restore', 'Downloaded a saved copy', $id);
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $id . '"');
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        readfile($path);
        exit;
    }
    if ($action === 'create') {
        $id = $backup->create($actor['full_name']);
        log_audit('Backup & Restore', 'Created a manual backup', $id);
        $message = 'A new saved copy is ready. Download it to keep a copy away from the website.';
    } elseif ($action === 'delete') {
        $backup->delete($id);
        log_audit('Backup & Restore', 'Deleted a saved copy', $id);
        $message = 'The saved copy was deleted.';
    } else {
        if (($_POST['source'] ?? '') === 'upload') {
            $file = $_FILES['backup_file'] ?? [];
            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) throw new RuntimeException('The file was not uploaded. Check the upload size limit shown in Settings and try again.');
            if (!is_uploaded_file($file['tmp_name'])) throw new RuntimeException('Choose a saved ZIP file from your computer.');
            $temporary = $file['tmp_name'];
            $path = $temporary;
            $label = 'Uploaded backup';
        } else {
            $path = $backup->path($id);
            $label = $id;
        }
        $safety = $backup->restore($path, $actor['full_name']);
        // The original user ID may no longer belong to the same account.
        try {
            $pdo->prepare('INSERT INTO audit_logs (user_id, user_name, role, action, module, details) VALUES (NULL, ?, ?, ?, ?, ?)')
                ->execute([$actor['full_name'], 'Super Admin', 'Restored a saved copy', 'Backup & Restore', $label . '; recovery copy: ' . $safety]);
        } catch (Throwable $auditError) { error_log('[Backup & Restore] Restore succeeded; audit entry failed: ' . $auditError->getMessage()); }
        $_SESSION = [];
        session_regenerate_id(true);
        $message = 'Restore completed. Sign in using the account details from the restored copy. A recovery copy of the previous data was saved.';
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'message' => $message, 'restored' => $action === 'restore', 'redirect' => BASE_URL . '/login.php']);
} catch (Throwable $e) {
    $message = app_error_message($e, 'The backup or restore could not be completed. Please try again or contact the administrator.');
    // A database failure must not cause a second exception while auditing it.
    try { log_audit('Backup & Restore', 'Backup operation failed', $action); }
    catch (Throwable $auditError) { app_log_error($auditError); }
    backup_error($message, $e instanceof PDOException ? 500 : 400);
}
