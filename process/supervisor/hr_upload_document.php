<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

require_once "../../config/db.php";
require_once "../../helpers/helpers.php";

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'supervisor') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$userUuid = $_SESSION['user_uuid'];

$stmt = $conn->prepare("SELECT company_uuid, is_hr_admin FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
$stmt->bind_param("s", $userUuid);
$stmt->execute();
$profRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$profRow || (int)$profRow['is_hr_admin'] !== 1) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Forbidden: Not an HR Admin']);
    exit;
}

$companyUuid = $profRow['company_uuid'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$docType = htmlspecialchars(strip_tags(trim($_POST['doc_type'] ?? '')));
$fileName = htmlspecialchars(strip_tags(trim($_POST['file_name'] ?? '')));
$validUntil = htmlspecialchars(strip_tags(trim($_POST['valid_until'] ?? '')));

if (empty($docType) || empty($fileName)) {
    echo json_encode(['status' => 'error', 'message' => 'Document type and file name are required.']);
    exit;
}

if (!isset($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'Please select a valid file to upload.']);
    exit;
}

$file = $_FILES['document_file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];

if (!in_array($ext, $allowedExts)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only PDF and images are allowed.']);
    exit;
}

if ($file['size'] > 10 * 1024 * 1024) {
    echo json_encode(['status' => 'error', 'message' => 'File size exceeds the 10MB limit.']);
    exit;
}

$uploadDir = "../../uploads/documents/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$docUuid = generateUUID();
$newFileName = $docType . '_' . $docUuid . '.' . $ext;
$destPath = $uploadDir . $newFileName;
$dbFilePath = "uploads/documents/" . $newFileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to save the uploaded file.']);
    exit;
}

try {
    $conn->begin_transaction();

    $stmt = $conn->prepare("
        INSERT INTO company_documents (uuid, company_uuid, doc_type, file_name, file_path, valid_until, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $validUntilVal = !empty($validUntil) ? $validUntil : null;
    $stmt->bind_param("ssssss", $docUuid, $companyUuid, $docType, $fileName, $dbFilePath, $validUntilVal);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'document_uploaded',
        description: "Uploaded a new {$docType} document: {$fileName}",
        module: 'companies',
        actorUuid: $userUuid,
        targetUuid: $companyUuid
    );

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Document uploaded successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    if (file_exists($destPath)) {
        unlink($destPath);
    }
    error_log("HR Document Upload Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while saving to the database.']);
}
