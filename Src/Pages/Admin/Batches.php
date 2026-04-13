<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Batches";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/batchesSripts.js"></script>
    <title><?= $ShortTitle ?></title>

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
    <div class="modal fade" id="closeBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-closebatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4 p-md-5">
                    <div class="mb-4">
                        <span
                            class="bg-danger-subtle text-danger rounded-circle d-inline-flex justify-content-center align-items-center"
                            style="width: 56px; height: 56px;">
                            <i class="bi bi-x-octagon-fill text-danger-emphasis" style="font-size: 24px;"></i>
                        </span>
                    </div>

                    <h5 class="modal-title mb-2 fw-bold">Close this batch?</h5>
                    <p class="mb-3 text-muted lh-sm">You are about to close <strong id="batchToCloseName"
                            class="text-white">N/A</strong>.</p>

                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-start gap-3 py-3"
                        role="alert"
                        style="background-color: rgba(220, 53, 69, 0.1); border: 1px solid rgba(220, 53, 69, 0.2);">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="font-size: 18px;"></i>
                        <div class="flex-grow-1">
                            <p class="mb-0 small">
                                <strong>This action cannot be undone.</strong> All records will become read-only.
                                Students will no longer be able to submit DTR, journals, or applications for this batch.
                            </p>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label for="closeBatchInput" class="form-label fw-medium mb-2">
                            Type <code id="closeBatchNameConfirm"
                                class="bg-secondary-subtle text-secondary px-2 py-1 rounded">CLOSE</code> to confirm
                        </label>
                        <input type="text"
                            class="form-control form-control-lg bg-blur-5 bg-semi-transparent border-subtle shadow-sm text-white"
                            style="--blur-lvl: 0.5" placeholder="Type CLOSE to confirm" id="closeBatchInput">
                    </div>
                </div>

                <div
                    class="modal-footer border-top-subtle bg-body-tertiary bg-opacity-10 d-flex gap-2 justify-content-center p-3">
                    <button type="button" class="btn btn-sm btn-outline-light py-2 px-4 rounded-3"
                        id="cancelCloseBatchBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-danger py-2 px-4 rounded-3" id="confirmCloseBatchBtn"
                        disabled>Yes, Close Batch</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ActivateBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-activatebatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4 p-md-5">
                    <div class="mb-4">
                        <span
                            class="bg-warning-subtle text-warning rounded-circle d-inline-flex justify-content-center align-items-center"
                            style="width: 56px; height: 56px;">
                            <i class="bi bi-question-octagon-fill text-warning-emphasis" style="font-size: 24px;"></i>
                        </span>
                    </div>

                    <h5 class="modal-title mb-2 fw-bold">Activate this batch?</h5>
                    <p class="mb-3 text-muted lh-sm">You are about to set <strong id="batchToActivateName"
                            class="text-white">N/A</strong> as the active batch.</p>

                    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-3 py-3"
                        role="alert"
                        style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="font-size: 18px;"></i>
                        <div class="flex-grow-1">
                            <p class="mb-0 small">
                                <strong>This will close the current active batch</strong> (<span
                                    id="currentActiveBatchName" class="text-white">N/A</span>). Students in the current
                                batch will no longer be able to submit new DTR or journal entries.
                            </p>
                        </div>
                    </div>
                </div>

                <div
                    class="modal-footer border-top-subtle bg-body-tertiary bg-opacity-10 d-flex gap-2 justify-content-center p-3">
                    <button type="button" class="btn btn-sm btn-outline-light py-2 px-4 rounded-3"
                        id="cancelActivateBatchBtn" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-success py-2 px-4 rounded-3"
                        id="confirmActivateBatchBtn">Yes, Activate Batch</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="NewBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4 p-md-5">
                    <div class="mb-4">
                        <h5 class="modal-title fw-bold mb-2">Create New Batch</h5>
                        <p class="text-muted small mb-0">Set up a new school year and semester grouping for your OJT
                            program.</p>
                    </div>

                    <div class="alert alert-info d-flex align-items-start gap-3 py-3 mb-4" role="alert"
                        style="background-color: rgba(13, 202, 240, 0.1); border: 1px solid rgba(13, 202, 240, 0.2);">
                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1" style="font-size: 16px;"></i>
                        <div class="flex-grow-1">
                            <small class="d-block">New batches start as <strong>Upcoming</strong> — activate them
                                separately to make them the current semester.</small>
                        </div>
                    </div>

                    <div class="card bg-blur-5 bg-semi-transparent border border-subtle shadow-sm"
                        style="--blur-lvl: <?= $opacitylvl ?>;">
                        <div class="card-body p-4">
                            <p class="card-title fw-semibold mb-1 fs-6">Semester Details</p>
                            <small class="text-muted d-block mb-4">Information used to organize students, applications,
                                and OJT records.</small>

                            <div class="row row-cols-1 row-cols-lg-2 g-3">
                                <div class="col">
                                    <label for="schoolYearInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        School Year
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="schoolYearInput" placeholder="e.g. 2025–2026" style="--blur-lvl: 0.5;">
                                    <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">Format:
                                        YYYY–YYYY</small>
                                </div>

                                <div class="col">
                                    <label for="semesterInput" class="form-label fw-medium" style="font-size: 0.9rem;">
                                        Semester
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="semesterInput"
                                        class="form-select form-select-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        style="--blur-lvl: 0.5;">
                                        <option value="" selected disabled hidden>Select semester</option>
                                        <option value="1st" class="CustomOption">1st Semester</option>
                                        <option value="2nd" class="CustomOption">2nd Semester</option>
                                        <option value="summer" class="CustomOption">Summer</option>
                                    </select>
                                </div>

                                <div class="col">
                                    <label for="startDateInput" class="form-label fw-medium" style="font-size: 0.9rem;">
                                        Start Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="startDateInput" style="--blur-lvl: 0.5;">
                                </div>

                                <div class="col">
                                    <label for="endDateInput" class="form-label fw-medium" style="font-size: 0.9rem;">
                                        End Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="endDateInput" style="--blur-lvl: 0.5;">
                                        <small class="text-muted d-block mt-2" id="batchDurationInfoNew" style="font-size: 0.8rem;"></small>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label for="requiredHoursInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        Required OJT Hours
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="0"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="requiredHoursInput" placeholder="e.g. 486" style="--blur-lvl: 0.5;">
                                    <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">CHED standard is
                                        486 hours</small>
                                </div>

                                <div class="col-12 col-lg-6 d-flex flex-column justify-content-end">
                                    <div class="form-check form-switch py-2">
                                        <input class="form-check-input shadow-none" type="checkbox"
                                            id="activateImmediatelySwitch">
                                        <label class="form-check-label fw-medium ps-2" for="activateImmediatelySwitch"
                                            style="font-size: 0.9rem;">
                                            Activate immediately
                                        </label>
                                        <small class="text-muted d-block ms-4 mt-1" style="font-size: 0.8rem;">Set as
                                            active batch after creation</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-5">
                                <button class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3"
                                    data-bs-dismiss="modal" id="cancelNewBatchBtn">
                                    Cancel
                                </button>
                                <button class="btn btn-sm btn-primary px-4 py-2 rounded-3" id="saveNewBatchBtn">
                                    <i class="bi bi-plus-lg me-2"></i>Create Batch
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="EditBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" data-editbatch-uuid="">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-lg modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow"
                style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4 p-md-5">
                    <div class="mb-4">
                        <h5 class="modal-title fw-bold mb-2">Edit Batch</h5>
                        <p class="text-muted small mb-0">Update batch details and semester information. Changes won't
                            affect existing student records.</p>
                    </div>

                    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-start gap-3 py-3 mb-4"
                        role="alert"
                        style="background-color: rgba(255, 193, 7, 0.1); border: 1px solid rgba(255, 193, 7, 0.2);">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1" style="font-size: 16px;"></i>
                        <div class="flex-grow-1">
                            <small class="d-block">Changes to semester dates may affect when students can submit DTR and
                                journal entries.</small>
                        </div>
                    </div>

                    <div class="card bg-blur-5 bg-semi-transparent border border-subtle shadow-sm"
                        style="--blur-lvl: <?= $opacitylvl ?>;">
                        <div class="card-body p-4">
                            <p class="card-title fw-semibold mb-1 fs-6">Semester Details</p>
                            <small class="text-muted d-block mb-4">Update the information used to organize students,
                                applications, and OJT records.</small>

                            <div class="row row-cols-1 row-cols-lg-2 g-3">
                                <div class="col">
                                    <label for="EditschoolYearInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        School Year
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="EditschoolYearInput" placeholder="e.g. 2025–2026"
                                        style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">Format:
                                        YYYY–YYYY</small>
                                </div>

                                <div class="col">
                                    <label for="EditsemesterInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        Semester
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="EditsemesterInput"
                                        class="form-select form-select-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        style="--blur-lvl: <?= $opacitylvl ?>;">
                                        <option value="" selected disabled hidden class="CustomOption">Select semester
                                        </option>
                                        <option value="1st" class="CustomOption">1st Semester</option>
                                        <option value="2nd" class="CustomOption">2nd Semester</option>
                                        <option value="summer" class="CustomOption">Summer</option>
                                    </select>
                                </div>

                                <div class="col">
                                    <label for="EditstartDateInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        Start Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="EditstartDateInput"
                                        style="--blur-lvl:  <?= $opacitylvl ?>;">
                                </div>

                                <div class="col">
                                    <label for="EditendDateInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        End Date
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="date"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="EditendDateInput"
                                        style="--blur-lvl:  <?= $opacitylvl ?>;">
                                    <small class="text-muted d-block mt-2" id="batchDurationInfo" style="font-size: 0.8rem;"></small>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <label for="EditrequiredHoursInput" class="form-label fw-medium"
                                        style="font-size: 0.9rem;">
                                        Required OJT Hours
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="number" min="0"
                                        class="form-control form-control-sm bg-blur-5 bg-semi-transparent shadow-sm text-white border-subtle"
                                        id="EditrequiredHoursInput" placeholder="e.g. 486"
                                        style="--blur-lvl:  <?= $opacitylvl ?>;">
                                    <small class="text-muted d-block mt-2" style="font-size: 0.8rem;">CHED standard is
                                        486 hours</small>
                                </div>
                            </div>

                            <div class="d-flex gap-2 justify-content-end mt-5">
                                <button class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3"
                                    data-bs-dismiss="modal" id="cancelEditBatchBtn">
                                    Cancel
                                </button>
                                <button class="btn btn-sm btn-primary px-4 py-2 rounded-3" id="saveEditBatchBtn">
                                    <i class="bi bi-check-lg me-2"></i>Save Changes
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="ViewStudentBatchModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down modal-xl modal-dialog-scrollable">
            <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: <?= $opacitylvl ?>;">
                <div class="modal-body p-4 p-lg-5">
                    <div class="mb-4">
                        <h6 class="modal-title fw-bold mb-2 fs-5" id="ViewStudentBatchModalLabel">Students in Batch</h6>
                        <p class="text-muted small mb-0">View all students currently assigned to this batch</p>
                    </div>

                    <div class="vstack gap-3">
                        <!-- Search and Action Controls -->
                        <div class="hstack gap-2 flex-wrap">
                            <div class="input-group input-group-sm flex-grow-1" style="min-width: 200px;">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border-subtle text-muted"
                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text"
                                    class="form-control form-control-sm bg-blur-5 bg-semi-transparent border-subtle text-white shadow-none placeholder-muted"
                                    placeholder="Search students..." id="searchBatchStudentsInput"
                                    style="--blur-lvl: <?= $opacitylvl ?>;">
                                <button class="btn btn-sm btn-outline-secondary" id="clearSearchBatchStudentsBtn"
                                    title="Clear search">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                            <button class="btn btn-sm btn-outline-secondary text-nowrap" id="refreshBatchStudentsBtn"
                                title="Refresh list">
                                <i class="bi bi-arrow-clockwise me-1"></i>
                                <span class="d-none d-sm-inline">Refresh</span>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary text-nowrap" id="exportBatchStudentsBtn"
                                title="Export as spreadsheet">
                                <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                                <span class="d-none d-sm-inline">Export</span>
                            </button>
                        </div>

                        <!-- Students List Container -->
                        <div class="border border-subtle rounded-2 bg-body-tertiary bg-opacity-25 p-3">
                            <div class="row row-cols-1 row-cols-md-2 g-3" id="batchStudentsContainer">
                                <!-- Dynamically populated list of students in this batch -->
                            </div>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top border-subtle">
                        <button class="btn btn-sm btn-outline-secondary py-2 px-4 rounded-3" data-bs-dismiss="modal"
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
                        <div class="col">
                            <!-- Batch Card Template -->
                            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-success"
                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                <!-- Header Section -->
                                <div class="card-body pb-3 pt-4 px-4 border-bottom border-subtle">
                                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                        <div class="flex-grow-1 min-w-0">
                                            <h5 class="card-title mb-1 fw-bold text-truncate fs-6">${batch.label}</h5>
                                            <small class="text-muted d-block">
                                                <i class="bi bi-calendar3 me-1"></i>${batch.start_date} —
                                                ${batch.end_date}
                                            </small>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <span
                                                class="badge bg-success bg-opacity-10 text-success rounded-pill fw-medium d-none">Active</span>
                                            <span
                                                class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill fw-medium d-none">Upcoming</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Stats Section -->
                                <div class="card-body py-3 px-4">
                                    <div class="row row-cols-2 row-cols-lg-4 g-2 mb-3">
                                        <div class="col">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted text-uppercase"
                                                    style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Students</small>
                                                <span class="fs-6 fw-semibold mt-1">${batch.student_count}</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted text-uppercase"
                                                    style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Req.
                                                    Hours</small>
                                                <span class="fs-6 fw-semibold mt-1">${batch.required_hours}</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted text-uppercase"
                                                    style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Activated</small>
                                                <span class="fs-6 fw-semibold mt-1 text-truncate">${batch.activated_at ?
                                                    batch.activated_at : "—"}</span>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="d-flex flex-column">
                                                <small class="text-muted text-uppercase"
                                                    style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">By</small>
                                                <span
                                                    class="fs-6 fw-semibold mt-1 text-truncate">${batch.created_by}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted d-block">
                                        <i class="bi bi-clock-history me-1"></i>Created ${batch.created_at}
                                    </small>
                                </div>

                                <!-- Actions Section -->
                                <div class="card-body pt-3 pb-4 px-4 border-top border-subtle">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-outline-secondary py-2 px-3 rounded-2"
                                            data-bs-toggle="modal" data-bs-target="#ViewStudentBatchModal"
                                            id="viewStudentsBtn-${batch.uuid}">
                                            <i class="bi bi-eye2"></i> View Students
                                        </button>
                                        <div class="d-flex gap-2">
                                            <button
                                                class="btn btn-sm btn-outline-secondary flex-grow-1 py-2 px-3 rounded-2"
                                                data-bs-toggle="modal" data-bs-target="#EditBatchModal"
                                                id="editBatchBtnc-${batch.uuid}">
                                                <i class="bi bi-pencil"></i><span class="d-none d-sm-inline">
                                                    Edit</span>
                                            </button>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <button
                                                class="btn btn-sm btn-outline-danger flex-grow-1 py-2 px-3 rounded-2"
                                                data-bs-toggle="modal" data-bs-target="#closeBatchModal"
                                                id="closeBatchBtn-${batch.uuid}">
                                                <i class="bi bi-x-lg"></i><span class="d-none d-sm-inline"> Close</span>
                                            </button>
                                        </div>
                                        <div class="d-flex gap-2 d-none">
                                            <button
                                                class="btn btn-sm btn-outline-success flex-grow-1 py-2 px-3 rounded-2"
                                                data-bs-toggle="modal" data-bs-target="#ActivateBatchModal"
                                                id="activateBatchBtn-${batch.uuid}">
                                                <i class="bi bi-check-lg"></i><span class="d-none d-sm-inline">
                                                    Activate</span>
                                            </button>
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