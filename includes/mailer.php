<?php
/**
 * Minimal dependency-free SMTP mailer (STARTTLS + AUTH LOGIN) for OTP emails.
 * No Composer / PHPMailer needed. Configure credentials in mail_config.php.
 *
 * Public API:
 *   send_otp_email(string $to, string $code, string $name = ''): array  // ['ok'=>bool,'error'=>?string,'demo'=>bool]
 */

require_once __DIR__ . '/auth.php'; // for DEV_MODE

/** Load SMTP config (cached). */
function mail_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $cfg = @include __DIR__ . '/mail_config.php';
        if (!is_array($cfg)) $cfg = [];
    }
    return $cfg;
}

/** True when SMTP credentials are present (otherwise we run in demo/log mode). */
function mail_configured(): bool
{
    $c = mail_config();
    return !empty($c['username']) && !empty($c['app_password']);
}

/** Append an OTP to the local demo log (used when SMTP isn't configured / for dev). */
function mail_log_otp(string $to, string $code): void
{
    $dir = __DIR__ . '/../uploads';
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    @file_put_contents(
        $dir . '/otp_log.txt',
        '[' . date('Y-m-d H:i:s') . "] {$to} -> {$code}\n",
        FILE_APPEND
    );
}

/**
 * Send the OTP email. Falls back to the demo log when SMTP isn't configured
 * (or if the real send fails), so the signup flow is always testable.
 * Always writes to the demo log while DEV_MODE is on.
 *
 * @return array{ok:bool, error:?string, demo:bool}
 */
function send_otp_email(string $to, string $code, string $name = ''): array
{
    if (DEV_MODE) mail_log_otp($to, $code);

    if (!mail_configured()) {
        if (!DEV_MODE) mail_log_otp($to, $code);
        return ['ok' => true, 'error' => null, 'demo' => true];
    }

    $c       = mail_config();
    $subject = 'Your Vast Solutions verification code';
    $safeName = $name !== '' ? htmlspecialchars($name) : 'there';
    $html = '<div style="font-family:Inter,Arial,sans-serif;max-width:480px;margin:auto">'
          . '<h2 style="color:#0D9676;margin:0 0 8px">Vast Solutions</h2>'
          . '<p>Hi ' . $safeName . ', use this code to verify your email and finish creating your account:</p>'
          . '<div style="font-size:30px;font-weight:800;letter-spacing:8px;background:#f0fdf9;border:1px solid #6ee7d0;'
          . 'border-radius:10px;padding:16px;text-align:center;color:#0a7a60;margin:16px 0">' . htmlspecialchars($code) . '</div>'
          . '<p style="color:#6b7280;font-size:13px">This code expires in 10 minutes. If you didn\'t request it, you can ignore this email.</p>'
          . '</div>';
    $text = "Vast Solutions verification code: {$code}\nThis code expires in 10 minutes.";

    $res = smtp_send($c, $to, $subject, $html, $text);
    if (!$res['ok']) {
        mail_log_otp($to, $code); // keep the flow usable even if SMTP fails
    }
    return ['ok' => $res['ok'], 'error' => $res['error'], 'demo' => false];
}

/**
 * Speak SMTP to the configured server and send one HTML+text message.
 * @return array{ok:bool, error:?string}
 */
function smtp_send(array $c, string $to, string $subject, string $html, string $text): array
{
    $host = $c['host'] ?? 'smtp.gmail.com';
    $port = (int) ($c['port'] ?? 587);
    $user = $c['username'];
    $pass = $c['app_password'];
    $from = $c['from_email'] ?: $user;
    $fromName = $c['from_name'] ?? 'Vast Solutions';
    $secure = $c['secure'] ?? 'tls';

    // 'ssl' = implicit TLS from the first byte (port 465); connect via ssl:// wrapper.
    $useImplicitSsl = ($secure === 'ssl');
    $target = ($useImplicitSsl ? 'ssl://' : '') . $host;

    $errno = 0; $errstr = '';
    $fp = @fsockopen($target, $port, $errno, $errstr, 15);
    if (!$fp) return ['ok' => false, 'error' => "Connect failed: {$errstr} ({$errno})"];
    stream_set_timeout($fp, 15);

    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            // Multiline replies have a '-' after the code; last line has a space.
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        return $data;
    };
    $expect = function (string $resp, string $codes) : bool {
        return in_array(substr($resp, 0, 3), explode('|', $codes), true);
    };
    $cmd = function (string $line) use ($fp, $read): string {
        fwrite($fp, $line . "\r\n");
        return $read();
    };

    try {
        if (!$expect($read(), '220')) throw new Exception('No 220 greeting');
        $ehlo = 'EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'localhost');
        if (!$expect($cmd($ehlo), '250')) throw new Exception('EHLO rejected');

        // STARTTLS upgrade (port 587). Skipped when already on an implicit-SSL socket (465).
        if ($secure === 'tls') {
            if (!$expect($cmd('STARTTLS'), '220')) throw new Exception('STARTTLS rejected');
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception('TLS negotiation failed (is openssl enabled?)');
            }
            if (!$expect($cmd($ehlo), '250')) throw new Exception('EHLO (post-TLS) rejected');
        }

        if (!$expect($cmd('AUTH LOGIN'), '334')) throw new Exception('AUTH LOGIN rejected');
        if (!$expect($cmd(base64_encode($user)), '334')) throw new Exception('Username rejected');
        if (!$expect($cmd(base64_encode($pass)), '235')) throw new Exception('Authentication failed (check app password)');

        if (!$expect($cmd('MAIL FROM:<' . $from . '>'), '250')) throw new Exception('MAIL FROM rejected');
        if (!$expect($cmd('RCPT TO:<' . $to . '>'), '250|251')) throw new Exception('RCPT TO rejected');
        if (!$expect($cmd('DATA'), '354')) throw new Exception('DATA rejected');

        $boundary = 'vast_' . bin2hex(random_bytes(8));
        $headers  = 'From: ' . mb_encode_mimeheader($fromName) . ' <' . $from . ">\r\n";
        $headers .= 'To: <' . $to . ">\r\n";
        $headers .= 'Subject: ' . mb_encode_mimeheader($subject) . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= 'Date: ' . date('r') . "\r\n";
        $headers .= 'Content-Type: multipart/alternative; boundary="' . $boundary . "\"\r\n";
        $body  = "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n";
        $body .= "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n";
        $body .= "--{$boundary}--\r\n";
        // Dot-stuffing for lines starting with '.'
        $message = preg_replace('/^\./m', '..', $headers . "\r\n" . $body);

        fwrite($fp, $message . "\r\n.\r\n");
        if (!$expect($read(), '250')) throw new Exception('Message not accepted');

        $cmd('QUIT');
        fclose($fp);
        return ['ok' => true, 'error' => null];
    } catch (Throwable $e) {
        @fclose($fp);
        return ['ok' => false, 'error' => $e->getMessage()];
    }
}
