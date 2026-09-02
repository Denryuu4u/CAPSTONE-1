<?php
/**
 * SMTP settings for outgoing mail (OTP verification emails).
 *
 * === RECOMMENDED: use environment variables (Railway / any host) ================
 * DO NOT paste your Gmail app password into this file and commit it to git.
 * Instead, set these variables in your host's dashboard (Railway → your service →
 * "Variables"), and this file reads them automatically:
 *
 *     SMTP_HOST          smtp.gmail.com        (optional, this is the default)
 *     SMTP_PORT          587                   (optional; 587 = STARTTLS, 465 = SSL)
 *     SMTP_SECURE        tls                   (optional; 'tls' for 587, 'ssl' for 465)
 *     SMTP_USERNAME      you@gmail.com         (your full Gmail address)
 *     SMTP_APP_PASSWORD  abcdefghijklmnop      (16-char Gmail App Password, NO spaces)
 *     SMTP_FROM_EMAIL    you@gmail.com         (optional; defaults to SMTP_USERNAME)
 *     SMTP_FROM_NAME     Vast Solutions        (optional)
 *
 * === HOW TO GET A GMAIL APP PASSWORD ==========================================
 * 1. Use a Gmail account with 2-Step Verification turned ON.
 * 2. Google Account → Security → App passwords → create one (16 characters,
 *    e.g. "abcd efgh ijkl mnop"). Use it WITHOUT the spaces.
 *
 * === LOCAL FALLBACK ===========================================================
 * For local XAMPP testing you MAY hardcode the two blanks below instead of using
 * env vars, but never commit real credentials. While username/app_password are
 * empty (and no env vars are set), the app runs in DEMO mode: OTP codes are
 * written to uploads/otp_log.txt instead of being emailed, so signup still works
 * end-to-end for testing.
 * ============================================================================
 */

// Read an env var, trimming whitespace; return $default when unset/blank.
$env = static function (string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v === false || trim($v) === '') return $default;
    return trim($v);
};

return [
    'host'         => $env('SMTP_HOST', 'smtp.gmail.com'),
    'port'         => (int) $env('SMTP_PORT', '587'),
    'secure'       => $env('SMTP_SECURE', 'tls'),  // 'tls' (587, STARTTLS) or 'ssl' (465)
    'username'     => $env('SMTP_USERNAME', ''),    // your full Gmail address
    'app_password' => $env('SMTP_APP_PASSWORD', ''),// 16-char Gmail app password (no spaces)
    'from_email'   => $env('SMTP_FROM_EMAIL', ''),  // defaults to username when blank
    'from_name'    => $env('SMTP_FROM_NAME', 'Vast Solutions'),
];
