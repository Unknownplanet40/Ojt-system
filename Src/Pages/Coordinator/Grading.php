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
$CurrentPage = "Grading";
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/GradingScripts/GradingModule.js?v=<?= time() ?>"></script>
    <title><?= $ShortTitle ?> - Grading</title>
</head>
<body class="login-page" data-role="coordinator" data-page-type="coordinator" data-uuid="<?= $_SESSION['user_uuid'] ?>">
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
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">Grade Computation</h4>
                        <p class="text-muted mb-0">Review readiness, compute grades, and finalize locked results for your assigned batch.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-secondary rounded-pill px-3" id="gradeRefreshBtn"><i class="bi bi-arrow-clockwise me-2"></i>Refresh</button>
                        <input type="search" class="form-control rounded-pill" id="gradeSearchInput" placeholder="Search student or grade" style="min-width: 240px;">
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-6 col-xl-3">
                        <div class="card glass-ui glass-ui-strong rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Total Students</div>
                                <div class="display-6 fw-bold mb-0" id="gradeSummaryTotal">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card glass-ui glass-ui-strong rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Ready</div>
                                <div class="display-6 fw-bold text-warning mb-0" id="gradeSummaryReady">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card glass-ui glass-ui-strong rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Computed</div>
                                <div class="display-6 fw-bold text-primary mb-0" id="gradeSummaryComputed">0</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-xl-3">
                        <div class="card glass-ui glass-ui-strong rounded-4 border-0 shadow-sm">
                            <div class="card-body p-3 p-md-4">
                                <div class="text-muted small text-uppercase fw-semibold">Finalized</div>
                                <div class="display-6 fw-bold text-success mb-0" id="gradeSummaryFinalized">0</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis rounded-4 shadow-sm mb-4">
                    <div class="d-flex gap-3">
                        <i class="bi bi-info-circle-fill fs-3"></i>
                        <div>
                            <h6 class="fw-bold mb-2">How grading works</h6>
                            <p class="mb-0 small">Open a student record once all required items are complete. The system previews a weighted score using hours, midterm, final, journal, and self-evaluation components. You can adjust the weights, save a draft, and finalize when ready.</p>
                        </div>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                    <div class="card-body p-3 p-md-4">
                        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                            <div>
                                <h5 class="fw-bold mb-1">Students to grade</h5>
                                <p class="text-muted mb-0 small">Ready students appear first, followed by computed and finalized entries.</p>
                            </div>
                            <span class="badge rounded-pill bg-light text-dark border" id="gradeSummaryIncomplete">0 incomplete</span>
                        </div>
                        <div id="gradingOverviewList"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div class="modal fade" id="gradeWorkbenchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold mb-1">Grade workbench</h5>
                        <p class="mb-0 text-muted small"><span id="gradeModalStudentName">—</span> • <span id="gradeModalStudentNumber">—</span> • <span id="gradeModalProgram">—</span></p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex flex-wrap gap-2 align-items-center mb-3" id="gradeModalStatusBadge"></div>
                    <div id="gradeModalReadiness" class="mb-4"></div>

                    <div class="card bg-body-tertiary border-0 rounded-4 mb-4">
                        <div class="card-body p-3 p-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0">Weights and scoring</h6>
                                <span class="badge rounded-pill bg-light text-dark border">Total: <span id="weightTotalLabel">100%</span></span>
                            </div>
                            <div id="gradeWeightsContainer"></div>
                        </div>
                    </div>

                    <div class="card bg-body-tertiary border-0 rounded-4 mb-4">
                        <div class="card-body p-3 p-md-4">
                            <h6 class="fw-bold mb-3">Live preview</h6>
                            <div id="gradePreviewPanel"></div>
                        </div>
                    </div>

                    <div class="card bg-body-tertiary border-0 rounded-4">
                        <div class="card-body p-3 p-md-4">
                            <h6 class="fw-bold mb-3">Coordinator notes</h6>
                            <textarea class="form-control" id="gradeCoordinatorNotes" rows="4" placeholder="Optional notes for the student or audit trail..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 d-flex justify-content-between flex-wrap gap-2">
                    <button class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn btn-outline-primary rounded-pill px-4" id="saveGradeBtn"><i class="bi bi-save me-2"></i>Save draft</button>
                        <button class="btn btn-success rounded-pill px-4" id="finalizeGradeBtn"><i class="bi bi-lock-fill me-2"></i>Finalize grade</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
