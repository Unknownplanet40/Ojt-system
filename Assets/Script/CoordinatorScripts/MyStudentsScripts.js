import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

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
  select.empty().append('<option value="" selected disabled hidden>Select Program</option>');
  programs.forEach((p) => {
    const option = `<option value="${p.uuid}" class="bg-dark text-white">${p.code} - ${p.name}</option>`;
    if (filter.find(`option[value="${p.uuid}"]`).length === 0) {
      filter.append(option);
    }
    select.append(option);
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
          <div class="card h-100 border-0 shadow-sm rounded-4 bg-blur-10 bg-semi-transparent overflow-hidden" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1) !important;">
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
              
              <div class="mt-auto hstack gap-2">
                <button class="btn btn-primary flex-grow-1 rounded-pill shadow-sm py-2" data-action="view" data-profile-uuid="${s.profile_uuid}">
                  <i class="bi bi-person-badge me-2"></i>View
                </button>
                <button class="btn btn-outline-warning rounded-circle shadow-sm" style="width: 42px; height: 42px;" data-action="reset" data-user-uuid="${s.user_uuid}" data-name="${s.full_name}" title="Reset Password">
                  <i class="bi bi-key"></i>
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
        $("#StudentCreatedModal").attr("data-profile-uuid", response.profile_uuid).modal("show");
        loadStudents();
        // Clear fields
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

function viewStudent(uuid) {
  $("#pageLoader").fadeIn();
  $.ajax({
    url: "../../../process/coordinators/get_student_full_details",
    type: "POST",
    data: { csrf_token: csrfToken, student_uuid: uuid },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const s = response.student;
        $("#viewStudentFullName").text(s.full_name);
        $("#viewStudentNumber").text(s.student_number);
        $("#viewStudentEmail").text(s.email);
        $("#viewStudentProgram").text(s.program_name);
        $("#viewStudentYearSection").text(`${s.year_level}nd Year - ${s.section || 'N/A'}`);
        $("#viewStudentBatch").text(s.batch_label || s.batch_name || "N/A");
        $("#viewStudentStatus").html(getStatusBadge(s.account_status, s.status_label));
        $("#viewStudentProfilePic").attr("src", s.profile_name ? `../../../Assets/Images/profiles/${s.profile_name}` : `https://placehold.co/80x80/C1C1C1/000000/png?text=${s.initials}&font=poppins`);
        
        $("#editStudentBtnView").attr("data-profile-uuid", uuid);
        $("#exportPdfBtnView").attr("data-profile-uuid", uuid);
        $("#resetPasswordBtnView").attr("data-user-uuid", s.user_uuid).attr("data-name", s.full_name);
        
        $("#ViewStudentModal").modal("show");
      } else {
        toast("error", response.message);
      }
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => $("#pageLoader").fadeOut(),
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

  $(document).on("click", '[data-action="view"]', function () {
    viewStudent($(this).data("profile-uuid"));
  });

  $("#resetPasswordBtnView").on("click", function () {
    $("#ViewStudentModal").modal("hide");
    resetPassword($(this).data("user-uuid"), $(this).data("name"));
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
    formData.append("bulk_file", file); // Match backend field name

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
      complete: () => $("#pageLoader").fadeOut(),
    });
  });

  $("#confirmImportBtn").on("click", function () {
    $("#pageLoader").fadeIn();
    $.ajax({
      url: "../../../process/students/bulk_create",
      type: "POST",
      data: { csrf_token: csrfToken },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#bulkCreationModal").modal("hide");
          Swal.fire({
            title: "Success!",
            text: response.message,
            icon: "success",
            background: swalTheme.background,
            color: swalTheme.color,
          });
          // Reset UI for next time
          $("#validationResults").hide();
          $("#bulkCsvFile").val("");
          loadStudents();
        } else {
          toast("error", response.message);
        }
      },
      error: (xhr, status, error) => Errors(xhr, status, error),
      complete: () => $("#pageLoader").fadeOut(),
    });
  });

  $("#cancelImportBtn").on("click", function () {
    $("#validationResults").slideUp();
    $("#bulkCsvFile").val("");
  });

  $("#exportPdfBtn, #exportPdfBtnView").on("click", function() {
    const uuid = $(this).attr("data-profile-uuid");
    window.open(`../../../process/students/export_student_pdf?uuid=${uuid}`, '_blank');
  });

  $("#exportResetPdfBtn").on("click", function() {
    const name = $("#resetPasswordSuccessStudentName").text();
    const password = $("#resetPasswordSuccessTempPassword").text();
    window.open(`../../../process/students/export_reset_password_pdf?full_name=${encodeURIComponent(name)}&temp_password=${encodeURIComponent(password)}`, '_blank');
  });
});
