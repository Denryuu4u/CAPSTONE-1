<?php
/**
 * Processing endpoint for the Summarization of Materials page.
 *
 * Accepts a POST multipart upload of a Cabinet Vision .cut cutting list,
 * summarizes it (see includes/cutlist_processor.php), saves the result to the
 * database (summ_batches + summ_items), and returns JSON for the page to render.
 *
 * POST fields:
 *   cutfile        (file, required)  the .cut file
 *   project_id     (int, optional)   selected project id
 *   project_name   (string, optional) selected project label
 *
 * Response: { ok, batch_id, project_name, source_filename, counts, wood[], alu[], hw[] }
 */

require_once __DIR__ . '/../includes/auth.php';
require_page('summarization', true); // back-office only
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cutlist_processor.php';

function fail(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $message]);
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        fail('Invalid request method.', 405);
    }

    if (!isset($_FILES['cutfile']) || $_FILES['cutfile']['error'] !== UPLOAD_ERR_OK) {
        fail('No file was uploaded or the upload failed.');
    }

    $original = $_FILES['cutfile']['name'];
    $tmpPath  = $_FILES['cutfile']['tmp_name'];

    // Validate extension (mirrors the legacy "not a .cut file" guard).
    if (strtolower((string) pathinfo($original, PATHINFO_EXTENSION)) !== 'cut') {
        fail('Uploaded file is not a .cut file.');
    }

    $contents = file_get_contents($tmpPath);
    if ($contents === false || trim($contents) === '') {
        fail('The uploaded file is empty or could not be read.');
    }

    $pdo = db();

    // Optional project association.
    $projectId   = isset($_POST['project_id']) && $_POST['project_id'] !== ''
        ? (int) $_POST['project_id'] : null;
    $projectName = isset($_POST['project_name']) ? trim((string) $_POST['project_name']) : null;
    if ($projectName === '' || $projectName === '-- Select Project --') {
        $projectName = null;
    }
    $uploadedBy = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;

    // Summarize using the DB material/edging override libraries.
    [$materialMap, $edgingMap] = cutlist_load_libraries($pdo);
    $result = summarize_cutlist($contents, $materialMap, $edgingMap);

    if ($result['rows_read'] === 0) {
        fail('No cabinet parts were found in this file. Is it a valid Cabinet Vision .cut export?');
    }

    // Persist: one batch + all its items, atomically.
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO summ_batches (project_id, project_name, source_filename, uploaded_by)
         VALUES (:project_id, :project_name, :source_filename, :uploaded_by)"
    );
    $stmt->execute([
        ':project_id'      => $projectId,
        ':project_name'    => $projectName,
        ':source_filename' => $original,
        ':uploaded_by'     => $uploadedBy,
    ]);
    $batchId = (int) $pdo->lastInsertId();

    $itemStmt = $pdo->prepare(
        "INSERT INTO summ_items
            (batch_id, category, partname, material, qty, width, length, edging, comment)
         VALUES
            (:batch_id, :category, :partname, :material, :qty, :width, :length, :edging, :comment)"
    );

    foreach (['wood', 'alu', 'hw'] as $category) {
        foreach ($result[$category] as $row) {
            $itemStmt->execute([
                ':batch_id' => $batchId,
                ':category' => $category,
                ':partname' => $row['partname'],
                ':material' => $row['material'],
                ':qty'      => $row['qty'],
                ':width'    => $row['width'],
                ':length'   => $row['length'],
                ':edging'   => $row['edging'],
                ':comment'  => $row['comment'],
            ]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok'              => true,
        'batch_id'        => $batchId,
        'project_name'    => $projectName,
        'source_filename' => $original,
        'counts'          => $result['counts'],
        'wood'            => $result['wood'],
        'alu'             => $result['alu'],
        'hw'              => $result['hw'],
    ]);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fail('Server error while processing the file: ' . $e->getMessage(), 500);
}
