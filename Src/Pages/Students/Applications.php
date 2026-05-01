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
    <script type="module" src="../../../Assets/Script/dashboardScripts/StudentDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/StudentsScripts/ApplicationsScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
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
    <div class="modal fade" id="IncompleteRequirementsModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-0">
                    <div class="d-flex flex-column text-center p-3 p-sm-5 p-lg-5">
                        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning shadow-sm"
                            style="width: 64px; height: 64px;">
                            <i class="bi bi-exclamation-octagon fs-3"></i>
                        </div>
                        <h5 class="mb-2 fw-bold text-body">Requirements Incomplete</h5>
                        <p class="mb-4 text-muted small px-2 px-sm-4 lh-base">
                            You must have all pre-OJT requirements approved before you can submit an application.
                        </p>
                        <div class="mt-2 w-100 p-2 p-md-3 bg-blur-5 bg-semi-transparent border rounded-3 text-start shadow-sm"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="row row-cols-1 row-cols-md-2 g-2">
                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi text-warning fs-6 flex-shrink-0" id="resumeIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">Resume / CV</span>
                                        <span
                                            class="badge rounded-pill bg-warning-subtle text-warning flex-shrink-0 text-nowrap"
                                            id="resumeStatus">Submitted</span>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi text-warning fs-6 flex-shrink-0" id="paiIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">Personal Accident
                                            Insurance</span>
                                        <span
                                            class="badge rounded-pill bg-warning-subtle text-warning flex-shrink-0 text-nowrap"
                                            id="paiStatus">Submitted</span>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi text-danger fs-6 flex-shrink-0" id="waiverIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">Parental consent /
                                            Waiver</span>
                                        <span
                                            class="badge rounded-pill bg-danger-subtle text-danger flex-shrink-0 text-nowrap"
                                            id="waiverStatus">Not submitted</span>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi text-warning fs-6 flex-shrink-0" id="guardianInfoIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">Parent / Guardian
                                            Information</span>
                                        <span
                                            class="badge rounded-pill bg-warning-subtle text-warning flex-shrink-0 text-nowrap"
                                            id="guardianInfoStatus">Submitted</span>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi bi-check-circle-fill text-success fs-6 flex-shrink-0"
                                            id="medicalCertIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">Medical certificate</span>
                                        <span
                                            class="badge rounded-pill bg-success-subtle text-success flex-shrink-0 text-nowrap"
                                            id="medicalCertStatus">Approved</span>
                                    </div>
                                </div>

                                <div class="col">
                                    <div class="d-flex align-items-center gap-2 p-2 border rounded-3 h-100">
                                        <i class="bi text-warning fs-6 flex-shrink-0" id="nbiIcon"></i>
                                        <span class="text-body small fw-medium flex-grow-1">NBI clearance</span>
                                        <span
                                            class="badge rounded-pill bg-warning-subtle text-warning flex-shrink-0 text-nowrap"
                                            id="nbiStatus">Submitted</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="../Students/Requirements"
                            class="btn bg-secondary-subtle text-body border rounded-3 px-5 py-2 mt-3 align-self-center shadow-sm fw-medium text-nowrap">
                            View requirements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ApplicationSubmittedModal" data-bs-backdrop="static" data-bs-keyboard="false"
        tabindex="-1" aria-labelledby="staticBackdropLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-0">
                    <div class="d-flex flex-column text-center p-3 p-sm-5 p-lg-5">
                        <div class="mx-auto mb-4 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success shadow-sm"
                            style="width: 64px; height: 64px;">
                            <i class="bi bi-check-circle-fill fs-3"></i>
                        </div>
                        <h5 class="mb-2 fw-bold text-body">Application Submitted!</h5>
                        <p class="mb-4 text-muted small px-2 px-sm-4 lh-base">
                            Your application to Accenture Philippines, Inc. has been submitted. Your coordinator will
                            review it and you'll be notified of the decision.
                        </p>
                        <div class="mt-2 w-100 p-2 p-sm-3 bg-blur-5 bg-semi-transparent border rounded-3 shadow-sm"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="d-flex align-items-start gap-2">
                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success"
                                    style="width: 36px; height: 36px;">
                                    <i class="bi bi-info-circle fs-6"></i>
                                </div>

                                <div class="flex-grow-1 text-start">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <small class="text-uppercase text-muted fw-semibold"
                                            style="letter-spacing: .04em;">
                                            What happens next?
                                        </small>
                                        <span class="badge rounded-pill bg-secondary-subtle text-body border">
                                            Application process
                                        </span>
                                    </div>

                                    <p class="mb-2 text-muted small lh-sm">
                                        After coordinator review, you’ll be guided through the next stage.
                                    </p>

                                    <div class="row g-2">
                                        <div class="col-12 col-sm-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold"
                                                    style="width: 24px; height: 24px; font-size: .78rem;">1</div>
                                                <div>
                                                    <div class="fw-semibold text-body small mb-0">Coordinator review
                                                    </div>
                                                    <div class="text-muted"
                                                        style="font-size: .78rem; line-height: 1.2;">Check completeness
                                                        and eligibility.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold"
                                                    style="width: 24px; height: 24px; font-size: .78rem;">2</div>
                                                <div>
                                                    <div class="fw-semibold text-body small mb-0">Endorsement letter
                                                    </div>
                                                    <div class="text-muted"
                                                        style="font-size: .78rem; line-height: 1.2;">Prepared if your
                                                        application is approved.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold"
                                                    style="width: 24px; height: 24px; font-size: .78rem;">3</div>
                                                <div>
                                                    <div class="fw-semibold text-body small mb-0">Company submission
                                                    </div>
                                                    <div class="text-muted"
                                                        style="font-size: .78rem; line-height: 1.2;">Download and submit
                                                        to your host company.</div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-sm-6">
                                            <div class="d-flex align-items-start gap-2">
                                                <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary fw-semibold"
                                                    style="width: 24px; height: 24px; font-size: .78rem;">4</div>
                                                <div>
                                                    <div class="fw-semibold text-body small mb-0">Start date
                                                        confirmation</div>
                                                    <div class="text-muted"
                                                        style="font-size: .78rem; line-height: 1.2;">Your official OJT
                                                        date will be confirmed.</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- CTA Button -->
                        <button type="button" data-bs-dismiss="modal"
                            class="btn bg-secondary-subtle text-body border rounded-3 px-5 py-2 mt-3 align-self-center shadow-sm fw-medium text-nowrap">
                            View application status
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ApplyFormsModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4">
                    <div class="vstack">
                        <h5 class="mb-0">Apply for OJT</h5>
                        <p class="text-muted"><span id="currentSemesterText">AY 2021 - 2022</span> <span
                                id="currentAcademicYearText">1st Semester</span> · Select a company to apply to</p>
                        <div class="mb-4">
                            <div class="d-flex flex-column gap-3 mx-3">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex flex-column align-items-center gap-2" style="flex: 0 0 auto;">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle text-bg-success fw-bold shadow-sm"
                                            style="width: 40px; height: 40px; font-size: 1rem;">1</div>
                                        <small class="text-muted text-center fw-medium"
                                            style="white-space: nowrap;">Select Company</small>
                                    </div>
                                    <div class="flex-grow-1 mx-2 mx-sm-3" style="height: 2px;">
                                        <div class="progress bg-secondary-subtle" style="height: 100%;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%;"
                                                id="step1ProgressBar" aria-valuenow="0" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-center gap-2" style="flex: 0 0 auto;">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary fw-bold shadow-sm"
                                            style="width: 40px; height: 40px; font-size: 1rem;" id="step2Indicator">2
                                        </div>
                                        <small class="text-muted text-center fw-medium" style="white-space: nowrap;">Add
                                            Details</small>
                                    </div>
                                    <div class="flex-grow-1 mx-2 mx-sm-3" style="height: 2px;">
                                        <div class="progress bg-secondary-subtle" style="height: 100%;">
                                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%;"
                                                id="step2ProgressBar" aria-valuenow="0" aria-valuemin="0"
                                                aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column align-items-center gap-2" style="flex: 0 0 auto;">
                                        <div class="d-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary fw-bold shadow-sm"
                                            style="width: 40px; height: 40px; font-size: 1rem;" id="step3Indicator">3
                                        </div>
                                        <small class="text-muted text-center fw-medium"
                                            style="white-space: nowrap;">Confirm</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="step-1">
                        <div class="row row-cols-1 g-3 pe-1" id="companyList"
                            style="max-height: 240px; overflow-y: auto;">
                            <?php for ($i = 0; $i < 5; $i++) : ?>
                            <div class="col-12">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 h-100 comcard <?= ($i === 0) ? 'selected-card' : '' ?>"
                                    style="--blur-lvl: <?= $opacitylvl ?>; cursor: pointer;"
                                    data-company-id="<?= $i ?>">
                                    <div class="card-body p-3 p-sm-4">
                                        <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                            <div class="flex-grow-1 min-w-0">
                                                <h5 class="mb-1 fw-semibold text-body text-break">Accenture Philippines,
                                                    Inc.</h5>
                                                <p class="mb-0 text-muted small">IT Services &amp; Consulting &middot;
                                                    Taguig</p>
                                            </div>
                                            <div class="ms-md-auto">
                                                <span
                                                    class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-medium text-nowrap">
                                                    7 slots left
                                                </span>
                                            </div>
                                        </div>

                                        <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                            <div class="d-flex flex-wrap gap-2">
                                                <small
                                                    class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">BSIT</small>
                                                <small
                                                    class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">BSCS</small>
                                                <small
                                                    class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">BSIS</small>
                                                <small
                                                    class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">BSSE</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <div class="hstack gap-2 mt-3">
                            <button type="button"
                                class="ms-auto btn bg-secondary-subtle text-body border rounded-3 px-4 py-2 shadow-sm"
                                id="cancelApplicationBtn" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="proceedToDetailsBtn"
                                class="btn bg-primary-subtle text-body border rounded-3 px-4 py-2 shadow-sm ">
                                Next - Add details <i class="bi bi-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    <div id="step-2" class="d-none" data-selected-company="">
                        <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 my-3 overflow-hidden"
                            style="--blur-lvl: <?= $opacitylvl ?>; cursor: pointer;"
                            data-company-id="<?= $i ?>">
                            <div class="card-body p-3 p-sm-4 p-lg-4">
                                <div class="d-flex flex-column flex-sm-row align-items-start gap-3 gap-sm-4">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0"
                                        style="width: 44px; height: 44px;">
                                        <i class="bi bi-building"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <small class="text-uppercase text-muted fw-semibold d-block mb-1"
                                            style="letter-spacing: .06em;">Selected company</small>
                                        <h5 class="mb-1 fw-semibold text-body text-break" id="selectedCompanyName">
                                            Accenture Philippines, Inc. </h5>
                                        <p class="mb-0 text-muted small"><span id="industryInfo"></span> &middot; <span
                                                id="locationInfo" class="text-capitalize"></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 overflow-hidden"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-sm-4 p-lg-4">
                                <div
                                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-3 mb-md-4">
                                    <div>
                                        <h5 class="mb-1">Application details</h5>
                                        <p class="text-muted mb-0 small">Provide additional information for your
                                            application.</p>
                                    </div>
                                    <span
                                        class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-2 fw-medium">
                                        Step 2 of 3
                                    </span>
                                </div>

                                <div class="row g-3 g-md-4">
                                    <div class="col-12">
                                        <label for="preferredDepartment" class="form-label fw-medium mb-2">
                                            Preferred department / area
                                        </label>
                                        <input type="text" class="form-control py-2" id="preferredDepartment"
                                            placeholder="E.g. Software Development, IT Support, etc.">
                                        <small class="text-muted d-block mt-2">
                                            Optional — helps the coordinator when processing your endorsement
                                        </small>
                                    </div>

                                    <div class="col-12">
                                        <label for="coverLetter" class="form-label fw-medium mb-2">
                                            Message to coordinator
                                        </label>
                                        <textarea class="form-control" id="coverLetter" rows="5"
                                            placeholder="Introduce yourself and mention any preferences or relevant information you'd like the coordinator to know when processing your application."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hstack gap-2 mt-3">
                            <button type="button"
                                class="ms-auto btn bg-secondary-subtle text-body border rounded-3 px-4 py-2 shadow-sm"
                                id="backToCompanySelectionBtn">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button"
                                class="btn bg-primary-subtle text-body border rounded-3 px-4 py-2 shadow-sm "
                                id="submitApplicationBtn">
                                <i class="bi bi-send"></i> Submit application
                            </button>
                        </div>
                    </div>
                    <div id="step-3" class="d-none">
                        <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 my-3 overflow-hidden"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-sm-4 p-lg-4">
                                <div
                                    class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-3 mb-md-4">
                                    <div>
                                        <h5 class="mb-1">Review your application</h5>
                                        <p class="text-muted mb-0 small">Please verify all details before submission.
                                        </p>
                                    </div>
                                    <span
                                        class="badge rounded-pill bg-warning-subtle text-warning border px-3 py-2 fw-medium">
                                        Step 3 of 3
                                    </span>
                                </div>

                                <div class="vstack gap-3">
                                    <div class="p-3 border rounded-4 bg-body bg-opacity-50">
                                        <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                            style="letter-spacing: .06em;">
                                            Selected company
                                        </small>
                                        <h6 class="mb-1 fw-semibold text-body" id="confirmCompanyName">Company name</h6>
                                        <p class="mb-0 text-muted small" id="confirmCompanyMeta">Industry · Location</p>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 border rounded-4 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Preferred department / area
                                                </small>
                                                <div class="text-body fw-medium" id="confirmPreferredDepartment">—</div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-6">
                                            <div class="p-3 border rounded-4 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Application status
                                                </small>
                                                <div class="text-body fw-medium">Ready to submit</div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="p-3 border rounded-4">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Message to coordinator
                                                </small>
                                                <div class="text-body small lh-base" id="confirmCoverLetter">
                                                    No message provided.
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-warning-subtle border-warning-subtle text-body mb-0">
                                        Review everything carefully. Once submitted, this application will be sent for
                                        coordinator review.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hstack gap-2 mt-3">
                            <button type="button"
                                class="ms-auto btn bg-secondary-subtle text-body border rounded-3 px-4 py-2 shadow-sm"
                                id="backToDetailsBtn">
                                <i class="bi bi-arrow-left"></i> Back
                            </button>
                            <button type="button"
                                class="btn btn-success text-white border rounded-3 px-4 py-2 shadow-sm"
                                id="finalSubmitApplicationBtn">
                                <i class="bi bi-check2-circle"></i> Confirm & submit
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row g-3 mb-2 align-items-stretch">
                    <div class="col-12 col-lg-12">
                        <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body py-3 p-md-4">
                                <div
                                    class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3">
                                    <img src="https://placehold.co/40x40?text=No+Photo" alt="Profile"
                                        id="DashboardProfilePhotoS" class="rounded-circle mx-3"
                                        style="width: 40px; height: 40px; object-fit: cover" />
                                    <div class="flex-grow-1 min-w-0">
                                        <h4 class="mb-1 fw-semibold text-break">
                                            <?= $greeting ?>,
                                            <strong id="welcomeUserName"></strong>
                                        </h4>
                                        <p class="mb-0 text-muted small d-flex flex-wrap align-items-center gap-2">
                                            <span><?= date("l, F j, Y") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><?= date("h:i A") ?></span>
                                            <span class="d-none d-sm-inline">&bull;</span>
                                            <span><span id="currentyearSem"></span> - <span
                                                    id="currentSchoolYear"></span></span>
                                        </p>
                                    </div>

                                    <div class="ms-sm-auto d-flex align-items-center gap-2">
                                        <button type="button"
                                            class="btn btn-outline-success btn-sm rounded-pill d-inline-flex align-items-center gap-1 px-3 py-2 shadow-sm"
                                            id="dashboardRefreshBtn" aria-label="Refresh dashboard">
                                            <i class="bi bi-arrow-clockwise"></i>
                                            <span class="fw-medium">Refresh</span>
                                        </button>
                                        <button type="button"
                                            class="btn bg-primary-subtle text-body border rounded-pill px-4 py-2 shadow-sm"
                                            id="applyNowBtn" aria-label="Apply now" data-bs-toggle="modal"
                                            data-bs-target="#ApplyFormsModal">
                                            <i class="bi bi-send"></i>
                                            <span class="fw-medium">Apply now</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-2 g-4">
                    <div class="col-md-12 d-none" id="noApplicationsContainer">
                        <div class="card border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent overflow-hidden h-100"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body py-4 py-md-5 px-3 px-sm-4 px-lg-5">
                                <div class="mx-auto text-center" style="max-width: 760px;">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary shadow-sm mb-3 mb-md-4"
                                        style="width: 72px; height: 72px;">
                                        <i class="bi bi-briefcase fs-3"></i>
                                    </div>

                                    <h4 class="fw-semibold mb-2 text-body">
                                        No Open Applications Available
                                    </h4>

                                    <p class="text-muted mb-4 mx-auto px-5">
                                        There are currently no active OJT application windows for <span
                                            id="currentSemesterTextss"></span> <span
                                            id="currentAcademicYearTextss"></span>.
                                        If your requirements are still incomplete, please finish them first. Once
                                        the
                                        application window opens and your requirements are approved, you will be
                                        able to
                                        see your application status here after submission.
                                    </p>

                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        <button type="button" onclick="location.href='../Students/Requirements'"
                                            class="btn bg-primary-subtle text-body border rounded-3 px-4 py-2 shadow-sm text-nowrap">
                                            Complete requirements
                                        </button>
                                        <button type="button"
                                            onclick="location.href='../Students/StudentsDashboard.php'"
                                            class="btn bg-body-tertiary text-body border-dark-subtle rounded-3 px-4 py-2 text-nowrap">
                                            Check back later
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 d-none" id="requirementsIncompleteContainer">
                        <div class="card border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent overflow-hidden h-100"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body py-4 py-md-5 px-3 px-sm-4 px-lg-5">
                                <div class="mx-auto text-center" style="max-width: 760px;">
                                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-warning shadow-sm mb-3 mb-md-4"
                                        style="width: 72px; height: 72px;">
                                        <i class="bi bi-shield-lock fs-3"></i>
                                    </div>

                                    <h4 class="fw-semibold mb-2 text-body">
                                        Application Locked
                                    </h4>

                                    <p class="text-muted mb-4 mx-auto px-5">
                                        You must complete and have all 6 pre-OJT requirements approved by your coordinator before you can view available companies and submit an application.
                                    </p>

                                    <div class="d-flex flex-wrap justify-content-center gap-2">
                                        <a href="Requirements.php"
                                            class="btn bg-primary-subtle text-body border rounded-3 px-4 py-2 shadow-sm text-nowrap">
                                            <i class="bi bi-file-earmark-text me-2"></i> View My Requirements
                                        </a>
                                        <button type="button"
                                            class="btn bg-body-tertiary text-body border-dark-subtle rounded-3 px-4 py-2 text-nowrap"
                                            id="viewReqStatusBtn">
                                            <i class="bi bi-info-circle me-2"></i> Check Status
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                        <div class="col-12 d-none" id="applicationStatusContainer">
                            <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent overflow-hidden"
                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                <div class="card-body p-3 p-sm-4 p-lg-5">
                                    <div
                                        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-4">
                                        <div>
                                            <h5 class="fw-semibold mb-1 text-body">Application status</h5>
                                            <p class="mb-0 text-muted small">Track your application progress in order.
                                            </p>
                                        </div>
                                        <span
                                            class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-2 fw-medium text-nowrap">
                                            Latest updates
                                        </span>
                                    </div>
                                    <div class="position-relative mt-3">
                                        <div id="applicationStatusTimeline" class="d-flex flex-column gap-4">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-none" id="applicationDetailsContainer">
                            <div class="card h-100 border shadow-sm rounded-4 bg-blur-5 bg-semi-transparent overflow-hidden"
                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                <div class="card-body p-3 p-sm-4 p-lg-5">
                                    <div
                                        class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
                                        <div>
                                            <h5 class="fw-semibold mb-1 text-body">Application details</h5>
                                            <p class="mb-0 text-muted small">Review the information you submitted.</p>
                                        </div>
                                        <span
                                            class="badge rounded-pill bg-secondary-subtle text-secondary border px-3 py-2 fw-medium text-nowrap">
                                            Submitted application
                                        </span>
                                    </div>
                                    <hr class="my-4">
                                    <div class="row g-3 g-lg-4">
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Company
                                                </small>
                                                <p class="mb-0 text-body fw-medium text-break" id="detailCompanyName"></p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Industry
                                                </small>
                                                <p class="mb-0 text-body fw-medium" id="detailIndustry"></p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Location
                                                </small>
                                                <p class="mb-0 text-body fw-medium" id="detailLocation"></p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Work arrangement
                                                </small>
                                                <p class="mb-0 text-body fw-medium" id="detailWorkArrangement"></p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Department preference
                                                </small>
                                                <p class="mb-0 text-body fw-medium" id="detailDepartmentPreference"></p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50 h-100">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Submitted
                                                </small>
                                                <p class="mb-0 text-body fw-medium" id="detailSubmitted"></p>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="p-3 p-sm-4 border rounded-4 bg-body bg-opacity-50">
                                                <small class="text-uppercase text-muted fw-semibold d-block mb-2"
                                                    style="letter-spacing: .06em;">
                                                    Application status
                                                </small>

                                                <div class="d-flex flex-column gap-3">
                                                    <div id="statusStepper" class="d-flex flex-row gap-2 gap-sm-3 overflow-x-auto overflow-y-hidden pb-2" style="flex-wrap:nowrap;align-items:flex-start;"></div>
                                                    <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2">
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div id="statusIconWrap" class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0" style="width:36px;height:36px;">
                                                                <span id="statusIcon"></span>
                                                            </div>
                                                            <div>
                                                                <p class="mb-0 text-body fw-semibold" id="statusText"></p>
                                                                <small class="text-muted" id="statusLastUpdated"></small>
                                                            </div>
                                                        </div>

                                                        <span class="badge rounded-pill px-3 py-2 fw-medium text-nowrap" id="currentStatusBadge"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <hr class="my-4">
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        <button type="button"
                                            class="btn btn-outline-primary rounded-3 px-4 py-2 shadow-sm d-none"
                                            id="downloadEndorsementBtn">
                                            <i class="bi bi-download me-1"></i> Download endorsement
                                        </button>
                                        <button type="button"
                                            class="btn btn-outline-warning rounded-3 px-4 py-2 shadow-sm d-none"
                                            id="resubmitApplicationBtn">
                                            <i class="bi bi-pencil-square me-1"></i> Edit & Resubmit
                                        </button>
                                        <button type="button"
                                            class="btn btn-outline-danger rounded-3 px-4 py-2 shadow-sm d-none"
                                            id="withdrawApplicationBtn">
                                            <i class="bi bi-x-lg me-1"></i> Withdraw application
                                        </button>
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