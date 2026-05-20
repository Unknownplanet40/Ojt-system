<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'student') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
$CurrentPage = "Certificates";
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/StudentDashboard.js?v=<?= time() ?>"></script>
    <script type="module" src="../../../Assets/Script/StudentScripts/CertificateScript.js?v=<?= time() ?>"></script>
    <title><?= $ShortTitle ?> - My Certificates</title>
</head>
<body class="login-page" data-role="student" data-page-type="student" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Students.php"; ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">My Certificates</h4>
                        <p class="text-muted mb-0">Your earned OJT certificates and credentials.</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm border-white-10" id="certificateRefreshBtn" title="Refresh certificates">
                            <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                        </button>
                    </div>
                </div>

                <div class="card bg-blur-5 bg-semi-transparent rounded-4 border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5" id="certificatesContainer">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
                            <p class="text-muted mb-0">Loading your certificates...</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Preview Modal -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 border-bottom border-light border-opacity-10 pb-3">
                    <h5 class="modal-title fw-bold">Certificate Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3" id="previewModalBody">
                    <!-- Loaded by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="qrModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 border-bottom border-light border-opacity-10 pb-3">
                    <h5 class="modal-title fw-bold">QR Code - Verify Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3" id="qrModalBody">
                    <!-- Loaded by JavaScript -->
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="shareModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow-lg" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-0 border-bottom border-light border-opacity-10 pb-3">
                    <h5 class="modal-title fw-bold">Share Certificate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-3" id="shareModalBody">
                    <!-- Loaded by JavaScript -->
                </div>
            </div>
        </div>
    </div>
</body>
</html>
