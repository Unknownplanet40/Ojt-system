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
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Review DTR entry</h5>
                        <p class="text-muted small mb-0"><span id="decisionStudentName">Student</span> · <span id="decisionEntryDate">Date</span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div class="row g-3 mb-3">
                        <div class="col-md-4"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Time</div><div class="fw-semibold" id="decisionTimeRange">—</div></div></div>
                        <div class="col-md-4"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Hours rendered</div><div class="fw-semibold" id="decisionHours">0.00</div></div></div>
                        <div class="col-md-4"><div class="border rounded-3 p-3 h-100"><div class="text-muted small">Status</div><div class="fw-semibold" id="decisionStatus">Pending</div></div></div>
                    </div>
                    <div class="mb-3">
                        <label for="decisionReason" class="form-label fw-medium">Rejection reason</label>
                        <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="decisionReason" rows="3" placeholder="Required only when rejecting" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                    </div>
                    <div class="alert alert-info border-0 rounded-3 py-2 px-3 mb-0">
                        <small>Backdated entries are flagged for manual review. Select the action that matches the submission.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-success" id="decisionApproveBtn"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                        <button type="button" class="btn btn-outline-danger" id="decisionRejectBtn"><i class="bi bi-x-circle me-1"></i>Reject</button>
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
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-clipboard2-check fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-primary small">DTR Review Queue</p>
                                        <h4 class="mb-1 fw-semibold text-break">Pending logs from your assigned students</h4>
                                        <p class="mb-0 text-muted small">Approve on-time entries, review backdated submissions, and keep the OJT timeline moving.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                        <button class="btn btn-primary rounded-pill px-3" id="approveSelectedBtn"><i class="bi bi-check2-circle me-1"></i>Approve selected</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="row row-cols-2 g-3 small">
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Pending entries</div><div class="fw-semibold fs-5" id="supervisorPendingCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Students</div><div class="fw-semibold fs-5" id="supervisorStudentCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Backdated</div><div class="fw-semibold fs-5" id="supervisorBackdatedCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Hours pending</div><div class="fw-semibold fs-5" id="supervisorPendingHours">0.00</div></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorSearchInput">Search</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent" id="supervisorSearchInput" placeholder="Search student or activity" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorStatusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent" id="supervisorStatusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option value="">All</option>
                                    <option value="pending" selected>Pending</option>
                                </select>
                            </div>
                            <div class="col-6 col-md-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="supervisorMonthFilter">Month</label>
                                <input type="month" class="form-control bg-blur-5 bg-semi-transparent" id="supervisorMonthFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-12 col-md-2 text-md-end">
                                <button type="button" class="btn btn-outline-secondary w-100" id="clearSupervisorFiltersBtn">Clear</button>
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
                                    <h5 class="mb-1 fw-semibold">Pending DTR reviews</h5>
                                    <p class="mb-0 text-muted small">Approve logs, inspect activities, and flag anything that needs changes.</p>
                                </div>
                            </div>
                            <div class="text-muted small">
                                <i class="bi bi-shield-check me-1"></i>Only pending entries are shown here
                            </div>
                        </div>
                        <div id="supervisorDtrList" class="dtr-list vstack gap-3"></div>
                        <div class="p-4 text-center d-none dtr-empty-state" id="supervisorDtrEmptyState">
                            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                                <i class="bi bi-clipboard-check fs-4"></i>
                            </div>
                            <h5 class="mb-2">No pending logs right now</h5>
                            <p class="text-muted mb-0">Once your students submit DTR entries, they’ll show up here for your review.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
