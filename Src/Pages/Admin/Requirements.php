<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Requirements";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js?v=<?= time() ?>"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/RequirementsScripts.js?v=<?= time() ?>"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-role="<?= $_SESSION['user_role'] ?>"
    data-uuid="<?= $_SESSION['user_uuid'] ?>">
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

    <div class="modal fade" id="requirementViewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel">
        <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-dark border border-secondary-subtle rounded-4 shadow-lg">
                <div class="modal-header border-bottom border-secondary-subtle py-3 px-4">
                    <div class="vstack">
                        <h5 class="modal-title fw-bold text-white mb-0">Student Audit: <span id="modalStudentName" class="text-success">Student Name</span></h5>
                        <p class="text-muted small mb-0">Comprehensive review of all pre-OJT documentation and coordinator feedback.</p>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4" id="adminRequirementsList">
                    </div>
                </div>
                <div class="modal-footer border-top border-secondary-subtle py-3">
                    <button class="btn btn-secondary px-4 rounded-3 fw-medium"
                        data-bs-dismiss="modal">Close Audit View</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Requirements — All Students</h4>
                        <p class="blockquote-footer pt-2 fs-6">View-only auditing across all coordinators.</p>
                    </div>
                </div>
                <div class="card bg-blur-5 bg-semi-transparent rounded-4"
                    style="--blur-lvl: <?= $opacitylvl ?>">
                    <div class="card-body">
                        <div class="row g-3 align-items-center mb-3">
                            <div class="col-md-6">
                                <h5 class="card-title">Audit Overview</h5>
                                <p class="card-text">Status tracking for all pre-OJT requirements.</p>
                            </div>
                            <div class="col-md-4 ms-auto">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-blur-5 bg-semi-transparent border-end-0"
                                        style="--blur-lvl: <?= $opacitylvl ?>"><i
                                            class="bi bi-search"></i></span>
                                    <input type="text"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent border-start-0 shadow-none"
                                        placeholder="Search student..." id="requirementSearchInput"
                                        style="--blur-lvl: <?= $opacitylvl ?>">
                                </div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row row-cols-1 g-2" id="requirementsContainer" style="max-height: 600px; overflow-y: auto;">
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>
