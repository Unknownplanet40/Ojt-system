<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "AdminDashboard";

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../../libs/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../../../libs/aos/css/aos.css" />
    <link rel="stylesheet" href="../../../libs/driverjs/css/driver.css" />
    <link rel="stylesheet" href="../../../Assets/style/AniBG.css" />
    <link rel="stylesheet" href="../../../Assets/style/MainStyle.css" />
    <link rel="manifest" href="../../../Assets/manifest.json" />

    <script defer src="../../../libs/bootstrap/js/bootstrap.bundle.js"></script>
    <script defer src="../../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script defer src="../../../libs/aos/js/aos.js"></script>
    <script src="../../../libs/driverjs/js/driver.js.iife.js"></script>
    <script src="../../../libs/jquery/js/jquery-3.7.1.min.js"></script>
    <script type="module" src="../../../Assets/Script/DashboardScripts/AdminDashboardScript.js"></script>
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
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Dashboard</h4>
                        <p class="blockquote-footer pt-2 fs-6">Welcome back, <strong id="welcomeUserName"></strong>! Here's what's happening this semester.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap"
                        id="dashboardnewCoordinatorBtn">
                        <i class="bi bi-person-plus me-1"></i>
                        Add Coordinator
                    </button>
                </div>
                <?php require_once "../../Components/lvl1cards.php" ?>
                <?php require_once "../../Components/lvl2cards.php" ?>
                <?php require_once "../../Components/lvl3cards.php" ?>
            </div>
        </main>
    </div>
</body>

</html>