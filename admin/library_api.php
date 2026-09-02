<?php
/**
 * CRUD endpoint for the Custom Material & Edge libraries
 * (material_library / edging_library) used by the summarization page's
 * "Custom Material & Edge Library" modal.
 *
 *   GET  ?action=list                              -> { material:[...], edging:[...] }
 *   POST action=add    type=material|edging code= value=   -> { ok, id }
 *   POST action=update type=material|edging id=  code= value=
 *   POST action=delete type=material|edging id=
 *
 * `code`  maps to the library's `code` column (the raw material/edge code).
 * `value` maps to `normalized_name` (the grouped value shown in the legacy UI).
 */

require_once __DIR__ . '/../includes/auth.php';
require_page('summarization', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';

function respond(array $payload, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($payload);
    exit;
}
function fail(string $message, int $code = 400): void
{
    respond(['ok' => false, 'error' => $message], $code);
}

/** Whitelist the type -> physical table (guards against SQL injection). */
function library_table(string $type): string
{
    if ($type === 'material') return 'material_library';
    if ($type === 'edging')   return 'edging_library';
    fail('Invalid library type.');
}

try {
    $pdo    = db();
    $action = $_REQUEST['action'] ?? 'list';

    if ($action === 'list') {
        $material = $pdo->query("SELECT id, code, normalized_name FROM material_library ORDER BY code")->fetchAll();
        $edging   = $pdo->query("SELECT id, code, normalized_name FROM edging_library ORDER BY code")->fetchAll();
        respond(['ok' => true, 'material' => $material, 'edging' => $edging]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        fail('Write actions require POST.', 405);
    }

    $type  = $_POST['type'] ?? '';
    $table = library_table($type);

    if ($action === 'add') {
        $code  = trim((string) ($_POST['code'] ?? ''));
        $value = trim((string) ($_POST['value'] ?? ''));
        if ($code === '') {
            fail('Code is required.');
        }

        $stmt = $pdo->prepare("INSERT INTO {$table} (code, normalized_name) VALUES (?, ?)");
        try {
            $stmt->execute([$code, $value]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') { // duplicate unique code
                fail("Code \"{$code}\" already exists in this library.", 409);
            }
            throw $e;
        }
        respond(['ok' => true, 'id' => (int) $pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id    = (int) ($_POST['id'] ?? 0);
        $code  = trim((string) ($_POST['code'] ?? ''));
        $value = trim((string) ($_POST['value'] ?? ''));
        if ($id <= 0)    fail('Missing record id.');
        if ($code === '') fail('Code is required.');

        $stmt = $pdo->prepare("UPDATE {$table} SET code = ?, normalized_name = ? WHERE id = ?");
        try {
            $stmt->execute([$code, $value, $id]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                fail("Code \"{$code}\" already exists in this library.", 409);
            }
            throw $e;
        }
        respond(['ok' => true]);
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id <= 0) fail('Missing record id.');
        $pdo->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
        respond(['ok' => true]);
    }

    fail('Unknown action.');
} catch (Throwable $e) {
    fail('Server error: ' . $e->getMessage(), 500);
}
