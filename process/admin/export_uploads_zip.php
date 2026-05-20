<?php
session_start();

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    exit('Unauthorized access');
}

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('The PHP ZipArchive extension is not enabled on this server. Please backup the uploads and profiles folders manually.');
}

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
    'type' => 'Uploads Backup'
];

if (count($history) > 10) {
    $history = array_slice($history, -10);
}

@file_put_contents($logFile, json_encode($history, JSON_PRETTY_PRINT));

set_time_limit(600); // 10 minutes limit
ini_set('memory_limit', '512M');

$zip = new ZipArchive();
$zipFilename = 'ojt_uploads_backup_' . date('Y-m-d_His') . '.zip';
$tempDir = sys_get_temp_dir();
$zipPath = $tempDir . DIRECTORY_SEPARATOR . $zipFilename;

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    http_response_code(500);
    exit("Failed to create temporary zip archive.");
}

$dirsToZip = [
    'uploads' => realpath(__DIR__ . '/../../uploads'),
    'Assets/Images/profiles' => realpath(__DIR__ . '/../../Assets/Images/profiles')
];

$fileCount = 0;

foreach ($dirsToZip as $zipPrefix => $sourceDir) {
    if (!$sourceDir || !is_dir($sourceDir)) {
        continue;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            
            if (basename($filePath) === '.gitkeep') {
                continue;
            }

            // Get relative path
            $relativePath = substr($filePath, strlen($sourceDir) + 1);

            // Normalize directory separator to forward slashes for zip file compatibility
            $zipPathName = $zipPrefix . '/' . str_replace('\\', '/', $relativePath);

            $zip->addFile($filePath, $zipPathName);
            $fileCount++;
        }
    }
}

// If zip contains no files, add an empty placeholder so it's a valid zip
if ($fileCount === 0) {
    $zip->addFromString('readme.txt', 'This is a backup of the OJT Management System uploads and profiles directories. The folders are currently empty.');
}

$zip->close();

if (file_exists($zipPath)) {
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFilename . '"');
    header('Content-Length: ' . filesize($zipPath));
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Clear output buffer to prevent corrupted file
    if (ob_get_level()) {
        ob_end_clean();
    }
    flush();
    
    readfile($zipPath);
    unlink($zipPath);
    exit;
} else {
    http_response_code(500);
    exit("Failed to generate zip file.");
}
?>
