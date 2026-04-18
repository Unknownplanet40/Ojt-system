import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
let swalTheme = SwalTheme();

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
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
});
