<?php
/**
 * Outgoing mail settings (OTP verification emails).
 *
 * TWO transports are supported — the mailer auto-picks one:
 *   • BREVO (HTTP API)  — works on Railway (SMTP ports are blocked there).
 *   • GMAIL SMTP        — fine for local XAMPP.
 * If BREVO_API_KEY is set, Brevo is used; otherwise it falls back to Gmail SMTP.
 * If neither is configured, the app runs in DEMO mode (OTP written to
 * uploads/otp_log.txt) so signup still works end-to-end for testing.
 *
 * === BREVO SETUP (recommended for Railway) ====================================
 * 1. Create a free Brevo account (brevo.com) — 300 emails/day, no card.
 * 2. Verify a sender: Senders & IPs → Senders → add your Gmail and click the
 *    confirmation link Brevo emails you. That address becomes MAIL_FROM_EMAIL.
 * 3. Create an API key: SMTP & API → API Keys → generate. That's BREVO_API_KEY.
 * 4. On Railway → Variables, set:
 *      BREVO_API_KEY    = xkeysib-...        (the API key)
 *      MAIL_FROM_EMAIL  = you@gmail.com      (the VERIFIED sender)
 *      MAIL_FROM_NAME   = Vast Solutions     (optional)
 *    DO NOT paste the API key into this file and commit it — use env vars.
 *
 * === GMAIL SMTP (local only) ==================================================
 * Set SMTP_USERNAME + SMTP_APP_PASSWORD (a Gmail App Password). Optional:
 * SMTP_HOST/PORT/SECURE (defaults: smtp.gmail.com / 587 / tls; use 465 / ssl
 * as a fallback).
 * ============================================================================
 */

// Read an env var, trimming whitespace; return $default when unset/blank.
$env = static function (string $key, string $default = ''): string {
    $v = getenv($key);
    if ($v === false || trim($v) === '') return $default;
    return trim($v);
};

return [
    // Force a transport ('brevo' or 'smtp'); blank = auto (brevo if key set).
    'provider'      => $env('MAIL_PROVIDER', ''),

    // Sender identity. Brevo requires a VERIFIED sender here.
    'from_email'    => $env('MAIL_FROM_EMAIL', $env('SMTP_FROM_EMAIL', $env('SMTP_USERNAME', ''))),
    'from_name'     => $env('MAIL_FROM_NAME', $env('SMTP_FROM_NAME', 'Vast Solutions')),

    // --- Brevo (HTTP API) ---
    'brevo_api_key' => $env('BREVO_API_KEY', ''),

    // --- Gmail SMTP (local fallback) ---
    'host'          => $env('SMTP_HOST', 'smtp.gmail.com'),
    'port'          => (int) $env('SMTP_PORT', '587'),
    'secure'        => $env('SMTP_SECURE', 'tls'), // 'tls' (587) or 'ssl' (465)
    'username'      => $env('SMTP_USERNAME', ''),
    'app_password'  => $env('SMTP_APP_PASSWORD', ''),
];
