<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";
require_once "../../../functions/settings_functions.php";

$CurrentPage = "Settings";

$dbLogRetention = getAdminSetting($conn, 'db_log_retention', '30');
$dbAutoOptimize = getAdminSetting($conn, 'db_auto_optimize', '0') === '1';

$dtrStatus = isFeatureMaintenanceActive($conn, 'dtr');
$disableDtr = $dtrStatus['active'];
$dtrDisableReason = getAdminSetting($conn, 'dtr_disable_reason', 'DTR submission is temporarily disabled for system maintenance.');
$dtrMaintenanceStart = getAdminSetting($conn, 'dtr_maintenance_start', '');
$dtrMaintenanceEnd = getAdminSetting($conn, 'dtr_maintenance_end', '');

$journalStatus = isFeatureMaintenanceActive($conn, 'journal');
$disableJournal = $journalStatus['active'];
$journalDisableReason = getAdminSetting($conn, 'journal_disable_reason', 'Weekly journal submission is temporarily disabled for system maintenance.');
$journalMaintenanceStart = getAdminSetting($conn, 'journal_maintenance_start', '');
$journalMaintenanceEnd = getAdminSetting($conn, 'journal_maintenance_end', '');

$evaluationStatus = isFeatureMaintenanceActive($conn, 'evaluation');
$disableEvaluation = $evaluationStatus['active'];
$evaluationDisableReason = getAdminSetting($conn, 'evaluation_disable_reason', 'Supervisor evaluation submission is temporarily disabled for system maintenance.');
$evaluationMaintenanceStart = getAdminSetting($conn, 'evaluation_maintenance_start', '');
$evaluationMaintenanceEnd = getAdminSetting($conn, 'evaluation_maintenance_end', '');
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <link rel="stylesheet" href="../../../Assets/style/settings.css">
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/SettingsScripts.js"></script>
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
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack mb-4">
                    <div>
                        <h4 class="mb-2" style="font-weight: 700; color: var(--bs-body-color);">
                            <i class="bi bi-sliders" style="color: #0d6efd; margin-right: 0.5rem;"></i>
                            Settings & Preferences
                        </h4>
                        <p class="blockquote-footer pt-0 fs-6 mt-1">Configure system-wide settings and preferences</p>
                    </div>
                </div>

                <!-- Tab Navigation -->
                <ul class="nav nav-pills mb-4" id="settings-tab" role="tablist" style="gap: 0.5rem;">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="settings-appearance-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-appearance" type="button" role="tab" 
                            aria-controls="settings-appearance" aria-selected="true">
                            <i class="bi bi-palette me-2"></i>Appearance
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-email-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-email" type="button" role="tab" 
                            aria-controls="settings-email" aria-selected="false">
                            <i class="bi bi-envelope me-2"></i>Email
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-system-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-system" type="button" role="tab" 
                            aria-controls="settings-system" aria-selected="false">
                            <i class="bi bi-info-circle me-2"></i>System Information
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-database-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-database" type="button" role="tab" 
                            aria-controls="settings-database" aria-selected="false">
                            <i class="bi bi-database me-2"></i>Database
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-security-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-security" type="button" role="tab" 
                            aria-controls="settings-security" aria-selected="false">
                            <i class="bi bi-shield-lock me-2"></i>Account Security
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-alerts-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-alerts" type="button" role="tab"
                            aria-controls="settings-alerts" aria-selected="false">
                            <i class="bi bi-megaphone me-2"></i>Alert Banners
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="settings-maintenance-tab" data-bs-toggle="pill"
                            data-bs-target="#settings-maintenance" type="button" role="tab" 
                            aria-controls="settings-maintenance" aria-selected="false">
                            <i class="bi bi-tools me-2"></i>Maintenance Mode
                        </button>
                    </li>
                </ul>

                <!-- Tab Content -->
                <div class="tab-content" id="settings-tabContent">
                    
                    <!-- Appearance Tab -->
                    <div class="tab-pane fade show active" id="settings-appearance" role="tabpanel"
                        aria-labelledby="settings-appearance-tab" tabindex="0">
                        
                        <!-- Theme Selection -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-palette"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Theme Mode</h5>
                                    <p>Choose your preferred color theme</p>
                                </div>
                            </div>
                            <div class="theme-selector">
                                <div class="theme-option">
                                    <input type="radio" id="theme-light" name="theme" value="light">
                                    <label for="theme-light">
                                        <i class="bi bi-sun"></i>
                                        <span>Light</span>
                                    </label>
                                </div>
                                <div class="theme-option">
                                    <input type="radio" id="theme-dark" name="theme" value="dark">
                                    <label for="theme-dark">
                                        <i class="bi bi-moon"></i>
                                        <span>Dark</span>
                                    </label>
                                </div>
                                <div class="theme-option">
                                    <input type="radio" id="theme-auto" name="theme" value="auto">
                                    <label for="theme-auto">
                                        <i class="bi bi-circle-half"></i>
                                        <span>Auto</span>
                                    </label>
                                </div>
                            </div>
                            <small class="form-text">Note: Light mode may have reduced contrast in some areas or incompatibility with certain features. Dark mode is recommended for optimal experience.</small>
                        </div>

                        <!-- Background Opacity -->
                        <div class="settings-section d-none">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-transparency"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Interface Effects</h5>
                                    <p>Customize visual effects and transparency</p>
                                </div>
                            </div>
                            <div class="form-group-custom">
                                <label for="opacityLevel">Background Opacity</label>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <input type="range" class="form-range range-slider" id="opacityLevel" 
                                           min="0" max="1" step="0.1" value="<?= $opacitylvl ?>" style="flex: 1;">
                                    <span id="opacityValue" style="font-weight: 600; color: var(--bs-body-color); min-width: 60px;">0%</span>
                                </div>
                                <script>
                                    const opacitySlider = document.getElementById('opacityLevel');
                                    const opacityValue = document.getElementById('opacityValue');

                                    const updateOpacity = () => {
                                        const value = parseFloat(opacitySlider.value);
                                        opacityValue.textContent = `${Math.round(value * 100)}%`;
                                        document.documentElement.style.setProperty('--blur-lvl', value);
                                    };

                                    opacitySlider.addEventListener('input', updateOpacity);
                                    updateOpacity();
                                </script>
                                <div class="form-text">Controls the blur effect intensity of the background</div>
                            </div>
                        </div>

                    </div>

                    <!-- Email Configuration Tab -->
                    <div class="tab-pane fade" id="settings-email" role="tabpanel"
                        aria-labelledby="settings-email-tab" tabindex="0">
                        
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-envelope"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Email Configuration</h5>
                                    <p>Set up SMTP settings for system emails</p>
                                </div>
                            </div>

                            <div class="info-box">
                                <i class="bi bi-info-circle"></i>
                                Configure SMTP credentials to enable system email notifications. We recommend using Gmail with App Passwords or any SMTP provider.
                            </div>

                            <form id="emailForm">
                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="emailSmtpHost">SMTP Host <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="emailSmtpHost" 
                                               placeholder="e.g., smtp.gmail.com" style="width: 100%;">
                                        <div class="form-text">e.g., smtp.gmail.com, mail.yourserver.com</div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="emailSmtpPort">SMTP Port <span style="color: #dc3545;">*</span></label>
                                        <input type="number" class="form-control-custom" id="emailSmtpPort" 
                                               placeholder="e.g., 587" style="width: 100%;">
                                        <div class="form-text">Usually 587 (TLS) or 465 (SSL)</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="emailSmtpUser">SMTP Username <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="emailSmtpUser" 
                                               placeholder="e.g., user@gmail.com" style="width: 100%;">
                                        <div class="form-text">Your SMTP login username</div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="emailSmtpPass">SMTP Password <span style="color: #dc3545;">*</span></label>
                                        <input type="password" class="form-control-custom" id="emailSmtpPass" 
                                               placeholder="Enter your SMTP password" style="width: 100%;">
                                        <div class="form-text">App-specific password recommended</div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="emailSmtpCrypto">Encryption <span style="color: #dc3545;">*</span></label>
                                        <select class="form-control-custom" id="emailSmtpCrypto" style="width: 100%;">
                                            <option class="CustomOption" value="tls" selected>TLS (Recommended)</option>
                                            <option class="CustomOption" value="ssl">SSL</option>
                                            <option class="CustomOption" value="none">None</option>
                                        </select>
                                        <div class="form-text">Choose your SMTP encryption type</div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="emailFromEmail">Sender Email <span style="color: #dc3545;">*</span></label>
                                        <input type="email" class="form-control-custom" id="emailFromEmail" 
                                               placeholder="noreply@example.com" style="width: 100%;">
                                        <div class="form-text">The email address shown to recipients</div>
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label for="emailFromName">From Name <span style="color: #dc3545;">*</span></label>
                                    <input type="text" class="form-control-custom" id="emailFromName" 
                                           placeholder="e.g., OJT Management System" style="width: 100%; max-width: 400px;">
                                    <div class="form-text">Display name for sent emails</div>
                                </div>

                                <div class="action-buttons">
                                    <button type="button" class="btn-action btn-save" id="emailTestBtn">
                                        <i class="bi bi-play-circle"></i> Test Connection
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>

                    <!-- System Information Tab -->
                    <div class="tab-pane fade" id="settings-system" role="tabpanel"
                        aria-labelledby="settings-system-tab" tabindex="0">
                        
                        <!-- Institutional Profile -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-building"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Institutional Profile</h5>
                                    <p>Manage school identity and contact details</p>
                                </div>
                            </div>

                            <form id="institutionalForm" enctype="multipart/form-data">
                                <div class="info-box mb-4">
                                    <i class="bi bi-window-sidebar"></i>
                                    System Application Identity
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group-custom" style="flex: 2;">
                                        <label for="instLongTitle">System Long Title <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="instLongTitle" name="long_title" placeholder="e.g. On-The-Job Training Management System" style="width: 100%;" required>
                                    </div>
                                    <div class="form-group-custom" style="flex: 1;">
                                        <label for="instShortTitle">Short Title <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="instShortTitle" name="short_title" placeholder="e.g. OJT-MS" style="width: 100%;" required>
                                    </div>
                                    <div class="form-group-custom" style="flex: 1;">
                                        <label for="instAuthor">System Author</label>
                                        <input type="text" class="form-control-custom" id="instAuthor" name="author" placeholder="e.g. IT Department" style="width: 100%;">
                                    </div>
                                </div>
                                
                                <div class="form-group-custom">
                                    <label for="instSystemDescription">Brief System Description</label>
                                    <textarea class="form-control-custom" id="instSystemDescription" name="system_description" rows="2" placeholder="Describe the purpose of this system..." style="width: 100%;"></textarea>
                                </div>

                                <div class="info-box mb-4 mt-4">
                                    <i class="bi bi-bank"></i>
                                    Institution Details
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom" style="flex: 2;">
                                        <label for="instSchoolName">School Name <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="instSchoolName" name="school_name" placeholder="e.g. University of Technology" style="width: 100%;" required>
                                    </div>
                                    <div class="form-group-custom" style="flex: 1;">
                                        <label for="instSchoolMotto">School Motto</label>
                                        <input type="text" class="form-control-custom" id="instSchoolMotto" name="school_motto" placeholder="e.g. Excellence and Innovation" style="width: 100%;">
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label for="instSchoolAddress">Complete Address</label>
                                    <input type="text" class="form-control-custom" id="instSchoolAddress" name="school_address" placeholder="123 University Ave, City, State, ZIP" style="width: 100%;">
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="instSchoolEmail">Official Email</label>
                                        <input type="email" class="form-control-custom" id="instSchoolEmail" name="school_email" placeholder="contact@school.edu" style="width: 100%;">
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="instSchoolPhone">Contact Number</label>
                                        <input type="text" class="form-control-custom" id="instSchoolPhone" name="school_phone" placeholder="+1 234 567 8900" style="width: 100%;">
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="instSchoolWebsite">Website URL</label>
                                        <input type="url" class="form-control-custom" id="instSchoolWebsite" name="school_website" placeholder="https://www.school.edu" style="width: 100%;">
                                    </div>
                                </div>

                                <div class="info-box mb-4 mt-4">
                                    <i class="bi bi-file-earmark-richtext"></i>
                                    Document Formatting & Branding
                                </div>
                                <div class="form-text mb-3" style="margin-top: -15px;">These logos and notes will appear on generated PDFs, emails, and official system documents.</div>

                                <div class="form-row mb-3">
                                    <div class="form-group-custom">
                                        <label for="instLogo1">Primary Logo (Left Side)</label>
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                            <div style="width: 60px; height: 60px; border-radius: 8px; border: 1px dashed rgba(var(--bs-body-color-rgb), 0.2); display: flex; align-items: center; justify-content: center; overflow: hidden; background: rgba(var(--bs-body-color-rgb), 0.02); flex-shrink: 0;">
                                                <img id="logo1Preview" src="https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO" onerror="this.src='https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO'" style="width: 100%; height: 100%; object-fit: contain;" alt="Logo 1">
                                            </div>
                                            <input type="file" class="form-control-custom" id="instLogo1" name="logo_1" accept="image/*" style="flex: 1;">
                                        </div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="instLogo2">Secondary Logo (Right Side)</label>
                                        <div style="display: flex; align-items: center; gap: 1rem; margin-top: 0.5rem;">
                                            <div style="width: 60px; height: 60px; border-radius: 8px; border: 1px dashed rgba(var(--bs-body-color-rgb), 0.2); display: flex; align-items: center; justify-content: center; overflow: hidden; background: rgba(var(--bs-body-color-rgb), 0.02); flex-shrink: 0;">
                                                <img id="logo2Preview" src="https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO" onerror="this.src='https://placehold.co/128x128/0F6E56/FFFFFF?text=LOGO'" style="width: 100%; height: 100%; object-fit: contain;" alt="Logo 2">
                                            </div>
                                            <input type="file" class="form-control-custom" id="instLogo2" name="logo_2" accept="image/*" style="flex: 1;">
                                        </div>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="instPageLink">Institutional Portal Link</label>
                                        <input type="url" class="form-control-custom" id="instPageLink" name="page_link" placeholder="e.g. portal.school.edu" style="width: 100%;">
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="instFooterNote">Document Footer Note</label>
                                        <input type="text" class="form-control-custom" id="instFooterNote" name="footer_note" placeholder="e.g. Officially issued by the OJT Office" style="width: 100%;">
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label for="instVerificationNote">Verification / E-Signature Note</label>
                                    <textarea class="form-control-custom" id="instVerificationNote" name="verification_note" rows="2" placeholder="e.g. Not valid without a dry seal. Verify authenticity at the Coordinator's office." style="width: 100%;"></textarea>
                                </div>
                            </form>
                        </div>

                        <!-- Environment Info -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-server"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Server Environment</h5>
                                    <p>System and server configuration details</p>
                                </div>
                            </div>

                            <div class="system-info-grid" id="environmentInfoGrid">
                                <!-- Populated by JavaScript -->
                                <div class="system-info-card system-info-card-wide" style="grid-column: 1 / -1;">
                                    <div style="text-align: center; padding: 2rem;">
                                        <div class="spinner-mini" style="display: inline-block; margin-right: 0.5rem;"></div>
                                        <span style="color: var(--bs-secondary-color);">Loading system information...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Import Section -->
                        <!-- Storage Info -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-hdd"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Storage & Directories</h5>
                                    <p>Disk space usage and directory status</p>
                                </div>
                            </div>

                            <div class="system-info-grid" id="storageInfoGrid">
                                <!-- Populated by JavaScript -->
                                <div class="system-info-card system-info-card-wide" style="grid-column: 1 / -1;">
                                    <div style="text-align: center; padding: 2rem;">
                                        <div class="spinner-mini" style="display: inline-block; margin-right: 0.5rem;"></div>
                                        <span style="color: var(--bs-secondary-color);">Loading storage information...</span>
                                    </div>
                                </div>
                            </div>
                        </div>



                    </div>

                    <!-- Database Tab -->
                    <div class="tab-pane fade" id="settings-database" role="tabpanel"
                        aria-labelledby="settings-database-tab" tabindex="0">
                        
                        <!-- Database Health Monitor -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon" style="background: rgba(var(--bs-success-rgb), 0.15); color: var(--bs-success);">
                                    <i class="bi bi-heart-pulse"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Database Health & Status</h5>
                                    <p>Monitor live storage usage, table structure complexity, and data volume. High record counts may impact performance.</p>
                                </div>
                            </div>

                            <div class="system-info-grid">
                                <article class="system-info-card" data-status="is-ok">
                                    <div class="system-info-card-top">
                                        <span class="system-info-icon is-ok"><i class="bi bi-hdd-stack"></i></span>
                                        <span class="system-status-dot is-ok"></span>
                                    </div>
                                    <div class="system-info-body">
                                        <span class="system-info-label">Database Size</span>
                                        <strong class="system-info-value" id="dbSizeValue">0.00 MB</strong>
                                    </div>
                                    <span class="system-status-pill is-ok"><i class="bi bi-check-circle-fill"></i> <span>Optimized</span></span>
                                </article>

                                <article class="system-info-card">
                                    <div class="system-info-card-top">
                                        <span class="system-info-icon"><i class="bi bi-table"></i></span>
                                    </div>
                                    <div class="system-info-body">
                                        <span class="system-info-label">Total Tables</span>
                                        <strong class="system-info-value" id="dbTablesValue">0</strong>
                                    </div>
                                    <span class="system-status-pill"><i class="bi bi-info-circle-fill"></i> <span>Active</span></span>
                                </article>

                                <article class="system-info-card">
                                    <div class="system-info-card-top">
                                        <span class="system-info-icon"><i class="bi bi-people"></i></span>
                                    </div>
                                    <div class="system-info-body">
                                        <span class="system-info-label">Total Records</span>
                                        <strong class="system-info-value" id="dbRecordsValue">0</strong>
                                    </div>
                                    <span class="system-status-pill"><i class="bi bi-graph-up"></i> <span>Growing</span></span>
                                </article>
                            </div>
                        </div>

                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-database"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Export & Backup</h5>
                                    <p>Generate a portable SQL dump of your entire system. Use this for manual backups or migrating to a new server.</p>
                                </div>
                            </div>

                             <div class="info-box">
                                <i class="bi bi-shield-lock"></i>
                                Regular backups are recommended to prevent data loss. The exported file will be in SQL format.
                            </div>

                            <div class="alert alert-warning border-0 rounded-4 my-3 d-flex align-items-start gap-3 p-3" style="background: rgba(var(--bs-warning-rgb), 0.08); border-left: 4px solid var(--bs-warning) !important;">
                                <i class="bi bi-exclamation-triangle-fill fs-5 text-warning mt-0.5"></i>
                                <div>
                                    <h6 class="fw-bold mb-1" style="color: var(--bs-warning-text-emphasis);">Database Backup Only</h6>
                                    <p class="mb-0 small text-white-75">This database export (.sql) only backups settings, tables, and system logs. It <strong>does not</strong> include user-uploaded files, student resumes, generated certificates, or profile pictures (stored under the <code>uploads/</code> and <code>Assets/Images/profiles/</code> directories). Please download the files backup zip below or back up these folders manually on your server.</p>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="settings-section h-100 mb-0" style="background: rgba(var(--bs-body-color-rgb), 0.03);">
                                        <h6 class="mb-3" style="color: var(--bs-body-color); font-weight: 600;">
                                            <i class="bi bi-cloud-download me-2 text-primary"></i>Export Options
                                        </h6>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="export-structure" checked>
                                            <label class="form-check-label" for="export-structure" style="color: var(--bs-body-color);">
                                                Include Table Structure (DDL)
                                            </label>
                                        </div>
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" id="export-data" checked>
                                            <label class="form-check-label" for="export-data" style="color: var(--bs-body-color);">
                                                Include Table Data (DML)
                                            </label>
                                        </div>
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="export-drop" checked>
                                            <label class="form-check-label" for="export-drop" style="color: var(--bs-body-color);">
                                                Add 'DROP TABLE IF EXISTS'
                                            </label>
                                        </div>
                                        <button class="btn-action btn-save w-100 justify-content-center mt-3" id="exportDatabaseBtn">
                                            <i class="bi bi-download"></i> Generate Export (.sql)
                                        </button>
                                        <button class="btn-action w-100 justify-content-center mt-2" id="exportUploadsZipBtn" style="background: rgba(var(--bs-primary-rgb), 0.15); color: var(--bs-primary-text-emphasis); border: 1px solid rgba(var(--bs-primary-rgb), 0.3);">
                                            <i class="bi bi-file-earmark-zip"></i> Download Uploads Backup (.zip)
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="settings-section h-100 mb-0" style="background: rgba(var(--bs-body-color-rgb), 0.03);">
                                        <h6 class="mb-3" style="color: var(--bs-body-color); font-weight: 600;">
                                            <i class="bi bi-history me-2 text-primary"></i>Export History
                                        </h6>
                                        <div id="exportHistoryContainer" class="text-center py-4 d-flex flex-column align-items-center justify-content-center h-75">
                                            <i class="bi bi-archive" style="font-size: 2.5rem; color: var(--settings-muted); opacity: 0.5;"></i>
                                            <p class="mt-3 text-muted small">No recent exports found in this session</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Import Section -->
                            <div class="settings-section mt-4 mb-0" style="border-color: rgba(var(--bs-warning-rgb), 0.2); background: rgba(var(--bs-warning-rgb), 0.02);">
                                <div class="section-header" style="border-bottom-color: rgba(var(--bs-warning-rgb), 0.1);">
                                    <div class="section-header-icon" style="background: rgba(var(--bs-warning-rgb), 0.15); color: var(--bs-warning-text-emphasis);">
                                        <i class="bi bi-upload"></i>
                                    </div>
                                    <div class="section-header-text">
                                        <h5>Import & Restore</h5>
                                        <p>Restore your system to a previous state using a valid .sql backup. <span class="text-danger fw-bold">Warning: This will overwrite all current data.</span></p>
                                    </div>
                                </div>
                                
                                <div class="p-4 mb-3 border border-dashed rounded-3 text-center" id="sqlDropZone" style="border-style: dashed !important; border-width: 2px !important; border-color: rgba(var(--bs-warning-rgb), 0.3) !important; background: rgba(var(--bs-warning-rgb), 0.05); cursor: pointer;">
                                    <input type="file" id="sqlFileInput" accept=".sql" style="display: none;">
                                    <i class="bi bi-filetype-sql mb-2" style="font-size: 3rem; color: var(--bs-warning);"></i>
                                    <p class="mb-1 fw-bold" id="sqlFileNameDisplay" style="color: var(--bs-body-color);">Click or Drag SQL Backup Here</p>
                                    <p class="text-muted small">Only .sql files generated by this system are supported</p>
                                </div>

                                <!-- SQL Validation Result Panel -->
                                <div id="sqlValidationResult" class="mb-3 d-none">
                                    <div id="sqlValidationContent" class="p-3 rounded-3 border small"></div>
                                </div>

                                <!-- Dry Run Result Panel -->
                                <div id="dryRunResult" class="mb-3 d-none">
                                    <div id="dryRunContent" class="p-3 rounded-3 border small" style="border-color: rgba(var(--bs-info-rgb),0.4); background: rgba(var(--bs-info-rgb),0.04);"></div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn-action flex-grow-1 justify-content-center" id="validateSqlBtn" style="background: rgba(var(--bs-warning-rgb),0.15); color: var(--bs-warning-text-emphasis); border: 1px solid rgba(var(--bs-warning-rgb),0.3);" disabled>
                                        <i class="bi bi-shield-check"></i> Validate SQL
                                    </button>
                                    <button class="btn-action flex-grow-1 justify-content-center" id="dryRunBtn" style="background: rgba(var(--bs-info-rgb),0.15); color: var(--bs-info-text-emphasis); border: 1px solid rgba(var(--bs-info-rgb),0.3); opacity: 0.45; cursor: not-allowed;" disabled title="Validate the SQL file first.">
                                        <i class="bi bi-eye"></i> Dry Run
                                    </button>
                                    <button class="btn-action w-100 justify-content-center mt-1" id="importDatabaseBtn" style="background: var(--bs-warning); color: #000; font-weight: 600; opacity: 0.45; cursor: not-allowed;" disabled title="Validate and Dry Run first.">
                                        <i class="bi bi-arrow-repeat"></i> Start Import
                                    </button>
                                </div>
                            </div>
                            <!-- Import Files Section -->
                            <div class="settings-section mt-4 mb-0 position-relative" id="importFilesSection" style="border-color: rgba(var(--bs-info-rgb), 0.2); background: rgba(var(--bs-info-rgb), 0.02); transition: all 0.3s ease;">
                                <div class="section-header" style="border-bottom-color: rgba(var(--bs-info-rgb), 0.1);">
                                    <div class="section-header-icon" style="background: rgba(var(--bs-info-rgb), 0.15); color: var(--bs-info-text-emphasis);">
                                        <i class="bi bi-folder-symlink"></i>
                                    </div>
                                    <div class="section-header-text">
                                        <h5>Import & Restore Files</h5>
                                        <p>Restore user-uploaded files and profile pictures from a valid backup .zip. <span class="text-danger fw-bold">Warning: Existing files with matching names will be overwritten.</span></p>
                                    </div>
                                </div>
                                
                                <div class="p-4 mb-3 border border-dashed rounded-3 text-center" id="zipDropZone" style="border-style: dashed !important; border-width: 2px !important; border-color: rgba(var(--bs-info-rgb), 0.3) !important; background: rgba(var(--bs-info-rgb), 0.05); cursor: pointer;">
                                    <input type="file" id="zipFileInput" accept=".zip" style="display: none;">
                                    <i class="bi bi-file-earmark-zip mb-2" style="font-size: 3rem; color: var(--bs-info);"></i>
                                    <p class="mb-1 fw-bold" id="zipFileNameDisplay" style="color: var(--bs-body-color);">Click or Drag ZIP Backup Here</p>
                                    <p class="text-muted small">Only .zip backups generated by this system are supported</p>
                                </div>

                                <!-- ZIP Validation Result Panel -->
                                <div id="zipValidationResult" class="mb-3 d-none">
                                    <div id="zipValidationContent" class="p-3 rounded-3 border small"></div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button class="btn-action flex-grow-1 justify-content-center" id="validateZipBtn" style="background: rgba(var(--bs-info-rgb),0.15); color: var(--bs-info-text-emphasis); border: 1px solid rgba(var(--bs-info-rgb),0.3);" disabled>
                                        <i class="bi bi-shield-check"></i> Validate ZIP
                                    </button>
                                    <button class="btn-action w-100 justify-content-center mt-1" id="importZipBtn" style="background: var(--bs-info); color: #000; font-weight: 600; opacity: 0.45; cursor: not-allowed;" disabled title="Validate ZIP first.">
                                        <i class="bi bi-arrow-repeat"></i> Start Files Import
                                    </button>
                                </div>
                            </div>

                            <!-- Maintenance & Retention -->
                            <div class="settings-section mt-4 mb-0">
                                <div class="section-header">
                                    <div class="section-header-icon" style="background: rgba(13, 110, 253, 0.15); color: #0d6efd;">
                                        <i class="bi bi-tools"></i>
                                    </div>
                                    <div class="section-header-text">
                                        <h5>Maintenance & Retention</h5>
                                        <p>Automate system cleanup to prevent database bloat and maintain peak performance.</p>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="logRetention">Log Retention Policy</label>
                                        <div class="form-text small mb-2" style="opacity: 0.8;">Automatically purges activity and audit logs older than the selected period to prevent database bloat.</div>
                                        <select class="form-select custom-select-glass" id="logRetentionPolicy">
                                            <option value="7" class="CustomOption" <?php echo ($dbLogRetention == '7') ? 'selected' : ''; ?>>Last 7 Days</option>
                                            <option value="30" class="CustomOption" <?php echo ($dbLogRetention == '30') ? 'selected' : ''; ?>>Last 30 Days</option>
                                            <option value="90" class="CustomOption" <?php echo ($dbLogRetention == '90') ? 'selected' : ''; ?>>Last 90 Days</option>
                                            <option value="0" class="CustomOption" <?php echo ($dbLogRetention == '0') ? 'selected' : ''; ?>>Never (Keep All)</option>
                                        </select>
                                        <div class="form-text">Older logs will be automatically purged during weekly maintenance.</div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="autoOptimize">Weekly Optimization</label>
                                        <div class="form-text small mb-2" style="opacity: 0.8;">Reclaims unused disk space and reorganizes table indexes to maintain peak system speed.</div>
                                        <div class="form-check form-switch mb-3">
                                            <input class="form-check-input" type="checkbox" id="autoOptimizeToggle" <?php echo $dbAutoOptimize ? 'checked' : ''; ?>>
                                            <label class="form-check-label" for="autoOptimizeToggle" style="color: var(--bs-body-color);">
                                                Weekly Auto-Optimization
                                            </label>
                                        </div>
                                        <div class="form-text">Improves database performance by defragmenting tables.</div>
                                    </div>
                                </div>

                                <div class="action-buttons mt-3">
                                    <button class="btn-action btn-reset" id="optimizeDbBtn">
                                        <i class="bi bi-magic"></i> Optimize Now
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Danger Zone -->
                        <div class="settings-section" style="border-color: rgba(220, 53, 69, 0.2); background: rgba(220, 53, 69, 0.03);">
                            <div class="section-header" style="border-bottom-color: rgba(220, 53, 69, 0.1);">
                                <div class="section-header-icon" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Danger Zone</h5>
                                    <p>High-risk administrative actions. These operations will permanently delete system logs and cannot be undone.</p>
                                </div>
                            </div>

                            <div class="vstack gap-2">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(var(--bs-body-color-rgb), 0.02); border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.1);">
                                    <div>
                                        <div style="font-weight: 500; color: var(--bs-body-color);">Clear Activity Log</div>
                                        <div style="font-size: 0.85rem; color: var(--bs-secondary-color);">Permanently deletes all entries from activity_log table</div>
                                    </div>
                                    <button class="btn-action" id="clearActivityLogBtn" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3);">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(var(--bs-body-color-rgb), 0.02); border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.1);">
                                    <div>
                                        <div style="font-weight: 500; color: var(--bs-body-color);">Clear Login Audit Log</div>
                                        <div style="font-size: 0.85rem; color: var(--bs-secondary-color);">Permanently deletes all entries from login_audit_log table</div>
                                    </div>
                                    <button class="btn-action" id="clearLoginLogBtn" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3);">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(var(--bs-danger-rgb), 0.02); border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.2);">
                                    <div>
                                        <div style="font-weight: 600; color: #dc3545;"><i class="bi bi-shield-fire me-1"></i>System Reset (Fresh Start)</div>
                                        <div style="font-size: 0.85rem; color: var(--bs-secondary-color);">Wipes all database tables, truncates uploaded files/profiles, and restores system to defaults while preserving your login.</div>
                                    </div>
                                    <button class="btn-action" id="systemResetBtn" style="background: #dc3545; color: #fff; border: 1px solid #dc3545; font-weight: 600;">
                                        <i class="bi bi-arrow-counterclockwise"></i> Reset System
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Account Security Tab -->
                    <div class="tab-pane fade" id="settings-security" role="tabpanel"
                        aria-labelledby="settings-security-tab" tabindex="0">
                        
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-shield-lock"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Account Lockout Management</h5>
                                    <p>Manage user access and security restrictions</p>
                                </div>
                            </div>

                            <div class="row g-4 mt-2">
                                <div class="col-md-5">
                                    <div class="card bg-transparent border-0 h-100">
                                        <div class="card-body p-0">
                                            <label class="form-label text-muted small fw-bold">SEARCH USER</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-transparent border-end-0" style="border-radius: 12px 0 0 12px; border-color: rgba(var(--bs-body-color-rgb), 0.1);">
                                                    <i class="bi bi-search"></i>
                                                </span>
                                                <input type="text" id="userSearchInput" class="form-control bg-transparent border-start-0 py-2" 
                                                       placeholder="Enter name or email..." style="border-radius: 0 12px 12px 0; border-color: rgba(var(--bs-body-color-rgb), 0.1);">
                                            </div>
                                            
                                            <div id="userSearchResults" class="mt-3 overflow-auto" style="max-height: 400px; border-radius: 12px;">
                                                <!-- User cards will appear here -->
                                                <div class="text-center py-5 text-muted">
                                                    <i class="bi bi-people" style="font-size: 2rem; opacity: 0.3;"></i>
                                                    <p class="mt-2 small">Start typing to find users</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-7">
                                    <div id="userLockoutDetails" class="card h-100 glass-ui border-0" style="border-radius: 15px; display: none;">
                                        <div class="card-body p-4">
                                            <div class="d-flex align-items-center mb-4">
                                                <div id="selectedUserInitials" class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                     style="width: 60px; height: 60px; background: var(--bs-primary); color: white; font-weight: 700; font-size: 1.2rem;">JD</div>
                                                <div>
                                                    <h5 id="selectedUserName" class="mb-0">John Doe</h5>
                                                    <p id="selectedUserEmail" class="text-muted small mb-0">john.doe@example.com</p>
                                                    <span id="selectedUserRole" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mt-1" style="font-size: 0.7rem;">STUDENT</span>
                                                </div>
                                                <div class="ms-auto text-end">
                                                    <div id="selectedUserStatus">
                                                        <span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVE</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="p-3 rounded-3 mb-4" id="lockoutInfoBox" style="background: rgba(var(--bs-body-color-rgb), 0.05); border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);">
                                                <div class="row text-center g-3">
                                                    <div class="col-4">
                                                        <label class="d-block text-muted small mb-1">FAILED ATTEMPTS</label>
                                                        <h4 id="failedAttemptsCount" class="mb-0">0</h4>
                                                    </div>
                                                    <div class="col-8">
                                                        <label class="d-block text-muted small mb-1">LOCKOUT EXPIRES</label>
                                                        <h6 id="lockoutExpiryText" class="mb-0">Not Locked</h6>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row g-3">
                                                <div class="col-12">
                                                    <label class="form-label text-muted small fw-bold">ADMIN ACTIONS</label>
                                                    <div class="d-flex gap-2">
                                                        <button id="unlockAccountBtn" class="btn btn-success flex-grow-1 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3" disabled>
                                                            <i class="bi bi-unlock"></i> Unlock Account
                                                        </button>
                                                        
                                                        <div class="dropdown flex-grow-1">
                                                            <button id="lockAccountDropdown" class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3 dropdown-toggle" data-bs-toggle="dropdown">
                                                                <i class="bi bi-lock"></i> Manual Lock
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end glass-ui border-0 shadow-lg p-2" style="border-radius: 12px; width: 250px;">
                                                                <li><h6 class="dropdown-header">Lock Duration</h6></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option" href="#" data-hours="1">1 Hour</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option" href="#" data-hours="3">3 Hours</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option" href="#" data-hours="6">6 Hours</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option" href="#" data-hours="12">12 Hours</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option" href="#" data-hours="24">24 Hours (1 Day)</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <p class="mt-4 text-muted small">
                                                <i class="bi bi-info-circle me-1"></i> 
                                                Manually locking an account will prevent the user from logging in even if they know their password. The user will be automatically unlocked after the selected duration.
                                            </p>
                                        </div>
                                    </div>

                                    <div id="noUserSelectedPlaceholder" class="card h-100 border-0 bg-transparent">
                                        <div class="card-body d-flex flex-column align-items-center justify-content-center py-5">
                                            <div class="glass-ui-strong rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px; background: rgba(var(--bs-primary-rgb), 0.1);">
                                                <i class="bi bi-cursor text-primary" style="font-size: 2rem;"></i>
                                            </div>
                                            <h6 class="text-muted">Select a user to manage lockout</h6>
                                            <p class="text-muted small text-center px-4">You can search for students, coordinators, or supervisors to manage their account security settings.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="settings-section mt-4">
                            <div class="section-header">
                                <div class="section-header-icon">
                                    <i class="bi bi-gear-wide-connected"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Lockout Policy Settings</h5>
                                    <p>Configure automatic lockout rules</p>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-1">
                                <div class="col-md-4">
                                    <label class="form-label">Attempts before Lockout</label>
                                    <select class="form-select bg-transparent border-color-opacity-1" id="lockoutAttemptsThreshold" style="border-radius: 10px;">
                                        <option class="CustomOption" value="3">3 Failed Attempts</option>
                                        <option class="CustomOption" value="5" selected>5 Failed Attempts</option>
                                        <option class="CustomOption" value="10">10 Failed Attempts</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Initial Lockout Duration</label>
                                    <select class="form-select bg-transparent border-color-opacity-1" id="lockoutInitialDuration" style="border-radius: 10px;">
                                        <option class="CustomOption" value="30">30 Minutes</option>
                                        <option class="CustomOption" value="60" selected>1 Hour</option>
                                        <option class="CustomOption" value="120">2 Hours</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input" type="checkbox" id="notifyAdminOnLockout" checked>
                                        <label class="form-check-label" for="notifyAdminOnLockout">Notify Admin on Lockout</label>
                                    </div>
                                </div>
                            </div>
                            <p class="mt-3 text-muted small">These settings apply to automatic lockouts triggered by consecutive failed login attempts. Manual locks are handled individually.</p>
                        </div>
                    </div>

                    <!-- Alert Banners Tab -->
                    <div class="tab-pane fade" id="settings-alerts" role="tabpanel"
                        aria-labelledby="settings-alerts-tab" tabindex="0">

                        <!-- Create Alert -->
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon" style="background: rgba(99,102,241,0.15); color:#818cf8;">
                                    <i class="bi bi-megaphone"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Create New Alert</h5>
                                    <p>Broadcast a notification to selected user roles</p>
                                </div>
                            </div>

                            <form id="alertCreateForm" autocomplete="off">
                                <div class="form-row">
                                    <div class="form-group-custom" style="flex:2;">
                                        <label for="alertTitle">Alert Title <span style="color:#dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="alertTitle" placeholder="e.g. Scheduled Maintenance" style="width:100%;" required>
                                    </div>
                                    <div class="form-group-custom" style="flex:1;">
                                        <label for="alertType">Alert Type</label>
                                        <select class="form-control-custom" id="alertType" style="width:100%;">
                                            <option class="CustomOption" value="info">Info (Blue)</option>
                                            <option class="CustomOption" value="success">Success (Green)</option>
                                            <option class="CustomOption" value="warning">Warning (Yellow)</option>
                                            <option class="CustomOption" value="danger">Danger (Red)</option>
                                        </select>
                                    </div>
                                    <div class="form-group-custom" style="flex:1;">
                                        <label for="alertDisplayType">Display As</label>
                                        <select class="form-control-custom" id="alertDisplayType" style="width:100%;">
                                            <option class="CustomOption" value="banner">Banner (top strip)</option>
                                            <option class="CustomOption" value="modal">Modal Popup</option>
                                            <option class="CustomOption" value="toast">Toast Notification</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label for="alertMessage">Message <span style="color:#dc3545;">*</span></label>
                                    <textarea class="form-control-custom" id="alertMessage" rows="3"
                                              placeholder="Describe the alert in detail..." style="width:100%;" required></textarea>
                                </div>

                                <div class="form-row">
                                    <div class="form-group-custom">
                                        <label for="alertTargetRoles">Target Roles</label>
                                        <select class="form-control-custom" id="alertTargetRoles" style="width:100%;">
                                            <option class="CustomOption" value="all">All Users</option>
                                            <option class="CustomOption" value="student">Students Only</option>
                                            <option class="CustomOption" value="coordinator">Coordinators Only</option>
                                            <option class="CustomOption" value="supervisor">Supervisors Only</option>
                                            <option class="CustomOption" value="student,coordinator">Students &amp; Coordinators</option>
                                            <option class="CustomOption" value="student,supervisor">Students &amp; Supervisors</option>
                                            <option class="CustomOption" value="coordinator,supervisor">Coordinators &amp; Supervisors</option>
                                        </select>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="alertExpiresAt">Expiry Date &amp; Time <small class="text-muted">(optional)</small></label>
                                        <input type="datetime-local" class="form-control-custom" id="alertExpiresAt" style="width:100%;">
                                        <div class="form-text">Leave blank for no expiry</div>
                                    </div>
                                    <div class="form-group-custom" style="align-self:center; padding-top:1.2rem;">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="alertDismissible" checked>
                                            <label class="form-check-label" for="alertDismissible">Dismissible by user</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="action-buttons mt-3">
                                    <button type="button" class="btn-action btn-save" id="alertCreateBtn">
                                        <i class="bi bi-send"></i> Broadcast Alert
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Active Alerts List -->
                        <div class="settings-section mt-4">
                            <div class="section-header">
                                <div class="section-header-icon" style="background: rgba(16,185,129,0.15); color:#34d399;">
                                    <i class="bi bi-list-check"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Manage Alerts</h5>
                                    <p>Toggle, view, or delete existing alerts</p>
                                </div>
                            </div>

                            <div id="alertListContainer">
                                <div class="text-center py-4 text-muted">
                                    <div class="spinner-mini d-inline-block me-2"></div>
                                    Loading alerts...
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Maintenance Toggles Tab -->
                    <div class="tab-pane fade" id="settings-maintenance" role="tabpanel"
                        aria-labelledby="settings-maintenance-tab" tabindex="0">
                        
                        <div class="settings-section">
                            <div class="section-header">
                                <div class="section-header-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                                    <i class="bi bi-tools"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5>Feature Maintenance Toggles</h5>
                                    <p>Disable specific system features temporarily during technical errors or updates. Users will receive standard warning banners when trying to access these features.</p>
                                </div>
                            </div>

                            <div class="vstack gap-4 mt-3">
                                <!-- DTR Submission Toggle Card -->
                                <div class="p-4 rounded-3 border" style="background: rgba(var(--bs-body-color-rgb), 0.02); border-color: rgba(var(--bs-body-color-rgb), 0.1);">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: rgba(13, 110, 253, 0.1); color: #0d6efd;">
                                                <i class="bi bi-calendar-check" style="font-size: 1.25rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Student DTR Submissions</h6>
                                                <p class="text-muted small mb-0">Allows students to clock in/out and submit their Daily Time Record.</p>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="disableDtrSubmissionToggle" <?php echo $disableDtr ? 'checked' : ''; ?>>
                                            <label class="form-check-label <?php echo $disableDtr ? 'text-danger' : 'text-success'; ?> fw-bold small" for="disableDtrSubmissionToggle" id="dtrToggleLabel">
                                                <?php echo $disableDtr ? 'DISABLED' : 'ENABLED'; ?>
                                            </label>
                                        </div>
                                    </div>
                                                                   <div class="form-group-custom mt-2 <?php echo $disableDtr ? '' : 'd-none'; ?>" id="dtrReasonContainer">
                                        <label for="dtrDisableReasonInput" class="form-label text-muted small fw-bold">CUSTOM WARNING MESSAGE</label>
                                        <input type="text" class="form-control bg-transparent border-color-opacity-1" id="dtrDisableReasonInput" 
                                               value="<?php echo htmlspecialchars($dtrDisableReason); ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        <div class="form-text text-muted">This message will be shown to students when DTR submissions are locked.</div>
                                    </div>
                                    <!-- Scheduled Maintenance Fields -->
                                    <div class="row g-3 mt-2 pt-2 border-top" style="border-color: rgba(var(--bs-body-color-rgb), 0.05) !important;">
                                        <div class="col-md-6">
                                            <label for="dtrMaintenanceStartInput" class="form-label text-muted small fw-bold">SCHEDULED START TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="dtrMaintenanceStartInput" 
                                                   value="<?php echo !empty($dtrMaintenanceStart) ? date('Y-m-d\TH:i', strtotime($dtrMaintenanceStart)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="dtrMaintenanceEndInput" class="form-label text-muted small fw-bold">SCHEDULED END TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="dtrMaintenanceEndInput" 
                                                   value="<?php echo !empty($dtrMaintenanceEnd) ? date('Y-m-d\TH:i', strtotime($dtrMaintenanceEnd)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-12 mt-1">
                                            <div class="form-text text-muted small">Configure a future time range to schedule an automatic maintenance lockout. Banners will show a countdown beforehand!</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Journal Submission Toggle Card -->
                                <div class="p-4 rounded-3 border" style="background: rgba(var(--bs-body-color-rgb), 0.02); border-color: rgba(var(--bs-body-color-rgb), 0.1);">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: rgba(25, 135, 84, 0.1); color: #198754;">
                                                <i class="bi bi-book" style="font-size: 1.25rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Student Weekly Journals</h6>
                                                <p class="text-muted small mb-0">Allows students to submit and edit their weekly narrative journals.</p>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="disableJournalSubmissionToggle" <?php echo $disableJournal ? 'checked' : ''; ?>>
                                            <label class="form-check-label <?php echo $disableJournal ? 'text-danger' : 'text-success'; ?> fw-bold small" for="disableJournalSubmissionToggle" id="journalToggleLabel">
                                                <?php echo $disableJournal ? 'DISABLED' : 'ENABLED'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group-custom mt-2 <?php echo $disableJournal ? '' : 'd-none'; ?>" id="journalReasonContainer">
                                        <label for="journalDisableReasonInput" class="form-label text-muted small fw-bold">CUSTOM WARNING MESSAGE</label>
                                        <input type="text" class="form-control bg-transparent border-color-opacity-1" id="journalDisableReasonInput" 
                                               value="<?php echo htmlspecialchars($journalDisableReason); ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        <div class="form-text text-muted">This message will be shown to students when weekly narrative journal submissions are locked.</div>
                                    </div>
                                    <!-- Scheduled Maintenance Fields -->
                                    <div class="row g-3 mt-2 pt-2 border-top" style="border-color: rgba(var(--bs-body-color-rgb), 0.05) !important;">
                                        <div class="col-md-6">
                                            <label for="journalMaintenanceStartInput" class="form-label text-muted small fw-bold">SCHEDULED START TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="journalMaintenanceStartInput" 
                                                   value="<?php echo !empty($journalMaintenanceStart) ? date('Y-m-d\TH:i', strtotime($journalMaintenanceStart)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="journalMaintenanceEndInput" class="form-label text-muted small fw-bold">SCHEDULED END TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="journalMaintenanceEndInput" 
                                                   value="<?php echo !empty($journalMaintenanceEnd) ? date('Y-m-d\TH:i', strtotime($journalMaintenanceEnd)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-12 mt-1">
                                            <div class="form-text text-muted small">Configure a future time range to schedule an automatic maintenance lockout. Banners will show a countdown beforehand!</div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Evaluation Submission Toggle Card -->
                                <div class="p-4 rounded-3 border" style="background: rgba(var(--bs-body-color-rgb), 0.02); border-color: rgba(var(--bs-body-color-rgb), 0.1);">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex align-items-center">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                                                <i class="bi bi-star" style="font-size: 1.25rem;"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold">Supervisor Student Evaluations</h6>
                                                <p class="text-muted small mb-0">Allows company supervisors to submit monthly or final evaluations for students.</p>
                                            </div>
                                        </div>
                                        <div class="form-check form-switch form-switch-lg">
                                            <input class="form-check-input" type="checkbox" id="disableEvaluationSubmissionToggle" <?php echo $disableEvaluation ? 'checked' : ''; ?>>
                                            <label class="form-check-label <?php echo $disableEvaluation ? 'text-danger' : 'text-success'; ?> fw-bold small" for="disableEvaluationSubmissionToggle" id="evaluationToggleLabel">
                                                <?php echo $disableEvaluation ? 'DISABLED' : 'ENABLED'; ?>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-group-custom mt-2 <?php echo $disableEvaluation ? '' : 'd-none'; ?>" id="evaluationReasonContainer">
                                        <label for="evaluationDisableReasonInput" class="form-label text-muted small fw-bold">CUSTOM WARNING MESSAGE</label>
                                        <input type="text" class="form-control bg-transparent border-color-opacity-1" id="evaluationDisableReasonInput" 
                                               value="<?php echo htmlspecialchars($evaluationDisableReason); ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        <div class="form-text text-muted">This message will be shown to supervisors when student evaluations are locked.</div>
                                    </div>
                                    <!-- Scheduled Maintenance Fields -->
                                    <div class="row g-3 mt-2 pt-2 border-top" style="border-color: rgba(var(--bs-body-color-rgb), 0.05) !important;">
                                        <div class="col-md-6">
                                            <label for="evaluationMaintenanceStartInput" class="form-label text-muted small fw-bold">SCHEDULED START TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="evaluationMaintenanceStartInput" 
                                                   value="<?php echo !empty($evaluationMaintenanceStart) ? date('Y-m-d\TH:i', strtotime($evaluationMaintenanceStart)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="evaluationMaintenanceEndInput" class="form-label text-muted small fw-bold">SCHEDULED END TIME</label>
                                            <input type="datetime-local" class="form-control bg-transparent border-color-opacity-1" id="evaluationMaintenanceEndInput" 
                                                   value="<?php echo !empty($evaluationMaintenanceEnd) ? date('Y-m-d\TH:i', strtotime($evaluationMaintenanceEnd)) : ''; ?>" style="border-radius: 10px; color: var(--bs-body-color);">
                                        </div>
                                        <div class="col-12 mt-1">
                                            <div class="form-text text-muted small">Configure a future time range to schedule an automatic maintenance lockout. Banners will show a countdown beforehand!</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div> <!-- End of settings-tabContent -->

                <!-- Global Action Buttons -->
                <div class="settings-section" style="background: transparent; border: none; box-shadow: none; padding: 1rem 0;">
                    <div class="action-buttons" style="margin: 0; padding: 0; border: none;">
                        <button class="btn-action btn-save" id="saveSettingsBtn">
                            <i class="bi bi-check-circle"></i>
                            <span>Save All Settings</span>
                        </button>
                        <button class="btn-action btn-reset" id="resetSettingsBtn">
                            <i class="bi bi-arrow-counterclockwise"></i>
                            <span>Reset to Defaults</span>
                        </button>
                    </div>
                </div>

            </div>
        </main>
    </div>
</body>

</html>
