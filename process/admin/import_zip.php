<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit;
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded or file upload error.']);
    exit;
}

$tempFile = $_FILES['zip_file']['tmp_name'];

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'PHP ZipArchive extension is not enabled on this server.']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($tempFile) !== true) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to open file as ZIP archive.']);
    exit;
}

$targetBase = realpath(__DIR__ . '/../../');
if (!$targetBase) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to resolve project base path.']);
    exit;
}

$validFolders = ['uploads/', 'Assets/Images/profiles/'];
$extractedCount = 0;
$skippedCount = 0;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    $entryName = $stat['name'];

    // Security Check: Zip Slip vulnerability check (prevent directory traversal)
    if (strpos($entryName, '..') !== false || strpos($entryName, '\\') !== false) {
        $skippedCount++;
        continue;
    }

    // Must match valid directories
    $matched = false;
    foreach ($validFolders as $folder) {
        if (stripos($entryName, $folder) === 0) {
            $matched = true;
            break;
        }
    }

    if (!$matched) {
        $skippedCount++;
        continue;
    }

    $targetPath = $targetBase . '/' . $entryName;

    // Is it a directory? (ends with /)
    if (substr($entryName, -1) === '/') {
        if (!is_dir($targetPath)) {
            if (!@mkdir($targetPath, 0755, true)) {
                $skippedCount++;
            }
        }
        continue;
    }

    // Ensure parent dir exists
    $parentDir = dirname($targetPath);
    if (!is_dir($parentDir)) {
        if (!@mkdir($parentDir, 0755, true)) {
            $skippedCount++;
            continue;
        }
    }

    // Write file content
    $content = $zip->getFromIndex($i);
    if ($content !== false) {
        if (@file_put_contents($targetPath, $content) !== false) {
            $extractedCount++;
        } else {
            $skippedCount++;
        }
    } else {
        $skippedCount++;
    }
}

$zip->close();

// Log history
$logFile = '../../config/export_history.json';
$history = [];
if (file_exists($logFile)) {
    $history = json_decode(file_get_contents($logFile), true) ?: [];
}

$history[] = [
    'date' => date('Y-m-d'),
    'time' => date('H:i'),
    'status' => 'Success',
    'type' => 'Files Import'
];

if (count($history) > 10) {
    $history = array_slice($history, -10);
}

@file_put_contents($logFile, json_encode($history, JSON_PRETTY_PRINT));

echo json_encode([
    'status' => 'success',
    'message' => "Successfully restored $extractedCount files. Skipped $skippedCount entries."
]);
exit;
