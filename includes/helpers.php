<?php
/**
 * Shared backend helpers: code generation, audit logging, notifications,
 * project updates, and small formatters. Used by every write endpoint.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/**
 * Generate the next human code for a given prefix, e.g. REQ-2026-001.
 * Prefix is mapped to its table/column here (trusted, not user input).
 */
function next_code(string $prefix): string
{
    static $map = [
        'REQ' => ['project_requests', 'request_code'],
        'QT'  => ['quotations',       'quote_code'],
        'PRJ' => ['projects',         'project_code'],
    ];
    if (!isset($map[$prefix])) {
        throw new InvalidArgumentException("Unknown code prefix: {$prefix}");
    }
    [$table, $col] = $map[$prefix];
    $year = date('Y');
    $like = "{$prefix}-{$year}-%";

    $stmt = db()->prepare(
        "SELECT {$col} FROM {$table} WHERE {$col} LIKE ? ORDER BY {$col} DESC LIMIT 1"
    );
    $stmt->execute([$like]);
    $last = $stmt->fetchColumn();

    $seq = 1;
    if ($last && preg_match('/-(\d+)$/', $last, $m)) {
        $seq = (int) $m[1] + 1;
    }
    return sprintf('%s-%s-%03d', $prefix, $year, $seq);
}

/**
 * Record an action in the audit trail using the current user.
 */
function log_audit(string $module, string $action, ?string $details = null): void
{
    $u = current_user();
    try {
        db()->prepare(
            "INSERT INTO audit_logs (user_id, user_name, role, action, module, details)
             VALUES (?,?,?,?,?,?)"
        )->execute([
            $u['id']        ?? null,
            $u['full_name'] ?? 'System',
            $u['role']      ?? null,
            $action,
            $module,
            $details,
        ]);
    } catch (Throwable $e) {
        // Never let audit logging break the main action.
    }
}

/**
 * Create a notification. $opts keys (all optional except title):
 *   user_id, target_role, type, title, message, link, severity,
 *   project_id, quotation_id
 */
function notify(array $opts): void
{
    try {
        db()->prepare(
            "INSERT INTO notifications
               (user_id, target_role, type, title, message, link, severity, project_id, quotation_id)
             VALUES (:user_id,:target_role,:type,:title,:message,:link,:severity,:project_id,:quotation_id)"
        )->execute([
            ':user_id'      => $opts['user_id']      ?? null,
            ':target_role'  => $opts['target_role']  ?? null,
            ':type'         => $opts['type']         ?? 'system',
            ':title'        => $opts['title']        ?? '',
            ':message'      => $opts['message']      ?? null,
            ':link'         => $opts['link']         ?? null,
            ':severity'     => $opts['severity']     ?? 'info',
            ':project_id'   => $opts['project_id']   ?? null,
            ':quotation_id' => $opts['quotation_id'] ?? null,
        ]);
    } catch (Throwable $e) {
        // Non-fatal.
    }
}

/** Find the client user_id that owns a project (via its customer), or null. */
function project_client_user_id(int $projectId): ?int
{
    $stmt = db()->prepare(
        "SELECT c.user_id
           FROM projects p
           JOIN customers c ON c.id = p.customer_id
          WHERE p.id = ?"
    );
    $stmt->execute([$projectId]);
    $uid = $stmt->fetchColumn();
    return $uid ? (int) $uid : null;
}

/**
 * Post a project update (timeline entry) and optionally notify the client.
 * Returns the new update id.
 */
function add_project_update(int $projectId, string $text, bool $notifyClient = true, ?string $attachmentPath = null): int
{
    $u = current_user();
    $stmt = db()->prepare(
        "INSERT INTO project_updates (project_id, author_id, author_name, update_text, attachment_path)
         VALUES (?,?,?,?,?)"
    );
    $stmt->execute([
        $projectId,
        $u['id']        ?? null,
        $u['full_name'] ?? 'Vast Solutions',
        $text,
        $attachmentPath,
    ]);
    $id = (int) db()->lastInsertId();

    if ($notifyClient) {
        $clientId = project_client_user_id($projectId);
        if ($clientId) {
            notify([
                'user_id'    => $clientId,
                'type'       => 'status_update',
                'title'      => 'Project update',
                'message'    => mb_strimwidth($text, 0, 90, '…'),
                'link'       => "my_projects.php?view={$projectId}",
                'severity'   => 'info',
                'project_id' => $projectId,
            ]);
        }
    }
    return $id;
}

/* ---------------------------------------------------------------------
 *  Completion confirmation — when a project is marked "completed" the
 *  client is asked to confirm they received it (like a shopping app's
 *  "Order received"). If they don't respond within this many days it is
 *  auto-confirmed.
 * ------------------------------------------------------------------- */

/** Days after which an unconfirmed completed project auto-confirms. */
const COMPLETION_AUTO_CONFIRM_DAYS = 7;

/**
 * Self-migration: make sure the two completion-tracking columns exist on
 * `projects`. Runs once per request; safe to call anywhere. Lets the feature
 * work on hosts (e.g. Railway) without a manual ALTER.
 */
function ensure_completion_columns(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $pdo  = db();
        $cols = $pdo->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('completion_notified_at', $cols, true)) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN completion_notified_at DATETIME NULL DEFAULT NULL");
        }
        if (!in_array('client_confirmed_at', $cols, true)) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN client_confirmed_at DATETIME NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // Non-fatal — the feature degrades gracefully if the ALTER can't run.
    }
}

/**
 * Sweep completed-but-unconfirmed projects and auto-confirm any that have sat
 * past the deadline. Cheap enough to call on relevant page loads (monitoring,
 * my_projects, dashboard) since there's no reliable cron here.
 */
function auto_confirm_completions(): void
{
    ensure_completion_columns();
    try {
        $pdo  = db();
        $days = (int) COMPLETION_AUTO_CONFIRM_DAYS;
        $rows = $pdo->query(
            "SELECT id, project_code, project_name FROM projects
              WHERE status = 'completed'
                AND client_confirmed_at IS NULL
                AND completion_notified_at IS NOT NULL
                AND completion_notified_at < (NOW() - INTERVAL {$days} DAY)"
        )->fetchAll();
        foreach ($rows as $r) {
            $pid = (int) $r['id'];
            $pdo->prepare("UPDATE projects SET client_confirmed_at = NOW() WHERE id = ?")->execute([$pid]);
            $pdo->prepare(
                "INSERT INTO project_updates (project_id, author_id, author_name, update_text)
                 VALUES (?, NULL, 'System', ?)"
            )->execute([$pid, "Completion auto-confirmed after {$days} days without a client response."]);
            notify([
                'target_role' => 'Admin',
                'type'        => 'system',
                'title'       => 'Project auto-confirmed complete',
                'message'     => "{$r['project_name']} ({$r['project_code']})",
                'link'        => 'monitoring.php',
                'severity'    => 'info',
                'project_id'  => $pid,
            ]);
        }
    } catch (Throwable $e) {
        // Non-fatal.
    }
}

/* ---------------------------------------------------------------------
 *  OTP (one-time codes) — client sign-up email verification.
 * ------------------------------------------------------------------- */

/** Generate a zero-padded numeric one-time code. */
function generate_otp(int $len = 6): string
{
    $max = (10 ** $len) - 1;
    return str_pad((string) random_int(0, $max), $len, '0', STR_PAD_LEFT);
}

/**
 * Ensure the otp_codes.purpose enum allows every purpose the app issues codes
 * for (signup, reset). Self-migrating so it also works on an already-deployed
 * database (e.g. Railway) without a manual ALTER.
 */
function ensure_otp_purposes(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $col = db()->query("SHOW COLUMNS FROM otp_codes LIKE 'purpose'")->fetch();
        if ($col && stripos((string) $col['Type'], "'reset'") === false) {
            db()->exec("ALTER TABLE otp_codes MODIFY purpose ENUM('signup','reset') NOT NULL DEFAULT 'signup'");
        }
    } catch (Throwable $e) { /* non-fatal */ }
}

/**
 * Issue a fresh OTP for an email: invalidates any prior unconsumed codes,
 * inserts a new one valid ~10 minutes, and returns the code.
 */
function create_otp(?int $userId, string $email, string $purpose = 'signup', int $ttlMinutes = 10): string
{
    if ($purpose !== 'signup') ensure_otp_purposes(); // widen the enum on demand
    $code = generate_otp();
    try {
        // Consume any outstanding codes for this email+purpose so only the latest works.
        db()->prepare(
            "UPDATE otp_codes SET consumed_at = NOW()
              WHERE email = ? AND purpose = ? AND consumed_at IS NULL"
        )->execute([$email, $purpose]);

        db()->prepare(
            "INSERT INTO otp_codes (user_id, email, code, purpose, expires_at)
             VALUES (?,?,?,?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        )->execute([$userId, $email, $code, $purpose, $ttlMinutes]);
    } catch (Throwable $e) {
        // Non-fatal; caller treats an empty return as failure if needed.
    }
    return $code;
}

/**
 * Verify a submitted code for an email. Returns true only if an unconsumed,
 * unexpired matching row exists; marks it consumed on success.
 */
function verify_otp(string $email, string $code, string $purpose = 'signup'): bool
{
    try {
        $stmt = db()->prepare(
            "SELECT id FROM otp_codes
              WHERE email = ? AND code = ? AND purpose = ?
                AND consumed_at IS NULL AND expires_at >= NOW()
              ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$email, $code, $purpose]);
        $id = $stmt->fetchColumn();
        if (!$id) return false;
        db()->prepare("UPDATE otp_codes SET consumed_at = NOW() WHERE id = ?")->execute([$id]);
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/**
 * Company branding (name, tagline, logo). Reads company_settings, self-migrating
 * the `tagline` column and singleton row so it works on a deployed DB too.
 * The row is cached per request.
 */
function ensure_company_branding(): array
{
    static $row = null;
    if ($row !== null) return $row;
    try {
        $col = db()->query("SHOW COLUMNS FROM company_settings LIKE 'tagline'")->fetch();
        if (!$col) {
            db()->exec("ALTER TABLE company_settings ADD COLUMN tagline VARCHAR(255) NULL AFTER company_name");
        }
        db()->exec("INSERT IGNORE INTO company_settings (id, company_name) VALUES (1, 'Vast Solutions')");
        $row = db()->query("SELECT * FROM company_settings WHERE id = 1")->fetch() ?: [];
    } catch (Throwable $e) {
        $row = [];
    }
    return $row;
}

/** The configured company name, falling back to the default. */
function company_name(): string
{
    $n = trim((string) (ensure_company_branding()['company_name'] ?? ''));
    return $n !== '' ? $n : 'Vast Solutions';
}

/** The configured landing-page tagline, falling back to the default. */
function company_tagline(): string
{
    $t = trim((string) (ensure_company_branding()['tagline'] ?? ''));
    return $t !== '' ? $t : 'Every Inch, Endless Possibilities';
}

/**
 * URL to the system logo. Uses the uploaded logo when its file is present,
 * otherwise the committed default (style/assets/logo.jpg) — so a custom logo
 * lost to an ephemeral filesystem (e.g. after a Railway redeploy) falls back
 * gracefully instead of breaking.
 */
function company_logo_url(): string
{
    $base = defined('BASE_URL') ? BASE_URL : '';
    $lp = trim((string) (ensure_company_branding()['logo_path'] ?? ''));
    if ($lp !== '' && is_file(__DIR__ . '/../' . $lp)) {
        return $base . '/' . $lp;
    }
    return $base . '/style/assets/logo.jpg';
}

/** Peso formatter. */
function peso($n): string
{
    return '₱' . number_format((float) $n, 2);
}

/** "x minutes/hours/days ago" from a datetime string. */
function time_ago(?string $datetime): string
{
    if (!$datetime) return '';
    $ts = strtotime($datetime);
    if (!$ts) return '';
    $diff = time() - $ts;
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M d, Y', $ts);
}
