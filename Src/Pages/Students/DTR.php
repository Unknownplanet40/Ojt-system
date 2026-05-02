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

$CurrentPage = "DTR";
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
    <script type="module" src="../../../Assets/Script/DashboardScripts/StudentDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/StudentsScripts/DTRScripts.js"></script>
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

    <div class="modal fade" id="dtrEntryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1" id="dtrEntryModalTitle">Log DTR Entry</h5>
                        <p class="text-muted small mb-0">Keep your daily time record accurate and up to date.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="dtrEntryForm" class="vstack gap-3">
                        <input type="hidden" id="dtrEntryUuid" name="dtr_uuid" value="">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="entryDate" class="form-label fw-medium">Entry date</label>
                                <input type="date" class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="entryDate" name="entry_date" value="<?= date('Y-m-d') ?>" style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="invalid-feedback d-block small" id="entryDateError"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="timeIn" class="form-label fw-medium">Time in</label>
                                <input type="time" class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="timeIn" name="time_in" style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="invalid-feedback d-block small" id="timeInError"></div>
                            </div>
                            <div class="col-md-4">
                                <label for="timeOut" class="form-label fw-medium">Time out</label>
                                <input type="time" class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="timeOut" name="time_out" style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="invalid-feedback d-block small" id="timeOutError"></div>
                            </div>
                        </div>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label for="lunchBreakMinutes" class="form-label fw-medium">Lunch break</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent border shadow-none" id="lunchBreakMinutes" name="lunch_break_minutes" style="--blur-lvl: <?= $opacitylvl ?>">
                                    <option class="CustomOption" value="0">No lunch break</option>
                                    <option class="CustomOption" value="30">30 minutes</option>
                                    <option class="CustomOption" value="45">45 minutes</option>
                                    <option class="CustomOption" value="60" selected>1 hour</option>
                                    <option class="CustomOption" value="90">1.5 hours</option>
                                    <option class="CustomOption" value="120">2 hours</option>
                                </select>
                                <div class="invalid-feedback d-block small" id="lunchBreakMinutesError"></div>
                            </div>
                            <div class="col-md-8">
                                <div class="alert alert-info border-0 rounded-3 py-2 px-3 mb-0">
                                    <small class="mb-0">Backdated entries up to 3 days old require a reason and go through review.</small>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="activities" class="form-label fw-medium">Activities performed</label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="activities" name="activities" rows="4" placeholder="Describe the work you accomplished today" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                            <div class="invalid-feedback d-block small" id="activitiesError"></div>
                        </div>
                        <div>
                            <label for="backdateReason" class="form-label fw-medium">Backdate reason</label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="backdateReason" name="backdate_reason" rows="2" placeholder="Required only for backdated entries" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                            <div class="invalid-feedback d-block small" id="backdateReasonError"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="saveDtrEntryBtn">Save entry</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row g-3 mb-4 align-items-stretch">
                    <div class="col-12 col-xl-8">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-clock-history fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-success small">Daily Time Record</p>
                                        <h4 class="mb-1 fw-semibold text-break">Good to see you, <strong id="welcomeUserName"></strong></h4>
                                        <p class="mb-0 text-muted small">Record your work hours, keep your log accurate, and track your progress toward completion.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                        <button class="btn btn-success rounded-pill px-3" id="newDtrEntryBtn"><i class="bi bi-plus-lg me-1"></i>Log entry</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <p class="mb-1 text-uppercase fw-semibold text-muted small">Completion</p>
                                        <h3 class="mb-0 fw-bold" id="completionPercent">0%</h3>
                                    </div>
                                    <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-bar-chart-line fs-5"></i>
                                    </div>
                                </div>
                                <div class="progress mb-3" style="height: 8px;">
                                    <div class="progress-bar bg-success rounded-pill" id="completionProgressBar" role="progressbar" style="width: 0%;"></div>
                                </div>
                                <div class="row row-cols-2 g-3 small">
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100">
                                            <div class="text-muted">Approved hours</div>
                                            <div class="fw-semibold fs-5" id="approvedHoursLabel">0.00</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100">
                                            <div class="text-muted">Remaining</div>
                                            <div class="fw-semibold fs-5" id="remainingHoursLabel">0.00</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100">
                                            <div class="text-muted">Pending</div>
                                            <div class="fw-semibold fs-5" id="pendingCountLabel">0</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100">
                                            <div class="text-muted">Backdated</div>
                                            <div class="fw-semibold fs-5" id="backdatedCountLabel">0</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="dtrStatusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent" id="dtrStatusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option value="">All statuses</option>
                                    <option value="pending">Pending</option>
                                    <option value="approved">Approved</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="dtrMonthFilter">Month</label>
                                <input type="month" class="form-control bg-blur-5 bg-semi-transparent" id="dtrMonthFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-12 col-md-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="dtrSearchInput">Search</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent" id="dtrSearchInput" placeholder="Search activity or date" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-12 col-md-3 text-md-end">
                                <button type="button" class="btn btn-outline-success w-100" id="clearDtrFiltersBtn">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="mb-1 fw-semibold">My DTR entries</h5>
                                <p class="mb-0 text-muted small">Track submitted time logs, edit pending entries, and review your progress.</p>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-info-circle me-1"></i>Tap an action to manage an entry
                            </div>
                        </div>
                        <div id="studentDtrList" class="dtr-list vstack gap-3"></div>
                        <div class="p-4 text-center d-none dtr-empty-state" id="studentDtrEmptyState">
                            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary" style="width: 64px; height: 64px;">
                                <i class="bi bi-inboxes fs-4"></i>
                            </div>
                            <h5 class="mb-2">No DTR entries yet</h5>
                            <p class="text-muted mb-3">Your recorded time entries will appear here as soon as you submit them.</p>
                            <button class="btn btn-success rounded-pill px-4" id="emptyStateNewEntryBtn"><i class="bi bi-plus-lg me-1"></i>Log your first entry</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
