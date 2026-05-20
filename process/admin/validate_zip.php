<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'invalid', 'message' => 'Unauthorized access.', 'errors' => ['Unauthorized access']]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'invalid', 'message' => 'Method not allowed.', 'errors' => ['Method not allowed']]);
    exit;
}

if (!isset($_FILES['zip_file']) || $_FILES['zip_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['status' => 'invalid', 'message' => 'No file uploaded or file upload error.', 'errors' => ['File upload error']]);
    exit;
}

$tempFile = $_FILES['zip_file']['tmp_name'];

if (!class_exists('ZipArchive')) {
    http_response_code(500);
    echo json_encode(['status' => 'invalid', 'message' => 'PHP ZipArchive extension is not enabled on this server.', 'errors' => ['ZipArchive extension not available']]);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($tempFile) !== true) {
    echo json_encode(['status' => 'invalid', 'message' => 'Failed to open file as ZIP archive.', 'errors' => ['Invalid ZIP archive format']]);
    exit;
}

$errors = [];
$warnings = [];
$info = [];
$fileCount = 0;
$validFolders = ['uploads/', 'Assets/Images/profiles/'];
$hasValidPaths = false;

for ($i = 0; $i < $zip->numFiles; $i++) {
    $stat = $zip->statIndex($i);
    $entryName = $stat['name'];

    // Security Check: Zip Slip vulnerability check (prevent directory traversal)
    if (strpos($entryName, '..') !== false || strpos($entryName, '\\') !== false) {
        $errors[] = "Security warning: Traversal characters detected in file: " . htmlspecialchars($entryName);
        continue;
    }

    $matched = false;
    foreach ($validFolders as $folder) {
        // Check if the path starts with the allowed prefix
        if (stripos($entryName, $folder) === 0) {
            $matched = true;
            $hasValidPaths = true;
            break;
        }
    }

    if (!$matched && $entryName !== 'readme.txt') {
        $warnings[] = "Ignored file outside target directories: " . htmlspecialchars($entryName);
    } else {
        $fileCount++;
    }
}

$zip->close();

if (count($errors) > 0) {
    echo json_encode([
        'status' => 'invalid',
        'message' => 'Validation failed due to security or integrity concerns.',
        'errors' => $errors,
        'warnings' => $warnings,
        'info' => $info
    ]);
    exit;
}

if (!$hasValidPaths) {
    echo json_encode([
        'status' => 'invalid',
        'message' => 'Validation failed. The ZIP does not contain any files for uploads/ or Assets/Images/profiles/.',
        'errors' => ['No backup assets found in ZIP file.'],
        'warnings' => $warnings,
        'info' => $info
    ]);
    exit;
}

$info[] = "ZIP contains $fileCount backup files ready to restore.";
$info[] = "Target directories matches: uploads/ and Assets/Images/profiles/.";

echo json_encode([
    'status' => 'valid',
    'message' => 'ZIP validation successful. Ready to import.',
    'errors' => [],
    'warnings' => $warnings,
    'info' => $info
]);
exit;
