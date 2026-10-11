<?php
/** Shared project freeze and design metadata. Migrations preserve existing data. */
require_once __DIR__ . '/project_status.php';

function ensure_project_features(): void
{
    static $ready = false;
    if ($ready) return;
    foreach (['projects' => ['paused_status' => 'VARCHAR(40) NULL'],
              'quotations' => ['design_path' => 'VARCHAR(255) NULL']] as $table => $columns) {
        $known = array_column(db()->query("SHOW COLUMNS FROM `$table`")->fetchAll(), 'Field');
        foreach ($columns as $name => $type) {
            if (in_array($name, $known, true)) continue;
            try { db()->exec("ALTER TABLE `$table` ADD COLUMN `$name` $type"); }
            catch (PDOException $e) { if (($e->errorInfo[1] ?? 0) !== 1060) throw $e; }
        }
    }
    $ready = true;
}

function project_control_fail(string $message, int $code = 409, bool $json = true): void
{
    if (!$json) { header('Location: my_projects.php?project_error=' . urlencode($message)); exit; }
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

/** Serialize pause/resume against writes, including requests from another session. */
function lock_project(int $id): void
{
    static $locked = [];
    if (isset($locked[$id])) return;
    $pdo = db();
    $key = 'vast-project-' . substr(hash('sha256', (string) $pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 16) . '-' . $id;
    $st = $pdo->prepare('SELECT GET_LOCK(?, 10)'); $st->execute([$key]);
    if ((int) $st->fetchColumn() !== 1) project_control_fail('This project is busy. Please try again.');
    $locked[$id] = true;
    register_shutdown_function(static function () use ($pdo, $key) {
        try { $st = $pdo->prepare('SELECT RELEASE_LOCK(?)'); $st->execute([$key]); } catch (Throwable $e) {}
    });
}

function require_project_writable(int $id, bool $json = true): void
{
    if ($id <= 0) return; // standalone quotations / summaries have no project
    lock_project($id);
    $st = db()->prepare('SELECT status FROM projects WHERE id = ?'); $st->execute([$id]);
    $status = $st->fetchColumn();
    if ($status === false) project_control_fail('Project not found.', 404, $json);
    if (project_status_key($status) === 'on_hold') {
        project_control_fail('This project is paused. An Admin or Super Admin must resume it before changes can be made.', 409, $json);
    }
}

function project_csrf_token(): string
{
    if (empty($_SESSION['project_csrf'])) $_SESSION['project_csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['project_csrf'];
}

function require_project_csrf(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') project_control_fail('POST required.', 405);
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(project_csrf_token(), $_POST['csrf'])) {
        project_control_fail('Please refresh the page and try again.', 403);
    }
}
