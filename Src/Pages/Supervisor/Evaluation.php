<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'supervisor') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
require_once __DIR__ . '/../../../functions/evaluation_functions.php';

$CurrentPage = "Evaluation";
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/DashboardScripts/SupervisorDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/SupervisorScripts/EvaluationScript.js"></script>
    <title><?= $ShortTitle ?></title>
    
    <style>
        .star-rating i {
            font-size: 1.5rem;
            color: #dee2e6;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star-rating i.active,
        .star-rating i:hover,
        .star-rating i:hover ~ i {
            color: #ffc107;
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
            <?php include '../../Components/Header_Supervisor.php'; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-4">
                    <h1 class="h3 fw-bold text-body mb-0">Student Evaluations</h1>
                </div>

                <!-- Module Guide -->
                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-start mb-4 rounded-4 p-4 shadow-sm">
                    <i class="bi bi-info-circle-fill fs-3 me-3 mt-1"></i>
                    <div>
                        <h5 class="alert-heading fw-bold mb-2">How Evaluations Work</h5>
                        <p class="mb-2">This module allows you to evaluate your assigned students' performance. Evaluations unlock automatically based on the student's approved DTR hours:</p>
                        <ul class="mb-0 ps-3 small">
                            <li class="mb-1"><strong>Midterm Evaluation:</strong> Unlocks when a student reaches <strong>50%</strong> of their required hours.</li>
                            <li><strong>Final Evaluation:</strong> Unlocks when a student completes <strong>100%</strong> of their required hours.</li>
                        </ul>
                    </div>
                </div>

                <!-- Students List Container -->
                <div class="row g-4" id="studentsContainer">
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Evaluation Modal -->
    <div class="modal fade" id="evaluationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent shadow-lg border-0" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="evalModalTitle">Midterm Evaluation</h5>
                        <p class="mb-0 text-muted small" id="evalStudentName"></p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="evaluationForm">
                        <input type="hidden" id="evalStudentUuid" name="student_uuid">
                        <input type="hidden" id="evalType" name="eval_type">

                        <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-center mb-4">
                            <i class="bi bi-info-circle me-3 fs-4"></i>
                            <div>
                                <h6 class="alert-heading mb-1 fw-bold">Evaluation Rubric</h6>
                                <p class="mb-0 small">Please rate the student from 1 (Poor) to 5 (Excellent) based on their performance during this period.</p>
                            </div>
                        </div>

                        <?php foreach (SUPERVISOR_CRITERIA as $key => $label): ?>
                        <div class="mb-4">
                            <label class="form-label fw-medium d-flex justify-content-between">
                                <span><?= htmlspecialchars($label) ?></span>
                                <span class="badge bg-secondary rounded-pill" id="badge-<?= $key ?>">0 / 5</span>
                            </label>
                            <div class="star-rating d-flex gap-2" data-input="<?= $key ?>">
                                <i class="bi bi-star-fill" data-val="1"></i>
                                <i class="bi bi-star-fill" data-val="2"></i>
                                <i class="bi bi-star-fill" data-val="3"></i>
                                <i class="bi bi-star-fill" data-val="4"></i>
                                <i class="bi bi-star-fill" data-val="5"></i>
                            </div>
                            <input type="hidden" name="<?= $key ?>" id="<?= $key ?>" required>
                        </div>
                        <?php endforeach; ?>

                        <div class="mb-4">
                            <label class="form-label fw-medium">General Comments & Feedback</label>
                            <textarea class="form-control bg-body-tertiary" name="comments" rows="3" placeholder="Provide constructive feedback..."></textarea>
                        </div>

                    </form>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary px-4" id="submitEvalBtn">Submit Evaluation</button>
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
