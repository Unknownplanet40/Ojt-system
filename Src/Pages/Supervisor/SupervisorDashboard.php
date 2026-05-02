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
        <!-- list of ready to access pages -->
        <div class="mt-4">
            <p>
                <i class="bi bi-info-circle-fill"></i>
                This dashboard will provide quick access to important sections and tools for supervisors to manage their supervised students
            </p>
            <div class="list-group">
                <a href="./Supervisor_Profile?action=edit" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-person-circle fs-4"></i>
                    <span>Profile</span>
                    <small class="text-muted ms-auto">Edit your profile information</small>
                </a>
                <a href="./DTR.php" class="list-group-item list-group-item-action d-flex align-items-center gap-2">
                    <i class="bi bi-clock fs-4"></i>
                    <span>DTR</span>
                    <small class="text-muted ms-auto">View and manage supervised students' Daily Time Records</small>
                </a>
            </div>
            <small>This is just a placeholder dashboard. More features and sections will be added in the future to enhance the supervisor's experience and provide better tools for managing their supervised students.</small>
        </div>
    </div>
</body>

</html>