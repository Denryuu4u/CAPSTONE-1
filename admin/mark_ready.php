<?php
/**
 * "Mark Ready for Production" from the summarization page.
 * Moves the project to 'production', copies the summarized cutlist into the
 * project's materials list, posts an update, and notifies the client.
 *
 * POST: batch_id (required), project_id (optional — else taken from the batch)
 */
require_once __DIR__ . '/../includes/helpers.php';
require_page('monitoring', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

function mr_fail(string $m, int $c = 400): void
{
    http_response_code($c);
    echo json_encode(['ok' => false, 'error' => $m]);
    exit;
}

$batchId   = (int) ($_POST['batch_id'] ?? 0);
$projectId = (int) ($_POST['project_id'] ?? 0);
if ($batchId <= 0) mr_fail('No summarization batch provided.');

$pdo = db();
$batch = $pdo->prepare("SELECT * FROM summ_batches WHERE id = ?");
$batch->execute([$batchId]);
$b = $batch->fetch();
if (!$b) mr_fail('Batch not found.', 404);

if ($projectId <= 0) $projectId = (int) ($b['project_id'] ?? 0);
if ($projectId <= 0) mr_fail('This summary is not linked to a project. Select an approved project first.');

$proj = $pdo->prepare("SELECT * FROM projects WHERE id = ?");
$proj->execute([$projectId]);
$project = $proj->fetch();
if (!$project) mr_fail('Project not found.', 404);

try {
    $pdo->beginTransaction();

    // Move to production (keep progress sensible).
    $pdo->prepare(
        "UPDATE projects
            SET status = 'production',
                progress = GREATEST(progress, 15),
                start_date = COALESCE(start_date, CURDATE())
          WHERE id = ?"
    )->execute([$projectId]);

    // Copy the summarized items into the project's materials list (once).
    $existing = $pdo->prepare("SELECT COUNT(*) FROM project_materials WHERE project_id = ?");
    $existing->execute([$projectId]);
    if ((int) $existing->fetchColumn() === 0) {
        $unitByCat = ['wood' => 'panel', 'alu' => 'edge', 'hw' => 'pcs'];
        $items = $pdo->prepare("SELECT * FROM summ_items WHERE batch_id = ? ORDER BY category, id");
        $items->execute([$batchId]);
        $ins = $pdo->prepare(
            "INSERT INTO project_materials (project_id, material, specification, qty, unit, status, sort_order)
             VALUES (?,?,?,?,?, 'available', ?)"
        );
        $i = 0;
        foreach ($items as $it) {
            $spec = trim(($it['partname'] ?? '') . ' ' .
                ($it['width'] && $it['length'] ? "{$it['width']}x{$it['length']}" : ''));
            $ins->execute([
                $projectId,
                $it['material'] ?: $it['partname'],
                $spec ?: null,
                (int) $it['qty'],
                $unitByCat[$it['category']] ?? 'pcs',
                $i++,
            ]);
        }
    }

    $pdo->commit();

    add_project_update($projectId, 'Materials finalized — project is now in production.');
    log_audit('Summarization', "Marked {$project['project_code']} ready for production", $project['project_name']);

    echo json_encode(['ok' => true, 'project_code' => $project['project_code']]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    mr_fail('Server error: ' . $e->getMessage(), 500);
}
