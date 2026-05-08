<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
$CurrentPage = "Grading";
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js?v=<?= time() ?>"></script>
    <script type="module" src="../../../Assets/Script/GradingScripts/GradingModule.js?v=<?= time() ?>"></script>
    <title><?= $ShortTitle ?> - Grading</title>
</head>
<body class="login-page" data-role="admin" data-page-type="admin" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Grades Overview</h4>
                        <p class="text-muted mb-0">View finalized grades across the active batch.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-secondary rounded-pill px-3" id="gradeRefreshBtn"><i class="bi bi-arrow-clockwise me-2"></i>Refresh</button>
                        <input type="search" class="form-control rounded-pill" id="gradeSearchInput" placeholder="Search student or grade" style="min-width: 240px;">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-xl-4">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Finalized Grades</div>
                                <div class="display-6 fw-bold text-success mb-0" id="gradesCountLabel">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-4">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Current Scope</div>
                                <div class="display-6 fw-bold mb-0">Active Batch</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">What you can do</div>
                                <p class="mb-0 text-muted small">Admins view finalized grades only. Coordinators compute and finalize them from the coordinator workbench.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Finalized grades</h5>
                                <p class="text-muted mb-0 small">Read-only list of locked grades for the active batch.</p>
                            </div>
                        </div>
                        <div id="finalizedGradesList"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="gradeDetailsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Grade details</h5>
                        <p class="mb-0 text-muted small"><span id="gradeDetailsStudentName">—</span> • <span id="gradeDetailsStudentNumber">—</span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-4" id="gradeDetailsStatus"></div>
                    <div id="gradeDetailsContent"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
