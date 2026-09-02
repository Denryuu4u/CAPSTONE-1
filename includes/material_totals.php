<?php
/**
 * Reader for Cabinet Vision "Material Summary" (material totals) exports.
 *
 * These are real BIFF8 .xls files (OLE compound documents) produced by Crystal
 * Reports. This parser is dependency-free: it reads the OLE container to locate
 * the Workbook stream, then parses the BIFF records (SST + cell records) to
 * reconstruct the sheet, and extracts the material rows.
 *
 * Column layout (verified against "sample_material totals - opt.xls"):
 *   col 2  = Material code/name
 *   col 15 = Totals + Waste (quantity)
 *   col 18 = Unit (Pieces / M / Sq M)
 *   col 20 = Cost
 *
 * Public API:
 *   parse_material_totals(string $contents): array
 *     -> ['job_name'=>?string, 'items'=>[['material','qty','unit','cost'], ...]]
 */

/* ── OLE compound document: locate + read the Workbook stream ─────────── */
function mt_ole_workbook(string $data): string
{
    $u16 = fn($o) => unpack('v', substr($data, $o, 2))[1];
    $u32 = fn($o) => unpack('V', substr($data, $o, 4))[1];

    if (substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
        throw new RuntimeException('Not an OLE compound document (.xls).');
    }

    $secSize   = 1 << $u16(0x1E);
    $miniSize  = 1 << $u16(0x20);
    $numFat    = $u32(0x2C);
    $dirStart  = $u32(0x30);
    $miniCut   = $u32(0x38);
    $miniFatStart = $u32(0x3C);
    $numMiniFat   = $u32(0x40);
    $difatStart   = $u32(0x44);

    $readSector = fn($s) => substr($data, ($s + 1) * $secSize, $secSize);

    // DIFAT → list of FAT sectors (first 109 in header, then chain).
    $fatSectors = [];
    for ($i = 0; $i < 109; $i++) {
        $s = $u32(0x4C + $i * 4);
        if ($s === 0xFFFFFFFF) break;
        $fatSectors[] = $s;
    }
    $ds = $difatStart;
    while ($ds !== 0xFFFFFFFF && $ds !== 0xFFFFFFFE && count($fatSectors) < $numFat) {
        $sec = $readSector($ds);
        $cnt = intdiv($secSize, 4) - 1;
        for ($i = 0; $i < $cnt; $i++) {
            $s = unpack('V', substr($sec, $i * 4, 4))[1];
            if ($s !== 0xFFFFFFFF) $fatSectors[] = $s;
        }
        $ds = unpack('V', substr($sec, $cnt * 4, 4))[1];
    }

    // Build the FAT.
    $fat = [];
    foreach ($fatSectors as $fs) {
        $sec = $readSector($fs);
        for ($i = 0; $i < $secSize; $i += 4) $fat[] = unpack('V', substr($sec, $i, 4))[1];
    }

    $chain = function ($start) use ($fat, $readSector) {
        $out = ''; $s = $start; $guard = 0;
        while ($s !== 0xFFFFFFFE && $s !== 0xFFFFFFFF && isset($fat[$s]) && $guard++ < 200000) {
            $out .= $readSector($s);
            $s = $fat[$s];
        }
        return $out;
    };

    // Directory entries.
    $dir = $chain($dirStart);
    $entries = [];
    for ($off = 0; $off + 128 <= strlen($dir); $off += 128) {
        $e = substr($dir, $off, 128);
        $nameLen = unpack('v', substr($e, 0x40, 2))[1];
        if ($nameLen <= 0) continue;
        $name  = @iconv('UTF-16LE', 'UTF-8//IGNORE', substr($e, 0, $nameLen - 2));
        $entries[] = [
            'name'  => $name,
            'type'  => ord($e[0x42]),
            'start' => unpack('V', substr($e, 0x74, 4))[1],
            'size'  => unpack('V', substr($e, 0x78, 4))[1],
        ];
    }

    // Root entry → mini-stream container; mini FAT for small streams.
    $root = null;
    foreach ($entries as $e) if ($e['type'] === 5) { $root = $e; break; }
    $miniFat = [];
    if ($numMiniFat > 0) {
        $mf = $chain($miniFatStart);
        for ($i = 0; $i + 4 <= strlen($mf); $i += 4) $miniFat[] = unpack('V', substr($mf, $i, 4))[1];
    }
    $miniStream = $root ? $chain($root['start']) : '';

    $readStream = function ($e) use ($chain, $miniCut, $miniStream, $miniFat, $miniSize) {
        if ($e['size'] >= $miniCut) return substr($chain($e['start']), 0, $e['size']);
        $out = ''; $s = $e['start']; $guard = 0;
        while ($s !== 0xFFFFFFFE && $s !== 0xFFFFFFFF && isset($miniFat[$s]) && $guard++ < 200000) {
            $out .= substr($miniStream, $s * $miniSize, $miniSize);
            $s = $miniFat[$s];
        }
        return substr($out, 0, $e['size']);
    };

    foreach ($entries as $e) {
        if ($e['type'] === 2 && (strcasecmp($e['name'], 'Workbook') === 0 || strcasecmp($e['name'], 'Book') === 0)) {
            return $readStream($e);
        }
    }
    throw new RuntimeException('Workbook stream not found in .xls.');
}

/* ── BIFF: decode an RK number ───────────────────────────────────────── */
function mt_rk_decode(int $rk): float
{
    $cents = $rk & 1;
    $isInt = $rk & 2;
    $val   = $rk & 0xFFFFFFFC;
    if ($isInt) {
        $num = $val >> 2;
        if ($num & 0x20000000) $num -= 0x40000000;
        $d = (float) $num;
    } else {
        $d = unpack('d', "\x00\x00\x00\x00" . pack('V', $val))[1];
    }
    return $cents ? $d / 100.0 : $d;
}

/* ── BIFF: shared string table (handles CONTINUE, common case) ───────── */
function mt_parse_sst(string $wb): array
{
    $len = strlen($wb); $pos = 0;
    while ($pos + 4 <= $len) {
        $type = unpack('v', substr($wb, $pos, 2))[1];
        $reclen = unpack('v', substr($wb, $pos + 2, 2))[1];
        $data = substr($wb, $pos + 4, $reclen);
        $pos += 4 + $reclen;
        if ($type === 0x00FC) {
            $buf = $data;
            while ($pos + 4 <= $len) {
                $t2 = unpack('v', substr($wb, $pos, 2))[1];
                $l2 = unpack('v', substr($wb, $pos + 2, 2))[1];
                if ($t2 === 0x003C) { $buf .= substr($wb, $pos + 4, $l2); $pos += 4 + $l2; }
                else break;
            }
            return mt_sst_extract($buf);
        }
    }
    return [];
}

function mt_sst_extract(string $buf): array
{
    $n = strlen($buf);
    $cstUnique = unpack('V', substr($buf, 4, 4))[1];
    $p = 8; $out = [];
    for ($i = 0; $i < $cstUnique && $p + 3 <= $n; $i++) {
        $cch = unpack('v', substr($buf, $p, 2))[1];
        $grbit = ord($buf[$p + 2]);
        $p += 3;
        $fHigh = $grbit & 0x01; $fExt = $grbit & 0x04; $fRich = $grbit & 0x08;
        $cRun = 0; $cbExt = 0;
        if ($fRich) { $cRun = unpack('v', substr($buf, $p, 2))[1]; $p += 2; }
        if ($fExt)  { $cbExt = unpack('V', substr($buf, $p, 4))[1]; $p += 4; }
        if ($fHigh) { $str = @iconv('UTF-16LE', 'UTF-8//IGNORE', substr($buf, $p, $cch * 2)); $p += $cch * 2; }
        else        { $str = substr($buf, $p, $cch); $p += $cch; }
        if ($fRich) $p += $cRun * 4;
        if ($fExt)  $p += $cbExt;
        $out[] = $str;
    }
    return $out;
}

/* ── BIFF: cell records → rows[row][col] ─────────────────────────────── */
function mt_parse_cells(string $wb, array $sst): array
{
    $cells = []; $len = strlen($wb); $pos = 0;
    while ($pos + 4 <= $len) {
        $type = unpack('v', substr($wb, $pos, 2))[1];
        $reclen = unpack('v', substr($wb, $pos + 2, 2))[1];
        $data = substr($wb, $pos + 4, $reclen);
        $pos += 4 + $reclen;
        switch ($type) {
            case 0x00FD: // LABELSST
                $cells[unpack('v', substr($data, 0, 2))[1]][unpack('v', substr($data, 2, 2))[1]]
                    = $sst[unpack('V', substr($data, 6, 4))[1]] ?? '';
                break;
            case 0x0204: // LABEL
                $r = unpack('v', substr($data, 0, 2))[1]; $c = unpack('v', substr($data, 2, 2))[1];
                $cch = unpack('v', substr($data, 6, 2))[1];
                $cells[$r][$c] = substr($data, 8, $cch);
                break;
            case 0x0203: // NUMBER
                $cells[unpack('v', substr($data, 0, 2))[1]][unpack('v', substr($data, 2, 2))[1]]
                    = unpack('d', substr($data, 6, 8))[1];
                break;
            case 0x027E: // RK
                $cells[unpack('v', substr($data, 0, 2))[1]][unpack('v', substr($data, 2, 2))[1]]
                    = mt_rk_decode(unpack('V', substr($data, 6, 4))[1]);
                break;
            case 0x00BD: // MULRK
                $r = unpack('v', substr($data, 0, 2))[1];
                $c1 = unpack('v', substr($data, 2, 2))[1];
                $c2 = unpack('v', substr($data, $reclen - 2, 2))[1];
                $off = 4;
                for ($c = $c1; $c <= $c2; $c++) {
                    $cells[$r][$c] = mt_rk_decode(unpack('V', substr($data, $off + 2, 4))[1]);
                    $off += 6;
                }
                break;
        }
    }
    return $cells;
}

/**
 * Parse a material-totals export into line items.
 *
 * @return array{job_name:?string, items:array<int,array{material:string,qty:float,unit:string,cost:float}>}
 */
function parse_material_totals(string $contents): array
{
    $wb    = mt_ole_workbook($contents);
    $sst   = mt_parse_sst($wb);
    $cells = mt_parse_cells($wb, $sst);
    ksort($cells);

    $jobName = null;
    $items   = [];
    foreach ($cells as $cols) {
        // Job name can appear in any early column; scan for it.
        if ($jobName === null) {
            foreach ($cols as $v) {
                if (is_string($v) && stripos($v, 'Job Name:') !== false) {
                    $jn = trim(preg_replace('/^.*Job Name:\s*/is', '', $v));
                    $jobName = preg_split('/[\r\n]/', $jn)[0] ?? $jn;
                    break;
                }
            }
        }

        $material = isset($cols[2]) ? trim((string) $cols[2]) : '';

        if ($material === '' || strcasecmp($material, 'Material') === 0) continue;
        if (preg_match('/^(Job |Material Summary|Material Cost|Generated|Page )/i', $material)) continue;

        // A data row has a numeric "Totals + Waste" quantity.
        $qty  = $cols[15] ?? ($cols[7] ?? null);
        $unit = isset($cols[18]) ? trim((string) $cols[18]) : (isset($cols[12]) ? trim((string) $cols[12]) : '');
        $cost = $cols[20] ?? ($cols[21] ?? 0);
        if (!is_numeric($qty)) continue;

        $items[] = [
            'material' => $material,
            'qty'      => round((float) $qty, 4),
            'unit'     => $unit,
            'cost'     => round((float) $cost, 2),
        ];
    }

    return ['job_name' => $jobName, 'items' => $items];
}
