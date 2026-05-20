<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

$CurrentPage = "HRDashboard";
require_once "../../../Assets/SystemInfo.php";

?>
<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php"; ?>
    <?php
    // Extra Security Layer: Ensure the user is actually an HR Admin
    $userUuid = $_SESSION['user_uuid'] ?? '';
    $stmt = $conn->prepare("SELECT is_hr_admin, company_uuid FROM supervisor_profiles WHERE user_uuid = ? LIMIT 1");
    $stmt->bind_param("s", $userUuid);
    $stmt->execute();
    $profRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$profRow || (int)$profRow['is_hr_admin'] !== 1) {
        header('Location: ./SupervisorDashboard.php');
        exit;
    }
    
    $companyUuid = $profRow['company_uuid'];
    ?>
    
    <script type="module" src="../../../Assets/Script/dashboardScripts/SupervisorDashboardScript.js"></script>
    <title><?= $ShortTitle ?> - HR Administrator</title>
    <style>
        .swal2-actions{
            justify-content: center;
        }
    </style>
</head>

<body class="login-page" data-role="<?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '' ?>" data-uuid="<?= isset($_SESSION['user_uuid']) ? $_SESSION['user_uuid'] : '' ?>" data-company="<?= $companyUuid ?>" data-only="<?= $CurrentPage ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Supervisor.php" ?>
            
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack mb-4">
                    <div>
                        <h4 class="mb-1" id="dashboardTitle">Company HR Portal</h4>
                        <p class="blockquote-footer pt-2 fs-6">
                            Manage your company's internship deployment, supervisors, and slots.
                        </p>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
                    <!-- Total Supervisors Widget -->
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Active Supervisors</h6>
                                    <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary">
                                        <i class="bi bi-people fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="hrTotalSupervisors">
                                    <span class="spinner-border spinner-border-sm text-primary"></span>
                                </h3>
                                <p class="card-text small text-muted mb-0">Staff managing interns</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Total Interns Widget -->
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Deployed Interns</h6>
                                    <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success">
                                        <i class="bi bi-mortarboard fs-5"></i>
                                    </div>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="hrTotalInterns">
                                    <span class="spinner-border spinner-border-sm text-success"></span>
                                </h3>
                                <p class="card-text small text-muted mb-0">Students currently deployed</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Slots Overview Widget -->
                    <div class="col">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 class="card-subtitle text-muted text-uppercase fw-bold small mb-0">Available Slots</h6>
                                    <button class="btn btn-sm btn-outline-warning hover-lift" id="hrRequestSlotsBtn" title="Request more slots">
                                        <i class="bi bi-plus-circle"></i> Request
                                    </button>
                                </div>
                                <h3 class="card-text fw-bold mb-1" id="hrRemainingSlots">
                                    <span class="spinner-border spinner-border-sm text-warning"></span>
                                </h3>
                                <p class="card-text small text-muted mb-0" id="hrTotalSlotsText">Loading capacity...</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Supervisors & Interns -->
                    <div class="col-lg-8">
                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4 mb-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="hstack justify-content-between mb-4">
                                    <h5 class="card-title mb-0">Company Supervisors</h5>
                                    <button class="btn btn-sm btn-outline-primary hover-lift" id="hrAddSupervisorBtn">
                                        <i class="bi bi-person-plus"></i> Add Supervisor
                                    </button>
                                </div>
                                <div class="vstack gap-3" id="hrSupervisorsList">
                                    <div class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                        <span class="text-muted">Loading supervisors...</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4">
                                <div class="hstack mb-4">
                                    <h5 class="card-title mb-0">Deployed Interns</h5>
                                </div>
                                <div class="vstack gap-2" id="hrInternsList">
                                    <div class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-success me-2" role="status"></div>
                                        <span class="text-muted">Loading interns...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Documents -->
                    <div class="col-lg-4">
                        <div class="card h-100 bg-blur-5 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-4 d-flex flex-column">
                                <div class="hstack justify-content-between mb-4">
                                    <h5 class="card-title mb-0">Company Documents</h5>
                                    <!-- Temporarily hide upload button if we want to handle it in another step, or show it -->
                                    <button class="btn btn-sm btn-outline-primary hover-lift" id="hrUploadDocBtn">
                                        <i class="bi bi-upload"></i> Upload
                                    </button>
                                </div>
                                <div class="flex-grow-1 vstack gap-3" id="hrDocumentsList">
                                    <div class="text-center py-5">
                                        <div class="spinner-border spinner-border-sm text-secondary me-2" role="status"></div>
                                        <span class="text-muted">Loading documents...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Document Upload Modal -->
    <div class="modal fade" id="uploadDocModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-10 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-bottom border-secondary border-opacity-10">
                    <h5 class="modal-title fw-bold">Upload Company Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="uploadDocForm">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Document Type</label>
                            <select class="form-select bg-body-tertiary border-opacity-10" name="doc_type" required>
                                <option value="" disabled selected>Select Document Type</option>
                                <option value="moa">Memorandum of Agreement (MOA)</option>
                                <option value="nda">Non-Disclosure Agreement (NDA)</option>
                                <option value="insurance">Insurance Certificate</option>
                                <option value="bir_cert">BIR Certificate</option>
                                <option value="sec_dti">SEC/DTI Registration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">File Name / Label</label>
                            <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="file_name" placeholder="e.g. 2026 Company MOA" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Valid Until (Optional)</label>
                            <input type="date" class="form-control bg-body-tertiary border-opacity-10" name="valid_until">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-medium">File (PDF/Image)</label>
                            <input type="file" class="form-control bg-body-tertiary border-opacity-10" name="document_file" accept=".pdf,image/*" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary hover-lift shadow-sm" id="uploadDocSubmitBtn">
                                <i class="bi bi-cloud-upload me-2"></i>Upload Document
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Slots Modal -->
    <div class="modal fade" id="requestSlotsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-10 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-bottom border-warning border-opacity-10">
                    <h5 class="modal-title fw-bold text-warning-emphasis">Request Additional Slots</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Requesting additional intern slots will notify the university coordinator. Once approved, your total capacity will automatically update.</p>
                    <form id="requestSlotsForm">
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Number of Additional Slots</label>
                            <input type="number" class="form-control bg-body-tertiary border-opacity-10" name="requested_slots" min="1" max="50" placeholder="e.g. 5" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-medium">Reason / Department Allocation (Optional)</label>
                            <textarea class="form-control bg-body-tertiary border-opacity-10" name="reason" rows="3" placeholder="e.g. Need 2 for IT, 3 for HR..."></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-warning hover-lift shadow-sm text-dark fw-medium" id="requestSlotsSubmitBtn">
                                <i class="bi bi-send me-2"></i>Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Add Supervisor Modal -->
    <div class="modal fade" id="addSupervisorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content bg-blur-10 bg-semi-transparent shadow-lg border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-header border-bottom border-primary border-opacity-10">
                    <h5 class="modal-title fw-bold text-primary-emphasis">Add Company Supervisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-4">Create a new supervisor account for your company. They will receive their temporary password immediately upon save.</p>
                    <form id="addSupervisorForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-medium">First Name</label>
                                <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="first_name" placeholder="e.g. John" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label small fw-medium">Last Name</label>
                                <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="last_name" placeholder="e.g. Doe" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Email Address</label>
                            <input type="email" class="form-control bg-body-tertiary border-opacity-10" name="email" placeholder="e.g. john.doe@company.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Department</label>
                            <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="department" placeholder="e.g. IT Department" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-medium">Position / Designation</label>
                            <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="position" placeholder="e.g. Senior Tech Lead" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-medium">Mobile Number (Optional)</label>
                            <input type="text" class="form-control bg-body-tertiary border-opacity-10" name="mobile" placeholder="e.g. 09171234567">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary hover-lift shadow-sm fw-medium" id="addSupervisorSubmitBtn">
                                <i class="bi bi-save me-2"></i>Save Supervisor
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script type="module" src="../../../Assets/Script/dashboardScripts/HRDashboardScript.js"></script>
</body>

</html>
