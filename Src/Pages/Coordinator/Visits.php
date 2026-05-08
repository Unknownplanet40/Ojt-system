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

$CurrentPage = "Visits";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/VisitsScript.js"></script>
    <title><?= $ShortTitle ?> - Coordinator Visits</title>
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

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php"; ?>
            
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row g-3 mb-4 align-items-stretch module-header">
                    <div class="col-12 col-xl-8">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-briefcase-fill fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-primary small">Visit Management</p>
                                        <h4 class="mb-1 fw-semibold text-break">Schedule and Log Company Visits</h4>
                                        <p class="mb-0 text-muted small">Monitor student progress and evaluate company environments.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#ScheduleVisitModal">
                                            <i class="bi bi-calendar-plus me-2"></i>Schedule Visit
                                        </button>
                                        <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#LogUnscheduledModal">
                                            <i class="bi bi-journal-plus me-2"></i>Log Unscheduled
                                        </button>
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="refreshBtn">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="row row-cols-2 g-3 small h-100">
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Total Visits</div><div class="fw-semibold fs-5" id="totalVisitsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Upcoming</div><div class="fw-semibold fs-5 text-primary" id="upcomingVisitsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Completed</div><div class="fw-semibold fs-5 text-success" id="completedVisitsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Overdue</div><div class="fw-semibold fs-5 text-danger" id="overdueVisitsCount">0</div></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4 filter-card" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="statusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent shadow-none" id="statusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All Statuses</option>
                                    <option class="CustomOption" value="scheduled">Scheduled</option>
                                    <option class="CustomOption" value="completed">Completed</option>
                                    <option class="CustomOption" value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="companyFilter">Company</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent shadow-none" id="companyFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All Companies</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-4">
                                <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="visitsGrid" class="row g-3 g-md-4">
                    <!-- Cards will be populated here -->
                </div>
                
                <div class="p-5 text-center d-none" id="emptyState">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                        <i class="bi bi-briefcase fs-4"></i>
                    </div>
                    <h5 class="mb-2 text-light">No visits found</h5>
                    <p class="text-muted mb-0">Try adjusting your filters or schedule a new visit.</p>
                </div>
            </div>

            <!-- Schedule Visit Modal -->
            <div class="modal fade" id="ScheduleVisitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <form id="scheduleVisitForm" class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                                <div class="mb-5">
                                    <div class="hstack gap-3 align-items-center">
                                        <div class="vstack gap-1 text-white">
                                            <h5 class="modal-title fw-bold mb-0 fs-5">Schedule a Visit</h5>
                                            <p class="text-white-50 small mb-0">Plan a future company visit to monitor students.</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="visit_type" value="scheduled">

                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Company to Visit <span class="text-danger">*</span></label>
                                                <select class="form-select bg-transparent text-white border-light border-opacity-10" id="scheduleCompany" name="company_uuid" required>
                                                    <option class="CustomOption" value="" selected disabled>Select Company</option>
                                                </select>
                                                <small class="text-muted" id="companyStudentCountLabel"></small>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label text-white-50 small">Visit Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="scheduleDate" name="visit_date" required min="<?= date('Y-m-d') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Purpose of Visit <span class="text-danger">*</span></label>
                                                <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="schedulePurpose" name="purpose" rows="3" placeholder="E.g., Routine checking, Final evaluation" required></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill" id="btnSaveSchedule">Schedule Visit</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>

            <!-- Log Unscheduled Visit Modal -->
            <div class="modal fade" id="LogUnscheduledModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <form id="logUnscheduledForm" class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                                <div class="mb-5">
                                    <div class="hstack gap-3 align-items-center">
                                        <div class="vstack gap-1 text-white">
                                            <h5 class="modal-title fw-bold mb-0 fs-5">Log Completed Visit</h5>
                                            <p class="text-white-50 small mb-0">Record a visit that has already occurred.</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" name="visit_type" value="unscheduled">

                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Company Visited <span class="text-danger">*</span></label>
                                                <select class="form-select bg-transparent text-white border-light border-opacity-10" id="unscheduledCompany" name="company_uuid" required>
                                                    <option class="CustomOption" value="" selected disabled>Select Company</option>
                                                </select>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <label class="form-label text-white-50 small">Visit Date <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="unscheduledDate" name="visit_date" required max="<?= date('Y-m-d') ?>">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Purpose <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="unscheduledPurpose" name="purpose" required>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Findings <span class="text-danger">*</span></label>
                                                <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="unscheduledFindings" name="findings" rows="3" required></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Recommendations (Optional)</label>
                                                <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="unscheduledRecommendations" name="recommendations" rows="2"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary px-4 rounded-pill" id="btnSaveUnscheduled">Save Record</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
            
            <!-- Complete Visit Modal -->
            <div class="modal fade" id="CompleteVisitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <form id="completeVisitForm" class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                                <div class="mb-4">
                                    <div class="hstack gap-3 align-items-center">
                                        <div class="vstack gap-1 text-white">
                                            <h5 class="modal-title fw-bold mb-0 fs-5">Log Visit Findings</h5>
                                            <p class="text-white-50 small mb-0">Record your observations for the scheduled visit.</p>
                                        </div>
                                    </div>
                                </div>
                                <input type="hidden" id="completeVisitUuid" name="visit_uuid">

                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                    <div class="card-body p-4">
                                        <div class="mb-3 p-3 rounded-3" style="background: rgba(255, 255, 255, 0.02); border: 1px dashed rgba(255, 255, 255, 0.1);">
                                            <h6 class="text-white mb-1" id="completeCompanyName">Company Name</h6>
                                            <small class="text-white-50">Date: <span id="completeVisitDate"></span></small>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Findings & Observations <span class="text-danger">*</span></label>
                                                <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="completeFindings" name="findings" rows="4" placeholder="Describe the working environment, student tasks, and overall performance" required></textarea>
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label text-white-50 small">Recommendations (Optional)</label>
                                                <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="completeRecommendations" name="recommendations" rows="3" placeholder="Any suggestions for the company or students"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success px-4 rounded-pill" id="btnSubmitComplete"><i class="bi bi-check-circle me-2"></i>Complete Visit</button>
                                </div>
                            </div>
                    </form>
                </div>
            </div>

            <!-- Cancel Visit Modal -->
            <div class="modal fade" id="CancelVisitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                    <form id="cancelVisitForm" class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="mb-4">
                                <div class="hstack gap-3 align-items-center">
                                    <div class="vstack gap-1 text-white">
                                        <h5 class="modal-title fw-bold mb-0 fs-5 text-danger">Cancel Visit</h5>
                                        <p class="text-white-50 small mb-0">Please provide a reason for cancellation.</p>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="cancelVisitUuid" name="visit_uuid">

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="col-12">
                                        <label class="form-label text-white-50 small">Reason for cancellation <span class="text-danger">*</span></label>
                                        <textarea class="form-control bg-transparent text-white border-light border-opacity-10 shadow-none" id="cancelReason" name="cancel_reason" rows="3" placeholder="Explain why the visit is being cancelled" required></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2">
                                <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">Keep Visit</button>
                                <button type="submit" class="btn btn-danger px-4 rounded-pill" id="btnSubmitCancel"><i class="bi bi-x-circle me-2"></i>Cancel Visit</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Visit Modal -->
            <div class="modal fade" id="ViewVisitModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="mb-4">
                                <div class="hstack gap-3 align-items-center">
                                    <div class="vstack gap-1 text-white flex-grow-1">
                                        <h5 class="modal-title fw-bold mb-0 fs-5" id="viewCompanyName">Company Name</h5>
                                        <div class="hstack gap-2 mt-1">
                                            <span class="badge" id="viewVisitStatus">Status</span>
                                            <small class="text-white-50" id="viewVisitDate">Date</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-12 col-md-6">
                                            <label class="text-white-50 small mb-1 d-block text-uppercase">Purpose</label>
                                            <div class="text-white fw-medium" id="viewPurpose"></div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label class="text-white-50 small mb-1 d-block text-uppercase">Visit Type</label>
                                            <div class="text-white fw-medium" id="viewVisitType"></div>
                                        </div>
                                        <div class="col-12 d-none" id="viewFindingsContainer">
                                            <div class="p-3 rounded-3" style="background: rgba(15, 110, 86, 0.1); border: 1px solid rgba(15, 110, 86, 0.2);">
                                                <label class="text-success small mb-2 d-block text-uppercase fw-bold"><i class="bi bi-search me-1"></i>Findings</label>
                                                <div class="text-white" id="viewFindings" style="white-space: pre-line;"></div>
                                            </div>
                                        </div>
                                        <div class="col-12 d-none" id="viewRecommendationsContainer">
                                            <div class="p-3 rounded-3" style="background: rgba(24, 95, 165, 0.1); border: 1px solid rgba(24, 95, 165, 0.2);">
                                                <label class="text-primary small mb-2 d-block text-uppercase fw-bold"><i class="bi bi-lightbulb me-1"></i>Recommendations</label>
                                                <div class="text-white" id="viewRecommendations" style="white-space: pre-line;"></div>
                                            </div>
                                        </div>
                                        <div class="col-12 d-none" id="viewCancelReasonContainer">
                                            <div class="p-3 rounded-3" style="background: rgba(220, 38, 38, 0.1); border: 1px solid rgba(220, 38, 38, 0.2);">
                                                <label class="text-danger small mb-2 d-block text-uppercase fw-bold"><i class="bi bi-exclamation-triangle me-1"></i>Cancel Reason</label>
                                                <div class="text-white" id="viewCancelReason" style="white-space: pre-line;"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end">
                                <button type="button" class="btn btn-outline-light px-4 rounded-pill" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
