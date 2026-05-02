<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('Asia/Manila');

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "AuditLogs";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <link rel="stylesheet" href="../../../Assets/style/admin/AuditLogsStyles.css">
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/AuditLogs.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
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
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack mb-3">
                    <div>
                        <h4 class="mb-1" id="auditLogsTitle">Audit Logs</h4>
                        <p class="blockquote-footer pt-1 fs-6 mb-0">Read-only system activity and authentication trail.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto me-2 text-nowrap" id="exportAuditCsvBtn">
                        <i class="bi bi-download me-1"></i>
                        Export CSV
                    </button>
                    <button class="btn btn-sm btn-outline-secondary text-nowrap" id="refreshAuditLogsBtn">
                        <i class="bi bi-arrow-clockwise me-1"></i>
                        Refresh
                    </button>
                </div>

                <div class="container p-0">
                    <div class="row g-3 mb-4 align-items-end">
                        <div class="col-12 col-md-3 col-lg-2">
                            <label for="auditDateFrom" class="form-label small fw-semibold text-muted mb-2">Date From</label>
                            <input type="date" class="form-control bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditDateFrom">
                        </div>
                        <div class="col-12 col-md-3 col-lg-2">
                            <label for="auditDateTo" class="form-label small fw-semibold text-muted mb-2">Date To</label>
                            <input type="date" class="form-control bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditDateTo">
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="auditSourceFilter" class="form-label small fw-semibold text-muted mb-2">Source</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditSourceFilter">
                                <option value="all" class="CustomOption" selected>All Sources</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="auditModuleFilter" class="form-label small fw-semibold text-muted mb-2">Module</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditModuleFilter">
                                <option value="" class="CustomOption" selected>All Modules</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="auditEventFilter" class="form-label small fw-semibold text-muted mb-2">Action Type</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditEventFilter">
                                <option value="" class="CustomOption" selected>All Actions</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6 col-lg-2">
                            <label for="auditActorFilter" class="form-label small fw-semibold text-muted mb-2">User</label>
                            <select class="form-select bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditActorFilter">
                                <option value="" class="CustomOption" selected>All Users</option>
                            </select>
                        </div>

                        <div class="col-12 col-lg-10">
                            <label for="auditSearchInput" class="form-label small fw-semibold text-muted mb-2">Search</label>
                            <div class="input-group rounded-3 overflow-hidden">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-blur-5 bg-semi-transparent border-0 shadow-none" id="auditSearchInput" placeholder="Search description, target UUID, IP, user-agent, reason...">
                            </div>
                        </div>
                        <div class="col-12 col-lg-2 d-flex">
                            <button class="btn btn-outline-secondary border rounded-3 shadow-none w-100 d-none" id="clearAuditFiltersBtn" title="Clear filters">
                                <i class="bi bi-x-circle-fill me-1"></i>Clear Filters
                            </button>
                        </div>
                    </div>

                    <div class="hstack mb-2 px-1">
                        <small class="text-muted">Showing <span class="fw-semibold text-body" id="auditLogCount">0</span> records</small>
                    </div>

                    <div class="row g-3" id="auditLogsContainer">
                        <!-- Audit Log Cards will be appended here -->
                    </div>

                    <div class="hstack gap-2 justify-content-end mt-3">
                        <small class="text-muted me-auto" id="auditPageInfo">Page 1 of 1</small>
                        <div class="hstack gap-2 align-items-center me-2">
                            <small class="text-muted text-nowrap">Rows</small>
                            <select class="form-select form-select-sm bg-blur-5 bg-semi-transparent border rounded-3 shadow-none" id="auditPageSize" style="width: 90px;">
                                <option value="10" class="CustomOption">10</option>
                                <option value="25" class="CustomOption" selected>25</option>
                                <option value="50" class="CustomOption">50</option>
                                <option value="100" class="CustomOption">100</option>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-outline-secondary" id="auditPrevBtn" disabled>
                            <i class="bi bi-chevron-left me-1"></i>Previous
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" id="auditNextBtn" disabled>
                            Next<i class="bi bi-chevron-right ms-1"></i>
                        </button>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="auditLogDetailsModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-1"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Audit Entry Details</h5>
                        <p class="text-muted small mb-0" id="auditDetailOccurredAt">—</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-12 col-md-6">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-3">User Information</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-person text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Actor:</span>
                                    <span id="auditDetailActor" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-shield-check text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Role:</span>
                                    <span id="auditDetailRole" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-globe text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">IP:</span>
                                    <code id="auditDetailIp" class="bg-transparent p-0 text-primary">—</code>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-3">Event Details</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body h-100">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-activity text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Action:</span>
                                    <span id="auditDetailEvent" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-box text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Module:</span>
                                    <span id="auditDetailModule" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-hdd-network text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Source:</span>
                                    <span id="auditDetailSource" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-crosshair text-secondary me-2"></i>
                                    <span class="text-muted me-2 small">Target:</span>
                                    <code id="auditDetailTarget" class="bg-transparent p-0">—</code>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-3">Description</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body">
                                <p class="mb-0" id="auditDetailDescription">—</p>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-3">Authentication Context</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-muted me-2 small">Login Result:</span>
                                    <span id="auditDetailLoginResult" class="fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="text-muted me-2 small">Fail Reason:</span>
                                    <span id="auditDetailFailReason" class="text-danger fw-medium">—</span>
                                </div>
                                <div class="d-flex align-items-start">
                                    <span class="text-muted me-2 small text-nowrap">User Agent:</span>
                                    <span id="auditDetailUserAgent" class="small text-break">—</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <h6 class="fw-semibold text-muted small text-uppercase mb-3">Metadata</h6>
                            <div class="p-3 bg-body-tertiary bg-opacity-50 rounded-3 text-body">
                                <div id="auditDetailMeta" class="small"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
