<?php
/** Local XAMPP checks; does not restore, delete or modify business records. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$base = 'http://localhost/CAPSTONE-1';
function expectBackup(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo 'PASS: ', $message, PHP_EOL;
}
function httpBackup($curl, string $url, ?array $post = null): array
{
    curl_setopt_array($curl, [CURLOPT_URL => $url, CURLOPT_POST => $post !== null, CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 20]);
    if ($post !== null) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
    $body = curl_exec($curl);
    if ($body === false) throw new RuntimeException(curl_error($curl));
    return [curl_getinfo($curl, CURLINFO_HTTP_CODE), $body];
}
foreach (['admin', 'staff', 'client'] as $role) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_COOKIEFILE, '');
    [$status, $html] = httpBackup($curl, $base . '/admin/settings.php?dev_user=' . $role);
    expectBackup(strpos($html, 'id="backupRestoreCard"') === false, $role . ' cannot see backup controls');
    foreach (['create', 'download', 'delete', 'restore'] as $action) {
        [$status] = httpBackup($curl, $base . '/admin/backup_action.php', ['action' => $action]);
        expectBackup($status === 403, $role . ' cannot call ' . $action);
    }
    curl_close($curl);
}
$curl = curl_init();
curl_setopt($curl, CURLOPT_COOKIEFILE, '');
[$status, $html] = httpBackup($curl, $base . '/admin/settings.php?dev_user=superadmin');
expectBackup($status === 200 && strpos($html, 'id="backupRestoreCard"') !== false, 'Super Admin sees Backup & Restore inside Settings');
preg_match('/name="csrf" value="([a-f0-9]+)"/', $html, $token);
expectBackup(!empty($token[1]), 'Settings issues a CSRF token');
[$status] = httpBackup($curl, $base . '/admin/backup_action.php', ['action' => 'create', 'csrf' => 'invalid']);
expectBackup($status === 403, 'missing or incorrect CSRF token is rejected');
[$status] = httpBackup($curl, $base . '/admin/backup_action.php', ['action' => 'restore', 'csrf' => $token[1], 'confirm_text' => 'RESTORE', 'current_password' => 'intentionally-incorrect-backup-test-password']);
expectBackup($status === 403, 'restore requires the current Super Admin password');
[$status] = httpBackup($curl, $base . '/admin/backup_action.php');
expectBackup($status === 405, 'GET cannot perform backup actions');
[$status] = httpBackup($curl, $base . '/admin/backup_settings.php');
expectBackup($status === 403, 'Settings fragment rejects direct access');
preg_match('/name="id" value="(vast-[a-f0-9-]+\.zip)"/', $html, $copy);
if (!empty($copy[1])) {
    [$status] = httpBackup($curl, $base . '/uploads/.system-backups/' . $copy[1]);
    expectBackup($status === 403, 'saved ZIP cannot be downloaded through its public URL');
    [$status, $bytes] = httpBackup($curl, $base . '/admin/backup_action.php', ['action' => 'download', 'csrf' => $token[1], 'id' => $copy[1]]);
    $temp = tempnam(sys_get_temp_dir(), 'vast-download-test-');
    try {
        file_put_contents($temp, $bytes);
        $zip = new ZipArchive();
        $opened = $zip->open($temp);
        expectBackup($status === 200 && $opened === true, 'authorized download is a valid ZIP');
        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        expectBackup(($manifest['format'] ?? '') === 'vast-solutions-data-v1', 'download contains the backup manifest');
        $zip->close();
    } finally { unlink($temp); }
}
curl_close($curl);

// The CLI and Apache must agree on the same lock for maintenance coordination.
$code = 'require ' . var_export(__DIR__ . '/../includes/data_lock.php', true) . '; system_data_lock(true); echo "ready\\n"; fflush(STDOUT); fgets(STDIN);';
$process = proc_open([PHP_BINARY, '-r', $code], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($process)) throw new RuntimeException('Cannot start the lock test.');
try {
    expectBackup(trim((string) fgets($pipes[1])) === 'ready', 'maintenance lock is acquired');
    $curl = curl_init();
    [$status] = httpBackup($curl, $base . '/admin/settings.php');
    expectBackup($status === 503, 'maintenance lock pauses other application requests');
    curl_close($curl);
} finally {
    fwrite($pipes[0], "done\n");
    foreach ($pipes as $pipe) fclose($pipe);
    proc_close($process);
}
echo "All HTTP checks passed.\n";
