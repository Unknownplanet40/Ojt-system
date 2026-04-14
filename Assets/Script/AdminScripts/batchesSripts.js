import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
let swalTheme = SwalTheme();

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const randomConformationWord = ["CONFIRM", "AGREE", "YES", "OK", "PROCEED", "ACCEPT", "VALIDATE", "APPROVE", "ACKNOWLEDGE", "CONSENT"];
let currentBatchStudents = [];
let currentFilteredBatchStudents = [];
let currentViewingBatchUuid = null;

function renderBatchStudents(students = []) {
  const batchStudentsContainer = $("#batchStudentsContainer");
  const ViewStudentBatchModalLabel = $("#ViewStudentBatchModalLabel");

  batchStudentsContainer.empty();
  ViewStudentBatchModalLabel.text(`Total students in batch: ${students.length}`);

  if (!students.length) {
    const activeFilter = String($("#searchBatchStudentsInput").val() ?? "").trim();
    const hasFilter = activeFilter.length > 0;
    const emptyTitle = hasFilter ? "No matching students" : "No students yet";
    const emptyMessage = hasFilter ? "Try a different name, email, student number, or clear your search." : "Students assigned to this batch will appear here once available.";

    batchStudentsContainer.append(`<div class="col-md-12 d-flex">
                          <div class="card bg-blur-5 bg-semi-transparent border border-subtle shadow-sm rounded-3 w-100">
                            <div class="card-body py-4 py-md-5 px-3 px-md-4 text-center d-flex flex-column align-items-center justify-content-center w-100 h-100">
                                                        <div class="mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10" style="width: 72px; height: 72px;">
                                                            <i class="bi ${hasFilter ? "bi-search" : "bi-people"}" style="font-size: 1.9rem; color: var(--bs-secondary-emphasis);"></i>
                                                        </div>
                                                        <h6 class="fw-semibold mb-2">${emptyTitle}</h6>
                                                        <p class="text-muted mb-0 mx-auto" style="max-width: 520px; font-size: 0.95rem; line-height: 1.55;">${emptyMessage}</p>
                                                        ${
                                                          hasFilter
                                                            ? `<button type="button" class="btn btn-sm btn-outline-secondary mt-3" id="clearBatchStudentsSearchBtn">
                                                            <i class="bi bi-x-circle me-1"></i>Clear search
                                                        </button>`
                                                            : ""
                                                        }
                                                    </div>
                                                </div>
                                            </div>`);

    $("#clearBatchStudentsSearchBtn")
      .off("click")
      .on("click", function () {
        $("#searchBatchStudentsInput").val("").trigger("input").trigger("focus");
      });
    return;
  }

  const badgeColorStatus = {
    active: { text: "Active", color: "info" },
    inactive: { text: "Inactive", color: "secondary" },
    never_logged_in: { text: "Never Logged In", color: "warning" },
    unknown: { text: "—", color: "dark" },
  };

  students.forEach((student) => {
    const studentCard = `<div class="col Student-Card">
                                    <div class="card bg-blur-5 bg-semi-transparent border border-subtle shadow-sm h-100 transition-all"
                                        style="--blur-lvl: 0.55">
                                        <div class="card-body p-3">
                                            <div class="d-flex flex-column flex-sm-row align-items-start gap-3 h-100">
                                                <img src="https://placehold.co/64x64/031633/6ea8fe?text=${student.initials}&font=poppins"
                                                    alt="Student Avatar" class="rounded-circle flex-shrink-0"
                                                    style="width: 48px; height: 48px; object-fit: cover;">
                                                <div class="d-flex flex-column min-w-0 flex-grow-1">
                                                    <span class="fw-semibold text-truncate">${student.full_name}</span>
                                                    <small class="text-muted text-truncate">${student.email}</small>
                                                    <small class="text-muted text-truncate mb-2">${student.year_label}</small>
                                                    <small class="text-muted"><span class="fw-medium">Coordinator:</span>
                                                        ${student.coordinator}</small>
                                                    <div class="d-flex flex-wrap gap-2 mt-3">
                                                        <span class="badge bg-${badgeColorStatus[student.account_status]?.color || "dark"} bg-opacity-10 text-${badgeColorStatus[student.account_status]?.color || "dark"} rounded-pill fw-medium">
                                                            ${badgeColorStatus[student.account_status]?.text || "Unknown"}
                                                        </span>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">${student.program_code} - ${student.year_label?.charAt(0) || ""}${student.section || ""}</span>
                                                        <span class="badge bg-secondary bg-opacity-10 text-secondary">${student.student_number}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`;
    batchStudentsContainer.append(studentCard);
  });
}

function filterBatchStudents(searchValue = "") {
  const keyword = String(searchValue).trim().toLowerCase();

  if (!keyword) {
    currentFilteredBatchStudents = [...currentBatchStudents];
    renderBatchStudents(currentBatchStudents);
    return;
  }

  const filteredStudents = currentBatchStudents.filter((student) => {
    const searchableFields = [student.full_name, student.email, student.coordinator, student.program_code, student.year_label, student.section, student.student_number, student.account_status];

    return searchableFields.some((field) =>
      String(field ?? "")
        .toLowerCase()
        .includes(keyword),
    );
  });

  currentFilteredBatchStudents = filteredStudents;
  renderBatchStudents(filteredStudents);
}

function bindBatchStudentActions() {
  const ViewStudentBatchModal = $("#ViewStudentBatchModal");
  const searchBatchStudentsInput = $("#searchBatchStudentsInput");
  const refreshBatchStudentsBtn = $("#refreshBatchStudentsBtn");
  const exportBatchStudentsBtn = $("#exportBatchStudentsBtn");

  const updateExportLabel = () => {
    const activeFilter = String(searchBatchStudentsInput.val() ?? "").trim();
    const hasFilter = activeFilter.length > 0;
    const studentsToExport = hasFilter ? currentFilteredBatchStudents : currentBatchStudents;

    const label = hasFilter ? "Export Filtered CSV" : "Export CSV";
    exportBatchStudentsBtn.text(label);
    exportBatchStudentsBtn.prop("disabled", !studentsToExport.length);
  };

  searchBatchStudentsInput.off("input.batchStudents").on("input.batchStudents", function () {
    filterBatchStudents($(this).val());
    updateExportLabel();
  });

  refreshBatchStudentsBtn.off("click.batchStudents").on("click.batchStudents", function () {
    if (!currentViewingBatchUuid) {
      ToastVersion(swalTheme, "No selected batch to refresh.", "info", 2500, "top-end");
      return;
    }

    const $btn = $(this);
    $btn.prop("disabled", true);
    loadBatchStudents(currentViewingBatchUuid);

    setTimeout(() => {
      $btn.prop("disabled", false);
    }, 1000);
  });

  exportBatchStudentsBtn.off("click.batchStudents").on("click.batchStudents", function () {
    if (!currentViewingBatchUuid) {
      ToastVersion(swalTheme, "No selected batch to export.", "info", 2500, "top-end");
      return;
    }

    const activeFilter = String(searchBatchStudentsInput.val() ?? "").trim();
    const studentsToExport = activeFilter ? currentFilteredBatchStudents : currentBatchStudents;

    if (!studentsToExport.length) {
      ToastVersion(swalTheme, "No students to export for this batch.", "info", 2500, "top-end");
      return;
    }

    const escapeCsv = (value) => `"${String(value ?? "").replace(/"/g, '""')}"`;
    const headers = ["Full Name", "Email", "Student Number", "Program", "Year", "Section", "Coordinator", "Account Status"];

    const rows = studentsToExport.map((student) => {
      return [student.full_name, student.email, student.student_number, student.program_code, student.year_label, student.section, student.coordinator, student.account_status]
        .map(escapeCsv)
        .join(",");
    });

    const csvText = [headers.join(","), ...rows].join("\n");
    const blob = new Blob([csvText], { type: "text/csv;charset=utf-8;" });
    const downloadUrl = URL.createObjectURL(blob);
    const link = document.createElement("a");

    link.href = downloadUrl;
    link.download = `Batch_${currentViewingBatchUuid}_Students.csv`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(downloadUrl);

    updateExportLabel();
  });

  ViewStudentBatchModal.off("hidden.bs.modal.batchStudents").on("hidden.bs.modal.batchStudents", function () {
    currentViewingBatchUuid = null;
    currentBatchStudents = [];
    currentFilteredBatchStudents = [];
    searchBatchStudentsInput.val("");
    updateExportLabel();
  });

  updateExportLabel();
}
const loadingSkeleton = `<div class="col">
                            <!-- Batch Card Loading Placeholder -->
                            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border border-subtle"
                                style="--blur-lvl: <?= $opacitylvl ?>;">
                                <div
                                    class="card-body p-4 p-md-5 d-flex flex-column justify-content-center align-items-center min-vh-50">
                                    <div class="text-center">
                                        <div class="mb-3">
                                            <div class="spinner-border text-secondary-emphasis" role="status"
                                                style="width: 2.5rem; height: 2.5rem;">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>
                                        <p class="text-muted fw-medium mb-0" style="font-size: 0.95rem;">
                                            Loading batch details...
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>`;
const noBatchesPlaceholder = `<div class="col">
                            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border border-subtle" style="--blur-lvl: <?= $opacitylvl ?>;">
                                <div
                                    class="card-body p-4 p-md-5 d-flex flex-column justify-content-center align-items-center min-vh-50">
                                    <div class="text-center">
                                        <p class="text-muted fw-medium mb-0" style="font-size: 0.95rem;">
                                            <i class="bi bi-exclamation-triangle-fill me-2" style="font-size: 1.2rem;"></i>
                                            No batches found. Please add a new batch to get started.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>`;
function loadBatchStudents(batchUuid) {
  const batchStudentsContainer = $("#batchStudentsContainer");

  currentViewingBatchUuid = batchUuid;
  bindBatchStudentActions();

  $.ajax({
    url: "../../../process/batches/get_batch_students",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      batch_uuid: batchUuid,
    },
    beforeSend: function () {
      batchStudentsContainer.empty();
      batchStudentsContainer.append(`<div class="d-flex flex-column align-items-center gap-3 py-5">
                                                <div class="spinner-border text-secondary-emphasis" role="status" style="width: 3rem; height: 3rem;">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                                <p class="text-muted fw-medium mb-0" style="font-size: 1rem;">Loading students...</p>
                                            </div>`);
    },
    success: function (response) {
      if (response.status === "success") {
        currentBatchStudents = Array.isArray(response.students) ? response.students : [];
        currentFilteredBatchStudents = [...currentBatchStudents];
        filterBatchStudents($("#searchBatchStudentsInput").val());
      } else {
        currentBatchStudents = [];
        currentFilteredBatchStudents = [];
        batchStudentsContainer.append(`<div class="d-flex flex-column align-items-center gap-3 py-5">
                                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: var(--bs-danger);"></i>
                                                <p class="text-muted fw-medium mb-0" style="font-size: 1rem;">${response.message}</p>
                                            </div>`);
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      batchStudentsContainer.empty();
      batchStudentsContainer.append(`<div class="d-flex flex-column align-items-center gap-3 py-5">
                                                <i class="bi bi-exclamation-triangle-fill" style="font-size: 3rem; color: var(--bs-danger);"></i>
                                                <p class="text-muted fw-medium mb-0" style="font-size: 1rem;">An error occurred while loading students.</p>
                                            </div>`);
      Errors(xhr, status, error);
    },
  });
}

function loadBatchDetails(batchUuid) {
  const EditschoolYearInput = $("#EditschoolYearInput");
  const EditsemesterInput = $("#EditsemesterInput");
  const EditstartDateInput = $("#EditstartDateInput");
  const EditendDateInput = $("#EditendDateInput");
  const EditrequiredHoursInput = $("#EditrequiredHoursInput");
  const batchDurationInfo = $("#batchDurationInfo");
  const EditBatchModal = $("#EditBatchModal");
  EditBatchModal.attr("data-editbatch-uuid", batchUuid);

  batchDurationInfo.text("");

  $.ajax({
    url: "../../../process/batches/get_batch_details",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      batch_uuid: batchUuid,
    },
    beforeSend: function () {
      EditschoolYearInput.val("").prop("disabled", true);
      EditsemesterInput.val("").prop("disabled", true);
      EditstartDateInput.val("").prop("disabled", true);
      EditendDateInput.val("").prop("disabled", true);
      EditrequiredHoursInput.val("").prop("disabled", true);
    },
    success: function (response) {
      if (response.status === "success") {
        const batch = response.data;
        EditschoolYearInput.val(batch.school_year).prop("disabled", false);
        EditsemesterInput.val(batch.semester).prop("disabled", false);
        EditstartDateInput.val(batch.start_date_raw).prop("disabled", false);
        EditendDateInput.val(batch.end_date_raw).prop("disabled", false);
        EditrequiredHoursInput.val(batch.required_hours).prop("disabled", false);

        EditschoolYearInput.attr("data-original-value", batch.school_year);
        EditsemesterInput.attr("data-original-value", batch.semester);
        EditstartDateInput.attr("data-original-value", batch.start_date_raw);
        EditendDateInput.attr("data-original-value", batch.end_date_raw);
        EditrequiredHoursInput.attr("data-original-value", batch.required_hours);

        // Calculate and display batch duration in days based on start and end date
        const startDate = new Date(batch.start_date_raw);
        const endDate = new Date(batch.end_date_raw);
        const durationInDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        batchDurationInfo.text(`Batch duration: ${durationInDays} day${durationInDays !== 1 ? "s" : ""}`);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
    complete: function () {
      EditschoolYearInput.prop("disabled", false);
      EditsemesterInput.prop("disabled", false);
      EditstartDateInput.prop("disabled", false);
      EditendDateInput.prop("disabled", false);
      EditrequiredHoursInput.prop("disabled", false);
    },
  });
}

function closeBatch(batchUuid, batchLabel) {
  const closeBatchModal = $("#closeBatchModal");
  closeBatchModal.attr("data-closebatch-uuid", batchUuid);
  $("#batchToCloseName").text(batchLabel);

  const randomWord = randomConformationWord[Math.floor(Math.random() * randomConformationWord.length)];
  $("#closeBatchNameConfirm").text(`${randomWord}`);
  $("#closeBatchInput").prop("placeholder", `Type "${randomWord}" to confirm`).val("").trigger("focus");

  $("#confirmCloseBatchBtn")
    .off("click")
    .on("click", function () {
      const userInput = $("#closeBatchInput").val().trim();
      if (userInput.toUpperCase() === randomWord) {
        $.ajax({
          url: "../../../process/batches/close_batch",
          method: "POST",
          dataType: "json",
          data: {
            csrf_token: csrfToken,
            batch_uuid: batchUuid,
          },
          beforeSend: function () {
            $("#confirmCloseBatchBtn").prop("disabled", true).html(`<span class="spinner-border spinner-border-sm text-light" role="status" aria-hidden="true"></span> Closing...`);
          },
          success: function (response) {
            if (response.status === "success") {
              closeBatchModal.modal("hide");
              ToastVersion(swalTheme, "Batch closed successfully.", "success", 3000, "top-end");
              loadBatches();
            } else {
              ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
            }
          },
          error: function (xhr, status, error) {
            Errors(xhr, status, error);
          },
          complete: function () {
            $("#confirmCloseBatchBtn").prop("disabled", false).html(`Confirm Close`);
          },
        });
      } else {
        ToastVersion(swalTheme, "Confirmation text does not match. Please try again.", "warning", 3000, "top-end");
      }
    });
}

function activateBatch(batchUuid, batchLabel, activebatchlabel) {
  const activateBatchModal = $("#ActivateBatchModal");
  activateBatchModal.attr("data-activatebatch-uuid", batchUuid);
  $("#batchToActivateName").text(batchLabel);
  $("#currentActiveBatchName").text(activebatchlabel);
}

function loadBatches() {
  const batchesContainer = $("#batchesContainer");
  batchesContainer.empty();

  $.ajax({
    url: "../../../process/batches/get_batches",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      batchesContainer.append(loadingSkeleton);
    },
    success: function (response) {
      batchesContainer.empty();
      if (response.status === "success") {
        const batches = response.batches;
        const activebatch = response.active_batch;
        if (batches.length > 0) {
          batches.forEach((batch) => {
            const batchCard = `<div class="col">
            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm ${batch.status === "active" ? "border-success" : "border-subtle"}">
                <!-- Header Section -->
                <div class="card-body pb-3 pt-4 px-4 border-bottom border-subtle">
                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                    <div class="flex-grow-1 min-w-0">
                    <h5 class="card-title mb-1 fw-bold text-truncate fs-6">${batch.label}</h5>
                    <small class="text-muted d-block">
                        <i class="bi bi-calendar3 me-1"></i>${batch.start_date} — ${batch.end_date}
                    </small>
                    </div>
                    <div class="flex-shrink-0">
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill fw-medium ${batch.status === "active" ? "" : "d-none"}">Active</span>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill fw-medium ${batch.status === "upcoming" ? "" : "d-none"}">Upcoming</span>
                    </div>
                </div>
                </div>
                <!-- Stats Section -->
                <div class="card-body py-3 px-4">
                <div class="row row-cols-2 row-cols-lg-4 g-2 mb-3">
                    <div class="col">
                    <div class="d-flex flex-column">
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Students</small>
                        <span class="fs-6 fw-semibold mt-1">${batch.student_count}</span>
                    </div>
                    </div>
                    <div class="col">
                    <div class="d-flex flex-column">
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Req. Hours</small>
                        <span class="fs-6 fw-semibold mt-1">${batch.required_hours}</span>
                    </div>
                    </div>
                    <div class="col">
                    <div class="d-flex flex-column">
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">Activated</small>
                        <span class="fs-6 fw-semibold mt-1 text-truncate">${batch.activated_at ? batch.activated_at : "—"}</span>
                    </div>
                    </div>
                    <div class="col">
                    <div class="d-flex flex-column">
                        <small class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 500; letter-spacing: 0.4px;">By</small>
                        <span class="fs-6 fw-semibold mt-1 text-truncate">${batch.created_by}</span>
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
                    <button class="btn btn-sm btn-outline-secondary py-2 px-3 rounded-2 ${batch.status === "active" ? "" : "d-none"}" data-bs-toggle="modal" data-bs-target="#ViewStudentBatchModal" id="viewStudentsBtn-${batch.uuid}">
                    <i class="bi bi-eye2"></i> View Students
                    </button>
                    <button class="btn btn-sm btn-outline-secondary py-2 px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#EditBatchModal" id="editBatchBtnc-${batch.uuid}">
                    <i class="bi bi-pencil"></i><span class="d-none d-sm-inline"> Edit</span>
                    </button>
                    <button class="btn btn-sm btn-outline-danger py-2 px-3 rounded-2 ${batch.status === "active" ? "" : "d-none"}" data-bs-toggle="modal" data-bs-target="#closeBatchModal" id="closeBatchBtn-${batch.uuid}">
                    <i class="bi bi-x-lg"></i><span class="d-none d-sm-inline"> Close</span>
                    </button>
                    <button class="btn btn-sm btn-outline-success py-2 px-3 rounded-2 ${batch.status === "upcoming" ? "" : "d-none"}" data-bs-toggle="modal" data-bs-target="#ActivateBatchModal" id="activateBatchBtn-${batch.uuid}">
                    <i class="bi bi-check-lg"></i><span class="d-none d-sm-inline"> Activate</span>
                    </button>
                </div>
                </div>
            </div>
            </div>`;
            batchesContainer.append(batchCard);
          });

          // Attach event listeners for the dynamically created buttons
          batches.forEach((batch) => {
            $(`#viewStudentsBtn-${batch.uuid}`).on("click", function () {
              loadBatchStudents(batch.uuid);
            });

            $(`#editBatchBtnc-${batch.uuid}`).on("click", function () {
              loadBatchDetails(batch.uuid);
            });

            $(`#closeBatchBtn-${batch.uuid}`).on("click", function () {
              closeBatch(batch.uuid, batch.label);
            });

            $(`#activateBatchBtn-${batch.uuid}`).on("click", function () {
              activateBatch(batch.uuid, batch.label, activebatch.label);
            });
          });
        } else {
          batchesContainer.append(noBatchesPlaceholder);
          ToastVersion(swalTheme, "No batches found. Please add a new batch to get started.", "info", 3000, "top-end");
        }
      } else if (response.status === "critical") {
        batchesContainer.append(noBatchesPlaceholder);
        ModalVersion(swalTheme, "Critical Error", response.Details, "error", "OK");
      } else {
        batchesContainer.append(noBatchesPlaceholder);
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      batchesContainer.empty();
      batchesContainer.append(noBatchesPlaceholder);
      Errors(xhr, status, error);
    },
  });
}

function updateDurationInfo({
  startInputSelector = "#EditstartDateInput",
  endInputSelector = "#EditendDateInput",
  outputSelectors = ["#batchDurationInfo", "#batchDurationInfoNew"],
} = {}) {
  const EditstartDateInput = $(startInputSelector);
  const EditendDateInput = $(endInputSelector);
  const outputElements = outputSelectors.map((selector) => $(selector)).filter((element) => element.length > 0);

  const startDate = new Date(EditstartDateInput.val());
  const endDate = new Date(EditendDateInput.val());

  if (isNaN(startDate) || isNaN(endDate)) {
    outputElements.forEach((element) => element.text(""));
    return;
  }

  const durationInDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
  outputElements.forEach((element, index) => {
    const label = index === 0 ? "Batch duration" : "New duration";
    element.text(`${label}: ${durationInDays} day${durationInDays !== 1 ? "s" : ""}`);
  });
}

$(document).ready(function () {
  bindBatchStudentActions();
  loadBatches();

  $("#EditstartDateInput, #EditendDateInput").on("change", function () {
    updateDurationInfo({
      startInputSelector: "#EditstartDateInput",
      endInputSelector: "#EditendDateInput",
      outputSelectors: ["#batchDurationInfo"],
    });
  });

  $("#startDateInput, #endDateInput").on("change", function () {
    updateDurationInfo({
      startInputSelector: "#startDateInput",
      endInputSelector: "#endDateInput",
      outputSelectors: ["#batchDurationInfoNew"],
    });
  });

  $("#cancelEditBatchBtn").on("click", function () {
    const EditschoolYearInput = $("#EditschoolYearInput");
    const EditsemesterInput = $("#EditsemesterInput");
    const EditstartDateInput = $("#EditstartDateInput");
    const EditendDateInput = $("#EditendDateInput");
    const EditrequiredHoursInput = $("#EditrequiredHoursInput");
    const batchDurationInfo = $("#batchDurationInfo");
    const EditBatchModal = $("#EditBatchModal");
    EditBatchModal.attr("data-editbatch-uuid", "");

    EditschoolYearInput.val("").prop("disabled", true);
    EditsemesterInput.val("").prop("disabled", true);
    EditstartDateInput.val("").prop("disabled", true);
    EditendDateInput.val("").prop("disabled", true);
    EditrequiredHoursInput.val("").prop("disabled", true);
    batchDurationInfo.text("");

    EditschoolYearInput.attr("data-original-value", "");
    EditsemesterInput.attr("data-original-value", "");
    EditstartDateInput.attr("data-original-value", "");
    EditendDateInput.attr("data-original-value", "");
    EditrequiredHoursInput.attr("data-original-value", "");
  });

  $("#saveEditBatchBtn").on("click", function () {
    const batchUuid = $("#EditBatchModal").attr("data-editbatch-uuid");
    const EditschoolYearInput = $("#EditschoolYearInput");
    const EditsemesterInput = $("#EditsemesterInput");
    const EditstartDateInput = $("#EditstartDateInput");
    const EditendDateInput = $("#EditendDateInput");
    const EditrequiredHoursInput = $("#EditrequiredHoursInput");

    const hasChanges =
      EditschoolYearInput.val() !== EditschoolYearInput.attr("data-original-value") ||
      EditsemesterInput.val() !== EditsemesterInput.attr("data-original-value") ||
      EditstartDateInput.val() !== EditstartDateInput.attr("data-original-value") ||
      EditendDateInput.val() !== EditendDateInput.attr("data-original-value") ||
      EditrequiredHoursInput.val() !== EditrequiredHoursInput.attr("data-original-value");

    if (!hasChanges) {
      ToastVersion(swalTheme, "No changes detected to save.", "info", 2500, "top-end");
      return;
    }

    if (!EditschoolYearInput.val() || !EditsemesterInput.val() || !EditstartDateInput.val() || !EditendDateInput.val() || !EditrequiredHoursInput.val()) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 2500, "top-end");
      return;
    }

    $.ajax({
      url: "../../../process/batches/update_batch",
      method: "POST",
      data: {
        batch_uuid: batchUuid,
        school_year: EditschoolYearInput.val(),
        semester: EditsemesterInput.val(),
        start_date: EditstartDateInput.val(),
        end_date: EditendDateInput.val(),
        required_hours: EditrequiredHoursInput.val(),
        csrf_token: csrfToken,
      },
      success: function (response) {
        if (response.status === "success") {
          $("#EditBatchModal").modal("hide");
          ToastVersion(swalTheme, "Batch updated successfully.", "success", 3000, "top-end");
          loadBatches();
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });

  $("#closeBatchInput").on("input", function () {
    const userInput = $(this).val().trim();
    const randomWord = $("#closeBatchNameConfirm").text().trim();
    $("#confirmCloseBatchBtn").prop("disabled", userInput.toUpperCase() !== randomWord);
  });

  $("#cancelCloseBatchBtn").on("click", function () {
    $("#closeBatchModal").attr("data-closebatch-uuid", "");
    $("#batchToCloseName").text("");
    $("#closeBatchNameConfirm").text("");
    $("#closeBatchInput").val("").prop("placeholder", "").trigger("blur");
    $("#confirmCloseBatchBtn").prop("disabled", true);
  });

  $("#cancelActivateBatchBtn").on("click", function () {
    $("#ActivateBatchModal").attr("data-activatebatch-uuid", "");
    $("#batchToActivateName").text("");
    $("#currentActiveBatchName").text("");
  });

  $("#confirmActivateBatchBtn").on("click", function () {
    const batchUuid = $("#ActivateBatchModal").attr("data-activatebatch-uuid");
    if (!batchUuid) {
      ToastVersion(swalTheme, "No batch selected to activate.", "info", 2500, "top-end");
      return;
    }

    $.ajax({
      url: "../../../process/batches/activate_batch",
      method: "POST",
      data: {
        batch_uuid: batchUuid,
        csrf_token: csrfToken,
      },
      success: function (response) {
        if (response.status === "success") {
          $("#ActivateBatchModal").modal("hide");
          ToastVersion(swalTheme, "Batch activated successfully.", "success", 3000, "top-end");
          loadBatches();
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });

  $("#cancelNewBatchBtn").on("click", function () {
    $("#NewBatchModal").find("input, select").val("").trigger("change").trigger("blur");
    $("#batchDurationInfoNew").text("");
  });

  $("#saveNewBatchBtn").on("click", function () {
    const schoolYearInput = $("#schoolYearInput");
    const semesterInput = $("#semesterInput");
    const startDateInput = $("#startDateInput");
    const endDateInput = $("#endDateInput");
    const requiredHoursInput = $("#requiredHoursInput");
    const activateImmediatelySwitch = $("#activateImmediatelySwitch");

    if (!schoolYearInput.val() || !semesterInput.val() || !startDateInput.val() || !endDateInput.val() || !requiredHoursInput.val()) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 2500, "top-end");
      return;
    }

    $.ajax({
      url: "../../../process/batches/create_batch",
      method: "POST",
      data: {
        school_year: schoolYearInput.val(),
        semester: semesterInput.val(),
        start_date: startDateInput.val(),
        end_date: endDateInput.val(),
        required_hours: requiredHoursInput.val(),
        activate_immediately: activateImmediatelySwitch.is(":checked") ? 1 : 0,
        csrf_token: csrfToken,
      },
      success: function (response) {
        if (response.status === "success") {
          $("#NewBatchModal").modal("hide");
          ToastVersion(swalTheme, "Batch created successfully.", "success", 3000, "top-end");
          loadBatches();
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });
});
