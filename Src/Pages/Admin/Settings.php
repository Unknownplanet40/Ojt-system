<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Settings";

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
                        <div class="settings-section">
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
                                        <label for="emailAddress">Email Address <span style="color: #dc3545;">*</span></label>
                                        <input type="email" class="form-control-custom" id="emailAddress" 
                                               placeholder="sender@example.com" style="width: 100%;">
                                        <div class="form-text">The sender email address</div>
                                    </div>
                                    <div class="form-group-custom">
                                        <label for="emailFromName">From Name <span style="color: #dc3545;">*</span></label>
                                        <input type="text" class="form-control-custom" id="emailFromName" 
                                               placeholder="e.g., OJT System" style="width: 100%;">
                                        <div class="form-text">Display name for emails</div>
                                    </div>
                                </div>

                                <div class="form-group-custom">
                                    <label for="emailAppPassword">App Password <span style="color: #dc3545;">*</span></label>
                                    <input type="password" class="form-control-custom" id="emailAppPassword" 
                                           placeholder="Enter your app-specific password" style="width: 100%; max-width: 400px;">
                                    <div class="form-text">
                                        For Gmail: Use an App Password (not your regular password)<br>
                                        For others: Use your SMTP password or authentication token
                                    </div>
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

                        <!-- Danger Zone -->
                        <div class="settings-section" style="border-color: rgba(220, 53, 69, 0.2); background: rgba(220, 53, 69, 0.03);">
                            <div class="section-header" style="border-bottom-color: rgba(220, 53, 69, 0.1);">
                                <div class="section-header-icon" style="background: rgba(220, 53, 69, 0.15); color: #dc3545;">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </div>
                                <div class="section-header-text">
                                    <h5 style="color: #dc3545;">Danger Zone</h5>
                                    <p>Irreversible actions — use with caution</p>
                                </div>
                            </div>

                            <div class="vstack gap-2">
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(var(--bs-body-color-rgb), 0.02); border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.1);">
                                    <div>
                                        <div style="font-weight: 500; color: var(--bs-body-color);">Clear Activity Log</div>
                                        <div style="font-size: 0.85rem; color: var(--bs-secondary-color);">Permanently deletes all entries from activity_log table</div>
                                    </div>
                                    <button class="btn-action" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3);">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </div>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 1rem; background: rgba(var(--bs-body-color-rgb), 0.02); border-radius: 8px; border: 1px solid rgba(220, 53, 69, 0.1);">
                                    <div>
                                        <div style="font-weight: 500; color: var(--bs-body-color);">Clear Login Audit Log</div>
                                        <div style="font-size: 0.85rem; color: var(--bs-secondary-color);">Permanently deletes all entries from login_audit_log table</div>
                                    </div>
                                    <button class="btn-action" style="background: rgba(220, 53, 69, 0.2); color: #dc3545; border: 1px solid rgba(220, 53, 69, 0.3);">
                                        <i class="bi bi-trash"></i> Clear
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

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
            </div>
        </main>
    </div>
</body>

</html>
