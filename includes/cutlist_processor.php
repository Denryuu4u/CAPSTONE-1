<?php
/**
 * Cutting-list (.cut) summarizer — ported from the legacy Visual FoxPro
 * tool "CVProcess".
 *
 * A .cut file is plain CSV text, one part per line, comma-separated with
 * quoted strings. Column mapping (0-indexed), verified against
 * "VERA-010-RE00-102125-MAIN KITCHEN CABINET.cut":
 *
 *   qty=4  width=5  length=7  thickness=9  partname=12  material=14
 *   opti=21  assembly=27  comment=28  edging=30
 *
 * Pipeline (mirrors the legacy logic):
 *   1. Parse each line.
 *   2. Route by `opti`: 6 -> wood (panels), 5 -> alu (edges), 0 -> hw (hardware).
 *      Rows with any other opti are ignored (legacy exported only 6/5/0).
 *   3. Normalize material/edging via optional lookup libraries (override if the
 *      raw code is present in the library, else keep raw).
 *   4. Deduplicate: group rows sharing partname|comment|material|width|length|edging
 *      and sum their quantities.
 *
 * Output columns per row: partname, material, qty, width, length, edging, comment.
 */

/** Map opti code -> category bucket, or null if the row should be dropped. */
function cutlist_category(int $opti): ?string
{
    switch ($opti) {
        case 6: return 'wood'; // panels
        case 5: return 'alu';  // edge banding
        case 0: return 'hw';   // hardware
        default: return null;
    }
}

/**
 * Summarize raw .cut file contents.
 *
 * @param string $contents   Raw file contents.
 * @param array  $materialMap  [raw_code => normalized_name] optional overrides.
 * @param array  $edgingMap    [raw_code => normalized_name] optional overrides.
 * @return array{wood:array,alu:array,hw:array,counts:array,rows_read:int}
 */
function summarize_cutlist(string $contents, array $materialMap = [], array $edgingMap = []): array
{
    $buckets = ['wood' => [], 'alu' => [], 'hw' => []];
    $rowsRead = 0;

    // Normalize line endings, then walk each non-empty line.
    $lines = preg_split('/\r\n|\r|\n/', $contents);

    foreach ($lines as $line) {
        if (trim($line) === '') {
            continue;
        }

        $c = str_getcsv($line);
        // Need at least up to the edging column (index 30).
        if (count($c) < 31) {
            continue;
        }

        $opti     = (int) trim((string) ($c[21] ?? ''));
        $category = cutlist_category($opti);
        if ($category === null) {
            continue; // legacy keeps only opti 6/5/0
        }

        $rowsRead++;

        $partname = trim((string) $c[12]);
        $material = trim((string) $c[14]);
        $comment  = trim((string) $c[28]);
        $edging   = trim((string) $c[30]);
        $width    = trim((string) $c[5]);
        $length   = trim((string) $c[7]);
        $qty      = (int) round((float) trim((string) $c[4]));

        // Optional normalization overrides (legacy SEEKs the code in the library).
        if ($material !== '' && isset($materialMap[$material])) {
            $material = $materialMap[$material];
        }
        if ($edging !== '' && isset($edgingMap[$edging])) {
            $edging = $edgingMap[$edging];
        }

        // Deduplicate + sum quantities on the identity key.
        $key = implode('|', [$partname, $comment, $material, $width, $length, $edging]);

        if (isset($buckets[$category][$key])) {
            $buckets[$category][$key]['qty'] += $qty;
        } else {
            $buckets[$category][$key] = [
                'partname' => $partname,
                'material' => $material,
                'qty'      => $qty,
                'width'    => $width,
                'length'   => $length,
                'edging'   => $edging,
                'comment'  => $comment,
            ];
        }
    }

    // Drop the associative keys; return clean ordered lists.
    $wood = array_values($buckets['wood']);
    $alu  = array_values($buckets['alu']);
    $hw   = array_values($buckets['hw']);

    return [
        'wood'      => $wood,
        'alu'       => $alu,
        'hw'        => $hw,
        'counts'    => ['wood' => count($wood), 'alu' => count($alu), 'hw' => count($hw)],
        'rows_read' => $rowsRead,
    ];
}

/** Load [code => normalized_name] override maps from the DB libraries. */
function cutlist_load_libraries(PDO $pdo): array
{
    $materialMap = [];
    foreach ($pdo->query("SELECT code, normalized_name FROM material_library") as $r) {
        $materialMap[$r['code']] = $r['normalized_name'];
    }
    $edgingMap = [];
    foreach ($pdo->query("SELECT code, normalized_name FROM edging_library") as $r) {
        $edgingMap[$r['code']] = $r['normalized_name'];
    }
    return [$materialMap, $edgingMap];
}
