<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=utf-8');

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Forbidden']);
        exit;
    }
}

require_once dirname(__DIR__, 2) . '/config/db.php';
require_once dirname(__DIR__, 2) . '/functions/company_functions.php';
require_once dirname(__DIR__, 2) . '/helpers/helpers.php';

// Strict Authorization: Must be coordinator
if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$companyUuid    = trim($_POST['company_uuid'] ?? '');
$latitude       = $_POST['latitude'] !== '' && $_POST['latitude'] !== null ? (float)$_POST['latitude'] : null;
$longitude      = $_POST['longitude'] !== '' && $_POST['longitude'] !== null ? (float)$_POST['longitude'] : null;
$geofenceRadius = $_POST['geofence_radius'] !== '' && $_POST['geofence_radius'] !== null ? (int)$_POST['geofence_radius'] : 100;
$csrfToken      = trim($_POST['csrf_token'] ?? '');

if (empty($csrfToken) || $csrfToken !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token.']);
    exit;
}

if (empty($companyUuid)) {
    echo json_encode(['status' => 'error', 'message' => 'Company UUID is required.']);
    exit;
}

if ($geofenceRadius < 10) {
    echo json_encode(['status' => 'error', 'message' => 'Radius must be at least 10 meters.']);
    exit;
}

try {
    $conn->begin_transaction();

    // Fetch company name for logging & existence check
    $stmt = $conn->prepare("SELECT name FROM companies WHERE uuid = ? LIMIT 1");
    $stmt->bind_param("s", $companyUuid);
    $stmt->execute();
    $cRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$cRow) {
        echo json_encode(['status' => 'error', 'message' => 'Company not found.']);
        exit;
    }

    $companyName = $cRow['name'];

    // Update coordinates and geofence radius
    $stmt = $conn->prepare("
        UPDATE companies
        SET latitude = ?,
            longitude = ?,
            geofence_radius = ?
        WHERE uuid = ?
    ");
    $stmt->bind_param("ddis", $latitude, $longitude, $geofenceRadius, $companyUuid);
    $stmt->execute();
    $stmt->close();

    logActivity(
        conn: $conn,
        eventType: 'geofence_updated',
        description: "Updated geofence limits for {$companyName} (Lat: " . ($latitude ?? 'N/A') . ", Lng: " . ($longitude ?? 'N/A') . ", Radius: {$geofenceRadius}m).",
        module: 'companies',
        actorUuid: $_SESSION['user_uuid'],
        targetUuid: $companyUuid
    );

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Company geofence limits updated successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Coordinator Geofence Update Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while updating geofence limits.']);
}
