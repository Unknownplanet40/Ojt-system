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

$companyUuid = trim($_POST['company_uuid'] ?? '');
$totalSlots = (int)($_POST['total_slots'] ?? 0);

if (empty($companyUuid)) {
    echo json_encode(['status' => 'error', 'message' => 'Company UUID is required.']);
    exit;
}

if ($totalSlots < 0) {
    echo json_encode(['status' => 'error', 'message' => 'Slots must be a positive number.']);
    exit;
}

// Check active batch
$result = $conn->query("SELECT uuid FROM batches WHERE status = 'active' LIMIT 1");
$activeBatch = $result->fetch_assoc();
$batchUuid = $activeBatch['uuid'] ?? null;

if (!$batchUuid) {
    echo json_encode(['status' => 'error', 'message' => 'No active internship batch found.']);
    exit;
}

try {
    $conn->begin_transaction();

    // Update the company slots capacity using setCompanySlots (which also auto-dismisses alerts)
    setCompanySlots($conn, $companyUuid, $batchUuid, $totalSlots);

    // Fetch company name for logging
    $stmt = $conn->prepare("SELECT name FROM companies WHERE uuid = ? LIMIT 1");
    $stmt->bind_param("s", $companyUuid);
    $stmt->execute();
    $cRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $companyName = $cRow['name'] ?? 'Unknown Company';

    logActivity(
        conn: $conn,
        eventType: 'slots_updated',
        description: "Updated capacity for {$companyName} to {$totalSlots} slots.",
        module: 'companies',
        actorUuid: $_SESSION['user_uuid'],
        targetUuid: $companyUuid
    );

    $conn->commit();
    echo json_encode(['status' => 'success', 'message' => 'Company slots updated successfully.']);

} catch (Exception $e) {
    $conn->rollback();
    error_log("Coordinator Slot Update Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while updating slots.']);
}
