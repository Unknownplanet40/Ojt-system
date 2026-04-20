<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Coordinators";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <link rel="stylesheet" href="../../../Assets/style/admin/StudentsStyles.css">
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/CoordinatorAccounts.js"></script>
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
        <div class="modal fade" id="CreateCoordinatorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-5">
                            <div class="hstack gap-3 align-items-center">
                                <div class="vstack gap-1">
                                    <h5 class="modal-title fw-bold mb-0 fs-5">Add Coordinator</h5>
                                    <p class="text-muted small mb-0">Create a new coordinator account and profile</p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto flex-shrink-0" data-bs-dismiss="modal">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="card-title fw-bold mb-1 fs-6">Account Information</h6>
                                    <small class="text-muted d-block">A temporary password will be generated automatically.</small>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="email" class="form-control bg-transparent border-0 shadow-none" id="coordinatorEmail" placeholder="Email Address">
                                            <label for="coordinatorEmail" class="text-muted">Email Address<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorEmployeeId" placeholder="Employee ID">
                                            <label for="coordinatorEmployeeId" class="text-muted">Employee ID<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Profile Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorLastName" placeholder="Last Name">
                                            <label for="coordinatorLastName" class="text-muted">Last Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorFirstName" placeholder="First Name">
                                            <label for="coordinatorFirstName" class="text-muted">First Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorMiddleName" placeholder="Middle Name">
                                            <label for="coordinatorMiddleName" class="text-muted">Middle Name</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorDepartment" placeholder="Department">
                                            <label for="coordinatorDepartment" class="text-muted">Department<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="coordinatorMobile" placeholder="Mobile Number">
                                            <label for="coordinatorMobile" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <div class="vstack gap-3">
                                    <p class="text-muted small mb-0">By clicking "Create", a coordinator account will be created with a temporary password. The coordinator will be required to change their password upon first login.</p>
                                    <button class="btn btn-primary py-2 px-5 align-self-start text-nowrap" id="createCoordinatorBtn">
                                        <i class="bi bi-person-plus me-2"></i>Create
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="EditCoordinatorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-4 p-md-5">
                        <div class="mb-5">
                            <div class="hstack gap-3 align-items-center">
                                <div class="vstack gap-1">
                                    <h5 class="modal-title fw-bold mb-0 fs-5">Edit Coordinator</h5>
                                    <p class="text-muted small mb-0"><span id="editCoordinatorFullName"></span> &bull; <span id="editCoordinatorEmployeeIdDisplay"></span></p>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-auto flex-shrink-0" data-bs-dismiss="modal">
                                    <i class="bi bi-arrow-left me-2"></i>Back
                                </button>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Account Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <p class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorEmail"></p>
                                            <label for="editCoordinatorEmail" class="text-muted">Email Address</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <p class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorEmployeeId"></p>
                                            <label for="editCoordinatorEmployeeId" class="text-muted">Employee ID</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="card-title fw-bold mb-4 fs-6">Profile Information</h6>
                                <div class="row g-3">
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorLastName" placeholder="Last Name">
                                            <label for="editCoordinatorLastName" class="text-muted">Last Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorFirstName" placeholder="First Name">
                                            <label for="editCoordinatorFirstName" class="text-muted">First Name<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorMiddleName" placeholder="Middle Name">
                                            <label for="editCoordinatorMiddleName" class="text-muted">Middle Name</label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorDepartment" placeholder="Department">
                                            <label for="editCoordinatorDepartment" class="text-muted">Department<span class="text-danger ms-1">*</span></label>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <div class="form-floating">
                                            <input type="text" class="form-control bg-transparent border-0 shadow-none" id="editCoordinatorMobile" placeholder="Mobile Number">
                                            <label for="editCoordinatorMobile" class="text-muted">Mobile Number</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <button class="btn btn-primary py-2 px-5 align-self-start text-nowrap" id="saveCoordinatorBtn">
                                    <i class="bi bi-check-circle me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ViewCoordinatorModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-dialog-scrollable modal-lg">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="modal-body p-4">
                        <div class="hstack gap-3 align-items-start mb-4">
                            <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0" style="width: 60px; height: 60px; min-width: 60px;">
                                <img src="https://placehold.co/64x64/483a0f/c6983d/png?text=CP&font=poppins" alt="Profile Picture" class="img-fluid" id="viewCoordinatorProfilePic">
                            </div>
                            <div class="vstack gap-1">
                                <h5 class="fw-bold mb-0" id="viewCoordinatorFullName"></h5>
                                <p class="text-muted mb-0"><span id="viewCoordinatorEmployeeId"></span> &bull; <span id="viewCoordinatorEmail"></span></p>
                                <span class="badge rounded-pill px-3 py-2" id="viewCoordinatorStatus"></span>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" data-bs-dismiss="modal">
                                <i class="bi bi-x-circle me-2"></i>Close
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <div class="card-body p-4">
                                        <h6 class="fw-bold mb-3">Coordinator Details</h6>
                                        <ul class="list-unstyled mb-0">
                                            <li class="py-2"><span class="text-muted small">Department</span><p class="mb-0 fw-semibold" id="viewCoordinatorDepartment"></p></li>
                                            <li class="py-2 border-top border-secondary border-opacity-25"><span class="text-muted small">Mobile</span><p class="mb-0 fw-semibold" id="viewCoordinatorMobile"></p></li>
                                            <li class="py-2 border-top border-secondary border-opacity-25"><span class="text-muted small">Assigned Students</span><p class="mb-0 fw-semibold" id="viewCoordinatorStudentCount"></p></li>
                                            <li class="py-2 border-top border-secondary border-opacity-25"><span class="text-muted small">Last Login</span><p class="mb-0 fw-semibold" id="viewCoordinatorLastLogin"></p></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm h-100" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <div class="card-body p-4 d-flex flex-column">
                                        <h6 class="fw-bold mb-3">Actions</h6>
                                        <div class="vstack gap-2 mt-1">
                                            <button class="btn btn-sm btn-outline-secondary text-start" id="editCoordinatorFromViewBtn"><i class="bi bi-pencil-square me-2"></i>Edit Details</button>
                                            <button class="btn btn-sm btn-outline-secondary text-start" id="resetCoordinatorPasswordBtn"><i class="bi bi-key me-2"></i>Reset Password</button>
                                            <button class="btn btn-sm btn-outline-danger text-start" id="deactivateCoordinatorBtn"><i class="bi bi-person-x me-2"></i>Deactivate Account</button>
                                            <button class="btn btn-sm btn-outline-success text-start d-none" id="activateCoordinatorBtn"><i class="bi bi-person-check me-2"></i>Reactivate Account</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="CoordinatorCreatedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-profile-uuid="">
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
                                            <h5 class="mb-0 fw-bold text-success">Coordinator account created</h5>
                                            <p class="text-muted mb-3">Account created for <span id="createdCoordinatorName"></span>. Share this temporary password securely.</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3" id="createdCoordinatorTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportCoordinatorPdfBtn">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                                        </button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#CreateCoordinatorModal">
                                            <i class="bi bi-person-plus me-2"></i>Create Another Coordinator
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="ResetCoordinatorPasswordSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
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
                                            <p class="text-muted mb-3">Password has been reset for <span id="resetCoordinatorSuccessName"></span>. The new temporary password is:</p>
                                            <small class="text-success-emphasis mb-1">Temporary Password</small>
                                            <span class="badge text-bg-dark bg-opacity-75 fs-6 py-3" id="resetCoordinatorSuccessTempPassword"></span>
                                        </div>
                                    </div>
                                    <div class="hstack gap-3">
                                        <button class="btn btn-sm btn-outline-secondary" id="exportResetCoordinatorPdfBtn">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                                        </button>
                                        <button class="btn btn-sm btn-primary ms-auto" data-bs-dismiss="modal"><i class="bi bi-check-circle me-2"></i>Done</button>
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
                        <h4 class="" id="dashboardTitle">Coordinator Accounts</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="coordinatorCount">0</span> coordinator accounts</p>
                        <a href="javascript:void(0)" class="small text-decoration-none" id="startCoordinatorTourLink">
                            <i class="bi bi-signpost-split me-1"></i>Start quick tour
                        </a>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addCoordinatorOpenBtn" data-bs-toggle="modal" data-bs-target="#CreateCoordinatorModal">
                        <i class="bi bi-person-plus me-1"></i>
                        Add Coordinator
                    </button>
                </div>

                <div class="container">
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-12 col-sm-6 col-lg-5">
                            <label for="coordinatorSearchInput" class="form-label small fw-semibold text-muted mb-2">Search</label>
                            <div class="input-group rounded-3 overflow-hidden">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-blur-5 bg-semi-transparent border-0 shadow-none" placeholder="Search by name, email, employee ID..." id="coordinatorSearchInput">
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="departmentFilter" class="form-label small fw-semibold text-muted mb-2">Department</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="departmentFilter">
                                <option value="" class="CustomOption" selected>All Departments</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <label for="coordinatorStatusFilter" class="form-label small fw-semibold text-muted mb-2">Status</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="coordinatorStatusFilter">
                                <option value="" class="CustomOption" selected>All Statuses</option>
                                <option value="active" class="CustomOption">Active</option>
                                <option value="never_logged_in" class="CustomOption">Never Logged In</option>
                                <option value="inactive" class="CustomOption">Inactive</option>
                            </select>
                        </div>
                        <div class="col-12 col-lg-1 d-flex">
                            <button class="btn btn-outline-secondary border rounded-3 shadow-none w-100 d-none" id="clearCoordinatorFiltersBtn" data-bs-toggle="tooltip" data-bs-placement="top" title="Clear filters"><i class="bi bi-x-circle-fill"></i></button>
                        </div>
                    </div>

                    <div class="table-responsive table-scroll-10 rounded-3">
                        <table class="table table-sm table-borderless table-hover table-striped align-middle mb-0" id="coordinatorsTable">
                            <thead class="bg-blur-5 bg-semi-transparent border-0">
                                <tr class="text-muted border-0">
                                    <th scope="col" class="ps-4 py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Coordinator</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-md-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Employee ID</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent d-none d-lg-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Department</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center d-none d-xl-table-cell border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Assigned Students</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Status</th>
                                    <th scope="col" class="py-3 fw-bold text-uppercase text-secondary-emphasis bg-blur-5 bg-semi-transparent text-center pe-4 border-0" style="width: 50px;"></th>
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
