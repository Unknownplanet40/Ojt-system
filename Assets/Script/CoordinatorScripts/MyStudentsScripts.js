import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
let currentViewedStudent = null;

function getDownloadFileNameFromResponse(response, fallbackName) {
  const contentDisposition = response.headers.get("Content-Disposition") || "";
  const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
  const fileNameFromHeader = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "";
  return fileNameFromHeader || fallbackName;
}

function downloadBlobFile(blob, fileName) {
  const blobUrl = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = blobUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(blobUrl);
}

function loadStudents() {
  const filters = {
    csrf_token: csrfToken,
    program_uuid: $("#programFilter").val(),
    status: $("#statusFilter").val(),
    search: $("#searchInput").val(),
  };

  $.ajax({
    url: "../../../process/coordinators/get_my_students",
    type: "POST",
    data: filters,
    dataType: "json",
    success: function (response) {
      console.log("AJAX Response:", response);
      if (response.status === "success") {
        renderStats(response.stats);
        renderStudents(response.students);
        if ($("#programFilter option").length <= 1) {
          renderPrograms(response.programs);
        }
      } else {
        Errors(response.message);
      }
    },
    error: function () {
      Errors("Failed to fetch students data.");
    },
    complete: function () {
      $("#pageLoader").fadeOut();
    },
  });
}

function renderStats(stats) {
  $("#totalStudentsCount").text(stats.total);
  $("#activeStudentsCount").text(stats.active);
  $("#pendingAppsCount").text(stats.pending);
  $("#completedStudentsCount").text(stats.completed);
}

function renderPrograms(programs) {
  const filter = $("#programFilter");
  const select = $("#programSelect");
  const editSelect = $("#editProgramSelect");
  select.empty().append('<option value="" selected disabled hidden>Select Program</option>');
  if (editSelect.length) {
    editSelect.empty().append('<option value="" selected disabled hidden>Select Program</option>');
  }
  programs.forEach((p) => {
    const option = `<option value="${p.uuid}" class="bg-dark text-white">${p.code} - ${p.name}</option>`;
    if (filter.find(`option[value="${p.uuid}"]`).length === 0) {
      filter.append(option);
    }
    select.append(option);
    if (editSelect.length) {
      editSelect.append(option);
    }
  });
}

function resetEditStudentModal() {
  $("#editStudentFullName").text("");
  $("#editStudentNumberDisplay").text("");
  $("#editStudentEmail").text("");
  $("#editStudentNumber").text("");
  $("#editLastName").val("");
  $("#editFirstName").val("");
  $("#editMiddleName").val("");
  $("#editMobileNumber").val("");
  $("#editAddress").val("");
  $("#editEmergencyContact").val("");
  $("#editEmergencyContactNumber").val("");
  $("#editProgramSelect").val("");
  $("#editYearLevelSelect").val("");
  $("#editSection").val("");
  $("#editCoordinatorUuid").val("");
  $("#editStudentUuid").val("");
}

function openEditStudentModal(student) {
  if (!student) {
    toast("warning", "Student details are not available yet.");
    return;
  }

  currentViewedStudent = student;

  $("#editStudentFullName").text(student.full_name || "Student");
  $("#editStudentNumberDisplay").text(student.student_number || "N/A");
  $("#editStudentEmail").text(student.email || "N/A");
  $("#editStudentNumber").text(student.student_number || "N/A");
  $("#editLastName").val(student.last_name || "");
  $("#editFirstName").val(student.first_name || "");
  $("#editMiddleName").val(student.middle_name || "");
  $("#editMobileNumber").val(student.mobile || "");
  $("#editAddress").val(student.home_address || "");
  $("#editEmergencyContact").val(student.emergency_contact || "");
  $("#editEmergencyContactNumber").val(student.emergency_phone || "");
  $("#editProgramSelect").val(student.program_uuid || "");
  $("#editYearLevelSelect").val(String(student.year_level || ""));
  $("#editSection").val(student.section || "");
  $("#editCoordinatorUuid").val(student.coordinator_uuid || $("body").data("uuid") || "");
  $("#editStudentUuid").val(student.profile_uuid || "");

  $("#ViewStudentModal").one("hidden.bs.modal", function () {
    $("#EditStudentModal").modal("show");
  });
  $("#ViewStudentModal").modal("hide");
}

function saveEditedStudent() {
  const profileUuid = $("#editStudentUuid").val();

  if (!profileUuid) {
    toast("warning", "Unable to update this student right now.");
    return;
  }

  const btn = $("#saveEditStudentBtn");
  const originalHtml = btn.html();
  btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');
  $("#pageLoader").fadeIn();

  $.ajax({
    url: "../../../process/students/update_student",
    type: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: profileUuid,
      last_name: $("#editLastName").val().trim(),
      first_name: $("#editFirstName").val().trim(),
      middle_name: $("#editMiddleName").val().trim(),
      mobile: $("#editMobileNumber").val().trim(),
      home_address: $("#editAddress").val().trim(),
      emergency_contact: $("#editEmergencyContact").val().trim(),
      emergency_phone: $("#editEmergencyContactNumber").val().trim(),
      program_uuid: $("#editProgramSelect").val(),
      year_level: $("#editYearLevelSelect").val(),
      section: $("#editSection").val().trim(),
      coordinator_uuid: $("#editCoordinatorUuid").val(),
    },
    success: function (response) {
      if (response.status === "success") {
        toast("success", response.message || "Student updated successfully.");
        $("#EditStudentModal").modal("hide");
        loadStudents();
      } else {
        toast("error", response.message || "Unable to update student.");
      }
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: function () {
      btn.prop("disabled", false).html(originalHtml);
      $("#pageLoader").fadeOut();
    },
  });
}

function renderStudents(students) {
  const grid = $("#studentGrid");
  const emptyState = $("#emptyState");
  grid.empty();

  if (students.length === 0) {
    emptyState.removeClass("d-none");
    return;
  }

  emptyState.addClass("d-none");
  students.forEach((s) => {
    try {
      const profileImg = s.profile_name
        ? `../../../Assets/Images/profiles/${s.profile_name}`
        : `https://placehold.co/80x80/C1C1C1/000000/png?text=${s.initials || 'S'}&font=poppins`;

      const statusBadge = getStatusBadge(s.account_status, s.status_label);

      const card = `
        <div class="col-12 col-md-6 col-xl-4 mb-4">
          <div class="card h-100 border shadow-sm rounded-4 glass-ui glass-ui-strong overflow-hidden">
            <div class="card-body p-4">
              <div class="d-flex align-items-start gap-3 mb-4 overflow-hidden">
                <img src="${profileImg}" alt="${s.full_name}" 
                     class="rounded-circle border border-2 border-primary-subtle shadow-sm flex-shrink-0" 
                     style="width: 64px; height: 64px; object-fit: cover;">
                <div class="min-w-0 flex-grow-1">
                  <h6 class="mb-1 fw-bold text-break text-white" title="${s.full_name}">${s.full_name}</h6>
                  <p class="mb-0 text-white-50 small text-truncate">${s.student_number}</p>
                </div>
                <div class="flex-shrink-0 ms-2">
                   ${statusBadge}
                </div>
              </div>
              
              <div class="vstack gap-2 mb-4">
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                  <i class="bi bi-mortarboard fs-6 text-primary"></i>
                  <span class="text-truncate">${s.program_code} &bull; ${s.year_label}</span>
                </div>
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                  <i class="bi bi-envelope fs-6 text-primary"></i>
                  <span class="text-truncate">${s.email}</span>
                </div>
                <div class="d-flex align-items-center gap-2 text-white-50 small">
                  <i class="bi bi-geo-alt fs-6 text-primary"></i>
                  <span class="text-truncate">${s.section ? `Section ${s.section}` : 'N/A'}</span>
                </div>
              </div>
              
              <div class="mt-auto vstack gap-2">
                <div class="hstack gap-2">
                  <button class="btn btn-primary flex-grow-1 rounded-pill shadow-sm py-2" data-action="view" data-profile-uuid="${s.profile_uuid}">
                    <i class="bi bi-person-badge me-2"></i>View
                  </button>
                  <button class="btn btn-outline-warning rounded-circle shadow-sm" style="width: 42px; height: 42px;" data-action="reset" data-user-uuid="${s.user_uuid}" data-name="${s.full_name}" title="Reset Password">
                    <i class="bi bi-key"></i>
                  </button>
                </div>
                <button class="btn ${s.account_status === 'inactive' ? 'btn-success' : 'btn-outline-danger'} rounded-pill shadow-sm py-2 w-100" data-action="toggle-status" data-user-uuid="${s.user_uuid}" data-profile-uuid="${s.profile_uuid}" data-account-status="${s.account_status}" data-name="${s.full_name}" title="${s.account_status === 'inactive' ? 'Activate Account' : 'Deactivate Account'}">
                  <i class="bi ${s.account_status === 'inactive' ? 'bi-unlock' : 'bi-lock'} me-2"></i>${s.account_status === 'inactive' ? 'Activate' : 'Deactivate'}
                </button>
              </div>
            </div>
          </div>
        </div>
      `;
      grid.append(card);
    } catch (err) {
      console.error("Error rendering student card:", err, s);
    }
  });
}

function getStatusBadge(status, label) {
  let badgeClass = "bg-secondary";
  if (status === "active") badgeClass = "bg-success";
  if (status === "inactive") badgeClass = "bg-danger";
  if (status === "never_logged_in") badgeClass = "bg-warning text-dark";

  return `<span class="badge ${badgeClass} rounded-pill px-2 py-1 small d-inline-flex align-items-center justify-content-center">${label}</span>`;
}

function createStudent() {
  const data = {
    csrf_token: csrfToken,
    email: $("#studentEmail").val(),
    student_number: $("#studentNumber").val(),
    last_name: $("#lastName").val(),
    first_name: $("#firstName").val(),
    middle_name: $("#middleName").val(),
    program_uuid: $("#programSelect").val(),
    year_level: $("#yearLevelSelect").val(),
  };

  if (!data.email || !data.student_number || !data.last_name || !data.first_name || !data.program_uuid || !data.year_level) {
    toast("warning", "Please fill in all required fields.");
    return;
  }

  $("#pageLoader").fadeIn();
  $.ajax({
    url: "../../../process/students/create_student",
    type: "POST",
    data: data,
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        $("#CreateStudentModal").modal("hide");
        $("#createdStudentName").text(response.full_name);
        $("#createdStudentTempPassword").text(response.temp_password);
        $("#StudentCreatedModal")
          .attr("data-profile-uuid", response.profile_uuid)
          .attr("data-temp-password", response.temp_password)
          .modal("show");
        $("#exportPdfBtn")
          .attr("data-profile-uuid", response.profile_uuid)
          .attr("data-temp-password", response.temp_password);
        loadStudents();
        
        $("#CreateStudentModal input, #CreateStudentModal select").val("");
      } else {
        toast("error", response.message);
      }
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => $("#pageLoader").fadeOut(),
  });
}

function resetPassword(uuid, name) {
  ConfirmVersion(
    swalTheme,
    "Reset Password?",
    `Are you sure you want to reset the password for ${name}?`,
    "warning",
    "Yes, reset it!",
    "warning",
    "secondary",
    "Cancel"
  ).then((result) => {
    if (result.isConfirmed) {
      $("#pageLoader").fadeIn();
      $.ajax({
        url: "../../../process/students/reset_student_password",
        type: "POST",
        data: { csrf_token: csrfToken, user_uuid: uuid },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            $("#resetPasswordSuccessStudentName").text(name);
            $("#resetPasswordSuccessTempPassword").text(response.temp_password);
            $("#ResetPasswordSuccessModal").modal("show");
          } else {
            toast("error", response.message);
          }
        },
        error: (xhr, status, error) => Errors(xhr, status, error),
        complete: () => $("#pageLoader").fadeOut(),
      });
    }
  });
}

function toggleStudentStatus(uuid, currentStatus, name) {
  const isInactive = currentStatus === 'inactive';
  const action = isInactive ? 'reactivate' : 'deactivate';
  const actionText = isInactive ? 'Activate' : 'Deactivate';
  const description = isInactive 
    ? `Are you sure you want to activate the account for ${name}?`
    : `Are you sure you want to deactivate the account for ${name}?`;
  
  ConfirmVersion(
    swalTheme,
    `${actionText} Account?`,
    description,
    isInactive ? "success" : "warning",
    `Yes, ${action} it!`,
    isInactive ? "success" : "warning",
    "secondary",
    "Cancel"
  ).then((result) => {
    if (result.isConfirmed) {
      $("#pageLoader").fadeIn();
      $.ajax({
        url: "../../../process/students/deactivate_student",
        type: "POST",
        data: { csrf_token: csrfToken, user_uuid: uuid, action: action },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            toast(isInactive ? "success" : "info", response.message);
            loadStudents();
          } else {
            toast("error", response.message);
          }
        },
        error: (xhr, status, error) => Errors(xhr, status, error),
        complete: () => $("#pageLoader").fadeOut(),
      });
    }
  });
}



function toast(icon, title) {
  if (window.Swal) ToastVersion(swalTheme, title, icon, 3000, "top-end", "8");
}

$(document).ready(function () {
  loadStudents();

  $("#refreshBtn").on("click", function () {
    $("#pageLoader").fadeIn();
    loadStudents();
  });

  $("#searchInput").on("input", function () {
    loadStudents();
  });

  $("#programFilter, #statusFilter").on("change", function () {
    loadStudents();
  });

  $("#clearFiltersBtn").on("click", function () {
    $("#searchInput").val("");
    $("#programFilter").val("");
    $("#statusFilter").val("");
    loadStudents();
  });

  $("#createStudentBtn").on("click", createStudent);

  $(document).on("click", '[data-action="reset"]', function () {
    resetPassword($(this).data("user-uuid"), $(this).data("name"));
  });

  $(document).on("click", '[data-action="toggle-status"]', function () {
    toggleStudentStatus($(this).data("user-uuid"), $(this).data("account-status"), $(this).data("name"));
  });

  $(document).on("click", '[data-action="view"]', function () {
    const profileUuid = $(this).data("profile-uuid");
    window.location.href = `./viewStudentProfile?uuid=${profileUuid}&from=mystudents`;
  });

  $("#saveEditStudentBtn").on("click", saveEditedStudent);

  $("#EditStudentModal").on("hidden.bs.modal", function () {
    resetEditStudentModal();
  });

  $("#bulkimportBtn").on("click", function () {
    $("#CreateStudentModal").modal("hide");
    $("#bulkCreationModal").modal("show");
  });

  $("#downloadTemplateBtn").on("click", function () {
    window.location.href = "../../../process/students/bulk_download_template";
  });

  $("#uploadCsvBtn").on("click", function () {
    const file = $("#bulkCsvFile")[0].files[0];
    if (!file) {
      toast("warning", "Please select a CSV file.");
      return;
    }

    const formData = new FormData();
    formData.append("csrf_token", csrfToken);
    formData.append("bulk_file", file); 

    const btn = $(this);
    const ogText = btn.html();
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Validating...');
    $("#pageLoader").fadeIn();
    $.ajax({
      url: "../../../process/students/bulk_validate",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#totalRowsCount").text(response.total);
          $("#validRowsCount").text(response.valid_count);
          $("#errorRowsCount").text(response.error_count);

          if (response.valid_count > 0) {
            let validHtml = '<div class="d-flex flex-column gap-2">';
            response.valid_rows.forEach((row) => {
              validHtml += `
                <div class="p-3 rounded-3 bg-success bg-opacity-10 border border-success border-opacity-10">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <div class="d-flex align-items-center gap-2">
                      <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25">Row ${row.row_num}</span>
                      <span class="fw-bold small text-white">${row.full_name}</span>
                    </div>
                    <span class="small text-white-50">${row.student_number}</span>
                  </div>
                  <div class="ms-1 small text-white-50">${row.email} • ${row.program_code}</div>
                </div>`;
            });
            validHtml += "</div>";
            $("#validList").html(validHtml);
            $("#validationSuccess").show();
          } else {
            $("#validationSuccess").hide();
          }

          if (response.error_count > 0) {
            let errorHtml = '<div class="d-flex flex-column gap-2">';
            response.error_rows.forEach((row) => {
              let rowErrors = '<ul class="list-unstyled ms-1 mb-0 small text-white-50">';
              row.errors.forEach(err => {
                rowErrors += `<li class="d-flex gap-2"><i class="bi bi-dot"></i>${err}</li>`;
              });
              rowErrors += '</ul>';

              errorHtml += `
                <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-10">
                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-danger bg-opacity-25 text-danger border border-danger border-opacity-25">Row ${row.row_num}</span>
                    <i class="bi bi-exclamation-circle-fill text-danger small"></i>
                  </div>
                  ${rowErrors}
                </div>`;
            });
            errorHtml += "</div>";
            $("#errorList").html(errorHtml);
            $("#validationErrors").show();
          } else {
            $("#validationErrors").hide();
          }

          $("#confirmImportBtn").prop("disabled", response.valid_count === 0);
          $("#validationResults").slideDown();
          toast("success", "Validation complete. Please review the results.");
        } else {
          toast("error", response.message);
        }
      },
      error: (xhr, status, error) => Errors(xhr, status, error),
      complete: () => {
        btn.prop("disabled", false).html(ogText);
        $("#pageLoader").fadeOut();
      },
    });
  });

  $("#confirmImportBtn").on("click", function () {
    const btn = $(this);
    const ogText = btn.html();
    btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Creating Accounts...');
    $("#pageLoader").fadeIn();
    $.ajax({
      url: "../../../process/students/bulk_create",
      type: "POST",
      data: { csrf_token: csrfToken },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#bulkCreationModal").modal("hide");
          
          
          $("#bulkBatchLabelCurrent").text("Current Batch");
          $("#bulkAccountsCreatedCount").text(response.created_count);
          $("#bulkSuccessCreatedCount").text(response.created_count);
          $("#bulkSuccessFailedCount").text(response.failed_count);
          
          
          const createdTable = $("#bulkCreatedAccountsTable tbody");
          createdTable.empty();
          if (response.created && response.created.length > 0) {
            response.created.forEach(student => {
              const row = `<tr class="border-0 border-bottom border-light border-opacity-10">
                <td class="py-3 fw-semibold text-white">${student.full_name}</td>
                <td class="py-3 d-none d-sm-table-cell text-white-50 small">${student.email}</td>
                <td class="py-3 d-none d-md-table-cell text-white-50 small">${student.student_number}</td>
                <td class="py-3 fw-mono text-info">${student.temp_password}</td>
              </tr>`;
              createdTable.append(row);
            });
          }
          
          
          if (response.failed_count > 0 && response.failed && response.failed.length > 0) {
            $("#bulkFailedRowsContainer").removeClass("d-none");
            const failedTable = $("#bulkFailedRowsTableBody");
            failedTable.empty();
            response.failed.forEach(row => {
              const failRow = `<tr class="border-0 border-bottom border-light border-opacity-10">
                <td class="py-3 text-white-50">${row.name || row.first_name + ' ' + row.last_name}</td>
                <td class="py-3 d-none d-sm-table-cell text-white-50 small">${row.email || 'N/A'}</td>
                <td class="py-3 text-danger small">${row.error || 'Unknown error'}</td>
              </tr>`;
              failedTable.append(failRow);
            });
          } else {
            $("#bulkFailedRowsContainer").addClass("d-none");
          }
          
          
          $("#bulkSuccessModal").modal("show");

          toast("success", response.message || "Accounts created successfully.");
          
          
          $("#validationResults").hide();
          $("#bulkCsvFile").val("");
        } else {
          toast("error", response.message);
        }
      },
      error: (xhr, status, error) => Errors(xhr, status, error),
      complete: () => {
        btn.prop("disabled", false).html(ogText);
        $("#pageLoader").fadeOut();
      },
    });
  });

  $("#cancelImportBtn").on("click", function () {
    $("#validationResults").slideUp();
    $("#bulkCsvFile").val("");
  });

  // Bulk Success Modal Handlers
  $("#bulkToggleFailedRowsBtn").on("click", function() {
    $("#bulkFailedRowsDetails").toggleClass("d-none");
    $(this).text($(this).text() === "Show details" ? "Hide details" : "Show details");
  });

  $("#bulkPdfCredentialsBtn").on("click", function () {
    const $btn = $(this);
    const originalHtml = $btn.html();
    if ($btn.prop("disabled")) return;

    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Downloading...');
    $.ajax({
      url: "../../../process/students/bulk_export_pdf",
      method: "POST",
      data: { csrf_token: csrfToken },
      xhrFields: { responseType: "blob" },
      success: function (blob, _status, xhr) {
        const contentType = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
        if (contentType.includes("application/json")) {
          const reader = new FileReader();
          reader.onload = function () {
            try {
              const json = JSON.parse(String(reader.result || "{}"));
              ToastVersion(swalTheme, json.message || "Failed to export PDF credentials.", "error", 3500, "top-end");
            } catch {
              const text = String(reader.result || "");
              ToastVersion(swalTheme, text || "Failed to export PDF credentials.", "error", 3500, "top-end");
            }
          };
          reader.readAsText(blob);
          return;
        }
        const contentDisposition = xhr.getResponseHeader("Content-Disposition") || "";
        const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|")?([^"]+)/i);
        const fileName = (fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "") || "bulk_created_accounts.pdf";
        downloadBlobFile(blob, fileName);
        ToastVersion(swalTheme, "PDF credentials downloaded successfully.", "success", 2500, "top-end");
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
      complete: function () {
        $btn.prop("disabled", false).html(originalHtml);
      },
    });
  });

  $("#bulkCsvCredentialsBtn").on("click", function () {
    const $btn = $(this);
    const originalHtml = $btn.html();
    if ($btn.prop("disabled")) return;

    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Downloading...');
    $.ajax({
      url: "../../../process/students/bulk_export_csv",
      method: "POST",
      data: { csrf_token: csrfToken },
      xhrFields: { responseType: "blob" },
      success: function (blob, _status, xhr) {
        const contentType = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
        if (contentType.includes("application/json")) {
          const reader = new FileReader();
          reader.onload = function () {
            try {
              const json = JSON.parse(String(reader.result || "{}"));
              ToastVersion(swalTheme, json.message || "Failed to export CSV credentials.", "error", 3500, "top-end");
            } catch {
              ToastVersion(swalTheme, "Failed to export CSV credentials.", "error", 3500, "top-end");
            }
          };
          reader.readAsText(blob);
          return;
        }
        const contentDisposition = xhr.getResponseHeader("Content-Disposition") || "";
        const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|")?([^"]+)/i);
        const fileName = (fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "") || "bulk_created_accounts.csv";
        downloadBlobFile(blob, fileName);
        ToastVersion(swalTheme, "CSV credentials downloaded successfully.", "success", 2500, "top-end");
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
      complete: function () {
        $btn.prop("disabled", false).html(originalHtml);
      },
    });
  });

  $("#bulkViewAllStudentsBtn").on("click", function() {
    $("#bulkSuccessModal").modal("hide");
    loadStudents();
  });

  $("#bulkImportNewBatchBtn").on("click", function() {
    $("#bulkSuccessModal").modal("hide");
    $("#bulkCreationModal").modal("show");
    $("#bulkCsvFile").val("");
    $("#validationResults").hide();
  });

  $("#exportPdfBtn, #exportPdfBtnView").on("click", function() {
    const uuid = $(this).attr("data-profile-uuid") || $("#StudentCreatedModal").attr("data-profile-uuid");
    const tempPassword = $(this).attr("data-temp-password") || $("#StudentCreatedModal").attr("data-temp-password") || "";
    let url = `../../../process/students/export_student_pdf?uuid=${uuid}`;
    if (tempPassword) {
      url += `&temp_password=${encodeURIComponent(tempPassword)}`;
    }
    window.open(url, '_blank');
  });

  $("#exportResetPdfBtn").on("click", function() {
    const name = $("#resetPasswordSuccessStudentName").text();
    const password = $("#resetPasswordSuccessTempPassword").text();
    window.open(`../../../process/students/export_reset_password_pdf?full_name=${encodeURIComponent(name)}&temp_password=${encodeURIComponent(password)}`, '_blank');
  });
});
