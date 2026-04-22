import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const APPLICATION_ENDPOINTS = {
  getApplications: "../../../process/applications/get_applications",
  approveApplication: "../../../process/applications/approve_application",
  endorseApplication: "../../../process/applications/endorse_application",
  confirmStart: "../../../process/applications/confirm_start",
  returnApplication: "../../../process/applications/return_application",
  rejectApplication: "../../../process/applications/reject_application",
};

let applicationsCache = [];
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

function normalize(value) {
  return (value ?? "").toString().toLowerCase().trim();
}

function getNoDataState(message = "No applications found.") {
  return `
    <div class="col">
      <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
        <div class="card-body p-4">
          <div class="d-flex align-items-center justify-content-center gap-3">
            <i class="bi bi-inbox fs-1 text-muted"></i>
            <p class="text-muted mb-0">${message}</p>
          </div>
        </div>
      </div>
    </div>
  `;
}

function getApplicationByUuid(uuid) {
  return applicationsCache.find((application) => application.uuid === uuid) || null;
}

function renderApplications(applications, noDataMessage = "No applications found.") {
  const list = $("#applicationsList");
  list.empty();

  if (!Array.isArray(applications) || applications.length === 0) {
    list.append(getNoDataState(noDataMessage));
    return;
  }

  applications.forEach((application) => {
    const colors = statusColors[application.status] || statusColors.pending;
    list.append(`
      <div class="col">
        <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4">
          <div class="card-body p-4">
            <div class="d-flex align-items-start gap-3 mb-4">
              <div class="avatar avatar-md rounded-circle bg-secondary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 56px; height: 56px;">
                <span class="fw-semibold text-secondary">${application.initials || "NA"}</span>
              </div>
              <div class="flex-grow-1">
                <h5 class="card-title mb-0 fw-semibold text-truncate">${application.full_name || "N/A"}</h5>
                <p class="card-text mb-0 text-muted">
                  <span class="d-block"><span class="text-body fw-medium">${application.program_code || "—"}</span> - <span class="text-body fw-medium">${application.year_label || "—"}</span></span>
                  <span class="d-block mb-0">Applied for: <span class="text-body fw-medium">${application.preferred_dept || "—"}</span></span>
                </p>
              </div>
              <div class="flex-shrink-0 ms-2">
                <span class="badge ${colors.bg} ${colors.text} px-2 py-1 rounded-pill">${application.status_label || application.status}</span>
              </div>
            </div>
            <div class="row g-2">
              <div class="col-6 col-md-3"><div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100"><p class="text-muted small mb-1 fw-medium">Application ID</p><p class="mb-0 fw-semibold small">${application.uuid}</p></div></div>
              <div class="col-6 col-md-3"><div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100"><p class="text-muted small mb-1 fw-medium">Work setup</p><p class="mb-0 fw-semibold small">${application.work_setup || "—"}</p></div></div>
              <div class="col-6 col-md-3"><div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100"><p class="text-muted small mb-1 fw-medium">Company</p><p class="mb-0 fw-semibold small">${application.company_name || "—"}</p></div></div>
              <div class="col-6 col-md-3"><div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100"><p class="text-muted small mb-1 fw-medium">Submitted</p><p class="mb-0 fw-semibold small">${application.submitted_at || "—"}</p></div></div>
            </div>
            <hr class="my-3">
            <div class="d-flex justify-content-end align-items-center gap-2 flex-wrap">
              <button type="button" class="btn btn-sm bg-secondary-subtle text-body border flex-md-grow-0 px-3 py-2 rounded-3 view-application-btn" data-uuid="${application.uuid}" data-bs-toggle="modal" data-bs-target="#ReviewModal">View Details</button>
            </div>
          </div>
        </div>
      </div>
    `);
  });
}

function applySearchFilter() {
  const term = normalize(currentSearchTerm);
  if (!term) {
    renderApplications(applicationsCache);
    return;
  }

  const filtered = applicationsCache.filter((application) => {
    const fields = [
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

    return fields.some((field) => normalize(field).includes(term));
  });

  renderApplications(filtered, `No applications found for "${currentSearchTerm}".`);
}

function clearApplicationDetails() {
  ["#stuName", "#stuNum", "#stuProg", "#stuNamec1", "#stuNumc1", "#stuProgc1", "#stuSectionc1", "#stuMobilec1", "#stuEmailc1", "#stuCompanyc2", "#stuIndustryc2", "#stuLocationc2", "#stuWorkSetupc2", "#stuSlotsc2", "#stuAcceptsc2", "#submittedAtc3", "#stuPreferredDeptc3", "#coverletterc3", "#stuNamem2c1", "#stuNumm2c1", "#stuProgm2c1", "#stuCompanym2c2", "#stuWorkSetupm2c2", "#stuSlotsm2c2"].forEach((selector) => {
    $(selector).text("N/A");
  });

  $("#stuProg").text("N/A - N/A");
  $("#requirementsStatusc4").empty().append('<p class="text-muted">No requirement information available.</p>');
  $("#returnBtn, #approveBtn, #endorseBtn, #startBtn, #rejectBtn").addClass("d-none");
  $("#ReviewModal, #ApproveModal, #EndorseModal, #StartModal, #ReturnModal, #RejectModal").attr("data-application-uuid", "");
  $("#approvalNote, #endorsementNote, #startNote, #startDate, #returnReason, #rejectionReason").val("");
}

function renderRequirementList(requirements = {}) {
  const order = [
    ["medical_certificate", "Medical Certificate"],
    ["parental_consent", "Parental Consent"],
    ["insurance", "Insurance"],
    ["nbi_clearance", "NBI Clearance"],
    ["resume", "Resume"],
    ["guardian_form", "Guardian Form"],
  ];

  const statusIcons = {
    not_submitted: { bg: "bg-secondary-subtle", text: "text-secondary-emphasis", icon: "bi-x-lg" },
    submitted: { bg: "bg-warning-subtle", text: "text-warning-emphasis", icon: "bi-hourglass-split" },
    under_review: { bg: "bg-info-subtle", text: "text-info-emphasis", icon: "bi-search" },
    approved: { bg: "bg-success-subtle", text: "text-success-emphasis", icon: "bi-file-earmark-check" },
    returned: { bg: "bg-danger-subtle", text: "text-danger-emphasis", icon: "bi-arrow-counterclockwise" },
  };

  const container = $("#requirementsStatusc4");
  container.empty();

  if (!requirements || typeof requirements !== "object") {
    container.append('<p class="text-muted">No requirement information available.</p>');
    return;
  }

  order.forEach(([key, label]) => {
    const status = requirements[key] || "not_submitted";
    const icon = statusIcons[status] || statusIcons.not_submitted;
    container.append(`
      <div class="col">
        <div class="d-flex flex-column align-items-center gap-2">
          <div class="rounded-circle ${icon.bg} ${icon.text} d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px;">
            <i class="bi ${icon.icon} fs-5"></i>
          </div>
          <p class="mb-0 small fw-medium text-muted text-center">${label}</p>
        </div>
      </div>
    `);
  });
}

function openApplicationDetails(application) {
  const cardOne = application.card_one || {};
  const cardTwo = application.card_two || {};
  const cardThree = application.card_three || {};
  const cardFour = application.card_four || {};

  $("#stuName").text(cardOne.student_name || "N/A");
  $("#stuNum").text(cardOne.student_No || "N/A");
  $("#stuProg").text(`${cardOne.program || "N/A"} - ${cardOne.course_Section || "N/A"}`);

  $("#stuNamec1").text(cardOne.student_name || "N/A");
  $("#stuNumc1").text(cardOne.student_No || "N/A");
  $("#stuProgc1").text(cardOne.program || "N/A");
  $("#stuSectionc1").text(cardOne.course_Section || "N/A");
  $("#stuMobilec1").text(cardOne.mobile || "N/A");
  $("#stuEmailc1").text(cardOne.email || "N/A");

  $("#stuCompanyc2").text(cardTwo.company_name || "N/A");
  $("#stuIndustryc2").text(cardTwo.industry || "N/A");
  $("#stuLocationc2").text(cardTwo.city ? `${cardTwo.city}, Philippines` : "N/A");
  $("#stuWorkSetupc2").text(cardTwo.work_setup || "N/A");
  $("#stuSlotsc2").text(cardTwo.slots_info || "N/A");
  $("#stuAcceptsc2").text(Array.isArray(cardTwo.accepted_programs) && cardTwo.accepted_programs.length ? cardTwo.accepted_programs.join(", ") : "N/A");

  $("#submittedAtc3").text(cardThree.submitted_at || "N/A");
  $("#stuPreferredDeptc3").text(cardThree.preferred_dept || "N/A");
  $("#coverletterc3").text(cardThree.cover_letter || "N/A");

  renderRequirementList(cardFour.requirements || application.requirements || {});

  $("#returnBtn").toggleClass("d-none", !application.can_return);
  $("#approveBtn").toggleClass("d-none", !application.can_approve);
  $("#endorseBtn").toggleClass("d-none", !application.can_endorse);
  $("#startBtn").toggleClass("d-none", !application.can_confirm_start);
  $("#rejectBtn").toggleClass("d-none", !application.can_reject);

  $("#ReviewModal, #ApproveModal, #EndorseModal, #StartModal, #ReturnModal, #RejectModal").attr("data-application-uuid", application.uuid || "");

  $("#stuNamem2c1").text(cardOne.student_name || "N/A");
  $("#stuNumm2c1").text(cardOne.student_No || "N/A");
  $("#stuProgm2c1").text(cardOne.program || "N/A");
  $("#stuCompanym2c2").text(cardTwo.company_name || "N/A");
  $("#stuWorkSetupm2c2").text(cardTwo.work_setup || "N/A");
  $("#stuSlotsm2c2").text(cardTwo.slots_info || "N/A");
}

function getApplications(filter = "all") {
  currentFilter = filter;

  const statuses = filter === "all"
    ? ["pending", "approved", "endorsed", "active", "needs_revision", "rejected", "withdrawn"]
    : [filter];

  $("#applicationsList").html(getNoDataState("Loading applications..."));

  $.ajax({
    url: APPLICATION_ENDPOINTS.getApplications,
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: $("meta[name='csrf-token']").attr("content") || "",
      action: "get_applications",
      status: statuses,
    },
    success: function (response) {
      if (response.status !== "success") {
        applicationsCache = [];
        renderApplications([], response.message || "No applications found.");
        ToastVersion(swalTheme, response.message || "Unable to load applications.", "error", 3000, "top-end", "8");
        return;
      }

      applicationsCache = Array.isArray(response.data) ? response.data : [];
      $("#AYshoolyear").text(response.batch?.label || "");
      applySearchFilter();

      const counts = response.status_counts || {};
      $("#filterAllBadge").text((counts.pending || 0) + (counts.approved || 0) + (counts.endorsed || 0) + (counts.active || 0) + (counts.needs_revision || 0) + (counts.rejected || 0) + (counts.withdrawn || 0));
      $("#filterPendingBadge").text(counts.pending || 0).toggleClass("d-none", (counts.pending || 0) === 0);
      $("#filterNeedRevisionsBadge").text(counts.needs_revision || 0).toggleClass("d-none", (counts.needs_revision || 0) === 0);
      $("#filterApprovedBadge").text(counts.approved || 0).toggleClass("d-none", (counts.approved || 0) === 0);
      $("#filterRejectedBadge").text(counts.rejected || 0).toggleClass("d-none", (counts.rejected || 0) === 0);
      $("#filterWithdrawnBadge").text(counts.withdrawn || 0).toggleClass("d-none", (counts.withdrawn || 0) === 0);
    },
    error: function (xhr, status) {
      applicationsCache = [];
      renderApplications([], "No applications found.");
      ToastVersion(
        swalTheme,
        status === "timeout" ? "Request timed out. Please try again." : "An error occurred while fetching applications.",
        "error",
        3000,
        "top-end",
        "8",
      );
    },
  });
}

function submitApplicationAction(endpointUrl, buttonSelector, noteSelector, modalSelector, buttonText) {
  const applicationUuid = $(modalSelector).attr("data-application-uuid");
  const note = $(noteSelector).val().trim();

  if (!applicationUuid) {
    ToastVersion(swalTheme, "Application ID is missing. Please try again.", "error", 3000, "top-end", "8");
    return;
  }

  $.ajax({
    url: endpointUrl,
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: $("meta[name='csrf-token']").attr("content") || "",
      application_uuid: applicationUuid,
      note,
    },
    beforeSend: function () {
      $(buttonSelector).prop("disabled", true).text("Processing...");
    },
    success: function (response) {
      $(buttonSelector).prop("disabled", false).text(buttonText);
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message || buttonText, "success", 3000, "top-end", "8");
        clearApplicationDetails();
        getApplications(currentFilter);
        $(modalSelector).modal("hide");
      } else {
        ToastVersion(swalTheme, response.message || "Action failed.", "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status) {
      $(buttonSelector).prop("disabled", false).text(buttonText);
      ToastVersion(
        swalTheme,
        status === "timeout" ? "Request timed out. Please try again." : "An error occurred. Please try again.",
        "error",
        3000,
        "top-end",
        "8",
      );
    },
  });
}

function submitStartConfirmation() {
  const applicationUuid = $("#StartModal").attr("data-application-uuid");
  const startDate = $("#startDate").val().trim();
  const note = $("#startNote").val().trim();

  if (!applicationUuid) {
    ToastVersion(swalTheme, "Application ID is missing. Please try again.", "error", 3000, "top-end", "8");
    return;
  }

  if (!startDate) {
    ToastVersion(swalTheme, "Please select the official start date.", "error", 3000, "top-end", "8");
    return;
  }

  $.ajax({
    url: APPLICATION_ENDPOINTS.confirmStart,
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: $("meta[name='csrf-token']").attr("content") || "",
      application_uuid: applicationUuid,
      start_date: startDate,
      note,
    },
    beforeSend: function () {
      $("#confirmStartBtn").prop("disabled", true).text("Processing...");
    },
    success: function (response) {
      $("#confirmStartBtn").prop("disabled", false).text("Confirm Start");
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message || "OJT start confirmed.", "success", 3000, "top-end", "8");
        clearApplicationDetails();
        getApplications(currentFilter);
        $("#StartModal").modal("hide");
      } else {
        ToastVersion(swalTheme, response.message || "Action failed.", "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status) {
      $("#confirmStartBtn").prop("disabled", false).text("Confirm Start");
      ToastVersion(
        swalTheme,
        status === "timeout" ? "Request timed out. Please try again." : "An error occurred. Please try again.",
        "error",
        3000,
        "top-end",
        "8",
      );
    },
  });
}

$(document).ready(function () {
  getApplications();

  $("#applicationSearchInput").on("input", function () {
    currentSearchTerm = $(this).val();
    applySearchFilter();
  });

  $("#filterAllBtn").on("click", function () { getApplications("all"); });
  $("#filterPendingBtn").on("click", function () { if ($("#filterPendingBadge").text() !== "0") getApplications("pending"); });
  $("#filterNeedRevisionsBtn").on("click", function () { if ($("#filterNeedRevisionsBadge").text() !== "0") getApplications("needs_revision"); });
  $("#filterApprovedBtn").on("click", function () { if ($("#filterApprovedBadge").text() !== "0") getApplications("approved"); });
  $("#filterRejectedBtn").on("click", function () { if ($("#filterRejectedBadge").text() !== "0") getApplications("rejected"); });
  $("#filterWithdrawnBtn").on("click", function () { if ($("#filterWithdrawnBadge").text() !== "0") getApplications("withdrawn"); });

  $("#applicationsList").on("click", ".view-application-btn", function () {
    const application = getApplicationByUuid($(this).data("uuid"));
    if (!application) {
      ToastVersion(swalTheme, "Application details are unavailable.", "error", 3000, "top-end", "8");
      return;
    }
    clearApplicationDetails();
    openApplicationDetails(application);
  });

  $("#confirmApproveBtn").on("click", function () {
    submitApplicationAction(APPLICATION_ENDPOINTS.approveApplication, "#confirmApproveBtn", "#approvalNote", "#ApproveModal", "Confirm Approval");
  });

  $("#confirmReturnBtn").on("click", function () {
    submitApplicationAction(APPLICATION_ENDPOINTS.returnApplication, "#confirmReturnBtn", "#returnReason", "#ReturnModal", "Return for Revision");
  });

  $("#confirmEndorseBtn").on("click", function () {
    submitApplicationAction(APPLICATION_ENDPOINTS.endorseApplication, "#confirmEndorseBtn", "#endorsementNote", "#EndorseModal", "Issue Endorsement");
  });

  $("#confirmStartBtn").on("click", function () {
    submitStartConfirmation();
  });

  $("#confirmRejectBtn").on("click", function () {
    submitApplicationAction(APPLICATION_ENDPOINTS.rejectApplication, "#confirmRejectBtn", "#rejectionReason", "#RejectModal", "Reject Application");
  });
});