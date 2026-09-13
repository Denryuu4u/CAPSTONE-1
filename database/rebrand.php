<?php
/**
 * Temporary rebrand toggle:  Vast Solutions  <->  Symetry Design
 * ---------------------------------------------------------------------------
 * Swaps, in one step and fully reversibly:
 *   • company name + tagline + logo   (company_settings row 1)
 *   • the 3 back-office account emails (users table)
 *   • the DEV-login constants          (includes/auth.php) so the dev switcher
 *     and DEV_MODE auto-login keep matching the account emails
 *
 * Every other brand mention in the app (page titles, sidebars, the landing
 * page, login/signup, reports, quotation views, OTP emails) reads the company
 * name/logo from the database, so those follow this toggle automatically.
 *
 * Passwords are NOT changed — the renamed accounts keep their existing password.
 *
 * Usage (command line only — never exposed over the web):
 *   C:\xampp\php\php.exe database/rebrand.php status
 *   C:\xampp\php\php.exe database/rebrand.php apply     # -> Symetry Design
 *   C:\xampp\php\php.exe database/rebrand.php revert    # -> Vast Solutions
 *
 * The committed baseline stays "Vast Solutions", so this rebrand lives only in
 * the database + working tree and is undone with a single `revert` (or by
 * `git checkout -- includes/auth.php` for the code half).
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("This script is command-line only.\n");
}

require_once __DIR__ . '/../includes/helpers.php'; // db() + ensure_company_branding()

$ROOT = dirname(__DIR__);
$AUTH = $ROOT . '/includes/auth.php';

$VAST = ['name' => 'Vast Solutions',  'tagline' => 'Every Inch, Endless Possibilities'];
$SYM  = ['name' => 'Symetry Design',  'tagline' => 'Precision in Every Line, Perfection in Every Space.'];

// old (Vast) email  =>  new (Symetry) email
$EMAILS = [
    'admin@vastsolutions.com'  => 'admin@symetrydesign.com',
    'admin2@vastsolutions.com' => 'admin2@symetrydesign.com',
    'staff@vastsolutions.com'  => 'staff@symetrydesign.com',
];

// Candidate committed logo file for the Symetry brand (first that exists wins).
$SYM_LOGO_CANDIDATES = [
    'style/assets/symetry-logo.png',
    'style/assets/symetry-logo.jpg',
    'style/assets/symetry-logo.jpeg',
    'style/assets/symetry-logo.webp',
    'style/assets/symetry-logo.svg',
];

function find_sym_logo(string $root, array $candidates): ?string
{
    foreach ($candidates as $rel) {
        if (is_file($root . '/' . $rel)) return $rel;
    }
    return null;
}

function current_brand(PDO $pdo): string
{
    $n = trim((string) $pdo->query("SELECT company_name FROM company_settings WHERE id = 1")->fetchColumn());
    if ($n === 'Symetry Design') return 'symetry';
    if ($n === 'Vast Solutions') return 'vast';
    return 'other (' . $n . ')';
}

function swap_auth_emails(string $authFile, array $from, array $to): int
{
    $src = file_get_contents($authFile);
    if ($src === false) { echo "  ! could not read includes/auth.php\n"; return 0; }
    $changed = 0;
    foreach ($from as $i => $oldEmail) {
        $newEmail = $to[$i];
        $needle = "'" . $oldEmail . "'";
        if (strpos($src, $needle) !== false) {
            $src = str_replace($needle, "'" . $newEmail . "'", $src);
            $changed++;
        }
    }
    if ($changed) file_put_contents($authFile, $src);
    return $changed;
}

$cmd = strtolower($argv[1] ?? 'status');
$pdo = db();
ensure_company_branding(); // makes sure row 1 + the tagline column exist

if ($cmd === 'status') {
    $row = $pdo->query("SELECT company_name, tagline, logo_path FROM company_settings WHERE id = 1")->fetch();
    echo "Current brand : " . current_brand($pdo) . "\n";
    echo "  company_name: " . ($row['company_name'] ?? '') . "\n";
    echo "  tagline     : " . ($row['tagline'] ?? '') . "\n";
    echo "  logo_path   : " . ($row['logo_path'] ?: '(default style/assets/logo.jpg)') . "\n";
    echo "Accounts:\n";
    foreach ($pdo->query("SELECT email, role FROM users ORDER BY id") as $u) {
        echo "  - {$u['email']}  ({$u['role']})\n";
    }
    $symLogo = find_sym_logo($ROOT, $SYM_LOGO_CANDIDATES);
    echo "Symetry logo file: " . ($symLogo ?: 'NOT FOUND — drop it at style/assets/symetry-logo.png then re-run apply') . "\n";
    exit;
}

if ($cmd === 'apply' || $cmd === 'revert') {
    $toSym = ($cmd === 'apply');
    $brand = $toSym ? $SYM : $VAST;

    // 1) Company name + tagline.
    if ($toSym) {
        // Logo: only set it if the committed Symetry logo file is present.
        $symLogo = find_sym_logo($ROOT, $SYM_LOGO_CANDIDATES);
        if ($symLogo !== null) {
            $pdo->prepare("UPDATE company_settings SET company_name = ?, tagline = ?, logo_path = ? WHERE id = 1")
                ->execute([$brand['name'], $brand['tagline'], $symLogo]);
            echo "Logo set to: {$symLogo}\n";
        } else {
            $pdo->prepare("UPDATE company_settings SET company_name = ?, tagline = ? WHERE id = 1")
                ->execute([$brand['name'], $brand['tagline']]);
            echo "! Symetry logo file not found — name/tagline changed, logo left as-is.\n";
            echo "  Drop the logo at style/assets/symetry-logo.png and run `apply` again to set it.\n";
        }
    } else {
        // Revert to the committed default logo (clear any custom logo_path).
        $pdo->prepare("UPDATE company_settings SET company_name = ?, tagline = ?, logo_path = NULL WHERE id = 1")
            ->execute([$brand['name'], $brand['tagline']]);
    }
    echo "Company name -> {$brand['name']}\n";
    echo "Tagline      -> {$brand['tagline']}\n";

    // 2) Account emails.
    $from = $toSym ? array_keys($EMAILS)   : array_values($EMAILS);
    $to   = $toSym ? array_values($EMAILS) : array_keys($EMAILS);
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE email = ?");
    $emailChanges = 0;
    foreach ($from as $i => $old) {
        $stmt->execute([$to[$i], $old]);
        if ($stmt->rowCount() > 0) { $emailChanges += $stmt->rowCount(); echo "  {$old} -> {$to[$i]}\n"; }
    }
    echo "Account emails changed: {$emailChanges}\n";

    // 3) DEV-login constants in includes/auth.php (keep them in sync with emails).
    $authChanges = swap_auth_emails($AUTH, $from, $to);
    echo "auth.php DEV constants updated: {$authChanges}\n";

    // Any active session still holding an old identity is refreshed on the next
    // dev-switch or real login; role/name display is unaffected.
    echo "\nDone. Now: " . current_brand($pdo) . "\n";
    if ($toSym) {
        echo "Log in with admin@symetrydesign.com (same password as before).\n";
        echo "To undo everything: php database/rebrand.php revert\n";
    }
    exit;
}

echo "Unknown command '{$cmd}'. Use: status | apply | revert\n";
exit(1);
