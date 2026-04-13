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

function response($data) {
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.'
    ]);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ]);
}

$apacheRunning = false;
$mysqlRunning = false;
$dbConnectionSuccessful = false;
$errorStatus = 'warning';
$isDatabaseExistError = false;

exec('tasklist /FI "IMAGENAME eq httpd.exe"', $outputApache);
exec('tasklist /FI "IMAGENAME eq mysqld.exe"', $outputMySQL);

if (count($outputApache) > 1) {
    $apacheRunning = true;
}

if (count($outputMySQL) > 1) {
    $mysqlRunning = true;
}

if ($mysqlRunning) {
    try {
        $conn = new mysqli($servername, $username, $password);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }

        $dbExistsQuery = "SHOW DATABASES LIKE '$dbname'";
        $result = $conn->query($dbExistsQuery);
        if ($result->num_rows === 0) {
            $isDatabaseExistError = true;
            throw new Exception('Database "' . $dbname . '" does not exist. Please create the database and try again.');
        }
        $dbConnectionSuccessful = true;
    } catch (\Throwable $th) {
        $dbConnectionSuccessful = false;
    }

    if ($apacheRunning && $mysqlRunning && $dbConnectionSuccessful) {
        response([
            'status' => 'success',
            'message' => 'Server is running and database connection is successful.'
        ]);
    } else {
        $errorMessage = 'Server status: ';
        $errorMessage .= $apacheRunning ? 'Apache is running. ' : 'Apache is not running. ';
        $errorMessage .= $mysqlRunning ? 'MySQL is running. ' : 'MySQL is not running. ';
        $errorMessage .= $dbConnectionSuccessful ? 'Database connection successful.' : 'Database connection failed.';

        if (!$dbConnectionSuccessful) {
            $errorMessage = 'Database connection failed. Please check your credentials and try again.';
        }
    }
} else {
    $errorMessage = 'MySQL is not running. Please start the MySQL service and try again.';
}

response([
        'code' => http_response_code(500),
        'status' => $errorStatus,
        'message' => $errorMessage,
        'isDatabaseExistError' => $isDatabaseExistError
    ]);
