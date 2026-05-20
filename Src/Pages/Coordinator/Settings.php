<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Settings";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/SettingsScript.js"></script>
    <title>Settings - <?= $ShortTitle ?></title>
    <style>
        .settings-layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 0;
            min-height: 600px;
        }

        .settings-sidebar {
            border-right: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
            padding: 1.5rem 0;
        }

        .sidebar-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--bs-secondary);
            letter-spacing: 0.1em;
            text-transform: uppercase;
            padding: 0 1.5rem;
            margin-bottom: 0.75rem;
        }

        .settings-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 1.5rem;
            font-size: 0.9rem;
            color: var(--bs-body-color);
            cursor: pointer;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            opacity: 0.7;
            text-decoration: none;
        }

        .settings-nav-item:hover {
            background: rgba(var(--bs-body-color-rgb), 0.05);
            opacity: 1;
        }

        .settings-nav-item.active {
            background: rgba(var(--bs-primary-rgb), 0.1);
            color: var(--bs-primary);
            border-left-color: var(--bs-primary);
            font-weight: 600;
            opacity: 1;
        }

        .settings-nav-item i {
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .settings-content {
            padding: 2rem;
            max-width: 900px;
        }

        .pane {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .pane.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .pane-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .pane-sub {
            font-size: 0.875rem;
            color: var(--bs-secondary-color);
            margin-bottom: 2rem;
        }

        .settings-section {
            background: rgba(var(--bs-body-bg-rgb), 0.03);
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .section-sub {
            font-size: 0.8rem;
            color: var(--bs-secondary-color);
            margin-bottom: 1.25rem;
        }

        .field-label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--bs-secondary-color);
            margin-bottom: 0.5rem;
        }

        .toggle-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
        }

        .toggle-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .toggle-info {
            flex: 1;
        }

        .toggle-label {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.15rem;
        }

        .toggle-desc {
            font-size: 0.8rem;
            color: var(--bs-secondary-color);
        }

        .toggle-track {
            width: 40px;
            height: 22px;
            background: rgba(var(--bs-body-color-rgb), 0.15);
            border-radius: 20px;
            position: relative;
            cursor: pointer;
            transition: background 0.3s;
            flex-shrink: 0;
            margin-left: 1.5rem;
        }

        .toggle-track.on {
            background: var(--bs-success);
        }

        .toggle-thumb {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            transition: transform 0.3s;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .toggle-track.on .toggle-thumb {
            transform: translateX(18px);
        }

        .theme-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 1rem;
        }

        .theme-card {
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-radius: 0.75rem;
            padding: 1rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s ease;
            background: rgba(var(--bs-body-bg-rgb), 0.02);
        }

        .theme-card:hover {
            border-color: rgba(var(--bs-primary-rgb), 0.3);
            background: rgba(var(--bs-primary-rgb), 0.03);
        }

        .theme-card.selected {
            border: 2px solid var(--bs-primary);
            background: rgba(var(--bs-primary-rgb), 0.05);
        }

        .theme-preview {
            height: 60px;
            border-radius: 0.5rem;
            margin-bottom: 0.75rem;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);
            display: flex;
            gap: 4px;
            overflow: hidden;
            padding: 8px;
        }

        .theme-preview-bar {
            border-radius: 4px;
        }

        .accent-grid {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .accent-dot {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .accent-dot.selected {
            border-color: white;
            box-shadow: 0 0 0 2px var(--bs-primary);
        }

        .accent-dot i {
            font-size: 0.9rem;
            color: white;
            display: none;
        }

        .accent-dot.selected i {
            display: block;
        }

        .avatar-circle {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(var(--bs-primary-rgb), 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--bs-primary);
            border: 1px solid rgba(var(--bs-primary-rgb), 0.2);
        }

        .secret-field {
            position: relative;
        }

        .reveal-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: var(--bs-secondary-color);
            padding: 0;
            display: flex;
            align-items: center;
        }

        @media (max-width: 768px) {
            .settings-layout {
                grid-template-columns: 1fr;
                min-height: auto;
            }

            .settings-sidebar {
                border-right: none;
                border-bottom: none;
                display: flex;
                flex-direction: row;
                padding: 0.75rem 1rem;
                gap: 0.5rem;
                position: sticky;
                top: 0;
                z-index: 10;
                background: rgba(var(--bs-body-bg-rgb), 0.85) !important;
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
            }

            .sidebar-label {
                display: none;
            }

            .settings-nav-item {
                flex: 1;
                border-left: none;
                border-radius: 14px;
                padding: 10px 8px;
                font-size: 0.75rem;
                font-weight: 600;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 4px;
                text-align: center;
                border: 1px solid rgba(var(--bs-body-color-rgb), 0.06);
                background: rgba(var(--bs-body-color-rgb), 0.03);
                min-width: 0;
            }

            .settings-nav-item i {
                font-size: 1.2rem;
                margin: 0;
            }

            .settings-nav-item span {
                display: inline !important;
                font-size: 0.7rem;
                letter-spacing: 0.01em;
                line-height: 1.2;
            }

            .settings-nav-item:hover {
                background: rgba(var(--bs-primary-rgb), 0.06);
                border-color: rgba(var(--bs-primary-rgb), 0.15);
            }

            .settings-nav-item.active {
                background: var(--bs-primary);
                color: white;
                border-color: var(--bs-primary);
                box-shadow: 0 4px 14px rgba(var(--bs-primary-rgb), 0.25);
            }

            .settings-nav-item.active span {
                color: white;
            }

            .settings-content {
                padding: 1.25rem 1rem;
            }

            .pane-title {
                font-size: 1.25rem;
            }

            .theme-grid {
                grid-template-columns: repeat(3, 1fr);
                gap: 0.65rem;
            }

            .theme-card {
                padding: 0.75rem 0.5rem;
            }

            .theme-preview {
                height: 48px;
                margin-bottom: 0.5rem;
            }

            .settings-section {
                padding: 1.25rem 1rem;
            }
        }

        .log-scroll-container {
            max-height: 460px;
            overflow-y: auto;
            padding-right: 4px;
            scrollbar-width: thin;
            scrollbar-color: rgba(var(--bs-primary-rgb), 0.2) transparent;
        }

        .log-scroll-container::-webkit-scrollbar {
            width: 4px;
        }

        .log-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(var(--bs-primary-rgb), 0.1);
            border-radius: 10px;
        }

        .log-scroll-container::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--bs-primary-rgb), 0.3);
        }
    </style>
</head>

<body class="login-page" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="w-100 min-vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75" id="pageLoader">
        <div class="d-flex flex-column align-items-center">
            <span class="loader"></span>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php"; ?>

            <div class="container-fluid p-0">
                <div class="settings-layout">
                    <!-- Sidebar -->
                    <div class="settings-sidebar bg-blur-5 bg-semi-transparent" style="--blur-lvl: 0.1">
                        <div class="sidebar-label">User Settings</div>
                        <div class="settings-nav-item active" data-pane="appearance">
                            <i class="bi bi-palette"></i> <span class="d-none d-md-inline">Appearance</span>
                        </div>
                        <div class="settings-nav-item" data-pane="sessions">
                            <i class="bi bi-clock-history"></i> <span class="d-none d-md-inline">Log History</span>
                        </div>
                        <div class="settings-nav-item" data-pane="security">
                            <i class="bi bi-shield-lock"></i> <span class="d-none d-md-inline">Account Security</span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="settings-content">
                        
                        <!-- Appearance Pane -->
                        <div id="pane-appearance" class="pane active">
                            <div class="pane-title">Appearance</div>
                            <p class="pane-sub">Customize how the interface looks on your device.</p>

                            <div class="settings-section">
                                <div class="section-title">Theme Mode</div>
                                <p class="section-sub">Choose between light, dark, or system default.</p>
                                <div class="theme-grid">
                                    <div class="theme-card selected" data-theme="light">
                                        <div class="theme-preview bg-light">
                                            <div class="theme-preview-bar bg-secondary-subtle" style="width:30%"></div>
                                            <div class="flex-grow-1 vstack gap-1">
                                                <div class="theme-preview-bar bg-primary" style="height:10px"></div>
                                                <div class="theme-preview-bar bg-secondary-subtle" style="height:10px"></div>
                                                <div class="theme-preview-bar bg-secondary-subtle" style="height:10px; width:70%"></div>
                                            </div>
                                        </div>
                                        <div class="fw-bold small">Light</div>
                                    </div>
                                    <div class="theme-card" data-theme="dark">
                                        <div class="theme-preview bg-dark">
                                            <div class="theme-preview-bar bg-secondary" style="width:30%; opacity:0.3"></div>
                                            <div class="flex-grow-1 vstack gap-1">
                                                <div class="theme-preview-bar bg-primary" style="height:10px"></div>
                                                <div class="theme-preview-bar bg-secondary" style="height:10px; opacity:0.3"></div>
                                                <div class="theme-preview-bar bg-secondary" style="height:10px; width:70%; opacity:0.3"></div>
                                            </div>
                                        </div>
                                        <div class="fw-bold small">Dark</div>
                                    </div>
                                    <div class="theme-card" data-theme="auto">
                                        <div class="theme-preview" style="background: linear-gradient(90deg, #f8f9fa 50%, #212529 50%)">
                                            <div class="theme-preview-bar bg-secondary-subtle" style="width:30%"></div>
                                            <div class="flex-grow-1 vstack gap-1">
                                                <div class="theme-preview-bar bg-primary" style="height:10px"></div>
                                                <div class="theme-preview-bar bg-secondary-subtle" style="height:10px"></div>
                                            </div>
                                        </div>
                                        <div class="fw-bold small">System</div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-2">
                                <button id="resetAppearance" class="btn btn-outline-secondary rounded-pill px-4">Reset</button>
                                <button id="saveAppearance" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                            </div>
                        </div>





                        <!-- Sessions Pane -->
                        <div id="pane-sessions" class="pane">
                            <div class="pane-title">Account Activity</div>
                            <p class="pane-sub">Monitor your recent login history and security credentials.</p>

                            <div class="settings-section">
                                <div class="section-title mb-3">Recent Logins</div>
                                <div class="vstack gap-2 log-scroll-container" id="logHistoryContainer">
                                    <!-- Dynamic logs will be loaded here -->
                                </div>
                            </div>

                            <div class="settings-section">
                                <div class="section-title mb-2">Developer Information</div>
                                <p class="section-sub">Security tokens for the current session.</p>
                                <div class="bg-dark bg-opacity-25 p-3 rounded font-monospace small position-relative overflow-hidden">
                                    <div class="text-muted mb-1 small">CSRF_TOKEN:</div>
                                    <div class="text-success text-break"><?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'Not set' ?></div>
                                    <i class="bi bi-shield-lock position-absolute end-0 bottom-0 m-2 opacity-25 fs-1"></i>
                                </div>
                            </div>

                            <div class="settings-section bg-blur-5 bg-semi-transparent border-danger border-opacity-25">
                                <div class="section-title text-danger">Danger Zone</div>
                                <p class="small text-danger mb-3">Ending all other sessions will log you out from all devices except this one.</p>
                                <button class="btn btn-danger rounded-pill px-4">Sign Out Other Devices</button>
                            </div>
                        </div>

                        <!-- Security Pane -->
                        <div id="pane-security" class="pane">
                            <div class="pane-title">Account Security</div>
                            <p class="pane-sub">Manage user access and security restrictions.</p>

                            <div class="settings-section">
                                <div class="row g-4">
                                    <div class="col-md-5">
                                        <div class="card bg-transparent border-0 h-100">
                                            <div class="card-body p-0">
                                                <label class="form-label text-muted small fw-bold">SEARCH USER</label>
                                                <div class="input-group input-group-sm">
                                                    <span class="input-group-text bg-transparent border-end-0 text-muted" style="border-radius: 10px 0 0 10px;">
                                                        <i class="bi bi-search"></i>
                                                    </span>
                                                    <input type="text" id="userSearchInput" class="form-control bg-transparent border-start-0 ps-0" 
                                                           placeholder="Search by name or email..." style="border-radius: 0 10px 10px 0;">
                                                </div>
                                                
                                                <div id="userSearchResults" class="mt-3 overflow-auto" style="max-height: 400px; border-radius: 12px; background: rgba(var(--bs-body-color-rgb), 0.02); border: 1px solid rgba(var(--bs-body-color-rgb), 0.05);">
                                                    <div class="text-center py-5 text-muted opacity-50">
                                                        <i class="bi bi-people" style="font-size: 2rem;"></i>
                                                        <p class="mt-2 small">Start typing to find users</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-7">
                                        <div id="userLockoutDetails" class="card h-100 border-0" style="border-radius: 15px; background: rgba(var(--bs-body-color-rgb), 0.03); display: none;">
                                            <div class="card-body p-4">
                                                <div class="d-flex align-items-center mb-4">
                                                    <div id="selectedUserInitials" class="rounded-circle d-flex align-items-center justify-content-center me-3" 
                                                         style="width: 54px; height: 54px; background: var(--bs-primary); color: white; font-weight: 700; font-size: 1.1rem;">JD</div>
                                                    <div>
                                                        <h6 id="selectedUserName" class="mb-0 fw-bold">John Doe</h6>
                                                        <p id="selectedUserEmail" class="text-muted small mb-0">john.doe@example.com</p>
                                                        <span id="selectedUserRole" class="badge bg-secondary-subtle text-secondary border border-secondary-subtle mt-1" style="font-size: 0.65rem;">STUDENT</span>
                                                    </div>
                                                    <div class="ms-auto text-end">
                                                        <div id="selectedUserStatus">
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ACTIVE</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="p-3 rounded-4 mb-4" id="lockoutInfoBox" style="background: rgba(var(--bs-body-color-rgb), 0.04); border: 1px solid rgba(var(--bs-body-color-rgb), 0.05);">
                                                    <div class="row text-center g-3">
                                                        <div class="col-4">
                                                            <label class="d-block text-muted small mb-1" style="font-size: 0.65rem; font-weight: 600;">FAILED ATTEMPTS</label>
                                                            <h5 id="failedAttemptsCount" class="mb-0 fw-bold">0</h5>
                                                        </div>
                                                        <div class="col-8 border-start border-secondary border-opacity-10">
                                                            <label class="d-block text-muted small mb-1" style="font-size: 0.65rem; font-weight: 600;">LOCKOUT EXPIRES</label>
                                                            <h6 id="lockoutExpiryText" class="mb-0 fw-bold small">Not Locked</h6>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="vstack gap-2">
                                                    <label class="form-label text-muted small fw-bold mb-1">COORDINATOR ACTIONS</label>
                                                    <div class="d-flex gap-2">
                                                        <button id="unlockAccountBtn" class="btn btn-success flex-grow-1 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3" disabled style="font-size: 0.85rem;">
                                                            <i class="bi bi-unlock"></i> Unlock Account
                                                        </button>
                                                        
                                                        <div class="dropdown flex-grow-1">
                                                            <button id="lockAccountDropdown" class="btn btn-outline-danger w-100 py-2 d-flex align-items-center justify-content-center gap-2 rounded-3 dropdown-toggle" data-bs-toggle="dropdown" style="font-size: 0.85rem;">
                                                                <i class="bi bi-lock"></i> Manual Lock
                                                            </button>
                                                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg p-2" style="border-radius: 12px; width: 220px; background: var(--bs-body-bg); border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);">
                                                                <li><h6 class="dropdown-header small text-uppercase fw-bold opacity-75">Lock Duration</h6></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option small" href="#" data-hours="1">1 Hour</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option small" href="#" data-hours="3">3 Hours</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option small" href="#" data-hours="6">6 Hours</a></li>
                                                                <li><a class="dropdown-item rounded-3 manual-lock-option small" href="#" data-hours="12">12 Hours</a></li>
                                                                <li><hr class="dropdown-divider"></li>
                                                                <li><a class="dropdown-item rounded-3 text-danger manual-lock-option small" href="#" data-hours="24">24 Hours (Restrict)</a></li>
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="mt-4 p-3 rounded-3 bg-primary bg-opacity-10 border border-primary border-opacity-10">
                                                    <p class="mb-0 text-primary small" style="font-size: 0.75rem; line-height: 1.4;">
                                                        <i class="bi bi-info-circle-fill me-1"></i> 
                                                        Coordinators can unlock student accounts or temporarily restrict access if security concerns arise. Administrative policies still apply.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="noUserSelected" class="d-none card h-100 border-0 d-flex align-items-center justify-content-center p-5 text-center text-muted opacity-50" style="border-radius: 15px; background: rgba(var(--bs-body-color-rgb), 0.02); border: 1px dashed rgba(var(--bs-body-color-rgb), 0.1);">
                                            <i class="bi bi-person-badge fs-1 mb-3"></i>
                                            <p class="small mb-0">Select a user from the results to manage their account security</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </main>
    </div>

</body>

</html>
