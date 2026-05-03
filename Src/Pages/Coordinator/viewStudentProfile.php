<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    header("Location: ../Login");
    exit;
}

$studentUuid = $_GET['uuid'] ?? '';
if (empty($studentUuid)) {
    header("Location: ./MyStudents");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "MyStudents";
?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/ViewStudentProfileScript.js"></script>
    <style>
        .nav-pills .nav-link.active {
            color: #fff !important;
            background-color: var(--bs-primary) !important;
        }
        .nav-pills .nav-link:not(.active) {
            color: rgba(255, 255, 255, 0.7) !important;
        }
        .nav-pills .nav-link:hover:not(.active) {
            background-color: rgba(255, 255, 255, 0.1);
            color: #fff !important;
        }
    </style>
    <title><?= $ShortTitle ?> - Student Profile</title>
</head>

<body class="login-page" data-role="<?= $_SESSION['user_role'] ?>" data-uuid="<?= $_SESSION['user_uuid'] ?>" data-student-uuid="<?= htmlspecialchars($studentUuid) ?>">
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
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="./MyStudents" class="btn btn-sm btn-outline-light rounded-circle p-2 d-inline-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <nav aria-label="breadcrumb" class="mb-0">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="./MyStudents" class="text-decoration-none text-primary">My Students</a></li>
                            <li class="breadcrumb-item active text-light" aria-current="page">Student Profile</li>
                        </ol>
                    </nav>
                </div>

                <div class="row g-3">
                    <div class="col-12 col-lg-4">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-3" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4 text-center">
                                <img src="https://placehold.co/128x128/C1C1C1/000000/png?text=SP&font=poppins" id="studentPhoto" class="rounded-circle border border-3 border-primary-subtle shadow-sm mb-3" style="width: 128px; height: 128px; object-fit: cover;">
                                <h4 class="mb-1 fw-bold" id="studentName">Loading...</h4>
                                <p class="text-muted small mb-3" id="studentNumber">---</p>
                                <span class="badge rounded-pill px-3 py-2 mb-3" id="studentStatusBadge">Status</span>
                                <hr class="border-light border-opacity-10">
                                <div class="vstack gap-2 text-start small">
                                    <div class="d-flex justify-content-between"><span class="text-muted">Program:</span><span class="fw-semibold" id="studentProgram">---</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Year & Section:</span><span class="fw-semibold" id="studentYearSection">---</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-muted">Batch:</span><span class="fw-semibold" id="studentBatch">---</span></div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h6 class="text-uppercase fw-bold text-primary small mb-3">Contact Information</h6>
                                <div class="vstack gap-3 small">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-envelope"></i></div>
                                        <div class="vstack"><span class="text-muted x-small">Email Address</span><span class="fw-medium text-break" id="studentEmail">---</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-phone"></i></div>
                                        <div class="vstack"><span class="text-muted x-small">Mobile Number</span><span class="fw-medium" id="studentMobile">---</span></div>
                                    </div>
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="bi bi-geo-alt"></i></div>
                                        <div class="vstack"><span class="text-muted x-small">Home Address</span><span class="fw-medium" id="studentAddress">---</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-8">
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-3" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">Placement Details</h5>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-4 h-100">
                                            <div class="text-muted small mb-1">Company / Host Training Establishment</div>
                                            <div class="fw-bold fs-5" id="companyName">Not Assigned Yet</div>
                                            <div class="text-muted small" id="companyAddress">---</div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="p-3 border rounded-4 h-100">
                                            <div class="text-muted small mb-1">Supervisor</div>
                                            <div class="fw-bold fs-5" id="supervisorName">---</div>
                                            <div class="text-muted small" id="supervisorContact">---</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm mb-3" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">OJT Progress</h5>
                                <div class="row g-4 text-center">
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted small mb-2 text-uppercase fw-semibold">Hours Rendered</div>
                                        <h3 class="fw-bold mb-0 text-primary" id="hoursRendered">0</h3>
                                        <small class="text-muted" id="hoursGoal">of 486 hrs</small>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted small mb-2 text-uppercase fw-semibold">Requirements</div>
                                        <h3 class="fw-bold mb-0 text-success" id="reqsCompleted">0</h3>
                                        <small class="text-muted">of 6 items</small>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted small mb-2 text-uppercase fw-semibold">Journals</div>
                                        <h3 class="fw-bold mb-0 text-info" id="journalsSubmitted">0</h3>
                                        <small class="text-muted">weeks submitted</small>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="text-muted small mb-2 text-uppercase fw-semibold">Evaluations</div>
                                        <h3 class="fw-bold mb-0 text-warning" id="evaluationsStatus">N/A</h3>
                                        <small class="text-muted">Final status</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm" style="--blur-lvl: <?= $opacitylvl ?>;">
                            <div class="card-body p-4">
                                <ul class="nav nav-pills mb-3 gap-2" id="studentDetailTabs" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#tab-reqs">Requirements</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#tab-dtr">Recent DTR</button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#tab-journals">Journals</button>
                                    </li>
                                </ul>
                                <div class="tab-content" id="studentDetailTabsContent">
                                    <div class="tab-pane fade show active" id="tab-reqs">
                                        <div class="list-group list-group-flush border-top border-light border-opacity-10" id="requirementsList">
                                            <!-- Dynamic Content -->
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-dtr">
                                        <div class="list-group list-group-flush border-top border-light border-opacity-10" id="recentDtrList">
                                            <!-- Dynamic Content -->
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="tab-journals">
                                        <div class="list-group list-group-flush border-top border-light border-opacity-10" id="journalsList">
                                            <!-- Dynamic Content -->
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
