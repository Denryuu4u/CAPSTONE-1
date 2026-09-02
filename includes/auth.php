<?php
/**
 * Central authentication + session bootstrap.
 *
 * Include this at the top of every page/endpoint:
 *     require_once __DIR__ . '/../includes/auth.php';
 *     require_login();   // pages only — enforced when DEV_MODE is false
 *
 * DEV_MODE:
 *   true  -> the system auto-"logs in" a real user from the database so you can
 *            use every page without signing in. Default identity is the admin;
 *            append ?dev_user=client (or =admin) to switch, which persists in
 *            the session. This is for development/testing only.
 *   false -> no auto-login. require_login() redirects guests to the login page,
 *            so real login/signup becomes the final step.
 *
 * Toggle it with the DEV_MODE environment variable (set it on your host, e.g.
 * Railway → Variables → DEV_MODE=false, to disable auto-login in production).
 * When the env var is unset it defaults to true for local XAMPP development.
 * Accepts: false / 0 / off / no  (case-insensitive) to turn it off.
 */

// Resolve DEV_MODE from the environment: unset -> true (local dev default);
// false / 0 / off / no (case-insensitive) -> false (e.g. production on Railway).
$__devEnv = getenv('DEV_MODE');
$__devOff = $__devEnv !== false
    && in_array(strtolower(trim($__devEnv)), ['false', '0', 'off', 'no'], true);
define('DEV_MODE', !$__devOff);
unset($__devEnv, $__devOff);

// Base URL path the app is served under, no trailing slash. Local XAMPP serves
// it at /CAPSTONE-1; when hosting at a domain root (e.g. Railway) set BASE_URL=""
// (an empty env var) so absolute links resolve to "/...". Unset -> local default.
$__base = getenv('BASE_URL');
define('BASE_URL', $__base !== false ? rtrim(trim($__base), '/') : '/CAPSTONE-1');
unset($__base);

// Emails of the seeded identities used while DEV_MODE is on (one per role).
const DEV_SUPERADMIN_EMAIL = 'admin@vastsolutions.com';
const DEV_ADMIN_EMAIL      = 'admin2@vastsolutions.com';
const DEV_STAFF_EMAIL      = 'staff@vastsolutions.com';
const DEV_CLIENT_EMAIL     = 'client@demo.test';

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Populate the session from a users row (dev auto-login).
 */
function auth_set_user(array $row): void
{
    $_SESSION['user_id']   = (int) $row['id'];
    $_SESSION['full_name'] = $row['full_name'];
    $_SESSION['role']      = $row['role'];
}

// Real logins set $_SESSION['real_login'] — don't let DEV auto-login clobber them.
if (DEV_MODE && empty($_SESSION['real_login'])) {
    // Impersonate a seeded identity: ?dev_user=superadmin|admin|staff|client (persists).
    $devEmails = [
        'superadmin' => DEV_SUPERADMIN_EMAIL,
        'admin'      => DEV_ADMIN_EMAIL,
        'staff'      => DEV_STAFF_EMAIL,
        'client'     => DEV_CLIENT_EMAIL,
    ];
    if (isset($_GET['dev_user']) && isset($devEmails[$_GET['dev_user']])) {
        $_SESSION['dev_user'] = $_GET['dev_user'];
    }
    $want  = $_SESSION['dev_user'] ?? 'superadmin';
    $email = $devEmails[$want] ?? DEV_SUPERADMIN_EMAIL;

    // (Re)load the identity when nobody is logged in or the dev identity changed.
    if (empty($_SESSION['user_id']) || ($_SESSION['dev_active'] ?? '') !== $want) {
        try {
            $stmt = db()->prepare("SELECT id, full_name, role FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $row = $stmt->fetch();
            if ($row) {
                auth_set_user($row);
                $_SESSION['dev_active'] = $want;
            }
        } catch (Throwable $e) {
            // DB unavailable — leave the session as-is; pages fall back to defaults.
        }
    }
}

/**
 * Current logged-in user, or null.
 * @return array{id:int, full_name:string, role:string}|null
 */
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id'        => (int) $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
    ];
}

/** Convenience role check. */
function current_role(): string
{
    return $_SESSION['role'] ?? '';
}

/**
 * Guard a page: redirect guests to the login page.
 * No-op while DEV_MODE is true (auto-login guarantees a user).
 *
 * @param string $loginPath Relative path to login.php from the calling page
 *                          (pages live one level deep, so the default fits).
 */
function require_login(string $loginPath = '../login.php'): void
{
    if (DEV_MODE) {
        return;
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $loginPath);
        exit;
    }
}

/**
 * Guard an admin/staff-only page. No-op while DEV_MODE is true.
 */
function require_role(array $roles, string $loginPath = '../login.php'): void
{
    if (DEV_MODE) {
        return;
    }
    if (empty($_SESSION['user_id'])) {
        header('Location: ' . $loginPath);
        exit;
    }
    if (!in_array($_SESSION['role'] ?? '', $roles, true)) {
        http_response_code(403);
        echo 'Access denied.';
        exit;
    }
}

/* =====================================================================
 *  ROLE-BASED PAGE ACCESS  (Super Admin / Admin / Staff)
 *  Single source of truth for which back-office role sees which page.
 *  Enforced by role regardless of DEV_MODE — the DEV switcher sets a real
 *  role in session, so switching roles demonstrates the separation.
 * ===================================================================== */

/** Back-office roles (everyone who may reach admin/*). Clients are excluded. */
const BACKOFFICE_ROLES = ['Super Admin', 'Admin', 'Staff'];

/** page key (matches $active_page) => roles allowed to open it. */
function role_pages(): array
{
    $all = BACKOFFICE_ROLES;                       // Super Admin, Admin, Staff
    $adminUp = ['Super Admin', 'Admin'];
    $superOnly = ['Super Admin'];
    return [
        'dashboard'        => $all,
        'project_requests' => $all,
        'quotations'       => $all,
        'summarization'    => $all,
        'monitoring'       => $all,
        'customers'        => $all,
        'reports'          => $adminUp,
        'settings'         => $adminUp,   // company/website/costing/gallery sections
        'settings_self'    => $all,       // opening Settings for own profile + password
        'archive'          => $adminUp,
        'user_management'  => $superOnly,
        'audit_logs'       => $superOnly,
    ];
}

/** Can the given role (defaults to the current user) open this page? */
function role_can(string $pageKey, ?string $role = null): bool
{
    $role  = $role ?? current_role();
    $map   = role_pages();
    $allow = $map[$pageKey] ?? BACKOFFICE_ROLES;   // unknown key = any back-office role
    return in_array($role, $allow, true);
}

/**
 * Guard an admin page (or endpoint) by page permission.
 * Guests → login. Wrong role → 403 (HTML page, or JSON when $json=true).
 */
function require_page(string $pageKey, bool $json = false, string $loginPath = '../login.php'): void
{
    if (empty($_SESSION['user_id'])) {
        if ($json) { http_response_code(401); echo json_encode(['ok' => false, 'error' => 'Not signed in.']); }
        else       { header('Location: ' . $loginPath); }
        exit;
    }
    if (!role_can($pageKey)) {
        http_response_code(403);
        if ($json) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'You do not have permission to do this.']);
        } else {
            echo '<!doctype html><html><head><meta charset="utf-8"><title>Access denied</title>'
               . '<style>body{font-family:Inter,system-ui,sans-serif;background:#f3f4f6;color:#111827;'
               . 'display:flex;min-height:100vh;align-items:center;justify-content:center;margin:0}'
               . '.card{background:#fff;border:1px solid #e5e7eb;border-radius:14px;padding:36px 40px;text-align:center;max-width:420px}'
               . 'h1{font-size:1.15rem;margin:0 0 8px}p{color:#6b7280;font-size:.9rem;margin:0 0 18px}'
               . 'a{display:inline-block;background:#0D9676;color:#fff;text-decoration:none;padding:9px 18px;border-radius:8px;font-size:.85rem}</style>'
               . '</head><body><div class="card"><h1>Access denied</h1>'
               . '<p>Your role (' . htmlspecialchars(current_role()) . ') doesn\'t have access to this page.</p>'
               . '<a href="admin-dashboard.php">Back to dashboard</a></div></body></html>';
        }
        exit;
    }
}

/** Where a user should land after signing in, based on role. */
function role_home(?string $role = null): string
{
    $role = $role ?? current_role();
    return in_array($role, BACKOFFICE_ROLES, true)
        ? BASE_URL . '/admin/admin-dashboard.php'
        : BASE_URL . '/user-dashboard/dashboard.php';
}
