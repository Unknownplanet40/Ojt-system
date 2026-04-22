<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Supervisors";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <link rel="stylesheet" href="../../../Assets/style/admin/StudentsStyles.css">
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/SupervisorAccounts.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
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

    <div class="container">
        <div class="modal fade" id="CreateSupervisorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-3 p-md-4 p-lg-5 supervisor-modal-body">
                        <div class="supervisor-modal-header mb-4">
                            <div class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center justify-content-between">
                                <div>
                                    <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-2 rounded-pill border border-secondary border-opacity-25 supervisor-meta-pill">
                                        <i class="bi bi-person-plus text-secondary"></i>
                                        <span class="small fw-semibold text-body">Create supervisor account</span>
                                    </div>
                                    <h4 class="mb-1 fw-bold text-white">Add Supervisor</h4>
                                    <p class="text-muted small mb-0">Create a new supervisor account for a company partner.</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-lg-auto" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0 supervisor-avatar" style="width: 56px; height: 56px; min-width: 56px;">
                                        <img src="https://placehold.co/64x64/483a0f/c6983d/png?text=SV&font=poppins" alt="Profile Picture" class="img-fluid" id="createSupervisorProfilePic">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <h5 class="fw-bold mb-1 text-truncate">New Supervisor</h5>
                                        <p class="text-muted mb-2 small">Fill out the account and company details below.</p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge rounded-pill supervisor-meta-pill text-body">Temporary password auto-generated</span>
                                            <span class="badge rounded-pill supervisor-meta-pill text-body">Required fields marked *</span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="card-title fw-bold mb-4 fs-6">Account Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control bg-transparent border-0 shadow-none" id="supervisorEmail" placeholder="Email Address">
                                            <label for="supervisorEmail" class="text-muted">Email Address<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none" id="supervisorCompany">
                                                <option value="" class="CustomOption" selected>Choose company</option>
                                            </select>
                                            <label for="supervisorCompany" class="text-muted">Company<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Supervisor Profile</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="supervisorLastName" placeholder="Last Name">
                                            <label for="supervisorLastName" class="text-muted">Last Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="supervisorFirstName" placeholder="First Name">
                                            <label for="supervisorFirstName" class="text-muted">First Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="supervisorPosition" placeholder="Position">
                                            <label for="supervisorPosition" class="text-muted">Position<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="supervisorDepartment" placeholder="Department">
                                            <label for="supervisorDepartment" class="text-muted">Department<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="supervisorMobile" placeholder="Mobile Number">
                                            <label for="supervisorMobile" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3 w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-sm btn-dark text-light px-4 py-2 rounded-3 border border-secondary w-100 w-sm-auto" id="createSupervisorBtn">Save Supervisor</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="EditSupervisorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-profile-uuid="">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-3 p-md-4 p-lg-5 supervisor-modal-body">
                        <div class="supervisor-modal-header mb-4">
                            <div class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center justify-content-between">
                                <div>
                                    <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-2 rounded-pill border border-secondary border-opacity-25 supervisor-meta-pill">
                                        <i class="bi bi-pencil-square text-secondary"></i>
                                        <span class="small fw-semibold text-body">Edit supervisor profile</span>
                                    </div>
                                    <h4 class="mb-1 fw-bold text-white">Edit Supervisor</h4>
                                    <p class="text-muted small mb-0">Update the supervisor’s account and profile details below.</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-lg-auto" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-start gap-3 mb-3">
                                    <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0 supervisor-avatar" style="width: 56px; height: 56px; min-width: 56px;">
                                        <img src="https://placehold.co/64x64/483a0f/c6983d/png?text=SV&font=poppins" alt="Profile Picture" class="img-fluid" id="editSupervisorProfilePic">
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <h5 class="fw-bold mb-1 text-truncate" id="editSupervisorFullName"></h5>
                                        <p class="text-muted mb-2 text-truncate small" id="editSupervisorEmail"></p>
                                        <div class="d-flex flex-wrap gap-2">
                                            <span class="badge rounded-pill supervisor-meta-pill text-body">Company details</span>
                                            <span class="badge rounded-pill supervisor-meta-pill text-body">Account info</span>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="card-title fw-bold mb-4 fs-6">Account Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-lg-6">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none" id="editSupervisorCompany">
                                                <option value="" class="CustomOption" selected>Choose company</option>
                                            </select>
                                            <label for="editSupervisorCompany" class="text-muted">Company<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-6">
                                        <div class="form-floating">
                                            <p class="form-control bg-transparent border-0 shadow-none" id="editSupervisorEmail"></p>
                                            <label for="editSupervisorEmail" class="text-muted">Email Address</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Supervisor Profile</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editSupervisorLastName" placeholder="Last Name">
                                            <label for="editSupervisorLastName" class="text-muted">Last Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editSupervisorFirstName" placeholder="First Name">
                                            <label for="editSupervisorFirstName" class="text-muted">First Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editSupervisorPosition" placeholder="Position">
                                            <label for="editSupervisorPosition" class="text-muted">Position<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editSupervisorDepartment" placeholder="Department">
                                            <label for="editSupervisorDepartment" class="text-muted">Department<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editSupervisorMobile" placeholder="Mobile Number">
                                            <label for="editSupervisorMobile" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="d-flex flex-column flex-sm-row gap-2 justify-content-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3 w-100 w-sm-auto" data-bs-dismiss="modal">Cancel</button>
                                    <button class="btn btn-sm btn-dark text-light px-4 py-2 rounded-3 border border-secondary w-100 w-sm-auto" id="saveSupervisorBtn">Save Changes</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ViewSupervisorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-profile-uuid="" data-user-uuid="">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-3 p-md-4 p-lg-5 supervisor-modal-body">
                        <div class="supervisor-modal-header mb-4">
                            <div class="d-flex flex-column flex-md-row gap-3 align-items-start align-items-md-center justify-content-between">
                                <div class="d-flex align-items-center gap-3 min-w-0 flex-grow-1">
                                    <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0 supervisor-avatar" style="width: 72px; height: 72px; min-width: 72px;">
                                        <img src="https://placehold.co/64x64/483a0f/c6983d/png?text=SV&font=poppins" alt="Profile Picture" class="img-fluid" id="viewSupervisorProfilePic">
                                    </div>
                                    <div class="min-w-0">
                                        <div class="d-inline-flex align-items-center gap-2 mb-2 px-3 py-2 rounded-pill border border-secondary border-opacity-25 supervisor-meta-pill">
                                            <i class="bi bi-person-badge text-secondary"></i>
                                            <span class="small fw-semibold text-body">Supervisor overview</span>
                                        </div>
                                        <h4 class="mb-1 fw-bold text-white text-truncate" id="viewSupervisorFullName"></h4>
                                        <p class="text-muted mb-2 text-truncate">
                                            <span id="viewSupervisorCompany"></span>
                                            <span class="mx-2">·</span>
                                            <span id="viewSupervisorEmail"></span>
                                        </p>
                                        <span class="badge rounded-pill px-3 py-2" id="viewSupervisorStatus"></span>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-md-auto" data-bs-dismiss="modal">
                                    <i class="bi bi-x-circle me-2"></i>Close
                                </button>
                            </div>
                        </div>

                        <div class="row g-4">
                            <div class="col-12 col-lg-7">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm h-100 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <div class="card-body p-4 p-lg-4">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="fw-bold mb-0">Supervisor Details</h6>
                                            <span class="small text-muted">Read-only summary</span>
                                        </div>
                                        <div class="row g-3">
                                            <div class="col-12 col-md-6">
                                                <div class="supervisor-info-item p-3 rounded-3 h-100">
                                                    <span class="text-muted small d-block mb-1">Position</span>
                                                    <p class="mb-0 fw-semibold" id="viewSupervisorPosition"></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="supervisor-info-item p-3 rounded-3 h-100">
                                                    <span class="text-muted small d-block mb-1">Department</span>
                                                    <p class="mb-0 fw-semibold" id="viewSupervisorDepartment"></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="supervisor-info-item p-3 rounded-3 h-100">
                                                    <span class="text-muted small d-block mb-1">Mobile</span>
                                                    <p class="mb-0 fw-semibold" id="viewSupervisorMobile"></p>
                                                </div>
                                            </div>
                                            <div class="col-12 col-md-6">
                                                <div class="supervisor-info-item p-3 rounded-3 h-100">
                                                    <span class="text-muted small d-block mb-1">Last Login</span>
                                                    <p class="mb-0 fw-semibold" id="viewSupervisorLastLogin"></p>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="supervisor-info-item p-3 rounded-3 h-100">
                                                    <span class="text-muted small d-block mb-1">Created</span>
                                                    <p class="mb-0 fw-semibold" id="viewSupervisorCreatedAt"></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-lg-5">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm h-100 supervisor-panel" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <div class="card-body p-4 p-lg-4 d-flex flex-column">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <h6 class="fw-bold mb-0">Quick Actions</h6>
                                            <span class="small text-muted">Manage account</span>
                                        </div>
                                        <div class="vstack gap-2 mt-1">
                                            <button class="btn btn-sm btn-outline-secondary text-start supervisor-action-btn" id="exportViewSupervisorPdfBtn">
                                                <i class="bi bi-file-earmark-pdf me-2"></i>Export Details PDF
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary text-start supervisor-action-btn" id="editSupervisorFromViewBtn"><i class="bi bi-pencil-square me-2"></i>Edit Details</button>
                                            <button class="btn btn-sm btn-outline-secondary text-start supervisor-action-btn" id="resetSupervisorPasswordBtn"><i class="bi bi-key me-2"></i>Reset Password</button>
                                            <button class="btn btn-sm btn-outline-danger text-start supervisor-action-btn" id="deactivateSupervisorBtn"><i class="bi bi-person-x me-2"></i>Deactivate Account</button>
                                            <button class="btn btn-sm btn-outline-success text-start supervisor-action-btn d-none" id="activateSupervisorBtn"><i class="bi bi-person-check me-2"></i>Reactivate Account</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="SupervisorCreatedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-success bg-opacity-25 rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="card-body">
                                <div class="vstack gap-3 py-4">
                                    <div class="hstack gap-3">
                                        <div class="bg-success bg-opacity-75 rounded-circle d-flex justify-content-center align-items-center" style="min-width: 40px; min-height: 40px;">
                                            <i class="bi bi-check-lg text-white"></i>
                                        </div>
                                        <div class="vstack">
                                            <h5 class="mb-0 fw-bold text-success">Supervisor account created</h5>
                                            <p class="text-muted mb-3">Account created for <span id="createdSupervisorName"></span>.</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3" id="createdSupervisorTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Done</button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#CreateSupervisorModal">
                                            <i class="bi bi-person-plus me-2"></i>Create Another Supervisor
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ResetSupervisorPasswordSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-success bg-opacity-25 rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="card-body">
                                <div class="vstack gap-3 py-4">
                                    <div class="hstack gap-3">
                                        <div class="bg-success bg-opacity-75 rounded-circle d-flex justify-content-center align-items-center" style="min-width: 40px; min-height: 40px;">
                                            <i class="bi bi-check-lg text-white"></i>
                                        </div>
                                        <div class="vstack">
                                            <h5 class="mb-0 fw-bold text-success">Password reset successful</h5>
                                            <p class="text-muted mb-3">The new temporary password for <span id="resetSupervisorSuccessName"></span> is:</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3" id="resetSupervisorSuccessTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3 justify-content-end">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportResetSupervisorPdfBtn">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                                        </button>
                                        <button class="btn btn-sm btn-primary" data-bs-dismiss="modal"><i class="bi bi-check-circle me-2"></i>Done</button>
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
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack mb-4">
                    <div>
                        <h4 class="mb-1" id="dashboardTitle">Supervisor Accounts</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="supervisorCount">0</span> supervisor accounts</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addSupervisorOpenBtn" data-bs-toggle="modal" data-bs-target="#CreateSupervisorModal">
                        <i class="bi bi-person-plus me-1"></i>
                        Add Supervisor
                    </button>
                </div>

                <div class="container">
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-5">
                            <label for="supervisorSearchInput" class="form-label small fw-semibold text-muted mb-2">Search</label>
                            <div class="input-group rounded-3 overflow-hidden">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-blur-5 bg-semi-transparent border-0 shadow-none" placeholder="Search by name, email, company..." id="supervisorSearchInput">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-4">
                            <label for="companyFilter" class="form-label small fw-semibold text-muted mb-2">Company</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="companyFilter">
                                <option value="" class="CustomOption" selected>All Companies</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="supervisorStatusFilter" class="form-label small fw-semibold text-muted mb-2">Status</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="supervisorStatusFilter">
                                <option value="" class="CustomOption" selected>All Statuses</option>
                                <option value="active" class="CustomOption">Active</option>
                                <option value="never_logged_in" class="CustomOption">Never Logged In</option>
                                <option value="inactive" class="CustomOption">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-1 d-flex">
                            <button class="btn btn-outline-secondary border rounded-3 shadow-none w-100 d-none" id="clearSupervisorFiltersBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear filters"><i class="bi bi-x-circle-fill"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive table-scroll-10 rounded-3">
                        <table class="table table-sm table-borderless table-hover table-striped align-middle mb-0" id="supervisorsTable">
                            <thead class="bg-blur-5 bg-semi-transparent border-0">
                                <tr class="text-muted border-0">
                                    <th scope="col" class="ps-4 py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Supervisor</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-lg-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Company</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-md-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Position</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-xl-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Department</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Students</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Status</th>
                                </tr>
                            </thead>
                            <tbody class="border-0"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
