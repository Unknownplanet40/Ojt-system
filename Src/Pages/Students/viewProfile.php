<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'student') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "viewProfile";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/StudentDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/ProfileScripts/StudentViewProfileScript.js"></script>
    <title><?= $ShortTitle ?> - My Profile</title>
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
    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <div class="col-md-12">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="row g-4 align-items-center">
                                    <div class="col-12 col-lg-8">
                                        <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-4">
                                            <div class="position-relative">
                                                <img src="" alt="profile picture"
                                                    class="rounded-circle border border-3 border-body border-opacity-25 shadow-lg flex-shrink-0 object-fit-cover"
                                                    style="width: 100px; height: 100px;"
                                                    id="ProfilePicture">
                                            </div>

                                            <div class="w-100 text-center text-sm-start">
                                                <h3 class="fw-bold mb-1" id="FullName">---</h3>
                                                <p class="text-body-secondary mb-3 d-flex align-items-center justify-content-center justify-content-sm-start gap-2">
                                                    <i class="bi bi-mortarboard"></i>
                                                    <span id="Program">---</span>
                                                </p>

                                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                                                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2" id="RoleBadge">
                                                        Student
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                                        <i class="bi bi-check-circle-fill me-1"></i>
                                                        <span id="Status">Active</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-lg-4 d-flex justify-content-center justify-content-lg-end align-items-start gap-2">
                                        <button class="btn btn-outline-primary rounded-pill px-3" id="editprofileBtn">
                                            <i class="bi bi-pencil-square me-2"></i>Edit Profile
                                        </button>
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="changepasswordBtn">
                                            <i class="bi bi-shield-lock me-2"></i>Security
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">Hours Rendered</div>
                                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-clock fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0" id="HoursRendered">0</h2>
                                <p class="text-body-secondary small mt-2 mb-0">Total approved OJT hours</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">Requirements</div>
                                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-file-earmark-check fs-4"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold mb-0" id="ReqProgress">0/0</h4>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-success" id="ReqProgressBar" style="width: 0%"></div>
                                </div>
                                <p class="text-body-secondary small mt-1 mb-0">Submission progress</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">OJT Status</div>
                                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-lightning fs-4"></i>
                                    </div>
                                </div>
                                <h4 class="fw-bold mb-0" id="OJTStatusBadge">---</h4>
                                <p class="text-body-secondary small mt-2 mb-0">Current internship status</p>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Info -->
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="rounded-3 bg-warning bg-opacity-10 text-warning p-2">
                                        <i class="bi bi-person-badge fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Account Details</h5>
                                        <small class="text-body-secondary">Personal and contact information</small>
                                    </div>
                                </div>

                                <div class="list-group list-group-flush rounded-3 overflow-hidden border-0">
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Full Name</small>
                                                <span class="fw-medium" id="PIFullName">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-upc-scan"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Student Number</small>
                                                <span class="fw-medium" id="PIStudentNumber">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-telephone"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Contact Number</small>
                                                <span class="fw-medium" id="PIMobileNumber">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-envelope"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Email Address</small>
                                                <span class="fw-medium text-break" id="Email">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-0">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-calendar-plus"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Member Since</small>
                                                <span class="fw-medium" id="PIAccountCreated">---</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- OJT Details -->
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-3 mb-4">
                                    <div class="rounded-3 bg-info bg-opacity-10 text-info p-2">
                                        <i class="bi bi-briefcase fs-4"></i>
                                    </div>
                                    <div>
                                        <h5 class="fw-bold mb-0">Internship Details</h5>
                                        <small class="text-body-secondary">Current OJT assignment and supervisor</small>
                                    </div>
                                </div>

                                <div class="vstack gap-3">
                                    <div class="p-4 rounded-4 bg-body-secondary bg-opacity-25 border border-body border-opacity-10 text-center mb-2">
                                        <div class="rounded-circle bg-body-secondary d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                            <i class="bi bi-building fs-3 text-primary"></i>
                                        </div>
                                        <h5 class="fw-bold mb-1" id="Company">Not Assigned</h5>
                                        <p class="text-body-secondary small mb-0">Host Training Establishment</p>
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 rounded-3 bg-body-secondary bg-opacity-25 border border-body border-opacity-10">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">Supervisor</small>
                                                <span class="fw-medium" id="Supervisor">---</span>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="p-3 rounded-3 bg-body-secondary bg-opacity-25 border border-body border-opacity-10">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold mb-1" style="font-size: 0.65rem;">OJT Status</small>
                                                <span class="fw-medium" id="OJTStatusDetail">---</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-auto p-4 rounded-4 bg-primary bg-opacity-10 border border-primary border-opacity-20 mt-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; min-width: 40px;">
                                                <i class="bi bi-info-circle"></i>
                                            </div>
                                            <p class="small mb-0 fw-medium">Keep your profile updated for accurate tracking of your internship progress.</p>
                                        </div>
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
