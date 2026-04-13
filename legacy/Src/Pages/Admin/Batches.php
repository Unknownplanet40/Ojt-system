<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Batches";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <script type="module" src="../../../Assets/Script/AdminScripts/batchesSripts.js"></script>
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
    <div class="modal fade" id="closeBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        data-closebatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.7">
                <div class="modal-body p-4">
                    <span
                        class="bg-danger-subtle text-danger rounded-circle d-inline-flex justify-content-center align-items-center mb-2"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-x-octagon-fill text-danger-emphasis" style="font-size: 18px;"></i>
                    </span>
                    <p class="modal-title mb-2 fw-bold ps-2 pb-0">Close this batch?</p>
                    <p class="mb-0 text-muted ps-2">You are about to close <strong id="batchToCloseName">N/A</strong>.
                    </p>
                    <span class="bg-danger text-danger-emphasis bg-opacity-25 rounded-3 d-inline-block mt-3 p-2"
                        style="font-size: 0.875rem;">
                        This cannot be undone. All records will become read-only. Students will no longer be able to
                        submit DTR, journals, or applications for this batch.
                    </span>
                    <small class="text-muted d-block mt-3" style="font-size: 0.875rem;">Type <strong
                            id="closeBatchNameConfirm">CLOSE</strong> in the box below to
                        confirm.</small>
                    <input type="text"
                        class="form-control mt-2 bg-blur-5 bg-semi-transparent border-0 shadow-sm text-white"
                        style="--blur-lvl: 0.5" placeholder="Type CLOSE to confirm" id="closeBatchInput">
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center">
                    <div class="hstack gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 flex-grow-1"
                            id="cancelCloseBatchBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-danger py-2 px-3 rounded-3 flex-grow-1"
                            id="confirmCloseBatchBtn" disabled>Yes, Close
                            Batch</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ActivateBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        data-activatebatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.7">
                <div class="modal-body p-4">
                    <span
                        class="bg-warning-subtle text-warning rounded-circle d-inline-flex justify-content-center align-items-center mb-2"
                        style="width: 48px; height: 48px;">
                        <i class="bi bi-question-octagon-fill text-warning-emphasis" style="font-size: 18px;"></i>
                    </span>
                    <p class="modal-title mb-2 fw-bold ps-2 pb-0">Activate this batch?</p>
                    <small class="mb-0 text-muted ps-2">You are about to set <strong
                            id="batchToActivateName">N/A</strong>. as the active batch.</small>
                    <span class="bg-warning text-warning-emphasis bg-opacity-25 rounded-3 d-inline-block mt-3 p-2"
                        style="font-size: 0.875rem;">
                        This will automatically close the current active batch (<span
                            id="currentActiveBatchName">N/A</span>). Students in
                        the current batch will no longer be able to submit new DTR or journal entries.
                    </span>
                </div>
                <div class="modal-footer border-0 d-flex justify-content-center">
                    <div class="hstack gap-2">
                        <button type="button" class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 flex-grow-1"
                            id="cancelActivateBatchBtn" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-sm btn-success py-2 px-3 rounded-3 flex-grow-1"
                            id="confirmActivateBatchBtn">Yes,
                            Activate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="NewBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.6">
                <div class="modal-body p-4">
                    <p class="modal-title fw-bold ps-2 pb-0 mb-0 fs-5">Create batch</p>
                    <small class="mb-0 text-muted ps-2 mt-0">Set up a new school year / semester grouping</small>
                    <div class="alert alert-info py-1 my-3" role="alert">
                        <small>New batches start as Upcoming — you must activate them separately to make them the
                            current semester.</small>
                    </div>

                    <div class="card bg-blur-5 bg-semi-transparent border border-muted shadow-sm"
                        style="--blur-lvl: 0.20">
                        <div class="card-body">
                            <p class="card-title fw-medium mb-0">Semester details</p>
                            <small class="text-muted mb-3">This will group all students, applications, and OJT records
                                for this period.</small>
                            <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                <div class="col">
                                    <label for="schoolYearInput" class="form-label" style="font-size: 0.875rem;">School
                                        Year <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="schoolYearInput" placeholder="e.g. 2025–2026" style="--blur-lvl: 0.5">
                                    <small class="text-muted ms-3" style="font-size: 0.75rem;">Format: YYYY-YYYY</small>
                                </div>
                                <div class="col">
                                    <label for="semesterInput" class="form-label" style="font-size: 0.875rem;">Semester
                                        <span class="text-danger">*</span></label>
                                    <select id="semesterInput"
                                        class="form-select bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        style="--blur-lvl: 0.5">
                                        <option value="" selected disabled hidden
                                            class="bg-blur-5 bg-semi-transparent text-muted">Select semester</option>
                                        <option value="1st" class="CustomOption">1st Semester</option>
                                        <option value="2nd" class="CustomOption">2nd Semester</option>
                                        <option value="summer" class="CustomOption">Summer</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="schoolYearInput" class="form-label" style="font-size: 0.875rem;">Start
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="startDateInput" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="schoolYearInput" class="form-label" style="font-size: 0.875rem;">End
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="endDateInput" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <small class="text-muted mt-1 d-block text-uppercase"
                                        style="font-size: 0.65rem;">Default OJT hours</small>
                                    <label for="schoolYearInput" class="form-label"
                                        style="font-size: 0.875rem;">Required Hours <span
                                            class="text-danger">*</span></label>
                                    <input type="number" min="0"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="requiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5">
                                    <small class="text-muted d-block" style="font-size: 0.70rem;">CHED standard is 486
                                        hrs — programs can override this</small>
                                </div>
                                <div class="col">
                                    <label for="schoolYearInput" class="form-label"
                                        style="font-size: 0.875rem;">Activate Imimediately?</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input shadow-none" type="checkbox"
                                            id="activateImmediatelySwitch">
                                        <label class="form-check-label" for="activateImmediatelySwitch"
                                            style="font-size: 0.875rem;">Yes, set this as the active batch immediately
                                            after creation.</label>
                                    </div>
                                </div>
                            </div>
                            <div class="hstack d-flex justify-content-end mt-4">
                                <button class="btn btn-sm btn-outline-dark border text-light py-2 px-3 rounded-3"
                                    data-bs-dismiss="modal" id="cancelNewBatchBtn">Cancel</button>
                                <button class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 ms-2"
                                    id="saveNewBatchBtn">Create Batch</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="EditBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        data-editbatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.6">
                <div class="modal-body p-4">
                    <p class="modal-title fw-bold ps-2 pb-0 mb-0 fs-5">Edit batch</p>
                    <small class="mb-0 text-muted ps-2 mt-0">Modify the details of this batch. You can also choose to
                        activate it immediately from here.</small>
                    <div class="alert alert-warning py-1 my-3" role="alert">
                        <small>Editing a batch will not affect existing student records, but changes to the semester
                            dates may affect when students can submit DTR and journal entries.</small>
                    </div>

                    <div class="card bg-blur-5 bg-semi-transparent border border-muted shadow-sm"
                        style="--blur-lvl: 0.20">
                        <div class="card-body">
                            <p class="card-title fw-medium mb-0">Semester details</p>
                            <small class="text-muted mb-3">This will group all students, applications, and OJT records
                                for this period.</small>
                            <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                <div class="col">
                                    <label for="EditschoolYearInput" class="form-label"
                                        style="font-size: 0.875rem;">School
                                        Year <span class="text-danger">*</span></label>
                                    <input type="text"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="EditschoolYearInput" placeholder="e.g. 2025–2026" style="--blur-lvl: 0.5">
                                    <small class="text-muted ms-3" style="font-size: 0.75rem;">Format: YYYY-YYYY</small>
                                </div>
                                <div class="col">
                                    <label for="EditsemesterInput" class="form-label"
                                        style="font-size: 0.875rem;">Semester
                                        <span class="text-danger">*</span></label>
                                    <select id="EditsemesterInput"
                                        class="form-select bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        style="--blur-lvl: 0.5">
                                        <option value="" selected disabled hidden
                                            class="bg-blur-5 bg-semi-transparent text-muted">Select semester</option>
                                        <option value="1st" class="CustomOption">1st Semester</option>
                                        <option value="2nd" class="CustomOption">2nd Semester</option>
                                        <option value="summer" class="CustomOption">Summer</option>
                                    </select>
                                </div>
                                <div class="col">
                                    <label for="EditstartDateInput" class="form-label"
                                        style="font-size: 0.875rem;">Start
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="EditstartDateInput" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <label for="EditendDateInput" class="form-label" style="font-size: 0.875rem;">End
                                        Date <span class="text-danger">*</span></label>
                                    <input type="date"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="EditendDateInput" style="--blur-lvl: 0.5">
                                </div>
                                <div class="col">
                                    <small class="text-muted mt-1 d-block text-uppercase"
                                        style="font-size: 0.65rem;">Default OJT hours</small>
                                    <label for="EditrequiredHoursInput" class="form-label"
                                        style="font-size: 0.875rem;">Required Hours <span
                                            class="text-danger">*</span></label>
                                    <input type="number" min="0"
                                        class="form-control bg-blur-5 bg-semi-transparent shadow-sm text-white"
                                        id="EditrequiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5">
                                    <small class="text-muted d-block" style="font-size: 0.70rem;">CHED standard is 486
                                        hrs — programs can override this</small>
                                </div>
                                <div class="col">
                                    <label for="EditactivateImmediatelySwitch" class="form-label"
                                        style="font-size: 0.875rem;">Activate Immediately?</label>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input shadow-none" type="checkbox"
                                            id="EditactivateImmediatelySwitch">
                                        <label class="form-check-label" for="EditactivateImmediatelySwitch"
                                            style="font-size: 0.875rem;">Yes, set this as the active batch immediately
                                            after creation.</label>
                                    </div>
                                </div>
                            </div>
                            <div class="hstack d-flex justify-content-end mt-4">
                                <button class="btn btn-sm btn-outline-dark border text-light py-2 px-3 rounded-3"
                                    data-bs-dismiss="modal" id="cancelEditBatchBtn">Cancel</button>
                                <button class="btn btn-sm btn-outline-light py-2 px-3 rounded-3 ms-2"
                                    id="saveEditBatchBtn">Save Changes</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ViewStudentBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-xl">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.6">
                <div class="modal-body p-4">
                    <p class="modal-title fw-bold ps-2 pb-0 mb-0 fs-5" id="ViewStudentBatchModalLabel">Students in this
                        batch</p>
                    <small class="mb-0 text-muted ps-2 mt-0">View the list of students currently assigned to this
                        batch.</small>
                    <div class="vstack mt-2">
                        <div class="hstack gap-2">
                            <div class="input-group input-group-sm w-100">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-0 text-muted"
                                    style="--blur-lvl: 0.5;">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text"
                                    class="form-control form-control-sm bg-blur-5 bg-semi-transparent border-0 text-white shadow-none"
                                    placeholder="Search students..." id="searchBatchStudentsInput"
                                    style="--blur-lvl: 0.5;">
                                <button class="btn btn-sm btn-outline-light ms-2" id="clearSearchBatchStudentsBtn">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <button class="btn btn-sm btn-outline-light me-auto text-nowrap"
                                id="exportBatchStudentsBtn">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                Export List
                            </button>
                            <button class="btn btn-sm btn-outline-light" id="refreshBatchStudentsBtn">
                                <i class="bi bi-arrow-clockwise"></i>
                            </button>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 g-3 py-2 my-2" id="batchStudentsContainer"
                            style="max-height: 380px; overflow-y: auto;">
                            <!-- Dynamically populated list of students in this batch -->
                        </div>
                    </div>
                    <div class="hstack d-flex justify-content-end mt-4">
                        <button class="btn btn-sm btn-outline-light py-2 px-3 rounded-3" data-bs-dismiss="modal"
                            id="closeViewBatchStudentsBtn">Close</button>
                    </div>
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
                        <h4 class="">Batches</h4>
                        <p class="blockquote-footer pt-2 fs-6">Manage school year and semester groupings.</p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addBatchBtn"
                        data-bs-toggle="modal" data-bs-target="#NewBatchModal">
                        <i class="bi bi-plus-lg me-1"></i>
                        Add New Batch
                    </button>
                </div>
                <p class="text-success ms-4 mb-0" id="activeBatchLabel"></span></p>
                <div class="container my-4 p-0">
                    <div class="row row-cols-1 row-cols-md-1 g-4 d-flex" id="batchesContainer">
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>

</html>