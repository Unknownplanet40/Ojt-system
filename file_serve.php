<?php

session_start();
require_once __DIR__ . '/config/db.php';

if (!isset($_SESSION['user_uuid'])) {
    http_response_code(401);
    header('Location: Src/Pages/ErrorPage.php?error=401');
    exit;
}

$allowedRoles = ['admin', 'coordinator', 'student'];
if (!in_array($_SESSION['user_role'], $allowedRoles)) {
    http_response_code(403);
    header('Location: Src/Pages/ErrorPage.php?error=403');
    exit;
}

try {
    $conn = new mysqli($host, $username, $password, $dbname);
} catch (Exception $e) {
    http_response_code(500);
    header('Location: Src/Pages/ErrorPage.php?error=500');
    exit('Database connection failed');
}

$documentUuid = trim($_GET['uuid'] ?? '');
$forModules   = ['coordinatorView', 'studentView', 'adminView', 'companyView'];
$documentFor  = $_GET['for'] ?? '';
$documentFor  = in_array($documentFor, $forModules, true) ? $documentFor : 'companyView';

function UUID_convert_Student($conn, $uuid): ?string
{
    $stmt = $conn->prepare("SELECT uuid FROM student_profiles WHERE user_uuid = ?");
    $stmt->bind_param("s", $uuid);
    $stmt->execute();
    $result = $stmt->get_result();
    $studentProfileUuid = null;
    if ($result->num_rows > 0) {
        $studentProfileUuid = $result->fetch_assoc()['uuid'];
    }

    return $studentProfileUuid;
}

function serveFile(string $absolutePath, string $contentType = 'application/octet-stream', bool $download = false, string $downloadName = 'document'): void
{
    $realPath   = realpath($absolutePath);
    $uploadRoot = realpath(__DIR__ . '/uploads/');

    if (!$realPath || !$uploadRoot || !str_starts_with($realPath, $uploadRoot)) {
        http_response_code(403);
        exit('Access denied');
    }

    if (!file_exists($realPath)) {
        http_response_code(404);
        exit('File not found on server');
    }

    $fileSize = filesize($realPath);

    if (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: ' . $contentType);
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: private, max-age=3600, must-revalidate');
    header('Pragma: public');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');
    header(
        'Content-Disposition: ' .
        ($download ? 'attachment' : 'inline') .
        '; filename="' . addslashes($downloadName) . '"'
    );

    readfile($realPath);
    exit;
}

$resourceType = trim($_GET['type'] ?? '');
$role = $_SESSION['user_role'] ?? '';

if ($resourceType === 'requirement') {
    $reqUuid = trim($_GET['req_uuid'] ?? '');

    if ($reqUuid === '') {
        http_response_code(400);
        exit('Missing requirement UUID.');
    }

    $stmt = $conn->prepare("\n        SELECT sr.file_path, sr.file_name, sr.student_uuid\n        FROM student_requirements sr\n        WHERE sr.uuid = ? LIMIT 1\n    ");
    $stmt->bind_param('s', $reqUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req || empty($req['file_path'])) {
        http_response_code(404);
        exit('File not found.');
    }

    if ($role === 'student' && $req['student_uuid'] !== ($_SESSION['profile_uuid'] ?? '')) {
        http_response_code(403);
        exit('Access denied.');
    }

    if ($role === 'coordinator') {
        $stmt = $conn->prepare("\n            SELECT id FROM student_profiles\n            WHERE uuid = ? AND coordinator_uuid = ? LIMIT 1\n        ");
        $stmt->bind_param('ss', $req['student_uuid'], $_SESSION['profile_uuid']);
        $stmt->execute();
        $allowed = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if (!$allowed) {
            http_response_code(403);
            exit('Access denied.');
        }
    }

    if (!in_array($role, ['student', 'coordinator', 'admin'], true)) {
        http_response_code(403);
        exit('Access denied.');
    }

    $absolutePath = __DIR__ . '/' . $req['file_path'];
    $download = (($_GET['action'] ?? 'inline') === 'download');
    $downloadName = basename($req['file_name'] ?? 'requirement.pdf');

    serveFile($absolutePath, 'application/pdf', $download, $downloadName);
}

if (empty($documentUuid)) {
    http_response_code(400);
    header('Location: Src/Pages/ErrorPage.php?error=400');
    exit('Missing document ID');
}

$userRole = $_SESSION['user_role'] ?? '';
$userUuid = $_SESSION['user_uuid'] ?? '';

if ($documentFor === 'companyView') {
    $stmt = $conn->prepare("
        SELECT file_path, file_name
        FROM company_documents
        WHERE uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $documentUuid);

} elseif ($documentFor === 'studentView' && $userRole === 'student') {
    $studentProfileUuid = UUID_convert_Student($conn, $userUuid);

    if (!$studentProfileUuid) {
        http_response_code(403);
        header('Location: Src/Pages/ErrorPage.php?error=403');
        exit;
    }

    $stmt = $conn->prepare("
        SELECT file_path, file_name
        FROM student_requirements
        WHERE uuid = ? AND student_uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('ss', $documentUuid, $studentProfileUuid);

} elseif (
    ($documentFor === 'coordinatorView' && $userRole === 'coordinator') ||
    ($documentFor === 'adminView'       && $userRole === 'admin')
) {
    $stmt = $conn->prepare("
        SELECT file_path, file_name
        FROM student_requirements
        WHERE uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $documentUuid);

} else {
    http_response_code(403);
    header('Location: Src/Pages/ErrorPage.php?error=403');
    exit;
}

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
$fileSize = filesize($realPath);

// Determine MIME type based on file extension
$ext = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$mimeTypes = [
    'pdf'  => 'application/pdf',
    'doc'  => 'application/msword',
    'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'xls'  => 'application/vnd.ms-excel',
    'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'ppt'  => 'application/vnd.ms-powerpoint',
    'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'txt'  => 'text/plain',
];

$mimeType = $mimeTypes[$ext] ?? 'application/octet-stream';

// Clear output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers before output
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, max-age=3600, must-revalidate');
header('Pragma: public');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

if ($action === 'download') {
    header('Content-Disposition: attachment; filename="' . addslashes($fileName) . '"');
} else {
    header('Content-Disposition: inline; filename="' . addslashes($fileName) . '"');
}

// Serve file
readfile($realPath);
exit;
