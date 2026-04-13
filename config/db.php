<?php

if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 2);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

$host     = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'ojt_system';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    http_response_code(500);
    exit('Database connection failed: ' . $conn->connect_error);
}

$conn->set_charset('utf8mb4');
