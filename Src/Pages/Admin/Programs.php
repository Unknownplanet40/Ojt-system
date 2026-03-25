<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Programs";

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../../libs/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../../../libs/aos/css/aos.css" />
    <link rel="stylesheet" href="../../../libs/driverjs/css/driver.css" />
    <link rel="stylesheet" href="../../../Assets/style/AniBG.css" />
    <link rel="stylesheet" href="../../../Assets/style/MainStyle.css" />
    <link rel="manifest" href="../../../Assets/manifest.json" />

    <script defer src="../../../libs/bootstrap/js/bootstrap.bundle.js"></script>
    <script defer src="../../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script defer src="../../../libs/aos/js/aos.js"></script>
    <script src="../../../libs/driverjs/js/driver.js.iife.js"></script>
    <script src="../../../libs/jquery/js/jquery-3.7.1.min.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/ProgramsScripts.js"></script>
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
    <div class="modal fade" id="disableProgramModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-disableprogram-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.7">
                <div class="modal-body p-4">
                    <span
                        class="bg-danger-subtle text-danger rounded-circle d-inline-flex justify-content-center align-items-center mb-2"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-x-octagon-fill text-danger-emphasis" style="font-size: 18px;"></i>
                    </span>
                    <p class="modal-title mb-2 fw-bold ps-2 pb-0">Disable this program?</p>
                    <p class="mb-0 text-muted ps-2">You are about to disable <strong id="programToDisableName">N/A</strong>. </p>
                    <span class="bg-danger text-danger-emphasis bg-opacity-25 rounded-3 d-inline-block mt-3 p-2"
                        style="font-size: 0.875rem;">
                        This will hide the program from student profile dropdowns and prevent it from being assigned to new students, but existing student records with this program will not be affected.
                    </span>
                    <small class="text-muted d-block mt-3" style="font-size: 0.875rem;">Type <strong
                            id="disableProgramNameConfirm">DISABLE</strong> in the box below to
                        confirm.</small>
                    <input type="text"
                        class="form-control mt-2 bg-blur-5 bg-semi-transparent border-0 shadow-sm text-white"
                        style="--blur-lvl: 0.5" placeholder="Type DISABLE to confirm" id="disableProgramInput">
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center">
                    <div class="hstack gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 flex-grow-1"
                            id="cancelDisableProgramBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-danger py-2 px-3 rounded-3 flex-grow-1"
                            id="confirmDisableProgramBtn" disabled>Yes, Disable Program</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="NewProgramModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.6">
                <div class="modal-body p-4">
                    <p class="modal-title fw-bold ps-2 pb-0 mb-0 fs-5">Add new program</p>
                    <small class="mb-0 text-muted ps-2 mt-0">Define a new course and its OJT hour requirement</small>

                    <div class="card bg-blur-5 bg-semi-transparent border border-muted shadow-sm mt-3"
                        style="--blur-lvl: 0.20">
                        <div class="card-body">
                            <p class="card-title fw-medium mb-0">Semester details</p>
                            <small class="text-muted mb-3">The program code appears in student profiles and reports.</small>
                            <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                <div class="col">
                                    <label for="programCodeInput" class="form-label" style="font-size: 0.875rem;">Program code <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="programCodeInput" placeholder="e.g. IT101" style="--blur-lvl: 0.5">
                                    <small class="text-muted ms-3" style="font-size: 0.75rem;">Short code shown in dropdowns and reports</small>
                                </div>
                                <div class="col">
                                    <label for="programNameInput" class="form-label" style="font-size: 0.875rem;">Program name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="programNameInput" placeholder="e.g. Bachelor of Science in Information Technology" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="programDepartmentInput" class="form-label" style="font-size: 0.875rem;">Department</label>
                                    <input type="text" class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="programDepartmentInput" placeholder="e.g. College of Computing" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="requiredHoursInput" class="form-label" style="font-size: 0.875rem;">Required OJT hours <span
                                            class="text-danger">*</span></label>
                                    <input type="number" min="0"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="requiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5">
                                    <small class="text-muted d-block" style="font-size: 0.70rem;">Overrides the batch default for students in this program</small>
                                </div>
                                <div class="col">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input shadow-none" type="checkbox"
                                            id="activateImmediatelySwitch">
                                        <label class="form-check-label" for="activateImmediatelySwitch"
                                            style="font-size: 0.875rem;">Active — visible in student profile dropdowns</label>
                                    </div>
                                </div>
                            </div>
                            <div class="hstack d-flex justify-content-end mt-4">
                                <button class="btn btn-sm btn-outline-dark border text-light py-2 px-3 rounded-3"
                                    data-bs-dismiss="modal" id="cancelNewProgramBtn">Cancel</button>
                                <button class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 ms-2"
                                    id="saveNewProgramBtn">Save Program</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="EditProgramModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-editprogram-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.6">
                <div class="modal-body p-4">
                    <p class="modal-title fw-bold ps-2 pb-0 mb-0 fs-5">Edit program details</p>
                    <small class="mb-0 text-muted ps-2 mt-0">Modify the details of this program. Changes will be reflected in all student records with this program.</small>

                    <div class="card bg-blur-5 bg-semi-transparent border border-muted shadow-sm mt-4"
                        style="--blur-lvl: 0.20">
                        <div class="card-body">
                            <p class="card-title fw-medium mb-0">Program details</p>
                            <small class="text-muted mb-3">The program code appears in student profiles and reports.</small>
                            <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                <div class="col">
                                    <label for="editProgramCodeInput" class="form-label" style="font-size: 0.875rem;">Program code <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="editProgramCodeInput" placeholder="e.g. IT101" style="--blur-lvl: 0.5">
                                    <small class="text-muted ms-3" style="font-size: 0.75rem;">Short code shown in dropdowns and reports</small>
                                </div>
                                <div class="col">
                                    <label for="editProgramNameInput" class="form-label" style="font-size: 0.875rem;">Program name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="editProgramNameInput" placeholder="e.g. Bachelor of Science in Information Technology" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="editProgramDepartmentInput" class="form-label" style="font-size: 0.875rem;">Department</label>
                                    <input type="text" class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="editProgramDepartmentInput" placeholder="e.g. College of Computing" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="editRequiredHoursInput" class="form-label" style="font-size: 0.875rem;">Required OJT hours <span
                                            class="text-danger">*</span></label>
                                    <input type="number" min="0"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="editRequiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5">
                                    <small class="text-muted d-block" style="font-size: 0.70rem;">Overrides the batch default for students in this program</small>
                                </div>
                                <div class="col">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input shadow-none" type="checkbox"
                                            id="editActivateImmediatelySwitch">
                                        <label class="form-check-label" for="editActivateImmediatelySwitch"
                                            style="font-size: 0.875rem;">Active — visible in student profile dropdowns</label>
                                    </div>
                                </div>
                            </div>
                            <div class="hstack d-flex justify-content-end mt-4">
                                <button class="btn btn-sm btn-outline-dark border text-light py-2 px-3 rounded-3"
                                    data-bs-dismiss="modal" id="cancelEditProgramBtn">Cancel</button>
                                <button class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 ms-2"
                                    id="saveEditProgramBtn">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="">Programs</h4>
                        <p class="blockquote-footer pt-2 fs-6">Manage courses and their OJT hour requirements.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addProgramBtn"
                        data-bs-toggle="modal" data-bs-target="#NewProgramModal">
                        <i class="bi bi-plus-lg me-1"></i>
                        add program
                    </button>
                </div>
                <div class="container bg-dark bg-opacity-25 rounded-3 border border-muted shadow-sm p-4 mb-4">
                    <div class="hstack">
                        <div>
                            <h5 class="">Active programs</h5>
                            <p class="blockquote-footer pt-2 fs-6">These programs appear in student profile dropdowns and override the batch default hours.</p>
                        </div>
                    </div>
                    <div class="row row-cols-1 row-cols-md-1 g-2 d-flex" id="programsContainer">
                        
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>