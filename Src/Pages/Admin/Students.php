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
        <div class="modal fade" id="StudentCreatedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-Student-Uuid="">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
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
                                            <p class="text-muted mb-3">Account created for <span id="createdStudentName"></span>. Share this temporary password with the student.</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3" id="createdStudentTempPassword">123123123123</span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportPdfBtn"><i class="bi bi-file-earmark-pdf me-2"></i>Export PDF</button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#CreateStudentModal"><i class="bi bi-person-plus me-2"></i>Create Another Student</button>
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
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Students</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="activeBatchLabel"></span> &bull; <span
                                id="activeBatchCount"></span> students</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" data-bs-toggle="modal"
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