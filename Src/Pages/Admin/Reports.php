<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../Login');
    exit;
}

require_once "../../../Assets/SystemInfo.php";
$CurrentPage = "Reports";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/ReportsScript.js"></script>
    <title>Reports & Analytics | <?= $ShortTitle ?></title>
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
                <!-- Header Section -->
                <div class="row g-3 mb-4">
                    <div class="col-12">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 text-primary p-3">
                                            <i class="bi bi-bar-chart-fill fs-3"></i>
                                        </div>
                                        <div>
                                            <h2 class="fw-bold mb-0">Reports & Analytics</h2>
                                            <p class="text-body-secondary mb-0">Monitor internship progress and system performance metrics.</p>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-primary rounded-pill px-4" id="refreshReports">
                                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                                        </button>
                                        <button class="btn btn-primary rounded-pill px-4" id="exportPDF">
                                            <i class="bi bi-file-earmark-pdf me-2"></i>Export PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row g-3 mb-4">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <p class="text-body-secondary small fw-bold text-uppercase ls-1 mb-0">Total Placements</p>
                                    <i class="bi bi-people text-primary fs-4"></i>
                                </div>
                                <h2 class="fw-bold mb-1" id="statTotalPlacements">0</h2>
                                <small class="text-success fw-bold"><i class="bi bi-graph-up me-1"></i>Active Batch</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <p class="text-body-secondary small fw-bold text-uppercase ls-1 mb-0">Completion Rate</p>
                                    <i class="bi bi-check2-circle text-success fs-4"></i>
                                </div>
                                <h2 class="fw-bold mb-1" id="statCompletionRate">0%</h2>
                                <div class="progress mt-2" style="height: 6px;">
                                    <div class="progress-bar bg-success rounded-pill" id="completionBar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <p class="text-body-secondary small fw-bold text-uppercase ls-1 mb-0">Total Hours</p>
                                    <i class="bi bi-clock-history text-info fs-4"></i>
                                </div>
                                <h2 class="fw-bold mb-1" id="statTotalHours">0</h2>
                                <small class="text-body-secondary">Rendered across all students</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between mb-3">
                                    <p class="text-body-secondary small fw-bold text-uppercase ls-1 mb-0">Active Partners</p>
                                    <i class="bi bi-building text-warning fs-4"></i>
                                </div>
                                <h2 class="fw-bold mb-1" id="statActivePartners">0</h2>
                                <small class="text-body-secondary">Accredited companies</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row g-3">
                    <div class="col-12 col-lg-8">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4">
                                <h5 class="fw-bold mb-0">System Activity Trends</h5>
                                <small class="text-body-secondary">Total actions logged over the last 6 months</small>
                            </div>
                            <div class="card-body p-4">
                                <div style="height: 300px;">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-lg-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4">
                                <h5 class="fw-bold mb-0">Placement Status</h5>
                                <small class="text-body-secondary">Current student distribution</small>
                            </div>
                            <div class="card-body p-4 d-flex align-items-center justify-content-center">
                                <div style="height: 250px; width: 100%;">
                                    <canvas id="placementChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4">
                                <h5 class="fw-bold mb-0">Program Distribution</h5>
                                <small class="text-body-secondary">Students by academic program</small>
                            </div>
                            <div class="card-body p-4">
                                <div style="height: 250px;">
                                    <canvas id="programChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 border-body border-opacity-10 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-header bg-transparent border-0 px-4 pt-4 d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="fw-bold mb-0">Top Company Partners</h5>
                                    <small class="text-body-secondary">Companies with the most active interns and highest performance ratings</small>
                                </div>
                                <a href="Companies" class="btn btn-sm btn-outline-primary rounded-pill px-3">View All Partners</a>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3" id="topCompaniesContainer">
                                    <!-- Dynamic Cards will be rendered here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Scripts -->
</body>

</html>
