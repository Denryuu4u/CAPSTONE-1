<?php
/**
 * Shared PDO connection to the `vast_solutions` database.
 *
 * Usage:  $pdo = db();
 * Returns a single shared PDO instance. Throws on error, fetches assoc arrays.
 *
 * Connection details come from environment variables when present (so the app
 * works on Railway / any host), falling back to XAMPP defaults for local dev:
 *   DB_HOST / DB_PORT / DB_NAME / DB_USER / DB_PASS
 * Railway's MySQL plugin also exposes MYSQLHOST/MYSQLPORT/MYSQLDATABASE/
 * MYSQLUSER/MYSQLPASSWORD — those are used automatically as a fallback.
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // env($primary, $railway, $default): first non-empty wins.
    $env = static function (string $primary, string $railway, string $default): string {
        foreach ([$primary, $railway] as $key) {
            $v = getenv($key);
            if ($v !== false && trim($v) !== '') return trim($v);
        }
        return $default;
    };

    $host = $env('DB_HOST', 'MYSQLHOST',     '127.0.0.1');
    $port = (int) $env('DB_PORT', 'MYSQLPORT', '3306');
    $name = $env('DB_NAME', 'MYSQLDATABASE', 'vast_solutions');
    $user = $env('DB_USER', 'MYSQLUSER',     'root');
    $pass = $env('DB_PASS', 'MYSQLPASSWORD', '');

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

    return $pdo;
}
