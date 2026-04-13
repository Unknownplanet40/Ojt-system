<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Applications";

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
    <script type="module" src="../../../Assets/Script/DashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/ApplicationsScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body data-role="<?= $_SESSION['user']['role'] ?>"
    data-uuid="<?= $_SESSION['user']['uuid'] ?>">
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
    <div class="modal fade" id="ReviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" data-application-uuid="">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border"
                style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title">Review Application</h5>
                        <small class="text-muted"><span id="stuName"></span> · <span id="stuNum"></span> · <span id="stuProg"></span></small>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 g-3">
                        <div class="col">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm h-100"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-4 fw-semibold text-uppercase letter-spacing">Student Information
                                    </h6>
                                    <div class="vstack gap-3">
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Name</span>
                                            <span class="text-body fw-semibold" id="stuNamec1"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Student No.</span>
                                            <span class="text-body fw-semibold" id="stuNumc1"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Program</span>
                                            <span class="text-body fw-semibold" id="stuProgc1"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Section</span>
                                            <span class="text-body fw-semibold" id="stuSectionc1"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Mobile</span>
                                            <span class="text-body fw-semibold" id="stuMobilec1"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-medium">Email</span>
                                            <span class="text-body fw-semibold text-end"
                                                style="font-size: 0.875rem;" id="stuEmailc1">
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm h-100"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-4 fw-semibold text-uppercase letter-spacing">Company Applied To
                                    </h6>
                                    <div class="vstack gap-3">
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Company</span>
                                            <span class="text-body fw-semibold text-end" id="stuCompanyc2"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Industry</span>
                                            <span class="text-body fw-semibold text-end" id="stuIndustryc2"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Location</span>
                                            <span class="text-body fw-semibold text-end" id="stuLocationc2"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Work Setup</span>
                                            <span class="text-body fw-semibold text-end" id="stuWorkSetupc2"></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Slots Left</span>
                                            <span class="badge bg-info-subtle text-info-emphasis fw-semibold" id="stuSlotsc2">—</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-medium">Accepts</span>
                                            <span class="text-body fw-semibold" id="stuAcceptsc2">—</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-4">
                                        <div>
                                            <h6 class="card-title mb-1 fw-semibold text-uppercase letter-spacing">Application
                                                Details</h6>
                                            <small class="text-muted" id="submittedAtc3">Submitted on: </small>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <p class="text-muted small fw-medium mb-2">Preferred Department</p>
                                        <p class="text-body fw-semibold" id="stuPreferredDeptc3">—</p>
                                    </div>
                                    <div>
                                        <p class="text-muted small fw-medium mb-2">Cover Letter</p>
                                        <div class="bg-body-secondary bg-opacity-25 rounded-3 p-3"
                                            style="max-height: 140px; overflow-y: auto;">
                                            <p class="mb-0 small lh-lg" id="coverletterc3">—</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title mb-1 fw-semibold text-uppercase letter-spacing">Pre-OJT
                                            Requirements</h6>
                                        <small class="text-muted">All must be approved before approving the
                                            application</small>
                                    </div>

                                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-6 g-3 mb-4" id="requirementsStatusc4">
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-success-subtle text-success-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-file-earmark-check fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">Resume</p>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-success-subtle text-success-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-file-earmark-check fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">Cover Letter</p>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-danger-subtle text-danger-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-file-earmark-x fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">Insurance</p>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-clock-history fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">Guardian Form</p>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-clock-history fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">Medical Form</p>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <div class="rounded-circle bg-warning-subtle text-warning-emphasis d-flex justify-content-center align-items-center flex-shrink-0"
                                                    style="width: 48px; height: 48px;">
                                                    <i class="bi bi-clock-history fs-5"></i>
                                                </div>
                                                <p class="mb-0 small fw-medium text-muted text-center">NBI Clearance</p>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-3 opacity-25">

                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn btn-sm bg-secondary-subtle text-secondary-emphasis border px-4 py-2 rounded-3" data-bs-dismiss="modal">Back</button>
                                        <button class="btn btn-sm bg-secondary-subtle text-secondary-emphasis border px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#ReturnModal" id="returnBtn">Return for revision</button>
                                        <button class="btn btn-sm bg-secondary-subtle text-secondary-emphasis border px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#RejectModal" id="rejectBtn">Reject</button>
                                        <button class="btn btn-sm bg-secondary-subtle text-secondary-emphasis border px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#ApproveModal" id="approveBtn">Approve</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ApproveModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" data-application-uuid="">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border"
                style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title">Approve Application</h5>
                        <small class="text-muted">Liza Bautista · 2021-00002 · BSIT 4th Year</small>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col-md-12">
                            <div class="card g-blur-5 bg-semi-transparent rounded-4 border"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body">
                                    <div class="vstack">
                                        <h6 class="card-title mb-0 fw-semibold">Your Decision</h6>
                                        <small class="text-muted mb-3">Add an optional note for the student before
                                            approving.</small>
                                    </div>
                                    <div class="mb-3">
                                        <label for="approvalNote" class="form-label">Note to student</label>
                                        <textarea class="form-control" id="approvalNote" rows="3"
                                            placeholder="Enter your note here..."></textarea>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn bg-secondary-subtle text-secondary-emphasis border-0 px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#ReviewModal">Back</button>
                                        <button class="btn bg-success-subtle text-success-emphasis border-0 px-4 py-2 rounded-3" id="confirmApproveBtn">Confirm Approval</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ReturnModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" data-application-uuid="">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border"
                style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title">Return Application for Revision</h5>
                        <small class="text-muted">Liza Bautista · 2021-00002 · BSIT 4th Year</small>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col-md-6">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm h-100"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase letter-spacing" style="font-size: 0.875rem;">Student Information</h6>
                                    <div class="vstack gap-3">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Name</span>
                                            <span class="text-body fw-semibold" id="stuNamem2c1"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Student No.</span>
                                            <span class="text-body fw-semibold" id="stuNumm2c1"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-medium">Program</span>
                                            <span class="text-body fw-semibold" id="stuProgm2c1"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm h-100"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <h6 class="card-title mb-3 fw-semibold text-uppercase letter-spacing" style="font-size: 0.875rem;">Applied To</h6>
                                    <div class="vstack gap-3">
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Company</span>
                                            <span class="text-body fw-semibold text-end" id="stuCompanym2c2"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center border-bottom pb-2">
                                            <span class="text-muted small fw-medium">Work Setup</span>
                                            <span class="text-body fw-semibold" id="stuWorkSetupm2c2"></span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="text-muted small fw-medium">Slots Remaining</span>
                                            <span class="badge bg-info-subtle text-info-emphasis fw-semibold" id="stuSlotsm2c2"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title mb-2 fw-semibold text-uppercase letter-spacing" style="font-size: 0.875rem;">Return for Revision</h6>
                                        <p class="text-muted small mb-0">The student will be notified to re-submit with updated information or a different company.</p>
                                    </div>
                                    <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                                        <small class="fw-medium"><i class="bi bi-exclamation-circle me-2"></i>The application status will change to <strong>Needs Revision</strong>. The student can then update and re-submit.</small>
                                    </div>
                                    <div class="mb-4">
                                        <label for="revisionReason" class="form-label fw-medium mb-2">Reason for Return <span class="text-danger">*</span></label>
                                        <textarea class="form-control shadow-none" id="revisionReason" rows="3"
                                            placeholder="Explain why the application needs revision..." style="resize: vertical;"></textarea>
                                        <small class="text-muted d-block mt-2">Be specific to help the student improve their application.</small>
                                    </div>
                                    <div class="d-flex gap-2 justify-content-end flex-wrap">
                                        <button class="btn bg-secondary-subtle text-secondary-emphasis border-0 px-4 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#ReviewModal">Back</button>
                                        <button class="btn btn-sm bg-warning-subtle text-warning-emphasis border-0 px-4 py-2 rounded-3 fw-medium" id="confirmReturnBtn">Return for Revision</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="RejectModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" data-application-uuid="">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border"
                style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0">
                    <div>
                        <h5 class="modal-title">Reject Application</h5>
                        <small class="text-muted">Liza Bautista · 2021-00002 · BSIT 4th Year</small>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        <div class="col-md-12">
                            <div class="card g-blur-5 bg-semi-transparent rounded-3 border shadow-sm"
                                style="--blur-lvl: <?= $opacitylvl ?>">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title mb-1 fw-semibold text-uppercase letter-spacing" style="font-size: 0.875rem;">Reject Application</h6>
                                        <p class="text-muted small mb-0 lh-sm">This action is final. The student will be notified and can apply to a different company.</p>
                                    </div>
                                    
                                    <div class="alert alert-danger alert-dismissible fade show mb-4 border-start border-danger" role="alert">
                                        <div class="d-flex gap-2">
                                            <i class="bi bi-exclamation-circle-fill"></i>
                                            <div>
                                                <small class="fw-semibold d-block mb-1">This will permanently reject the application.</small>
                                                <small class="fw-medium">The student can submit a new application to a different company for this batch.</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="revisionReason" class="form-label fw-semibold mb-2" style="font-size: 0.95rem;">Reason for Rejection <span class="text-danger">*</span></label>
                                        <textarea class="form-control shadow-none border-secondary-subtle" id="revisionReason" rows="4"
                                            placeholder="Explain why the application is being rejected..." style="resize: vertical; min-height: 110px;"></textarea>
                                        <small class="text-muted d-block mt-2">Be specific and constructive to help the student improve for future applications.</small>
                                    </div>
                                    
                                    <div class="d-flex gap-2 justify-content-end flex-wrap pt-2">
                                        <button class="btn btn-sm bg-secondary-subtle text-secondary-emphasis border-0 px-4 py-2 rounded-3 fw-medium transition-all" style="min-width: 100px;" data-bs-toggle="modal" data-bs-target="#ReviewModal">
                                            Back
                                        </button>
                                        <button class="btn btn-sm bg-danger-subtle text-danger-emphasis border-0 px-4 py-2 rounded-3 fw-semibold transition-all" 
                                            style="min-width: 100px;" id="confirmRejectBtn">
                                            Reject Application
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Applications</h4>
                        <p class="blockquote-footer pt-2 fs-6">
                            <span id="AYshoolyear"></span>
                        </p>
                    </div>
                </div>
                <div class="row g-4 mb-5">
                    <div class="col-12">
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-blur-5 bg-semi-transparent border text-muted"
                                id="searchIcon">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text"
                                class="form-control shadow-none bg-blur-5 bg-semi-transparent border fw-medium"
                                placeholder="Search applications..." aria-label="Search" aria-describedby="searchIcon"
                                id="applicationSearchInput">
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="d-flex flex-wrap gap-3 justify-content-start align-items-center">
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterAllBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterAllBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">All</span>
                            </button>
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterPendingBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterPendingBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">Pending</span>
                            </button>
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterNeedRevisionsBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterNeedRevisionsBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">Revisions</span>
                            </button>
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterApprovedBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterApprovedBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">Approved</span>
                            </button>
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterRejectedBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterRejectedBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">Rejected</span>
                            </button>
                            <button
                                class="btn btn-sm border-0 position-relative px-3 py-2 flex-grow-1 flex-md-grow-0 transition-all"
                                style="min-width: 90px; background-color: rgba(108, 117, 125, 0.15); color: inherit;"
                                id="filterWithdrawnBtn">
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                    id="filterWithdrawnBadge" style="font-size: 0.65rem;">
                                    0
                                </span>
                                <span class="d-block fs-6 fw-medium">Withdrawn</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="container d-d-flex flex-column gap-4">
                    <div class="row row-cols-1 row-cols-md-1 g-4" id="applicationsList">
                        <?php for ($i = 0; $i < 10; $i++) : ?>
                        <div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3 mb-4">
                                        <div class="avatar avatar-md rounded-circle bg-secondary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0"
                                            style="width: 56px; height: 56px;">
                                            <i class="bi bi-person fs-4 text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="card-title mb-0 fw-semibold text-truncate">John Doe</h5>
                                            <p class="card-text mb-0 text-muted">
                                                <span class="d-block"><span id="programAPP"
                                                        class="text-body fw-medium">BS Computer Science</span> - <span
                                                        id="yearLevelAPP" class="text-body fw-medium">3rd
                                                        Year</span></span>
                                                <span class="d-block mb-0">Applied for: <span
                                                        class="text-body fw-medium">Scholarship A</span></span>
                                            </p>
                                        </div>
                                        <div class="flex-shrink-0 ms-2">
                                            <span
                                                class="badge bg-secondary-subtle text-secondary-emphasis px-2 py-1 rounded-pill">Pending</span>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6 col-md-3">
                                            <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3">
                                                <p class="text-muted small mb-1 fw-medium">Application ID</p>
                                                <p class="mb-0 fw-semibold small">APP-20240615-001</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3">
                                                <p class="text-muted small mb-1 fw-medium">Work setup</p>
                                                <p class="mb-0 fw-semibold small">On-site</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3">
                                                <p class="text-muted small mb-1 fw-medium">Preferred Dept.</p>
                                                <p class="mb-0 fw-semibold small">IT Department</p>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3">
                                                <p class="text-muted small mb-1 fw-medium">Submitted</p>
                                                <p class="mb-0 fw-semibold small">2024-06-15 14:30</p>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-3">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button
                                            class="btn btn-sm bg-secondary-subtle text-body border flex-md-grow-0 px-3 py-2 rounded-3"
                                            data-bs-toggle="modal" data-bs-target="#ReviewModal">View Details</button>
                                        <button
                                            class="btn btn-sm bg-primary-subtle text-primary-emphasis border flex-md-grow-0 px-3 py-2 rounded-3">Generate
                                            Endorsement</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>