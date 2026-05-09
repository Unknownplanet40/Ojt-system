<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../config/db.php";
require_once "../../functions/coordinator_functions.php";
require_once "../../helpers/helpers.php";

if (!isset($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
    response(['status' => 'error', 'message' => "Unauthorized access."]);
}

$coordinatorUuid = $_SESSION['user_uuid'];

try {
    $data = getCoordinatorDashboardStats($conn, $coordinatorUuid);
    
    if (isset($data['error'])) {
        response(['status' => 'error', 'message' => $data['error']]);
    }

    response([
        'status' => 'success',
        'message' => "Dashboard data fetched.",
        'data' => $data
    ]);
} catch (Exception $e) {
    response(['status' => 'error', 'message' => "Error fetching dashboard data: " . $e->getMessage()]);
}
