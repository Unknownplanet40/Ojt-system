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
                                    <div class="col-md-8">
                                        <div class="hstack">
                                            <img src="https://placehold.co/64x64/C1C1C1/000000/png?text=RJ&font=poppins"
                                                alt="profile picture" class="rounded-circle m-2 mx-3 me-4"
                                                style="width: 64px; height: 64px;" id="ProfilePicture">
                                            <div class="vstack">
                                                <h5 class="card-title mb-0" id="FullName">John Michael Doe</h5>
                                                <p class="card-text">
                                                    <small><?= $_SESSION['user']['email'] ?>
                                                        &bull; <span id="Department"></span></small>
                                                </p>
                                                <div class="hstack gap-2">
                                                    <small class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle rounded-pill"><?= ucfirst($_SESSION['user']['role']) ?></small>
                                                    <small class="badge bg-success-subtle text-success-emphasis border border-success-subtle rounded-pill" id="StatusBadge"><span id="Status">Active</span></small>
                                                    <small class="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle rounded-pill" id="EmployeeIDBadge"><span id="EmployeeID">EMP-0000-00000000</span></small>
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
                    <div class="col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h6 class="card-title">My students</h6>
                                <p class="card-text display-6 fw-bold mb-0" id="StudentCount">0</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h6 class="card-title">Active batches</h6>
                                <p class="card-text fw-bold" id="activeBatch"></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h6 class="card-title">last Login</h6>
                                <p class="card-text fw-bold" id="lastLogin">N/A</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <h6 class="card-title">Personal Information</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Employee ID:</strong>
                                        <span id="PIEmployeeID"></span>
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Full Name:</strong>
                                        <span id="PIFullName"></span>
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Department:</strong>
                                        <span id="PIDepartment"></span>
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Mobile Number:</strong>
                                        <span id="PIMobileNumber"></span>
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Email:</strong>
                                        <span id="Email"><?= $_SESSION['user']['email'] ?></span>
                                    </li>
                                    <li class="list-group-item bg-transparent">
                                        <strong class="text-muted w-50 d-inline-block">Account Created:</strong>
                                        <span id="PIAccountCreated"></span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-blur-5 bg-semi-transparent h-100 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <div class="hstack">
                                    <h6 class="card-title">My Students &bull; <small class="text-muted" id="BatchInfo"></small></h6>
                                </div>
                                <ul class="list-group list-group-flush" id="studentList" style="max-height: 265px; overflow-y: auto;">
                                    <?php for ($i = 0; $i < 5; $i++) : ?>
                                    <li class="list-group-item bg-transparent">
                                        <div class="hstack">
                                            <img src="https://placehold.co/40x40/C1C1C1/000000/png?text=JD&font=poppins"
                                                alt="profile picture" class="rounded-circle me-3"
                                                style="width: 40px; height: 40px;">
                                            <div>
                                                <div class="fw-bold">Jane Doe</div>
                                                <small class="text-muted">BS Computer Science, 3rd Year</small>
                                            </div>
                                            <span class="badge bg-primary-subtle text-primary-emphasis border border-primary-subtle rounded-pill ms-auto align-self-start">Active</span>
                                        </div>
                                    </li>
                                    <?php endfor; ?>
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