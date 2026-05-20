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
require_once dirname(__DIR__, 2) . '/functions/dtr_functions.php';

if (empty($_SESSION['user_uuid']) || !in_array($_SESSION['user_role'] ?? '', ['coordinator', 'supervisor', 'admin'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

$dtrUuid = trim($_POST['dtr_uuid'] ?? '');

if (empty($dtrUuid)) {
    echo json_encode(['status' => 'error', 'message' => 'DTR UUID is required.']);
    exit;
}

try {
    $stmt = $conn->prepare("
        SELECT 
            d.uuid,
            d.clock_in_latitude,
            d.clock_in_longitude,
            d.clock_out_latitude,
            d.clock_out_longitude,
            c.latitude AS company_lat,
            c.longitude AS company_lon,
            c.geofence_radius,
            c.name AS company_name
        FROM dtr_entries d
        LEFT JOIN ojt_applications a ON d.application_uuid = a.uuid
        LEFT JOIN companies c ON a.company_uuid = c.uuid
        WHERE d.uuid = ?
        LIMIT 1
    ");
    $stmt->bind_param('s', $dtrUuid);
    $stmt->execute();
    $entry = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$entry) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'DTR entry not found.']);
        exit;
    }

    if ($entry['company_lat'] === null || $entry['company_lon'] === null) {
        echo json_encode([
            'status' => 'success',
            'data' => [
                'clock_in_distance' => null,
                'clock_out_distance' => null,
                'clock_in_within_bounds' => null,
                'clock_out_within_bounds' => null,
                'allowed_radius' => 100,
                'company_name' => $entry['company_name'] ?? 'Company',
                'message' => 'Company geofence not configured'
            ]
        ]);
        exit;
    }

    $allowedRadius = (int)($entry['geofence_radius'] ?? 100);
    $clockInDistance = null;
    $clockOutDistance = null;
    $clockInWithinBounds = null;
    $clockOutWithinBounds = null;

    if ($entry['clock_in_latitude'] !== null && $entry['clock_in_longitude'] !== null) {
        $result = verifyGeofenceServer(
            (float)$entry['clock_in_latitude'],
            (float)$entry['clock_in_longitude'],
            (float)$entry['company_lat'],
            (float)$entry['company_lon'],
            $allowedRadius
        );
        $clockInDistance = (float)$result['distance'];
        $clockInWithinBounds = $result['is_within'];
    }

    if ($entry['clock_out_latitude'] !== null && $entry['clock_out_longitude'] !== null) {
        $result = verifyGeofenceServer(
            (float)$entry['clock_out_latitude'],
            (float)$entry['clock_out_longitude'],
            (float)$entry['company_lat'],
            (float)$entry['company_lon'],
            $allowedRadius
        );
        $clockOutDistance = (float)$result['distance'];
        $clockOutWithinBounds = $result['is_within'];
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'dtr_uuid' => $dtrUuid,
            'company_lat' => (float)$entry['company_lat'],
            'company_lon' => (float)$entry['company_lon'],
            'company_name' => $entry['company_name'] ?? 'Company',
            'allowed_radius' => $allowedRadius,
            'clock_in_latitude' => $entry['clock_in_latitude'] !== null ? (float)$entry['clock_in_latitude'] : null,
            'clock_in_longitude' => $entry['clock_in_longitude'] !== null ? (float)$entry['clock_in_longitude'] : null,
            'clock_in_distance' => $clockInDistance,
            'clock_in_within_bounds' => $clockInWithinBounds,
            'clock_out_latitude' => $entry['clock_out_latitude'] !== null ? (float)$entry['clock_out_latitude'] : null,
            'clock_out_longitude' => $entry['clock_out_longitude'] !== null ? (float)$entry['clock_out_longitude'] : null,
            'clock_out_distance' => $clockOutDistance,
            'clock_out_within_bounds' => $clockOutWithinBounds
        ]
    ]);

} catch (Exception $e) {
    error_log("DTR Distance Calculation Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'An error occurred while calculating distances.']);
}
