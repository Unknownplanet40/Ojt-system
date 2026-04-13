<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
?>


<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/ProfileScripts/StudentProfileScript.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="admin-profile-page">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="admin-profile-main container w-100 d-flex justify-content-center align-items-center z-1">
        <div class="admin-profile-card card rounded-3 bg-blur-3 bg-semi-transparent w-100" style="--blur-lvl: 0.50;">
            <div class="card-body">
                <small class="text-muted" id="backToDashboardLink"><a href="./StudentsDashboard.php"
                        class="text-decoration-none text-muted">&larr; Back</a></small>
                <div class="hstack mb-4">
                    <button class="btn btn-sm btn-outline-secondary p-1 px-2 me-3 d-none" id="backBtn"><i
                            class="bi bi-arrow-left"></i> Back</button>
                    <small class="fw-bold"><?= $LongTitle ?> <span
                            class="badge bg-warning-subtle bg-opacity-50 text-warning-emphasis rounded-pill px-2 fw-medium">Student</span></small>
                    <small class="text-muted ms-auto">Step 1 of
                        <?= isset($_SESSION['user']['require_password_change']) && $_SESSION['user']['require_password_change'] ? '2' : '1' ?></small>
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
                    <small class="mb-0" id="profileInfoText">Complete your profile to unlock your OJT dashboard. This
                        only takes a minute.</small>
                </div>
                <div class="card bg-semi-transparent mb-3" style="--blur-lvl: 0.70;">
                    <div class="card-body p-3 px-4">
                        <div class="vstack">
                            <small class="fw-medium">Personal information</small>
                            <span class="text-muted"><small>Fill in your details exactly as they appear on your school
                                    ID.</small></span>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 g-3 mt-2">
                            <div class="col-md-8">
                                <div class="hstack">
                                    <img src="https://placehold.co/64x64/483a0f/c6983d/png?text=<?= isset($_SESSION['user_initials']) ? $_SESSION['user_initials'] : 'NA' ?>&font=poppins"
                                        alt="" class="rounded-circle mt-2" id="adminProfilePhoto"
                                        style="width: 64px; height: 64px; object-fit: cover;">
                                    <div class="vstack ms-3 justify-content-center">
                                        <small
                                            class="fw-medium"><?= $_SESSION['user_email'] ?></small>
                                        <small
                                            class="text-muted"><?= isset($_SESSION['user_role']) ? ucfirst($_SESSION['user_role']) : 'Unknown Role' ?>
                                            account.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 gap-2 d-flex align-items-center justify-content-end">
                                <button class="btn btn-sm btn-primary p-1 px-2 text-nowrap" id="saveProfileBtn"> Save &
                                    Continue</button>
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2 text-nowrap" id="uploadPhotoBtn"
                                    onclick="$('#photoInput').click();">Upload Photo</button>
                                <input type="file" id="photoInput" accept="image/*" class="d-none">
                                <!-- change password button will only   show if user is not required to change password on next login -->
                                <?php if (!isset($_SESSION['user']['require_password_change']) || !$_SESSION['user']['require_password_change']): ?>
                                <button class="btn btn-sm btn-outline-secondary p-1 px-2 text-nowrap" id="changePasswordBtn">Change
                                    Password</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <label for="firstName" class="form-label fw-500 mb-2">First Name <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="firstName" placeholder="John">
                        </div>
                        <div class="col-md-6">
                            <label for="lastName" class="form-label fw-500 mb-2">Last Name <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="lastName" placeholder="Doe">
                        </div>
                        <div class="col-md-6">
                            <label for="middleName" class="form-label fw-500 mb-2">Middle Name</label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="middleName" placeholder="Optional">
                        </div>
                        <div class="col-md-6">
                            <label for="studentNumber" class="form-label fw-500 mb-2">Student Number <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="studentNumber" placeholder="2024-001">
                        </div>
                        <div class="col-md-6">
                            <label for="contactNumber" class="form-label fw-500 mb-2">Contact Number <span
                                    class="text-danger">*</span></label>
                            <input type="tel"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="contactNumber" placeholder="09XX-XXX-XXXX">
                        </div>
                        <div class="col-md-6">
                            <label for="emergencyContactNumber" class="form-label fw-500 mb-2">Emergency Contact
                                Number <span class="text-danger">*</span></label>
                            <input type="tel"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="emergencyContactNumber" placeholder="09XX-XXX-XXXX">
                        </div>
                        <div class="col-md-12">
                            <label for="homeAddress" class="form-label fw-500 mb-2">Home Address <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="homeAddress" rows="3" placeholder="Street address, city, province"></textarea>
                        </div>
                        <div class="col-md-12">
                            <label for="emergencyContactName" class="form-label fw-500 mb-2">Emergency Contact Name
                                <span class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none"
                                id="emergencyContactName" placeholder="Full name of emergency contact">
                        </div>
                    </div>
                </div>
            </div>
            <div class="card bg-semi-transparent mb-3" style="--blur-lvl: 0.70;">
                <div class="card-body p-3 px-4">
                    <div class="vstack gap-2 mb-3">
                        <h6 class="fw-600 mb-0">Academic Information</h6>
                        <small class="text-muted">Pre-filled by your coordinator. Review and confirm.</small>
                    </div>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="program" class="form-label fw-500 mb-2">Program</label>
                            <select class="form-select form-select-sm bg-semi-transparent border shadow-none"
                                id="program" disabled>
                                <option value="" class="CustomOption" selected hidden disabled>Already set by
                                    coordinator</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-4">
                            <label for="yearLevel" class="form-label fw-500 mb-2">Year Level</label>
                            <select class="form-select form-select-sm bg-semi-transparent border shadow-none"
                                id="yearLevel" disabled>
                                <option class="CustomOption" selected hidden disabled value="">Already set by
                                    coordinator</option>
                                <option value="1">1st Year</option>
                                <option value="2">2nd Year</option>
                                <option value="3">3rd Year</option>
                                <option value="4">4th Year</option>
                            </select>
                        </div>
                        <div class="col-12 col-sm-6 col-md-2">
                            <label for="section" class="form-label fw-500 mb-2">Section <span
                                    class="text-danger">*</span></label>
                            <input type="text"
                                class="form-control form-control-sm bg-semi-transparent border shadow-none" id="section"
                                placeholder="e.g. A, B, C">
                        </div>
                    </div>
                </div>
            </div>
            <div class="text-center">
                <small class="text-muted"><a href="javascript:void(0);" id="startTourLink">Take a tour of the
                        profile setup</a></small>
            </div>
        </div>
    </div>
    </div>
</body>

</html>