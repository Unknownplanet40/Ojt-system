<?php
session_start();
require_once "../../Assets/SystemInfo.php";
require_once "../../config/db.php";

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup Wizard | <?= $ShortTitle ?></title>
    <?php include_once "./srcPageHeader.php"; ?>
    <link rel="stylesheet" href="../../Assets/style/SetupWizard.css">
    <script type="module" src="../../Assets/Script/SetupWizard.js"></script>
</head>

<body class="setup-wizard-page">
    <!-- Animated Background Circles -->
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="container-fluid min-vh-100 d-flex align-items-center justify-content-center p-3 p-md-4">
        <div class="setup-card card glass-ui glass-ui-strong w-100" style="max-width: 900px; --blur-lvl: 0.6;">
            <div class="card-header border-0 bg-transparent pt-4 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="setup-icon-box shadow-sm">
                        <i class="bi bi-gear-fill text-primary fs-3"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold">System Setup Wizard</h4>
                        <small class="text-muted">Configure your OJT Management System in 5 easy steps.</small>
                    </div>
                </div>
                
                <!-- Progress Stepper -->
                <div class="setup-stepper mt-4 mb-2 d-none d-md-flex justify-content-between position-relative">
                    <div class="step-line position-absolute w-100 top-50 start-0 translate-middle-y z-n1" style="height: 2px; background: rgba(255,255,255,0.1);"></div>
                    <div class="step-progress-line position-absolute top-50 start-0 translate-middle-y z-n1" id="stepProgress" style="height: 2px; background: var(--bs-primary); width: 0%; transition: width 0.4s ease;"></div>
                    
                    <div class="step-item active" data-step="1">
                        <div class="step-num shadow-sm">1</div>
                        <span class="step-label">Info</span>
                    </div>
                    <div class="step-item" data-step="2">
                        <div class="step-num shadow-sm">2</div>
                        <span class="step-label">Admin</span>
                    </div>
                    <div class="step-item" data-step="3">
                        <div class="step-num shadow-sm">3</div>
                        <span class="step-label">Verify</span>
                    </div>
                    <div class="step-item" data-step="4">
                        <div class="step-num shadow-sm">4</div>
                        <span class="step-label">Email</span>
                    </div>
                    <div class="step-item" data-step="5">
                        <div class="step-num shadow-sm">5</div>
                        <span class="step-label">Review</span>
                    </div>
                </div>
            </div>

            <div class="card-body p-4 overflow-hidden">
                <form id="setupForm" novalidate>
                    <!-- Step 1: School Info -->
                    <div class="setup-step active" id="step1">
                        <div class="row g-3">
                            <div class="col-12 mb-2">
                                <div class="setup-hero-banner glass-ui rounded-4 overflow-hidden position-relative" style="height: 160px; border: 1px solid rgba(255,255,255,0.1);">
                                    <img src="../../Assets/Images/systemImages/setup_wizard_hero.png" class="w-100 h-100 object-fit-cover" alt="Setup Hero" style="filter: brightness(0.6);">
                                    <div class="position-absolute top-50 start-0 translate-middle-y px-4">
                                        <h4 class="fw-bold mb-0 text-white shadow-sm">System Initialization</h4>
                                        <p class="text-white-50 small mb-0 shadow-sm">Complete the institutional profile to launch your OJT platform.</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <h5 class="fw-bold mb-1"><i class="bi bi-info-circle me-2 text-primary"></i>School & System Information</h5>
                                <p class="text-muted small">Configure the identity and contact details of your institution.</p>
                            </div>
                            
                            <!-- App Identity -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Short Title</label>
                                <input type="text" name="short_title" class="form-control glass-input" value="<?= $ShortTitle ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Long Title / App Name</label>
                                <input type="text" name="long_title" class="form-control glass-input" value="<?= $LongTitle ?>" required>
                            </div>
                            
                            <!-- School Identity -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">School Name</label>
                                <input type="text" name="school_name" class="form-control glass-input" value="<?= $SchoolName ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">School Motto</label>
                                <input type="text" name="school_motto" class="form-control glass-input" value="<?= $SchoolMotto ?>">
                            </div>
                            
                            <!-- Contact Info -->
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">School Email</label>
                                <input type="email" name="school_email" class="form-control glass-input" value="<?= $SchoolEmail ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">School Phone</label>
                                <input type="text" name="school_phone" class="form-control glass-input" value="<?= $SchoolPhone ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">Website URL</label>
                                <input type="url" name="school_website" class="form-control glass-input" value="<?= $SchoolWebsite ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label small fw-semibold">Physical Address</label>
                                <input type="text" name="school_address" class="form-control glass-input" value="<?= $SchoolAddress ?>">
                            </div>

                            <!-- Assets -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">School Logo (Left/Primary)</label>
                                <div class="preview-box glass-ui mb-2 d-flex align-items-center justify-content-center overflow-hidden" style="height: 120px; width: 120px; border-radius: 1rem;">
                                    <img id="previewLogoLeft" src="<?= $SchoolLogoLeft ?>" alt="Logo Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <input type="file" name="logo_left" id="inputLogoLeft" class="form-control glass-input" accept="image/*">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">School Logo (Right/Secondary)</label>
                                <div class="preview-box glass-ui mb-2 d-flex align-items-center justify-content-center overflow-hidden" style="height: 120px; width: 120px; border-radius: 1rem;">
                                    <img id="previewLogoRight" src="<?= $SchoolLogoRight ?>" alt="Logo Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                                </div>
                                <input type="file" name="logo_right" id="inputLogoRight" class="form-control glass-input" accept="image/*">
                            </div>

                            <!-- Document Notes -->
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Document Footer Note</label>
                                <input type="text" name="footer_note" class="form-control glass-input" value="<?= $DocumentFooterNote ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Verification Note</label>
                                <input type="text" name="verify_note" class="form-control glass-input" value="<?= $DocumentVerificationNote ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-semibold">System Description</label>
                                <textarea name="description" class="form-control glass-input" rows="2" required><?= $Description ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Admin Account -->
                    <div class="setup-step" id="step2">
                        <div class="row g-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3"><i class="bi bi-person-badge me-2"></i>Admin Account Creation</h5>
                                <p class="text-muted small">Create the primary administrator account. This user will have full access to the system.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Full Name</label>
                                <input type="text" name="admin_name" class="form-control glass-input" placeholder="e.g. John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Admin Email</label>
                                <input type="email" name="admin_email" class="form-control glass-input" placeholder="admin@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Password</label>
                                <div class="input-group">
                                    <input type="password" name="admin_password" id="adminPassword" class="form-control glass-input" required>
                                    <button class="btn btn-outline-secondary glass-btn" type="button" onclick="togglePassword('adminPassword')">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control glass-input" required>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Dependencies Check -->
                    <div class="setup-step" id="step3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="fw-bold mb-0"><i class="bi bi-check-all me-2 text-primary"></i>Dependency Verification</h5>
                                <p class="text-muted small mb-0">Server environment must meet all requirements.</p>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-primary btn-sm glass-ui d-none" id="btnFixDependencies">
                                    <i class="bi bi-tools me-1"></i> How to Fix
                                </button>
                                <button type="button" class="btn btn-primary btn-sm glass-ui" id="btnRecheck">
                                    <i class="bi bi-arrow-clockwise me-1"></i> Recheck
                                </button>
                            </div>
                        </div>
                        
                        <!-- Dependencies Grid -->
                        <div class="row g-3" id="dependencyGrid">
                            <!-- PHP Version -->
                            <div class="col-md-6">
                                <?php $php_ok = version_compare(PHP_VERSION, '8.0.0', '>='); ?>
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="<?= $php_ok ? 'valid' : 'error' ?>" data-fix="php">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                                            <i class="bi bi-code-square fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">PHP Version</h6>
                                            <small class="text-muted fs-xs">Min 8.0 (Current: <?= PHP_VERSION ?>)</small>
                                        </div>
                                    </div>
                                    <i class="bi <?= $php_ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> fs-5"></i>
                                </div>
                            </div>

                            <!-- Mod Rewrite -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="rewrite">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-info bg-opacity-10 text-info rounded-3 p-2">
                                            <i class="bi bi-link-45deg fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">Mod Rewrite</h6>
                                            <small class="text-muted fs-xs">Apache URL Routing</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- MySQL Connection -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="mysql">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-warning bg-opacity-10 text-warning rounded-3 p-2">
                                            <i class="bi bi-database-check fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">MySQL Server</h6>
                                            <small class="text-muted fs-xs">Server Connectivity</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- Apache Status -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="apache">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-danger bg-opacity-10 text-danger rounded-3 p-2">
                                            <i class="bi bi-cpu fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">Apache Status</h6>
                                            <small class="text-muted fs-xs">Server Environment</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- PHPMailer -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="phpmailer">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-primary bg-opacity-10 text-primary rounded-3 p-2">
                                            <i class="bi bi-envelope-check fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">PHPMailer</h6>
                                            <small class="text-muted fs-xs">Email automated engine</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- Write Access -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="permissions">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-success bg-opacity-10 text-success rounded-3 p-2">
                                            <i class="bi bi-pencil-square fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">Write Access</h6>
                                            <small class="text-muted fs-xs">System Permissions</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- Ratchet WebSocket -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="ratchet">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-info bg-opacity-10 text-info rounded-3 p-2">
                                            <i class="bi bi-lightning-charge fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">Ratchet WebSocket</h6>
                                            <small class="text-muted fs-xs">Real-time Messaging</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- mPDF -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="mpdf">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-danger bg-opacity-10 text-danger rounded-3 p-2">
                                            <i class="bi bi-file-earmark-pdf fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">mPDF Library</h6>
                                            <small class="text-muted fs-xs">PDF Generation</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>

                            <!-- PHPOffice -->
                            <div class="col-md-6">
                                <div class="dependency-item glass-ui p-3 h-100 d-flex align-items-center justify-content-between" data-status="valid" data-fix="phpoffice">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="dep-icon bg-success bg-opacity-10 text-success rounded-3 p-2">
                                            <i class="bi bi-file-earmark-excel fs-5"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold small">PHPOffice</h6>
                                            <small class="text-muted fs-xs">Document Handling</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info border-0 glass-ui mt-4 mb-0">
                            <div class="d-flex gap-2">
                                <i class="bi bi-info-circle-fill"></i>
                                <small>All libraries are essential for system modules like Real-time Chat, Certificate Generation, and Data Export.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Step 4: Email Configuration -->
                    <div class="setup-step" id="step4">
                        <div class="row g-4">
                            <div class="col-12">
                                <h5 class="fw-bold mb-3"><i class="bi bi-envelope-at me-2"></i>Email (SMTP) Configuration</h5>
                                <p class="text-muted small">Configure PHPMailer to send notifications to students and coordinators.</p>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label small fw-semibold">SMTP Host</label>
                                <input type="text" name="smtp_host" class="form-control glass-input" placeholder="smtp.gmail.com" value="smtp.gmail.com" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold">SMTP Port</label>
                                <input type="number" name="smtp_port" class="form-control glass-input" value="587" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">SMTP Username / Email</label>
                                <input type="email" name="smtp_user" class="form-control glass-input" placeholder="youremail@gmail.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">SMTP Password / App Password</label>
                                <input type="password" name="smtp_pass" class="form-control glass-input" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Encryption</label>
                                <select name="smtp_encryption" class="form-select glass-input">
                                    <option value="tls" selected>TLS (Recommended)</option>
                                    <option value="ssl">SSL</option>
                                    <option value="none">None</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Step 5: Review -->
                    <!-- Step 5: Review -->
                    <div class="setup-step" id="step5">
                        <div class="col-12">
                            <h5 class="fw-bold mb-3"><i class="bi bi-eye me-2 text-primary"></i>Final Review</h5>
                            <p class="text-muted small">Please verify all configurations before finalizing the setup.</p>
                        </div>
                        
                        <div class="row g-3">
                            <!-- System Info -->
                            <div class="col-md-12">
                                <div class="review-card glass-ui p-3">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="bg-primary bg-opacity-10 text-primary rounded-2 p-2">
                                            <i class="bi bi-info-circle"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold small text-uppercase">System Configuration</h6>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                                <span class="text-muted small">School Name</span>
                                                <span class="fw-semibold small" id="rev_school">--</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                                <span class="text-muted small">Short Title</span>
                                                <span class="fw-semibold small" id="rev_short">--</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                                <span class="text-muted small">Motto</span>
                                                <span class="fw-semibold small" id="rev_motto">--</span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                                <span class="text-muted small">Address</span>
                                                <span class="fw-semibold small" id="rev_address">--</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Admin & SMTP -->
                            <div class="col-md-6">
                                <div class="review-card glass-ui p-3 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="bg-success bg-opacity-10 text-success rounded-2 p-2">
                                            <i class="bi bi-person-badge"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold small text-uppercase">Admin Account</h6>
                                    </div>
                                    <div class="vstack gap-2">
                                        <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                            <span class="text-muted small">Full Name</span>
                                            <span class="fw-semibold small" id="rev_admin_name">--</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">Email</span>
                                            <span class="fw-semibold small" id="rev_admin_email">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="review-card glass-ui p-3 h-100">
                                    <div class="d-flex align-items-center gap-2 mb-3">
                                        <div class="bg-warning bg-opacity-10 text-warning rounded-2 p-2">
                                            <i class="bi bi-envelope-at"></i>
                                        </div>
                                        <h6 class="mb-0 fw-bold small text-uppercase">Email (SMTP)</h6>
                                    </div>
                                    <div class="vstack gap-2">
                                        <div class="d-flex justify-content-between border-bottom border-white border-opacity-10 pb-2">
                                            <span class="text-muted small">Host</span>
                                            <span class="fw-semibold small" id="rev_smtp_host">--</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted small">User</span>
                                            <span class="fw-semibold small" id="rev_smtp_user">--</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning border-0 glass-ui mt-4 mb-0">
                            <div class="d-flex gap-2">
                                <i class="bi bi-exclamation-triangle-fill"></i>
                                <small>Once initialized, core settings are written to the system registry. Please ensure all data is accurate.</small>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-footer border-0 bg-transparent p-4 d-flex justify-content-between">
                <button type="button" class="btn btn-link text-muted text-decoration-none px-0 d-none" id="prevBtn">
                    <i class="bi bi-arrow-left me-2"></i>Back
                </button>
                <div class="ms-auto d-flex gap-2">
                    <button type="button" class="btn btn-primary px-4 py-2 fw-semibold shadow-sm" id="nextBtn">
                        Next <i class="bi bi-arrow-right ms-2"></i>
                    </button>
                    <button type="button" class="btn btn-success px-4 py-2 fw-semibold shadow-sm d-none" id="finishBtn">
                        Initialize System <i class="bi bi-rocket-takeoff ms-2"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
