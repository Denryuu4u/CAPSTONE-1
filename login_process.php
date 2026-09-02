<?php
/**
 * Handle sign-in (login.php). Verifies credentials, blocks unverified/inactive
 * accounts, establishes the session, and redirects by role.
 */
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: login.php'); exit; }

$email = trim((string) ($_POST['email'] ?? ''));
$pass  = (string) ($_POST['password'] ?? '');

$fail = function (string $err) use ($email) {
    header('Location: login.php?error=' . urlencode($err) . '&email=' . urlencode($email));
    exit;
};

if ($email === '' || $pass === '') $fail('Enter your email and password.');

$stmt = db()->prepare(
    "SELECT id, full_name, role, password_hash, status, email_verified, is_archived
       FROM users WHERE email = ? LIMIT 1"
);
$stmt->execute([$email]);
$u = $stmt->fetch();

if (!$u || !password_verify($pass, $u['password_hash'])) $fail('Incorrect email or password.');
if ((int) $u['is_archived'] === 1 || $u['status'] !== 'Active') $fail('This account is inactive. Contact an administrator.');
if ($u['role'] === 'Client' && (int) $u['email_verified'] !== 1) {
    // Let them finish verifying instead of a dead end.
    header('Location: verify.php?email=' . urlencode($email)); exit;
}

auth_set_user(['id' => $u['id'], 'full_name' => $u['full_name'], 'role' => $u['role']]);
$_SESSION['real_login'] = true;
try { db()->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([(int) $u['id']]); } catch (Throwable $e) {}
log_audit('Auth', 'Signed in', $email);

header('Location: ' . role_home($u['role']));
exit;
