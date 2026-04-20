import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
const driver = window.driver?.js?.driver;
let swalTheme = SwalTheme();

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const STUDENTS_TOUR_KEY = "admin_students_module_tour_done";
let studentsStatuses = [];
let studentsStatusLabels = {
  active: "Active",
  inactive: "Inactive",
  never_logged_in: "Never Logged In",
  suspended: "Suspended",
  unknown: "Unknown",
};

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
const tooltipList = [...tooltipTriggerList].map((tooltipTriggerEl) => new bootstrap.Tooltip(tooltipTriggerEl));

const loadingRow = `
        <tr class="border-0">
            <td colspan="7" class="text-center py-4 bg-transparent border-0" style="cursor: wait;">
                <div class="d-flex flex-column align-items-center gap-2">
                    <div class="spinner-border text-secondary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>
                        <p class="mb-2 text-body fw-semibold">Loading students...</p>
                        <small class="text-muted d-block" style="font-size: 0.875rem;">Please wait while we fetch the student data.</small>
                    </div>
                </div>
            </td>
        </tr>`;

const emptyRow = `<tr class="border-0">
                            <td colspan="7" class="text-center py-4 bg-transparent border-0" style="cursor: default;">
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-person-x fa-lg"></i>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-body fw-semibold">No students found</p>
                                        <small class="text-muted d-block" style="font-size: 0.875rem;">Click "Add Student" to create a new student account.</small>
                                    </div>
                                </div>
                            </td>
                        </tr>`;

function startStudentsModuleTour() {
  if (!window.driver?.js?.driver || typeof driver !== "function") {
    ToastVersion(swalTheme, "Guided tour is currently unavailable.", "warning", 3000, "top-end");
    return;
  }

  const moduleTour = driver({
    showProgress: true,
    animate: true,
    smoothScroll: true,
    allowClose: true,
    doneBtnText: "Finish",
    nextBtnText: "&#187;",
    prevBtnText: "&#171;",
    popoverClass: "bg-blur-10 bg-semi-transparent text-body",
    overlayColor: "rgba(0, 0, 0, 0.80)",
    steps: [
      {
        element: "#dashboardTitle",
        popover: {
          title: "Students Module",
          description: "This page is where Admin manages student accounts and bulk imports.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#addStudentOpenBtn",
        popover: {
          title: "Add Student",
          description: "Use this button to create a single student account with temporary credentials.",
          side: "left",
          align: "center",
        },
      },
      {
        element: "#searchInput",
        popover: {
          title: "Search",
          description: "Search students by name or email.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#programFilter",
        popover: {
          title: "Program Filter",
          description: "Filter students by academic program.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#yearlvlFilter",
        popover: {
          title: "Year Filter",
          description: "Filter students by year level.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#statusFilter",
        popover: {
          title: "Status Filter",
          description: "Filter by account status such as Active, Never Logged In, or Inactive.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#studentsTable",
        popover: {
          title: "Student List",
          description: "Each row shows profile details and quick actions from the three-dot menu.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "#activeBatchLabel",
        popover: {
          title: "Active Batch Context",
          description: "This shows which batch is currently active and how many students belong to it.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#startStudentsTourLink",
        popover: {
          title: "Replay Tour",
          description: "Click here anytime to replay this quick guide.",
          side: "top",
          align: "start",
        },
      },
    ],
    onDestroyed: () => {
      localStorage.setItem(STUDENTS_TOUR_KEY, "1");
    },
  });

  moduleTour.drive();
}

function updateActiveBatchHeader(activeBatch, activeBatchCount) {
  const $label = $("#activeBatchLabel");
  const $count = $("#activeBatchCount");
  const $summary = $label.closest("p");

  const hasActiveBatch = Boolean(activeBatch && activeBatch.label);
  const rawBatchLabel = hasActiveBatch ? String(activeBatch.label) : "No Active Batch";
  const richBatchLabel = hasActiveBatch ? `Batch: ${rawBatchLabel}` : rawBatchLabel;
  const normalizedCount = Number.isFinite(activeBatchCount) ? Math.max(0, activeBatchCount) : 0;

  $label
    .text(richBatchLabel)
    .attr("data-batch-label", hasActiveBatch ? rawBatchLabel : "");

  $count.text(hasActiveBatch ? normalizedCount : 0);

  $label
    .removeClass("fw-semibold text-success-emphasis text-muted fst-italic")
    .addClass(hasActiveBatch ? "fw-semibold text-success-emphasis" : "text-muted fst-italic");

  $count
    .removeClass("fw-semibold text-success text-muted")
    .addClass(hasActiveBatch ? "fw-semibold text-success" : "text-muted");

  $summary
    .removeClass("opacity-75")
    .toggleClass("opacity-75", !hasActiveBatch);
}

function getStudents() {
  const studentsTable = $("#studentsTable tbody");
  studentsTable.empty();
  $.ajax({
    url: "../../../process/students/get_students",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      program_uuid: $("#programFilter").val(),
      year_level: $("#yearlvlFilter").val(),
      status: $("#statusFilter").val(),
      search: $("#searchInput").val(),
    },
    beforeSend: function () {
      studentsTable.html(loadingRow);
    },
    success: function (response) {
      if (response.status === "success") {
        studentsTable.empty();

        const activeBatch = response.active_batch || null;
        const activeBatchCount = Number.parseInt(response.active_batch_count, 10) || 0;
        updateActiveBatchHeader(activeBatch, activeBatchCount);

        const coordinatorSelect = $("#coordinatorSelect");
        const editCoordinatorSelect = $("#editCoordinatorSelect");
        coordinatorSelect.empty();
        coordinatorSelect.append('<option value="" class="CustomOption" selected disabled hidden>Select coordinator</option>');
        editCoordinatorSelect.empty();
        editCoordinatorSelect.append('<option value="" class="CustomOption" selected disabled hidden>Select coordinator</option>');

        if (response.coordinators && response.coordinators.length > 0) {
          response.coordinators.forEach((coordinator) => {
            coordinatorSelect.append(`<option class="CustomOption" value="${coordinator.uuid}">${coordinator.full_name} &bull; ${coordinator.department}</option>`);
            editCoordinatorSelect.append(`<option class="CustomOption" value="${coordinator.uuid}">${coordinator.full_name} &bull; ${coordinator.department}</option>`);
          });
        } else {
          coordinatorSelect.append('<option value="" class="CustomOption" disabled>No coordinators available</option>');
          editCoordinatorSelect.append('<option value="" class="CustomOption" disabled>No coordinators available</option>');
        }

        const programSelect = $("#programSelect");
        const editProgramSelect = $("#editProgramSelect");
        const programFilter = $("#programFilter");
        programFilter.empty();
        programSelect.empty();
        editProgramSelect.empty();
        programSelect.append('<option value="" class="CustomOption" selected disabled hidden>Select program</option>');
        editProgramSelect.append('<option value="" class="CustomOption" selected disabled hidden>Select program</option>');

        if (response.programs && response.programs.length > 0) {
          response.programs.forEach((program) => {
            programSelect.append(`<option class="CustomOption" value="${program.uuid}">${program.label}</option>`);
            editProgramSelect.append(`<option class="CustomOption" value="${program.uuid}">${program.label}</option>`);
          });
        } else {
          programSelect.append('<option value="" class="CustomOption" disabled>No programs available</option>');
          editProgramSelect.append('<option value="" class="CustomOption" disabled>No programs available</option>');
        }

        if (response.programs && response.programs.length > 0) {
          programFilter.append('<option value="" class="CustomOption" selected>All Programs</option>');
          response.programs.forEach((program) => {
            const isSelected = response.selectedProgram === program.uuid ? "selected" : "";
            programFilter.append(`<option class="CustomOption" value="${program.uuid}" ${isSelected}>${program.label}</option>`);
          });
        } else {
          programFilter.append('<option value="" class="CustomOption" disabled>No programs available</option>');
        }

        const yearlvlFilter = $("#yearlvlFilter");
        if (response.selectedYearLevel) {
          yearlvlFilter.val(response.selectedYearLevel);
        } else {
          yearlvlFilter.val("");
        }

        const searchInput = $("#searchInput");
        if (response.searchQuery) {
          searchInput.val(response.searchQuery);
        } else {
          searchInput.val("");
        }

        const clearFiltersBtn = $("#clearFiltersBtn");
        if (response.selectedProgram || response.selectedYearLevel || response.selectedStatus || response.searchQuery) {
          clearFiltersBtn.removeClass("d-none");
        } else {
          clearFiltersBtn.addClass("d-none");
        }

        if (response.total > 0) {
          response.students.forEach((student) => {
            if (!studentsStatuses.includes(student.account_status)) {
              studentsStatuses.push(student.account_status);
            }

            const getStatusBadgeClass = (accountStatus, isActive) => {
              if (!isActive) return "bg-secondary-subtle text-secondary";
              switch (accountStatus) {
                case "active":
                  return "bg-success-subtle text-success";
                case "inactive":
                  return "bg-warning-subtle text-warning";
                case "never_logged_in":
                  return "bg-info-subtle text-info";
                case "suspended":
                  return "bg-danger-subtle text-danger";
                default:
                  return "bg-secondary-subtle text-secondary";
              }
            };

            if (student.profile_name === null || student.profile_name === "") {
              student.profile_name = `https://placehold.co/64x64/483a0f/c6983d/png?text=${student.initials}&font=poppins`;
            } else {
              student.profile_name = "../../../Assets/Images/profiles/" + student.profile_name;
            }

            const studentRow = `
            <tr style="cursor: pointer;">
                <td colspan="1" class="bg-blur-5 bg-semi-transparent border-0">
                    <div class="hstack">
                        <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0" style="width: 40px; height: 40px; min-width: 40px;">
                            <img src="${student.profile_name}" alt="Profile Picture" class="img-fluid">
                        </div>
                        <div class="ms-3">
                            <p class="mb-0 fw-bold">${student.full_name}</p>
                                <small class="mb-0 text-muted" style="font-size: 0.80rem;">${student.email} &bull; ${student.program_code}-${student.year_level}${student.section}</small>
                        </div>
                    </div>
                </td>
                <td class="py-3 d-none d-md-table-cell bg-blur-5 bg-semi-transparent border-0">${student.student_number}</td>
                <td class="py-3 d-none d-lg-table-cell bg-blur-5 bg-semi-transparent border-0">${student.program_name}</td>
                <td class="py-3 text-center d-none d-xl-table-cell bg-blur-5 bg-semi-transparent border-0">${student.year_label}</td>
                <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">${student.coordinator_name}</td>
                <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">
                    <span class="badge rounded-pill bg-opacity-10 ${getStatusBadgeClass(student.account_status, student.is_active)}">${student.status_label}</span>
                </td>
                <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">
                    <div class="position-relative">
                        <button class="btn btn-sm border-0 py-1 px-2 rounded-3" type="button" data-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="customDropdown bg-blur-10 rounded-4 border bg-semi-transparent m-0 p-0" style="--blur-lvl: .75; display: none; position: absolute; top: 100%; right: 32px; min-width: 170px; list-style: none; z-index: 2000;">
                            <li>
                                <span class="dropdown-item-text text-nowrap text-body p-2">
                                    <img src="${student.profile_name}" alt="Profile Picture" class="img-fluid rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
                                    <small class="me-2">${student.full_name}</small>
                                </span>
                            </li>
                            <li><button class="dropdown-item text-body py-2 px-3 js-student-action-btn" data-action="view" data-profile-uuid="${student.profile_uuid}" data-user-uuid="${student.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-eye me-2"></i>View details</button></li>
                            <li><button class="dropdown-item text-body py-2 px-3 js-student-action-btn" data-action="edit" data-profile-uuid="${student.profile_uuid}" data-user-uuid="${student.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-pencil-square me-2"></i>Edit details</button></li>
                            <li><button class="dropdown-item text-danger py-2 px-3 ${student.is_active ? "" : "d-none"} js-student-action-btn" data-action="deactivate" data-profile-uuid="${student.profile_uuid}" data-user-uuid="${student.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-x-octagon-fill me-2"></i>Deactivate Account</button></li>
                            <li><button class="dropdown-item text-success py-2 px-3 ${!student.is_active ? "" : "d-none"} js-student-action-btn" data-action="activate" data-profile-uuid="${student.profile_uuid}" data-user-uuid="${student.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-check-circle me-2"></i>Activate Account</button></li>
                        </ul>
                    </div>
                </td>
            </tr>`;
            studentsTable.append(studentRow);
          });

          const statusFilter = $("#statusFilter");
          statusFilter.empty();
          statusFilter.append('<option value="" class="CustomOption" selected>All Statuses</option>');
          studentsStatuses.forEach((status) => {
            const isSelected = response.selectedStatus === status ? "selected" : "";
            const statusLabel = studentsStatusLabels[status] || status;
            statusFilter.append(`<option class="CustomOption" value="${status}" ${isSelected}>${statusLabel}</option>`);
          });
        } else {
          studentsTable.html(emptyRow);
        }
      } else if (response.status === "critical") {
        studentsTable.html(emptyRow);
        ToastVersion(swalTheme.response.Details, "error", 5000, "top-end", "8");
      } else {
        studentsTable.html(emptyRow);
        ToastVersion(swalTheme.response.message, "warning", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      studentsTable.html(emptyRow);
      Errors(xhr, status, error);
    },
  });
}

function viewStudentDetails(studentUuid) {
  if (!studentUuid) {
    ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/students/get_student",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: studentUuid,
    },
    success: function (response) {
      if (response.status === "success") {
        $("#ViewStudentModal").modal("show");

        let BadgeStatusClass = {
          active: "bg-success-subtle text-success",
          inactive: "bg-warning-subtle text-warning",
          never_logged_in: "bg-info-subtle text-info",
          suspended: "bg-danger-subtle text-danger",
          unknown: "bg-secondary-subtle text-secondary",
        };

        if (response.student.profile_name === null || response.student.profile_name === "") {
          response.student.profile_name = `https://placehold.co/64x64/483a0f/c6983d/png?text=${response.student.initials}&font=poppins`;
        } else {
          response.student.profile_name = "../../../Assets/Images/profiles/" + response.student.profile_name;
        }

        $("#viewStudentProfilePic")
          .attr("src", response.student.profile_name)
          .attr("alt", response.student.full_name + " Profile Picture");

        $("#viewStudentFullName").text(response.student.full_name);
        $("#viewStudentNumber").text(response.student.student_number);
        $("#deactivateStudentBtn").toggleClass("d-none", response.student.is_active !== 1);
        $("#activateStudentBtn").toggleClass("d-none", response.student.is_active === 1);
        $("#viewStudentEmail").text(response.student.email);
        $("#viewStudentFullName2").text(response.student.full_name);
        $("#viewStudentMobile").text(response.student.mobile);
        $("#viewStudentHomeAddress").text(response.student.home_address);
        $("#viewStudentEmergencyContact").text(response.student.emergency_contact);
        $("#viewStudentEmergencyPhone").text(response.student.emergency_phone);
        $("#viewStudentLastLogin").text(response.student.last_login ? response.student.last_login : "Never logged in");
        $("#viewStudentStudentNo").text(response.student.student_number);
        $("#viewStudentProgram").text(response.student.program_name);
        $("#viewStudentYearSection").text(`${response.student.year_label} - Section ${response.student.section}`);
        $("#viewStudentDepartment").text(response.student.department);
        $("#viewStudentCoordinator").text(response.student.coordinator_name);
        $("#viewStudentBatch").text(response.student.batch_label);
        $("#viewStudentRequiredHours").text(response.student.required_hours);
        $("#viewStudentStatus").text(response.student.status_label).removeClass().addClass(`badge rounded-pill ${BadgeStatusClass[response.student.account_status]}`);

        $("#deactivateStudentBtn")
          .off("click")
          .on("click", function () {
            changeStudentStatus(response.student.user_uuid, false);
          });

        $("#activateStudentBtn")
          .off("click")
          .on("click", function () {
            changeStudentStatus(response.student.user_uuid, true);
          });

        $("#editStudentFromViewBtn")
          .off("click")
          .on("click", function () {
            $("#ViewStudentModal").modal("hide");
            editStudentDetails(response.student.profile_uuid);
          });

        $("#changePasswordBtn")
          .off("click")
          .on("click", function () {
            $("#ViewStudentModal").modal("hide");
            resetStudentPassword(response.student.user_uuid, response.student.full_name, response.student.profile_uuid);
          });
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function editStudentDetails(studentUuid) {
  if (!studentUuid) {
    ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/students/get_student",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: studentUuid,
    },
    success: function (response) {
      if (response.status === "success") {
        $("#editStudentFullName").text(response.student.full_name);
        $("#editStudentNumberDisplay").text(response.student.student_number);
        $("#editStudentEmail").text(response.student.email);
        $("#editStudentNumber").text(response.student.student_number);

        $("#EditStudentModal").modal("show");

        $("#editLastName").val(response.student.last_name);
        $("#editFirstName").val(response.student.first_name);
        $("#editMiddleName").val(response.student.middle_name);
        $("#editMobileNumber").val(response.student.mobile);
        $("#editAddress").val(response.student.home_address);
        $("#editEmergencyContact").val(response.student.emergency_contact);
        $("#editEmergencyContactNumber").val(response.student.emergency_phone);
        $("#editProgramSelect").val(response.student.program_uuid);
        $("#editYearLevelSelect").val(response.student.year_level);
        $("#editSection").val(response.student.section);
        $("#editCoordinatorSelect").val(response.student.coordinator_uuid);

        $("#editStudentBtn").attr("data-profile-uuid", studentUuid);

        $("#EditStudentModal").on("hidden.bs.modal", function () {
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
          $("#editCoordinatorSelect").val("");
        });
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function changeStudentStatus(studentUuid, activate = true) {
  if (!studentUuid) {
    ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
    return;
  }

  let action = activate ? "reactivate" : "deactivate";
  let confirmText = activate ? "Are you sure you want to activate this student's account?" : "Are you sure you want to deactivate this student's account?";

  swal
    .fire({
      title: activate ? "Activate Account" : "Deactivate Account",
      text: confirmText,
      icon: activate ? "success" : "warning",
      showCancelButton: true,
      confirmButtonText: activate ? "Yes, Activate" : "Yes, Deactivate",
      cancelButtonText: "Cancel",
      reverseButtons: true,
      theme: swalTheme,
      customClass: {
        popup: "bg-blur-5 bg-semi-transparent border-1 rounded-2",
        container: "overflow-hidden",
        confirmButton: `btn ${activate ? "btn-success" : "btn-danger"} py-2 px-3 rounded-3`,
        cancelButton: "btn btn-secondary py-2 px-3 rounded-3",
      },
    })
    .then((result) => {
      if (result.isConfirmed) {
        $.ajax({
          url: `../../../process/students/deactivate_student`,
          method: "POST",
          dataType: "json",
          data: {
            csrf_token: csrfToken,
            user_uuid: studentUuid,
            action: action,
          },
          success: function (response) {
            if (response.status === "success") {
              ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
              getStudents();
              $(".modal").modal("hide");
            } else if (response.status === "critical") {
              ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
            } else {
              ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
            }
          },
          error: function (xhr, status, error) {
            Errors(xhr, status, error);
          },
        });
      }
    });
}

function createStudent() {
  const studentEmail = $.trim($("#studentEmail").val());
  const studentNumber = $.trim($("#studentNumber").val());
  const lastName = $.trim($("#lastName").val());
  const firstName = $.trim($("#firstName").val());
  const middleName = $.trim($("#middleName").val());
  const mobileNumber = $.trim($("#mobileNumber").val());
  const address = $.trim($("#address").val());
  const emergencyContact = $.trim($("#emergencyContact").val());
  const emergencyContactNumber = $.trim($("#emergencyContactNumber").val());
  const programSelect = $("#programSelect");
  const yearLevelSelect = $("#yearLevelSelect");
  const sectionSelect = $("#section");
  const coordinatorSelect = $("#coordinatorSelect");
  const activeBatch = $("#activeBatch").val();

  const programUuid = programSelect.val();
  const yearLevel = yearLevelSelect.val();
  const section = sectionSelect.val();
  const coordinatorUuid = coordinatorSelect.val();

  if (!studentEmail || !studentNumber || !lastName || !firstName || !programUuid || !yearLevel || !section || !coordinatorUuid || !emergencyContact || !emergencyContactNumber) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/students/create_student",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      email: studentEmail,
      student_number: studentNumber,
      last_name: lastName,
      first_name: firstName,
      middle_name: middleName,
      mobile: mobileNumber,
      emergency_contact: emergencyContact,
      emergency_phone: emergencyContactNumber,
      home_address: address,
      program_uuid: programUuid,
      year_level: yearLevel,
      section: section,
      coordinator_uuid: coordinatorUuid,
      batch_uuid: activeBatch,
    },
    beforeSend: function () {
      $("#createStudentBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
    },
    success: function (response) {
      $("#createStudentBtn").prop("disabled", false).html("Create");
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        getStudents();

        $("#studentEmail").val("");
        $("#studentNumber").val("");
        $("#lastName").val("");
        $("#firstName").val("");
        $("#middleName").val("");
        $("#mobileNumber").val("");
        $("#address").val("");
        $("#emergencyContact").val("");
        $("#emergencyContactNumber").val("");
        $("#programSelect").val("");
        $("#yearLevelSelect").val("");
        $("#section").val("");
        $("#coordinatorSelect").val("");

        $("#CreateStudentModal").modal("hide");

        $("#StudentCreatedModal").modal("show");
        $("#createdStudentName").text(response.full_name);
        $("#createdStudentTempPassword").text(response.temp_password);
        $("#StudentCreatedModal").attr("data-Student-Uuid", response.profile_uuid);
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#createStudentBtn").prop("disabled", false).html("Create");
      Errors(xhr, status, error);
    },
  });
}

function SaveEditedStudent(uuid) {
  if (!uuid) {
    ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
    return;
  }

  const lastName = $.trim($("#editLastName").val());
  const firstName = $.trim($("#editFirstName").val());
  const middleName = $.trim($("#editMiddleName").val());
  const mobileNumber = $.trim($("#editMobileNumber").val());
  const address = $.trim($("#editAddress").val());
  const emergencyContact = $.trim($("#editEmergencyContact").val());
  const emergencyContactNumber = $.trim($("#editEmergencyContactNumber").val());
  const programUuid = $("#editProgramSelect").val();
  const yearLevel = $("#editYearLevelSelect").val();
  const section = $("#editSection").val();
  const coordinatorUuid = $("#editCoordinatorSelect").val();

  if (!lastName || !firstName || !programUuid || !yearLevel || !section || !coordinatorUuid || !emergencyContact || !emergencyContactNumber) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/students/update_student",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: uuid,
      last_name: lastName,
      first_name: firstName,
      middle_name: middleName,
      mobile: mobileNumber,
      home_address: address,
      emergency_contact: emergencyContact,
      emergency_phone: emergencyContactNumber,
      program_uuid: programUuid,
      year_level: yearLevel,
      section: section,
      coordinator_uuid: coordinatorUuid,
    },
    beforeSend: function () {
      $("#editStudentBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    },
    success: function (response) {
      $("#editStudentBtn").prop("disabled", false).html("<span class='bi bi-check-lg me-2'></span>Save Changes");
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        getStudents();
        $("#EditStudentModal").modal("hide");
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#editStudentBtn").prop("disabled", false).html("<span class='bi bi-check-lg me-2'></span>Save Changes");
      Errors(xhr, status, error);
    },
  });
}

function resetStudentPassword(studentUuid, name = "", returnUUID = "") {
  if (!studentUuid) {
    ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
    return;
  }

  $("#ResetPasswordModal").modal("show");
  $("#resetPasswordStudentName").text(name);

  $("#cancelResetPasswordBtn")
    .off("click")
    .on("click", function () {
      $("#ResetPasswordModal").modal("hide");
      if (name) {
        viewStudentDetails(returnUUID);
      }
    });

  $("#resetPasswordBtn")
    .off("click")
    .on("click", function () {
      $.ajax({
        url: "../../../process/students/reset_student_password",
        method: "POST",
        dataType: "json",
        data: {
          csrf_token: csrfToken,
          user_uuid: studentUuid,
        },
        beforeSend: function () {
          $("#resetPasswordBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Resetting...');
        },
        success: function (response) {
          $("#resetPasswordBtn").prop("disabled", false).html("Reset Password");
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            $("#ResetPasswordModal").modal("hide");

            $("#ResetPasswordSuccessModal").modal("show");

            $("#resetPasswordSuccessStudentName").text(name);
            $("#resetPasswordSuccessTempPassword").text(response.temp_password);
            $("#exportResetPdfBtn")
              .off("click")
              .on("click", function () {
                const tempPassword = response.temp_password;
                const fullName = name;
                if (!tempPassword || !fullName) {
                  ToastVersion(swalTheme, "Missing data for PDF export.", "error", 3000, "top-end");
                  return;
                }

                const exportData = {
                  full_name: fullName,
                  temp_password: tempPassword,
                };

                $.ajax({
                  url: "../../../process/students/export_reset_password_pdf",
                  method: "POST",
                  data: {
                    csrf_token: csrfToken,
                    student_data: JSON.stringify(exportData),
                  },
                  xhrFields: {
                    responseType: "blob",
                  },
                  beforeSend: function () {
                    ModalVersion(swalTheme, "Generating PDF...", "Please wait while we generate the reset password details PDF.", "info", 0, "center");
                  },
                  success: function (pdfResponse, _status, xhr) {
                    swal.close();
                    const contentType = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();
                    if (contentType.includes("application/json")) {
                      const reader = new FileReader();
                      reader.onload = function () {
                        try {
                          const json = JSON.parse(String(reader.result || "{}"));
                          ToastVersion(swalTheme, json.message || "Failed to generate PDF.", "warning", 3500, "top-end");
                        } catch {
                          ToastVersion(swalTheme, "Unexpected server response while generating PDF.", "error", 3500, "top-end");
                        }
                      };
                      reader.readAsText(pdfResponse);
                      return;
                    }
                    const contentDisposition = xhr.getResponseHeader("Content-Disposition") || "";
                    const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
                    const fileNameFromHeader = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "";
                    const blob = pdfResponse instanceof Blob ? pdfResponse : new Blob([pdfResponse], { type: "application/pdf" });
                    const fileName = fileNameFromHeader || `${fullName.replace(/\s+/g, "_")}_Reset_Password_Details.pdf`;
                    const blobUrl = window.URL.createObjectURL(blob);
                    const link = document.createElement("a");
                    link.href = blobUrl;
                    link.download = fileName;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(blobUrl);
                    $("#ResetPasswordSuccessModal").modal("hide");
                    viewStudentDetails(returnUUID);
                  },
                  error: function (xhr, status, error) {
                    swal.close();
                    Errors(xhr, status, error);
                  },
                });
              });
          } else if (response.status === "critical") {
            ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
          } else {
            ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
          }
        },
        error: function (xhr, status, error) {
          $("#resetPasswordBtn").prop("disabled", false).html("Reset Password");
          Errors(xhr, status, error);
        },
      });
    });
}

function showTemplateDownloadLoading() {
  swal.fire({
    title: "Preparing download...",
    text: "Please wait while we prepare the student template.",
    icon: "info",
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    backdrop: true,
    customClass: {
      popup: "bg-blur-5 bg-semi-transparent border-1 rounded-2",
      container: "overflow-hidden",
    },
    didOpen: () => {
      swal.showLoading();
    },
  });
}

function closeTemplateDownloadLoading() {
  if (swal.isVisible()) {
    swal.close();
  }
}

async function saveTemplateBlob(blob, fileName) {
  if (window.showSaveFilePicker) {
    const fileHandle = await window.showSaveFilePicker({
      suggestedName: fileName,
      types: [
        {
          description: "CSV file",
          accept: {
            "text/csv": [".csv"],
          },
        },
      ],
    });

    const writable = await fileHandle.createWritable();
    try {
      await writable.write(blob);
    } finally {
      await writable.close();
    }

    return true;
  }

  const blobUrl = window.URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.href = blobUrl;
  link.download = fileName;
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  window.URL.revokeObjectURL(blobUrl);
  return true;
}

function updateFileUploadState(file = null) {
  const $label = $("#fileUploadLabel");
  const $icon = $("#fileUploadIcon");
  const $title = $("#fileUploadTitle");
  const $hint = $("#fileUploadHint");
  const $fileName = $("#selectedFileName");
  const $validateBtn = $("#validatePreviewBtn");

  if (!file) {
    $label
      .removeClass("border-primary bg-primary bg-opacity-10")
      .addClass("border-success");
    $icon.removeClass("bi-check-circle-fill text-success").addClass("bi-file-earmark-arrow-up text-muted");
    $title.text("Click to upload or drag and drop your file here");
    $hint.text("CSV or Excel (.xlsx) • Max 500 rows • Max file size 5MB");
    $fileName.addClass("d-none").text("");
    $validateBtn
      .prop("disabled", true)
      .removeClass("btn-primary")
      .addClass("btn-secondary")
      .html('<i class="bi bi-check-circle me-2"></i>Validate &amp; Preview');
    return;
  }

  const fileSizeKb = Math.max(1, Math.round(file.size / 1024));
  $label
    .removeClass("border-success")
    .addClass("border-primary bg-primary bg-opacity-10");
  $icon.removeClass("bi-file-earmark-arrow-up text-muted").addClass("bi-check-circle-fill text-success");
  $title.text("File selected. Ready to validate.");
  $hint.text("You can click again to replace the selected file.");
  $fileName.removeClass("d-none").text(`${file.name} • ${fileSizeKb} KB`);
  $validateBtn
    .prop("disabled", false)
    .removeClass("btn-secondary")
    .addClass("btn-primary");
}

function resetBulkUploadState() {
  $("#fileUploadInput").val("");
  updateFileUploadState(null);
}

function resetValidationPreviewState() {
  $("#validCount").text("0");
  $("#validCountLabel").text("0");
  $("#invalidCount").text("0");
  $("#TotalCount").text("0");
  $("#validationErrorsText").text("Validation results will be shown after file upload and checking.");
  $("#validationErrorsAlert").addClass("d-none");
  $("#validationResultsTable tbody").html(`
    <tr>
      <td colspan="9" class="text-center py-4 text-muted">
        <i class="bi bi-table me-2"></i>Validation results will appear here after upload.
      </td>
    </tr>
  `);
}

function escapeHtml(value = "") {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function validateAndPreviewBulkFile() {
  const fileInput = $("#fileUploadInput");
  const file = fileInput[0]?.files[0];

  if (!file) {
    ToastVersion(swalTheme, "Please select a file to upload.", "warning", 3000, "top-end");
    return;
  }

  const formData = new FormData();
  formData.append("csrf_token", csrfToken);
  formData.append("bulk_file", file);

  if ($("#coordinatorSelect").length) {
    const coordinatorUuid = $("#coordinatorSelect").val();
    if (coordinatorUuid) {
      formData.append("coordinator_uuid", coordinatorUuid);
    }
  }

  ModalVersion(swalTheme, "Validating file...", "Please wait while we validate your file.", "info", 0, "center");

  $.ajax({
    url: "../../../process/students/bulk_validate",
    method: "POST",
    data: formData,
    contentType: false,
    processData: false,
    dataType: "json",
    success: function (response) {
      swal.close();

      if (response.status === "success") {
        populateValidationPreview(response);
        $("#bulkCreationModal").modal("hide");
        $("#validatePreviewModal").modal("show");
      } else {
        ToastVersion(swalTheme, response.message || "Validation failed.", "warning", 3500, "top-end");
      }
    },
    error: function (xhr, status, error) {
      swal.close();
      Errors(xhr, status, error);
    },
  });
}

function populateValidationPreview(validationData) {
  const { valid_rows, error_rows, valid_count, error_count, total } = validationData;

  $("#validCount").text(valid_count);
  $("#validCountLabel").text(valid_count);
  $("#invalidCount").text(error_count);
  $("#TotalCount").text(total);

  if (error_count > 0) {
    const errorMsg = `${error_count} row(s) have errors and will be skipped. Fix the file and re-upload to include them, or proceed with the ${valid_count} valid rows only.`;
    $("#validationErrorsText").text(errorMsg);
    $("#validationErrorsAlert").removeClass("d-none");
  } else {
    $("#validationErrorsAlert").addClass("d-none");
  }

  const tableBody = $("#validationResultsTable tbody");
  tableBody.empty();

  const renderValidRow = (row) => {
    const safeName = escapeHtml(row.full_name || "—");
    const safeEmail = escapeHtml(row.email || "—");
    const safeStudentNo = escapeHtml(row.student_number || "—");
    const safeProgram = escapeHtml(row.program_code || "—");
    const safeYear = escapeHtml(row.year_level || "—");
    const safeCoordinator = escapeHtml(row.coordinator_name || "—");
    const safeRowNum = escapeHtml(row.row_num || "—");

    return `
      <tr class="align-top">
        <td class="d-none d-sm-table-cell fw-semibold">${safeRowNum}</td>
        <td>
          <div class="fw-semibold text-body">${safeName}</div>
        </td>
        <td class="d-none d-sm-table-cell text-muted">${safeEmail}</td>
        <td class="d-none d-sm-table-cell">${safeStudentNo}</td>
        <td class="d-none d-sm-table-cell">${safeProgram}</td>
        <td class="d-none d-sm-table-cell">${safeYear}</td>
        <td class="d-none d-md-table-cell">${safeCoordinator}</td>
        <td><span class="badge bg-success-subtle text-success border border-success-subtle">Valid</span></td>
        <td class="text-muted">—</td>
      </tr>
    `;
  };

  const renderErrorRow = (row) => {
    const safeName = escapeHtml(row.full_name || "—");
    const safeEmail = escapeHtml(row.email || "—");
    const safeStudentNo = escapeHtml(row.student_number || "—");
    const safeProgram = escapeHtml(row.program_code || "—");
    const safeYear = escapeHtml(row.year_level || "—");
    const safeCoordinator = escapeHtml(row.coordinator_name || "—");
    const safeRowNum = escapeHtml(row.row_num || "—");
    const errors = Array.isArray(row.errors) && row.errors.length > 0 ? row.errors : ["Unknown error"];
    const visibleErrors = errors.slice(0, 2);
    const hiddenErrors = errors.slice(2);
    const visibleList = visibleErrors.map((err) => `<li>${escapeHtml(err)}</li>`).join("");
    const hiddenList = hiddenErrors.map((err) => `<li>${escapeHtml(err)}</li>`).join("");
    const hasHiddenErrors = hiddenErrors.length > 0;

    return `
      <tr class="align-top border-start border-3 border-danger-subtle">
        <td class="d-none d-sm-table-cell fw-semibold">${safeRowNum}</td>
        <td>
          <div class="fw-semibold text-body">${safeName}</div>
        </td>
        <td class="d-none d-sm-table-cell text-muted">${safeEmail}</td>
        <td class="d-none d-sm-table-cell">${safeStudentNo}</td>
        <td class="d-none d-sm-table-cell">${safeProgram}</td>
        <td class="d-none d-sm-table-cell">${safeYear}</td>
        <td class="d-none d-md-table-cell">${safeCoordinator}</td>
        <td><span class="badge bg-danger-subtle text-danger border border-danger-subtle">Invalid</span></td>
        <td>
          <ul class="mb-0 ps-3 small text-danger-emphasis">
            ${visibleList}
          </ul>
          ${
            hasHiddenErrors
              ? `
            <ul class="mb-0 ps-3 small text-danger-emphasis d-none js-more-errors">
              ${hiddenList}
            </ul>
            <button type="button" class="btn btn-link btn-sm p-0 mt-1 text-decoration-none js-toggle-errors">
              Show more (${hiddenErrors.length})
            </button>
          `
              : ""
          }
        </td>
      </tr>
    `;
  };

  if (total === 0) {
    tableBody.append(`
      <tr>
        <td colspan="9" class="text-center py-4 text-muted">No rows found in file.</td>
      </tr>
    `);
    return;
  }

  valid_rows.forEach((row) => tableBody.append(renderValidRow(row)));
  error_rows.forEach((row) => tableBody.append(renderErrorRow(row)));
}

function populateCreatedAccountsTable(createdAccounts = []) {
  const $tableBody = $("#createdAccountsTable tbody");
  $tableBody.empty();

  if (!Array.isArray(createdAccounts) || createdAccounts.length === 0) {
    $tableBody.append(`
      <tr>
        <td colspan="5" class="text-center py-4 text-muted">No created accounts to display.</td>
      </tr>
    `);
    return;
  }

  createdAccounts.forEach((student) => {
    const safeName = escapeHtml(student.full_name || "—");
    const safeEmail = escapeHtml(student.email || "—");
    const safeStudentNo = escapeHtml(student.student_number || "—");
    const safeProgram = escapeHtml(`${student.program_code || "—"} ${student.year_label || ""}`.trim());
    const safePassword = escapeHtml(student.temp_password || "—");

    $tableBody.append(`
      <tr>
        <td class="fw-semibold">${safeName}</td>
        <td class="d-none d-sm-table-cell">${safeEmail}</td>
        <td class="d-none d-sm-table-cell">${safeStudentNo}</td>
        <td class="d-none d-sm-table-cell">${safeProgram}</td>
        <td class="d-none d-sm-table-cell text-success fw-semibold">${safePassword}</td>
      </tr>
    `);
  });
}

function normalizeFailureReason(failure = {}) {
  const rawReason = failure.reason;
  if (Array.isArray(rawReason)) {
    return rawReason.filter(Boolean).join("; ") || "Unknown error";
  }
  return String(rawReason || "Unknown error");
}

function populateBulkCreationSummary(createdCount = 0, failedCount = 0, failedRows = []) {
  $("#successCreatedCount").text(createdCount);
  $("#successFailedCount").text(failedCount);

  const $failedContainer = $("#failedRowsContainer");
  const $failedDetails = $("#failedRowsDetails");
  const $failedBody = $("#failedRowsTableBody");
  const $toggleBtn = $("#toggleFailedRowsBtn");

  $failedBody.empty();
  $failedDetails.addClass("d-none");
  $toggleBtn.text("Show details");

  if (!Array.isArray(failedRows) || failedRows.length === 0) {
    $failedContainer.addClass("d-none");
    return;
  }

  failedRows.forEach((row) => {
    const safeName = escapeHtml(row.full_name || row.name || "—");
    const safeEmail = escapeHtml(row.email || "—");
    const safeStudentNo = escapeHtml(row.student_number || "—");
    const safeReason = escapeHtml(normalizeFailureReason(row));

    $failedBody.append(`
      <tr>
        <td class="fw-semibold">${safeName}</td>
        <td class="d-none d-sm-table-cell">${safeEmail}</td>
        <td class="d-none d-sm-table-cell">${safeStudentNo}</td>
        <td class="text-danger-emphasis">${safeReason}</td>
      </tr>
    `);
  });

  $failedContainer.removeClass("d-none");
}

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

function createBulkAccounts() {
  const validCount = Number.parseInt($("#validCount").text(), 10) || 0;
  if (validCount <= 0) {
    ToastVersion(swalTheme, "No valid rows to create. Please fix the file and validate again.", "warning", 3500, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/students/bulk_create",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      $("#createAccountsBtn")
        .prop("disabled", true)
        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
      ModalVersion(swalTheme, "Creating accounts...", "Please wait while we create student accounts.", "info", 0, "center");
    },
    success: function (response) {
      swal.close();
      $("#createAccountsBtn")
        .prop("disabled", false)
        .html('<i class="bi bi-check-circle me-2"></i>Create <span id="validCountLabel">0</span> Accounts');
      $("#validCountLabel").text($("#validCount").text());

      if (response.status === "success") {
        const createdCount = response.created_count || 0;
        const failedCount = response.failed_count || 0;
        const createdRows = response.created || [];
        const failedRows = response.failed || [];
        const currentBatchLabel =
          $("#activeBatchLabel").attr("data-batch-label") ||
          $("#activeBatchLabel")
            .text()
            .replace(/^Batch:\s*/i, "")
            .trim() ||
          "Current Batch";
        $("#accountsCreatedCount").text(createdCount);
        $("#batchlabelCurrent").text(currentBatchLabel);
        populateCreatedAccountsTable(createdRows);
        populateBulkCreationSummary(createdCount, failedCount, failedRows);

        $("#validatePreviewModal").modal("hide");
        $("#bulkSuccessModal").modal("show");

        ToastVersion(swalTheme, response.message || "Accounts created successfully.", "success", 3000, "top-end");
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.details || response.message || "Critical error occurred.", "error", 5000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message || "Failed to create accounts.", "warning", 3500, "top-end");
      }
    },
    error: function (xhr, status, error) {
      swal.close();
      $("#createAccountsBtn")
        .prop("disabled", false)
        .html('<i class="bi bi-check-circle me-2"></i>Create <span id="validCountLabel">0</span> Accounts');
      $("#validCountLabel").text($("#validCount").text());
      Errors(xhr, status, error);
    },
  });
}

async function downloadBulkCredentialsCsv() {
  try {
    const response = await fetch("../../../process/students/bulk_export_csv", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({ csrf_token: csrfToken }),
    });

    if (!response.ok) {
      const text = await response.text();
      ToastVersion(swalTheme, text || "Failed to export CSV credentials.", "error", 3500, "top-end");
      return;
    }

    const fileName = getDownloadFileNameFromResponse(response, "bulk_created_accounts.csv");
    const blob = await response.blob();
    downloadBlobFile(blob, fileName);
  } catch (error) {
    Errors({ status: 0 }, "error", error?.message || error);
  }
}

async function downloadBulkCredentialsPdf() {
  try {
    const response = await fetch("../../../process/students/bulk_export_pdf", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams({ csrf_token: csrfToken }),
    });

    const contentType = (response.headers.get("Content-Type") || "").toLowerCase();
    if (!response.ok || contentType.includes("application/json")) {
      let message = "Failed to export PDF credentials.";
      try {
        const json = await response.json();
        message = json.message || message;
      } catch {
        const text = await response.text();
        message = text || message;
      }
      ToastVersion(swalTheme, message, "error", 3500, "top-end");
      return;
    }

    const fileName = getDownloadFileNameFromResponse(response, "bulk_created_accounts.pdf");
    const blob = await response.blob();
    downloadBlobFile(blob, fileName);
  } catch (error) {
    Errors({ status: 0 }, "error", error?.message || error);
  }
}

$(document).ready(function () {
  $(document)
    .off("click", ".js-student-action-btn")
    .on("click", ".js-student-action-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const profileUuid = $(this).data("profile-uuid");
      const studentUuid = $(this).data("user-uuid");

      const action = String($(this).data("action") || "").toLowerCase();

      switch (action) {
        case "view":
          viewStudentDetails(profileUuid);
          break;
        case "edit":
          editStudentDetails(profileUuid);
          break;
        case "deactivate":
          changeStudentStatus(studentUuid, false);
          break;
        case "activate":
          changeStudentStatus(studentUuid, true);
          break;
        default:
          ToastVersion(swalTheme, "Unknown action: " + action, "error", 3000, "top-end");
      }
    });

  getStudents();

  const hasSeenTour = localStorage.getItem(STUDENTS_TOUR_KEY) === "1";
  if (!hasSeenTour) {
    setTimeout(() => {
      startStudentsModuleTour();
    }, 500);
  }

  $("#programFilter, #yearlvlFilter, #statusFilter").change(function () {
    getStudents();
  });

  $("#searchInput").on("input", function () {
    clearTimeout($(this).data("searchTimeout"));
    $(this).data(
      "searchTimeout",
      setTimeout(function () {
        getStudents();
      }, 500),
    );
  });

  $("#clearFiltersBtn").click(function () {
    $("#programFilter").val("");
    $("#yearlvlFilter").val("");
    $("#statusFilter").val("");
    $("#searchInput").val("");
    getStudents();
  });

  $("#startStudentsTourLink").on("click", function (e) {
    e.preventDefault();
    startStudentsModuleTour();
  });

  $("#createStudentBtn").click(function () {
    createStudent();
  });

  $("#editStudentBtn").click(function () {
    const profileUuid = $(this).attr("data-profile-uuid");
    if (!profileUuid) {
      ToastVersion(swalTheme, "Invalid student selected.", "error", 3000, "top-end");
      return;
    }
    SaveEditedStudent(profileUuid);
  });

  $("#exportPdfBtn").click(function () {
    const StudentUUID = $("#StudentCreatedModal").attr("data-Student-Uuid");
    const fullName = $("#createdStudentName").text();
    const tempPassword = $("#createdStudentTempPassword").text();
    if (!StudentUUID) {
      ToastVersion(swalTheme, "No student data available for export.", "error", 3000, "top-end");
      return;
    }

    if (!fullName || !tempPassword) {
      ToastVersion(swalTheme, "Incomplete student data for export.", "error", 3000, "top-end");
      return;
    }

    $.ajax({
      url: "../../../process/students/get_student",
      method: "POST",
      dataType: "json",
      data: {
        csrf_token: csrfToken,
        profile_uuid: StudentUUID,
      },
      success: function (response) {
        if (response.status === "success") {
          const studentData = response.student;
          const exportData = {
            full_name: fullName,
            temp_password: tempPassword,
            student_number: studentData.student_number,
            email: studentData.email,
            program: studentData.program_name,
            year_level: studentData.year_label,
            section: studentData.section,
          };
          $.ajax({
            url: "../../../process/students/export_student_pdf",
            method: "POST",
            data: {
              csrf_token: csrfToken,
              student_data: JSON.stringify(exportData),
            },
            xhrFields: {
              responseType: "blob",
            },
            beforeSend: function () {
              ModalVersion(swalTheme, "Generating PDF...", "Please wait while we generate the student's account details PDF.", "info", 0, "center");
            },
            success: function (pdfResponse, _status, xhr) {
              swal.close();
              const contentType = (xhr.getResponseHeader("Content-Type") || "").toLowerCase();

              if (contentType.includes("application/json")) {
                const reader = new FileReader();
                reader.onload = function () {
                  try {
                    const json = JSON.parse(String(reader.result || "{}"));
                    ToastVersion(swalTheme, json.message || "Failed to generate PDF.", "warning", 3500, "top-end");
                  } catch {
                    ToastVersion(swalTheme, "Unexpected server response while generating PDF.", "error", 3500, "top-end");
                  }
                };
                reader.readAsText(pdfResponse);
                return;
              }

              const contentDisposition = xhr.getResponseHeader("Content-Disposition") || "";
              const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
              const fileNameFromHeader = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "";

              const blob = pdfResponse instanceof Blob ? pdfResponse : new Blob([pdfResponse], { type: "application/pdf" });
              const fileName = fileNameFromHeader || `${fullName.replace(/\s+/g, "_")}_Account_Details.pdf`;

              const blobUrl = window.URL.createObjectURL(blob);
              const link = document.createElement("a");
              link.href = blobUrl;
              link.download = fileName;
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
              window.URL.revokeObjectURL(blobUrl);
            },
            error: function (xhr, status, error) {
              swal.close();
              Errors(xhr, status, error);
            },
          });
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 5000, "top-end");
        } else {
          ToastVersion(swalTheme, response.message, "warning", 3000, "top-end");
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });

  $("#bulkimportBtn").click(function () {
    $(".modal").modal("hide");
    $("#bulkCreationModal").modal("show");
    resetBulkUploadState();
  });

  $("#fileUploadInput").on("change", function () {
    const selectedFile = this.files && this.files.length > 0 ? this.files[0] : null;
    updateFileUploadState(selectedFile);
  });

  $("#cancelBulkUploadBtn").click(function () {
    resetBulkUploadState();
    $("#bulkCreationModal").modal("hide");
  });

  $("#downloadTemplateBtn").click(function () {
    const $downloadButton = $(this);
    if ($downloadButton.prop("disabled")) {
      return;
    }

    (async () => {
      showTemplateDownloadLoading();
      $downloadButton
        .prop("disabled", true)
        .html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Downloading...');

      try {
        const response = await fetch("../../../process/students/bulk_download_template", {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "X-Requested-With": "XMLHttpRequest",
          },
          body: new URLSearchParams({ csrf_token: csrfToken }),
        });

        const contentType = (response.headers.get("Content-Type") || "").toLowerCase();

        if (!response.ok) {
          if (contentType.includes("application/json")) {
            const errorJson = await response.json();
            ToastVersion(swalTheme, errorJson.message || "Failed to download template.", "warning", 3500, "top-end");
          } else {
            ToastVersion(swalTheme, "Failed to download template.", "error", 3500, "top-end");
          }
          return;
        }

        if (contentType.includes("application/json")) {
          const errorJson = await response.json();
          ToastVersion(swalTheme, errorJson.message || "Failed to download template.", "warning", 3500, "top-end");
          return;
        }

        const contentDisposition = response.headers.get("Content-Disposition") || "";
        const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
        const fileNameFromHeader = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "";
        const fileName = fileNameFromHeader || "student_bulk_import_template.csv";
        const blob = await response.blob();

        try {
          await saveTemplateBlob(blob, fileName);
        } catch (downloadError) {
          if (downloadError && (downloadError.name === "AbortError" || downloadError.name === "NotAllowedError")) {
            ToastVersion(swalTheme, "Download cancelled.", "info", 2500, "top-end");
          } else {
            ToastVersion(swalTheme, downloadError?.message || "Failed to save the file.", "error", 3500, "top-end");
          }
        }
      } catch (error) {
        Errors({ status: 0 }, "error", error?.message || error);
      } finally {
        closeTemplateDownloadLoading();
        $downloadButton.prop("disabled", false).html("Download Template");
      }
    })();
  });

  $("#validatePreviewBtn").click(function () {
    validateAndPreviewBulkFile();
  });

  $(document).on("click", ".js-toggle-errors", function () {
    const $button = $(this);
    const $moreList = $button.siblings(".js-more-errors");
    const isHidden = $moreList.hasClass("d-none");

    if (isHidden) {
      $moreList.removeClass("d-none");
      $button.text("Show less");
    } else {
      $moreList.addClass("d-none");
      const hiddenCount = $moreList.find("li").length;
      $button.text(`Show more (${hiddenCount})`);
    }
  });

  $("#reuploadBtn").click(function () {
    resetValidationPreviewState();
    $("#validatePreviewModal").modal("hide");
    $("#bulkCreationModal").modal("show");
    resetBulkUploadState();
  });

  $("#reuploadFixedFileBtn").click(function () {
    const $fileInput = $("#fileUploadInput");

    // reset value so selecting the same file still triggers the change event
    $fileInput.val("");

    $fileInput
      .off("change.reuploadFixed")
      .one("change.reuploadFixed", function () {
        const selectedFile = this.files && this.files.length > 0 ? this.files[0] : null;

        if (!selectedFile) {
          return;
        }

        updateFileUploadState(selectedFile);
        resetValidationPreviewState();
        validateAndPreviewBulkFile();
      });

    $fileInput.trigger("click");
  });

  $("#createAccountsBtn").click(function () {
    createBulkAccounts();
  });

  $("#CsvCredentialsBtn").click(function () {
    downloadBulkCredentialsCsv();
  });

  $("#PdfCredentialsBtn").click(function () {
    downloadBulkCredentialsPdf();
  });

  $("#toggleFailedRowsBtn").click(function () {
    const $details = $("#failedRowsDetails");
    const isHidden = $details.hasClass("d-none");

    if (isHidden) {
      $details.removeClass("d-none");
      $(this).text("Hide details");
    } else {
      $details.addClass("d-none");
      $(this).text("Show details");
    }
  });

  $("#viewAllStudentsBtn").click(function () {
    $("#bulkSuccessModal").modal("hide");
    getStudents();
  });

  $("#importNewBatchBtn").click(function () {
    $("#bulkSuccessModal").modal("hide");
    resetValidationPreviewState();
    resetBulkUploadState();
    populateBulkCreationSummary(0, 0, []);
    populateCreatedAccountsTable([]);
    $("#bulkCreationModal").modal("show");
  });
});
