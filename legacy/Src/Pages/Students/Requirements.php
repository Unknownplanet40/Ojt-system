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

$CurrentPage = "Requirements";

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
    <script type="module" src="../../../Assets/Script/StudentsScripts/RequirementsScripts.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-role="<?= $_SESSION['user']['role'] ?>"
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
    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php"; ?>
            <div class="container p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Pre OJT Requirements <i class="bi bi-arrow-clockwise ms-2"
                                id="dashboardRefreshBtn" style="cursor: pointer;"></i></h4>
                        <p class="blockquote-footer pt-2 fs-6">
                            <?php echo date("F j, Y, g:i A"); ?>
                            |
                            <?= $greeting ?>, <strong
                                id="welcomeUserName"></strong>!
                            upload all documents before applying.
                        </p>
                    </div>
                </div>
                <div class="row row-cols-1 row-cols-md-1 g-2">
                    <div class="col-md-12">
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <div class="hstack">
                                    <p class="mb-0 align-self-center">Overall progress</p>
                                    <div class="progress flex-grow-1 mx-3 align-self-center" style="height: 7px;">
                                        <div class="progress-bar bg-success rounded-pill" role="progressbar"
                                            style="width: 0%;" id="overallProgressBar" aria-valuenow="0"
                                            aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span>
                                        <span class="" id="submittedCount">0</span> / <span id="totalCount">0</span>
                                        Approved
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="alert bg-info-subtle text-info-emphasis rounded-3 border py-2 px-3" role="alert">
                            You must complete all requirements before you can submit an OJT application. Accepted file
                            format: PDF only, max 5MB per file.
                        </div>
                    </div>
                    <div class="col-md-12" id="ResumeContainer" data-Requirement-uuid=""> <!-- Resume Container -->
                        <!-- only show if resume is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedResumeCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="ResumeLabelS">Unknown</p>
                                        <small class="text-muted" id="ResumeDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="ResumeStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="ResumeFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="ResumeSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewResumeBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ResumeStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="ResumeStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ResumeCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="ResumeCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadResumeBtnS">Upload / Update Resume</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedResumeCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="ResumeLabelNS">Unknown</p>
                                    <small class="text-muted" id="ResumeDescriptionNS">Unknown</small>
                                    <label for="ResumeFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadResumeAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your resume</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="ResumeFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedResumeInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedResumeFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedResumeBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedResumeBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="ResumeNoteInputNS" class="form-label mt-2">Additional notes for your resume (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="ResumeNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your resume here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelResumeBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitResumeBtnNS">Submit Document</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="PersonalAccidentInsuranceContainer" data-Requirement-uuid=""> <!-- PersonalAccidentInsurance Container -->
                        <!-- only show if PersonalAccidentInsurance is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedPersonalAccidentInsuranceCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="PersonalAccidentInsuranceLabelS">Unknown</p>
                                        <small class="text-muted" id="PersonalAccidentInsuranceDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="PersonalAccidentInsuranceStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="PersonalAccidentInsuranceFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="PersonalAccidentInsuranceSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewPersonalAccidentInsuranceBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="PersonalAccidentInsuranceStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="PersonalAccidentInsuranceStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="PersonalAccidentInsuranceCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="PersonalAccidentInsuranceCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadPersonalAccidentInsuranceBtnS">Upload / Update Personal Accident Insurance</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedPersonalAccidentInsuranceCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="PersonalAccidentInsuranceLabelNS">Unknown</p>
                                    <small class="text-muted" id="PersonalAccidentInsuranceDescriptionNS">Unknown</small>
                                    <label for="PersonalAccidentInsuranceFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadPersonalAccidentInsuranceAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your personal accident insurance</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="PersonalAccidentInsuranceFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedPersonalAccidentInsuranceInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedPersonalAccidentInsuranceFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedPersonalAccidentInsuranceBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedPersonalAccidentInsuranceBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="PersonalAccidentInsuranceNoteInputNS" class="form-label mt-2">Additional notes for your personal accident insurance (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="PersonalAccidentInsuranceNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your personal accident insurance here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelPersonalAccidentInsuranceBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitPersonalAccidentInsuranceBtnNS">Submit Document</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="ParentConsentContainer" data-Requirement-uuid=""> <!-- ParentConsent Container -->
                        <!-- only show if ParentConsent is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedParentConsentCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="ParentConsentLabelS">Unknown</p>
                                        <small class="text-muted" id="ParentConsentDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="ParentConsentStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="ParentConsentFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="ParentConsentSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewParentConsentBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ParentConsentStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="ParentConsentStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ParentConsentCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="ParentConsentCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadParentConsentBtnS">Upload / Update Parent Consent</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedParentConsentCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="ParentConsentLabelNS">Unknown</p>
                                    <small class="text-muted" id="ParentConsentDescriptionNS">Unknown</small>
                                    <label for="ParentConsentFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadParentConsentAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your parent consent</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="ParentConsentFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedParentConsentInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedParentConsentFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedParentConsentBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedParentConsentBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="ParentConsentNoteInputNS" class="form-label mt-2">Additional notes for your parent consent (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="ParentConsentNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your parent consent here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelParentConsentBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitParentConsentBtnNS">Submit Document</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="ParentalGuardianInfoContainer" data-Requirement-uuid=""> <!-- ParentalGuardianInfo Container -->
                        <!-- only show if ParentalGuardianInfo is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedParentalGuardianInfoCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="ParentalGuardianInfoLabelS">Unknown</p>
                                        <small class="text-muted" id="ParentalGuardianInfoDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="ParentalGuardianInfoStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="ParentalGuardianInfoFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="ParentalGuardianInfoSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewParentalGuardianInfoBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ParentalGuardianInfoStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="ParentalGuardianInfoStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="ParentalGuardianInfoCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="ParentalGuardianInfoCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadParentalGuardianInfoBtnS">Upload / Update Parental Guardian Info</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedParentalGuardianInfoCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="ParentalGuardianInfoLabelNS">Unknown</p>
                                    <small class="text-muted" id="ParentalGuardianInfoDescriptionNS">Unknown</small>
                                    <label for="ParentalGuardianInfoFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadParentalGuardianInfoAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your parental guardian info</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="ParentalGuardianInfoFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedParentalGuardianInfoInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedParentalGuardianInfoFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedParentalGuardianInfoBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedParentalGuardianInfoBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="ParentalGuardianInfoNoteInputNS" class="form-label mt-2">Additional notes for your parental guardian info (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="ParentalGuardianInfoNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your parental guardian info here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelParentalGuardianInfoBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitParentalGuardianInfoBtnNS">Submit Document</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="MedCertContainer" data-Requirement-uuid=""> <!-- MedCert Container -->
                        <!-- only show if MedCert is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedMedCertCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="MedCertLabelS">Unknown</p>
                                        <small class="text-muted" id="MedCertDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="MedCertStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="MedCertFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="MedCertSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewMedCertBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="MedCertStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="MedCertStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="MedCertCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="MedCertCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadMedCertBtnS">Upload / Update Medical Certificate</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedMedCertCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="MedCertLabelNS">Unknown</p>
                                    <small class="text-muted" id="MedCertDescriptionNS">Unknown</small>
                                    <label for="MedCertFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadMedCertAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your medical certificate</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="MedCertFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedMedCertInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedMedCertFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedMedCertBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedMedCertBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="MedCertNoteInputNS" class="form-label mt-2">Additional notes for your medical certificate (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="MedCertNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your medical certificate here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelMedCertBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitMedCertBtnNS">Submit Document</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12" id="NbiClearanceContainer" data-Requirement-uuid=""> <!-- NbiClearance Container -->
                        <!-- only show if NbiClearance is submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent rounded-0 rounded-end-3 border-0 border-danger border-start border-5"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="SubmittedNbiClearanceCard">
                            <div class="card-body">
                                <div class="hstack">
                                    <div>
                                        <p class="card-title mb-0" id="NbiClearanceLabelS">Unknown</p>
                                        <small class="text-muted" id="NbiClearanceDescriptionS">Unknown</small>
                                    </div>
                                    <div class="ms-auto">
                                        <span class="badge rounded-pill bg-danger-subtle text-danger-emphasis"
                                            id="NbiClearanceStatusS">Unknown</span>
                                    </div>
                                </div>
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-pdf me-3"></i>
                                        <div>
                                            <h6 class="mb-0" id="NbiClearanceFileNameS">Unknown</h6>
                                            <small class="text-muted" style="font-size: 0.75rem;">Submitted on: <span
                                                    id="NbiClearanceSubmittedDateS">N/A</span></small>
                                        </div>
                                        <div class="ms-auto">
                                            <button class="btn btn-sm border-0 text-success"
                                                id="viewNbiClearanceBtnS">View</button>
                                        </div>
                                    </div>
                                </div>
                                <!-- Student Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="NbiClearanceStudentNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Your Note's</h6>
                                            <p class="mb-0" id="NbiClearanceStudentNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Coordinator Note -->
                                <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent"
                                    role="alert"
                                    style="--blur-lvl: <?= $opacitylvl ?>" id="NbiClearanceCoordinatorNoteS">
                                    <div class="hstack">
                                        <i class="bi bi-person-badge me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Coordinator's Note's</h6>
                                            <p class="mb-0" id="NbiClearanceCoordinatorNoteContentS">N/A</p>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-dark border text-light"
                                    id="uploadNbiClearanceBtnS">Upload / Update NBI Clearance</button>
                            </div>
                        </div>
                        <!-- Show this if resume is not submitted -->
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg p-1 rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>"
                            id="NotSubmittedNbiClearanceCard">
                            <div class="card-body">
                                <div class="vstack">
                                    <p class="card-title mb-0" id="NbiClearanceLabelNS">Unknown</p>
                                    <small class="text-muted" id="NbiClearanceDescriptionNS">Unknown</small>
                                    <label for="NbiClearanceFileInputNS"
                                        class="card bg-blur-5 bg-semi-transparent rounded-3 my-2 border border-success border-2"
                                        style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                        id="uploadNbiClearanceAreaNS">
                                        <div class="card-body d-flex flex-column align-items-center">
                                            <i class="bi bi-file-earmark-pdf text-muted fs-4"></i>
                                            <p class="card-text text-muted mb-0">Click to upload your NBI clearance</p>
                                            <small class="text-muted">PDF files only &bullet; Max size: 5MB</small>
                                        </div>
                                    </label>
                                    <input type="file" id="NbiClearanceFileInputNS" accept=".pdf" class="d-none">
                                    <!-- display this if file is selected -->
                                    <div class="alert bg-secondary-subtle text-secondary-emphasis rounded-3 border py-2 px-3 mt-2 bg-blur-5 bg-semi-transparent d-none"
                                        role="alert"
                                        style="--blur-lvl: <?= $opacitylvl ?>"
                                        id="selectedNbiClearanceInfoNS">
                                        <div class="hstack">
                                            <i class="bi bi-file-earmark-pdf me-3"></i>
                                            <div>
                                                <h6 class="mb-0" id="selectedNbiClearanceFileNameNS">Unknown</h6>
                                            </div>
                                            <div class="ms-auto">
                                                <button class="btn btn-sm border-0 text-success" id="viewSelectedNbiClearanceBtnNS">View</button>
                                                <button class="btn btn-sm border-0 text-danger" id="removeSelectedNbiClearanceBtnNS">Remove</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <label for="NbiClearanceNoteInputNS" class="form-label mt-2">Additional notes for your NBI clearance (optional)</label>
                                <textarea class="form-control bg-blur-5 bg-semi-transparent border-1 shadow-none mb-1" id="NbiClearanceNoteInputNS" rows="3" style="--blur-lvl: <?= $opacitylvl ?>" placeholder="Add any notes or comments about your NBI clearance here..."></textarea>
                                <div class="hstac">
                                    <button class="btn btn-sm btn-outline-dark border text-light d-none" id="CancelNbiClearanceBtnNS">Cancel</button>
                                    <button class="btn btn-sm btn-outline-dark border text-light" id="submitNbiClearanceBtnNS">Submit Document</button>
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