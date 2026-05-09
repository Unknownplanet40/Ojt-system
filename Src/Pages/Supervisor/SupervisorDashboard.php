<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "SupervisorDashboard";

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
    <script type="module" src="../../../Assets/Script/dashboardScripts/SupervisorDashboardScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page" data-role="<?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '' ?>" data-uuid="<?= isset($_SESSION['user_uuid']) ? $_SESSION['user_uuid'] : '' ?>" data-only="<?= $CurrentPage ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="w-100 min-vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75" id="pageLoader">
        <div class="d-flex flex-column align-items-center">
            <span class="loader"></span>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Supervisor.php" ?>
            
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack mb-4">
                    <div>
                        <h4 class="mb-1" id="dashboardTitle">Dashboard <i class="bi bi-arrow-clockwise ms-2" id="dashboardRefreshBtn" style="cursor: pointer;"></i></h4>
                        <p class="blockquote-footer pt-2 fs-6">
                            <?= $greeting ?>, <strong id="welcomeUserName"></strong>! Here's an overview of your supervised students.
                        </p>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-4 g-4 mb-4">
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Total Students</h6>
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                                        <i class="bi bi-people fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="totalStudentsCount">0</h3>
                                <p class="card-text small text-muted mb-0" id="totalStudentsStatus">Assigned to your company</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Active OJT</h6>
                                    <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success">
                                        <i class="bi bi-person-check fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="activeOjtCount">0</h3>
                                <p class="card-text small text-muted mb-0" id="activeOjtStatus">Currently rendering hours</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Pending DTR</h6>
                                    <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning">
                                        <i class="bi bi-clock-history fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="pendingDtrCount">0</h3>
                                <p class="card-text small text-muted mb-0 text-warning" id="pendingDtrStatus">Needs your approval</p>
                            </div>
                        </div>
                    </div>
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Pending Evals</h6>
                                    <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info">
                                        <i class="bi bi-file-earmark-text fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="pendingEvalsCount">0</h3>
                                <p class="card-text small text-muted mb-0" id="pendingEvalsStatus">Midterm/Final evaluations</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="hstack mb-4">
                                    <h5 class="card-title mb-0">Active Supervised Students</h5>
                                    <a href="./DTR.php" class="ms-auto text-decoration-none small fw-bold text-success">View all</a>
                                </div>
                                <div class="vstack gap-3" id="recentStudentsList">
                                    <div class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                                        <span class="text-muted">Loading students...</span>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-4">Quick Links</h5>
                                <div class="vstack gap-3">
                                    <a href="./DTR.php" class="d-flex align-items-center gap-3 p-3 rounded-4 border bg-body-tertiary text-decoration-none hover-shadow-sm transition-all">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                                            <i class="bi bi-clock-fill fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body">Manage DTR</div>
                                            <small class="text-muted">Approve or reject student hours</small>
                                        </div>
                                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                                    </a>
                                    <a href="./Evaluation.php" class="d-flex align-items-center gap-3 p-3 rounded-4 border bg-body-tertiary text-decoration-none hover-shadow-sm transition-all">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success">
                                            <i class="bi bi-star-fill fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body">Evaluations</div>
                                            <small class="text-muted">Conduct student performance reviews</small>
                                        </div>
                                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                                    </a>
                                    <a href="./Journal.php" class="d-flex align-items-center gap-3 p-3 rounded-4 border bg-body-tertiary text-decoration-none hover-shadow-sm transition-all">
                                        <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info">
                                            <i class="bi bi-journal-text fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body">Weekly Journals</div>
                                            <small class="text-muted">Read student reflections</small>
                                        </div>
                                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                                    </a>
                                    <a href="./Supervisor_Profile.php?action=edit" class="d-flex align-items-center gap-3 p-3 rounded-4 border bg-body-tertiary text-decoration-none hover-shadow-sm transition-all">
                                        <div class="bg-secondary bg-opacity-10 p-2 rounded-3 text-secondary">
                                            <i class="bi bi-person-fill-gear fs-5"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold text-body">My Profile</div>
                                            <small class="text-muted">Manage your account settings</small>
                                        </div>
                                        <i class="bi bi-chevron-right ms-auto text-muted"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>