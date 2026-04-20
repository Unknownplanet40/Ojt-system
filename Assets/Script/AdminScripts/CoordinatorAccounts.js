import { ToastVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

const driver = window.driver?.js?.driver;
let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const COORDINATOR_TOUR_KEY = "admin_coordinator_accounts_tour_done";

const loadingRow = `
<tr class="border-0">
  <td colspan="6" class="text-center py-4 bg-transparent border-0" style="cursor: wait;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>
      <div>
        <p class="mb-2 text-body fw-semibold">Loading coordinators...</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Please wait while we fetch coordinator accounts.</small>
      </div>
    </div>
  </td>
</tr>`;

const emptyRow = `
<tr class="border-0">
  <td colspan="6" class="text-center py-4 bg-transparent border-0" style="cursor: default;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
        <i class="bi bi-person-badge fa-lg"></i>
      </div>
      <div>
        <p class="mb-2 text-body fw-semibold">No coordinators found</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Click \"Add Coordinator\" to create a coordinator account.</small>
      </div>
    </div>
  </td>
</tr>`;

function getStatusBadgeClass(accountStatus, isActive) {
  if (!isActive) return "bg-secondary-subtle text-secondary";

  switch (accountStatus) {
    case "active":
      return "bg-success-subtle text-success";
    case "never_logged_in":
      return "bg-info-subtle text-info";
    case "inactive":
      return "bg-warning-subtle text-warning";
    default:
      return "bg-secondary-subtle text-secondary";
  }
}

function resetCreateCoordinatorForm() {
  $("#coordinatorEmail").val("");
  $("#coordinatorEmployeeId").val("");
  $("#coordinatorLastName").val("");
  $("#coordinatorFirstName").val("");
  $("#coordinatorMiddleName").val("");
  $("#coordinatorDepartment").val("");
  $("#coordinatorMobile").val("");
}

function exportCoordinatorCredentialsPdf(exportData, defaultFileName = "Coordinator_Account_Details.pdf", $triggerButton = null) {
  if (!exportData || !exportData.full_name || !exportData.temp_password) {
    ToastVersion(swalTheme, "Missing data for PDF export.", "error", 3000, "top-end");
    return;
  }

  const hasButton = $triggerButton && $triggerButton.length;
  const originalButtonHtml = hasButton ? $triggerButton.html() : "";

  if (hasButton) {
    if ($triggerButton.prop("disabled")) {
      return;
    }

    $triggerButton
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating PDF...');
  }

  ToastVersion(swalTheme, "Generating PDF, please wait...", "info", 1800, "top-end");

  $.ajax({
    url: "../../../process/coordinators/export_coordinator_pdf",
    method: "POST",
    data: {
      csrf_token: csrfToken,
      coordinator_data: JSON.stringify(exportData),
    },
    xhrFields: {
      responseType: "blob",
    },
    success: function (pdfResponse, _status, xhr) {
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
      const fileName = fileNameFromHeader || defaultFileName;

      const blobUrl = window.URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = blobUrl;
      link.download = fileName;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      window.URL.revokeObjectURL(blobUrl);

      ToastVersion(swalTheme, "Download started.", "success", 1800, "top-end");
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
    complete: function () {
      if (hasButton) {
        $triggerButton.prop("disabled", false).html(originalButtonHtml);
      }
    },
  });
}

function startCoordinatorAccountsTour() {
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
        element: "#dashboardContent",
        popover: {
          title: "Coordinator Accounts Module",
          description: "This page is where Admin manages coordinator accounts — create, edit, view, reset password, and activate/deactivate.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#addCoordinatorOpenBtn",
        popover: {
          title: "Add Coordinator",
          description: "Click here to create a new coordinator account with Employee ID, Department, and an auto-generated temporary password.",
          side: "left",
          align: "center",
        },
      },
      {
        element: "#coordinatorSearchInput",
        popover: {
          title: "Search",
          description: "Quickly find coordinators by name, email, or employee ID.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#departmentFilter",
        popover: {
          title: "Department Filter",
          description: "Narrow the list to a specific department.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#coordinatorStatusFilter",
        popover: {
          title: "Status Filter",
          description: "Filter by account status: Active, Never Logged In, or Inactive.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#coordinatorsTable",
        popover: {
          title: "Coordinator List",
          description: "Each row shows coordinator details, assigned student count, and account status.",
          side: "top",
          align: "center",
        },
      },
      {
        element: ".actionMenuCell",
        popover: {
          title: "Actions Menu",
          description: "Use the three-dot action menu per row to View details, Edit profile, or Deactivate/Reactivate account.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "#startCoordinatorTourLink",
        popover: {
          title: "Replay Tour Anytime",
          description: "Click this link whenever you want to walk through the module again.",
          side: "top",
          align: "start",
        },
      },
    ],
    onDestroyed: () => {
      localStorage.setItem(COORDINATOR_TOUR_KEY, "1");
    },
  });

  moduleTour.drive();
}

function getCoordinators() {
  const tableBody = $("#coordinatorsTable tbody");

  $.ajax({
    url: "../../../process/coordinators/get_coordinators",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      status: $("#coordinatorStatusFilter").val(),
      department: $("#departmentFilter").val(),
      search: $("#coordinatorSearchInput").val(),
    },
    beforeSend: function () {
      tableBody.html(loadingRow);
    },
    success: function (response) {
      if (response.status !== "success") {
        tableBody.html(emptyRow);
        ToastVersion(swalTheme, response.message || "Failed to fetch coordinators.", "warning", 3000, "top-end");
        return;
      }

      const coordinators = response.coordinators || [];
      $("#coordinatorCount").text(response.total || coordinators.length || 0);

      const departmentFilter = $("#departmentFilter");
      departmentFilter.empty().append('<option value="" class="CustomOption" selected>All Departments</option>');
      (response.departments || []).forEach((dept) => {
        const selected = response.selectedDepartment === dept ? "selected" : "";
        departmentFilter.append(`<option class="CustomOption" value="${dept}" ${selected}>${dept}</option>`);
      });

      $("#coordinatorStatusFilter").val(response.selectedStatus || "");
      $("#coordinatorSearchInput").val(response.searchQuery || "");

      if (response.selectedStatus || response.selectedDepartment || response.searchQuery) {
        $("#clearCoordinatorFiltersBtn").removeClass("d-none");
      } else {
        $("#clearCoordinatorFiltersBtn").addClass("d-none");
      }

      tableBody.empty();
      if (!coordinators.length) {
        tableBody.html(emptyRow);
        return;
      }

      coordinators.forEach((coordinator) => {
        const profileImage = coordinator.profile_name
          ? `../../../Assets/Images/profiles/${coordinator.profile_name}`
          : `https://placehold.co/64x64/483a0f/c6983d/png?text=${coordinator.initials}&font=poppins`;

        tableBody.append(`
          <tr>
            <td class="bg-blur-5 bg-semi-transparent border-0">
              <div class="hstack">
                <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0" style="width: 40px; height: 40px; min-width: 40px;">
                  <img src="${profileImage}" alt="Profile Picture" class="img-fluid">
                </div>
                <div class="ms-3">
                  <p class="mb-0 fw-bold">${coordinator.full_name}</p>
                  <small class="mb-0 text-muted" style="font-size: 0.80rem;">${coordinator.email}</small>
                </div>
              </div>
            </td>
            <td class="py-3 d-none d-md-table-cell bg-blur-5 bg-semi-transparent border-0">${coordinator.employee_id}</td>
            <td class="py-3 d-none d-lg-table-cell bg-blur-5 bg-semi-transparent border-0">${coordinator.department}</td>
            <td class="py-3 text-center d-none d-xl-table-cell bg-blur-5 bg-semi-transparent border-0">${coordinator.assigned_students}</td>
            <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">
              <span class="badge rounded-pill bg-opacity-10 ${getStatusBadgeClass(coordinator.account_status, coordinator.is_active)}">${coordinator.status_label}</span>
            </td>
            <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0 actionMenuCell">
              <div class="position-relative">
                <button class="btn btn-sm border-0 py-1 px-2 rounded-3" type="button" data-toggle="dropdown"><i class="bi bi-three-dots-vertical"></i></button>
                <ul class="customDropdown bg-blur-10 rounded-4 border bg-semi-transparent m-0 p-0" style="--blur-lvl: .75; display: none; position: absolute; top: 100%; right: 32px; min-width: 170px; list-style: none; z-index: 2000;">
                  <li><button class="dropdown-item text-body py-2 px-3 js-coordinator-action-btn" data-action="view" data-profile-uuid="${coordinator.profile_uuid}" data-user-uuid="${coordinator.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-eye me-2"></i>View details</button></li>
                  <li><button class="dropdown-item text-body py-2 px-3 js-coordinator-action-btn" data-action="edit" data-profile-uuid="${coordinator.profile_uuid}" data-user-uuid="${coordinator.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-pencil-square me-2"></i>Edit details</button></li>
                  <li><button class="dropdown-item text-danger py-2 px-3 ${coordinator.is_active ? "" : "d-none"} js-coordinator-action-btn" data-action="deactivate" data-profile-uuid="${coordinator.profile_uuid}" data-user-uuid="${coordinator.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-x-octagon-fill me-2"></i>Deactivate Account</button></li>
                  <li><button class="dropdown-item text-success py-2 px-3 ${!coordinator.is_active ? "" : "d-none"} js-coordinator-action-btn" data-action="activate" data-profile-uuid="${coordinator.profile_uuid}" data-user-uuid="${coordinator.user_uuid}" style="font-size: 0.875rem; width: 100%; text-align: left; cursor: pointer;"><i class="bi bi-check-circle me-2"></i>Activate Account</button></li>
                </ul>
              </div>
            </td>
          </tr>
        `);
      });
    },
    error: function (xhr, status, error) {
      tableBody.html(emptyRow);
      Errors(xhr, status, error);
    },
  });
}

function createCoordinator() {
  const payload = {
    csrf_token: csrfToken,
    email: $.trim($("#coordinatorEmail").val()),
    employee_id: $.trim($("#coordinatorEmployeeId").val()),
    last_name: $.trim($("#coordinatorLastName").val()),
    first_name: $.trim($("#coordinatorFirstName").val()),
    middle_name: $.trim($("#coordinatorMiddleName").val()),
    department: $.trim($("#coordinatorDepartment").val()),
    mobile: $.trim($("#coordinatorMobile").val()),
  };

  if (!payload.email || !payload.employee_id || !payload.last_name || !payload.first_name || !payload.department) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/coordinators/create_coordinator",
    method: "POST",
    dataType: "json",
    data: payload,
    beforeSend: function () {
      $("#createCoordinatorBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
    },
    success: function (response) {
      $("#createCoordinatorBtn").prop("disabled", false).html('<i class="bi bi-person-plus me-2"></i>Create');

      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        resetCreateCoordinatorForm();
        $("#CreateCoordinatorModal").modal("hide");

        $("#CoordinatorCreatedModal").modal("show");
        $("#createdCoordinatorName").text(response.full_name);
        $("#createdCoordinatorTempPassword").text(response.temp_password);
        $("#CoordinatorCreatedModal").attr("data-profile-uuid", response.profile_uuid);
        $("#CoordinatorCreatedModal").attr(
          "data-export-payload",
          JSON.stringify({
            full_name: response.full_name,
            temp_password: response.temp_password,
            email: payload.email,
            employee_id: payload.employee_id,
            department: payload.department,
            mobile: payload.mobile,
          }),
        );

        getCoordinators();
      } else {
        ToastVersion(swalTheme, response.message || "Failed to create coordinator account.", "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#createCoordinatorBtn").prop("disabled", false).html('<i class="bi bi-person-plus me-2"></i>Create');
      Errors(xhr, status, error);
    },
  });
}

function viewCoordinator(profileUuid) {
  if (!profileUuid) {
    ToastVersion(swalTheme, "Invalid coordinator selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/coordinators/get_coordinator",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: profileUuid,
    },
    success: function (response) {
      if (response.status !== "success") {
        ToastVersion(swalTheme, response.message || "Unable to load coordinator details.", "warning", 3000, "top-end");
        return;
      }

      const c = response.coordinator;
      const image = c.profile_name
        ? `../../../Assets/Images/profiles/${c.profile_name}`
        : `https://placehold.co/64x64/483a0f/c6983d/png?text=${c.initials}&font=poppins`;

      $("#ViewCoordinatorModal").attr("data-profile-uuid", c.profile_uuid).attr("data-user-uuid", c.user_uuid);
      $("#viewCoordinatorProfilePic").attr("src", image);
      $("#viewCoordinatorFullName").text(c.full_name);
      $("#viewCoordinatorEmployeeId").text(c.employee_id || "—");
      $("#viewCoordinatorEmail").text(c.email || "—");
      $("#viewCoordinatorDepartment").text(c.department || "—");
      $("#viewCoordinatorMobile").text(c.mobile || "—");
      $("#viewCoordinatorStudentCount").text(c.assigned_students ?? 0);
      $("#viewCoordinatorLastLogin").text(c.last_login || "Never logged in");

      $("#viewCoordinatorStatus")
        .text(c.status_label)
        .removeClass()
        .addClass(`badge rounded-pill px-3 py-2 ${getStatusBadgeClass(c.account_status, c.is_active)}`);

      $("#deactivateCoordinatorBtn").toggleClass("d-none", c.is_active !== 1);
      $("#activateCoordinatorBtn").toggleClass("d-none", c.is_active === 1);

      $("#ViewCoordinatorModal").modal("show");
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function editCoordinator(profileUuid) {
  if (!profileUuid) {
    ToastVersion(swalTheme, "Invalid coordinator selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/coordinators/get_coordinator",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: profileUuid,
    },
    success: function (response) {
      if (response.status !== "success") {
        ToastVersion(swalTheme, response.message || "Unable to load coordinator details.", "warning", 3000, "top-end");
        return;
      }

      const c = response.coordinator;
      $("#EditCoordinatorModal").attr("data-profile-uuid", c.profile_uuid);
      $("#editCoordinatorFullName").text(c.full_name);
      $("#editCoordinatorEmployeeIdDisplay").text(c.employee_id || "—");
      $("#editCoordinatorEmail").text(c.email || "—");
      $("#editCoordinatorEmployeeId").text(c.employee_id || "—");
      $("#editCoordinatorLastName").val(c.last_name || "");
      $("#editCoordinatorFirstName").val(c.first_name || "");
      $("#editCoordinatorMiddleName").val(c.middle_name || "");
      $("#editCoordinatorDepartment").val(c.department || "");
      $("#editCoordinatorMobile").val(c.mobile || "");
      $("#EditCoordinatorModal").modal("show");
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function saveCoordinator(profileUuid) {
  if (!profileUuid) {
    ToastVersion(swalTheme, "Invalid coordinator selected.", "error", 3000, "top-end");
    return;
  }

  const payload = {
    csrf_token: csrfToken,
    profile_uuid: profileUuid,
    last_name: $.trim($("#editCoordinatorLastName").val()),
    first_name: $.trim($("#editCoordinatorFirstName").val()),
    middle_name: $.trim($("#editCoordinatorMiddleName").val()),
    department: $.trim($("#editCoordinatorDepartment").val()),
    mobile: $.trim($("#editCoordinatorMobile").val()),
  };

  if (!payload.last_name || !payload.first_name || !payload.department) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/coordinators/update_coordinator",
    method: "POST",
    dataType: "json",
    data: payload,
    beforeSend: function () {
      $("#saveCoordinatorBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    },
    success: function (response) {
      $("#saveCoordinatorBtn").prop("disabled", false).html('<i class="bi bi-check-circle me-2"></i>Save Changes');

      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        $("#EditCoordinatorModal").modal("hide");
        getCoordinators();
      } else {
        ToastVersion(swalTheme, response.message || "Unable to save coordinator.", "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#saveCoordinatorBtn").prop("disabled", false).html('<i class="bi bi-check-circle me-2"></i>Save Changes');
      Errors(xhr, status, error);
    },
  });
}

function changeCoordinatorStatus(userUuid, activate = false) {
  if (!userUuid) {
    ToastVersion(swalTheme, "Invalid coordinator selected.", "error", 3000, "top-end");
    return;
  }

  const action = activate ? "reactivate" : "deactivate";
  const promptText = activate
    ? "Are you sure you want to reactivate this coordinator account?"
    : "Are you sure you want to deactivate this coordinator account?";

  swal
    .fire({
      title: activate ? "Reactivate Account" : "Deactivate Account",
      text: promptText,
      icon: activate ? "success" : "warning",
      showCancelButton: true,
      confirmButtonText: activate ? "Yes, Reactivate" : "Yes, Deactivate",
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
      if (!result.isConfirmed) return;

      $.ajax({
        url: "../../../process/coordinators/deactivate_coordinator",
        method: "POST",
        dataType: "json",
        data: {
          csrf_token: csrfToken,
          user_uuid: userUuid,
          action,
        },
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            $("#ViewCoordinatorModal").modal("hide");
            getCoordinators();
          } else {
            ToastVersion(swalTheme, response.message || "Status update failed.", "warning", 3000, "top-end");
          }
        },
        error: function (xhr, status, error) {
          Errors(xhr, status, error);
        },
      });
    });
}

function resetCoordinatorPassword(userUuid, displayName) {
  if (!userUuid) {
    ToastVersion(swalTheme, "Invalid coordinator selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/coordinators/reset_coordinator_password",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      user_uuid: userUuid,
    },
    success: function (response) {
      if (response.status === "success") {
        $("#ViewCoordinatorModal").modal("hide");
        $("#ResetCoordinatorPasswordSuccessModal").modal("show");
        $("#resetCoordinatorSuccessName").text(displayName || "Coordinator");
        $("#resetCoordinatorSuccessTempPassword").text(response.temp_password || "—");
        $("#ResetCoordinatorPasswordSuccessModal").attr(
          "data-export-payload",
          JSON.stringify({
            full_name: $("#viewCoordinatorFullName").text() || "Coordinator",
            temp_password: response.temp_password || "",
            email: $("#viewCoordinatorEmail").text() || "",
            employee_id: $("#viewCoordinatorEmployeeId").text() || "",
            department: $("#viewCoordinatorDepartment").text() || "",
            mobile: $("#viewCoordinatorMobile").text() || "",
          }),
        );
        ToastVersion(swalTheme, response.message || "Password reset successful.", "success", 3000, "top-end");
      } else {
        ToastVersion(swalTheme, response.message || "Password reset failed.", "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

$(document).ready(function () {
  getCoordinators();

  const hasSeenTour = localStorage.getItem(COORDINATOR_TOUR_KEY) === "1";
  if (!hasSeenTour) {
    setTimeout(() => {
      startCoordinatorAccountsTour();
    }, 500);
  }

  $(document)
    .off("click", ".js-coordinator-action-btn")
    .on("click", ".js-coordinator-action-btn", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const action = String($(this).data("action") || "").toLowerCase();
      const profileUuid = $(this).data("profile-uuid");
      const userUuid = $(this).data("user-uuid");

      if (action === "view") {
        viewCoordinator(profileUuid);
      } else if (action === "edit") {
        editCoordinator(profileUuid);
      } else if (action === "deactivate") {
        changeCoordinatorStatus(userUuid, false);
      } else if (action === "activate") {
        changeCoordinatorStatus(userUuid, true);
      }
    });

  $("#createCoordinatorBtn").on("click", function () {
    createCoordinator();
  });

  $("#saveCoordinatorBtn").on("click", function () {
    const profileUuid = $("#EditCoordinatorModal").attr("data-profile-uuid");
    saveCoordinator(profileUuid);
  });

  $("#editCoordinatorFromViewBtn").on("click", function () {
    const profileUuid = $("#ViewCoordinatorModal").attr("data-profile-uuid");
    $("#ViewCoordinatorModal").modal("hide");
    editCoordinator(profileUuid);
  });

  $("#resetCoordinatorPasswordBtn").on("click", function () {
    const userUuid = $("#ViewCoordinatorModal").attr("data-user-uuid");
    const name = $("#viewCoordinatorFullName").text();
    resetCoordinatorPassword(userUuid, name);
  });

  $("#deactivateCoordinatorBtn").on("click", function () {
    const userUuid = $("#ViewCoordinatorModal").attr("data-user-uuid");
    changeCoordinatorStatus(userUuid, false);
  });

  $("#activateCoordinatorBtn").on("click", function () {
    const userUuid = $("#ViewCoordinatorModal").attr("data-user-uuid");
    changeCoordinatorStatus(userUuid, true);
  });

  $("#departmentFilter, #coordinatorStatusFilter").on("change", function () {
    getCoordinators();
  });

  $("#coordinatorSearchInput").on("input", function () {
    clearTimeout($(this).data("searchTimeout"));
    $(this).data(
      "searchTimeout",
      setTimeout(function () {
        getCoordinators();
      }, 450),
    );
  });

  $("#clearCoordinatorFiltersBtn").on("click", function () {
    $("#departmentFilter").val("");
    $("#coordinatorStatusFilter").val("");
    $("#coordinatorSearchInput").val("");
    getCoordinators();
  });

  $("#startCoordinatorTourLink").on("click", function (e) {
    e.preventDefault();
    startCoordinatorAccountsTour();
  });

  $("#exportCoordinatorPdfBtn").on("click", function () {
    let payload = {};
    try {
      payload = JSON.parse($("#CoordinatorCreatedModal").attr("data-export-payload") || "{}");
    } catch {
      payload = {};
    }

    const fileName = `${String(payload.full_name || "Coordinator").replace(/\s+/g, "_")}_Coordinator_Account_Details.pdf`;
    exportCoordinatorCredentialsPdf(payload, fileName, $(this));
  });

  $("#exportResetCoordinatorPdfBtn").on("click", function () {
    let payload = {};
    try {
      payload = JSON.parse($("#ResetCoordinatorPasswordSuccessModal").attr("data-export-payload") || "{}");
    } catch {
      payload = {};
    }

    const fileName = `${String(payload.full_name || "Coordinator").replace(/\s+/g, "_")}_Coordinator_Reset_Password_Details.pdf`;
    exportCoordinatorCredentialsPdf(payload, fileName, $(this));
  });
});
