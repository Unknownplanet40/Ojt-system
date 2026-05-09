<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "StudentDashboard";

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

<body class="login-page" >
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
                <div class="row g-3 mb-4 align-items-stretch">
                    <div class="col-12 col-lg-9">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
                                        style="width: 48px; height: 48px;">
                                        <i class="bi bi-person-check fs-5"></i>
                                    </div>

                                    <div class="flex-grow-1 min-w-0 text-center text-sm-start">
                                        <h4 class="mb-1 fw-bold text-break">
                                            <?= $greeting ?>, <span id="welcomeUserName" class="text-primary">Student</span>
                                        </h4>
                                        <p class="mb-0 text-body-secondary small d-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-2">
                                            <span><?= date("l, F j, Y") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><?= date("h:i A") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><span id="currentSemesterLabel"></span> - <span id="currentAcademicYearLabel"></span></span>
                                        </p>
                                    </div>

                                    <div class="ms-sm-auto">
                                        <button type="button"
                                            class="btn btn-outline-primary rounded-pill d-inline-flex align-items-center gap-2 px-3 px-xl-4 py-2 shadow-sm"
                                            id="dashboardRefreshBtn" aria-label="Refresh dashboard">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            <span class="fw-medium">Refresh</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <button
                            class="btn btn-success rounded-4 px-4 py-3 fw-bold shadow-sm h-100 w-100 d-flex align-items-center justify-content-center gap-2"
                            id="LogTimeBtn">
                            <i class="bi bi-clock-fill fs-5"></i>
                            Log Today's Time
                        </button>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <!-- Active OJT Card (Conditionally shown) -->
                    <div class="col-md-12" id="activeOjtSection" style="display: none;">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-success border-opacity-25"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex flex-column flex-md-row align-items-start gap-4">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success flex-shrink-0"
                                        style="width: 64px; height: 64px;">
                                        <i class="bi bi-building fs-3"></i>
                                    </div>

                                    <div class="flex-grow-1 min-w-0 w-100">
                                        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-3 mb-2">
                                            <p class="mb-0 text-uppercase fw-bold text-success small ls-1">Active Internship</p>
                                            <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2" id="ojtStatusBadge">On-going</span>
                                        </div>

                                        <h3 class="fw-bold mb-3" id="ojtCompany">---</h3>

                                        <div class="row g-3 text-body-secondary">
                                            <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center gap-2">
                                                <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                    <i class="bi bi-diagram-3"></i>
                                                </div>
                                                <span class="text-truncate" id="ojtDept">---</span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-2 d-flex align-items-center gap-2">
                                                <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                    <i class="bi bi-laptop"></i>
                                                </div>
                                                <span id="ojtSetup">---</span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center gap-2">
                                                <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                    <i class="bi bi-person-badge"></i>
                                                </div>
                                                <span class="text-truncate">Supervisor: <span id="ojtSupervisor" class="fw-medium text-body">---</span></span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center gap-2">
                                                <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                    <i class="bi bi-calendar-event"></i>
                                                </div>
                                                <span>Started <span id="ojtStart" class="fw-medium text-body">---</span></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="ms-md-auto mt-2 mt-md-0">
                                        <a href="../../Pages/Students/Applications" class="btn btn-primary rounded-pill px-4 shadow-sm text-nowrap">
                                            View Internship Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-body border-opacity-10 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-body-secondary small fw-bold text-uppercase ls-1">Hours Rendered</p>
                                    <span class="badge rounded-pill bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-3 py-2" id="hoursPercentBadge">0%</span>
                                </div>
                                <div>
                                    <h2 class="mb-1 fw-bold" id="renderedHoursCount">0</h2>
                                    <small class="text-body-secondary">of <span id="requiredHoursCount">0</span> total hours</small>
                                </div>
                                <div class="progress mt-auto" style="height: 10px;">
                                    <div class="progress-bar bg-success rounded-pill shadow-sm" role="progressbar"
                                        style="width: 0%;" id="hoursProgressBarSide" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-body border-opacity-10 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-body-secondary small fw-bold text-uppercase ls-1">Days Remaining</p>
                                    <div class="rounded-3 bg-info bg-opacity-10 text-info p-2">
                                        <i class="bi bi-calendar-event fs-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h2 class="mb-1 fw-bold" id="daysRemainingCount">—</h2>
                                    <small class="text-body-secondary" id="estimatedEndDate">Placement pending</small>
                                </div>
                                <small class="text-body-secondary mt-auto">Based on average daily hours</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-body border-opacity-10 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-body-secondary small fw-bold text-uppercase ls-1">Journals</p>
                                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary p-2">
                                        <i class="bi bi-journal-check fs-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h2 class="mb-1 fw-bold" id="journalsCount">0</h2>
                                    <small class="text-body-secondary">approved journals</small>
                                </div>
                                <div class="progress mt-auto" style="height: 10px;">
                                    <div class="progress-bar bg-primary rounded-pill shadow-sm" role="progressbar"
                                        style="width: 0%;" id="journalProgressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border-body border-opacity-10 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-body-secondary small fw-bold text-uppercase ls-1">Requirements</p>
                                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2">
                                        <i class="bi bi-file-earmark-check fs-5"></i>
                                    </div>
                                </div>
                                <div>
                                    <h2 class="mb-1 fw-bold" id="reqApprovedCount">—</h2>
                                    <small class="text-body-secondary" id="reqTotalCount">of 0 approved</small>
                                </div>
                                <div class="progress mt-auto" style="height: 10px;">
                                    <div class="progress-bar bg-warning rounded-pill shadow-sm" role="progressbar"
                                        style="width: 0%;" id="reqProgressBar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress & Quick Actions -->
                    <div class="col-md-6">
                        <div class="card h-100 border-body border-opacity-10 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success"
                                        style="width: 44px; height: 44px;">
                                        <i class="bi bi-graph-up-arrow fs-5"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0 fw-bold">Hours Progress</h5>
                                        <small class="text-body-secondary">Overall internship completion status</small>
                                    </div>
                                    <a href="DTR" class="ms-auto text-decoration-none text-primary small fw-bold">View DTR</a>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="text-center mb-4">
                                    <h1 class="display-5 fw-bold mb-0" id="mainRenderedHours">0</h1>
                                    <p class="text-body-secondary">rendered out of <span id="mainRequiredHours" class="fw-bold text-body">0</span> hours</p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-body-secondary fw-bold text-uppercase ls-1" style="font-size: 0.65rem;">Progress</small>
                                    <small class="fw-bold text-success" id="mainHoursPercent">0%</small>
                                </div>

                                <div class="progress mb-3" style="height: 12px;">
                                    <div class="progress-bar bg-success rounded-pill shadow-sm" role="progressbar"
                                        id="mainHoursProgressBar"
                                        style="width: 0%;"
                                        aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mb-4">
                                    <small class="text-body-secondary"><i class="bi bi-info-circle me-1"></i> <span id="mainRemainingHours">0</span> hours left</small>
                                    <small class="text-body-secondary fw-medium"><span id="mainRenderedTotal">0</span> / <span id="mainRequiredTotal">0</span></small>
                                </div>

                                <div class="row row-cols-3 g-3 text-center">
                                    <div class="col">
                                        <div class="rounded-4 bg-body-secondary bg-opacity-50 p-3 border border-body border-opacity-10">
                                            <h4 class="mb-0 fw-bold" id="daysLoggedCount">0</h4>
                                            <small class="text-body-secondary d-block mt-1">Days Logged</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-4 bg-body-secondary bg-opacity-50 p-3 border border-body border-opacity-10">
                                            <h4 class="mb-0 fw-bold" id="avgHoursCount">0</h4>
                                            <small class="text-body-secondary d-block mt-1">Avg Hrs/Day</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-4 bg-body-secondary bg-opacity-50 p-3 border border-body border-opacity-10">
                                            <h4 class="mb-0 fw-bold text-warning" id="pendingDtrCount">0</h4>
                                            <small class="text-body-secondary d-block mt-1">Pending</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary"
                                        style="width: 44px; height: 44px;">
                                        <i class="bi bi-lightning-charge fs-5"></i>
                                    </span>
                                    <div>
                                        <h5 class="mb-0 fw-bold">Quick Actions</h5>
                                        <small class="text-body-secondary">Access essential tools quickly</small>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body p-4">
                                <div class="d-grid gap-3">
                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-4 border border-body border-opacity-10 bg-body-secondary bg-opacity-25 p-3 d-flex align-items-center gap-3"
                                        id="quickLogTime">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center shadow-sm"
                                            style="width: 48px; height: 48px; min-width: 48px;">
                                            <i class="bi bi-clock-fill fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold">Daily Time Record</span>
                                                <i class="bi bi-chevron-right text-body-secondary small"></i>
                                            </div>
                                            <small class="text-body-secondary">Record your attendance for today</small>
                                        </div>
                                    </button>

                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-4 border border-body border-opacity-10 bg-body-secondary bg-opacity-25 p-3 d-flex align-items-center gap-3"
                                        id="quickSubmitJournal">
                                        <div class="rounded-circle bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center shadow-sm"
                                            style="width: 48px; height: 48px; min-width: 48px;">
                                            <i class="bi bi-journal-text fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold">Weekly Journal</span>
                                                <i class="bi bi-chevron-right text-body-secondary small"></i>
                                            </div>
                                            <small class="text-body-secondary">Update your internship reflections</small>
                                        </div>
                                    </button>

                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-4 border border-body border-opacity-10 bg-body-secondary bg-opacity-25 p-3 d-flex align-items-center gap-3"
                                        id="quickViewEndorsementLetter">
                                        <div class="rounded-circle bg-warning bg-opacity-10 text-warning d-flex align-items-center justify-content-center shadow-sm"
                                            style="width: 48px; height: 48px; min-width: 48px;">
                                            <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span class="fw-bold">Requirements</span>
                                                <i class="bi bi-chevron-right text-body-secondary small"></i>
                                            </div>
                                            <small class="text-body-secondary">Check and upload needed documents</small>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom Cards -->
                    <div class="col-md-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0 fw-bold">Requirements</h5>
                                    <a href="Requirements" class="text-decoration-none text-primary small fw-bold">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <ul class="list-group list-group-flush border-0" id="requirementsList">
                                    <!-- Dynamic Requirements List -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                                <div class="d-flex align-items-center justify-content-between">
                                    <h5 class="mb-0 fw-bold">Recent DTR</h5>
                                    <a href="DTR" class="text-decoration-none text-primary small fw-bold">View All</a>
                                </div>
                            </div>
                            <div class="card-body p-2">
                                <ul class="list-group list-group-flush border-0" id="dtrList">
                                    <!-- Dynamic DTR entries -->
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4" id="ojtDetailsCardSection" style="display: none;">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 pb-0">
                                <h5 class="mb-0 fw-bold">My OJT Details</h5>
                            </div>
                            <div class="card-body p-4 pt-2">
                                <div class="list-group list-group-flush border-0">
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <small class="text-body-secondary d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Company</small>
                                        <span class="fw-bold" id="detailCompany">---</span>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <small class="text-body-secondary d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Work Setup</small>
                                        <div id="detailSetup">---</div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <small class="text-body-secondary d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Supervisor</small>
                                        <span class="fw-bold" id="detailSupervisor">---</span>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <small class="text-body-secondary d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Coordinator</small>
                                        <span class="fw-bold" id="detailCoordinator">---</span>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-0">
                                        <small class="text-body-secondary d-block text-uppercase fw-bold ls-1 mb-1" style="font-size: 0.65rem;">Start Date</small>
                                        <span class="fw-bold" id="detailStart">---</span>
                                    </div>
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