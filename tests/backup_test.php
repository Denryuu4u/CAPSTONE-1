<?php
/** Run with PHP CLI. Creates and removes only a randomly named test database. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/backup.php';

function check(bool $condition, string $label): void
{
    if (!$condition) throw new RuntimeException('FAIL: ' . $label);
    echo 'PASS: ', $label, PHP_EOL;
}
function rejects(callable $run, string $label): void
{
    try { $run(); } catch (Throwable $e) { echo 'PASS: ', $label, PHP_EOL; return; }
    throw new RuntimeException('FAIL: accepted ' . $label);
}
function editCopy(string $source, string $dest, callable $edit): string
{
    copy($source, $dest);
    $z = new ZipArchive();
    if ($z->open($dest) !== true) throw new RuntimeException('Cannot edit test ZIP.');
    $m = json_decode($z->getFromName('manifest.json'), true, 32, JSON_THROW_ON_ERROR);
    $edit($z, $m);
    $z->addFromString('manifest.json', json_encode($m, JSON_THROW_ON_ERROR));
    $z->close();
    return $dest;
}

$pdo = db();
$original = $pdo->query('SELECT DATABASE()')->fetchColumn();
$dbName = 'vast_backup_test_' . bin2hex(random_bytes(6));
$root = sys_get_temp_dir() . '/vast-backup-test-' . bin2hex(random_bytes(6));
$created = false;
mkdir($root, 0700);
mkdir($root . '/uploads', 0700);
try {
    $pdo->exec('CREATE DATABASE `' . $dbName . '` CHARACTER SET utf8mb4');
    $created = true;
    $pdo->exec('USE `' . $dbName . '`');
    $pdo->exec("CREATE TABLE users (id INT PRIMARY KEY, full_name VARCHAR(100), role VARCHAR(30), status VARCHAR(20), is_archived TINYINT DEFAULT 0, password_hash VARCHAR(255)) ENGINE=InnoDB");
    $pdo->exec('CREATE TABLE projects (id INT PRIMARY KEY, user_id INT, note TEXT, payload BLOB, optional_text TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY (user_id) REFERENCES users(id)) ENGINE=InnoDB');
    $pdo->prepare('INSERT INTO users VALUES (1, ?, ?, ?, 0, ?)')->execute(['Test Super Admin', 'Super Admin', 'Active', password_hash('fixture-password', PASSWORD_DEFAULT)]);
    $note = "Quotes ' and \"; newlines\nUnicode: café — cabinet";
    $binary = "\x00\xff\x01blob";
    $pdo->prepare('INSERT INTO projects (id, user_id, note, payload, optional_text) VALUES (1, 1, ?, ?, NULL)')->execute([$note, $binary]);
    file_put_contents($root . '/uploads/design.pdf', 'original attachment');
    file_put_contents($root . '/uploads/otp_log.txt', 'excluded diagnostic log');
    $backup = new SystemBackup($pdo, $root . '/uploads', $root . '/uploads/.system-backups');
    $id = $backup->create('Test Super Admin');
    $path = $backup->path($id);
    check(count($backup->copies()) === 1, 'snapshot is listed');
    check($backup->copies()[0]['files'] === 1, 'diagnostic logs and backup folder are excluded');
    rejects(fn() => $backup->path('../outside.zip'), 'path traversal is rejected');
    $pdo->exec("UPDATE projects SET note='later edit'");
    file_put_contents($root . '/uploads/design.pdf', 'later attachment');
    file_put_contents($root . '/uploads/new.png', 'new attachment');
    $safety = $backup->restore($path, 'Test Super Admin');
    $restored = $pdo->query('SELECT * FROM projects')->fetch();
    check($restored['note'] === $note && $restored['payload'] === $binary && $restored['optional_text'] === null, 'restore preserves Unicode, binary data, quotes and NULL');
    check(file_get_contents($root . '/uploads/design.pdf') === 'original attachment' && !is_file($root . '/uploads/new.png'), 'uploaded files return to the selected snapshot');
    check(is_file($backup->path($safety)) && count($backup->copies()) === 2, 'recovery copy is saved before restore');
    check(file_get_contents($root . '/uploads/otp_log.txt') === 'excluded diagnostic log', 'restore preserves excluded diagnostic files');
    $backup->restore($backup->path($safety), 'Test Super Admin');
    check($pdo->query('SELECT note FROM projects')->fetchColumn() === 'later edit' && is_file($root . '/uploads/new.png'), 'recovery copy can restore the previous state');

    $bad = editCopy($path, $root . '/damaged.zip', function ($z, &$m) { $z->addFromString('uploads/design.pdf', 'tampered'); });
    $before = count($backup->copies());
    rejects(fn() => $backup->restore($bad, 'Test'), 'corrupt content is rejected before changes');
    check(count($backup->copies()) === $before && $pdo->query('SELECT note FROM projects')->fetchColumn() === 'later edit', 'invalid backup leaves data unchanged');
    $bad = editCopy($path, $root . '/unsafe.zip', function ($z, &$m) {
        $m['files']['../outside.php'] = ['entry' => 'uploads/../outside.php', 'sha256' => hash('sha256', 'bad')];
        $z->addFromString('uploads/../outside.php', 'bad');
    });
    rejects(fn() => $backup->restore($bad, 'Test'), 'unsafe archive paths are rejected');
    $bad = editCopy($path, $root . '/script.zip', function ($z, &$m) {
        $m['files']['bad.php.jpg'] = ['entry' => 'uploads/bad.php.jpg', 'sha256' => hash('sha256', 'bad')];
        $z->addFromString('uploads/bad.php.jpg', 'bad');
    });
    rejects(fn() => $backup->restore($bad, 'Test'), 'executable double extensions are rejected');
    $bad = editCopy($path, $root . '/schema.zip', function ($z, &$m) { $m['tables']['projects']['signature'] = str_repeat('0', 64); });
    rejects(fn() => $backup->restore($bad, 'Test'), 'incompatible schema is rejected');
    $bad = editCopy($path, $root . '/noadmin.zip', function ($z, &$m) {
        $line = json_decode(trim($z->getFromName('tables/users.jsonl')), true);
        $line[2] = base64_encode('Client');
        $text = json_encode($line) . "\n";
        $z->addFromString('tables/users.jsonl', $text);
        $m['tables']['users']['sha256'] = hash('sha256', $text);
    });
    rejects(fn() => $backup->restore($bad, 'Test'), 'backup without active Super Admin is rejected');
    $bad = editCopy($path, $root . '/orphan.zip', function ($z, &$m) {
        $line = json_decode(trim($z->getFromName('tables/projects.jsonl')), true);
        $line[1] = base64_encode('999');
        $text = json_encode($line) . "\n";
        $z->addFromString('tables/projects.jsonl', $text);
        $m['tables']['projects']['sha256'] = hash('sha256', $text);
    });
    rejects(fn() => $backup->restore($bad, 'Test'), 'broken foreign key relationships roll back');
    check($pdo->query('SELECT note FROM projects')->fetchColumn() === 'later edit', 'database transaction restores prior rows after failure');

    // Make a valid archive whose file destination is occupied by a directory.
    rename($root . '/uploads/design.pdf', $root . '/uploads/renamed.pdf');
    mkdir($root . '/uploads/design.pdf');
    rejects(fn() => $backup->restore($path, 'Test'), 'file replacement failure rolls back');
    check($pdo->query('SELECT note FROM projects')->fetchColumn() === 'later edit' && is_file($root . '/uploads/renamed.pdf') && is_file($root . '/uploads/new.png'), 'both rows and moved files survive failed restore');
    check((int) $pdo->query('SELECT @@FOREIGN_KEY_CHECKS')->fetchColumn() === 1, 'foreign key enforcement is re-enabled');
    $backup->delete($id);
    check(!is_file($path), 'saved copy deletion works');
    echo "All backup tests passed.\n";
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    $pdo->exec('USE `' . str_replace('`', '``', $original) . '`');
    if ($created && preg_match('/^vast_backup_test_[a-f0-9]{12}$/D', $dbName)) $pdo->exec('DROP DATABASE `' . $dbName . '`');
    $resolved = realpath($root);
    if ($resolved && preg_match('/^vast-backup-test-[a-f0-9]{12}$/D', basename($resolved))) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($resolved, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $file) {
            if ($file->isDir() && !$file->isLink()) rmdir($file->getPathname());
            else unlink($file->getPathname());
        }
        rmdir($resolved);
    }
}
