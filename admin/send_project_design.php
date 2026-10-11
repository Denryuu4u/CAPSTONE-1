<?php
require_once __DIR__ . '/../includes/quotation_design.php';
require_page('monitoring', true);
require_project_csrf();
if (!in_array(current_role(), ['Admin', 'Super Admin'], true)) project_control_fail('Only Admin and Super Admin can send a design from Monitoring.', 403);
ensure_project_features();
$id = (int) ($_POST['quotation_id'] ?? 0);
$pdo = db();
$st = $pdo->prepare('SELECT * FROM quotations WHERE id=?'); $st->execute([$id]); $q = $st->fetch();
if (!$q || !$q['project_id']) project_control_fail('Quotation not found.', 404);
require_project_writable((int) $q['project_id']);
$path = null;
try {
    $pdo->beginTransaction();
    $st->execute([$id]); $q = $st->fetch();
    if ($q['status'] !== 'Approved' || $q['design_path']) {
        $pdo->rollBack(); project_control_fail('Only an approved quotation without a design can receive an image here.');
    }
    $path = save_quotation_design_upload($_FILES['design_image'] ?? []);
    if (!$path) throw new InvalidArgumentException('Choose a design image first.');
    $pdo->prepare('UPDATE quotations SET design_path=? WHERE id=?')->execute([$path, $id]);
    add_project_update((int) $q['project_id'], 'The initial design is now available. Select View design to see it.');
    log_audit('Monitoring', 'Sent initial design for ' . $q['quote_code'], $q['project_name']);
    $pdo->commit();
    header('Content-Type: application/json'); echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    remove_unsaved_design($path);
    project_control_fail($e instanceof InvalidArgumentException ? $e->getMessage() : 'The design could not be saved. Please try again.', $e instanceof InvalidArgumentException ? 400 : 500);
}
