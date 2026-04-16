<?php

require_once './db.php';

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

function isModRewriteEnabled()
{
    if (function_exists('apache_get_modules')) {
        $modules = apache_get_modules();
        return in_array('mod_rewrite', $modules) || in_array('rewrite_module', $modules);
    }
    return function_exists('apache_getenv') || isset($_SERVER['REDIRECT_STATUS']);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

function response(array $data, int $statusCode = 200): void
{
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Content-Security-Policy: default-src \'none\'; frame-ancestors \'none\'; base-uri \'none\'; form-action \'none\';');

        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }

        header('X-Powered-By:');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Cross-Origin-Resource-Policy: same-origin');
    }

    try {
        echo json_encode(
            $data,
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );
    } catch (JsonException $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }

        echo '{"status":"error","message":"Failed to encode response as JSON."}';
    }

    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    response([
        'status' => 'info',
        'message' => 'Request method is not allowed.'
    ], 405);
}

if (!isModRewriteEnabled()) {
    response([
        'status' => 'critical',
        'message' => 'mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.'
    ], 500);
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
        $conn = new mysqli($host, $username, $password);
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
    ], 500);
