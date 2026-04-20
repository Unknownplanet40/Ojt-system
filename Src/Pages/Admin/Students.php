<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// time to manila asia
date_default_timezone_set('Asia/Manila');

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Students";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <link rel="stylesheet" href="../../../Assets/style/admin/StudentsStyles.css">
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/Students.js"></script>
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
    <div class="container">
        <div class="modal fade" id="CreateStudentModal" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-5">
                            <div class="hstack gap-3 align-items-center">
                                <div class="vstack gap-1">
                                    <h5 class="modal-title fw-bold mb-0 fs-5">Add Student</h5>
                                    <p class="text-muted small mb-0">Create a new student account and profile</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto flex-shrink-0"
                                    id="bulkimportBtn"><i class="bi bi-upload me-2"></i>Bulk Import</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto flex-shrink-0"
                                    data-bs-dismiss="modal"><i class="bi bi-arrow-left me-2"></i>Back</button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="card-title fw-bold mb-1 fs-6">Account Information</h6>
                                    <small class="text-muted d-block">A temporary password will be generated
                                        automatically.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control bg-transparent border-0 shadow-none"
                                                id="studentEmail" placeholder="Email Address">
                                            <label for="studentEmail" class="text-muted">Email Address<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                        <small class="text-muted d-block mt-2">This will be the student's login
                                            email</small>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="studentNumber" placeholder="Student Number">
                                            <label for="studentNumber" class="text-muted">Student Number<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                        <small class="text-muted d-block mt-2">Must be unique</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Personal Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="lastName" placeholder="Last Name">
                                            <label for="lastName" class="text-muted">Last Name<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="firstName" placeholder="First Name">
                                            <label for="firstName" class="text-muted">First Name<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="middleName" placeholder="Middle Name">
                                            <label for="middleName" class="text-muted">Middle Name</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="mobileNumber" placeholder="Mobile Number">
                                            <label for="mobileNumber" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12">
                                        <div class="form-floating">
                                            <textarea class="form-control bg-transparent border-0 shadow-none"
                                                placeholder="Address" id="address" style="height: 80px;"></textarea>
                                            <label for="address" class="text-muted">Address</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="emergencyContact" placeholder="Emergency contact">
                                            <label for="emergencyContact" class="text-muted">Emergency Contact</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="emergencyContactNumber" placeholder="Emergency contact number">
                                            <label for="emergencyContactNumber" class="text-muted">Emergency Contact
                                                Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="card-title fw-bold mb-1 fs-6">Academic Information</h6>
                                    <small class="text-muted d-block">Student is assigned to the active batch
                                        automatically.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="programSelect">
                                            </select>
                                            <label for="programSelect" class="text-muted">Program<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="yearLevelSelect">
                                                <option value="" class="CustomOption" selected disabled hidden>Select
                                                    year level</option>
                                                <option value="1" class="CustomOption">1st Year</option>
                                                <option value="2" class="CustomOption">2nd Year</option>
                                                <option value="3" class="CustomOption">3rd Year</option>
                                                <option value="4" class="CustomOption">4th Year</option>
                                            </select>
                                            <label for="yearLevelSelect" class="text-muted">Year Level<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="section" placeholder="Section">
                                            <label for="section" class="text-muted">Section</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="coordinatorSelect">
                                            </select>
                                            <label for="coordinatorSelect" class="text-muted">Coordinator</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="StudactiveBatch" placeholder="Active Batch" disabled>
                                            <label for="activeBatch" class="text-muted">Active Batch</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="vstack gap-3">
                                    <p class="text-muted small mb-0">By clicking "Create", a student account will be
                                        created with a temporary password. The student will be required to change their
                                        password upon first login.</p>
                                    <button class="btn btn-primary py-2 px-5 align-self-start text-nowrap"
                                        id="createStudentBtn">
                                        <i class="bi bi-person-plus me-2"></i>Create
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="EditStudentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-5">
                            <div class="hstack gap-3 align-items-center">
                                <div class="vstack gap-1">
                                    <h5 class="modal-title fw-bold mb-0 fs-5">Edit Student</h5>
                                    <p class="text-muted small mb-0"><span id="editStudentFullName"></span> &bull; <span
                                            id="editStudentNumberDisplay"></span></p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto flex-shrink-0"
                                    data-bs-dismiss="modal"><i class="bi bi-arrow-left me-2"></i>Back</button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="card-title fw-bold mb-1 fs-6">Account Information</h6>
                                    <small class="text-muted d-block">Email and student number cannot be
                                        changed.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <p class="form-control bg-transparent border-0 shadow-none"
                                                id="editStudentEmail"></p>
                                            <label for="editStudentEmail" class="text-muted">Email Address</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <p class="form-control bg-transparent border-0 shadow-none"
                                                id="editStudentNumber"></p>
                                            <label for="editStudentNumber" class="text-muted">Student Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Personal Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editLastName" placeholder="Last Name">
                                            <label for="editLastName" class="text-muted">Last Name<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editFirstName" placeholder="First Name">
                                            <label for="editFirstName" class="text-muted">First Name<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editMiddleName" placeholder="Middle Name">
                                            <label for="editMiddleName" class="text-muted">Middle Name</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editMobileNumber" placeholder="Mobile Number">
                                            <label for="editMobileNumber" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12">
                                        <div class="form-floating">
                                            <textarea class="form-control bg-transparent border-0 shadow-none"
                                                placeholder="Address" id="editAddress" style="height: 80px;"></textarea>
                                            <label for="editAddress" class="text-muted">Address</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editEmergencyContact" placeholder="Emergency contact">
                                            <label for="editEmergencyContact" class="text-muted">Emergency
                                                Contact</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editEmergencyContactNumber" placeholder="Emergency contact number">
                                            <label for="editEmergencyContactNumber" class="text-muted">Emergency Contact
                                                Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="card-title fw-bold mb-1 fs-6">Academic Information</h6>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-6">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="editProgramSelect">
                                            </select>
                                            <label for="editProgramSelect" class="text-muted">Program<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="editYearLevelSelect">
                                                <option value="" class="CustomOption" selected disabled hidden>Select
                                                    year level</option>
                                                <option value="1" class="CustomOption">1st Year</option>
                                                <option value="2" class="CustomOption">2nd Year</option>
                                                <option value="3" class="CustomOption">3rd Year</option>
                                                <option value="4" class="CustomOption">4th Year</option>
                                            </select>
                                            <label for="editYearLevelSelect" class="text-muted">Year Level<span
                                                    class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-2">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editSection" placeholder="Section">
                                            <label for="editSection" class="text-muted">Section</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-7">
                                        <div class="form-floating">
                                            <select class="form-select bg-transparent border-0 shadow-none"
                                                id="editCoordinatorSelect">
                                            </select>
                                            <label for="editCoordinatorSelect" class="text-muted">Coordinator</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none"
                                                id="editActiveBatch" placeholder="Active Batch" disabled>
                                            <label for="editActiveBatch" class="text-muted">Active Batch</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm"
                            style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="vstack gap-3">
                                    <p class="text-muted small mb-0">Click "Save Changes" to update the student
                                        information.</p>
                                    <button class="btn btn-primary py-2 px-5 align-self-start text-nowrap"
                                        id="editStudentBtn">
                                        <i class="bi bi-check-circle me-2"></i>Save Changes
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="StudentCreatedModal" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false" data-Student-Uuid="">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-success bg-opacity-25 rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="card-body">
                                <div class="vstack gap-3 py-4">
                                    <div class="hstack gap-3">
                                        <div class="bg-success bg-opacity-75 rounded-circle d-flex justify-content-center align-items-center"
                                            style="min-width: 40px; min-height: 40px;">
                                            <i class="bi bi-check-lg text-white"></i>
                                        </div>
                                        <div class="vstack">
                                            <h5 class="mb-0 fw-bold text-success">Student account created</h5>
                                            <p class="text-muted mb-3">Account created for <span
                                                    id="createdStudentName"></span>. Share this temporary password with
                                                the student.</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3"
                                                id="createdStudentTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportPdfBtn"><i
                                                class="bi bi-file-earmark-pdf me-2"></i>Export PDF</button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal"
                                            data-bs-target="#CreateStudentModal"><i
                                                class="bi bi-person-plus me-2"></i>Create Another Student</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ResetPasswordSuccessModal" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-success bg-opacity-25 rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="card-body">
                                <div class="vstack gap-3 py-4">
                                    <div class="hstack gap-3">
                                        <div class="bg-success bg-opacity-75 rounded-circle d-flex justify-content-center align-items-center"
                                            style="min-width: 40px; min-height: 40px;">
                                            <i class="bi bi-check-lg text-white"></i>
                                        </div>
                                        <div class="vstack">
                                            <h5 class="mb-0 fw-bold text-success">Password reset successful</h5>
                                            <p class="text-muted mb-3">Password has been reset for <span
                                                    id="resetPasswordSuccessStudentName"></span>. The new temporary
                                                password is:</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3"
                                                id="resetPasswordSuccessTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportResetPdfBtn"><i
                                                class="bi bi-file-earmark-pdf me-2"></i>Export PDF</button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-dismiss="modal"><i
                                                class="bi bi-check-circle me-2"></i>Done</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ViewStudentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable modal-xl">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-blur-5 bg-semi-transparent rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="vstack gap-4 p-2 p-md-4">
                                <div class="hstack gap-3 flex-wrap align-items-start">
                                    <div class="vstack gap-1">
                                        <div class="hstack gap-2">
                                            <img src="" alt="Profile Picture" class="rounded-circle border"
                                                id="viewStudentProfilePic"
                                                style="width: 60px; height: 60px; object-fit: cover;">
                                            <div>
                                                <h5 class="mb-0 fw-bold">Student Profile</h5>
                                                <p class="text-muted mb-0 hstack gap-2 align-items-center">
                                                    <span id="viewStudentFullName"></span>
                                                    <span class="mx-2">&bull;</span>
                                                    <span id="viewStudentNumber"></span>
                                                    <span class="mx-2">&bull;</span>
                                                    <span class="px-3 py-2" id="viewStudentStatus"></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div
                                        class="hstack gap-2 w-100 ms-md-auto flex-wrap justify-content-center justify-content-md-end">
                                        <button type="button"
                                            class="btn btn-sm bg-secondary-subtle text-body py-2 px-3 rounded-3 border flex-grow-1 flex-md-grow-0"
                                            id="deactivateStudentBtn" title="Deactivate this student">
                                            <i class="bi bi-person-x me-1"></i>Deactivate
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm bg-secondary-subtle text-body py-2 px-3 rounded-3 border flex-grow-1 flex-md-grow-0"
                                            id="activateStudentBtn" title="Activate this student">
                                            <i class="bi bi-person-check me-1"></i>Activate
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm bg-secondary-subtle text-body py-2 px-3 rounded-3 border flex-grow-1 flex-md-grow-0"
                                            id="changePasswordBtn" title="Set a new password">
                                            <i class="bi bi-key me-1"></i>Reset Password
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm bg-secondary-subtle text-body py-2 px-3 rounded-3 border flex-grow-1 flex-md-grow-0"
                                            id="editStudentFromViewBtn" title="Edit student details">
                                            <i class="bi bi-pencil-square me-1"></i>Edit
                                        </button>
                                    </div>
                                </div>

                                <section class="w-100">
                                    <div class="row g-3 g-md-4">
                                        <div class="col-12">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm"
                                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                                <div class="card-body p-3 pt-0 p-md-4">
                                                    <div class="row g-4 mt-1">
                                                        <div class="col-12 col-xl-6">
                                                            <div
                                                                class="rounded-4 border bg-dark bg-opacity-10 p-3 p-md-4 h-100 shadow-sm">
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold mb-1">Personal Information</h6>
                                                                    <p class="text-muted small mb-0">Basic profile and
                                                                        contact details</p>
                                                                </div>

                                                                <ul class="list-unstyled mb-0">
                                                                    <li class="py-2">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Full
                                                                                Name</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentFullName2"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Email
                                                                                Address</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentEmail"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Mobile</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentMobile"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Home
                                                                                Address</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentHomeAddress"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Emergency
                                                                                Contact</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentEmergencyContact"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Emergency
                                                                                Contact Phone</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentEmergencyPhone"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="pt-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Last
                                                                                Login</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentLastLogin"></span>
                                                                        </div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>

                                                        <div class="col-12 col-xl-6">
                                                            <div
                                                                class="rounded-4 border bg-dark bg-opacity-10 p-3 p-md-4 h-100 shadow-sm">
                                                                <div class="mb-3">
                                                                    <h6 class="fw-bold mb-1">Academic Information</h6>
                                                                    <p class="text-muted small mb-0">Enrollment and
                                                                        internship requirements</p>
                                                                </div>

                                                                <ul class="list-unstyled mb-0">
                                                                    <li class="py-2">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Student
                                                                                No.</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentStudentNo"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Program</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentProgram"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Year
                                                                                &amp; Section</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentYearSection"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Department</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentDepartment"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Coordinator</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentCoordinator"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="py-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Batch</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentBatch"></span>
                                                                        </div>
                                                                    </li>
                                                                    <li
                                                                        class="pt-2 border-top border-secondary border-opacity-25">
                                                                        <div
                                                                            class="d-flex flex-column flex-sm-row justify-content-between gap-1 gap-sm-3">
                                                                            <span
                                                                                class="text-muted small fw-semibold text-uppercase">Required
                                                                                Hours</span>
                                                                            <span
                                                                                class="fw-medium text-sm-end text-break"
                                                                                id="viewStudentRequiredHours">120</span>
                                                                        </div>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>
                                <div class="hstack gap-3 justify-content-end">
                                    <button class="btn btn-sm bg-secondary-subtle text-body py-2 px-3 rounded-3 border"
                                        data-bs-dismiss="modal"><i class="bi bi-x-circle me-1"></i>Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ResetPasswordModal" tabindex="-1" data-bs-backdrop="static"
            data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body bg-blur-5 bg-semi-transparent rounded-4">
                        <div class="card bg-transparent border-0 shadow-sm">
                            <div class="card-body p-4">
                                <div class="vstack gap-4">
                                    <div class="hstack gap-3">
                                        <div class="bg-info bg-opacity-75 rounded-circle d-flex justify-content-center align-items-center"
                                            style="min-width: 40px; min-height: 40px;">
                                            <i class="bi bi-key text-white"></i>
                                        </div>
                                        <div class="vstack">
                                            <h5 class="mb-0 fw-bold text-info">Reset Student Password</h5>
                                            <p class="text-muted mb-0">
                                                Reset password for <span id="resetPasswordStudentName"></span>. A
                                                temporary password will be generated automatically.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="hstack">
                                        <button class="btn btn-primary py-2 px-4 align-self-start text-nowrap"
                                            id="resetPasswordBtn">
                                            <i class="bi bi-check-circle me-2"></i>Reset Password
                                        </button>
                                        <button class="btn btn-outline-secondary py-2 px-4 align-self-start ms-auto"
                                            data-bs-dismiss="modal" id="cancelResetPasswordBtn">
                                            <i class="bi bi-x-circle me-2"></i>Cancel
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="bulkCreationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
                <div class="modal-content bg-transparent border-0 shadow"
                    style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-3">
                            <div class="card-body p-md-5">
                                <div class="vstack gap-4">
                                    <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                            style="width: 48px; height: 48px;">
                                            <i class="bi bi-file-earmark-spreadsheet-fill fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 fw-bold">Step 1 — Download the template</h5>
                                            <p class="text-muted mb-0">
                                                Use the official template to keep your data formatted correctly for bulk
                                                import.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="rounded-4 border bg-body-tertiary bg-opacity-50 p-3 p-md-4 shadow-sm">
                                        <div
                                            class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                            <div class="flex-shrink-0 rounded-3 bg-success bg-opacity-10 text-success d-flex align-items-center justify-content-center"
                                                style="width: 44px; height: 44px;">
                                                <i class="bi bi-file-earmark-spreadsheet fs-4"></i>
                                            </div>

                                            <div class="flex-grow-1 min-w-0">
                                                <p class="mb-1 fw-semibold text-break">student_bulk_import_template.xlsx
                                                </p>
                                                <small class="text-muted d-block">
                                                    Includes required headers, sample rows, and formatting guidance.
                                                </small>
                                            </div>

                                            <button
                                                class="btn btn-sm btn-outline-primary px-3 py-2 rounded-3 text-nowrap ms-md-auto"
                                                id="downloadTemplateBtn">
                                                <i class="bi bi-download me-2"></i>
                                                Download Template
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        class="alert alert-info border-0 rounded-4 mb-0 shadow-sm bg-info bg-opacity-10">
                                        <div class="d-flex align-items-start gap-3">
                                            <i class="bi bi-info-circle-fill text-info fs-5 mt-1"></i>
                                            <div class="small">
                                                <div class="fw-semibold mb-2">Required columns</div>
                                                <div class="text-body-secondary lh-lg">
                                                    <span class="fw-medium text-body">last_name</span>,
                                                    <span class="fw-medium text-body">first_name</span>,
                                                    <span class="fw-medium text-body">middle_name</span>,
                                                    <span class="fw-medium text-body">email</span>,
                                                    <span class="fw-medium text-body">student_number</span>,
                                                    <span class="fw-medium text-body">program_code</span>,
                                                    <span class="fw-medium text-body">year_level</span>,
                                                    <span class="fw-medium text-body">section</span>,
                                                    <span class="fw-medium text-body">mobile</span>
                                                </div>
                                                <hr class="my-3">
                                                <div>
                                                    Program codes must match active programs, such as
                                                    <strong>BSIT</strong>, <strong>BSCS</strong>,
                                                    <strong>BSCpE</strong>, and <strong>BSIS</strong>.
                                                    Year level must be <strong>1</strong>, <strong>2</strong>,
                                                    <strong>3</strong>, or <strong>4</strong>.
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                            <div class="card-body p-md-5">
                                <div class="vstack gap-4">
                                    <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                        <div class="flex-shrink-0 rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center"
                                            style="width: 48px; height: 48px;">
                                            <i class="bi bi-file-earmark-spreadsheet-fill fs-4"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h5 class="mb-1 fw-bold">Step 2 — Upload your file</h5>
                                            <p class="text-muted mb-0">
                                                Supports .csv and .xlsx files. Max 500 rows per upload.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="vstack">
                                            <label for="fileUploadInput" id="fileUploadLabel"
                                                class="card bg-blur-5 bg-semi-transparent rounded-3 py-3 border border-success border-2"
                                                style="--blur-lvl: <?= $opacitylvl ?>; border-style: dashed !important; cursor: pointer;"
                                                id="fileUploadLabel">
                                                <div class="card-body d-flex flex-column align-items-center">
                                                    <i class="bi bi-file-earmark-arrow-up text-muted fs-4" id="fileUploadIcon"></i>
                                                    <p class="card-text text-muted mb-0" id="fileUploadTitle">Click to upload or drag and
                                                        drop your file here</p>
                                                    <small class="text-muted" id="fileUploadHint">CSV or Excel (.xlsx) &bull; Max 500 rows
                                                        &bull; Max file size 5MB</small>
                                                    <span class="badge text-bg-success mt-2 d-none" id="selectedFileName"></span>
                                                </div>
                                            </label>
                                            <input type="file" id="fileUploadInput" accept=".csv,.xlsx" class="d-none">
                                        </div>
                                    </div>
                                    <!-- cancel and validate & Preview buttons will be shown after file selection -->
                                    <div class="hstack gap-3 justify-content-end" id="bulkUploadActions">
                                        <button class="btn btn-outline-secondary py-2 px-4 align-self-start"
                                            id="cancelBulkUploadBtn">
                                            <i class="bi bi-x-circle me-2"></i>Cancel
                                        </button>
                                        <button class="btn btn-primary py-2 px-4 align-self-start text-nowrap"
                                            id="validatePreviewBtn" disabled>
                                            <i class="bi bi-check-circle me-2"></i>Validate &amp; Preview
                                        </button>
                                    </div>
                                    <small class="text-muted ms-auto" id="validatePreviewHelpText">Select a file first to enable validation.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="validatePreviewModal" tabindex="-1" data-bs-backdrop="static"  data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
                <div class="modal-content bg-transparent border-0" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0">
                            <div class="card-body p-2 mb-2">
                                <div class="hstack gap-3 p-3 pb-0">
                                    <div class="vstack gap-4">
                                        <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                                            <div class="flex-grow-1">
                                                <h5 class="mb-1 fw-bold">Validate & preview</h5>
                                                <p class="text-muted mb-0">
                                                    Review results before creating accounts
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <button
                                        class="btn btn-sm bg-secondary-subtle px-3 py-2 rounded-3 text-nowrap ms-auto"
                                        id="reuploadBtn">
                                        <i class="bi bi-arrow-clockwise me-2"></i>
                                        Re-upload File
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row row-col-1 row-cols-3 g-2">
                                    <div class="col-md-4">
                                        <div
                                            class="card text-bg-light border-2 border-success text-success rounded-4 p-2 text-center h-100">
                                            <div class="card-body">
                                                <h4 class="card-title fw-bold mb-1" id="validCount">0</h4>
                                                <p class="card-text small mb-0">Valid Rows</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div
                                            class="card bg-danger-subtle border-2 border-danger text-danger rounded-4 p-2 text-center h-100">
                                            <div class="card-body">
                                                <h4 class="card-title fw-bold mb-1" id="invalidCount">0</h4>
                                                <p class="card-text small mb-0">Rows with errors</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-secondary-subtle text-body rounded-4 p-2 text-center h-100">
                                            <div class="card-body">
                                                <h4 class="card-title fw-bold mb-1" id="TotalCount">0</h4>
                                                <p class="card-text small mb-0">Total rows</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-warning d-flex align-items-center border-0 rounded-4"
                                    role="alert" id="validationErrorsAlert">
                                    <span id="validationErrorsText" class="mb-0">
                                        2 rows have errors and will be skipped. Fix the file and re-upload to include
                                        them, or proceed with the 8 valid rows only.
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive table-scroll-10 rounded-3 border">
                                    <table
                                        class="table table-sm table-hover align-middle mb-0"
                                        id="validationResultsTable">
                                        <thead class="bg-blur-5 bg-semi-transparent border-0 sticky-top">
                                            <tr class="text-muted border-0">
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">#</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Name</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Email</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Student No.</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Program</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Year</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-md-table-cell"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Coordinator</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Status</th>
                                                <th scope="col"
                                                    class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0"
                                                    style="font-size: 0.70rem; letter-spacing: 0.6px;">Error Details</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-0" id="validationResultsBody">
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">
                                                    <i class="bi bi-table me-2"></i>Validation results will appear here after upload.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div>
                                     <div class="hstack gap-3 justify-content-end mt-3">
                                        <button class="btn btn-outline-secondary py-2 px-4 align-self-start"
                                            id="reuploadFixedFileBtn">
                                            <i class="bi bi-arrow-clockwise me-2"></i>
                                            Re-upload Fixed File
                                        </button>
                                        <button class="btn btn-primary py-2 px-4 align-self-start text-nowrap"
                                            id="createAccountsBtn">
                                            <i class="bi bi-check-circle me-2"></i>Create <span id="validCountLabel">0</span> Accounts
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="bulkSuccessModal" tabindex="-1" data-bs-backdrop="static"  data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-transparent border-0" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body">
                        <div class="alert bg-success-subtle border-0 rounded-4 d-flex align-items-center gap-3">
                            <i class="bi bi-check-circle-fill text-success fs-3"></i>
                            <div>
                                <h5 class="mb-1 fw-bold text-success"><span id="accountsCreatedCount">0</span> Accounts Created successfully</h5>
                                <p class="mb-0">All students have been assigned <span id="batchlabelCurrent">Current Batch</span> and their pre-OJT requirements have been initialized.</p>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border mb-3">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-12 col-md-6">
                                        <div class="card bg-success-subtle border border-success-subtle rounded-4 text-center h-100">
                                            <div class="card-body py-3">
                                                <h4 class="fw-bold text-success mb-1" id="successCreatedCount">0</h4>
                                                <p class="small mb-0 text-success-emphasis">Successfully Created</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="card bg-danger-subtle border border-danger-subtle rounded-4 text-center h-100">
                                            <div class="card-body py-3">
                                                <h4 class="fw-bold text-danger mb-1" id="successFailedCount">0</h4>
                                                <p class="small mb-0 text-danger-emphasis">Failed to Create</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="failedRowsContainer" class="mt-3 d-none">
                                    <div class="alert alert-danger border-0 rounded-4 mb-2">
                                        <div class="hstack gap-2">
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                            <p class="mb-0 small fw-semibold">Some rows failed during account creation.</p>
                                            <button class="btn btn-sm btn-link text-decoration-none ms-auto p-0"
                                                id="toggleFailedRowsBtn" type="button">
                                                Show details
                                            </button>
                                        </div>
                                    </div>
                                    <div class="table-responsive rounded-3 border d-none" id="failedRowsDetails">
                                        <table class="table table-sm table-striped table-hover align-middle mb-0">
                                            <thead class="bg-blur-5 bg-semi-transparent border-0">
                                                <tr class="text-muted border-0">
                                                    <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Name</th>
                                                    <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Email</th>
                                                    <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Student No.</th>
                                                    <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Reason</th>
                                                </tr>
                                            </thead>
                                            <tbody id="failedRowsTableBody"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border">
                            <div class="card-body">
                                <div class="vstrack">
                                    <p class="mb-0 fw-semibold">Export credentials</p>
                                    <small class="text-muted">Download the credentials sheet to distribute to your students.</small>
                                </div>
                                <div class="vstrack mt-1">
                                    <div class="card-body border rounded-4 mb-3">
                                        <div class="hstack gap-2">
                                            <i class="bi bi-file-earmark-pdf-fill text-muted fs-4"></i>
                                            <div class="vstack">
                                                <p class="mb-0">PDF Credentials Sheet</p>
                                                <small class="text-muted">Formatted document with each student's name, email, student number, and temporary password. Print-ready.</small>
                                            </div>
                                            <button class="btn btn-sm bg-secondary-subtle border text-body px-3 py-2 rounded-3 text-nowrap ms-auto" id="PdfCredentialsBtn">
                                                <i class="bi bi-download me-2"></i>
                                                Download PDF
                                            </button>
                                        </div>
                                    </div>
                                    <div class="card-body border rounded-4">
                                        <div class="hstack gap-2">
                                            <i class="bi bi-file-earmark-spreadsheet-fill text-muted fs-4"></i>
                                            <div class="vstack">
                                                <p class="mb-0">CSV Credentials Export</p>
                                                <small class="text-muted">Spreadsheet with all created accounts. Useful if you want to email credentials individually or import into another system.</small>
                                            </div>
                                            <button class="btn btn-sm bg-secondary-subtle border text-body px-3 py-2 rounded-3 text-nowrap ms-auto" id="CsvCredentialsBtn">
                                                <i class="bi bi-download me-2"></i>
                                                Download CSV
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive table-scroll-10 rounded-3">
                                    <table class="table table-sm table-borderless table-hover table-striped align-middle mb-0" id="createdAccountsTable">
                                        <thead class="bg-blur-5 bg-semi-transparent border-0">
                                            <tr class="text-muted border-0">
                                                <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Name</th>
                                                <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Email</th>
                                                <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Student No.</th>
                                                <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Program</th>
                                                <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Temporary Password</th>
                                            </tr>
                                        </thead>
                                        <tbody class="border-0">
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted">Created accounts will appear here after import.</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <!-- import new, view all students buttons(aka close modal) -->
                                <div class="hstack gap-3 justify-content-end mt-3">
                                    <button class="btn btn-outline-secondary py-2 px-4 align-self-start"
                                        id="viewAllStudentsBtn">
                                        <i class="bi bi-eye me-2"></i>
                                        View All Students
                                    </button>
                                    <button class="btn btn-primary py-2 px-4 align-self-start text-nowrap"
                                        id="importNewBatchBtn">
                                        <i class="bi bi-file-earmark-plus me-2"></i>
                                        Import New Batch
                                    </button>
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
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Students</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="activeBatchLabel"></span> &bull; <span
                                id="activeBatchCount"></span> students</p>
                        <a href="javascript:void(0)" class="small text-decoration-none" id="startStudentsTourLink">
                            <i class="bi bi-signpost-split me-1"></i>Start quick tour
                        </a>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addStudentOpenBtn" data-bs-toggle="modal"
                        data-bs-target="#CreateStudentModal">
                        <i class="bi bi-person-plus me-1"></i>
                        Add Student
                    </button>
                </div>
                <div class="container">
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-5">
                            <label for="searchInput" class="form-label small fw-semibold text-muted mb-2">Search</label>
                            <div class="input-group rounded-3 overflow-hidden">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-0"><i
                                        class="bi bi-search text-muted"></i></span>
                                <input type="text"
                                    class="form-control bg-blur-5 bg-semi-transparent border-0 shadow-none"
                                    placeholder="Search by name or email..." id="searchInput">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="programFilter"
                                class="form-label small fw-semibold text-muted mb-2">Program</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none"
                                id="programFilter">
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="yearlvlFilter" class="form-label small fw-semibold text-muted mb-2">Year
                                Level</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none"
                                id="yearlvlFilter">
                                <option value="" class="CustomOption" selected>All Levels</option>
                                <option value="1" class="CustomOption">1st Year</option>
                                <option value="2" class="CustomOption">2nd Year</option>
                                <option value="3" class="CustomOption">3rd Year</option>
                                <option value="4" class="CustomOption">4th Year</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-2">
                            <label for="statusFilter"
                                class="form-label small fw-semibold text-muted mb-2">Status</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none"
                                id="statusFilter">
                            </select>
                        </div>
                        <div class="col-12 col-lg-1 d-flex">
                            <button class="btn btn-outline-secondary border rounded-3 shadow-none w-100 d-none"
                                id="clearFiltersBtn" data-bs-toggle="tooltip" data-bs-placement="top"
                                title="Clear filters"><i class="bi bi-x-circle-fill"></i></button>
                        </div>
                    </div>
                    <div class="table-responsive table-scroll-10 rounded-3">
                        <table class="table table-sm table-borderless table-hover table-striped align-middle mb-0"
                            id="studentsTable">
                            <thead class="bg-blur-5 bg-semi-transparent border-0">
                                <tr class="text-muted border-0">
                                    <th scope="col"
                                        class="ps-4 py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Profile</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-md-table-cell border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Student No</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-lg-table-cell border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Program</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center d-none d-xl-table-cell border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Year</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Coordinator</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center border-0"
                                        style="font-size: 0.70rem; letter-spacing: 0.6px;">Status</th>
                                    <th scope="col"
                                        class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center pe-4 border-0"
                                        style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody class="border-0">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>