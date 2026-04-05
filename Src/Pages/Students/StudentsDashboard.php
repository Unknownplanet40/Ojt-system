<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Dashboard";

$greeting = "";
$currentHour = date("H");
if ($currentHour >= 5 && $currentHour < 12) {
    $greeting = "Good morning";
} elseif ($currentHour >= 12 && $currentHour < 17) {
    $greeting = "Good afternoon";
} elseif ($currentHour >= 17 && $currentHour < 21) {
    $greeting = "Good evening";
} else {
    $greeting = "Good night";
}

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/DashboardScripts/StudentDashboard.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-role="<?= $_SESSION['user']['role'] ?>"
    data-uuid="<?= $_SESSION['user']['uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>
    <div class="w-100 min-vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75"
        id="pageLoader">
        <div class="d-flex flex-column align-items-center">
            <span class="loader"></span>
        </div>
    </div>
    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Dashboard <i class="bi bi-arrow-clockwise ms-2" id="dashboardRefreshBtn" style="cursor: pointer;"></i></h4>
                        <p class="blockquote-footer pt-2 fs-6">
                            <?= $greeting ?>, <strong
                                id="welcomeUserName"></strong>! Here's what's happening this semester.
                        </p>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h5 class="card-title">My Students</h5>
                                <p class="card-text display-6 fw-bold mb-0" id="TotalUsersCounts">0</p>
                                <p class="card-text" id="TotalUsersStatus">Loading...</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h5 class="card-title">Active OJT</h5>
                                <p class="card-text display-6 fw-bold mb-0" id="activeOjtCounts">0</p>
                                <p class="card-text" id="activeOjtStatus">Loading...</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h5 class="card-title">Pending Approvals</h5>
                                <p class="card-text display-6 fw-bold mb-0" id="pendingApprovalsCounts">0</p>
                                <p class="card-text text-danger" id="pendingApprovalsStatus">Loading...</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h5 class="card-title">Avg hours rendered</h5>
                                <p class="card-text display-6 fw-bold mb-0" id="avgHoursRendered">0</p>
                                <p class="card-text" id="avgHoursRenderedStatus">Loading...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>