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

$CurrentPage = "viewProfile";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/ProfileScripts/CoordinatorViewProfileScript.js"></script>
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
            <?php require_once "../../Components/Header_Coordinator.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row row-cols-1 row-cols-md-3 g-3">
                    <div class="col-md-12">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col-12 col-lg-8">
                                        <div class="d-flex flex-column flex-sm-row align-items-center align-items-sm-start gap-3">
                                            <img src="https://placehold.co/64x64/C1C1C1/000000/png?text=RJ&font=poppins"
                                                alt="profile picture"
                                                class="rounded-circle border border-2 border-light-subtle shadow-sm flex-shrink-0"
                                                style="width: clamp(56px, 16vw, 72px); height: clamp(56px, 16vw, 72px);"
                                                id="ProfilePicture">

                                            <div class="w-100 text-center text-sm-start">
                                                <h5 class="card-title mb-1" id="FullName">John Michael Doe</h5>
                                                <p class="card-text mb-2">
                                                    <small class="text-muted d-inline-flex flex-wrap justify-content-center justify-content-sm-start align-items-center gap-1">
                                                        <span class="text-break"><?= htmlspecialchars($_SESSION['user']['email']) ?></span>
                                                        <span class="d-none d-sm-inline">&bull;</span>
                                                        <span id="Department"></span>
                                                    </small>
                                                </p>

                                                <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-sm-start">
                                                    <small class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle rounded-pill px-2 py-1">
                                                        <?= ucfirst($_SESSION['user']['role']) ?>
                                                    </small>
                                                    <small class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill px-2 py-1" id="StatusBadge">
                                                        <span id="Status">Active</span>
                                                    </small>
                                                    <small class="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle rounded-pill px-2 py-1" id="EmployeeIDBadge">
                                                        <span id="EmployeeID">EMP-0000-00000000</span>
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 d-flex justify-content-end align-items-start">
                                        <div class="hstack gap-2 justify-content-end">
                                            <button class="btn btn-sm btn-outline-secondary border-light text-light"
                                                id="changepasswordBtn">
                                                <i class="bi bi-key me-1"></i>
                                                Change Password
                                            </button>
                                            <button class="btn btn-sm btn-outline-secondary border-light text-light"
                                                id="editprofileBtn">
                                                <i class="bi bi-pencil me-1"></i>
                                                Edit Profile
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-light border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body d-flex flex-column justify-content-between p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-muted small text-uppercase fw-semibold">My Students</div>
                                    <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-people-fill"></i>
                                    </div>
                                </div>
                                <p class="card-text display-6 fw-bold mb-0 lh-1" id="StudentCount">0</p>
                                <small class="text-muted mt-2">Total assigned students</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-light border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body d-flex flex-column justify-content-between p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-muted small text-uppercase fw-semibold">Active Batches</div>
                                    <div class="rounded-circle bg-success-subtle text-success-emphasis d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-calendar2-check-fill"></i>
                                    </div>
                                </div>
                                <p class="card-text fs-2 fw-bold mb-0 lh-1" id="activeBatch"></p>
                                <small class="text-muted mt-2">Currently active batches</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-light border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body d-flex flex-column justify-content-between p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div class="text-muted small text-uppercase fw-semibold">Last Login</div>
                                    <div class="rounded-circle bg-dark-subtle text-dark-emphasis d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-clock-history"></i>
                                    </div>
                                </div>
                                <p class="card-text fs-5 fw-semibold mb-0 lh-sm" id="lastLogin">N/A</p>
                                <small class="text-muted mt-2">Most recent account access</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4 shadow-sm border border-light border-opacity-10"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-4">
                                    <div>
                                        <h6 class="card-title mb-1">Personal Information</h6>
                                        <small class="text-muted">Your account details at a glance</small>
                                    </div>
                                    <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-inline-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="bi bi-person-vcard"></i>
                                    </div>
                                </div>

                                <div class="list-group list-group-flush rounded-3 overflow-hidden">
                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-upc-scan fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Employee ID</small>
                                                <span class="fw-semibold" id="PIEmployeeID"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-person-fill fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Full Name</small>
                                                <span class="fw-semibold" id="PIFullName"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-building fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Department</small>
                                                <span class="fw-semibold" id="PIDepartment"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-phone fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Mobile Number</small>
                                                <span class="fw-semibold" id="PIMobileNumber"></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-envelope fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Email</small>
                                                <span class="fw-semibold text-break" id="Email"><?= htmlspecialchars($_SESSION['user']['email']) ?></span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="list-group-item bg-transparent px-0 py-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="text-primary-emphasis">
                                                <i class="bi bi-calendar-event fs-5"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <small class="text-muted d-block">Account Created</small>
                                                <span class="fw-semibold" id="PIAccountCreated"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <div class="hstack">
                                    <h6 class="card-title">My Students &bull; <small class="text-muted" id="BatchInfo"></small></h6>
                                </div>
                                <ul class="list-group list-group-flush" id="studentList" style="max-height: 512px; overflow-y: auto;">
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