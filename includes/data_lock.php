<?php
/** Coordinate ordinary requests with manual backup/restore on this installation. */
function system_data_lock(bool $exclusive = false): void
{
    static $handle = null;
    static $mode = 0;
    if ($handle && $mode && (!$exclusive || $mode === LOCK_EX)) return;
    if (!$handle) {
        // Apache and CLI can have different Windows temp folders. Keep one lock
        // in shared application storage, outside the files replaced by restore.
        $folder = __DIR__ . '/../uploads/.system-backups';
        if (!is_dir($folder) && !@mkdir($folder, 0700, true) && !is_dir($folder)) {
            throw new RuntimeException('Cannot create the system maintenance folder.');
        }
        if (!is_file($folder . '/.htaccess') && file_put_contents($folder . '/.htaccess', "Require all denied\n") === false) {
            throw new RuntimeException('Cannot protect the system maintenance folder.');
        }
        $handle = fopen($folder . '/request.lock', 'c');
        if (!$handle) throw new RuntimeException('Cannot open the system maintenance lock.');
    } else {
        flock($handle, LOCK_UN);
        $mode = 0;
    }
    $until = microtime(true) + ($exclusive ? 10 : 0);
    do {
        if (flock($handle, ($exclusive ? LOCK_EX : LOCK_SH) | LOCK_NB)) {
            $mode = $exclusive ? LOCK_EX : LOCK_SH;
            return;
        }
        if (!$exclusive) {
            http_response_code(503);
            header('Retry-After: 15');
            exit('A backup or restore is in progress. Please try again in a moment.');
        }
        usleep(100000);
    } while (microtime(true) < $until);
    throw new RuntimeException('The system is busy. Wait a moment and try again.');
}

function system_restore_epoch(): string
{
    $path = __DIR__ . '/../uploads/.system-backups/session-epoch';
    return is_file($path) ? (string) file_get_contents($path) : '';
}
