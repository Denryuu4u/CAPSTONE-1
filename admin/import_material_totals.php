<?php
/**
 * Parse an uploaded Cabinet Vision "material totals" export and return its line
 * items as JSON, to pre-fill the Create Cost Quotation costing preview.
 *
 * POST: totals_file (.xls or .csv)
 * Response: { ok, job_name, items:[{material, qty, unit, cost, unit_cost}] }
 */
require_once __DIR__ . '/../includes/auth.php';
require_page('summarization', true); // back-office only
require_once __DIR__ . '/../includes/material_totals.php';
header('Content-Type: application/json; charset=utf-8');

function mt_fail(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') mt_fail('POST required.', 405);
if (!isset($_FILES['totals_file']) || $_FILES['totals_file']['error'] !== UPLOAD_ERR_OK) {
    mt_fail('No file uploaded.');
}

$name = $_FILES['totals_file']['name'];
$ext  = strtolower(pathinfo($name, PATHINFO_EXTENSION));
$data = file_get_contents($_FILES['totals_file']['tmp_name']);
if ($data === false || $data === '') mt_fail('File is empty or unreadable.');

try {
    if ($ext === 'csv') {
        // Simple CSV fallback: columns Material, Qty, Unit, Cost.
        $items = [];
        foreach (preg_split('/\r\n|\r|\n/', $data) as $line) {
            if (trim($line) === '') continue;
            $c = str_getcsv($line);
            $mat = trim($c[0] ?? '');
            if ($mat === '' || strcasecmp($mat, 'Material') === 0) continue;
            if (!is_numeric($c[1] ?? '')) continue;
            $items[] = [
                'material' => $mat,
                'qty'      => round((float) $c[1], 4),
                'unit'     => trim($c[2] ?? ''),
                'cost'     => round((float) ($c[3] ?? 0), 2),
            ];
        }
        $parsed = ['job_name' => null, 'items' => $items];
    } else {
        $parsed = parse_material_totals($data);
    }

    if (empty($parsed['items'])) {
        mt_fail('No material rows found. Is this a Cabinet Vision material-totals export?');
    }

    // Add a pre-filled unit cost (cost / qty) for the editable preview.
    foreach ($parsed['items'] as &$it) {
        $it['unit_cost'] = ($it['qty'] > 0) ? round($it['cost'] / $it['qty'], 2) : 0.0;
    }
    unset($it);

    echo json_encode(['ok' => true, 'job_name' => $parsed['job_name'], 'items' => $parsed['items']]);
} catch (Throwable $e) {
    mt_fail('Could not read the file: ' . $e->getMessage(), 500);
}
