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

$CurrentPage = "Journal";
$greeting = "Good day";
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
    <script type="module" src="../../../Assets/Script/DashboardScripts/SupervisorDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/SupervisorScripts/JournalScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>">
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

    <!-- View Journal Modal -->
    <div class="modal fade" id="viewJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Journal Details</h5>
                        <p class="text-muted small mb-0"><span id="viewStudentName" class="fw-bold text-body"></span> • <span id="viewJournalWeekRange"></span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="viewJournalStatusBadge" class="mb-3"></div>

                    <div class="mb-3">
                        <h6 class="fw-semibold text-muted small text-uppercase">Accomplishments & Tasks</h6>
                        <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body" id="viewAccomplishments" style="white-space: pre-wrap;"></div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-4">
                            <h6 class="fw-semibold text-muted small text-uppercase">Skills Learned</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body h-100" id="viewSkillsLearned" style="white-space: pre-wrap;"></div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-semibold text-muted small text-uppercase">Issues & Challenges</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body h-100" id="viewChallenges" style="white-space: pre-wrap;"></div>
                        </div>
                        <div class="col-md-4">
                            <h6 class="fw-semibold text-muted small text-uppercase">Plans for Next Week</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body h-100" id="viewPlansNextWeek" style="white-space: pre-wrap;"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Supervisor.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">Student Journals</h4>
                        <p class="text-muted mb-0 small">View weekly progress reports from your assigned trainees.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control bg-transparent border-start-0 ps-0 shadow-none" id="journalSearchInput" placeholder="Search by student name or content...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4" id="supervisorJournalList">
                    <!-- Journals will be rendered here -->
                </div>
                
                <div class="p-5 text-center d-none" id="supervisorJournalEmptyState">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary" style="width: 80px; height: 80px;">
                        <i class="bi bi-journal-x fs-1"></i>
                    </div>
                    <h5 class="mb-2 fw-semibold">No Journals Found</h5>
                    <p class="text-muted mb-0">Your assigned students have not submitted any weekly journals yet.</p>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
