<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Evaluations";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/DashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/EvaluationScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>
<body class="login-page bg-body-tertiary" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php include '../../Components/Header_Coordinator.php'; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                    <h1 class="h3 fw-bold text-body mb-0">Evaluations Overview</h1>
                </div>

                <!-- Module Guide -->
                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-start mb-4 rounded-4 p-4 shadow-sm">
                    <i class="bi bi-info-circle-fill fs-3 me-3 mt-1"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-2">Evaluations Monitoring</h5>
                        <p class="mb-2">This dashboard provides a consolidated view of all performance evaluations across your batch. As a coordinator, you use this data to compute final grades.</p>
                        <ul class="mb-0 ps-3 small">
                            <li class="mb-1"><strong>Supervisor Evaluations:</strong> Supervisors evaluate students at the Midterm (50% hours) and Final (100% hours) milestones.</li>
                            <li><strong>Student Self-Evaluations:</strong> Students submit their self-reflection and company recommendation once their Final Evaluation is complete.</li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4 mb-4" id="evaluationsContainer">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Details Modal -->
    <div class="modal fade" id="evalDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent shadow-lg border-0" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="detailTitle">Evaluation Details</h5>
                        <p class="mb-0 text-muted small" id="detailStudentName">—</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" id="detailContent">
                    <!-- Populated via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- JS Scripts -->
    <script>
        const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";
    </script>
</body>
</html>
