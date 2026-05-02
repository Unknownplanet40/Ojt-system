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

<body class="login-page forgot-password-page">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="container py-4 py-lg-5">
        <div class="d-flex justify-content-center align-items-center min-vh-100">
            <div class="fp-shell w-100 mx-auto">
                <div class="text-center text-lg-start mb-4">
                    <div class="d-inline-flex align-items-center gap-3 rounded-4 px-3 py-2 bg-blur-5 bg-semi-transparent border border-success-subtle shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                        <div class="rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px;">
                            <i class="bi bi-shield-lock-fill fs-5"></i>
                        </div>
                        <div class="text-start">
                            <div class="fw-semibold text-body"><?= htmlspecialchars($SchoolName) ?></div>
                            <small class="text-muted"><?= htmlspecialchars($SchoolMotto) ?></small>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg bg-blur-5 bg-semi-transparent fp-card" id="SendResetLinkCard" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                            <div class="rounded-4 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0 fp-icon">
                                <i class="bi bi-envelope-paper-heart fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <p class="text-uppercase small fw-semibold text-success mb-1">Password recovery</p>
                                <h1 class="h4 mb-1 fw-semibold">Forgot your password?</h1>
                                <p class="text-muted mb-0"><?= htmlspecialchars($Description) ?></p>
                            </div>
                        </div>

                        <div class="alert alert-success-subtle border-0 rounded-4 mb-4">
                            <div class="d-flex gap-3">
                                <i class="bi bi-info-circle-fill text-success fs-5 flex-shrink-0"></i>
                                <div>
                                    <div class="fw-semibold text-success-emphasis mb-1">We’ll send a reset link to your email</div>
                                    <div class="small text-muted">Enter the email address registered in the system. If the account exists, you’ll receive a secure link shortly.</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small text-uppercase text-muted">Email address</label>
                            <input type="email" class="form-control bg-blur-5 bg-semi-transparent border shadow-none fp-input" id="email" name="email"
                                placeholder="yourname@school.edu.ph" required style="--blur-lvl: <?= $opacitylvl ?>;">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg rounded-4 shadow-sm" id="SendLinkBtn">
                                <i class="bi bi-send-check me-1"></i>Send reset link
                            </button>
                        </div>

                        <div class="d-flex align-items-center gap-2 my-4 text-muted small">
                            <hr class="flex-grow-1 my-0">
                            <span>or</span>
                            <hr class="flex-grow-1 my-0">
                        </div>

                        <div class="text-center">
                            <a href="Login.php" class="text-decoration-none fw-semibold text-success">Back to sign in</a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg bg-blur-5 bg-semi-transparent fp-card d-none" id="EmailSentCard" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column align-items-center text-center gap-3">
                            <div class="rounded-4 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center fp-icon">
                                <i class="bi bi-send-check-fill fs-4"></i>
                            </div>
                            <div>
                                <h2 class="h4 fw-semibold mb-2">Check your email</h2>
                                <p class="text-muted mb-0">If <span id="emailDisplay" class="fw-semibold text-body"></span> is registered, a reset link will be sent shortly.</p>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 rounded-4 my-4">
                            <div class="small text-muted text-center">For security, the link can only be used once and expires after one hour.</div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-outline-success btn-lg rounded-4" id="ResendLinkBtn">
                                <i class="bi bi-arrow-repeat me-1"></i>Resend link
                            </button>
                        </div>

                        <div class="text-center mt-4">
                            <a href="Login.php" class="text-decoration-none fw-semibold text-success">Back to sign in</a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg bg-blur-5 bg-semi-transparent fp-card d-none" id="ResetPasswordCard" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-4 p-lg-5">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3 mb-4">
                            <div class="rounded-4 bg-warning-subtle text-warning d-inline-flex align-items-center justify-content-center flex-shrink-0 fp-icon">
                                <i class="bi bi-key-fill fs-4"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-inline-flex align-items-center gap-2 rounded-pill bg-warning-subtle text-warning px-3 py-2 mb-2">
                                    <i class="bi bi-stopwatch-fill"></i>
                                    <small class="fw-semibold text-warning-emphasis">Link expires in <span id="countdown">0 seconds</span></small>
                                </div>
                                <h2 class="h4 fw-semibold mb-1">Set a new password</h2>
                                <p class="text-muted mb-0">Choose a strong password to keep your account secure.</p>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="newPassword" class="form-label fw-semibold small text-uppercase text-muted">New password</label>
                            <input type="password" class="form-control bg-blur-5 bg-semi-transparent border shadow-none fp-input" id="newPassword"
                                name="newPassword" placeholder="Enter new password" required style="--blur-lvl: <?= $opacitylvl ?>;">
                            <small class="form-text text-muted">Minimum 8 characters</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label fw-semibold small text-uppercase text-muted">Confirm new password</label>
                            <input type="password" class="form-control bg-blur-5 bg-semi-transparent border shadow-none fp-input" id="confirmPassword"
                                name="confirmPassword" placeholder="Confirm new password" required style="--blur-lvl: <?= $opacitylvl ?>;">
                        </div>

                        <div class="card border-0 rounded-4 bg-body-tertiary bg-opacity-50 mb-4">
                            <div class="card-body p-3">
                                <div class="vstack gap-2 small">
                                    <span><small class="text-secondary" id="charCheck">&#9864;</small> <span class="text-muted">At least 8 characters</span></span>
                                    <span><small class="text-secondary" id="upperCheck">&#9864;</small> <span class="text-muted">At least one uppercase letter</span></span>
                                    <span><small class="text-secondary" id="numberCheck">&#9864;</small> <span class="text-muted">At least one number</span></span>
                                    <span><small class="text-secondary" id="specialCheck">&#9864;</small> <span class="text-muted">At least one special character</span></span>
                                    <span><small class="text-secondary" id="matchCheck">&#9864;</small> <span class="text-muted">Passwords match</span></span>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success btn-lg rounded-4 shadow-sm w-100" disabled id="ResetPasswordBtn">Update Password</button>
                    </div>
                </div>

                <div class="card border-0 shadow-lg bg-blur-5 bg-semi-transparent fp-card d-none" id="ExpiredLinkCard" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="rounded-4 bg-danger-subtle text-danger d-inline-flex align-items-center justify-content-center fp-icon mb-3">
                            <i class="bi bi-x-octagon-fill fs-4"></i>
                        </div>
                        <h2 class="h4 fw-semibold mb-2">Link expired or invalid</h2>
                        <p class="text-muted mb-4">This password reset link has expired or has already been used. Reset links are valid for one hour only.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-outline-success btn-lg rounded-4" id="RequestNewLinkBtn">Request new reset link</button>
                        </div>
                        <div class="text-center mt-4">
                            <a href="Login.php" class="text-decoration-none fw-semibold text-success">Back to sign in</a>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-lg bg-blur-5 bg-semi-transparent fp-card d-none" id="SuccessCard" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-4 p-lg-5 text-center">
                        <div class="rounded-4 bg-success-subtle text-success d-inline-flex align-items-center justify-content-center fp-icon mb-3">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                        <h2 class="h4 fw-semibold mb-2">Password updated</h2>
                        <p class="text-muted mb-4">Your password has been changed successfully. You can now sign in with your new password.</p>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-success btn-lg rounded-4 shadow-sm" id="GoToLoginBtn">Go to sign in</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>