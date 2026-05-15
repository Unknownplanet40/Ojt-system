<?php

require_once 'ServerConfig.php';

// Prevent direct access to this file
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    // Only allow AJAX requests
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . '/../database/dbconfig.php';

function response($data)
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !==  'POST') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.',
        'long_message' => 'Only POST requests are allowed for this endpoint.'
    ]);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled.',
        'long_message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ]);
}

$action = isset($_POST['action']) ? $_POST['action'] : null;

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
} catch (Exception $e) {
    response([
        'status' => 'critical',
        'message' => 'Database connection failed',
        'long_message' => $e->getMessage()
    ]);
}

require_once 'helpers.php';

switch ($action) {
    case 'fetch_academic_info':
        $programs = getActivePrograms($conn);
        response([
            'status' => 'success',
            'message' => 'Academic information fetched successfully.',
            'data' => [
                'programs' => $programs
            ]
        ]);
        
        break;
    default:
        response([
            'status' => 'info',
            'message' => 'No valid action specified.',
            'long_message' => 'Please provide a valid action parameter in the request.'
        ]);
}

function getActivePrograms($conn): array
{
    $result = $conn->query("
        SELECT uuid, code, name, required_hours
        FROM programs
        WHERE is_active = 1
        ORDER BY code ASC
    ");

    $programs = [];
    while ($row = $result->fetch_assoc()) {
        $programs[] = [
            'uuid'           => $row['uuid'],
            'code'           => $row['code'],
            'name'           => $row['name'],
            'required_hours' => (int) $row['required_hours'],
            'label'          => $row['code'] . ' — ' . $row['name'],
        ];
    }

    return $programs;
}
