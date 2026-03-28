<?php

session_start();
require_once __DIR__ . '/Assets/database/dbconfig.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    header('Location: Src/Pages/ErrorPage.php?error=401');
    exit;
}

$allowedRoles = ['admin', 'coordinator'];
if (!in_array($_SESSION['user']['role'], $allowedRoles)) {
    http_response_code(403);
    header('Location: Src/Pages/ErrorPage.php?error=403');
    exit;
}

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

$documentUuid = trim($_GET['uuid'] ?? '');

if (empty($documentUuid)) {
    http_response_code(400);
    header('Location: Src/Pages/ErrorPage.php?error=400');
    exit('Missing document ID');
}

$stmt = $conn->prepare("
    SELECT file_path, file_name
    FROM company_documents
    WHERE uuid = ?
    LIMIT 1
");
$stmt->bind_param('s', $documentUuid);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    http_response_code(404);
    header('Location: Src/Pages/ErrorPage.php?error=404');
    exit('File not found');
}

$absolutePath = __DIR__ . '/' . $doc['file_path'];
$realPath     = realpath($absolutePath);
$uploadRoot   = realpath(__DIR__ . '/uploads/');

if (!$realPath || !$uploadRoot || !str_starts_with($realPath, $uploadRoot)) {
    http_response_code(403);
    header('Location: Src/Pages/ErrorPage.php?error=403');
    exit('Access denied');
}

if (!file_exists($realPath)) {
    http_response_code(404);
    header('Location: Src/Pages/ErrorPage.php?error=404');
    exit('File not found on server');
}

$action   = $_GET['action'] ?? 'inline';
$fileName = basename($doc['file_name']);
$mimeType = mime_content_type($realPath) ?: 'application/pdf';

header('Content-Type: '   . $mimeType);
header('Content-Length: ' . filesize($realPath));
header('Cache-Control: private, no-cache, no-store');
header('Pragma: no-cache');

if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
} else {
    header('Content-Disposition: inline; filename="' . $fileName . '"');
}

readfile($realPath);
exit;
