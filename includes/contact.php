<?php
/**
 * Public contact-form storage + spam guards.
 *
 * DB-only (requires db.php, NOT auth.php) so the public index.php can include it
 * without starting a session or triggering DEV auto-login. Messages are stored in
 * `contact_messages` and reviewed by admins in customers.php (Messages tab).
 */
require_once __DIR__ . '/db.php';

/** Create contact_messages if missing — auto-migrates on any host (no manual step). */
function ensure_contact_messages_table(): void
{
    static $done = false;
    if ($done) return;
    db()->exec(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            email VARCHAR(150) NOT NULL,
            subject VARCHAR(200) NULL,
            message TEXT NOT NULL,
            ip_address VARCHAR(45) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_created (created_at),
            INDEX idx_ip_created (ip_address, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
    $done = true;
}

/** Secret for signing the form's time-trap token (env override; spam-grade only). */
function contact_form_secret(): string
{
    $s = getenv('FORM_SECRET');
    return ($s !== false && $s !== '') ? $s : 'vast-solutions-contact-form-2026';
}

/** A signed "rendered at" token to embed in the form as a hidden field (ts:hmac). */
function contact_form_token(): string
{
    $t = time();
    return $t . ':' . hash_hmac('sha256', (string) $t, contact_form_secret());
}

/**
 * Valid only if the signature checks out AND the form was submitted no faster
 * than $min seconds (bots submit instantly) and no older than $max seconds.
 */
function contact_token_valid(string $token, int $min = 3, int $max = 7200): bool
{
    $parts = explode(':', $token, 2);
    if (count($parts) !== 2) return false;
    [$ts, $sig] = $parts;
    if (!ctype_digit($ts)) return false;
    $expected = hash_hmac('sha256', $ts, contact_form_secret());
    if (!hash_equals($expected, $sig)) return false;
    $elapsed = time() - (int) $ts;
    return $elapsed >= $min && $elapsed <= $max;
}

/**
 * Notify every back-office user (Super Admin / Admin / Staff) about a new
 * contact message so it shows in their notification bell. One row per user
 * (the bell matches on user_id), links to the Messages tab in customers.php.
 */
function contact_notify_backoffice(string $senderName): void
{
    try {
        $ids = db()->query(
            "SELECT id FROM users WHERE role IN ('Super Admin','Admin','Staff')"
        )->fetchAll(PDO::FETCH_COLUMN);
        if (!$ids) return;

        $ins = db()->prepare(
            "INSERT INTO notifications (user_id, type, title, message, link, severity)
             VALUES (?, 'system', ?, ?, 'customers.php?tab=messages', 'info')"
        );
        $title = 'New contact message';
        $msg   = 'From ' . ($senderName !== '' ? $senderName : 'a website visitor')
               . ' — via the website contact form.';
        foreach ($ids as $uid) {
            $ins->execute([(int) $uid, $title, $msg]);
        }
    } catch (Throwable $e) {
        // Non-fatal: a failed notification must not block storing the message.
    }
}

/** Messages sent from this IP within the last N minutes (per-IP rate limiting). */
function contact_recent_count_by_ip(string $ip, int $minutes = 60): int
{
    if ($ip === '') return 0;
    $st = db()->prepare(
        "SELECT COUNT(*) FROM contact_messages
          WHERE ip_address = ? AND created_at >= (NOW() - INTERVAL " . (int) $minutes . " MINUTE)"
    );
    $st->execute([$ip]);
    return (int) $st->fetchColumn();
}
