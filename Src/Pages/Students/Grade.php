<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
$CurrentPage = "Grade";
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/StudentDashboard.js?v=<?= time() ?>"></script>
    <script type="module" src="../../../Assets/Script/GradingScripts/GradingModule.js?v=<?= time() ?>"></script>
    <title><?= $ShortTitle ?> - My Grade</title>
</head>
<body class="login-page" data-role="student" data-page-type="student" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">My Grade</h4>
                        <p class="text-muted mb-0">Your finalized grade will appear here once the coordinator locks it.</p>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5" id="studentGradeContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
                            <p class="text-muted mb-0">Loading your grade...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
