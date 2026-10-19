<?php
/** Connection-local timestamp regression check. No business rows are changed. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/helpers.php';
function timezone_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
$pdo = db();
timezone_check($pdo->query('SELECT @@session.time_zone')->fetchColumn() === '+08:00', 'Every application connection explicitly uses Philippine time');
timezone_check(date_default_timezone_get() === 'Asia/Manila', 'PHP uses the same Philippine timezone');
try {
    // Demonstrate the mismatch without changing the server or global timezone.
    $pdo->exec("SET SESSION time_zone = '+00:00'");
    $utcNow = $pdo->query('SELECT NOW()')->fetchColumn();
    timezone_check(time_ago($utcNow) === '8 hours ago', 'UTC database time reproduces the eight-hour display error');
    $pdo->exec("SET SESSION time_zone = '+08:00'");
    $now = $pdo->query('SELECT NOW()')->fetchColumn();
    timezone_check(abs(time() - strtotime($now)) < 5 && time_ago($now) === 'just now', 'New database timestamps display just now');
    $pdo->exec('CREATE TEMPORARY TABLE timezone_activity_test (id INT PRIMARY KEY, label VARCHAR(20), created_at DATETIME DEFAULT CURRENT_TIMESTAMP, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP)');
    $pdo->exec("INSERT INTO timezone_activity_test (id,label) VALUES (1,'New record')");
    $row = $pdo->query('SELECT * FROM timezone_activity_test WHERE id=1')->fetch();
    timezone_check(time_ago($row['created_at']) === 'just now' && time_ago($row['updated_at']) === 'just now', 'Created records have correct default timestamps');
    $pdo->exec("UPDATE timezone_activity_test SET updated_at=DATE_SUB(NOW(), INTERVAL 1 DAY) WHERE id=1");
    $pdo->exec("UPDATE timezone_activity_test SET label='Updated record' WHERE id=1");
    timezone_check(time_ago($pdo->query('SELECT updated_at FROM timezone_activity_test WHERE id=1')->fetchColumn()) === 'just now', 'Updating a record refreshes Last Updated without an eight-hour offset');
    timezone_check(time_ago(date('Y-m-d H:i:s', time()-3600)) === '1 hours ago', 'Older timestamps still retain their actual age');
} finally {
    $pdo->exec("SET SESSION time_zone = '+08:00'");
    $pdo->exec('DROP TEMPORARY TABLE IF EXISTS timezone_activity_test');
}
