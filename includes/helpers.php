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
        // Set when a client reports a completed project wasn't done properly. While
        // it's set (and unconfirmed), the project is disputed: no auto-confirm.
        if (!in_array('completion_issue_at', $cols, true)) {
            $pdo->exec("ALTER TABLE projects ADD COLUMN completion_issue_at DATETIME NULL DEFAULT NULL");
        }
    } catch (Throwable $e) {
        // Non-fatal — the feature degrades gracefully if the ALTER can't run.
    }
}

/**
 * Self-migrate the projects.prev_status column, used by the monitoring rollback:
 * every status change records the phase it came from so it can be undone.
 */
function ensure_prev_status_column(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = db()->query("SHOW COLUMNS FROM projects")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('prev_status', $cols, true)) {
            db()->exec("ALTER TABLE projects ADD COLUMN prev_status VARCHAR(30) NULL DEFAULT NULL AFTER status");
        }
    } catch (Throwable $e) {
        // Non-fatal — revert simply won't be offered if this can't run.
    }
}

/**
 * Self-migrate the revert_codes table. Super Admin / Admin generate single-use
 * codes (Settings) that Staff enter to authorize a monitoring rollback, instead
 * of an admin password.
 */
function ensure_revert_codes_table(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS revert_codes (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                code       VARCHAR(20) NOT NULL UNIQUE,
                is_used    TINYINT(1) NOT NULL DEFAULT 0,
                created_by INT UNSIGNED NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                used_by    INT UNSIGNED NULL,
                used_at    DATETIME NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        // Non-fatal.
    }
}

/* ---------------------------------------------------------------------
 * Client saved addresses — managed in Settings, picked per quote request
 * (the chosen one is copied onto the request/project/quotation as its
 * installation_address, so later edits don't rewrite past projects).
 * ------------------------------------------------------------------- */

/** Self-migrate the client_addresses table. */
function ensure_client_addresses_table(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        db()->exec(
            "CREATE TABLE IF NOT EXISTS client_addresses (
                id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id    INT UNSIGNED NOT NULL,
                label      VARCHAR(60)  DEFAULT NULL,
                address    VARCHAR(255) NOT NULL,
                is_default TINYINT(1)   NOT NULL DEFAULT 0,
                created_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY ix_caddr_user (user_id),
                CONSTRAINT fk_caddr_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    } catch (Throwable $e) {
        // Non-fatal — the address list simply stays empty.
    }
}

/**
 * A client's saved addresses, default first. The first time a client has none,
 * their old single address (customers.address / users.location, from before
 * multiple addresses existed) is carried over as the default, so nothing is lost.
 */
function client_addresses(int $userId): array
{
    ensure_client_addresses_table();
    $pdo  = db();
    $list = function () use ($pdo, $userId): array {
        $s = $pdo->prepare("SELECT id, label, address, is_default FROM client_addresses WHERE user_id = ? ORDER BY is_default DESC, id");
        $s->execute([$userId]);
        return $s->fetchAll();
    };
    try {
        $rows = $list();
        if (!$rows) {
            $s = $pdo->prepare(
                "SELECT COALESCE(NULLIF(TRIM(c.address), ''), NULLIF(TRIM(u.location), ''))
                   FROM users u LEFT JOIN customers c ON c.user_id = u.id
                  WHERE u.id = ? ORDER BY c.id LIMIT 1"
            );
            $s->execute([$userId]);
            $legacy = (string) $s->fetchColumn();
            if ($legacy !== '') {
                $pdo->prepare("INSERT INTO client_addresses (user_id, address, is_default) VALUES (?, ?, 1)")
                    ->execute([$userId, mb_substr($legacy, 0, 255)]);
                $rows = $list();
            }
        }
        return $rows;
    } catch (Throwable $e) {
        return [];
    }
}

/**
 * Keep exactly one default address, and mirror it onto users.location and
 * customers.address — what Customer Profiles and the quotation bill-to read.
 * With no addresses left, both are cleared (so the old value isn't re-imported).
 */
function sync_client_default_address(int $userId): void
{
    $pdo = db();
    $has = $pdo->prepare("SELECT COUNT(*) FROM client_addresses WHERE user_id = ? AND is_default = 1");
    $has->execute([$userId]);
    if (!(int) $has->fetchColumn()) {
        $pdo->prepare("UPDATE client_addresses SET is_default = 1 WHERE user_id = ? ORDER BY id LIMIT 1")->execute([$userId]);
    }
    $d = $pdo->prepare("SELECT address FROM client_addresses WHERE user_id = ? AND is_default = 1 ORDER BY id LIMIT 1");
    $d->execute([$userId]);
    $addr = $d->fetchColumn();
    $addr = ($addr === false || $addr === '') ? null : (string) $addr;
    $pdo->prepare("UPDATE users SET location = ? WHERE id = ?")->execute([$addr !== null ? mb_substr($addr, 0, 150) : null, $userId]);
    $pdo->prepare("UPDATE customers SET address = ? WHERE user_id = ?")->execute([$addr, $userId]);
}

/**
 * Give every verified, active client account a customer record, so self-registered
 * clients appear in Customer Profiles straight away — not only after their first
 * quote request (which used to be the only place the record was created). Pass a
 * user id to handle just that account (called on email verification); with no
 * argument it backfills everyone who's missing one (called by customers.php).
 */
function ensure_client_customers(?int $userId = null): void
{
    try {
        $sql = "INSERT INTO customers (user_id, name, contact_person, email, phone, address)
                SELECT u.id, u.full_name, u.full_name, u.email, NULLIF(u.phone, ''), NULLIF(u.location, '')
                  FROM users u
                  LEFT JOIN customers c ON c.user_id = u.id
                 WHERE u.role = 'Client' AND u.email_verified = 1 AND u.is_archived = 0
                   AND c.id IS NULL";
        if ($userId !== null) {
            db()->prepare($sql . ' AND u.id = ?')->execute([$userId]);
        } else {
            db()->exec($sql);
        }
    } catch (Throwable $e) {
        // Non-fatal — a quote request still creates the record as a fallback.
    }
}

/**
 * Self-migrate project_updates.is_client — marks timeline entries a client posted
 * (their own message/comment) so both sides can distinguish them from team updates.
 */
function ensure_client_comment_column(): void
{
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = db()->query("SHOW COLUMNS FROM project_updates")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('is_client', $cols, true)) {
            db()->exec("ALTER TABLE project_updates ADD COLUMN is_client TINYINT(1) NOT NULL DEFAULT 0");
        }
        // client_read_at: when the back office has seen a client's message (NULL = unread).
        if (!in_array('client_read_at', $cols, true)) {
            db()->exec("ALTER TABLE project_updates ADD COLUMN client_read_at DATETIME NULL DEFAULT NULL");
        }
    } catch (Throwable $e) { /* non-fatal */ }
}

/** Generate a fresh, unambiguous single-use revert code (e.g. "R7K2QX"). */
function generate_revert_code(): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // no 0/O/1/I
    for ($tries = 0; $tries < 10; $tries++) {
        $code = '';
        for ($i = 0; $i < 6; $i++) $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        try {
            $st = db()->prepare("SELECT 1 FROM revert_codes WHERE code = ? LIMIT 1");
            $st->execute([$code]);
            if (!$st->fetchColumn()) return $code;
        } catch (Throwable $e) {
            return $code;
        }
    }
    return $code; // extremely unlikely to loop out
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
                AND completion_issue_at IS NULL
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

/**
 * Self-migrate the columns behind the client counter-offer flow: widen
 * quotations.status to include 'Countered' and add the counter fields.
 * Safe to call repeatedly (checks before altering), so features work on
 * Railway without a manual migration.
 */
function ensure_counter_offer_columns(): void
{
    static $done = false;
    if ($done) return;
    try {
        $cols = db()->query("SHOW COLUMNS FROM quotations")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('counter_amount', $cols, true)) {
            db()->exec("ALTER TABLE quotations ADD COLUMN counter_amount DECIMAL(12,2) NULL AFTER status");
        }
        if (!in_array('counter_comment', $cols, true)) {
            db()->exec("ALTER TABLE quotations ADD COLUMN counter_comment VARCHAR(1000) NULL AFTER counter_amount");
        }
        if (!in_array('counter_at', $cols, true)) {
            db()->exec("ALTER TABLE quotations ADD COLUMN counter_at DATETIME NULL AFTER counter_comment");
        }
        $status = db()->query("SHOW COLUMNS FROM quotations LIKE 'status'")->fetch();
        if ($status && stripos((string) ($status['Type'] ?? ''), "'Countered'") === false) {
            db()->exec("ALTER TABLE quotations MODIFY COLUMN status
                        ENUM('Sent','Accepted','Approved','Rejected','Countered') NOT NULL DEFAULT 'Sent'");
        }
        $done = true;
    } catch (Throwable $e) {
        // leave $done false so a later call can retry
    }
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
