<?php
/**
 * Minimal .xlsx writer — no external library, uses PHP's ZipArchive.
 *
 * Enough to emit a styled single-sheet workbook: bold, header fill, a big
 * title, and peso/number formats. Values are written as inline strings or
 * numbers; there is no formula support (we write computed values).
 *
 * Usage:
 *   $rows = [
 *     [ ['v'=>'Project Tracking','s'=>XLSX_TITLE] ],           // row 1
 *     [ '', '', ['v'=>'PROJECT AMOUNT','s'=>XLSX_HEAD] ],      // A,B blank, C styled
 *     [ 'Project Name', 'Chua Kitchen', ['v'=>470000,'s'=>XLSX_MONEY] ],
 *   ];
 *   xlsx_stream('Project_Tracking.xlsx', $rows, [12,26,16,16,16]);
 *
 * A cell is either a scalar (number → numeric cell, string → text) or an
 * array ['v'=>value, 's'=>styleIndex, 't'=>'n'|'s'] to force type/style.
 */

const XLSX_DEFAULT = 0;
const XLSX_BOLD    = 1;
const XLSX_HEAD    = 2; // bold white on navy
const XLSX_MONEY   = 3; // #,##0.00
const XLSX_TITLE   = 4; // big bold
const XLSX_BOLDMON = 5; // bold + money
const XLSX_SUBHEAD = 6; // bold on light fill

function xlsx_col(int $i): string
{
    $s = '';
    $i++;
    while ($i > 0) { $m = ($i - 1) % 26; $s = chr(65 + $m) . $s; $i = intdiv($i - 1, 26); }
    return $s;
}

function xlsx_esc(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

/** Build the workbook binary. Returns the raw .xlsx bytes. */
function xlsx_build(array $rows, ?array $colWidths = null): string
{
    // ── sheet body ──
    $sheetRows = '';
    foreach ($rows as $r => $cells) {
        $rowNum = $r + 1;
        $cellsXml = '';
        foreach ($cells as $c => $cell) {
            if ($cell === null || $cell === '') continue;
            $ref = xlsx_col($c) . $rowNum;
            $style = 0; $val = $cell; $type = null;
            if (is_array($cell)) {
                $val   = $cell['v'] ?? '';
                $style = (int) ($cell['s'] ?? 0);
                $type  = $cell['t'] ?? null;
            }
            if ($val === null || $val === '') continue;
            $isNum = ($type === 'n') || ($type !== 's' && is_numeric($val) && !is_string($val))
                     || ($type !== 's' && is_int($val) === false && is_float($val));
            // Decide numeric vs text: force by $type, else auto (numeric scalar → number).
            if ($type === 's') $isNum = false;
            elseif ($type === 'n') $isNum = true;
            else $isNum = is_int($val) || is_float($val);

            $sAttr = $style ? ' s="' . $style . '"' : '';
            if ($isNum) {
                $cellsXml .= '<c r="' . $ref . '"' . $sAttr . '><v>' . $val . '</v></c>';
            } else {
                $cellsXml .= '<c r="' . $ref . '"' . $sAttr . ' t="inlineStr"><is><t xml:space="preserve">'
                           . xlsx_esc((string) $val) . '</t></is></c>';
            }
        }
        $sheetRows .= '<row r="' . $rowNum . '">' . $cellsXml . '</row>';
    }

    $colsXml = '';
    if ($colWidths) {
        $colsXml = '<cols>';
        foreach ($colWidths as $i => $w) {
            $colsXml .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . (float) $w . '" customWidth="1"/>';
        }
        $colsXml .= '</cols>';
    }

    $sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
        . $colsXml
        . '<sheetData>' . $sheetRows . '</sheetData></worksheet>';

    // ── styles ──
    $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
      . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
      . '<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
      . '<fonts count="4">'
      .   '<font><sz val="11"/><name val="Arial"/></font>'                                   // 0 default
      .   '<font><b/><sz val="11"/><name val="Arial"/></font>'                               // 1 bold
      .   '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Arial"/></font>'        // 2 bold white
      .   '<font><b/><sz val="15"/><name val="Arial"/></font>'                               // 3 title
      . '</fonts>'
      . '<fills count="4">'
      .   '<fill><patternFill patternType="none"/></fill>'                                    // 0
      .   '<fill><patternFill patternType="gray125"/></fill>'                                 // 1 (reserved)
      .   '<fill><patternFill patternType="solid"><fgColor rgb="FF0D1B2A"/></patternFill></fill>' // 2 navy
      .   '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F5F1"/></patternFill></fill>' // 3 light teal
      . '</fills>'
      . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
      . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
      . '<cellXfs count="7">'
      .   '<xf numFmtId="0"   fontId="0" fillId="0" borderId="0" xfId="0"/>'                              // 0 default
      .   '<xf numFmtId="0"   fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                // 1 bold
      .   '<xf numFmtId="0"   fontId="2" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 2 head
      .   '<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'        // 3 money
      .   '<xf numFmtId="0"   fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/>'                // 4 title
      .   '<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>' // 5 bold money
      .   '<xf numFmtId="0"   fontId="1" fillId="3" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'  // 6 subhead
      . '</cellXfs>'
      . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
      . '</styleSheet>';

    $workbookXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
      . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
      . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
      . '<sheets><sheet name="Tracking" sheetId="1" r:id="rId1"/></sheets></workbook>';

    $workbookRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
      . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
      . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
      . '</Relationships>';

    $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
      . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
      . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
      . '</Relationships>';

    $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
      . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
      . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
      . '<Default Extension="xml" ContentType="application/xml"/>'
      . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
      . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
      . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
      . '</Types>';

    $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
    $zip = new ZipArchive();
    $zip->open($tmp, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', $contentTypes);
    $zip->addFromString('_rels/.rels', $rels);
    $zip->addFromString('xl/workbook.xml', $workbookXml);
    $zip->addFromString('xl/_rels/workbook.xml.rels', $workbookRels);
    $zip->addFromString('xl/styles.xml', $stylesXml);
    $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
    $zip->close();
    $bytes = file_get_contents($tmp);
    @unlink($tmp);
    return $bytes;
}

/** Build + stream an .xlsx download to the browser and exit. */
function xlsx_stream(string $filename, array $rows, ?array $colWidths = null): void
{
    $bytes = xlsx_build($rows, $colWidths);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . str_replace('"', '', $filename) . '"');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: no-store');
    echo $bytes;
    exit;
}
