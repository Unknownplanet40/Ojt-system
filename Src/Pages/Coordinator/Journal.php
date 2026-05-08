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
    <script type="module" src="../../../Assets/Script/DashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/JournalScript.js"></script>
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

    <!-- Review Journal Modal -->
    <div class="modal fade" id="reviewJournalModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 border-bottom border-light border-opacity-10 pb-3">
                    <div class="flex-grow-1">
                        <h5 class="modal-title fw-bold mb-1">Review Journal Entry</h5>
                        <p class="text-muted small mb-0"><span id="viewStudentName" class="fw-semibold text-body"></span> • <span id="viewJournalWeekRange" class="text-primary"></span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-4">
                    <input type="hidden" id="reviewJournalUuid">
                    
                    <div id="viewJournalStatusBadge" class="mb-4"></div>

                    <h6 class="fw-bold text-uppercase text-muted mb-3 small d-flex align-items-center"><i class="bi bi-pencil-square me-2 text-primary"></i>Student's Entry</h6>
                    
                    <div class="mb-4">
                        <label class="fw-semibold small text-muted text-uppercase mb-2 d-block">Accomplishments & Tasks</label>
                        <div class="p-4 bg-body-secondary bg-opacity-10 rounded-4 text-body border border-light border-opacity-10 lh-lg" id="viewAccomplishments" style="white-space: pre-wrap; font-size: 0.95rem;"></div>
                    </div>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-lg-4 col-md-6">
                            <label class="fw-semibold small text-muted text-uppercase mb-2 d-block">Skills Learned</label>
                            <div class="p-3 bg-body-secondary bg-opacity-10 rounded-3 text-body h-100 border border-light border-opacity-10 small lh-lg" id="viewSkillsLearned" style="white-space: pre-wrap;"></div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="fw-semibold small text-muted text-uppercase mb-2 d-block">Challenges</label>
                            <div class="p-3 bg-body-secondary bg-opacity-10 rounded-3 text-body h-100 border border-light border-opacity-10 small lh-lg" id="viewChallenges" style="white-space: pre-wrap;"></div>
                        </div>
                        <div class="col-lg-4 col-md-6">
                            <label class="fw-semibold small text-muted text-uppercase mb-2 d-block">Plans Next Week</label>
                            <div class="p-3 bg-body-secondary bg-opacity-10 rounded-3 text-body h-100 border border-light border-opacity-10 small lh-lg" id="viewPlansNextWeek" style="white-space: pre-wrap;"></div>
                        </div>
                    </div>

                    <hr class="my-4 opacity-25">
                    
                    <h6 class="fw-bold text-uppercase text-muted mb-3 small d-flex align-items-center"><i class="bi bi-chat-dots me-2 text-info"></i>Your Evaluation</h6>

                    <div class="mb-3">
                        <label for="coordinatorRemarks" class="form-label fw-medium small">Remarks / Feedback</label>
                        <textarea class="form-control bg-blur-5 bg-semi-transparent border border-light border-opacity-25 shadow-none" id="coordinatorRemarks" rows="3" placeholder="Provide constructive feedback..." style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                    </div>

                    <div class="mb-0 d-none" id="returnReasonContainer">
                        <label for="returnReason" class="form-label fw-medium small text-danger">Return Reason <span class="text-danger">*</span></label>
                        <textarea class="form-control bg-blur-5 bg-semi-transparent border border-danger border-opacity-50 shadow-none" id="returnReason" rows="2" placeholder="Clearly explain what needs to be revised..." style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                        <div class="invalid-feedback d-block small mt-1" id="returnReasonError"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 border-top border-light border-opacity-10 pt-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-sm btn-outline-info action-btn rounded-pill px-4" data-action="remark" id="btnSaveRemarks"><i class="bi bi-check-lg me-1"></i>Save Remarks</button>
                    <button type="button" class="btn btn-sm btn-outline-danger action-btn rounded-pill px-4" data-action="return" id="btnReturnJournal"><i class="bi bi-arrow-return-left me-1"></i>Return</button>
                    <button type="button" class="btn btn-sm btn-success action-btn rounded-pill px-4" data-action="approve" id="btnApproveJournal"><i class="bi bi-check-circle me-1"></i>Approve</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
                    <div>
                        <h4 class="mb-1 fw-bold">Student Journals</h4>
                        <p class="text-muted mb-0 small">Review and approve weekly progress reports from your students.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-3">
                        <div class="card glass-ui glass-ui-strong border-0 rounded-4 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0 fw-semibold text-uppercase small">Total Journals</h6>
                                    <div class="rounded-circle bg-primary-subtle text-primary p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-journal-text"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0" id="statTotal">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card glass-ui glass-ui-strong border-0 rounded-4 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0 fw-semibold text-uppercase small">Pending Review</h6>
                                    <div class="rounded-circle bg-info-subtle text-info p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-clock-history"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-info" id="statPending">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card glass-ui glass-ui-strong border-0 rounded-4 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0 fw-semibold text-uppercase small">Approved</h6>
                                    <div class="rounded-circle bg-success-subtle text-success p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-check-circle"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-success" id="statApproved">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-3">
                        <div class="card glass-ui glass-ui-strong border-0 rounded-4 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="text-muted mb-0 fw-semibold text-uppercase small">Returned</h6>
                                    <div class="rounded-circle bg-danger-subtle text-danger p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-arrow-return-left"></i></div>
                                </div>
                                <h3 class="fw-bold mb-0 text-danger" id="statReturned">0</h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card glass-ui glass-ui-strong rounded-4 mb-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3">
                            <div class="col-12 col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="bi bi-search"></i></span>
                                    <input type="search" class="form-control bg-transparent border-start-0 ps-0 shadow-none" id="journalSearchInput" placeholder="Search by student name or content...">
                                </div>
                            </div>
                            <div class="col-12 col-md-8">
                                <div class="d-flex gap-2 flex-wrap justify-content-md-end">
                                    <button class="btn btn-outline-primary border-0 bg-primary bg-opacity-10 rounded-pill px-3 filter-btn active" data-filter="">All</button>
                                    <button class="btn btn-outline-info border-0 rounded-pill px-3 filter-btn" data-filter="submitted">Pending Review <span class="badge bg-info ms-1 d-none" id="badgePending">0</span></button>
                                    <button class="btn btn-outline-success border-0 rounded-pill px-3 filter-btn" data-filter="approved">Approved</button>
                                    <button class="btn btn-outline-danger border-0 rounded-pill px-3 filter-btn" data-filter="returned">Returned</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4" id="coordinatorJournalList">
                    <!-- Journals will be rendered here -->
                </div>
                
                <div class="p-5 text-center d-none" id="coordinatorJournalEmptyState">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary" style="width: 80px; height: 80px;">
                        <i class="bi bi-journal-x fs-1"></i>
                    </div>
                    <h5 class="mb-2 fw-semibold">No Journals Found</h5>
                    <p class="text-muted mb-0">There are no weekly journals matching your current filters.</p>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
