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

$CurrentPage = "MyStudents";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/MyStudentsScripts.js"></script>
    <title><?= $ShortTitle ?> - My Students</title>
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
                <div class="row g-3 mb-4 align-items-stretch">
                    <div class="col-12 col-xl-8">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-people-fill fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-primary small">Student Directory</p>
                                        <h4 class="mb-1 fw-semibold text-break">Manage and Monitor Your Assigned Students</h4>
                                        <p class="mb-0 text-muted small">View profiles, track progress, and manage details for all students under your supervision.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#CreateStudentModal">
                                            <i class="bi bi-person-plus me-2"></i>Add Student
                                        </button>
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="refreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-xl-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="row row-cols-2 g-3 small">
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Total Students</div><div class="fw-semibold fs-5" id="totalStudentsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Active OJT</div><div class="fw-semibold fs-5" id="activeStudentsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Pending Apps</div><div class="fw-semibold fs-5" id="pendingAppsCount">0</div></div></div>
                                    <div class="col"><div class="rounded-3 border p-3 h-100"><div class="text-muted">Completed</div><div class="fw-semibold fs-5" id="completedStudentsCount">0</div></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-4">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="searchInput">Search Students</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent shadow-none" id="searchInput" placeholder="Search by name, student no, or email..." style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="programFilter">Program</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent shadow-none" id="programFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option value="">All Programs</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="statusFilter">Status</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent shadow-none" id="statusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option class="CustomOption" value="">All Statuses</option>
                                    <option class="CustomOption" value="active">Active</option>
                                    <option class="CustomOption" value="never_logged_in">Never Logged In</option>
                                    <option class="CustomOption" value="inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-12 col-lg-2">
                                <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="studentGrid" class="row g-3 g-md-4">
                </div>
                <div class="p-5 text-center d-none" id="emptyState">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                        <i class="bi bi-people fs-4"></i>
                    </div>
                    <h5 class="mb-2 text-light">No students found</h5>
                    <p class="text-muted mb-0">Try adjusting your search or filters.</p>
                </div>
            </div>

            <!-- Modals -->
            <div class="modal fade" id="CreateStudentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="mb-5">
                                <div class="hstack gap-3 align-items-center">
                                    <div class="vstack gap-1 text-white">
                                        <h5 class="modal-title fw-bold mb-0 fs-5">Add Student</h5>
                                        <p class="text-white-50 small mb-0">Create a new student account and profile</p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-light ms-auto flex-shrink-0" id="bulkimportBtn"><i class="bi bi-upload me-2"></i>Bulk Import</button>
                                    <button type="button" class="btn btn-sm btn-outline-light ms-2 flex-shrink-0" data-bs-dismiss="modal"><i class="bi bi-arrow-left me-2"></i>Back</button>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title fw-bold mb-1 fs-6 text-white">Account Information</h6>
                                        <small class="text-white-50 d-block">A temporary password will be generated automatically.</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="email" class="form-control bg-transparent border-0 shadow-none text-white" id="studentEmail" placeholder="Email Address">
                                                <label for="studentEmail" class="text-white-50">Email Address<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="studentNumber" placeholder="Student Number">
                                                <label for="studentNumber" class="text-white-50">Student Number<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <h6 class="card-title fw-bold mb-4 fs-6 text-white">Personal Information</h6>
                                    <div class="row g-3 text-white">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="lastName" placeholder="Last Name">
                                                <label for="lastName" class="text-white-50">Last Name<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="firstName" placeholder="First Name">
                                                <label for="firstName" class="text-white-50">First Name<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="middleName" placeholder="Middle Name">
                                                <label for="middleName" class="text-white-50">Middle Name</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title fw-bold mb-1 fs-6 text-white">Academic Information</h6>
                                        <small class="text-white-50 d-block">Assigned to your supervision and active batch automatically.</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <select class="form-select bg-transparent border-0 shadow-none text-white" id="programSelect"></select>
                                                <label for="programSelect" class="text-white-50">Program<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <select class="form-select bg-transparent border-0 shadow-none text-white" id="yearLevelSelect">
                                                    <option value="" class="bg-dark" selected disabled hidden>Select year level</option>
                                                    <option value="1" class="bg-dark">1st Year</option>
                                                    <option value="2" class="bg-dark">2nd Year</option>
                                                    <option value="3" class="bg-dark">3rd Year</option>
                                                    <option value="4" class="bg-dark">4th Year</option>
                                                </select>
                                                <label for="yearLevelSelect" class="text-white-50">Year Level<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button class="btn btn-primary py-2 px-5 rounded-pill" id="createStudentBtn">
                                <i class="bi bi-person-plus me-2"></i>Create Account
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Created Success Modal -->
            <div class="modal fade" id="StudentCreatedModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5 text-center">
                            <div class="mx-auto mb-4 d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-20 text-success" style="width: 80px; height: 80px;">
                                <i class="bi bi-check2-all fs-1"></i>
                            </div>
                            <h4 class="text-white fw-bold mb-2">Student Account Created!</h4>
                            <p class="text-white-50 mb-4">The account for <span id="createdStudentName" class="text-white fw-semibold"></span> is ready. Please share the temporary password below:</p>
                            
                            <div class="bg-blur-5 bg-semi-transparent rounded-4 p-4 mb-4 border border-light border-opacity-10">
                                <small class="text-white-50 d-block mb-1 text-uppercase fw-semibold letter-spacing-1">Temporary Password</small>
                                <div class="fs-2 fw-mono text-primary letter-spacing-2" id="createdStudentTempPassword">------</div>
                            </div>

                            <div class="vstack gap-2">
                                <button class="btn btn-outline-light rounded-pill py-2" id="exportPdfBtn"><i class="bi bi-file-earmark-pdf me-2"></i>Export as PDF</button>
                                <button class="btn btn-primary rounded-pill py-2" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reset Password Modal -->
            <div class="modal fade" id="ResetPasswordModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5 text-center text-white">
                            <div class="mx-auto mb-4 d-inline-flex align-items-center justify-content-center rounded-circle bg-warning bg-opacity-20 text-warning" style="width: 80px; height: 80px;">
                                <i class="bi bi-key fs-1"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Reset Password?</h4>
                            <p class="text-white-50 mb-4">Are you sure you want to reset the password for <span id="resetPasswordStudentName" class="fw-semibold text-white"></span>? A new temporary password will be generated.</p>
                            <div class="hstack gap-2 justify-content-center">
                                <button class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                <button class="btn btn-warning rounded-pill px-4" id="resetPasswordBtn">Confirm Reset</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password Reset Success Modal -->
            <div class="modal fade" id="ResetPasswordSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5 text-center text-white">
                            <div class="mx-auto mb-4 d-inline-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-20 text-success" style="width: 80px; height: 80px;">
                                <i class="bi bi-shield-check fs-1"></i>
                            </div>
                            <h4 class="fw-bold mb-2">Password Reset Successful!</h4>
                            <p class="text-white-50 mb-4">The new password for <span id="resetPasswordSuccessStudentName" class="fw-semibold text-white"></span> is:</p>
                            <div class="bg-blur-5 bg-semi-transparent rounded-4 p-4 mb-4 border border-light border-opacity-10">
                                <div class="fs-2 fw-mono text-success letter-spacing-2" id="resetPasswordSuccessTempPassword">------</div>
                            </div>
                            <div class="vstack gap-2">
                                <button class="btn btn-outline-light rounded-pill py-2" id="exportResetPdfBtn"><i class="bi bi-file-earmark-pdf me-2"></i>Export PDF</button>
                                <button class="btn btn-primary rounded-pill py-2" data-bs-dismiss="modal">Done</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Import Success Modal -->
            <div class="modal fade" id="bulkSuccessModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="alert bg-success-subtle border-0 rounded-4 d-flex align-items-center gap-3 mb-4">
                                <i class="bi bi-check-circle-fill text-success fs-3"></i>
                                <div>
                                    <h5 class="mb-1 fw-bold text-success"><span id="bulkAccountsCreatedCount">0</span> Accounts Created successfully</h5>
                                    <p class="mb-0 text-success-emphasis small">All students have been assigned <span id="bulkBatchLabelCurrent">Current Batch</span> and their pre-OJT requirements have been initialized.</p>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border mb-3" style="--blur-lvl: 5;">
                                <div class="card-body p-3 p-md-4">
                                    <div class="row g-2 mb-3">
                                        <div class="col-12 col-md-6">
                                            <div class="card bg-success-subtle border border-success-subtle rounded-4 text-center h-100">
                                                <div class="card-body py-3">
                                                    <h4 class="fw-bold text-success mb-1" id="bulkSuccessCreatedCount">0</h4>
                                                    <p class="small mb-0 text-success-emphasis">Successfully Created</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="card bg-danger-subtle border border-danger-subtle rounded-4 text-center h-100">
                                                <div class="card-body py-3">
                                                    <h4 class="fw-bold text-danger mb-1" id="bulkSuccessFailedCount">0</h4>
                                                    <p class="small mb-0 text-danger-emphasis">Failed to Create</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="bulkFailedRowsContainer" class="mt-3 d-none">
                                        <div class="alert alert-danger border-0 rounded-4 mb-2">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-exclamation-triangle-fill"></i>
                                                <p class="mb-0 small fw-semibold">Some rows failed during account creation.</p>
                                                <button class="btn btn-sm btn-link text-decoration-none ms-auto p-0" id="bulkToggleFailedRowsBtn" type="button">Show details</button>
                                            </div>
                                        </div>
                                        <div class="table-responsive rounded-3 border d-none" id="bulkFailedRowsDetails">
                                            <table class="table table-sm table-striped table-hover align-middle mb-0">
                                                <thead class="bg-blur-5 bg-semi-transparent border-0">
                                                    <tr class="text-muted border-0">
                                                        <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Name</th>
                                                        <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Email</th>
                                                        <th class="py-2 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Reason</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="bulkFailedRowsTableBody"></tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border" style="--blur-lvl: 5;">
                                <div class="card-body p-3 p-md-4">
                                    <div class="mb-4">
                                        <h6 class="fw-bold text-white mb-1">Export Credentials</h6>
                                        <small class="text-white-50 d-block">Download the credentials sheet to distribute to your students.</small>
                                    </div>
                                    <div class="vstack gap-3 mb-4">
                                        <div class="card-body border rounded-4 p-3">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-file-earmark-pdf-fill text-muted fs-4"></i>
                                                <div class="vstack gap-0 flex-grow-1">
                                                    <p class="mb-0 fw-semibold small text-white">PDF Credentials Sheet</p>
                                                    <small class="text-white-50">Print-ready document with credentials</small>
                                                </div>
                                                <button class="btn btn-sm bg-secondary-subtle border text-body px-3 py-2 rounded-3 text-nowrap ms-auto" id="bulkPdfCredentialsBtn">
                                                    <i class="bi bi-download me-2"></i>Download
                                                </button>
                                            </div>
                                        </div>
                                        <div class="card-body border rounded-4 p-3">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-file-earmark-spreadsheet-fill text-muted fs-4"></i>
                                                <div class="vstack gap-0 flex-grow-1">
                                                    <p class="mb-0 fw-semibold small text-white">CSV Export</p>
                                                    <small class="text-white-50">Spreadsheet with all created accounts</small>
                                                </div>
                                                <button class="btn btn-sm bg-secondary-subtle border text-body px-3 py-2 rounded-3 text-nowrap ms-auto" id="bulkCsvCredentialsBtn">
                                                    <i class="bi bi-download me-2"></i>Download
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="table-responsive rounded-3 border bg-dark bg-opacity-50" style="max-height: 400px;">
                                        <table class="table table-sm table-borderless table-hover align-middle mb-0 text-white-50" id="bulkCreatedAccountsTable">
                                            <thead class="bg-blur-5 bg-semi-transparent border-0 sticky-top">
                                                <tr class="text-muted border-0">
                                                    <th class="py-3 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Name</th>
                                                    <th class="py-3 fw-bold text-uppercase text-secondary-emphasis border-0 d-none d-sm-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Email</th>
                                                    <th class="py-3 fw-bold text-uppercase text-secondary-emphasis border-0 d-none d-md-table-cell" style="font-size: 0.70rem; letter-spacing: 0.6px;">Student No.</th>
                                                    <th class="py-3 fw-bold text-uppercase text-secondary-emphasis border-0" style="font-size: 0.70rem; letter-spacing: 0.6px;">Temp Password</th>
                                                </tr>
                                            </thead>
                                            <tbody class="border-0"></tbody>
                                        </table>
                                    </div>

                                    <div class="hstack gap-3 justify-content-end mt-4 pt-3 border-top border-light border-opacity-10">
                                        <button class="btn btn-outline-light rounded-pill px-4 py-2" id="bulkViewAllStudentsBtn">
                                            <i class="bi bi-eye me-2"></i>View All Students
                                        </button>
                                        <button class="btn btn-primary rounded-pill px-4 py-2 text-nowrap" id="bulkImportNewBatchBtn">
                                            <i class="bi bi-file-earmark-plus me-2"></i>Import New Batch
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bulk Import Modal -->
            <div class="modal fade" id="bulkCreationModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg text-white" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="hstack gap-3 mb-4">
                                <div class="rounded-circle bg-primary bg-opacity-20 text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-file-earmark-spreadsheet fs-4 text-primary-emphasis"></i>
                                </div>
                                <h4 class="mb-0 fw-bold">Bulk Student Import</h4>
                                <button type="button" class="btn-close btn-close-white ms-auto shadow-none" data-bs-dismiss="modal"></button>
                            </div>

                            <div class="row g-4">
                                <div class="col-12 col-lg-6">
                                    <div class="card bg-blur-5 bg-semi-transparent rounded-4 p-4 h-100 border border-light border-opacity-10 shadow-sm" style="background: rgba(255, 255, 255, 0.03);">
                                        <h6 class="fw-bold mb-3"><i class="bi bi-1-circle me-2"></i>Step 1: Get the Template</h6>
                                        <p class="text-white-50 small mb-4">Download our CSV template to ensure your data matches the system's required format.</p>
                                        <button class="btn btn-outline-primary rounded-pill w-100 py-2" id="downloadTemplateBtn">
                                            <i class="bi bi-download me-2"></i>Download Template
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-6">
                                    <div class="card bg-blur-5 bg-semi-transparent rounded-4 p-4 h-100 border border-light border-opacity-10 shadow-sm" style="background: rgba(255, 255, 255, 0.03);">
                                        <h6 class="fw-bold mb-3"><i class="bi bi-2-circle me-2"></i>Step 2: Upload File</h6>
                                        <p class="text-white-50 small mb-4">Select your completed CSV file. We'll validate the data before importing.</p>
                                        <div class="input-group">
                                            <input type="file" class="form-control bg-transparent border-primary border-opacity-25 text-white shadow-none" id="bulkCsvFile" accept=".csv">
                                            <button class="btn btn-primary px-4" id="uploadCsvBtn">Validate</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Results -->
                            <div id="validationResults" class="mt-4 pt-4 border-top border-light border-opacity-10" style="display: none;">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 p-4 border border-light border-opacity-10 shadow-sm" style="background: rgba(255, 255, 255, 0.03);">
                                    <div class="d-flex align-items-center gap-3 mb-4">
                                        <div class="rounded-circle bg-info bg-opacity-20 text-info d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="bi bi-info-circle text-dark"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold fs-5 text-white">Validation Results</h6>
                                            <p class="text-white-50 small mb-0">Please review the summary below before proceeding.</p>
                                        </div>
                                    </div>

                                    <div class="row g-3 mb-4">
                                        <div class="col-6 col-md-4">
                                            <div class="p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-20 shadow-sm text-center">
                                                <small class="text-info-emphasis d-block mb-1 text-uppercase small fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Total Rows</small>
                                                <div class="h4 mb-0 fw-bold text-info" id="totalRowsCount">0</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-4">
                                            <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-20 shadow-sm text-center">
                                                <small class="text-success-emphasis d-block mb-1 text-uppercase small fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Valid Rows</small>
                                                <div class="h4 mb-0 fw-bold text-success" id="validRowsCount">0</div>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-4">
                                            <div class="p-3 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-20 shadow-sm text-center">
                                                <small class="text-danger-emphasis d-block mb-1 text-uppercase small fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">Error Rows</small>
                                                <div class="h4 mb-0 fw-bold text-danger" id="errorRowsCount">0</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="validationSuccess" class="mb-4" style="display: none;">
                                        <h6 class="text-success small fw-bold mb-3 ms-1"><i class="bi bi-check-circle me-2"></i>Valid Rows (Ready to Import):</h6>
                                        <div id="validList" style="max-height: 250px; overflow-y: auto;"></div>
                                    </div>

                                    <div id="validationErrors" class="mb-4" style="display: none;">
                                        <h6 class="text-danger small fw-bold mb-3 ms-1"><i class="bi bi-exclamation-triangle me-2"></i>Detected Issues:</h6>
                                        <div id="errorList" style="max-height: 250px; overflow-y: auto;"></div>
                                    </div>

                                    <div class="hstack gap-2 justify-content-end pt-3">
                                        <button class="btn btn-outline-light rounded-pill px-4 py-2 border-opacity-25" id="cancelImportBtn">Cancel</button>
                                        <button class="btn btn-success rounded-pill px-4 py-2 shadow-sm" id="confirmImportBtn" disabled>
                                            <i class="bi bi-check-circle me-2"></i>Confirm & Import
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Edit Student Modal -->
            <div class="modal fade" id="EditStudentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
                <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
                    <div class="modal-content bg-blur-10 bg-semi-transparent border-light border-opacity-10 shadow-lg text-white" style="background: rgba(255, 255, 255, 0.05);">
                        <div class="modal-body p-4 p-md-5">
                            <div class="mb-5">
                                <div class="hstack gap-3 align-items-center">
                                    <div class="vstack gap-1 text-white">
                                        <h5 class="modal-title fw-bold mb-0 fs-5">Edit Student</h5>
                                        <p class="text-white-50 small mb-0"><span id="editStudentFullName"></span> &bull; <span id="editStudentNumberDisplay"></span></p>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-light ms-auto flex-shrink-0" data-bs-dismiss="modal"><i class="bi bi-arrow-left me-2"></i>Back</button>
                                </div>
                            </div>

                            <input type="hidden" id="editStudentUuid">
                            <input type="hidden" id="editCoordinatorUuid">

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="mb-4">
                                        <h6 class="card-title fw-bold mb-1 fs-6 text-white">Account Information</h6>
                                        <small class="text-white-50 d-block">Email and student number cannot be changed here.</small>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <p class="form-control bg-transparent border-0 shadow-none text-white mb-0" id="editStudentEmail"></p>
                                                <label class="text-white-50">Email Address</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <p class="form-control bg-transparent border-0 shadow-none text-white mb-0" id="editStudentNumber"></p>
                                                <label class="text-white-50">Student Number</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <h6 class="card-title fw-bold mb-4 fs-6 text-white">Personal Information</h6>
                                    <div class="row g-3 text-white">
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editLastName" placeholder="Last Name">
                                                <label for="editLastName" class="text-white-50">Last Name<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editFirstName" placeholder="First Name">
                                                <label for="editFirstName" class="text-white-50">First Name<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editMiddleName" placeholder="Middle Name">
                                                <label for="editMiddleName" class="text-white-50">Middle Name</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editMobileNumber" placeholder="Mobile Number">
                                                <label for="editMobileNumber" class="text-white-50">Mobile Number</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-12">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <textarea class="form-control bg-transparent border-0 shadow-none text-white" placeholder="Address" id="editAddress" style="height: 80px;"></textarea>
                                                <label for="editAddress" class="text-white-50">Address</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editEmergencyContact" placeholder="Emergency contact">
                                                <label for="editEmergencyContact" class="text-white-50">Emergency Contact</label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editEmergencyContactNumber" placeholder="Emergency contact number">
                                                <label for="editEmergencyContactNumber" class="text-white-50">Emergency Contact Number</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm mb-4" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <h6 class="card-title fw-bold mb-4 fs-6 text-white">Academic Information</h6>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6 col-lg-6">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <select class="form-select bg-transparent border-0 shadow-none text-white" id="editProgramSelect"></select>
                                                <label for="editProgramSelect" class="text-white-50">Program<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6 col-lg-4">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <select class="form-select bg-transparent border-0 shadow-none text-white" id="editYearLevelSelect">
                                                    <option value="" class="bg-dark" selected disabled hidden>Select year level</option>
                                                    <option value="1" class="bg-dark">1st Year</option>
                                                    <option value="2" class="bg-dark">2nd Year</option>
                                                    <option value="3" class="bg-dark">3rd Year</option>
                                                    <option value="4" class="bg-dark">4th Year</option>
                                                </select>
                                                <label for="editYearLevelSelect" class="text-white-50">Year Level<span class="text-danger ms-1">*</span></label>
                                            </div>
                                        </div>
                                        <div class="col-12 col-lg-2">
                                            <div class="form-floating border border-light border-opacity-10 rounded-3">
                                                <input type="text" class="form-control bg-transparent border-0 shadow-none text-white" id="editSection" placeholder="Section">
                                                <label for="editSection" class="text-white-50">Section</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-light border-opacity-10 shadow-sm" style="background: rgba(255, 255, 255, 0.03);">
                                <div class="card-body p-4">
                                    <div class="vstack gap-3">
                                        <p class="text-white-50 small mb-0">Click "Save Changes" to update the student information.</p>
                                        <button class="btn btn-primary py-2 px-5 align-self-start text-nowrap" id="saveEditStudentBtn">
                                            <i class="bi bi-check-circle me-2"></i>Save Changes
                                        </button>
                                    </div>
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
