<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');
require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "SupervisorDashboard";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
    <div class="container py-5">
        <h4>Supervisor Dashboard</h4>
        <p class="text-muted mb-0">Dashboard is being prepared.</p>
    </div>
</body>

</html>