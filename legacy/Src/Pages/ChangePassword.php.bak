<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../Assets/SystemInfo.php";
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../libs/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../../libs/aos/css/aos.css" />
    <link rel="stylesheet" href="../../libs/driverjs/css/driver.css" />
    <link rel="stylesheet" href="../../Assets/style/AniBG.css" />
    <link rel="stylesheet" href="../../Assets/style/MainStyle.css" />
    <link rel="manifest" href="../../Assets/manifest.json" />

    <script defer src="../../libs/bootstrap/js/bootstrap.bundle.js"></script>
    <script defer src="../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script defer src="../../libs/aos/js/aos.js"></script>
    <script src="../../libs/driverjs/js/driver.js.iife.js"></script>
    <script src="../../libs/jquery/js/jquery-3.7.1.min.js"></script>
    <script type="module" src="../../Assets/Script/ChangePassword.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page" data-changepasswordmode="<?= isset($_SESSION['user']['mode']) ? $_SESSION['user']['mode'] : 'none' ?>" data-user-uuid="<?= isset($_SESSION['user']['uuid']) ? 'Authenticated' : 'Unauthenticated' ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="container-fluid py-5" style="overflow-y: auto; max-height: 100vh;">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="ForcePasswordChangeCard">
                <span class="mt-3 ms-3 bg-warning-subtle text-warning rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-key-fill text-warning-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <div class="vstack mb-0">
                        <span class="alert alert-warning d-flex align-items-center mb-3 p-3 py-2" role="alert">
                                <small class="fw-medium text-warning-emphasis text-center" style="font-size: 12px;">You must set a new password before continuing.</small>
                        </span>
                        <span class="fw-bold fs-5">Set your password</span>
                    </div>
                    <small class="text-muted">Your account was created with a temporary password. Choose a new one to secure your account.</small>
                    <div class="mb-2 mt-3">
                        <small class="form-label">Temporary password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="tempPassword" name="tempPassword" placeholder="Enter temporary password" required>
                    </div>
                    <div class="mb-2">
                        <small class="form-label">New password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="newPassword" name="newPassword" placeholder="Enter new password" required>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="form-label">Confirm new password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="confirmPassword" name="confirmPassword" placeholder="Confirm new password" required>
                    </div>
                    <div class="mb-2 bg-dark p-3 rounded bg-opacity-75">
                        <div class="vstack ps-3">
                            <span><small class="text-secondary" id="charCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">At least 8 characters</small></span>
                            <span><small class="text-secondary" id="upperCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">At least one uppercase
                                    letter</small></span>
                            <span><small class="text-secondary" id="numberCheck"
                                    style="font-size: 11px;">&#9864;</small> <small class="text-muted"
                                    style="font-size: 11px;">At least one number</small></span>
                            <span><small class="text-secondary" id="specialCheck"
                                    style="font-size: 11px;">&#9864;</small> <small class="text-muted"
                                    style="font-size: 11px;">At least one special character (e.g. !@#$%^&*)</small></span>
                            <span><small class="text-secondary" id="matchCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">Passwords match</small></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100 border-white text-white mt-3" disabled id="SetPasswordBtn">Set password and continue</button>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="VoluntaryPasswordChangeCard">
                <span class="mt-3 ms-3 bg-info-subtle text-info rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-key-fill text-info-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <div class="vstack mb-0">
                        <span class="fw-bold fs-5">Change password</span>
                    </div>
                    <small class="text-muted">Enter your current password then choose a new one.</small>
                    <div class="mb-2 mt-3">
                        <small class="form-label">Current password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="currentPassword" name="currentPassword" placeholder="Enter current password" required>
                        <small class="form-text text-muted" style="font-size: 11px;">Forgot your password? <a href="ForgotPassword" class="text-decoration-none">Reset it here</a>.</small>
                    </div>
                    <div class="mb-2 mt-3">
                        <small class="form-label">New password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="voluntaryNewPassword" name="voluntaryNewPassword" placeholder="Enter new password" required>
                        <div class="progress mt-1" style="height: 5px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;" id="voluntaryPasswordStrengthBar"></div>
                        </div>
                    </div>
                    <div class="mb-2">
                        <small class="form-label">Confirm new password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="voluntaryConfirmPassword" name="voluntaryConfirmPassword" placeholder="Confirm new password" required>
                    </div>
                    <div>
                        <div class="vstack ps-3 bg-dark rounded bg-opacity-75 p-3">
                            <span><small class="text-secondary" id="vcharCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">At least 8 characters</small></span>
                            <span><small class="text-secondary" id="vupperCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">At least one uppercase
                                    letter</small></span>
                            <span><small class="text-secondary" id="vnumberCheck"
                                    style="font-size: 11px;">&#9864;</small> <small class="text-muted"
                                    style="font-size: 11px;">At least one number</small></span>
                            <span><small class="text-secondary" id="vspecialCheck"
                                    style="font-size: 11px;">&#9864;</small> <small class="text-muted"
                                    style="font-size: 11px;">At least one special character (e.g. !@#$%^&*)</small></span>
                            <span><small class="text-secondary" id="vmatchCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">Passwords match</small></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100 border-white text-white mt-3" disabled id="updatePasswordBtn">Update password</button>
                    <button type="button" class="btn btn-outline-secondary w-100 border-white text-white mt-2" id="CancelBtn" data-distination="<?= isset($_SESSION['user']['continueUrl']) ? $_SESSION['user']['continueUrl'] : 'none' ?>">Cancel</button>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="SuccessCard">
                <span class="mt-3 ms-3 bg-success-subtle text-success rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-check-circle-fill text-success-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <span class="fw-bold fs-5">Password updated</span>
                    <p class="mb-4" style="font-size: 12px;">Your password has been changed successfully. You can now sign in with your new password.</p>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-outline-dark w-100 border-white text-white"
                            id="GoToLoginBtn">Go to sign in</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>