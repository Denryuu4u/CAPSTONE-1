<?php
/**
 * One-time setup of a CLEAN database on a remote MySQL server (e.g. a new Railway
 * deployment). Runs from your computer and connects only to the URL you pass —
 * local databases are refused, so your XAMPP data is never touched.
 *
 * Usage (CLI only):
 *   php database/import_clean.php "mysql://USER:PASS@HOST:PORT/DBNAME"
 *   php database/import_clean.php "mysql://..." --dry-run   (show plan, don't connect)
 *
 * On Railway, use the MySQL service's MYSQL_PUBLIC_URL (Variables tab).
 *
 * What it does:
 *   1. Refuses to run if the target database already has tables (the schema file
 *      drops tables, so this protects existing data). Pass --force to override.
 *   2. Imports database/vast_solutions.sql into the database named in the URL
 *      (the file's CREATE DATABASE / USE lines are skipped).
 *   3. Removes the sample client (client@demo.test), its customer record and its
 *      two sample projects, so the system starts clean. Kept: the back-office
 *      accounts, company settings, material library and legal documents.
 */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function out(string $s = ''): void { fwrite(STDOUT, $s . PHP_EOL); }
function fail(string $s): void { fwrite(STDERR, "ERROR: {$s}" . PHP_EOL); exit(1); }

$args   = array_slice($argv, 1);
$dryRun = in_array('--dry-run', $args, true);
$force  = in_array('--force', $args, true);
$urlArg = array_values(array_filter($args, fn($a) => strpos($a, '--') !== 0))[0] ?? (getenv('MYSQL_URL') ?: '');

if ($urlArg === '') {
    fail("Pass the database URL, e.g.\n  php database/import_clean.php \"mysql://root:PASS@HOST.proxy.rlwy.net:PORT/railway\"");
}

$u = parse_url($urlArg);
if (!$u || ($u['scheme'] ?? '') !== 'mysql' || empty($u['host']) || empty($u['path']) || trim($u['path'], '/') === '') {
    fail('That does not look like a mysql://USER:PASS@HOST:PORT/DBNAME URL.');
}
$host = $u['host'];
$port = (int) ($u['port'] ?? 3306);
$user = urldecode($u['user'] ?? 'root');
$pass = urldecode($u['pass'] ?? '');
$name = trim($u['path'], '/');

if (in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '[::1]'], true)) {
    fail("Target is {$host} — this script only sets up remote databases and won't touch your local one.");
}

$sqlFile = __DIR__ . '/vast_solutions.sql';
$sql = @file_get_contents($sqlFile);
if ($sql === false) fail("Can't read {$sqlFile}");

// Import into the database from the URL, not the file's hard-coded `vast_solutions`.
$sql = preg_replace('/^\s*CREATE DATABASE\b[^;]*;\s*$/mi', '', $sql);
$sql = preg_replace('/^\s*USE\s+`?\w+`?\s*;\s*$/mi', '', $sql);

out("Target:  {$user}@{$host}:{$port}/{$name}  (password hidden)");
out("Schema:  database/vast_solutions.sql (" . number_format(strlen($sql)) . " bytes)");
if ($dryRun) { out('Dry run — nothing was changed.'); exit(0); }

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw on every error
try {
    $db = mysqli_init();
    $db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
    $db->real_connect($host, $user, $pass, $name, $port);
    $db->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    fail('Could not connect: ' . $e->getMessage());
}

// 1. Safety: never overwrite a database that already has data.
$existing = [];
$r = $db->query('SHOW TABLES');
while ($row = $r->fetch_row()) $existing[] = $row[0];
if ($existing && !$force) {
    fail("Database `{$name}` already has " . count($existing) . " table(s) (" . implode(', ', array_slice($existing, 0, 5))
       . (count($existing) > 5 ? ', …' : '') . ").\nImporting would wipe them. Re-run with --force only if you're sure.");
}

// 2. Import the schema + seed (multi_query lets the server parse the whole file).
out('Importing schema…');
$stmt = 0;
try {
    $db->multi_query($sql);
    do {
        $stmt++;
        if ($res = $db->store_result()) $res->free();
    } while ($db->more_results() && $db->next_result());
} catch (mysqli_sql_exception $e) {
    fail("Import stopped at statement #{$stmt}: " . $e->getMessage());
}
out("  {$stmt} statements executed.");

// 3. Remove the sample client + its customer and projects.
out('Removing sample data…');
$demo = "'client@demo.test'";
$db->query("DELETE p FROM projects p JOIN customers c ON c.id = p.customer_id JOIN users u ON u.id = c.user_id WHERE u.email = {$demo}");
$db->query("DELETE c FROM customers c JOIN users u ON u.id = c.user_id WHERE u.email = {$demo}");
$db->query("DELETE FROM users WHERE email = {$demo}");
$db->query('ALTER TABLE projects AUTO_INCREMENT = 1');
$db->query('ALTER TABLE customers AUTO_INCREMENT = 1');

// Summary.
$tables = $db->query('SHOW TABLES')->num_rows;
$count  = fn(string $t) => (int) $db->query("SELECT COUNT(*) FROM `{$t}`")->fetch_row()[0];
out();
out("Done. {$tables} tables · {$count('projects')} projects · {$count('customers')} customers.");
out('Accounts:');
$r = $db->query("SELECT email, role FROM users ORDER BY FIELD(role,'Super Admin','Admin','Staff','Client'), id");
while ($row = $r->fetch_assoc()) out("  {$row['role']}: {$row['email']}");
out();
out('These accounts all use the password "admin123", which is visible in the repo.');
out('Sign in and change every password right away (Settings → Password).');
