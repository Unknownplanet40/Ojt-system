<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    header("Location: ../Login");
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Companies";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/CompaniesScripts.js"></script>
    <title><?= $ShortTitle ?> - Companies</title>
</head>

<body class="login-page" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <input type="hidden" id="csrfToken" value="<?= $_SESSION['csrf_token'] ?>">
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
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="row g-3 mb-4 align-items-stretch">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 bg-blur-5 bg-semi-transparent" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0" style="width: 52px; height: 52px;">
                                        <i class="bi bi-building fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1 min-w-0">
                                        <p class="mb-1 text-uppercase fw-semibold text-primary small">Company Directory</p>
                                        <h4 class="mb-1 fw-semibold text-break">OJT Partner Establishments</h4>
                                        <p class="mb-0 text-muted small">View and explore accredited companies and available OJT slots for students.</p>
                                    </div>
                                    <div class="ms-md-auto d-flex gap-2 flex-wrap">
                                        <button class="btn btn-outline-secondary rounded-pill px-3" id="refreshBtn"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>;">
                    <div class="card-body p-3 p-md-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-12 col-lg-6">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="searchInput">Search Companies</label>
                                <input type="search" class="form-control bg-blur-5 bg-semi-transparent shadow-none" id="searchInput" placeholder="Search by name, industry, or city..." style="--blur-lvl: <?= $opacitylvl ?>;">
                            </div>
                            <div class="col-6 col-lg-3">
                                <label class="form-label small fw-semibold text-uppercase text-muted" for="statusFilter">Accreditation</label>
                                <select class="form-select bg-blur-5 bg-semi-transparent shadow-none" id="statusFilter" style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <option value="">All Statuses</option>
                                    <option value="active">Active</option>
                                    <option value="pending">Pending</option>
                                    <option value="expired">Expired</option>
                                </select>
                            </div>
                            <div class="col-6 col-lg-3">
                                <button type="button" class="btn btn-outline-secondary w-100" id="clearFiltersBtn">Clear filters</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="companyGrid" class="row g-3 g-md-4">
                    <!-- Dynamic Content -->
                </div>

                <div class="p-5 text-center d-none" id="emptyState">
                    <div class="mx-auto mb-3 d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 64px; height: 64px;">
                        <i class="bi bi-building fs-4"></i>
                    </div>
                    <h5 class="mb-2 text-light">No companies found</h5>
                    <p class="text-muted mb-0">Try adjusting your search or filters.</p>
                </div>
            </div>
        </main>
    </div>

    <!-- Company Details Modal -->
    <div class="modal fade" id="companyDetailsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content bg-blur-15 bg-semi-transparent border-light border-opacity-10 text-light rounded-4 shadow-lg" style="background: rgba(15, 23, 42, 0.9); backdrop-filter: blur(20px);">
                <div class="modal-header border-bottom border-light border-opacity-10 p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 48px; height: 48px;">
                            <i class="bi bi-building fs-4"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold mb-0 text-white" id="detCompanyName">Company Name</h5>
                            <small class="text-white-50" id="detCompanyIndustry">Industry</small>
                        </div>
                    </div>
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <input type="file" id="moaFileInput" class="d-none" accept="application/pdf">
                        <input type="hidden" id="currentCompanyUuid">
                        <button class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm border-opacity-50" id="uploadMoABtn">
                            <i class="bi bi-upload me-2"></i>Upload MOA
                        </button>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <div class="col-md-7">
                            <h6 class="text-uppercase fw-bold text-primary small mb-3">Company Information</h6>
                            <div class="vstack gap-3">
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-geo-alt text-primary mt-1"></i>
                                    <div>
                                        <div class="small text-white-50">Full Address</div>
                                        <div id="detCompanyAddress" class="text-white">---</div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-globe text-primary mt-1"></i>
                                    <div>
                                        <div class="small text-white-50">Website</div>
                                        <a href="#" target="_blank" class="text-decoration-none text-info" id="detCompanyWebsite">---</a>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-briefcase text-primary mt-1"></i>
                                    <div>
                                        <div class="small text-white-50">Work Setup & Programs</div>
                                        <div id="detCompanySetup" class="text-white">---</div>
                                        <div class="mt-2 d-flex flex-wrap gap-1" id="detCompanyPrograms"></div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-start gap-3">
                                    <i class="bi bi-person-badge text-primary mt-1"></i>
                                    <div>
                                        <div class="small text-white-50">Assigned Supervisors</div>
                                        <div id="detSupervisorList" class="vstack gap-2 mt-1">
                                            <div class="text-white">---</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="p-4 rounded-4 h-100" style="background: rgba(255, 255, 255, 0.03); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.08) !important;">
                                <h6 class="text-uppercase fw-bold text-primary small mb-3">Primary Contact</h6>
                                <div class="vstack gap-3" id="detContactInfo">
                                    <!-- Dynamic Contact Info -->
                                </div>
                                <hr class="border-light border-opacity-10 my-3">
                                <h6 class="text-uppercase fw-bold text-primary small mb-3">MOA Status</h6>
                                <div id="detMoaStatus">
                                    <!-- Dynamic MOA Status -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-top border-light border-opacity-10">
                        <h6 class="text-uppercase fw-bold text-primary small mb-3">Placed Students (<span id="detStudentCount">0</span>)</h6>
                        <div class="list-group list-group-flush border border-light border-opacity-10 rounded-4 overflow-hidden" id="detStudentList" style="max-height: 250px; overflow-y: auto;">
                            <!-- Dynamic Student List -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-light border-opacity-10 p-3">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
