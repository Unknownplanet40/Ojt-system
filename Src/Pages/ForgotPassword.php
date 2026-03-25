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
    <script type="module" src="../../Assets/Script/ForgotPassword.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="container">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent" style="width: 400px; --blur-lvl: 0.65;" id="SendResetLinkCard">
                <span class="mt-3 ms-3 bg-primary-subtle text-primary rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-envelope-at-fill text-primary-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <span class="fw-bold fs-5">Forgot your password?</span>
                    <p class="mb-4">Enter the email address on your account and we'll send you a reset link.</p>
                    <div class="mb-3">
                        <small class="form-label">Email address</small>
                        <input type="email" class="form-control bg-semi-transparent" id="email" name="email"
                            placeholder="yourname@school.edu.ph" required>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100 border-white text-white" id="SendLinkBtn">Send reset link</button>
                    <hr>
                    <div class="text-center mt-2">
                        <a href="Login.php" class="text-decoration-none text-success"><small>Back to sign in</small></a>
                    </div>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="EmailSentCard">
                <span class="mt-3 ms-3 bg-success-subtle text-success rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-send-check-fill text-success-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <span class="fw-bold fs-5">Check your email</span>
                    <p class="mb-4">If <span id="emailDisplay" class="fw-bold"></span> is registered, you'll receive a
                        password reset link shortly.</p>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-outline-dark w-100 border-white text-white"
                            id="ResendLinkBtn">Resend Link</button>
                    </div>
                    <hr>
                    <div class="text-center mt-2">
                        <a href="Login.php" class="text-decoration-none text-success"><small>Back to sign in</small></a>
                    </div>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="ResetPasswordCard">
                <span class="mt-3 ms-3 bg-warning-subtle text-warning rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-key-fill text-warning-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <div class="vstack mb-0">
                        <span class="badge text-warning bg-warning-subtle text-start mb-3 px-2"
                            style="width: fit-content;"> <i class="bi bi-stopwatch-fill"></i> <small
                                class="fw-medium text-warning-emphasis">Link expires in <span id="countdown">0 seconds</span></small></span>
                        <span class="fw-bold fs-5">Set new password</span>
                    </div>
                    <small class="text-muted">Choose a strong password for your account.</small>
                    <div class="mb-2 mt-3">
                        <small class="form-label">New password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="newPassword"
                            name="newPassword" placeholder="Enter new password" required>
                        <small class="form-text text-muted" style="font-size: 11px;">Minimum 8 characters</small>
                    </div>
                    <div class="mb-2">
                        <small class="form-label">Confirm new password</small>
                        <input type="password" class="form-control bg-semi-transparent" id="confirmPassword"
                            name="confirmPassword" placeholder="Confirm new password" required>
                    </div>
                    <div>
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
                                    style="font-size: 11px;">At least one special character</small></span>
                            <span><small class="text-secondary" id="matchCheck" style="font-size: 11px;">&#9864;</small>
                                <small class="text-muted" style="font-size: 11px;">Passwords match</small></span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-outline-dark w-100 border-white text-white mt-3" disabled id="ResetPasswordBtn">Update Password</button>
                </div>
            </div>
            <div class="card p-4 rounded-3 bg-blur-3 bg-semi-transparent d-none" style="width: 400px; --blur-lvl: 0.65;" id="ExpiredLinkCard">
                <span class="mt-3 ms-3 bg-danger-subtle text-danger rounded-circle d-inline-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                    <i class="bi bi-x-octagon-fill text-danger-emphasis" style="font-size: 18px;"></i>
                </span>
                <div class="card-body">
                    <span class="fw-bold fs-5">Link expired or invalid</span>
                    <p class="mb-4" style="font-size: 12px;">This password reset link has expired or has already been used. Reset links are only valid for 1 hour.</p>
                    <div class="mb-3">
                        <button type="submit" class="btn btn-outline-dark w-100 border-white text-white"
                            id="RequestNewLinkBtn">Request new reset link</button>
                    </div>
                    <hr>
                    <div class="text-center mt-2">
                        <a href="Login.php" class="text-decoration-none text-success fw-bold"><small>Back to sign in</small></a>
                    </div>
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