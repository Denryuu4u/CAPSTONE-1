<?php
/**
 * Download endpoint for a saved summarization batch.
 *
 * GET params:
 *   batch     (int, required)   summ_batches.id
 *   category  wood | alu | hw | all   (default: all)
 *
 * A single category streams an .xls file (columns partname, material, qty,
 * width, length, edging, comment) — matching the legacy CVProcess output.
 * `all` streams a ZIP of the three category .xls files (the legacy
 * "results - wood/alu/hw - <file>" trio).
 *
 * The .xls is produced with the dependency-free HTML-spreadsheet method
 * (Excel opens an HTML table sent as application/vnd.ms-excel). No Composer /
 * PhpSpreadsheet required.
 */

require __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_page('summarization', true); // back-office only

$batchId  = isset($_GET['batch']) ? (int) $_GET['batch'] : 0;
$category = strtolower($_GET['category'] ?? 'all');
$valid    = ['wood', 'alu', 'hw', 'all'];

if ($batchId <= 0 || !in_array($category, $valid, true)) {
    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Invalid request. Expected ?batch=<id>&category=wood|alu|hw|all';
    exit;
}

$pdo = db();

// Batch (for the download file name / source).
$batchStmt = $pdo->prepare("SELECT * FROM summ_batches WHERE id = ?");
$batchStmt->execute([$batchId]);
$batch = $batchStmt->fetch();
if (!$batch) {
    http_response_code(404);
    header('Content-Type: text/plain');
    echo 'Batch not found.';
    exit;
}

const SUMMARY_HEADERS = ['PARTNAME', 'MATERIAL', 'QTY', 'WIDTH', 'LENGTH', 'EDGING', 'COMMENT'];

/** Base name (no extension) of the uploaded source, for friendly file names. */
function summary_base(array $batch): string
{
    $src  = $batch['source_filename'] ?: ('batch-' . $batch['id']);
    $base = pathinfo($src, PATHINFO_FILENAME);
    $base = preg_replace('/[^A-Za-z0-9 _.\-]/', '', $base); // header/filesystem safe
    return $base !== '' ? strtolower($base) : ('batch-' . $batch['id']);
}

/** Fetch summarized rows for one category, ordered for a stable export. */
function fetch_items(PDO $pdo, int $batchId, string $category): array
{
    $stmt = $pdo->prepare(
        "SELECT partname, material, qty, width, length, edging, comment
           FROM summ_items
          WHERE batch_id = ? AND category = ?
          ORDER BY partname, material, id"
    );
    $stmt->execute([$batchId, $category]);
    return $stmt->fetchAll();
}

/**
 * Build a complete .xls (HTML-spreadsheet) document for one category's rows.
 * QTY is emitted as a real number (right-aligned in Excel); every other column
 * is forced to text so material codes and dimensions are preserved verbatim.
 */
function build_xls(array $rows): string
{
    $esc  = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    // mso-number-format:"\@"  => tell Excel this cell is text.
    $text = static fn($v) => '<td style="mso-number-format:\'\@\';">' . $esc($v) . '</td>';
    $num  = static fn($v) => '<td style="text-align:right;">' . $esc($v) . '</td>';

    $out  = '<html xmlns:o="urn:schemas-microsoft-com:office:office" '
          . 'xmlns:x="urn:schemas-microsoft-com:office:excel">'
          . '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head>'
          . '<body><table border="1" cellspacing="0" cellpadding="4">';

    $out .= '<thead><tr style="background:#2e4a45;color:#fff;font-weight:bold;">';
    foreach (SUMMARY_HEADERS as $h) {
        $out .= '<th>' . $h . '</th>';
    }
    $out .= '</tr></thead><tbody>';

    foreach ($rows as $r) {
        $out .= '<tr>'
              . $text($r['partname'])
              . $text($r['material'])
              . $num($r['qty'])
              . $text($r['width'])
              . $text($r['length'])
              . $text($r['edging'])
              . $text($r['comment'])
              . '</tr>';
    }

    $out .= '</tbody></table></body></html>';
    return $out;
}

$base = summary_base($batch);

// ── Single category → .xls download ──────────────────────────────────
if ($category !== 'all') {
    $rows     = fetch_items($pdo, $batchId, $category);
    $filename = "results - {$category} - {$base}.xls";

    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo build_xls($rows);
    exit;
}

// ── All categories → ZIP of three .xls files ─────────────────────────
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'ZIP support (ext-zip) is not available on this server.';
    exit;
}

$tmpZip = tempnam(sys_get_temp_dir(), 'summ');
$zip    = new ZipArchive();
if ($zip->open($tmpZip, ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo 'Could not create the ZIP archive.';
    exit;
}

foreach (['wood', 'alu', 'hw'] as $cat) {
    $rows = fetch_items($pdo, $batchId, $cat);
    $zip->addFromString("results - {$cat} - {$base}.xls", build_xls($rows));
}
$zip->close();

$zipName = "summary - {$base}.zip";
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmpZip));
header('Pragma: no-cache');
header('Expires: 0');
readfile($tmpZip);
unlink($tmpZip);
exit;
