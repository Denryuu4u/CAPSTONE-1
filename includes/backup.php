<?php
/**
 * Data-only ZIP snapshots. No uploaded SQL or PHP is ever executed.
 * Restore requires the current schema and InnoDB, so DELETE/INSERT can roll back.
 * The HTTP controller holds the installation-wide exclusive lock throughout.
 */
class SystemBackup
{
    private PDO $pdo;
    private string $uploads;
    private string $storage;
    private const LIMIT = 536870912; // 512 MiB expanded, including table data.
    private const ROW_LIMIT = 16777216;

    public function __construct(PDO $pdo, string $uploads, string $storage)
    {
        $this->pdo = $pdo;
        $this->uploads = rtrim($uploads, '/\\');
        $this->storage = rtrim($storage, '/\\');
        if (!class_exists('ZipArchive')) throw new RuntimeException('The PHP ZIP extension must be enabled to use backups.');
        if (!is_dir($this->storage) && !mkdir($this->storage, 0700, true)) {
            throw new RuntimeException('The backup folder could not be created.');
        }
        // Also protect a newly provisioned uploads volume under local Apache.
        if (file_put_contents($this->storage . '/.htaccess', "Require all denied\n") === false) {
            throw new RuntimeException('The backup folder is not writable.');
        }
    }

    private function ident(string $value): string
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/D', $value)) throw new RuntimeException('Unsupported database identifier.');
        return '`' . $value . '`';
    }

    private function schema(): array
    {
        if ($this->pdo->query('SELECT COUNT(*) FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')->fetchColumn()) {
            throw new RuntimeException('Databases with custom triggers require a database administrator to back up and restore them.');
        }
        $tables = $this->pdo->query("SELECT TABLE_NAME, ENGINE, TABLE_TYPE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_ASSOC);
        $schema = [];
        foreach ($tables as $table) {
            $name = $table['TABLE_NAME'];
            if ($table['TABLE_TYPE'] !== 'BASE TABLE' || $table['ENGINE'] !== 'InnoDB') {
                throw new RuntimeException('Backup and restore require InnoDB tables and no database views.');
            }
            $columns = $this->pdo->query('SHOW FULL COLUMNS FROM ' . $this->ident($name))->fetchAll(PDO::FETCH_ASSOC);
            foreach ($columns as $column) {
                if (preg_match('/(?:VIRTUAL|STORED) GENERATED/i', $column['Extra'])) throw new RuntimeException('Generated database columns are not supported.');
            }
            $ddl = $this->pdo->query('SHOW CREATE TABLE ' . $this->ident($name))->fetch(PDO::FETCH_NUM)[1];
            $ddl = preg_replace('/ AUTO_INCREMENT=\d+/', '', $ddl);
            $schema[$name] = ['columns' => array_column($columns, 'Field'), 'signature' => hash('sha256', $ddl)];
        }
        if (!$schema || count($schema) > 200) throw new RuntimeException('The database cannot be backed up in this format.');
        return $schema;
    }

    private function safeUpload(string $name): bool
    {
        // Files created by the app; never restore server configuration or scripts.
        if (strlen($name) > 240 || !preg_match('~^[a-zA-Z0-9_-][a-zA-Z0-9_./-]*$~D', $name)) return false;
        foreach (explode('/', $name) as $part) {
            if ($part === '' || $part[0] === '.' || substr($part, -1) === '.') return false;
            if (preg_match('/\.(?:php\d*|phtml|phar|cgi|pl|py|asp|aspx|shtml)(?:\.|$)/i', $part)) return false;
        }
        return in_array(strtolower(pathinfo($name, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'dwg', 'skp', 'csv', 'cut', 'xls', 'xlsx', 'doc', 'docx'], true);
    }

    private function uploadFiles(): array
    {
        $result = [];
        $walk = function (string $dir, string $prefix = '') use (&$walk, &$result): void {
            foreach (new DirectoryIterator($dir) as $file) {
                $name = $file->getFilename();
                if ($file->isDot() || $name[0] === '.') continue;
                if ($file->isLink()) throw new RuntimeException('Linked upload files are not supported.');
                $relative = $prefix . $name;
                if ($file->isDir()) $walk($file->getPathname(), $relative . '/');
                elseif ($this->safeUpload($relative)) $result[$relative] = $file->getPathname();
            }
        };
        $walk($this->uploads);
        ksort($result);
        return $result;
    }

    private function write($stream, string $text): void
    {
        if (fwrite($stream, $text) !== strlen($text)) throw new RuntimeException('There is not enough space to save the backup.');
    }

    private function workdir(): string
    {
        $dir = $this->storage . '/work-' . bin2hex(random_bytes(12));
        if (!mkdir($dir, 0700)) throw new RuntimeException('Cannot prepare the backup folder.');
        return $dir;
    }

    private function cleanup(string $dir): void
    {
        // Only our flat, randomly named staging directory; never follow links.
        foreach (new DirectoryIterator($dir) as $f) {
            if (!$f->isDot() && !$f->isDir()) @unlink($f->getPathname());
        }
        @rmdir($dir);
    }

    public function path(string $id): string
    {
        if (!preg_match('/^vast-\d{8}-\d{6}-[a-f0-9]{16}\.zip$/D', $id)) throw new RuntimeException('Choose a valid saved copy.');
        $path = $this->storage . '/' . $id;
        if (!is_file($path) || is_link($path)) throw new RuntimeException('The saved copy was not found.');
        return $path;
    }

    public function copies(): array
    {
        $result = [];
        foreach (glob($this->storage . '/vast-*.zip') ?: [] as $path) {
            $zip = new ZipArchive();
            try {
                $this->path(basename($path));
                if ($zip->open($path) !== true) continue;
                $stat = $zip->statName('manifest.json');
                if (!$stat || $stat['size'] > 1048576) continue;
                $m = json_decode($zip->getFromName('manifest.json'), true, 32, JSON_THROW_ON_ERROR);
                if (($m['format'] ?? '') !== 'vast-solutions-data-v1') continue;
                $result[] = ['id' => basename($path), 'created_at' => $m['created_at'], 'kind' => $m['kind'], 'by' => $m['by'], 'size' => filesize($path), 'tables' => count($m['tables']), 'files' => count($m['files'])];
            } catch (Throwable $e) {
                // An incomplete/unrelated archive is never offered for restore.
            } finally {
                if ($zip->status === ZipArchive::ER_OK) { try { $zip->close(); } catch (Throwable $e) {} }
            }
        }
        usort($result, fn($a, $b) => strcmp($b['id'], $a['id']));
        return $result;
    }

    public function create(string $by, string $kind = 'manual'): string
    {
        $schema = $this->schema();
        $dir = $this->workdir();
        $id = 'vast-' . date('Ymd-His') . '-' . bin2hex(random_bytes(8)) . '.zip';
        $zip = new ZipArchive();
        $opened = false;
        try {
            if ($zip->open($dir . '/copy.zip', ZipArchive::CREATE | ZipArchive::EXCL) !== true) throw new RuntimeException('Cannot create the backup file.');
            $opened = true;
            $m = ['format' => 'vast-solutions-data-v1', 'created_at' => date(DATE_ATOM), 'kind' => $kind, 'by' => $by, 'tables' => [], 'files' => []];
            $total = 0;
            $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $this->pdo->beginTransaction();
            foreach ($schema as $name => $spec) {
                $file = $dir . '/' . $name . '.jsonl';
                $out = fopen($file, 'wb');
                if (!$out) throw new RuntimeException('Cannot write database backup.');
                $count = 0;
                try {
                    $query = $this->pdo->query('SELECT * FROM ' . $this->ident($name));
                    while ($row = $query->fetch(PDO::FETCH_NUM)) {
                        $line = json_encode(array_map(fn($v) => $v === null ? null : base64_encode((string) $v), $row), JSON_THROW_ON_ERROR) . "\n";
                        $total += strlen($line);
                        if (strlen($line) >= self::ROW_LIMIT || $total > self::LIMIT) throw new RuntimeException('This backup exceeds the supported 512 MB size.');
                        $this->write($out, $line);
                        $count++;
                    }
                } finally { fclose($out); }
                $entry = 'tables/' . $name . '.jsonl';
                $m['tables'][$name] = $spec + ['entry' => $entry, 'rows' => $count, 'sha256' => hash_file('sha256', $file)];
                if (!$zip->addFile($file, $entry)) throw new RuntimeException('Could not add database records to the backup.');
            }
            $this->pdo->commit();
            foreach ($this->uploadFiles() as $name => $path) {
                $total += filesize($path);
                if ($total > self::LIMIT || count($m['files']) >= 20000) throw new RuntimeException('This backup exceeds the supported file or size limit.');
                $entry = 'uploads/' . $name;
                $m['files'][$name] = ['entry' => $entry, 'sha256' => hash_file('sha256', $path)];
                if (!$zip->addFile($path, $entry)) throw new RuntimeException('Could not add an uploaded file to the backup.');
            }
            $manifest = json_encode($m, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            if (strlen($manifest) > 1048576) throw new RuntimeException('This backup contains too many files.');
            if (!$zip->addFromString('manifest.json', $manifest) || !$zip->close()) throw new RuntimeException('Could not finish the backup. Check available disk space.');
            $opened = false;
            if (!rename($dir . '/copy.zip', $this->storage . '/' . $id)) throw new RuntimeException('Could not save the backup.');
            return $id;
        } finally {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if ($opened) { try { $zip->close(); } catch (Throwable $e) {} }
            $this->cleanup($dir);
        }
    }

    private function decodeRow(string $line, int $columns): array
    {
        $row = json_decode($line, true, 8, JSON_THROW_ON_ERROR);
        if (!is_array($row) || array_keys($row) !== range(0, $columns - 1)) throw new RuntimeException('The backup contains invalid database records.');
        return array_map(function ($v) {
            if ($v === null) return null;
            if (!is_string($v) || ($decoded = base64_decode($v, true)) === false) throw new RuntimeException('The backup contains invalid field data.');
            return $decoded;
        }, $row);
    }

    private function eachRow(string $path, int $columns, callable $callback): int
    {
        $in = fopen($path, 'rb');
        if (!$in) throw new RuntimeException('Cannot read the backup records.');
        $count = 0;
        try {
            while (($line = fgets($in, self::ROW_LIMIT)) !== false) {
                if (substr($line, -1) !== "\n") throw new RuntimeException('The backup has an incomplete or oversized record.');
                $callback($this->decodeRow($line, $columns));
                $count++;
            }
            if (!feof($in)) throw new RuntimeException('Could not read the complete backup.');
        } finally { fclose($in); }
        return $count;
    }

    private function validate(string $path, string $dir): array
    {
        if (filesize($path) > self::LIMIT) throw new RuntimeException('The backup is larger than 512 MB.');
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true) throw new RuntimeException('Choose a valid ZIP backup created by this system.');
        try {
            $stat = $zip->statName('manifest.json');
            if (!$stat || $stat['size'] > 1048576) throw new RuntimeException('The backup information is missing or invalid.');
            $m = json_decode($zip->getFromName('manifest.json'), true, 32, JSON_THROW_ON_ERROR);
            if (($m['format'] ?? '') !== 'vast-solutions-data-v1' || !is_array($m['tables'] ?? null) || !is_array($m['files'] ?? null)) throw new RuntimeException('This file is not a supported Vast Solutions backup.');
            $schema = $this->schema();
            if (array_keys($m['tables']) !== array_keys($schema)) throw new RuntimeException('This backup uses a different database structure. Restore it with the matching system version.');
            $expected = [];
            foreach ($schema as $name => $spec) {
                $table = $m['tables'][$name];
                if (($table['columns'] ?? null) !== $spec['columns'] || ($table['signature'] ?? '') !== $spec['signature'] || ($table['entry'] ?? '') !== 'tables/' . $name . '.jsonl' || !is_int($table['rows'] ?? null) || $table['rows'] < 0) {
                    throw new RuntimeException('This backup uses a different database structure. Restore it with the matching system version.');
                }
                $expected[$table['entry']] = $table['sha256'] ?? '';
            }
            foreach ($m['files'] as $name => $file) {
                if (!$this->safeUpload((string) $name) || ($file['entry'] ?? '') !== 'uploads/' . $name) throw new RuntimeException('The backup contains an unsafe file path.');
                $expected[$file['entry']] = $file['sha256'] ?? '';
            }
            if (count($m['files']) > 20000 || $zip->numFiles !== count($expected) + 1) throw new RuntimeException('The backup contains unexpected files.');
            $seen = [];
            $total = 0;
            $staged = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $info = $zip->statIndex($i);
                $name = $info['name'];
                $lower = strtolower($name);
                if (isset($seen[$lower])) throw new RuntimeException('The backup contains duplicate files.');
                $seen[$lower] = true;
                $total += $info['size'];
                if ($total > self::LIMIT || ($info['encryption_method'] ?? 0) !== 0) throw new RuntimeException('The backup is encrypted or exceeds the 512 MB size limit.');
                if ($name === 'manifest.json') continue;
                if (!isset($expected[$name]) || !preg_match('/^[a-f0-9]{64}$/D', $expected[$name])) throw new RuntimeException('The backup contains unexpected files.');
                // Write to flat random staging paths; never extract archive paths.
                $dest = $dir . '/entry-' . $i;
                $in = $zip->getStream($name);
                $out = fopen($dest, 'wb');
                if (!$in || !$out) throw new RuntimeException('Cannot read the backup files.');
                try { $copied = stream_copy_to_stream($in, $out, $info['size'] + 1); }
                finally { fclose($in); fclose($out); }
                if ($copied !== $info['size'] || !hash_equals($expected[$name], hash_file('sha256', $dest))) throw new RuntimeException('The backup is damaged or incomplete.');
                $staged[$name] = $dest;
            }
            $hasAdmin = false;
            foreach ($m['tables'] as $name => $table) {
                $count = $this->eachRow($staged[$table['entry']], count($table['columns']), function ($row) use ($name, $table, &$hasAdmin) {
                    if ($name !== 'users') return;
                    $user = array_combine($table['columns'], $row);
                    if (($user['role'] ?? '') === 'Super Admin' && ($user['status'] ?? '') === 'Active' && empty($user['is_archived']) && !empty($user['password_hash'])) $hasAdmin = true;
                });
                if ($count !== $table['rows']) throw new RuntimeException('The backup has missing database records.');
            }
            if (!$hasAdmin) throw new RuntimeException('The backup must contain an active Super Admin account.');
            return [$m, $staged];
        } finally { $zip->close(); }
    }

    private function verifyReferences(): void
    {
        $rows = $this->pdo->query("SELECT TABLE_NAME, CONSTRAINT_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
        $groups = [];
        foreach ($rows as $r) $groups[$r['TABLE_NAME'] . '/' . $r['CONSTRAINT_NAME']][] = $r;
        foreach ($groups as $cols) {
            $join = $nonnull = [];
            foreach ($cols as $c) {
                $join[] = 'c.' . $this->ident($c['COLUMN_NAME']) . '=p.' . $this->ident($c['REFERENCED_COLUMN_NAME']);
                $nonnull[] = 'c.' . $this->ident($c['COLUMN_NAME']) . ' IS NOT NULL';
            }
            $sql = 'SELECT 1 FROM ' . $this->ident($cols[0]['TABLE_NAME']) . ' c LEFT JOIN ' . $this->ident($cols[0]['REFERENCED_TABLE_NAME']) . ' p ON ' . implode(' AND ', $join) . ' WHERE ' . implode(' AND ', $nonnull) . ' AND p.' . $this->ident($cols[0]['REFERENCED_COLUMN_NAME']) . ' IS NULL LIMIT 1';
            if ($this->pdo->query($sql)->fetchColumn()) throw new RuntimeException('The backup has records with missing related data. Nothing was restored.');
        }
    }

    public function restore(string $path, string $by): string
    {
        $dir = $this->workdir();
        $moved = $installed = [];
        $committed = false;
        $preserve = false;
        $oldEpoch = is_file($this->storage . '/session-epoch') ? file_get_contents($this->storage . '/session-epoch') : '';
        try {
            [$m, $staged] = $this->validate($path, $dir);
            // No destructive work starts before a complete recovery copy exists.
            $safety = $this->create($by, 'before_restore');
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=0');
            $this->pdo->beginTransaction();
            foreach ($m['tables'] as $name => $table) $this->pdo->exec('DELETE FROM ' . $this->ident($name));
            foreach ($m['tables'] as $name => $table) {
                $sql = 'INSERT INTO ' . $this->ident($name) . ' (' . implode(',', array_map([$this, 'ident'], $table['columns'])) . ') VALUES (' . implode(',', array_fill(0, count($table['columns']), '?')) . ')';
                $insert = $this->pdo->prepare($sql);
                $this->eachRow($staged[$table['entry']], count($table['columns']), fn($row) => $insert->execute($row));
            }
            $this->verifyReferences();
            foreach ($this->uploadFiles() as $name => $old) {
                $dest = $dir . '/previous-' . count($moved);
                if (!rename($old, $dest)) throw new RuntimeException('Cannot replace uploaded files. Check folder permissions.');
                $moved[$old] = $dest;
            }
            foreach ($m['files'] as $name => $file) {
                $dest = $this->uploads . '/' . $name;
                $parent = dirname($dest);
                // Walk each parent to reject links even when the directory is empty.
                $check = $this->uploads;
                $parts = explode('/', $name);
                array_pop($parts);
                foreach ($parts as $part) {
                    $check .= '/' . $part;
                    if (is_link($check)) throw new RuntimeException('Linked upload folders cannot be restored.');
                }
                if (!is_dir($parent) && !mkdir($parent, 0755, true)) throw new RuntimeException('Cannot create an upload folder.');
                if (file_exists($dest) || is_link($dest) || !rename($staged[$file['entry']], $dest)) throw new RuntimeException('Cannot restore an uploaded file.');
                $installed[] = $dest;
            }
            if (file_put_contents($this->storage . '/session-epoch', bin2hex(random_bytes(24)), LOCK_EX) === false) throw new RuntimeException('Cannot reset sessions after restore.');
            $this->pdo->commit();
            $committed = true;
            return $safety;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            if (!$committed) {
                foreach (array_reverse($installed) as $dest) if (!@unlink($dest)) $preserve = true;
                foreach ($moved as $original => $saved) if (!@rename($saved, $original)) $preserve = true;
                if (file_put_contents($this->storage . '/session-epoch', $oldEpoch, LOCK_EX) === false) $preserve = true;
            }
            if ($preserve) throw new RuntimeException('Restore failed and some files could not be put back. The recovery copy and recovery folder have been kept. Contact your system administrator.', 0, $e);
            throw $e;
        } finally {
            $this->pdo->exec('SET FOREIGN_KEY_CHECKS=1');
            if (!$preserve) $this->cleanup($dir);
        }
    }

    public function delete(string $id): void
    {
        if (!unlink($this->path($id))) throw new RuntimeException('The saved copy could not be deleted.');
    }
}

function system_backup(): SystemBackup
{
    return new SystemBackup(db(), __DIR__ . '/../uploads', __DIR__ . '/../uploads/.system-backups');
}
