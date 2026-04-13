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
?>


<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/ProfileScripts/CoordinatorProfileScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="admin-profile-page" data-enable-changepassword="<?= isset($_SESSION['user']['require_password_change']) ? 'true' : 'false' ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="admin-profile-main container w-100 d-flex justify-content-center align-items-center z-1">
        <div class="admin-profile-card card rounded-3 bg-blur-3 bg-semi-transparent w-100" style="--blur-lvl: 0.50;">
            <div class="card-body">
                <div class="hstack mb-4">
                    <small class="fw-bold"><?= $LongTitle ?> <span
                            class="badge bg-info bg-opacity-50 text-info-emphasis rounded-pill px-2 fw-medium">Coordinator</span></small>
                    <small class="text-muted ms-auto">Step 1 of 1</small>
                </div>
                <div class="hstack">
                    <small class="fw-medium" id="profileprogressLabel">Profile Setup</small>
                    <small class="text-muted ms-auto" id="profileprogressStatus">0%</small>
                </div>
                <div class="progress w-auto mt-2 mb-3" style="height: 4px;">
                    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0"
                        aria-valuemax="100" id="profileProgressBar"></div>
                </div>
                <div class="alert alert-info mb-3 rounded-3 py-2 border-0" role="alert">
                    <small class="mb-0" id="profileInfoText">Set up your coordinator profile. Students and supervisors will see this information.</small>
                </div>
                <div class="card bg-semi-transparent mb-3" style="--blur-lvl: 0.70;">
                    <div class="card-body p-3 px-4">
                        <div class="vstack">
                            <small class="fw-medium">Administrator information</small>
                            <span class="text-muted"><small id="adminInfoStatus">Basic details for your system admin
                                    account.</small></span>
                        </div>
                        <div class="hstack">
                            <img src="https://placehold.co/64x64?text=Upload+Photo" alt="" class="rounded-circle mt-2"
                                id="adminProfilePhoto" style="width: 64px; height: 64px; object-fit: cover;">
                            <div class="vstack ms-3 justify-content-center">
                                <small class="fw-medium"><?= $_SESSION['user']['email'] ?></small>
                                <small class="text-muted"><?= isset($_SESSION['user']['role']) ? ucfirst($_SESSION['user']['role']) : 'Coordinator' ?></small>
                            </div>
                            <div class="ms-auto vstack gap-2 justify-content-center" style="min-width: 150px;">
                                <button class="btn btn-sm btn-primary p-1" id="saveProfileBtn"> Save & Continue</button>
                                <button class="btn btn-sm btn-outline-secondary p-1" id="uploadPhotoBtn">Upload Photo</button>
                                <input type="file" id="photoInput" accept="image/*" class="d-none">
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <small for="firstName" class="form-label">First Name <span
                                        class="text-danger">*</span></small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="firstName" placeholder="Enter your first name">
                            </div>
                            <div class="col-md-4">
                                <small for="lastName" class="form-label">Last Name <span
                                        class="text-danger">*</span></small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="lastName" placeholder="Enter your last name">
                            </div>
                            <div class="col-md-4">
                                <small for="middleName" class="form-label">Middle Name</small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="middleName" placeholder="Enter your middle name (optional)">
                            </div>
                            <div class="col-md-4">
                                <small for="employeeId" class="form-label">Employee ID <span
                                        class="text-danger">*</span></small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="employeeId" placeholder="EMP-12345">
                            </div>
                            <div class="col-md-4">
                                <small for="department" class="form-label">Department <span
                                        class="text-danger">*</span></small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="department" placeholder="Enter your department">
                            </div>
                            <div class="col-md-4">
                                <small for="contactNumber" class="form-label">Contact Number <span
                                        class="text-danger">*</span></small>
                                <input type="text" class="form-control form-control-sm bg-semi-transparent"
                                    id="contactNumber" placeholder="09XX-XXX-XXXX">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>