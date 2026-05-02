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
require_once __DIR__ . '/../../../functions/evaluation_functions.php';

$CurrentPage = "Evaluations";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/DashboardScripts/StudentDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/StudentsScripts/EvaluationScript.js"></script>
    <title><?= $ShortTitle ?></title>
    
    <style>
        .star-rating i {
            font-size: 1.5rem;
            color: #dee2e6;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star-rating.interactive i.active,
        .star-rating.interactive i:hover,
        .star-rating.interactive i:hover ~ i {
            color: #ffc107;
        }
        .star-rating.readonly i.text-warning {
            color: #ffc107 !important;
        }
    </style>
</head>
<body class="login-page bg-body-tertiary" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php include '../../Components/Header_Students.php'; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                    <h1 class="h3 fw-bold text-body mb-0">My Evaluations</h1>
                </div>

                <!-- Module Guide -->
                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-start mb-4 rounded-4 p-4 shadow-sm">
                    <i class="bi bi-info-circle-fill fs-3 me-3 mt-1"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-2">Evaluation Process Guide</h5>
                        <p class="mb-2">This page displays the performance evaluations submitted by your HTE Supervisor. Here is how the process works:</p>
                        <ul class="mb-0 ps-3 small">
                            <li class="mb-1"><strong>Midterm & Final Evaluations:</strong> Your supervisor will be able to evaluate you once you reach <strong>50%</strong> and <strong>100%</strong> of your required hours, respectively.</li>
                            <li><strong>Self-Evaluation:</strong> You are required to submit a self-evaluation to reflect on your OJT experience. This section will automatically <strong>unlock</strong> after your supervisor submits your Final Evaluation.</li>
                        </ul>
                    </div>
                </div>

                <div class="row g-4" id="evaluationsContainer">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>

                <!-- Self Evaluation Section -->
                <div class="mt-5" id="selfEvalSection" style="display: none;">
                    <h4 class="fw-bold mb-3">Self Evaluation</h4>
                    <div class="card bg-blur-5 bg-semi-transparent shadow-sm border-0" style="--blur-lvl: <?= $opacitylvl ?>;">
                        <div class="card-body p-4" id="selfEvalContent">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- JS Scripts -->
    <script>
        const csrfToken = "<?= $_SESSION['csrf_token'] ?? '' ?>";
        const selfCriteria = <?= json_encode(SELF_EVAL_CRITERIA) ?>;
    </script>
</body>
</html>
