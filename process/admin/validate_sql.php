<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required.']);
    exit;
}
if (!isset($_FILES['sql_file'])) {
    if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH']) && $_SERVER['CONTENT_LENGTH'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'File too large. Max: ' . ini_get('post_max_size')]);
        exit;
    }
    echo json_encode(['status' => 'error', 'message' => 'No file provided.']);
    exit;
}

$file = $_FILES['sql_file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Upload error code: ' . $file['error']]);
    exit;
}
if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'sql') {
    echo json_encode(['status' => 'error', 'message' => 'Only .sql files are allowed.']);
    exit;
}

$sql = file_get_contents($file['tmp_name']);
if (empty(trim($sql))) {
    echo json_encode(['status' => 'error', 'message' => 'The uploaded file is empty.']);
    exit;
}

$errors   = [];
$warnings = [];
$info     = [];

function extractBlocks(string $sql): array {
    $blocks  = [];
    $pattern = '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?(\w+)[`"]?\s*\(/i';
    $offset  = 0;

    while (preg_match($pattern, $sql, $m, PREG_OFFSET_CAPTURE, $offset)) {
        $tableName  = $m[1][0];
        $blockStart = $m[0][1];
        $parenStart = $blockStart + strlen($m[0][0]) - 1;

        $depth = 0; $inSingle = false; $inDouble = false; $end = $parenStart;
        for ($i = $parenStart, $len = strlen($sql); $i < $len; $i++) {
            $c = $sql[$i];
            if ($c === "'" && !$inDouble) { $inSingle = !$inSingle; }
            elseif ($c === '"' && !$inSingle) { $inDouble = !$inDouble; }
            elseif (!$inSingle && !$inDouble) {
                if ($c === '(') $depth++;
                elseif ($c === ')') { $depth--; if ($depth === 0) { $end = $i; break; } }
            }
        }
        $semiPos = strpos($sql, ';', $end);
        if ($semiPos !== false) $end = $semiPos;

        $blocks[$tableName] = substr($sql, $blockStart, $end - $blockStart + 1);
        $offset = $end + 1;
    }
    return $blocks;
}

function parseColumns(string $block): array {
    $start = strpos($block, '(');
    $end   = strrpos($block, ')');
    if ($start === false || $end === false) return [];

    $body    = substr($block, $start + 1, $end - $start - 1);
    $lines   = preg_split('/,(?=\s*\n|\s*`)/m', $body);
    $columns = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        if (preg_match('/^\s*(PRIMARY\s+KEY|UNIQUE\s+(KEY|INDEX)?|KEY|INDEX|CONSTRAINT|FULLTEXT|SPATIAL)/i', $line)) continue;

        if (preg_match('/^[`"]?(\w+)[`"]?\s+(\w+(?:\s*\([^)]+\))?(?:\s+UNSIGNED)?(?:\s+ZEROFILL)?)/i', $line, $cm)) {
            $name = $cm[1];
            $type = strtolower(trim(preg_replace('/\s+/', ' ', $cm[2])));
            $notNull = (bool) preg_match('/\bNOT\s+NULL\b/i', $line);
            $default = null;
            if (preg_match('/\bDEFAULT\s+(\'[^\']*\'|"[^"]*"|\S+)/i', $line, $dm)) {
                $default = trim($dm[1], "'\"");
            }
            $columns[$name] = ['type' => $type, 'not_null' => $notNull, 'default' => $default, 'raw' => $line];
        }
    }
    return $columns;
}

function parsePrimaryKey(string $block): array {
    if (preg_match('/PRIMARY\s+KEY\s*\(([^)]+)\)/i', $block, $m)) {
        return array_map(fn($c) => trim($c, '`" '), explode(',', $m[1]));
    }
    return [];
}

function parseUniqueKeys(string $block): array {
    preg_match_all('/UNIQUE\s+(?:KEY|INDEX)\s+[`"]?(\w+)[`"]?\s*\(([^)]+)\)/i', $block, $m, PREG_SET_ORDER);
    $keys = [];
    foreach ($m as $match) {
        $keys[$match[1]] = array_map(fn($c) => trim($c, '`" '), explode(',', $match[2]));
    }
    return $keys;
}

function parseForeignKeys(string $block): array {
    preg_match_all(
        '/FOREIGN\s+KEY\s*\(([^)]+)\)\s*REFERENCES\s+[`"]?(\w+)[`"]?\s*\(([^)]+)\)/i',
        $block, $m, PREG_SET_ORDER
    );
    $fks = [];
    foreach ($m as $match) {
        $fks[] = [
            'col'       => trim($match[1], '`" '),
            'ref_table' => $match[2],
            'ref_col'   => trim($match[3], '`" '),
        ];
    }
    return $fks;
}

function hasBalancedParens(string $sql): bool {
    $depth = 0; $inSingle = false; $inDouble = false;
    for ($i = 0, $len = strlen($sql); $i < $len; $i++) {
        $c = $sql[$i];
        if ($c === "'" && !$inDouble)       { $inSingle = !$inSingle; }
        elseif ($c === '"' && !$inSingle)   { $inDouble = !$inDouble; }
        elseif (!$inSingle && !$inDouble)   {
            if ($c === '(') $depth++;
            elseif ($c === ')') { $depth--; if ($depth < 0) return false; }
        }
    }
    return $depth === 0;
}

$initPath = __DIR__ . '/../../config/init.sql';
$refBlocks  = [];
$refColumns = [];
$refPKs     = [];
$refUKs     = [];
$refFKs     = [];
$refVersion = null;

if (!file_exists($initPath)) {
    $errors[] = 'Server error: init.sql reference schema not found. Cannot validate.';
} else {
    $initSql    = file_get_contents($initPath);
    $refBlocks  = extractBlocks($initSql);

    $optionalTables = ['schema_version'];

    foreach ($refBlocks as $tbl => $blk) {
        $refColumns[$tbl] = parseColumns($blk);
        $refPKs[$tbl]     = parsePrimaryKey($blk);
        $refUKs[$tbl]     = parseUniqueKeys($blk);
        $refFKs[$tbl]     = parseForeignKeys($blk);
    }

    if (preg_match('/--\s*Version:\s*([\d.]+)/i', $initSql, $vm)) {
        $refVersion = $vm[1];
    }

    $info[] = 'Schema reference: init.sql loaded — ' . count($refBlocks) . ' table(s), '
              . array_sum(array_map('count', $refColumns)) . ' total column(s).';
}

$upBlocks = extractBlocks($sql);

$sizeKb = round(strlen($sql) / 1024, 2);
$info[]  = "File size: {$sizeKb} KB";

if (strpos($sql, "\0") !== false) {
    $errors[] = 'File contains null bytes — binary corruption detected.';
}
if (stripos($sql, 'SQLite format') !== false) {
    $errors[] = 'This is a SQLite binary file, not a MySQL dump.';
}
if (!preg_match('/\b(CREATE\s+TABLE|INSERT\s+INTO)\b/i', $sql)) {
    $errors[] = 'No CREATE TABLE or INSERT INTO found. Not a valid SQL dump.';
}
if (preg_match('/DROP\s+DATABASE/i', $sql)) {
    $errors[] = 'DROP DATABASE statement detected — not permitted for safety.';
}

$hasOjtHeader = stripos($sql, 'OJT Management System') !== false || stripos($sql, 'OJT System Backup') !== false;
if (!$hasOjtHeader && stripos($sql, 'MySQL dump') === false) {
    $warnings[] = 'No recognised dump signature — file may not be from this system.';
} else {
    $info[] = 'Dump signature verified.';
}

if ($refVersion) {
    if (preg_match('/--\s*Version:\s*([\d.]+)/i', $sql, $uvm)) {
        if (version_compare($uvm[1], $refVersion, '<')) {
            $warnings[] = "Schema version mismatch: backup is v{$uvm[1]}, current system is v{$refVersion}. Columns or tables may be missing.";
        } else {
            $info[] = "Schema version: backup v{$uvm[1]} — OK.";
        }
    } else {
        $warnings[] = "No schema version found in backup. Current system expects v{$refVersion}.";
    }
}

if (!empty($refBlocks)) {
    $missingRequired = [];
    $missingOptional = [];

    foreach (array_keys($refBlocks) as $requiredTable) {
        if (!isset($upBlocks[$requiredTable])) {
            if (in_array($requiredTable, $optionalTables ?? [], true)) {
                $missingOptional[] = $requiredTable;
            } else {
                $missingRequired[] = $requiredTable;
            }
        }
    }

    if (!empty($missingRequired)) {
        $errors[] = 'Missing ' . count($missingRequired) . ' required table(s): '
                    . implode(', ', $missingRequired) . '.';
    }
    if (!empty($missingOptional)) {
        $warnings[] = 'Missing optional system table(s): ' . implode(', ', $missingOptional)
                      . ' (will be created automatically on first import).';
    }
    if (empty($missingRequired) && empty($missingOptional)) {
        $info[] = 'All ' . count($refBlocks) . ' table(s) present.';
    } elseif (empty($missingRequired)) {
        $info[] = 'All required table(s) present.';
    }
}

foreach ($refColumns as $tbl => $refCols) {
    if (!isset($upBlocks[$tbl])) continue;

    $upCols = parseColumns($upBlocks[$tbl]);

    foreach ($refCols as $col => $refDef) {
        if (!isset($upCols[$col])) {
            $errors[] = "Table `{$tbl}`: missing column `{$col}`.";
            continue;
        }

        $upDef = $upCols[$col];

        $refType = preg_replace('/\s+/', ' ', trim($refDef['type']));
        $upType  = preg_replace('/\s+/', ' ', trim($upDef['type']));
        if ($refType !== $upType) {
            $errors[] = "Table `{$tbl}`.`{$col}`: type mismatch — expected `{$refType}`, got `{$upType}`.";
        }

        if ($refDef['not_null'] !== $upDef['not_null']) {
            $exp = $refDef['not_null'] ? 'NOT NULL' : 'NULL';
            $got = $upDef['not_null']  ? 'NOT NULL' : 'NULL';
            $warnings[] = "Table `{$tbl}`.`{$col}`: nullability differs — expected {$exp}, got {$got}.";
        }
    }
}

foreach ($refPKs as $tbl => $refPK) {
    if (!isset($upBlocks[$tbl]) || empty($refPK)) continue;
    $upPK = parsePrimaryKey($upBlocks[$tbl]);
    if (array_diff($refPK, $upPK) || array_diff($upPK, $refPK)) {
        $errors[] = "Table `{$tbl}`: PRIMARY KEY mismatch — expected (" . implode(', ', $refPK) . "), got (" . implode(', ', $upPK) . ").";
    }
}

foreach ($refUKs as $tbl => $refUniqueKeys) {
    if (!isset($upBlocks[$tbl]) || empty($refUniqueKeys)) continue;
    $upUniqueKeys = parseUniqueKeys($upBlocks[$tbl]);
    foreach ($refUniqueKeys as $keyName => $refKeyCols) {
        $found = false;
        foreach ($upUniqueKeys as $upKeyCols) {
            if (!array_diff($refKeyCols, $upKeyCols) && !array_diff($upKeyCols, $refKeyCols)) {
                $found = true; break;
            }
        }
        if (!$found) {
            $warnings[] = "Table `{$tbl}`: UNIQUE KEY `{$keyName}` (" . implode(', ', $refKeyCols) . ") not found in backup.";
        }
    }
}

foreach ($refFKs as $tbl => $refTableFKs) {
    if (!isset($upBlocks[$tbl]) || empty($refTableFKs)) continue;
    $upFKs = parseForeignKeys($upBlocks[$tbl]);
    foreach ($refTableFKs as $refFK) {
        $found = false;
        foreach ($upFKs as $upFK) {
            if ($upFK['col'] === $refFK['col'] && $upFK['ref_table'] === $refFK['ref_table']) {
                $found = true; break;
            }
        }
        if (!$found) {
            $warnings[] = "Table `{$tbl}`: FK on `{$refFK['col']}` → `{$refFK['ref_table']}` not found in backup.";
        }
    }
}

$logFile = __DIR__ . '/../../config/export_history.json';
if (!file_exists($logFile)) {
    $warnings[] = 'No export history found. Export a fresh backup before importing.';
} else {
    $history = json_decode(file_get_contents($logFile), true) ?: [];
    if (empty($history)) {
        $warnings[] = 'Export history is empty. Ensure you have a current backup.';
    } else {
        $last     = end($history);
        $lastDate = $last['date'] ?? '';
        $daysDiff = (time() - strtotime($lastDate)) / 86400;
        if ($daysDiff > 7) {
            $warnings[] = "Last backup was {$lastDate} (" . ceil($daysDiff) . " days ago). Create a fresh backup first.";
        } else {
            $info[] = "Recent backup on record: {$lastDate} at " . ($last['time'] ?? '?') . ".";
        }
    }
}

$freeBytes = @disk_free_space(__DIR__);
if ($freeBytes !== false) {
    $needed = filesize($file['tmp_name']) * 4; // 4× safety margin
    if ($freeBytes < $needed) {
        $warnings[] = 'Low disk space: need ~' . round($needed / 1048576, 1) . ' MB but only '
                      . round($freeBytes / 1048576, 1) . ' MB free.';
    } else {
        $info[] = 'Disk space OK: ' . round($freeBytes / 1048576, 1) . ' MB free.';
    }
}

$stripped = preg_replace('/--[^\n]*\n/m', "\n", $sql);
$stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);

if (!hasBalancedParens($stripped)) {
    $errors[] = 'Unbalanced parentheses detected — the SQL file appears malformed or truncated.';
} else {
    $stmtCount = count(array_filter(array_map('trim', explode(';', $stripped))));
    $info[]    = "Syntax check OK: ~{$stmtCount} statement(s), parentheses balanced.";
}

if (substr_count($stripped, "'") % 2 !== 0) {
    $warnings[] = 'Odd number of single-quotes — possible unclosed string literal.';
}

if (stripos($sql, 'SET FOREIGN_KEY_CHECKS') === false) {
    $warnings[] = 'SET FOREIGN_KEY_CHECKS not found — import may fail on FK constraints.';
}

if (!empty($errors)) {
    echo json_encode([
        'status'   => 'invalid',
        'message'  => 'Validation failed — ' . count($errors) . ' error(s) must be resolved before importing.',
        'errors'   => $errors,
        'warnings' => $warnings,
        'info'     => $info,
    ]);
    exit;
}

echo json_encode([
    'status'   => 'valid',
    'message'  => 'All checks passed. File is safe to import.',
    'errors'   => [],
    'warnings' => $warnings,
    'info'     => $info,
]);
