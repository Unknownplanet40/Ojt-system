<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Programs";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/ProgramsScripts.js"></script>
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
    <div class="modal fade" id="disableProgramModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-disableprogram-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.7">
                <div class="modal-body p-4 p-md-5">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="bg-danger-subtle rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-exclamation-triangle-fill text-danger" style="font-size: 28px;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-2">Disable this program?</h5>
                        <p class="text-muted mb-0 lh-sm">You are about to disable <strong class="text-white" id="programToDisableName">N/A</strong></p>
                    </div>

                    <div class="alert alert-danger border-0 rounded-3 py-3 px-3 mb-4" role="alert" style="font-size: 0.9375rem; line-height: 1.5;">
                        <i class="bi bi-info-circle me-2"></i>
                        This will hide the program from student dropdowns and prevent new assignments, but existing student records remain unaffected.
                    </div>

                    <div class="mb-4">
                        <label for="disableProgramInput" class="form-label fw-medium mb-2" style="font-size: 0.9375rem;">Confirmation required</label>
                        <p class="text-muted mb-3" style="font-size: 0.875rem;">Type <strong id="disableProgramNameConfirm">DISABLE</strong> to confirm this action.</p>
                        <input type="text" class="form-control form-control-lg bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle shadow-sm text-white rounded-3"
                            style="--blur-lvl: 0.5; font-size: 0.9375rem;" placeholder="Type DISABLE to confirm" id="disableProgramInput">
                    </div>
                </div>

                <div class="modal-footer border-0 bg-body-tertiary bg-opacity-10 d-flex justify-content-end gap-2 p-3 p-md-4 rounded-bottom">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-2 px-4 rounded-3" id="cancelDisableProgramBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger py-2 px-4 rounded-3" id="confirmDisableProgramBtn" disabled>Disable Program</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="enableProgramModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-enableprogram-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.7">
                <div class="modal-body p-4 p-md-5">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div class="bg-success-subtle rounded-circle d-inline-flex justify-content-center align-items-center mb-3" style="width: 56px; height: 56px;">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 28px;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-2">Enable this program?</h5>
                        <p class="text-muted mb-0 lh-sm">You are about to enable <strong class="text-white" id="programToEnableName">N/A</strong></p>
                    </div>

                    <div class="alert alert-success border-0 rounded-3 py-3 px-3 mb-4" role="alert" style="font-size: 0.9375rem; line-height: 1.5;">
                        <i class="bi bi-info-circle me-2"></i>
                        This will make the program visible in student dropdowns and allow new assignments to be created.
                    </div>

                    <div class="mb-4">
                        <label for="enableProgramInput" class="form-label fw-medium mb-2" style="font-size: 0.9375rem;">Confirmation required</label>
                        <p class="text-muted mb-3" style="font-size: 0.875rem;">Type <strong id="enableProgramNameConfirm">ENABLE</strong> to confirm this action.</p>
                        <input type="text" class="form-control form-control-lg bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle shadow-sm text-white rounded-3"
                            style="--blur-lvl: 0.5; font-size: 0.9375rem;" placeholder="Type ENABLE to confirm" id="enableProgramInput">
                    </div>
                </div>

                <div class="modal-footer border-0 bg-body-tertiary bg-opacity-10 d-flex justify-content-end gap-2 p-3 p-md-4 rounded-bottom">
                    <button type="button" class="btn btn-sm btn-outline-secondary py-2 px-4 rounded-3" id="cancelEnableProgramBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success py-2 px-4 rounded-3" id="confirmEnableProgramBtn" disabled>Enable Program</button>
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
                                        id="requiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5" value="486">
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
                    <button class="btn btn-sm btn-outline-secondary ms-2 text-nowrap" id="toggleInactiveProgramsBtn" data-show-inactive="false">
                        <i class="bi bi-eye-slash me-1"></i>
                        <span>Hide inactive</span>
                    </button>
                </div>
                <div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="input-group input-group-sm w-100 w-sm-auto" style="max-width: 240px;">
                            <span class="input-group-text bg-blur-5 bg-semi-transparent border-secondary-subtle text-muted" style="--blur-lvl: 0.3;">
                                <i class="bi bi-search"></i>
                            </span>
                            <input type="text" class="form-control bg-blur-5 bg-semi-transparent shadow-none border-secondary-subtle text-white" id="programSearchInput" placeholder="Search programs..." style="--blur-lvl: 0.3;">
                        </div>
                        <select class="form-select form-select-sm bg-blur-5 bg-semi-transparent border-secondary-subtle text-white w-100 w-sm-auto" id="departmentFilterSelect" style="--blur-lvl: 0.3; max-width: 200px;">
                            <option value="" selected class="CustomOption">All departments</option>
                        </select>
                    </div>
                </div>
                <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mt-3" style="--blur-lvl: 0.2">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="programsTable">
                                <thead>
                                    <tr class="text-muted border-bottom border-secondary-subtle">
                                        <th scope="col" class="ps-4 py-3 fw-bold text-uppercase text-secondary-emphasis" style="font-size: 0.70rem; letter-spacing: 0.6px;">Code</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis" style="font-size: 0.70rem; letter-spacing: 0.6px;">Program name</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis d-none d-md-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Department</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis text-center d-none d-lg-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Req. hours</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis text-center" style="font-size: 0.70rem; letter-spacing: 0.6px;">Students</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis text-center" style="font-size: 0.70rem; letter-spacing: 0.6px;">Status</th>
                                        <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis text-center pe-4" style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody class="border-0">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>