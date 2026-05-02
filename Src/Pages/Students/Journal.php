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
require_once "../../../config/db.php";

$ojtStartDate = null;
$studentProfileUuid = $_SESSION['profile_uuid'] ?? '';
$activeBatchUuid = $_SESSION['active_batch_uuid'] ?? '';

if (!empty($studentProfileUuid) && !empty($activeBatchUuid) && isset($conn) && !$conn->connect_error) {
    $stmt = $conn->prepare("\n        SELECT osc.start_date\n        FROM ojt_applications a\n        JOIN ojt_start_confirmations osc ON osc.application_uuid = a.uuid\n        WHERE a.student_uuid = ? AND a.batch_uuid = ? AND a.status = 'active'\n        LIMIT 1\n    ");
    if ($stmt) {
        $stmt->bind_param('ss', $studentProfileUuid, $activeBatchUuid);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $ojtStartDate = $row['start_date'] ?? null;
    }
}

$ojtStartDateValue = $ojtStartDate ? date('Y-m-d', strtotime($ojtStartDate)) : '';

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
    <script type="module" src="../../../Assets/Script/DashboardScripts/StudentDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/StudentsScripts/JournalScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>" data-ojt-start-date="<?= htmlspecialchars($ojtStartDateValue) ?>">
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

    <!-- Journal Entry Modal -->
    <div class="modal fade" id="journalEntryModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1" id="journalEntryModalTitle">Weekly Journal Entry</h5>
                        <p class="text-muted small mb-0">Record your weekly accomplishments, challenges, and plans.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <form id="journalEntryForm" class="vstack gap-3">
                        <input type="hidden" id="journalEntryUuid" name="journal_uuid" value="">
                        
                        <!-- For Returned Journals: Feedback -->
                        <div id="returnFeedbackContainer" class="d-none alert alert-danger border-danger-subtle rounded-3 py-2 px-3 mb-0">
                            <div class="fw-semibold small"><i class="bi bi-exclamation-triangle-fill me-2"></i>Returned by Coordinator</div>
                            <p class="small mb-0 mt-1" id="returnReasonText"></p>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="weekStart" class="form-label fw-medium">Week Start Date</label>
                                <input type="date" class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="weekStart" name="week_start" min="<?= htmlspecialchars($ojtStartDateValue) ?>" style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="invalid-feedback d-block small" id="weekStartError"></div>
                            </div>
                            <div class="col-md-6">
                                <label for="weekEnd" class="form-label fw-medium">Week End Date</label>
                                <input type="date" class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="weekEnd" name="week_end" style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="invalid-feedback d-block small" id="weekEndError"></div>
                            </div>
                        </div>
                        
                        <div>
                            <label for="accomplishments" class="form-label fw-medium">Accomplishments & Tasks <span class="text-danger">*</span></label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="accomplishments" name="accomplishments" rows="4" placeholder="Describe the tasks and deliverables you completed this week" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                            <div class="invalid-feedback d-block small" id="accomplishmentsError"></div>
                        </div>

                        <div>
                            <label for="skillsLearned" class="form-label fw-medium">Skills Learned</label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="skillsLearned" name="skills_learned" rows="3" placeholder="What new technical or soft skills did you acquire?" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                        </div>

                        <div>
                            <label for="challenges" class="form-label fw-medium">Issues & Challenges</label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="challenges" name="challenges" rows="3" placeholder="What difficulties did you encounter and how did you resolve them?" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                        </div>

                        <div>
                            <label for="plansNextWeek" class="form-label fw-medium">Plans for Next Week</label>
                            <textarea class="form-control bg-blur-5 bg-semi-transparent border shadow-none" id="plansNextWeek" name="plans_next_week" rows="3" placeholder="What are your tasks or goals for the upcoming week?" style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveJournalEntryBtn">Submit Journal</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Journal Modal -->
    <div class="modal fade" id="viewJournalModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-semibold mb-1">Journal Details</h5>
                        <p class="text-muted small mb-0" id="viewJournalWeekRange"></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3">
                    <div id="viewJournalStatusBadge" class="mb-3"></div>

                    <div id="viewCoordinatorRemarksContainer" class="d-none alert alert-info border-info-subtle rounded-3 py-2 px-3 mb-3">
                        <div class="fw-semibold small"><i class="bi bi-chat-left-text me-2"></i>Coordinator Remarks</div>
                        <p class="small mb-0 mt-1" id="viewCoordinatorRemarks"></p>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-semibold text-muted small text-uppercase">Accomplishments & Tasks</h6>
                        <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body" id="viewAccomplishments" style="white-space: pre-wrap;"></div>
                    </div>
                    
                    <div class="mb-3">
                        <h6 class="fw-semibold text-muted small text-uppercase">Skills Learned</h6>
                        <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body" id="viewSkillsLearned" style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-semibold text-muted small text-uppercase">Issues & Challenges</h6>
                        <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body" id="viewChallenges" style="white-space: pre-wrap;"></div>
                    </div>

                    <div class="mb-3">
                        <h6 class="fw-semibold text-muted small text-uppercase">Plans for Next Week</h6>
                        <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body" id="viewPlansNextWeek" style="white-space: pre-wrap;"></div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning d-none" id="editReturnedJournalBtn">Edit & Resubmit</button>
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
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-journal-text fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-primary small">Weekly Journals</p>
                                        <h4 class="mb-1 fw-semibold text-break">Good to see you, <strong id="welcomeUserName"></strong></h4>
                                        <p class="mb-0 text-muted small">Submit your weekly progress reports to keep your coordinator updated.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="dashboardRefreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                        <button class="btn btn-primary rounded-pill px-3" id="newJournalEntryBtn"><i class="bi bi-plus-lg me-1"></i>Submit Journal</button>
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
                                        <p class="mb-1 text-uppercase fw-semibold text-muted small">Journal Stats</p>
                                        <h3 class="mb-0 fw-bold" id="totalJournalsCount">0</h3>
                                    </div>
                                    <div class="rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-book fs-5"></i>
                                    </div>
                                </div>
                                <div class="row row-cols-3 g-3 small">
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100 text-center">
                                            <div class="text-muted mb-1">Approved</div>
                                            <div class="fw-semibold fs-5 text-success" id="approvedJournalsCount">0</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100 text-center">
                                            <div class="text-muted mb-1">Pending</div>
                                            <div class="fw-semibold fs-5 text-info" id="pendingJournalsCount">0</div>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="rounded-3 border p-3 h-100 text-center">
                                            <div class="text-muted mb-1">Returned</div>
                                            <div class="fw-semibold fs-5 text-danger" id="returnedJournalsCount">0</div>
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
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="journalStatusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent" id="journalStatusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All statuses</option>
                                    <option class="CustomOption" value="submitted">Submitted</option>
                                    <option class="CustomOption" value="approved">Approved</option>
                                    <option class="CustomOption" value="returned">Returned</option>
                                </select>
                            </div>
                            <div class="col-12 col-md-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="journalSearchInput">Search</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent" id="journalSearchInput" placeholder="Search by content" style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
                            <div>
                                <h5 class="mb-1 fw-semibold">My Journals</h5>
                                <p class="mb-0 text-muted small">View your submitted journals and respond to coordinator feedback.</p>
                            </div>
                        </div>
                        <div id="studentJournalList" class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4"></div>
                        <div class="p-4 text-center d-none" id="studentJournalEmptyState">
                            <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary" style="width: 64px; height: 64px;">
                                <i class="bi bi-inboxes fs-4"></i>
                            </div>
                            <h5 class="mb-2">No Journals Found</h5>
                            <p class="text-muted mb-3">You haven't submitted any weekly journals yet.</p>
                            <button class="btn btn-primary rounded-pill px-4" id="emptyStateNewJournalBtn"><i class="bi bi-plus-lg me-1"></i>Submit your first journal</button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
