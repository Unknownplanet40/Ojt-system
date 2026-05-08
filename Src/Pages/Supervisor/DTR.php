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

$CurrentPage = "DTR";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/SupervisorDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/SupervisorScripts/DTRScripts.js"></script>
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

    <div class="modal fade" id="supervisorDecisionModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">DTR entry details</h5>
                        <p class="text-muted small mb-0"><span id="supervisorDecisionStudentName">Student</span> · <span id="supervisorDecisionEntryDate">Date</span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Time</div><div class="fw-semibold" id="supervisorDecisionTimeRange">—</div></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Hours rendered</div><div class="fw-semibold" id="supervisorDecisionHours">0.00</div></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Status</div><div class="fw-semibold" id="supervisorDecisionStatus">Pending</div></div></div>
                        <div class="col-md-3"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Backdated</div><div class="fw-semibold" id="supervisorDecisionBackdated">No</div></div></div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-2">Activities</div>
                                <div class="fw-normal" id="supervisorDecisionActivities">—</div>
                                <hr>
                                <div class="text-muted small mb-2">Backdate reason / rejection reason</div>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="supervisorDecisionReason" rows="4" placeholder="Required when rejecting or for backdated review" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                            </div>
                        </div>
                        <div class="col-lg-5">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-2">Student snapshot</div>
                                <div class="vstack gap-2">
                                    <div><span class="text-muted small d-block">Name</span><span class="fw-semibold" id="supervisorDecisionStudentLabel">—</span></div>
                                    <div><span class="text-muted small d-block">Student no.</span><span class="fw-semibold" id="supervisorDecisionStudentNumber">—</span></div>
                                    <div><span class="text-muted small d-block">Program</span><span class="fw-semibold" id="supervisorDecisionProgram">—</span></div>
                                    <div><span class="text-muted small d-block">Submitted</span><span class="fw-semibold" id="supervisorDecisionSubmittedAt">—</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success" id="supervisorDecisionApproveBtn"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                        <button type="button" class="btn btn-outline-danger" id="supervisorDecisionRejectBtn"><i class="bi bi-x-circle me-1"></i>Reject</button>
                    </div>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Supervisor.php"; ?>
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
                                        <p class="mb-1 text-uppercase fw-semibold text-success small">DTR Review Queue</p>
                                        <h4 class="mb-1 fw-semibold text-break">Monitor and review time records from your assigned students</h4>
                                        <p class="mb-0 text-muted small">Approve on-time entries, review backdated submissions, and keep the OJT timeline moving.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                        <button class="btn btn-success rounded-pill px-3" id="approveSelectedBtn"><i class="bi bi-check2-circle me-1"></i>Approve selected</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="row row-cols-2 g-3 small">
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Entries</div><div class="fw-semibold fs-5" id="supervisorEntriesCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Students</div><div class="fw-semibold fs-5" id="supervisorStudentsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Pending</div><div class="fw-semibold fs-5" id="supervisorPendingCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Backdated</div><div class="fw-semibold fs-5" id="supervisorBackdatedCount">0</div></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorSearchInput">Search</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent" id="supervisorSearchInput" placeholder="Search student or activity" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorStatusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent" id="supervisorStatusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All</option>
                                    <option class="CustomOption" value="pending">Pending</option>
                                    <option class="CustomOption" value="approved">Approved</option>
                                    <option class="CustomOption" value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorMonthFilter">Month</label>
                                <input type="month" class="form-control bg-blur-5 bg-semi-transparent" id="supervisorMonthFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-6 col-lg-2">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorBackdatedFilter">Backdated</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent" id="supervisorBackdatedFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All</option>
                                    <option class="CustomOption" value="0">No</option>
                                    <option class="CustomOption" value="1">Yes</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-3 text-lg-end">
                                <button type="button" class="btn btn-outline-secondary w-100" id="clearSupervisorFiltersBtn">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <input class="form-check-input" type="checkbox" id="selectAllSupervisorEntries">
                                <div>
                                    <h5 class="mb-1 fw-semibold">DTR records</h5>
                                    <p class="mb-0 text-muted small">Review all entries from your assigned students, including pending, approved, and rejected logs.</p>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-funnel me-1"></i>Filters update the list instantly
                            </div>
                        </div>
                        <div id="supervisorDtrList" class="dtr-list vstack gap-3"></div>
                        <div class="p-4 text-center d-none dtr-empty-state" id="supervisorDtrEmptyState">
                            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 64px; height: 64px;">
                                <i class="bi bi-clipboard-data fs-4"></i>
                            </div>
                            <h5 class="mb-2">No DTR records match your filters</h5>
                            <p class="text-muted mb-0">Try adjusting your search or status filters to find the records you need.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
