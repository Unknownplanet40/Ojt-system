<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../Assets/SystemInfo.php";
?>


<!doctype html>
<html lang="en">

<head>
    <?php require_once "./srcPageHeader.php" ?>
    <script type="module" src="../../Assets/Script/ChangePassword.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-must-change="<?= $_SESSION['must_change_password'] ?>"
    data-user-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="container-fluid py-5" style="overflow-y: auto; max-height: 100vh;">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card p-5 rounded-4 bg-blur-3 bg-semi-transparent d-none shadow-lg"
                style="width: 95%; max-width: 500px; --blur-lvl: <?= $opacitylvl ?>"
                id="ForcePasswordChangeCard">
                <div class="mb-4">
                    <span
                        class="bg-warning-subtle text-warning rounded-circle d-inline-flex justify-content-center align-items-center"
                        style="width: 56px; height: 56px;">
                        <i class="bi bi-key-fill text-warning-emphasis" style="font-size: 24px;"></i>
                    </span>
                </div>
                <div class="mb-4">
                    <h4 class="fw-bold mb-2" style="font-size: 22px;">Set your password</h4>
                    <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.6;">Your account was created with
                        a temporary password. Choose a new one to secure your account.</p>
                </div>
                <div class="alert alert-warning d-flex align-items-center mb-4 p-3" role="alert"
                    style="border-radius: 8px; border: none; font-size: 13px;">
                    <i class="bi bi-exclamation-circle-fill me-2" style="font-size: 16px;"></i>
                    <small class="fw-500 m-0">You must set a new password before continuing.</small>
                </div>
                <div class="vstack gap-3">
                    <div>
                        <label for="tempPassword" class="form-label fw-500 mb-2" style="font-size: 13px;">Temporary
                            password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1" id="tempPassword"
                            name="tempPassword" placeholder="Enter temporary password" required
                            style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                    </div>
                    <div>
                        <label for="newPassword" class="form-label fw-500 mb-2" style="font-size: 13px;">New
                            password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1" id="newPassword"
                            name="newPassword" placeholder="Enter new password" required
                            style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                        <div class="progress mt-2" style="height: 4px; border-radius: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"
                                id="passwordStrengthBar"></div>
                        </div>
                    </div>
                    <div>
                        <label for="confirmPassword" class="form-label fw-500 mb-2" style="font-size: 13px;">Confirm new
                            password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1" id="confirmPassword"
                            name="confirmPassword" placeholder="Confirm new password" required
                            style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                    </div>
                    <div class="bg-dark p-3 rounded-3 bg-opacity-50 mt-2" style="font-size: 12px;">
                        <div class="vstack">
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="text-secondary m-0 fw-bold" id="charCheck">✓</h6>
                                <small class="text-muted my-1">At least 8 characters</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="text-secondary m-0 fw-bold" id="upperCheck">✓</h6>
                                <small class="text-muted my-1">Uppercase letter</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="text-secondary m-0 fw-bold" id="numberCheck">✓</h6>
                                <small class="text-muted my-1">One number</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="text-secondary m-0 fw-bold" id="specialCheck">✓</h6>
                                <small class="text-muted my-1">One special character</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <h6 class="text-secondary m-0 fw-bold" id="matchCheck">✓</h6>
                                <small class="text-muted my-1">Passwords match</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-500 mt-3" disabled id="SetPasswordBtn"
                        style="border-radius: 8px; padding: 11px; font-size: 14px;">Set password and continue</button>
                </div>
            </div>
            <div class="card p-5 rounded-4 bg-blur-3 bg-semi-transparent d-none shadow-lg"
                style="width: 95%; max-width: 500px; --blur-lvl: <?= $opacitylvl ?>" id="VoluntaryPasswordChangeCard">
                <div class="mb-4">
                    <span
                        class="bg-info-subtle text-info rounded-circle d-inline-flex justify-content-center align-items-center"
                        style="width: 56px; height: 56px;">
                        <i class="bi bi-key-fill text-info-emphasis" style="font-size: 24px;"></i>
                    </span>
                </div>
                <div class="mb-4">
                    <h4 class="fw-bold mb-2" style="font-size: 22px;">Change password</h4>
                    <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.6;">Enter your current password
                        and choose a new one to secure your account.</p>
                </div>
                <div class="vstack gap-3">
                    <div>
                        <label for="currentPassword" class="form-label fw-500 mb-2" style="font-size: 13px;">Current
                            password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1" id="currentPassword"
                            name="currentPassword" placeholder="Enter current password" required
                            style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                        <small class="form-text text-muted d-block mt-2" style="font-size: 12px;">Forgot your password?
                            <a href="ForgotPassword" class="text-decoration-none">Reset it here</a>.</small>
                    </div>
                    <div>
                        <label for="voluntaryNewPassword" class="form-label fw-500 mb-2" style="font-size: 13px;">New
                            password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1"
                            id="voluntaryNewPassword" name="voluntaryNewPassword" placeholder="Enter new password"
                            required style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                        <div class="progress mt-2" style="height: 4px; border-radius: 4px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: 0%;"
                                id="voluntaryPasswordStrengthBar"></div>
                        </div>
                    </div>
                    <div>
                        <label for="voluntaryConfirmPassword" class="form-label fw-500 mb-2"
                            style="font-size: 13px;">Confirm new password</label>
                        <input type="password" class="form-control bg-semi-transparent border-1"
                            id="voluntaryConfirmPassword" name="voluntaryConfirmPassword"
                            placeholder="Confirm new password" required
                            style="border-radius: 8px; font-size: 14px; padding: 10px 12px;">
                    </div>
                    <div class="bg-dark p-3 rounded-3 bg-opacity-50 mt-2" style="font-size: 12px;">
                        <div class="vstack gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary" id="vcharCheck">✓</small>
                                <small class="text-muted m-0">At least 8 characters</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary" id="vupperCheck">✓</small>
                                <small class="text-muted m-0">Uppercase letter</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary" id="vnumberCheck">✓</small>
                                <small class="text-muted m-0">One number</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary" id="vspecialCheck">✓</small>
                                <small class="text-muted m-0">One special character</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <small class="text-secondary" id="vmatchCheck">✓</small>
                                <small class="text-muted m-0">Passwords match</small>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-dark w-100 fw-500 mt-3" disabled id="updatePasswordBtn"
                        style="border-radius: 8px; padding: 11px; font-size: 14px;">Update password</button>
                    <button type="button" class="btn btn-outline-secondary w-100 fw-500" id="CancelBtn"
                        style="border-radius: 8px; padding: 11px; font-size: 14px;">Cancel</button>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: <?= $opacitylvl ?>" id="SuccessCard">
                <div class="text-center">
                    <div class="mb-4">
                        <span class="bg-success-subtle text-success rounded-circle d-inline-flex justify-content-center align-items-center"
                            style="width: 64px; height: 64px;">
                            <i class="bi bi-check-circle-fill text-success-emphasis" style="font-size: 32px;"></i>
                        </span>
                    </div>
                    <h5 class="fw-bold mb-2" style="font-size: 20px;">Password Updated</h5>
                    <p class="text-muted mb-4" style="font-size: 14px; line-height: 1.6; margin: 0;">Your password has now been updated. if you are not redirected automatically, click the button below to continue.</p>
                </div>
                <div class="mt-4">
                    <button type="submit" class="btn btn-success w-100 fw-500"
                        id="redirectBtn" style="border-radius: 8px; padding: 11px; font-size: 14px;">Continue</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>