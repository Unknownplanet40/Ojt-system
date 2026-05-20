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
require_once __DIR__ . '/../../../functions/settings_functions.php';

$CurrentPage = "Evaluation";
$maintenanceStatus = isFeatureMaintenanceActive($conn, 'evaluation');
$disableEvaluation = $maintenanceStatus['active'];
$evaluationDisableReason = $maintenanceStatus['reason'];
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
        .star-rating:hover i {
            color: #dee2e6 !important;
        }
        .star-rating i:hover,
        .star-rating i:hover ~ i {
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
            <?php include '../../Components/Header_Supervisor.php'; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <?php if ($disableEvaluation): ?>
                    <div class="alert alert-warning border-0 rounded-4 shadow-sm p-4 mb-4 d-flex align-items-center bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                        <div class="rounded-circle bg-warning bg-opacity-25 d-flex align-items-center justify-content-center text-warning me-3" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-exclamation-triangle-fill fs-4"></i>
                        </div>
                        <div>
                            <h5 class="alert-heading fw-bold mb-1">Evaluations Locked</h5>
                            <p class="mb-0 text-body-secondary small"><?= htmlspecialchars($evaluationDisableReason) ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($maintenanceStatus['upcoming'] ?? false): ?>
                    <div class="alert alert-info border-0 rounded-4 shadow-sm p-4 mb-4 d-flex align-items-center bg-blur-5 bg-semi-transparent ojt-maintenance-upcoming-banner" 
                         style="--blur-lvl: <?= $opacitylvl ?>;"
                         data-start="<?= htmlspecialchars($maintenanceStatus['start']) ?>"
                         data-end="<?= htmlspecialchars($maintenanceStatus['end']) ?>">
                        <div class="rounded-circle bg-info bg-opacity-25 d-flex align-items-center justify-content-center text-info me-3" style="width: 50px; height: 50px; min-width: 50px;">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="alert-heading fw-bold mb-1 text-info">Upcoming Scheduled Maintenance</h5>
                            <p class="mb-0 text-body-secondary small">
                                The Student Evaluation system will undergo maintenance starting from <strong><?= date('F j, Y, g:i A', strtotime($maintenanceStatus['start'])) ?></strong> until <strong><?= date('g:i A', strtotime($maintenanceStatus['end'])) ?></strong>.
                            </p>
                        </div>
                        <div class="ms-auto text-end countdown-wrapper ps-3">
                            <div class="small text-muted mb-1 text-uppercase fw-bold">Starts In</div>
                            <div class="fw-bold fs-5 text-info countdown-timer">--:--:--</div>
                        </div>
                    </div>
                <?php endif; ?>
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
                            <div class="star-rating d-flex flex-row-reverse justify-content-end gap-2" data-input="<?= $key ?>">
                                <i class="bi bi-star-fill" data-val="5"></i>
                                <i class="bi bi-star-fill" data-val="4"></i>
                                <i class="bi bi-star-fill" data-val="3"></i>
                                <i class="bi bi-star-fill" data-val="2"></i>
                                <i class="bi bi-star-fill" data-val="1"></i>
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
        const disableEvaluation = <?= $disableEvaluation ? 'true' : 'false' ?>;
        const evaluationDisableReason = <?= json_encode($evaluationDisableReason) ?>;

        document.addEventListener("DOMContentLoaded", function() {
            const banners = document.querySelectorAll(".ojt-maintenance-upcoming-banner");
            banners.forEach(banner => {
                const startStr = banner.dataset.start;
                if (!startStr) return;
                
                const formattedStart = startStr.replace(" ", "T");
                const startTime = new Date(formattedStart).getTime();
                const timerEl = banner.querySelector(".countdown-timer");
                
                function updateTimer() {
                    const now = new Date().getTime();
                    const distance = startTime - now;
                    
                    if (distance <= 0) {
                        window.location.reload();
                        return;
                    }
                    
                    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                    
                    let displayStr = "";
                    if (days > 0) {
                        displayStr += days + "d ";
                    }
                    displayStr += String(hours).padStart(2, '0') + "h " + 
                                  String(minutes).padStart(2, '0') + "m " + 
                                  String(seconds).padStart(2, '0') + "s";
                                  
                    timerEl.textContent = displayStr;
                }
                
                updateTimer();
                setInterval(updateTimer, 1000);
            });
        });
    </script>
</body>
</html>
