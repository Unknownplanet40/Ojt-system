import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

let allApplications = [];
let currentFilter = "all";
let currentSearchTerm = "";

const statusColors = {
  pending: { bg: "bg-warning-subtle", text: "text-warning-emphasis" },
  approved: { bg: "bg-success-subtle", text: "text-success-emphasis" },
  endorsed: { bg: "bg-info-subtle", text: "text-info-emphasis" },
  active: { bg: "bg-success-subtle", text: "text-success-emphasis" },
  needs_revision: { bg: "bg-danger-subtle", text: "text-danger-emphasis" },
  rejected: { bg: "bg-danger-subtle", text: "text-danger-emphasis" },
  withdrawn: { bg: "bg-secondary-subtle", text: "text-secondary-emphasis" },
};

function escapeHtml(value) {
  return (value || "")
    .toString()
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

function getNoDataState(message = "No applications found.") {
  const safeMessage = escapeHtml(message);
  return `
        <div class=col>
            <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mb-0">${safeMessage}</p>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function renderApplications(applications, noDataMessage = "No applications found.") {
  const applicationsList = $("#applicationsList");
  applicationsList.empty();

  if (!applications || applications.length === 0) {
    applicationsList.append(getNoDataState(noDataMessage));
    return;
  }

  applications.forEach(function (application) {
    const colors = statusColors[application.status] || statusColors.pending;

    const applicationCard = `
                <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
                  <div class="card-body p-4">
                  <div class="d-flex align-items-start gap-3 mb-4">
                  <div class="avatar avatar-md rounded-circle bg-secondary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 56px; height: 56px;">
                  <span class="fw-semibold text-secondary">${application.initials}</span>
                  </div>
                  <div class="flex-grow-1">
                  <h5 class="card-title mb-0 fw-semibold text-truncate">${application.full_name}</h5>
                  <p class="card-text mb-0 text-muted">
                    <span class="d-block"><span class="text-body fw-medium">${application.program_code}</span> - <span class="text-body fw-medium">${application.year_label}</span></span>
                    <span class="d-block mb-0">Applied for: <span class="text-body fw-medium">${application.preferred_dept}</span></span>
                  </p>
                  </div>
                  <div class="flex-shrink-0 ms-2">
                  <span class="badge ${colors.bg} ${colors.text} px-2 py-1 rounded-pill">${application.status_label}</span>
                  </div>
                  </div>
                  <div class="row g-2">
                  <div class="col-6 col-md-3">
                  <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                    <p class="text-muted small mb-1 fw-medium">Application ID</p>
                    <p class="mb-0 fw-semibold small">${application.uuid}</p>
                  </div>
                  </div>
                  <div class="col-6 col-md-3">
                  <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                    <p class="text-muted small mb-1 fw-medium">Work setup</p>
                    <p class="mb-0 fw-semibold small">${application.work_setup}</p>
                  </div>
                  </div>
                  <div class="col-6 col-md-3">
                  <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                    <p class="text-muted small mb-1 fw-medium">Company</p>
                    <p class="mb-0 fw-semibold small">${application.company_name}</p>
                  </div>
                  </div>
                  <div class="col-6 col-md-3">
                  <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                    <p class="text-muted small mb-1 fw-medium">Submitted</p>
                    <p class="mb-0 fw-semibold small">${application.submitted_at}</p>
                  </div>
                  </div>
                  </div>
                  <hr class="my-3">
                  <div class="d-flex justify-content-end align-items-center">
                  <div class="d-grid gap-2 d-md-flex">
                    <button class="btn btn-sm bg-secondary-subtle text-body border flex-md-grow-0 px-3 py-2 rounded-3" data-bs-toggle="modal" data-bs-target="#ReviewModal" id="viewDetailsBtn-${application.uuid}">View Details</button>
                    ${application.status === "approved" || application.status === "active" ? '<button class="btn btn-sm bg-primary-subtle text-primary-emphasis border flex-md-grow-0 px-3 py-2 rounded-3">Generate Endorsement</button>' : ""}
                  </div>
                  </div>
                  </div>
                </div>
                </div>
                `;

    applicationsList.append(applicationCard);

    $(`#viewDetailsBtn-${application.uuid}`).click(function () {
      const card_one = application.card_one || {};
      const card_two = application.card_two || {};
      const card_three = application.card_three || {};
      const card_four = application.card_four || {};

      const modalData = {
        student_name: card_one.student_name || "N/A",
        student_number: card_one.student_No || "N/A",
        program: card_one.program || "N/A",
        course_section: card_one.course_Section || "N/A",
        mobile: card_one.mobile || "N/A",
        email: card_one.email || "N/A",
        company_name: card_two.company_name || "N/A",
        work_setup: card_two.work_setup || "N/A",
        city: card_two.city || "N/A",
        industry: card_two.industry || "N/A",
        slots_info: card_two.slots_info || "N/A",
        accepted_programs: card_two.accepted_programs || [],
        submitted_at: card_three.submitted_at || "N/A",
        preferred_dept: card_three.preferred_dept || "N/A",
        cover_letter: card_three.cover_letter || "N/A",
        coordinator_note: card_three.coordinator_note || "N/A",
        requirements: card_four.requirements || {},
        canapprove: application.can_approve || false,
        canreturn: application.can_return || false,
        canreject: application.can_reject || false,
        application_uuid: application.uuid || "",
      };

      clearApplicationDetails();
      openApplicationDetails(modalData);
    });
  });
}

function normalizeSearchText(value) {
  return (value || "").toString().toLowerCase().trim();
}

function applySearchFilter() {
  const searchTerm = normalizeSearchText(currentSearchTerm);

  if (!searchTerm) {
    renderApplications(allApplications);
    return;
  }

  const filteredApplications = allApplications.filter((application) => {
    const searchableFields = [
      application.full_name,
      application.uuid,
      application.program_code,
      application.year_label,
      application.preferred_dept,
      application.work_setup,
      application.company_name,
      application.submitted_at,
      application.status_label,
    ];

    return searchableFields.some((field) => normalizeSearchText(field).includes(searchTerm));
  });

  renderApplications(filteredApplications, `No applications found for "${currentSearchTerm}".`);
}

function getApplications(filter = "all") {
  const applicationsList = $("#applicationsList");
  applicationsList.empty();

  const loadingState = `
        <div class=col>
            <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <div class="spinner-border text-secondary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mb-0">Loading applications...</p>
                    </div>
                </div>
            </div>
        </div>
    `;

  currentFilter = filter;

  let requestStatuses;

  if (filter === "all") {
    requestStatuses = ["pending", "approved", "endorsed", "active", "needs_revision", "rejected", "withdrawn"];
  } else {
    requestStatuses = [filter];
  }

  $.ajax({
    url: "../../../Assets/api/application_functions",
    method: "POST",
    data: {
      action: "get_applications",
      status: requestStatuses,
    },
    dataType: "json",
    timeout: 5000,
    beforeSend: function () {
      applicationsList.append(loadingState);
    },
    success: function (response) {
      applicationsList.empty();

      if (response.status === "success") {
        allApplications = Array.isArray(response.data) ? response.data : [];
        applySearchFilter();

        $("#filterAllBadge").text(
          response.status_counts.pending +
            response.status_counts.approved +
            response.status_counts.endorsed +
            response.status_counts.active +
            response.status_counts.needs_revision +
            response.status_counts.rejected +
            response.status_counts.withdrawn,
        );
        $("#filterPendingBadge")
          .text(response.status_counts.pending)
          .toggleClass("d-none", response.status_counts.pending === 0);
        $("#filterApprovedBadge")
          .text(response.status_counts.approved)
          .toggleClass("d-none", response.status_counts.approved === 0);
        $("#filterEndorsedBadge")
          .text(response.status_counts.endorsed)
          .toggleClass("d-none", response.status_counts.endorsed === 0);
        $("#filterActiveBadge")
          .text(response.status_counts.active)
          .toggleClass("d-none", response.status_counts.active === 0);
        $("#filterNeedRevisionsBadge")
          .text(response.status_counts.needs_revision)
          .toggleClass("d-none", response.status_counts.needs_revision === 0);
        $("#filterRejectedBadge")
          .text(response.status_counts.rejected)
          .toggleClass("d-none", response.status_counts.rejected === 0);
        $("#filterWithdrawnBadge")
          .text(response.status_counts.withdrawn)
          .toggleClass("d-none", response.status_counts.withdrawn === 0);
      } else {
        allApplications = [];
        applicationsList.append(getNoDataState());
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      allApplications = [];
      applicationsList.empty();
      applicationsList.append(getNoDataState());
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching applications. Please try again.", "error", 3000, "top-end", "8");
      }
    },
  });
}

function clearApplicationDetails() {
  $("#stuName").text("N/A");
  $("#stuNum").text("N/A");
  $("#stuProg").text("N/A - N/A");

  $("#stuNamec1").text("N/A");
  $("#stuNumc1").text("N/A");
  $("#stuProgc1").text("N/A");
  $("#stuSectionc1").text("N/A");
  $("#stuMobilec1").text("N/A");
  $("#stuEmailc1").text("N/A");

  $("#stuCompanyc2").text("N/A");
  $("#stuIndustryc2").text("N/A");
  $("#stuLocationc2").text("N/A");
  $("#stuWorkSetupc2").text("N/A");
  $("#stuSlotsc2").text("N/A");
  $("#stuAcceptsc2").text("N/A");

  $("#submittedAtc3").text("N/A");
  $("#stuPreferredDeptc3").text("N/A");
  $("#coverletterc3").text("N/A");

  $("#requirementsStatusc4").empty().append('<p class="text-muted">No requirement information available.</p>');

  $("#returnBtn").addClass("d-none");
  $("#approveBtn").addClass("d-none");
  $("#rejectBtn").addClass("d-none");

  $("#ReviewModal").attr("data-application-uuid", "");
  $("#ApproveModal").attr("data-application-uuid", "");
  $("#ReturnModal").attr("data-application-uuid", "");
  $("#RejectModal").attr("data-application-uuid", "");

  $("#stuNamem2c1").text("N/A");
  $("#stuNumm2c1").text("N/A");
  $("#stuProgm2c1").text("N/A");
  $("#stuCompanym2c2").text("N/A");
  $("#stuWorkSetupm2c2").text("N/A");
  $("#stuSlotsm2c2").text("N/A");
}

function openApplicationDetails(data) {
  $("#stuName").text(data.student_name || "N/A");
  $("#stuNum").text(data.student_number || "N/A");
  $("#stuProg").text(`${data.program || "N/A"} - ${data.course_section || "N/A"}`);
  $("#stuNamec1").text(data.student_name || "N/A");
  $("#stuNumc1").text(data.student_number || "N/A");
  $("#stuProgc1").text(data.program || "N/A");
  $("#stuSectionc1").text(`${data.course_section || "N/A"}`);
  $("#stuMobilec1").text(data.mobile || "N/A");
  $("#stuEmailc1").text(data.email || "N/A");
  $("#stuCompanyc2").text(data.company_name || "N/A");
  $("#stuIndustryc2").text(data.industry || "N/A");
  $("#stuLocationc2").text(data.city + ", Philippines" || "N/A");
  $("#stuWorkSetupc2").text(data.work_setup ? data.work_setup.charAt(0).toUpperCase() + data.work_setup.slice(1) : "N/A");
  $("#stuSlotsc2").text(data.slots_info || "N/A");
  $("#stuAcceptsc2").text(Array.isArray(data.accepted_programs) && data.accepted_programs.length > 0 ? data.accepted_programs.join(", ") : "N/A");
  $("#submittedAtc3").text(data.submitted_at || "N/A");
  $("#stuPreferredDeptc3").text(data.preferred_dept || "N/A");
  $("#coverletterc3").text(data.cover_letter || "N/A");

  const requirementsStatusc4 = $("#requirementsStatusc4");
  requirementsStatusc4.empty();

  const requirementIcons = {
    not_submitted: { bg: "bg-secondary-subtle", text: "text-secondary-emphasis", icon: "bi-x-lg" },
    submitted: { bg: "bg-warning-subtle", text: "text-warning-emphasis", icon: "bi-hourglass-split" },
    under_review: { bg: "bg-info-subtle", text: "text-info-emphasis", icon: "bi-search" },
    approved: { bg: "bg-success-subtle", text: "text-success-emphasis", icon: "bi-file-earmark-check" },
    returned: { bg: "bg-danger-subtle", text: "text-danger-emphasis", icon: "bi-arrow-counterclockwise" },
  };

  if (data.requirements && typeof data.requirements === "object") {
    Object.entries(data.requirements).forEach(([requirement, status]) => {
      const icons = requirementIcons[status] || requirementIcons.not_submitted;
      const requirementElement = `
                <div class="col">
                    <div class="d-flex flex-column align-items-center gap-2">
                        <div class="rounded-circle ${icons.bg} ${icons.text} d-flex justify-content-center align-items-center flex-shrink-0"
                            style="width: 48px; height: 48px;">
                            <i class="bi ${icons.icon} fs-5"></i>
                        </div>
                        <p class="mb-0 small fw-medium text-muted text-center">${requirement.replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase())}</p>
                    </div>
                </div>
            `;
      requirementsStatusc4.append(requirementElement);
    });
  } else {
    requirementsStatusc4.append('<p class="text-muted">No requirement information available.</p>');
  }

  $("#returnBtn").toggleClass("d-none", !data.canreturn);
  $("#approveBtn").toggleClass("d-none", !data.canapprove);
  $("#rejectBtn").toggleClass("d-none", !data.canreject);

  $("#ReviewModal").attr("data-application-uuid", data.application_uuid || "");
  $("#ApproveModal").attr("data-application-uuid", data.application_uuid || "");
  $("#ReturnModal").attr("data-application-uuid", data.application_uuid || "");
  $("#RejectModal").attr("data-application-uuid", data.application_uuid || "");

  $("#stuNamem2c1").text(data.student_name || "N/A");
  $("#stuNumm2c1").text(data.student_number || "N/A");
  $("#stuProgm2c1").text(data.program || "N/A");
  $("#stuCompanym2c2").text(data.company_name || "N/A");
  $("#stuWorkSetupm2c2").text(data.work_setup ? data.work_setup.charAt(0).toUpperCase() + data.work_setup.slice(1) : "N/A");
  $("#stuSlotsm2c2").text(data.slots_info || "N/A");
}

$(document).ready(function () {
  getApplications();

  $("#applicationSearchInput").on("input", function () {
    currentSearchTerm = $(this).val();
    applySearchFilter();
  });

  $("#filterAllBtn").click(function () {
    getApplications();
  });

  $("#filterPendingBtn").click(function () {
    if ($("#filterPendingBadge").text() !== "0") {
      getApplications("pending");
    }
  });

  $("#filterNeedRevisionsBtn").click(function () {
    if ($("#filterNeedRevisionsBadge").text() !== "0") {
      getApplications("needs_revision");
    }
  });

  $("#filterApprovedBtn").click(function () {
    if ($("#filterApprovedBadge").text() !== "0") {
      getApplications("approved");
    }
  });

  $("#filterRejectedBtn").click(function () {
    if ($("#filterRejectedBadge").text() !== "0") {
      getApplications("rejected");
    }
  });

  $("#filterWithdrawnBtn").click(function () {
    if ($("#filterWithdrawnBadge").text() !== "0") {
      getApplications("withdrawn");
    }
  });

  $("#confirmApproveBtn").click(function () {
    const applicationUuid = $("#ApproveModal").attr("data-application-uuid");
    const approvalNote = $("#approvalNote").val().trim();

    if (!applicationUuid) {
      ToastVersion(swalTheme, "Application ID is missing. Please try again.", "error", 3000, "top-end", "8");
      return;
    }

    $.ajax({
      url: "../../../Assets/api/application_functions",
      method: "POST",
      data: {
        action: "approve_application",
        application_uuid: applicationUuid,
        note: approvalNote,
      },
      dataType: "json",
      timeout: 5000,
      beforeSend: function () {
        $("#confirmApproveBtn").prop("disabled", true).text("Approving...");
      },
      success: function (response) {
        $("#confirmApproveBtn").prop("disabled", false).text("Confirm Approval");
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          clearApplicationDetails();
          getApplications(currentFilter);
          $("#ApproveModal").modal("hide");
        }
      },
      error: function (xhr, status, error) {
        $("#confirmApproveBtn").prop("disabled", false).text("Confirm Approval");
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
        } else {
          ToastVersion(swalTheme, "An error occurred while approving the application. Please try again.", "error", 3000, "top-end", "8");
        }
      },
    });
  });

  $("#confirmReturnBtn").click(function () {
    const applicationUuid = $("#ReturnModal").attr("data-application-uuid");
    const returnNote = $("#revisionReason").val().trim();

    if (!applicationUuid) {
      ToastVersion(swalTheme, "Application ID is missing. Please try again.", "error", 3000, "top-end", "8");
      return;
    }

    $.ajax({
      url: "../../../Assets/api/application_functions",
      method: "POST",
      data: {
        action: "return_application",
        application_uuid: applicationUuid,
        note: returnNote,
      },
      dataType: "json",
      timeout: 5000,
      beforeSend: function () {
        $("#confirmReturnBtn").prop("disabled", true).text("Returning...");
      },
      success: function (response) {
        $("#confirmReturnBtn").prop("disabled", false).text("Return for Revision");
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          clearApplicationDetails();
          getApplications(currentFilter);
          $("#ReturnModal").modal("hide");
        }
      },
      error: function (xhr, status, error) {
        $("#confirmReturnBtn").prop("disabled", false).text("Return for Revision");
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
        } else {
          ToastVersion(swalTheme, "An error occurred while returning the application. Please try again.", "error", 3000, "top-end", "8");
        }
      },
    });
  });

  $("#confirmRejectBtn").click(function () {
    const applicationUuid = $("#RejectModal").attr("data-application-uuid");
    const rejectionReason = $("#revisionReason").val().trim();

    if (!applicationUuid) {
      ToastVersion(swalTheme, "Application ID is missing. Please try again.", "error", 3000, "top-end", "8");
      return;
    }

    $.ajax({
      url: "../../../Assets/api/application_functions",
      method: "POST",
      data: {
        action: "reject_application",
        application_uuid: applicationUuid,
        note: rejectionReason,
      },
      dataType: "json",
      timeout: 5000,
      beforeSend: function () {
        $("#confirmRejectBtn").prop("disabled", true).text("Rejecting...");
      },
      success: function (response) {
        $("#confirmRejectBtn").prop("disabled", false).text("Reject Application");
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          clearApplicationDetails();
          getApplications(currentFilter);
          $("#RejectModal").modal("hide");
        }
      },
      error: function (xhr, status, error) {
        $("#confirmRejectBtn").prop("disabled", false).text("Reject Application");
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
        } else {
          ToastVersion(swalTheme, "An error occurred while rejecting the application. Please try again.", "error", 3000, "top-end", "8");
        }
      },
    });
  });
});
