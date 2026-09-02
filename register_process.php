<?php
/**
 * Handle client sign-up (signup.php). Creates an unverified Client account,
 * issues a signup OTP, emails it, then sends the user to verify.php.
 */
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/legal.php';
require_once __DIR__ . '/includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: signup.php'); exit; }

$name    = trim((string) ($_POST['full_name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$pass    = (string) ($_POST['password'] ?? '');
$confirm = (string) ($_POST['confirm_password'] ?? '');
$agree   = ($_POST['agree'] ?? '') === '1';

$back = function (string $err) use ($email) {
    header('Location: signup.php?error=' . urlencode($err) . '&email=' . urlencode($email));
    exit;
};

if ($name === '' || $email === '' || $pass === '') $back('All fields are required.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $back('Enter a valid email address.');
if (strlen($pass) < 8)                             $back('Password must be at least 8 characters.');
if ($pass !== $confirm)                            $back('Passwords do not match.');
if (!$agree)                                       $back('You must agree to the Terms & Conditions and Privacy Policy.');

try {
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    db()->prepare(
        "INSERT INTO users (full_name, email, password_hash, role, status, email_verified)
         VALUES (?,?,?, 'Client', 'Active', 0)"
    )->execute([$name, $email, $hash]);
    $userId = (int) db()->lastInsertId();

    // Record acceptance of the current Terms & Privacy at sign-up time.
    legal_record_acceptance($userId, $_SERVER['REMOTE_ADDR'] ?? null);

    $code = create_otp($userId, $email, 'signup');
    send_otp_email($email, $code, $name);
    log_audit('Auth', 'Client sign-up (pending verification)', $email);

    header('Location: verify.php?email=' . urlencode($email));
    exit;
} catch (PDOException $e) {
    if ($e->getCode() === '23000') $back('That email is already registered. Try signing in.');
    $back('Something went wrong. Please try again.');
}
