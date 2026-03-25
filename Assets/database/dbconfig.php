<?php
if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    $base = dirname($_SERVER['SCRIPT_NAME'], 3);
    header("Location: $base/Src/Pages/ErrorPage.php?error=403");
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "ojt_system";