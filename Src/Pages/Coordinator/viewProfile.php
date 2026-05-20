<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || $_SESSION['user_role'] !== 'coordinator') {
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
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/ProfileScripts/CoordinatorViewProfileScript.js"></script>
    <title><?= $ShortTitle ?> - Coordinator Profile</title>
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
            <?php require_once "../../Components/Header_Coordinator.php" ?>
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
                                                <h3 class="fw-bold mb-1" id="FullName"><?= ucfirst($_SESSION['user_name']) ?></h3>
                                                <p class="text-body-secondary mb-3 d-flex flex-wrap align-items-center justify-content-center justify-content-sm-start gap-2">
                                                    <span class="d-inline-flex align-items-center gap-2 mw-100">
                                                        <i class="bi bi-envelope flex-shrink-0"></i>
                                                        <span id="EmailHeader" class="text-break"><?= htmlspecialchars($_SESSION['user_email']) ?></span>
                                                    </span>
                                                    <span class="d-none d-sm-inline">&bull;</span>
                                                    <span id="DepartmentHeader">---</span>
                                                </p>

                                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                                                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2">
                                                        Coordinator
                                                    </span>
                                                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25 rounded-pill px-3 py-2">
                                                        <i class="bi bi-shield-check me-1"></i>
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
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">My Students</div>
                                    <div class="rounded-3 bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-people-fill fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0" id="StudentCount">0</h2>
                                <p class="text-body-secondary small mt-2 mb-0">Total assigned students</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">Active Batch</div>
                                    <div class="rounded-3 bg-success bg-opacity-10 text-success d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-calendar2-check-fill fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0 lh-1" style="font-size: 1.5rem;" id="activeBatch">---</h2>
                                <p class="text-body-secondary small mt-2 mb-0">Currently active batches</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-body-secondary small text-uppercase fw-bold ls-1">Last Login</div>
                                    <div class="rounded-3 bg-info bg-opacity-10 text-info d-inline-flex align-items-center justify-content-center"
                                        style="width: 45px; height: 45px;">
                                        <i class="bi bi-clock-history fs-4"></i>
                                    </div>
                                </div>
                                <h2 class="fw-bold mb-0 lh-1" style="font-size: 1.25rem;" id="lastLogin">---</h2>
                                <p class="text-body-secondary small mt-2 mb-0">Most recent account access</p>
                            </div>
                        </div>
                    </div>

                    <!-- Personal Information -->
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <h5 class="fw-bold mb-0">Personal Information</h5>
                                        <small class="text-body-secondary">Your account details at a glance</small>
                                    </div>
                                    <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-vcard"></i>
                                    </div>
                                </div>

                                <div class="list-group list-group-flush rounded-3 overflow-hidden border-0">
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-upc-scan"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Employee ID</small>
                                                <span class="fw-medium" id="PIEmployeeID">---</span>
                                            </div>
                                        </div>
                                    </div>
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
                                                <i class="bi bi-building"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Department</small>
                                                <span class="fw-medium" id="PIDepartment">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="rounded-3 bg-body-secondary p-2 text-primary">
                                                <i class="bi bi-telephone"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-body-secondary d-block text-uppercase fw-bold" style="font-size: 0.65rem;">Mobile Number</small>
                                                <span class="fw-medium" id="PIMobileNumber">---</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
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

                    <!-- My Students List -->
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-body border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <h5 class="fw-bold mb-0">My Students</h5>
                                        <small class="text-body-secondary" id="BatchInfo">Recent active trainees</small>
                                    </div>
                                    <div class="rounded-circle bg-info bg-opacity-10 text-info d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-mortarboard"></i>
                                    </div>
                                </div>
                                <ul class="list-group list-group-flush border-0" id="studentList"
                                    style="max-height: 480px; overflow-y: auto;">
                                    <!-- Populated by JS -->
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>