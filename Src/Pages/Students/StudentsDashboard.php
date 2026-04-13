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
                                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
                                        style="width: 48px; height: 48px;">
                                        <i class="bi bi-person-check fs-5"></i>
                                    </div>

                                    <div class="flex-grow-1 min-w-0">
                                        <h4 class="mb-1 fw-semibold text-break">
                                            <?= $greeting ?>, <strong id="welcomeUserName"></strong>
                                        </h4>
                                        <p class="mb-0 text-muted small d-flex flex-wrap align-items-center gap-2">
                                            <span><?= date("l, F j, Y") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><?= date("h:i A") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><span id="currentSemester"></span> - <span id="currentAcademicYear"></span></span>
                                        </p>
                                    </div>

                                    <div class="ms-sm-auto">
                                        <button type="button"
                                            class="btn btn-outline-success rounded-pill d-inline-flex align-items-center gap-2 px-3 px-xl-4 py-2 shadow-sm"
                                            id="dashboardRefreshBtn" aria-label="Refresh dashboard">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            <span class="fw-medium">Refresh dashboard</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-3">
                        <button
                            class="btn btn-success rounded-4 px-4 py-3 fw-semibold shadow-sm h-100 w-100"
                            id="LogTimeBtn">
                            <i class="bi bi-clock me-2"></i>Log today's time
                        </button>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-4 g-4">
                    <div class="col-md-12">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border-success-subtle"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
                                        style="width: 52px; height: 52px;">
                                        <i class="bi bi-building fs-4"></i>
                                    </div>

                                    <div class="flex-grow-1 min-w-0 w-100">
                                        <div class="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-2 mb-2">
                                            <p class="mb-0 text-uppercase fw-semibold text-success small">Active OJT</p>
                                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">On-going</span>
                                        </div>

                                        <h5 class="card-title mb-2 text-truncate">Accenture Philippines, Inc.</h5>

                                        <div class="row g-2 g-md-3 text-muted small">
                                            <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center gap-2">
                                                <i class="bi bi-diagram-3 text-success"></i>
                                                <span class="text-break">Software Engineering</span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-2 d-flex align-items-center gap-2">
                                                <i class="bi bi-laptop text-success"></i>
                                                <span>Hybrid</span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-4 d-flex align-items-center gap-2">
                                                <i class="bi bi-person-badge text-success"></i>
                                                <span class="text-break">Supervisor: David Tan</span>
                                            </div>
                                            <div class="col-12 col-sm-6 col-lg-3 d-flex align-items-center gap-2">
                                                <i class="bi bi-calendar-event text-success"></i>
                                                <span>Started Jun 10, 2025</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex flex-row flex-md-column gap-2 ms-md-auto mt-2 mt-md-0 w-auto justify-content-md-start align-items-md-end">
                                        <a href="javascript:void(0)" class="btn btn-success btn-sm px-3 text-center" style="width: 140px;">View details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-lg-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-muted small fw-semibold text-uppercase">Hours rendered</p>
                                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">24.7%</span>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold">120</h3>
                                    <small class="text-muted">of 486 required</small>
                                </div>
                                <div class="progress mt-auto" style="height: 8px;">
                                    <div class="progress-bar bg-success rounded-pill" role="progressbar"
                                        style="width: 24.7%;" aria-valuenow="120" aria-valuemin="0" aria-valuemax="486"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-lg-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-muted small fw-semibold text-uppercase">Days remaining</p>
                                    <i class="bi bi-calendar-event text-success fs-5"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold">48</h3>
                                    <small class="text-muted">Est. end Oct 15, 2025</small>
                                </div>
                                <small class="text-muted mt-auto">Keep your daily logs updated.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-lg-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-muted small fw-semibold text-uppercase">Journals submitted</p>
                                    <i class="bi bi-journal-check text-success fs-5"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold">12</h3>
                                    <small class="text-muted">of 24 weeks</small>
                                </div>
                                <div class="progress mt-auto" style="height: 8px;">
                                    <div class="progress-bar bg-primary rounded-pill" role="progressbar"
                                        style="width: 50%;" aria-valuenow="12" aria-valuemin="0" aria-valuemax="24"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-lg-4 d-flex flex-column gap-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <p class="mb-0 text-muted small fw-semibold text-uppercase">Current grade</p>
                                    <i class="bi bi-award text-success fs-5"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold">&mdash;</h3>
                                    <small class="text-muted">Not yet computed</small>
                                </div>
                                <small class="text-muted mt-auto">Visible after coordinator evaluation.</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <?php
                        $requiredHours = 486;
                        $renderedHours = 120;
                        $remainingHours = max(0, $requiredHours - $renderedHours);
                        $hoursPercent = $requiredHours > 0 ? round(($renderedHours / $requiredHours) * 100, 1) : 0;

                        $daysLogged = 22;
                        $avgHoursPerDay = 6.5;
                        $pendingDtr = 0;
                        ?>

                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-header bg-transparent border-0 px-3 px-md-4 pt-3 pt-md-4 pb-0">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </span>
                                    <div>
                                        <p class="mb-0 fw-semibold">Hours Progress</p>
                                        <small class="text-muted">Track your internship completion</small>
                                    </div>
                                    <a href="javascript:void(0)" class="ms-auto text-decoration-none text-success small fw-semibold"
                                        id="hoursProgressDetailsLink">View details</a>
                                </div>
                            </div>

                            <div class="card-body px-3 px-md-4 pb-3 pb-md-4">
                                <div class="text-center mb-3">
                                    <h2 class="fw-bold mb-1"><?= number_format($renderedHours) ?></h2>
                                    <p class="text-muted mb-0">of <?= number_format($requiredHours) ?> required hours</p>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <small class="text-muted">Completion</small>
                                    <small class="fw-semibold text-success"><?= $hoursPercent ?>%</small>
                                </div>

                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-success rounded-pill" role="progressbar"
                                        style="width: <?= $hoursPercent ?>%;"
                                        aria-valuenow="<?= $renderedHours ?>" aria-valuemin="0" aria-valuemax="<?= $requiredHours ?>">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted"><?= number_format($remainingHours) ?> hours remaining</small>
                                    <small class="text-muted"><?= number_format($renderedHours) ?>/<?= number_format($requiredHours) ?></small>
                                </div>

                                <hr class="my-3">

                                <div class="row row-cols-3 g-2 text-center">
                                    <div class="col">
                                        <div class="rounded-3 bg-dark bg-opacity-25 p-2 h-100">
                                            <p class="mb-0 fw-bold"><?= number_format($daysLogged) ?></p>
                                            <small class="text-muted">Days logged</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 bg-dark bg-opacity-25 p-2 h-100">
                                            <p class="mb-0 fw-bold"><?= number_format($avgHoursPerDay, 1) ?></p>
                                            <small class="text-muted">Avg hrs/day</small>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 bg-dark bg-opacity-25 p-2 h-100">
                                            <p class="mb-0 fw-bold"><?= number_format($pendingDtr) ?></p>
                                            <small class="text-muted">Pending DTR</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 border"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-bottom-0 pb-0">
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <p class="card-text fw-semibold mb-0">Quick actions</p>
                                    <small class="text-muted">Frequently used shortcuts</small>
                                </div>
                            </div>

                            <div class="card-body pt-3">
                                <div class="d-grid gap-2">
                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-3 border border-secondary-subtle bg-dark bg-opacity-50 p-3 d-flex align-items-start gap-3"
                                        id="quickLogTime" aria-label="Log today's time">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0"
                                            style="width: 42px; height: 42px;">
                                            <i class="bi bi-clock fs-5"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-white text-wrap">Log today’s time</span>
                                                <i class="bi bi-arrow-up-right-circle text-muted"></i>
                                            </span>
                                            <small class="text-muted d-block">Record your time-in and time-out</small>
                                        </span>
                                    </button>

                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-3 border border-secondary-subtle bg-dark bg-opacity-50 p-3 d-flex align-items-start gap-3"
                                        id="quickSubmitJournal" aria-label="Submit this week's journal">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
                                            style="width: 42px; height: 42px;">
                                            <i class="bi bi-journal-text fs-5"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-white text-wrap">Submit this week’s journal</span>
                                                <i class="bi bi-arrow-up-right-circle text-muted"></i>
                                            </span>
                                            <small class="text-muted d-block">Week 4 — due Friday</small>
                                        </span>
                                    </button>

                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-3 border border-secondary-subtle bg-dark bg-opacity-50 p-3 d-flex align-items-start gap-3"
                                        id="quickViewEndorsementLetter" aria-label="View endorsement letter">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning flex-shrink-0"
                                            style="width: 42px; height: 42px;">
                                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-white text-wrap">View endorsement letter</span>
                                                <i class="bi bi-arrow-up-right-circle text-muted"></i>
                                            </span>
                                            <small class="text-muted d-block">Download your endorsement PDF</small>
                                        </span>
                                    </button>

                                    <button type="button"
                                        class="quickactions btn w-100 text-start rounded-3 border border-secondary-subtle bg-dark bg-opacity-50 p-3 d-flex align-items-start gap-3"
                                        id="quickViewProfile" aria-label="View my profile">
                                        <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary flex-shrink-0"
                                            style="width: 42px; height: 42px;">
                                            <i class="bi bi-person-circle fs-5"></i>
                                        </span>
                                        <span class="flex-grow-1 min-w-0">
                                            <span class="d-flex align-items-center justify-content-between gap-2">
                                                <span class="fw-semibold text-white text-wrap">View my profile</span>
                                                <i class="bi bi-arrow-up-right-circle text-muted"></i>
                                            </span>
                                            <small class="text-muted d-block">Update personal information</small>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 p-2"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-bottom-0">
                                <div class="hstack">
                                    <p class="card-text fw-semibold mb-0">Requirements</p>
                                    <a href="../../Pages/Students/Requirements" class="ms-auto text-decoration-none text-success">View all</a>
                                </div>
                            </div>
                            <?php
                            $requirements = [
                                ['name' => 'Medical certificate', 'status' => 'Approved'],
                                ['name' => 'Parental consent', 'status' => 'Returned'],
                                ['name' => 'Insurance', 'status' => 'Approved'],
                                ['name' => 'NBI clearance', 'status' => 'Approved'],
                                ['name' => 'Resume / CV', 'status' => 'Submitted'],
                                ['name' => 'Guardian form', 'status' => 'Not submitted'],
                            ];

                            $statusStyles = [
                                'Approved'      => ['badge' => 'bg-success-subtle text-success-emphasis', 'dot' => 'text-success'],
                                'Returned'      => ['badge' => 'bg-danger-subtle text-danger-emphasis', 'dot' => 'text-danger'],
                                'Submitted'     => ['badge' => 'bg-primary-subtle text-primary-emphasis', 'dot' => 'text-primary'],
                                'Not submitted' => ['badge' => 'bg-secondary-subtle text-secondary-emphasis', 'dot' => 'text-secondary'],
                            ];
                            ?>

                            <ul class="list-group list-group-flush" id="requirementsList" style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($requirements as $req): ?>
                                    <?php
                                    $status = $req['status'];
                                    $style = $statusStyles[$status] ?? ['badge' => 'bg-light text-dark', 'dot' => 'text-muted'];
                                    ?>
                                    <li class="list-group-item bg-transparent px-2 px-sm-3 py-3 border-secondary-subtle">
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 w-100">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="<?= $style['dot'] ?>" style="font-size: 0.70rem;">&#11044;</span>
                                                <span class="fw-semibold"><?= htmlspecialchars($req['name']) ?></span>
                                            </div>
                                            <span class="badge rounded-pill px-3 py-2 ms-sm-auto <?= $style['badge'] ?>">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 p-2"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-bottom-0">
                                <div class="hstack">
                                    <p class="card-text fw-semibold mb-0">Recent DTR</p>
                                    <a href="javascript:void(0)" class="ms-auto text-decoration-none text-success"
                                        id="dtrViewAllLink">View all</a>
                                </div>
                            </div>
                            <ul class="list-group list-group-flush" id="dtrList" style="max-height: 400px; overflow-y: auto;">
                                <?php for ($i = 0; $i < 20; $i++) : ?>
                                <li class="list-group-item bg-transparent px-3 py-3">
                                    <?php
                                    $logDate = strtotime("-{$i} day");
                                    $timeIn = strtotime("08:00");
                                    $timeOut = strtotime("17:00");
                                    $hoursRendered = (int) (($timeOut - $timeIn) / 3600);
                                    ?>
                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 w-100">
                                        <div class="vstack gap-1">
                                            <span class="fw-semibold text-body"><?= date("M j, D", $logDate) ?></span>
                                            <span class="text-muted small"><?= date("g:i A", $timeIn) ?> – <?= date("g:i A", $timeOut) ?></span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 ms-sm-auto">
                                            <span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3 py-2">
                                                <?= $hoursRendered ?>h
                                            </span>
                                            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2">
                                                Approved
                                            </span>
                                        </div>
                                    </div>
                                </li>
                                <?php endfor; ?>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg rounded-4 p-2"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-bottom-0">
                                <div class="hstack">
                                    <p class="card-text fw-semibold mb-0">My OJT Details</p>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Company</div>
                                            <div class="col-12 col-sm-8 fw-semibold text-break">Accenture Philippines, Inc.</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Work setup</div>
                                            <div class="col-12 col-sm-8">
                                                <span class="badge rounded-pill text-bg-primary-subtle text-primary-emphasis">Hybrid</span>
                                            </div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Department</div>
                                            <div class="col-12 col-sm-8 fw-semibold text-break">Software Engineering</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Supervisor</div>
                                            <div class="col-12 col-sm-8 fw-semibold text-break">David Tan</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Coordinator</div>
                                            <div class="col-12 col-sm-8 fw-semibold text-break">Jane Smith</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Start date</div>
                                            <div class="col-12 col-sm-8 fw-semibold">Jun 10, 2025</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-secondary-subtle">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Program</div>
                                            <div class="col-12 col-sm-8 fw-semibold text-break">BS Computer Science</div>
                                        </div>
                                    </li>
                                    <li class="list-group-item bg-transparent px-0 py-2 border-0">
                                        <div class="row g-1">
                                            <div class="col-12 col-sm-4 text-muted small">Required hours</div>
                                            <div class="col-12 col-sm-8 fw-semibold">486 hours</div>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>