import { ToastVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

const swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const loadingRow = `
<tr class="border-0">
  <td colspan="6" class="text-center py-4 bg-transparent border-0" style="cursor: wait;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>
      <div>
        <p class="mb-2 text-body fw-semibold">Loading supervisors...</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Please wait while we fetch supervisor accounts.</small>
      </div>
    </div>
  </td>
</tr>`;

const emptyRow = `
<tr class="border-0">
  <td colspan="6" class="text-center py-4 bg-transparent border-0" style="cursor: default;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
        <i class="bi bi-person-circle fa-lg"></i>
      </div>
      <div>
        <p class="mb-2 text-body fw-semibold">No supervisors found</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Click "Add Supervisor" to create a new account.</small>
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

function resetCreateSupervisorForm() {
  $("#supervisorEmail").val("");
  $("#supervisorCompany").val("");
  $("#supervisorLastName").val("");
  $("#supervisorFirstName").val("");
  $("#supervisorPosition").val("");
  $("#supervisorDepartment").val("");
  $("#supervisorMobile").val("");
}

function exportSupervisorCredentialsPdf(exportData, defaultFileName = "Supervisor_Account_Details.pdf", $triggerButton = null, requireTempPassword = true) {
  if (!exportData || !exportData.full_name || (requireTempPassword && !exportData.temp_password)) {
    ToastVersion(swalTheme, "Missing data for PDF export.", "error", 3000, "top-end");
    return;
  }

  const hasButton = $triggerButton && $triggerButton.length;
  const originalButtonHtml = hasButton ? $triggerButton.html() : "";

  if (hasButton) {
    if ($triggerButton.prop("disabled")) return;

    $triggerButton
      .prop("disabled", true)
      .html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating PDF...');
  }

  ToastVersion(swalTheme, "Generating PDF, please wait...", "info", 1800, "top-end");

  $.ajax({
    url: "../../../process/supervisors/export_supervisor_pdf",
    method: "POST",
    data: {
      csrf_token: csrfToken,
      supervisor_data: JSON.stringify(exportData),
    },
    xhrFields: {
      responseType: "blob",
    },
    success: function (pdfResponse, _status, xhr) {
      if (hasButton) {
        $triggerButton.prop("disabled", false).html(originalButtonHtml);
      }

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
      if (hasButton) {
        $triggerButton.prop("disabled", false).html(originalButtonHtml);
      }
      Errors(xhr, status, error);
    },
  });
}

function fillCompanySelect($select, companies = [], selectedValue = "") {
  $select.empty().append('<option value="" class="CustomOption" selected>Choose company</option>');

  companies.forEach((company) => {
    const selected = String(selectedValue || "") === String(company.uuid || "") ? "selected" : "";
    const labelParts = [company.name || "Company"];
    if (company.city) labelParts.push(company.city);
    if (company.work_setup) labelParts.push(company.work_setup);
    $select.append(`<option value="${company.uuid}" class="CustomOption" ${selected}>${labelParts.join(" · ")}</option>`);
  });
}

function getSupervisors() {
  const tableBody = $("#supervisorsTable tbody");

  $.ajax({
    url: "../../../process/supervisors/get_supervisors",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      status: $("#supervisorStatusFilter").val(),
      company_uuid: $("#companyFilter").val(),
      search: $("#supervisorSearchInput").val(),
    },
    beforeSend: function () {
      tableBody.html(loadingRow);
    },
    success: function (response) {
      if (response.status !== "success") {
        tableBody.html(emptyRow);
        ToastVersion(swalTheme, response.message || "Failed to fetch supervisors.", "warning", 3000, "top-end");
        return;
      }

      const supervisors = response.supervisors || [];
      $("#supervisorCount").text(response.total || supervisors.length || 0);

      fillCompanySelect($("#supervisorCompany"), response.companies || []);
      fillCompanySelect($("#editSupervisorCompany"), response.companies || [], $("#EditSupervisorModal").attr("data-company-uuid") || "");

      const companyFilter = $("#companyFilter");
      companyFilter.empty().append('<option value="" class="CustomOption" selected>All Companies</option>');
      (response.companies || []).forEach((company) => {
        const selected = response.selectedCompany === company.uuid ? "selected" : "";
        companyFilter.append(`<option class="CustomOption" value="${company.uuid}" ${selected}>${company.name}</option>`);
      });

      $("#supervisorStatusFilter").val(response.selectedStatus || "");
      $("#supervisorSearchInput").val(response.searchQuery || "");

      if (response.selectedStatus || response.selectedCompany || response.searchQuery) {
        $("#clearSupervisorFiltersBtn").removeClass("d-none");
      } else {
        $("#clearSupervisorFiltersBtn").addClass("d-none");
      }

      tableBody.empty();
      if (!supervisors.length) {
        tableBody.html(emptyRow);
        return;
      }

      supervisors.forEach((supervisor) => {
        const profileImage = supervisor.profile_name
          ? `../../../Assets/Images/profiles/${supervisor.profile_name}`
          : `https://placehold.co/64x64/483a0f/c6983d/png?text=${supervisor.initials}&font=poppins`;

        tableBody.append(`
          <tr class="js-supervisor-row" data-profile-uuid="${supervisor.profile_uuid}" style="cursor: pointer;">
            <td class="bg-blur-5 bg-semi-transparent border-0">
              <div class="hstack">
                <div class="rounded-circle overflow-hidden d-flex justify-content-center align-items-center border border-secondary-subtle flex-shrink-0" style="width: 40px; height: 40px; min-width: 40px;">
                  <img src="${profileImage}" alt="Profile Picture" class="img-fluid">
                </div>
                <div class="ms-3 min-w-0">
                  <p class="mb-0 fw-bold text-truncate">${supervisor.full_name}</p>
                  <small class="mb-0 text-muted text-truncate d-block" style="font-size: 0.80rem;">${supervisor.email}</small>
                </div>
              </div>
            </td>
            <td class="py-3 d-none d-lg-table-cell bg-blur-5 bg-semi-transparent border-0">${supervisor.company_name || '—'}</td>
            <td class="py-3 d-none d-md-table-cell bg-blur-5 bg-semi-transparent border-0">${supervisor.position || '—'}</td>
            <td class="py-3 d-none d-xl-table-cell bg-blur-5 bg-semi-transparent border-0">${supervisor.department || '—'}</td>
            <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">
              <a href="javascript:void(0)" class="js-supervisor-students-link text-decoration-none fw-semibold" data-company-uuid="${supervisor.company_uuid || ""}" title="View students for ${supervisor.company_name || "this company"}">
                ${supervisor.students_count ?? 0}
              </a>
            </td>
            <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0">
              <span class="badge rounded-pill bg-opacity-10 ${getStatusBadgeClass(supervisor.account_status, supervisor.is_active)}">${supervisor.status_label}</span>
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

function createSupervisor() {
  const payload = {
    csrf_token: csrfToken,
    email: $.trim($("#supervisorEmail").val()),
    company_uuid: $.trim($("#supervisorCompany").val()),
    last_name: $.trim($("#supervisorLastName").val()),
    first_name: $.trim($("#supervisorFirstName").val()),
    position: $.trim($("#supervisorPosition").val()),
    department: $.trim($("#supervisorDepartment").val()),
    mobile: $.trim($("#supervisorMobile").val()),
  };

  if (!payload.email || !payload.company_uuid || !payload.last_name || !payload.first_name || !payload.position || !payload.department) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/supervisors/create_supervisor",
    method: "POST",
    dataType: "json",
    data: payload,
    beforeSend: function () {
      $("#createSupervisorBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Creating...');
    },
    success: function (response) {
      $("#createSupervisorBtn").prop("disabled", false).text("Save Supervisor");

      if (response.status === "success") {
        const exportData = {
          full_name: response.full_name,
          temp_password: response.temp_password,
          email: payload.email,
          company_name: $.trim($("#supervisorCompany option:selected").text()),
          position: payload.position,
          department: payload.department,
          mobile: payload.mobile,
        };

        $("#SupervisorCreatedModal").attr("data-supervisor-data", JSON.stringify(exportData));
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        resetCreateSupervisorForm();
        $("#CreateSupervisorModal").modal("hide");
  $("#exportSupervisorPdfBtn").removeClass("d-none");
        $("#SupervisorCreatedModal").modal("show");
        $("#createdSupervisorName").text(response.full_name);
        $("#createdSupervisorTempPassword").text(response.temp_password);
        getSupervisors();
      } else {
        ToastVersion(swalTheme, response.message || "Failed to create supervisor account.", "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#createSupervisorBtn").prop("disabled", false).text("Save Supervisor");
      Errors(xhr, status, error);
    },
  });
}

function loadSupervisor(profileUuid, mode = "view") {
  if (!profileUuid) {
    ToastVersion(swalTheme, "Invalid supervisor selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/supervisors/get_supervisor",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      profile_uuid: profileUuid,
    },
    success: function (response) {
      if (response.status !== "success") {
        ToastVersion(swalTheme, response.message || "Unable to load supervisor details.", "warning", 3000, "top-end");
        return;
      }

      const s = response.supervisor;
      const image = s.profile_name
        ? `../../../Assets/Images/profiles/${s.profile_name}`
        : `https://placehold.co/64x64/483a0f/c6983d/png?text=${s.initials}&font=poppins`;

      $("#EditSupervisorModal").attr("data-profile-uuid", s.profile_uuid).attr("data-company-uuid", s.company_uuid || "");
      $("#ViewSupervisorModal").attr("data-profile-uuid", s.profile_uuid).attr("data-user-uuid", s.user_uuid);
      fillCompanySelect($("#editSupervisorCompany"), response.companies || [], s.company_uuid || "");

      if (mode === "view") {
        $("#viewSupervisorProfilePic").attr("src", image);
        $("#viewSupervisorFullName").text(s.full_name || "—");
        $("#viewSupervisorCompany").text(s.company_name || "—");
        $("#viewSupervisorEmail").text(s.email || "—");
        $("#viewSupervisorPosition").text(s.position || "—");
        $("#viewSupervisorDepartment").text(s.department || "—");
        $("#viewSupervisorMobile").text(s.mobile || "—");
        $("#viewSupervisorLastLogin").text(s.last_login || "Never logged in");
        $("#viewSupervisorCreatedAt").text(s.created_at || s.account_created_at || "—");

        $("#viewSupervisorStatus")
          .text(s.status_label)
          .removeClass()
          .addClass(`badge rounded-pill px-3 py-2 ${getStatusBadgeClass(s.account_status, s.is_active)}`);

        $("#deactivateSupervisorBtn").toggleClass("d-none", s.is_active !== 1);
        $("#activateSupervisorBtn").toggleClass("d-none", s.is_active === 1);
        $("#ViewSupervisorModal").attr(
          "data-supervisor-data",
          JSON.stringify({
            full_name: s.full_name || "",
            email: s.email || "",
            company_name: s.company_name || "",
            position: s.position || "",
            department: s.department || "",
            mobile: s.mobile || "",
          }),
        );
        $("#ViewSupervisorModal").modal("show");
        return;
      }

      $("#editSupervisorFullName").text(s.full_name || "—");
      $("#editSupervisorEmail").text(s.email || "—");
      $("#editSupervisorProfilePic").attr("src", image);
      $("#editSupervisorLastName").val(s.last_name || "");
      $("#editSupervisorFirstName").val(s.first_name || "");
      $("#editSupervisorPosition").val(s.position || "");
      $("#editSupervisorDepartment").val(s.department || "");
      $("#editSupervisorMobile").val(s.mobile || "");
      $("#EditSupervisorModal").modal("show");
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function saveSupervisor(profileUuid) {
  if (!profileUuid) {
    ToastVersion(swalTheme, "Invalid supervisor selected.", "error", 3000, "top-end");
    return;
  }

  const payload = {
    csrf_token: csrfToken,
    profile_uuid: profileUuid,
    company_uuid: $.trim($("#editSupervisorCompany").val()),
    last_name: $.trim($("#editSupervisorLastName").val()),
    first_name: $.trim($("#editSupervisorFirstName").val()),
    position: $.trim($("#editSupervisorPosition").val()),
    department: $.trim($("#editSupervisorDepartment").val()),
    mobile: $.trim($("#editSupervisorMobile").val()),
  };

  if (!payload.company_uuid || !payload.last_name || !payload.first_name || !payload.position || !payload.department) {
    ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/supervisors/update_supervisor",
    method: "POST",
    dataType: "json",
    data: payload,
    beforeSend: function () {
      $("#saveSupervisorBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
    },
    success: function (response) {
      $("#saveSupervisorBtn").prop("disabled", false).text("Save Changes");

      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
        $("#EditSupervisorModal").modal("hide");
        getSupervisors();
      } else {
        ToastVersion(swalTheme, response.message || "Unable to save supervisor.", "warning", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      $("#saveSupervisorBtn").prop("disabled", false).text("Save Changes");
      Errors(xhr, status, error);
    },
  });
}

function changeSupervisorStatus(userUuid, activate = false) {
  if (!userUuid) {
    ToastVersion(swalTheme, "Invalid supervisor selected.", "error", 3000, "top-end");
    return;
  }

  const action = activate ? "reactivate" : "deactivate";

  swal
    .fire({
      title: activate ? "Reactivate Account" : "Deactivate Account",
      text: activate ? "Are you sure you want to reactivate this supervisor account?" : "Are you sure you want to deactivate this supervisor account?",
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
        url: "../../../process/supervisors/deactivate_supervisor",
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
            $("#ViewSupervisorModal").modal("hide");
            getSupervisors();
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

function resetSupervisorPassword(userUuid, displayName) {
  if (!userUuid) {
    ToastVersion(swalTheme, "Invalid supervisor selected.", "error", 3000, "top-end");
    return;
  }

  $.ajax({
    url: "../../../process/supervisors/reset_supervisor_password",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      user_uuid: userUuid,
    },
    success: function (response) {
      if (response.status === "success") {
        const existingSupervisorDataRaw = $("#ViewSupervisorModal").attr("data-supervisor-data") || "{}";
        let existingSupervisorData = {};

        try {
          existingSupervisorData = JSON.parse(existingSupervisorDataRaw);
        } catch {
          existingSupervisorData = {};
        }

        const resetExportData = {
          ...existingSupervisorData,
          temp_password: response.temp_password || "",
          full_name: displayName || existingSupervisorData.full_name || "Supervisor",
        };

        $("#ResetSupervisorPasswordSuccessModal").attr("data-supervisor-data", JSON.stringify(resetExportData));
        $("#ViewSupervisorModal").modal("hide");
        $("#ResetSupervisorPasswordSuccessModal").modal("show");
        $("#resetSupervisorSuccessName").text(displayName || "Supervisor");
        $("#resetSupervisorSuccessTempPassword").text(response.temp_password || "—");
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
  getSupervisors();

  $(document)
    .off("click", ".js-supervisor-row")
    .on("click", ".js-supervisor-row", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const profileUuid = $(this).data("profile-uuid");
      loadSupervisor(profileUuid, "view");
    });

  $(document)
    .off("click", ".js-supervisor-students-link")
    .on("click", ".js-supervisor-students-link", function (e) {
      e.preventDefault();
      e.stopPropagation();

      const companyUuid = String($(this).data("company-uuid") || "").trim();
      const target = companyUuid
        ? `../Admin/Students?company_uuid=${encodeURIComponent(companyUuid)}`
        : "../Admin/Students";

      window.location.href = target;
    });

  $("#createSupervisorBtn").on("click", function () {
    createSupervisor();
  });

  $("#saveSupervisorBtn").on("click", function () {
    const profileUuid = $("#EditSupervisorModal").attr("data-profile-uuid");
    saveSupervisor(profileUuid);
  });

  $("#editSupervisorFromViewBtn").on("click", function () {
    const profileUuid = $("#ViewSupervisorModal").attr("data-profile-uuid");
    $("#ViewSupervisorModal").modal("hide");
    loadSupervisor(profileUuid, "edit");
  });

  $("#resetSupervisorPasswordBtn").on("click", function () {
    const userUuid = $("#ViewSupervisorModal").attr("data-user-uuid");
    const name = $("#viewSupervisorFullName").text();
    resetSupervisorPassword(userUuid, name);
  });

  $("#exportViewSupervisorPdfBtn").on("click", function () {
    const exportDataRaw = $("#ViewSupervisorModal").attr("data-supervisor-data") || "{}";
    let exportData = {};

    try {
      exportData = JSON.parse(exportDataRaw);
    } catch {
      exportData = {};
    }

    exportSupervisorCredentialsPdf(exportData, "Supervisor_Account_Details.pdf", $(this), false);
  });

  $("#exportResetSupervisorPdfBtn").on("click", function () {
    const exportDataRaw = $("#ResetSupervisorPasswordSuccessModal").attr("data-supervisor-data") || "{}";
    let exportData = {};

    try {
      exportData = JSON.parse(exportDataRaw);
    } catch {
      exportData = {};
    }

    exportSupervisorCredentialsPdf(exportData, "Supervisor_Reset_Credentials.pdf", $(this));
  });

  $("#exportSupervisorPdfBtn").on("click", function () {
    const exportDataRaw = $("#SupervisorCreatedModal").attr("data-supervisor-data") || "{}";
    let exportData = {};

    try {
      exportData = JSON.parse(exportDataRaw);
    } catch {
      exportData = {};
    }

    exportSupervisorCredentialsPdf(exportData, "Supervisor_Account_Details.pdf", $(this));
  });

  $("#deactivateSupervisorBtn").on("click", function () {
    const userUuid = $("#ViewSupervisorModal").attr("data-user-uuid");
    changeSupervisorStatus(userUuid, false);
  });

  $("#activateSupervisorBtn").on("click", function () {
    const userUuid = $("#ViewSupervisorModal").attr("data-user-uuid");
    changeSupervisorStatus(userUuid, true);
  });

  $("#companyFilter, #supervisorStatusFilter").on("change", function () {
    getSupervisors();
  });

  $("#supervisorSearchInput").on("input", function () {
    clearTimeout($(this).data("searchTimeout"));
    $(this).data(
      "searchTimeout",
      setTimeout(function () {
        getSupervisors();
      }, 450),
    );
  });

  $("#clearSupervisorFiltersBtn").on("click", function () {
    $("#companyFilter").val("");
    $("#supervisorStatusFilter").val("");
    $("#supervisorSearchInput").val("");
    getSupervisors();
  });

  $("#CreateSupervisorModal").on("hidden.bs.modal", function () {
    resetCreateSupervisorForm();
  });

  $("#SupervisorCreatedModal").on("hidden.bs.modal", function () {
    $(this).removeAttr("data-supervisor-data");
  });

  $("#ResetSupervisorPasswordSuccessModal").on("hidden.bs.modal", function () {
    $(this).removeAttr("data-supervisor-data");
  });
});
