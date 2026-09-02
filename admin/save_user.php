<?php
/** Add or edit a system user (from user-management.php modals). Super Admin only. */
require_once __DIR__ . '/../includes/helpers.php';
require_page('user_management', true); // JSON 403 unless Super Admin
header('Content-Type: application/json; charset=utf-8');

$id     = (int) ($_POST['id'] ?? 0);
$name   = trim((string) ($_POST['full_name'] ?? ''));
$email  = trim((string) ($_POST['email'] ?? ''));
$phone  = trim((string) ($_POST['phone'] ?? ''));
$role   = in_array($_POST['role'] ?? '', ['Super Admin', 'Admin', 'Staff', 'Client'], true) ? $_POST['role'] : 'Staff';
$status = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';
$pass   = (string) ($_POST['password'] ?? '');

if ($name === '' || $email === '') { http_response_code(400); echo json_encode(['ok' => false, 'error' => 'Name and email are required.']); exit; }

// Safeguard: don't let a user demote or deactivate their own account.
$meId = current_user()['id'] ?? 0;
if ($id > 0 && $id === (int) $meId && ($role !== 'Super Admin' || $status !== 'Active')) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'You cannot change your own role or status.']);
    exit;
}

try {
    if ($id > 0) {
        if ($pass !== '') {
            db()->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, status=?, password_hash=? WHERE id=?")
                ->execute([$name, $email, $phone ?: null, $role, $status, password_hash($pass, PASSWORD_BCRYPT), $id]);
        } else {
            db()->prepare("UPDATE users SET full_name=?, email=?, phone=?, role=?, status=? WHERE id=?")
                ->execute([$name, $email, $phone ?: null, $role, $status, $id]);
        }
        log_audit('User Management', "Edited user #{$id}", "{$name} ({$role})");
    } else {
        $hash = password_hash($pass !== '' ? $pass : 'changeme123', PASSWORD_BCRYPT);
        // Admin-created accounts skip the client OTP flow — mark them verified.
        db()->prepare("INSERT INTO users (full_name, email, phone, role, status, password_hash, email_verified) VALUES (?,?,?,?,?,?,1)")
            ->execute([$name, $email, $phone ?: null, $role, $status, $hash]);
        $id = (int) db()->lastInsertId();
        log_audit('User Management', "Added user #{$id}", "{$name} ({$role})");
    }
    echo json_encode(['ok' => true, 'id' => $id]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { http_response_code(409); echo json_encode(['ok' => false, 'error' => 'That email is already in use.']); exit; }
    http_response_code(500); echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
