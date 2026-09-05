<?php
/**
 * Handle the public "Let's Talk" contact form (index.php).
 * Stores the message in `contact_messages` for admins to review + reply to
 * manually (customers.php → Messages tab). No email is sent from here.
 *
 * Spam guards: honeypot field, signed time-trap, per-IP rate limit,
 * length caps, and a link-count check.
 */
require_once __DIR__ . '/includes/contact.php';

$back = function (string $status): void {
    header('Location: index.php?contact=' . $status . '#contact');
    exit;
};

if ($_SERVER['REQUEST_METHOD'] !== 'POST') $back('error');

// Guard 1 — honeypot: humans never see/fill 'website'; bots do. Silently drop.
if (trim((string) ($_POST['website'] ?? '')) !== '') $back('sent');

// Guard 2 — signed time-trap: rejects instant (bot) or tampered submissions.
if (!contact_token_valid((string) ($_POST['form_token'] ?? ''))) $back('error');

$name    = trim((string) ($_POST['name'] ?? ''));
$email   = trim((string) ($_POST['email'] ?? ''));
$subject = trim((string) ($_POST['subject'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));

// Basic validation + length caps.
if ($name === '' || $email === '' || $message === '') $back('error');
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $back('error');
if (mb_strlen($name) > 150 || mb_strlen($email) > 150
    || mb_strlen($subject) > 200 || mb_strlen($message) > 5000) $back('error');

// Guard 3 — link flood: legit inquiries rarely contain many URLs.
if (preg_match_all('~https?://~i', $message) > 5) $back('error');

$ip = $_SERVER['REMOTE_ADDR'] ?? '';

try {
    ensure_contact_messages_table();

    // Guard 4 — per-IP rate limit: max 5 messages per hour.
    if (contact_recent_count_by_ip($ip, 60) >= 5) $back('error');

    db()->prepare(
        "INSERT INTO contact_messages (name, email, subject, message, ip_address)
         VALUES (?,?,?,?,?)"
    )->execute([$name, $email, $subject !== '' ? $subject : null, $message, $ip ?: null]);

    contact_notify_backoffice($name); // ring the admin notification bell

    $back('sent');
} catch (Throwable $e) {
    error_log('[CONTACT] insert failed: ' . $e->getMessage());
    $back('error');
}
