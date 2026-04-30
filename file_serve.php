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

function servePdfWithTitle(string $absolutePath, string $tabTitle, string $fileName): void
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

    $escapedTitle    = htmlspecialchars($tabTitle);
    $escapedFileName = htmlspecialchars($fileName);

    $params           = $_GET;
    $params['action'] = 'download';
    $downloadUrl      = '?' . http_build_query($params);
    $base64Pdf = base64_encode(file_get_contents($realPath));

    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>{$escapedTitle}</title>
      <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { height: 100%; overflow: hidden; background: #3c3c3c; }

        .toolbar {
          position: fixed;
          top: 0; left: 0; right: 0;
          height: 46px;
          background: #3c3c3c;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 0 16px;
          z-index: 100;
          gap: 12px;
        }
        .toolbar-left {
          display: flex;
          align-items: center;
          gap: 10px;
          min-width: 0;
        }
        .doc-icon { font-size: 17px; flex-shrink: 0; }
        .toolbar-title {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
          font-size: 13px;
          font-weight: 500;
          color: #e5e7eb;
          white-space: nowrap;
          overflow: hidden;
          text-overflow: ellipsis;
        }
        .toolbar-right {
          display: flex;
          gap: 8px;
          flex-shrink: 0;
        }
        .btn-tool {
          font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
          font-size: 12px;
          font-weight: 500;
          padding: 5px 13px;
          border-radius: 6px;
          border: none;
          cursor: pointer;
          text-decoration: none;
          display: inline-flex;
          align-items: center;
          gap: 5px;
          transition: background .15s;
        }
        .btn-back {
          background: #2d2d2f;
          color: #d1d5db;
        }
        .btn-back:hover { background: #3a3a3c; }
        .btn-download {
          background: #0F6E56;
          color: #fff;
          display: none;
        }
        .btn-download:hover { background: #0d5f49; }

        .pdf-container {
          position: fixed;
          top: 46px;
          left: 0; right: 0; bottom: 0;
        }
        iframe {
          width: 100%;
          height: 100%;
          border: none;
          display: block;
        }
      </style>
    </head>
    <body>

      <div class="toolbar">
        <div class="toolbar-left">
          <span class="doc-icon">📄</span>
          <span class="toolbar-title">{$escapedTitle}</span>
        </div>
        <div class="toolbar-right">
          <button class="btn-tool btn-back" onclick="if (window.history.length > 1) { window.history.back(); } else { window.close(); }">← Back</button>
          <a class="btn-tool btn-download" href="{$downloadUrl}">⬇ Download</a>
        </div>
      </div>

      <div class="pdf-container" onload="document.querySelector('.btn-download').style.display = 'inline-flex';">
        <iframe src="data:application/pdf;base64,{$base64Pdf}" title="{$escapedTitle} - {$escapedFileName}">
           <p>Your browser does not support iframes. Please <a href="{$downloadUrl}">download the PDF</a> to view it.</p>
        </iframe>
      </div>

    </body>
    </html>
    HTML;
    exit;
}

$resourceType = trim($_GET['type'] ?? '');
$role = $_SESSION['user_role'] ?? '';

if ($resourceType === 'requirement') {
    $reqUuid = trim($_GET['req_uuid'] ?? '');

    if ($reqUuid === '') {
        http_response_code(400);
        header('Location: Src/Pages/ErrorPage.php?error=400');
        exit('Missing requirement UUID');
    }

    $stmt = $conn->prepare("
        SELECT sr.file_path, sr.file_name, sr.student_uuid, sr.req_type
        FROM student_requirements sr
        WHERE sr.uuid = ? LIMIT 1
    ");
    $stmt->bind_param('s', $reqUuid);
    $stmt->execute();
    $req = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$req || empty($req['file_path'])) {
        http_response_code(404);
        header('Location: Src/Pages/ErrorPage.php?error=404');
        exit('File not found');
    }

    if ($role === 'student' && $req['student_uuid'] !== ($_SESSION['profile_uuid'] ?? '')) {
        http_response_code(403);
        header('Location: Src/Pages/ErrorPage.php?error=403');
        exit('Access denied.');
    }

    if ($role === 'coordinator') {
        $stmt = $conn->prepare("
            SELECT id FROM student_profiles
            WHERE uuid = ? AND coordinator_uuid = ? LIMIT 1
        ");
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
    $download     = (($_GET['action'] ?? 'inline') === 'download');
    $downloadName = basename($req['file_name'] ?? 'requirement.pdf');

    $reqLabels = [
        'medical_certificate' => 'Medical Certificate',
        'parental_consent'    => 'Parental Consent Form',
        'insurance'           => 'Student Insurance',
        'nbi_clearance'       => 'NBI Clearance',
        'resume'              => 'Resume / CV',
        'guardian_form'       => 'Guardian Information Form',
    ];
    $reqType  = $req['req_type'] ?? '';
    $tabTitle = $reqLabels[$reqType] ?? 'Requirement Document';

    if ($download) {
        serveFile($absolutePath, 'application/pdf', true, $downloadName);
    } else {
        servePdfWithTitle($absolutePath, $tabTitle, $downloadName);
    }
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
        SELECT file_path, file_name, student_uuid, req_type
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
if ($action !== 'download' && $mimeType === 'application/pdf') {
    $docTypeLabel = [
        'moa'       => 'MOA',
        'nda'       => 'NDA',
        'insurance' => 'Insurance',
        'bir_cert'  => 'BIR Certificate',
        'sec_dti'   => 'SEC/DTI',
        'other'     => 'Document',
    ];

    if ($documentFor === 'companyView') {
        $stmt = $conn->prepare("
            SELECT c.name AS company_name, cd.doc_type
            FROM company_documents cd
            JOIN companies c ON cd.company_uuid = c.uuid
            WHERE cd.uuid = ? LIMIT 1
        ");
        $stmt->bind_param('s', $documentUuid);
        $stmt->execute();
        $meta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $typeLabel = $docTypeLabel[$meta['doc_type'] ?? ''] ?? 'Document';
        $tabTitle  = $typeLabel . ' — ' . ($meta['company_name'] ?? 'Company');
    } else {
        $stmt = $conn->prepare("
            SELECT sr.req_type, sp.first_name, sp.last_name
            FROM student_requirements sr
            JOIN student_profiles sp ON sr.student_uuid = sp.uuid
            WHERE sr.uuid = ? LIMIT 1
        ");
        $stmt->bind_param('s', $documentUuid);
        $stmt->execute();
        $meta = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $reqLabels = [
            'medical_certificate' => 'Medical Certificate',
            'parental_consent'    => 'Parental Consent Form',
            'insurance'           => 'Student Insurance',
            'nbi_clearance'       => 'NBI Clearance',
            'resume'              => 'Resume / CV',
            'guardian_form'       => 'Guardian Information Form',
        ];
        $reqLabel = $reqLabels[$meta['req_type'] ?? ''] ?? 'Document';
        $tabTitle = $reqLabel . ' — ' . ($meta['first_name'] ?? '') . ' ' . ($meta['last_name'] ?? '');
    }

    servePdfWithTitle($absolutePath, $tabTitle, $fileName);
}

if (ob_get_level()) {
    ob_end_clean();
}

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

readfile($realPath);
exit;