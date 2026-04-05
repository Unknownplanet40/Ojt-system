<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user'])) {
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
    <script type="module" src="../../../Assets/Script/CoordinatorScripts/RequirementsScripts.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-role="<?= $_SESSION['user']['role'] ?>"
    data-uuid="<?= $_SESSION['user']['uuid'] ?>">
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

    <!-- modals -->
    <div class="modal fade" id="requirementReviewModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
        aria-labelledby="staticBackdropLabel">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 rounded-4"
                style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="modal-body">
                    <div class="vstack">
                        <div class="hstack">
                            <div class="vstack">
                                <h5 class="modal-title"><span id="modalDocType">John Doe</span> — <span id="modalStudentName">John Doe</span></h5>
                                <small class="text-secondary mb-3 "><span id="modalStudentDocumentName">Resume</span>
                                    &bull; <span id="modalStudentDocumentStatus">Submitted</span></small>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <p class="card-text mb-0">Submitted document</p>
                                <small class="text-secondary mb-2">Review the file before approving or returning.</small>
                                <div class="alert alert-dark py-1 px-3" role="alert">
                                    <div class="hstack">
                                        <i class="bi bi-file-earmark-text me-2 ps-2 text-secondary-emphasis"></i>
                                        <div class="vstack">
                                            <span id="documentFileName" class=text-truncate style="max-width: 200px;">John_Doe_Resume.pdf</span>
                                            <small class="text-secondary" id="documentdate">Submitted on: 2024-08-15 14:30</small>
                                        </div>
                                        <div class="hstack gap-3">
                                            <a href="javascript:void(0);"
                                                class="text-decoration-none text-success-emphasis ms-auto"
                                                id="viewDocumentBtn" style="font-size: 0.8em;">View PDF</a>
                                            <a href="javascript:void(0);"
                                                class="text-decoration-none text-success-emphasis ms-auto"
                                                id="downloadDocumentBtn" style="font-size: 0.8em;">Download</a>
                                        </div>
                                    </div>
                                </div>
                                <div class="alert alert-dark py-2 px-3 mb-0" role="alert">
                                    <p class="card-text mb-0">Student's notes</p>
                                    <small class="text-secondary mb-2">Any additional information provided by the
                                        student.</small>
                                    <div class="hstack gap-2">
                                        <i class="bi bi-chat-dots me-2 ps-2 text-secondary-emphasis"></i>
                                        <span id="studentNotesContent">I have included my updated
                                            resume with additional internship experience.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent rounded-4 mt-2"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body">
                                <p class="card-text mb-0">Your decision</p>
                                <small class="text-secondary mb-2">Add a note for the student if returning the
                                    document.</small>
                                <div class="mb-3">
                                    <label for="reviewNote" class="form-label">Note to student (required if
                                        returning)</label>
                                    <textarea class="form-control bg-blur-5 bg-semi-transparent" id="reviewNote"
                                        rows="3"
                                        style="--blur-lvl: <?= $opacitylvl ?>"></textarea>
                                </div>
                                <div class="hstack gap-2 justify-content-end" id="reviewActionButtonsContainer">
                                    <button class="btn btn-outline-secondary text-light px-4 py-1 rounded-2"
                                        id="closeModalBtn" data-bs-dismiss="modal">Back</button>
                                    <button class="btn btn-outline-secondary text-light px-4 py-1 rounded-2"
                                        id="returnBtn">Return document</button>
                                    <button class="btn btn-outline-secondary text-light px-4 py-1 rounded-2"
                                        id="approveBtn">Approve</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="" id="dashboardTitle">Requirements — My Students</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="CurrentBatch"></span> &bull; <span
                                id="StudentCount"></span></p>
                        </p>
                    </div>
                </div>
                <div class="card bg-blur-5 bg-semi-transparent rounded-4"
                    style="--blur-lvl: <?= $opacitylvl ?>">
                    <div class="card-body">
                        <div class="row row-cols-1 row-cols-md-4 g-2">
                            <div class="col-md-6">
                                <h5 class="card-title">Requirements overview</h5>
                                <p class="card-text">Click a student to review their submitted documents.</p>
                            </div>
                            <div class="col-md-4 d-none">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-blur-5 bg-semi-transparent border-end-0"
                                        style="--blur-lvl: <?= $opacitylvl ?>"><i
                                            class="bi bi-search"></i></span>
                                    <input type="text"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent border-start-0 shadow-none"
                                        placeholder="Search by name or course" id="requirementSearchInput"
                                        style="--blur-lvl: <?= $opacitylvl ?>">
                                </div>
                            </div>
                            <div class="col-md-2 d-none">
                                <select class="form-select form-select-sm bg-blur-5 bg-semi-transparent"
                                    id="requirementStatusFilter"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <option class="CustomOption" value="all" selected>All statuses</option>
                                    <option class="CustomOption" value="not_submitted">Not submitted</option>
                                    <option class="CustomOption" value="submitted">Submitted</option>
                                    <option class="CustomOption" value="under_review">Under review</option>
                                    <option class="CustomOption" value="approved">Approved</option>
                                    <option class="CustomOption" value="returned">Returned</option>
                                </select>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="row row-cols-1 row-cols-md-1" id="requirementsContainer"
                            style="max-height: 400px; overflow-y: auto;">
                            <?php for ($i = 0; $i < 5; $i++) : ?>
                            <div class="col">
                                <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4"
                                    style="--blur-lvl: <?= $opacitylvl ?>">
                                    <div class="card-body">
                                        <div class="hstack">
                                            <img src="https://placehold.co/64x64/483a0f/c7993d/png?text=RJ&font=poppins"
                                                alt="profile picture" class="rounded-circle m-2 mx-3 me-3"
                                                style="width: 26px; height: 26px;" id="ProfilePicture">
                                            <div class="vstack">
                                                <h6 class="card-title mb-0" id="StudentName">John Doe</h6>
                                                <p class="card-text mb-0" id="StudentCourse">BS Computer Science</p>
                                                <div class="hstack gap-1">
                                                    <!-- 'not_submitted','submitted','under_review','approved','returned' -->
                                                     <!-- text-secondary for not submitted, text-success for submitted, text-warning for under review, text-success for approved, text-danger for returned -->
                                                    <span class="text-secondary-emphasis" id="resume">&#11044;</span><!-- for Not submitted -->
                                                    <span class="text-danger-emphasis" id="guardian_form">&#11044;</span><!-- for Returned -->
                                                    <span class="text-success-emphasis" id="parental_consent">&#11044;</span><!-- for Submitted -->
                                                    <span class="text-warning-emphasis" id="medical_certificate">&#11044;</span><!-- for Under review -->
                                                    <span class="text-secondary-emphasis" id="insurance">&#11044;</span>
                                                    <span class="text-secondary-emphasis" id="nbi_clearance">&#11044;</span>
                                                </div>
                                            </div>
                                            <span
                                                class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill ms-auto"
                                                id="requirementStatus">Not submitted</span>
                                            <button
                                                class="btn btn-sm btn-outline-secondary text-light ms-3 px-4 py-2 rounded-2"
                                                id="reviewBtn" data-bs-toggle="modal"
                                                data-bs-target="#requirementReviewModal">Review</button>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>