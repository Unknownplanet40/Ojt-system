import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
let LetApplicationListToLoad = false;
AOS.init();

function canStudentApply() {
  $.ajax({
    url: "../../../Assets/api/requirements_functions",
    method: "POST",
    data: {
      action: "check_can_apply",
    },
    success: function (response) {
      if (response.status === "success") {
        let showModal = response.showModal ?? false;
        $("#currentyearSem").text(`${response.active_batch.semester} Semester`);
        $("#currentSchoolYear").text(`S.Y. ${response.active_batch.school_year}`);
        $("#currentSemesterTextss").text(`${response.active_batch.semester} Semester`);
        $("#currentAcademicYearTextss").text(`S.Y. ${response.active_batch.school_year}`);
        $("#currentSemesterText").text(`${response.active_batch.semester} Semester`);
        $("#currentAcademicYearText").text(`S.Y. ${response.active_batch.school_year}`);

        $("#applyNowBtn").toggleClass("disabled", !response.can_apply);
        $("#applyNowBtn").toggleClass("d-none", !response.can_apply);
        const iconClasses = {
          not_submitted: "bi-x-circle-fill text-secondary",
          submitted: "bi-hourglass-split text-warning",
          under_review: "bi-hourglass-split text-warning",
          approved: "bi-check-circle-fill text-success",
          returned: "bi-x-circle-fill text-danger",
        };

        const statusColors = {
          not_submitted: "bg-secondary-subtle text-secondary-emphasis",
          submitted: "bg-warning-subtle text-warning",
          under_review: "bg-warning-subtle text-warning",
          approved: "bg-success-subtle text-success",
          returned: "bg-danger-subtle text-danger",
        };

        const correspondingIDs = {
          resume: {
            status: "#resumeStatus",
            icon: "#resumeIcon",
          },
          insurance: {
            status: "#paiStatus",
            icon: "#paiIcon",
          },
          parental_consent: {
            status: "#waiverStatus",
            icon: "#waiverIcon",
          },
          guardian_form: {
            status: "#guardianInfoStatus",
            icon: "#guardianInfoIcon",
          },
          medical_certificate: {
            status: "#medicalCertStatus",
            icon: "#medicalCertIcon",
          },
          nbi_clearance: {
            status: "#nbiStatus",
            icon: "#nbiIcon",
          },
        };

        if (!response.can_apply && Array.isArray(response.details)) {
          $("#IncompleteRequirementsModal").modal("show");
          response.details.forEach((req) => {
            const mapping = correspondingIDs[req.req_type];
            if (!mapping) return;

            const statusBadge = mapping.status;
            const statusClass = statusColors[req.status] || statusColors["not_submitted"];
            const iconClass = iconClasses[req.status] || iconClasses["not_submitted"];

            $(statusBadge)
              .removeClass("bg-secondary-subtle text-secondary-emphasis bg-warning-subtle text-warning bg-success-subtle text-success bg-danger-subtle text-danger")
              .addClass(statusClass)
              .text(req.status_label || "Not submitted");

            $(mapping.icon).removeClass("bi-x-circle-fill bi-hourglass-split bi-check-circle-fill text-warning text-danger text-success").addClass(iconClass);
          });
        } else {
          fetchcompanylist();
          if (showModal) {
            $("#ApplyFormsModal").modal("show");
          }

          if (response.application_status != null && response.application_status != undefined && response.application_status != "") {
            $("#noApplicationsContainer").addClass("d-none");
            $("#applicationStatusContainer").removeClass("d-none");
            $("#applicationDetailsContainer").removeClass("d-none");
            ApplicationStatusandDetails();
          } else {
            $("#noApplicationsContainer").removeClass("d-none");
            $("#applicationStatusContainer").addClass("d-none");
            $("#applicationDetailsContainer").addClass("d-none");
          }
        }
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      ToastVersion(swalTheme, "An error occurred while checking application status.", "error", 3000, "top-end", "8");
    },
  });
}

function fetchcompanylist() {
  const companyListContainer = $("#companyList");
  companyListContainer.empty();

  const loadingSpinner = $(`<div class="d-flex justify-content-center align-items-center" style="height: 200px;">
        <div class="spinner-border text-success-emphasis rounded-pill" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>`);
  companyListContainer.append(loadingSpinner);

  $.ajax({
    url: "../../../Assets/api/application_functions",
    method: "POST",
    data: {
      action: "fetch_company_list",
    },
    success: function (response) {
      companyListContainer.empty();
      if (response.status === "success") {
        if (response.data.length === 0) {
          const noDataMessage = $(`<div class="alert alert-info text-center" role="alert">No companies available at the moment. Please check back later.</div>`);
          companyListContainer.append(noDataMessage);
        } else {
          response.data.forEach((company) => {
            const MoaBadges = [
              { status: "valid", label: "Valid", classes: "bg-success-subtle text-success border border-success-subtle" },
              { status: "expiring", label: "Expiring Soon", classes: "bg-warning-subtle text-warning border border-warning-subtle" },
              { status: "expired", label: "Expired", classes: "bg-danger-subtle text-danger border border-danger-subtle" },
              { status: "none", label: "No MOA", classes: "bg-secondary-subtle text-secondary border border-secondary-subtle" },
            ];

            const companyCard = $(`
              <div class="col-12">
                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 h-100 comcard" style="--blur-lvl: 5; cursor: pointer;" data-company-id="${company.uuid}" id="company-${company.uuid}">
                  <div class="card-body p-3 p-sm-4">
                    <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                      <div class="flex-grow-1 min-w-0">
                        <h5 class="mb-1 fw-semibold text-body text-break">${company.name}</h5>
                        <p class="mb-0 text-muted small">${company.industry} &middot; ${company.city}</p>
                      </div>
                      <div class="ms-md-auto">
                        <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-medium text-nowrap">
                          ${company.remaining_slots} slots left
                        </span>
                        ${MoaBadges.find((badge) => badge.status === company.moa_status) ? `<small class="badge ${MoaBadges.find((badge) => badge.status === company.moa_status).classes} border rounded-pill px-3 py-2 fw-normal">${MoaBadges.find((badge) => badge.status === company.moa_status).label}</small>` : ""}
                      </div>
                    </div>
                    <div class="mt-3 pt-3 border-top border-secondary-subtle">
                      <div class="d-flex flex-wrap gap-2">
                        ${company.accepted_programs
                          .split(",")
                          .map((program) => `<small class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">${program.trim()}</small>`)
                          .join("")}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            `);
            companyListContainer.append(companyCard);

            $(`#company-${company.uuid}`).on("click", function () {
              $(".comcard").removeClass("selected-card");
              $(this).addClass("selected-card");
              $("#step-2").attr("data-selected-company", company.uuid);
              $("#selectedCompanyName").text(company.name);
              $("#industryInfo").text(company.industry);
              $("#locationInfo").text(company.city);
              $("#confirmCompanyName").text(company.name);
              $("#confirmCompanyMeta").text(`${company.industry} &middot; ${company.city}`);
            });
          });
        }
      } else {
        const errorMessage = $(`<div class="alert alert-danger text-center" role="alert">${response.message}</div>`);
        companyListContainer.append(errorMessage);
      }
    },
    error: function (xhr, status, error) {
      companyListContainer.empty();
      const errorMessage = $(`<div class="alert alert-danger text-center" role="alert">An error occurred while fetching the company list.</div>`);
      companyListContainer.append(errorMessage);
    },
  });
}

function ApplicationStatusandDetails() {
  $.ajax({
    url: "../../../Assets/api/application_functions",
    method: "POST",
    data: {
      action: "get_application_status_details",
    },
    success: function (response) {
      if (response.status === "success") {
        const statusColors = {
          pending: { badge: "bg-warning-subtle text-warning border border-warning-subtle", icon: "bi-hourglass-split text-warning", cicle: "bg-warning-subtle" },
          under_review: { badge: "bg-warning-subtle text-warning border border-warning-subtle", icon: "bi-hourglass-split text-warning", cicle: "bg-warning-subtle" },
          approved: { badge: "bg-success-subtle text-success border border-success-subtle", icon: "bi-check-circle-fill text-success", cicle: "bg-success-subtle" },
          returned: { badge: "bg-danger-subtle text-danger border border-danger-subtle", icon: "bi-x-circle-fill text-danger", cicle: "bg-danger-subtle" },
        };

        if (Array.isArray(response.data.status_log)) {
          const timelineContainer = $("#applicationStatusTimeline");
          timelineContainer.empty();
          response.data.status_log.forEach((log) => {
            const notItemEnd = $(`
              <div class="d-flex align-items-start gap-3">
                <div class="d-flex flex-column align-items-center flex-shrink-0">
                  <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold shadow-sm border ${statusColors[log.to_status] ? statusColors[log.to_status].cicle : "text-bg-secondary-subtle"}" style="width: 42px; height: 42px; font-size: 1rem;">
                    <i class="bi ${statusColors[log.to_status] ? statusColors[log.to_status].icon : "bi-x-circle-fill text-secondary"}"></i>
                  </div>
                  <span class="bg-secondary-subtle rounded-pill mt-2 d-none d-sm-block" style="width: 2px; min-height: 28px;"></span>
                </div>
                <div class="flex-grow-1 pt-1 pb-2">
                  <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mb-1">
                    <h6 class="mb-0 fw-semibold text-body text-break">${log.note}</h6>
                    <span class="badge rounded-pill ${statusColors[log.to_status] ? statusColors[log.to_status].badge : "bg-secondary-subtle text-secondary border border-secondary-subtle"} px-2 py-1 fw-medium">${log.to_status_label || log.to_status}</span>
                  </div>
                  <small class="text-muted d-block">${log.changed_by} (${log.changed_by_role}) &middot; ${log.date}</small>
                </div>
              </div>
            `);
            const itemEnd = $(`
              <div class="d-flex align-items-start gap-3">
                <div class="d-flex flex-column align-items-center flex-shrink-0">
                  <div class="d-flex align-items-center justify-content-center rounded-circle fw-bold shadow-sm border ${statusColors[log.to_status] ? statusColors[log.to_status].cicle : "text-bg-secondary-subtle"}" style="width: 42px; height: 42px; font-size: 1rem;">
                    <i class="bi ${statusColors[log.to_status] ? statusColors[log.to_status].icon : "bi-x-circle-fill text-secondary"}"></i>
                  </div>
                </div>
                <div class="flex-grow-1 pt-1 pb-2">
                  <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 mb-1">
                    <h6 class="mb-0 fw-semibold text-body text-break">${log.note}</h6>
                    <span class="badge rounded-pill ${statusColors[log.to_status] ? statusColors[log.to_status].badge : "bg-secondary-subtle text-secondary border border-secondary-subtle"} px-2 py-1 fw-medium">${log.to_status_label || log.to_status}</span>
                  </div>
                  <small class="text-muted d-block">${log.changed_by} (${log.changed_by_role}) &middot; ${log.date}</small>
                </div>
              </div>
            `);

            if (log === response.data.status_log[response.data.status_log.length - 1]) {
              timelineContainer.append(itemEnd);
            } else {
              timelineContainer.append(notItemEnd);
            }
          });
        }

        $("#detailCompanyName").text(response.data.application.company_name);
        $("#detailIndustry").text(response.data.application.industry);
        $("#detailLocation").text(response.data.application.city);
        $("#detailWorkArrangement").text(response.data.application.work_setup);
        $("#detailDepartmentPreference").text(response.data.application.preferred_dept || "—");
        $("#detailSubmitted").text(response.data.application.submitted_at);

        const currentStatus = response.data.application.status;
        $("#statusIcon")
          .removeClass("bi-x-circle-fill text-secondary bi-hourglass-split text-warning bi-check-circle-fill text-success bi-x-circle-fill text-danger")
          .addClass(statusColors[currentStatus] ? statusColors[currentStatus].icon : "bi-x-circle-fill text-secondary");
        $("#statusText").text(response.data.application.status_label || currentStatus);
        $("#statusLastUpdated").text(response.data.application.reviewed_at ? `Last updated: ${response.data.application.reviewed_at}` : `Submitted on: ${response.data.application.submitted_at}`);
        $("#currentStatusBadge")
          .removeClass(
            "bg-secondary-subtle text-secondary border border-secondary-subtle bg-warning-subtle text-warning border border-warning-subtle bg-success-subtle text-success border border-success-subtle bg-danger-subtle text-danger border border-danger-subtle",
          )
          .addClass(statusColors[currentStatus] ? statusColors[currentStatus].badge : "bg-secondary-subtle text-secondary border border-secondary-subtle")
          .text(response.data.application.status_label || currentStatus);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      ToastVersion(swalTheme, "An error occurred while fetching application details.", "error", 3000, "top-end", "8");
    },
  });
}

$(document).ready(function () {
  canStudentApply();
  $("#dashboardRefreshBtn").on("click", function () {
    canStudentApply();
    $("#dashboardContent").stop(true, true).fadeTo(500, 0.5).fadeTo(500, 1);
  });

  $("#applyNowBtn").on("click", function () {
    canStudentApply();
  });

  $("#proceedToDetailsBtn").on("click", function () {
    const selectedCompany = $("#step-2").attr("data-selected-company");
    if (!selectedCompany) {
      ToastVersion(swalTheme, "Please select a company to proceed.", "warning", 3000, "top-end", "8");
      return;
    }
    $("#step-1").addClass("d-none");
    $("#step-2").removeClass("d-none");
    $("#step-3").addClass("d-none");

    $("#step1ProgressBar").css("width", "100%").attr("aria-valuenow", "100");
    $("#step2Indicator").removeClass("bg-secondary-subtle text-secondary").addClass("text-bg-success");
  });

  $("#backToCompanySelectionBtn").on("click", function () {
    $("#step-1").removeClass("d-none");
    $("#step-2").addClass("d-none");
    $("#step-3").addClass("d-none");
    $("#step1ProgressBar").css("width", "0%").attr("aria-valuenow", "0");
    $("#step2Indicator").removeClass("text-bg-success").addClass("bg-secondary-subtle text-secondary");
  });

  $("#submitApplicationBtn").on("click", function () {
    if ($("#coverLetter").val() === "") {
      ToastVersion(swalTheme, "Please enter a cover letter.", "warning", 3000, "top-end", "8");
      return;
    }

    if ($("#coverLetter").val().length < 512) {
      ToastVersion(swalTheme, "Cover letter must be at least 512 characters long.", "warning", 3000, "top-end", "8");
      return;
    }

    $("#confirmPreferredDepartment").text($("#preferredDepartment").val() || "—");
    $("#confirmCoverLetter").text($("#coverLetter").val() || "—");

    $("#step-2").addClass("d-none");
    $("#step-3").removeClass("d-none");
    $("#step2ProgressBar").css("width", "100%").attr("aria-valuenow", "100");
    $("#step3Indicator").removeClass("bg-secondary-subtle text-secondary").addClass("text-bg-success");
  });

  $("#backToDetailsBtn").on("click", function () {
    $("#step-2").removeClass("d-none");
    $("#step-3").addClass("d-none");
    $("#step2ProgressBar").css("width", "0%").attr("aria-valuenow", "0");
    $("#step3Indicator").removeClass("text-bg-success").addClass("bg-secondary-subtle text-secondary");
  });

  $("#cancelApplicationBtn").on("click", function () {
    $("#step-1").removeClass("d-none");
    $("#step-2").addClass("d-none").attr("data-selected-company", "");
    $("#step-3").addClass("d-none");
    $("#step1ProgressBar").css("width", "0%").attr("aria-valuenow", "0");
    $("#step2ProgressBar").css("width", "0%").attr("aria-valuenow", "0");
    $("#step2Indicator").removeClass("text-bg-success").addClass("bg-secondary-subtle text-secondary");
    $("#step3Indicator").removeClass("text-bg-success").addClass("bg-secondary-subtle text-secondary");
  });

  $("#finalSubmitApplicationBtn").on("click", function () {
    const selectedCompany = $("#step-2").attr("data-selected-company");
    const PreferredDepartment = $("#preferredDepartment").val();
    const CoverLetter = $("#coverLetter").val();
    if (!selectedCompany) {
      ToastVersion(swalTheme, "No company selected. Please start your application again.", "error", 3000, "top-end", "8");
      $("#cancelApplicationBtn").trigger("click");
      return;
    }

    $.ajax({
      url: "../../../Assets/api/application_functions",
      method: "POST",
      data: {
        action: "submit_application",
        company_id: selectedCompany,
        preferred_department: PreferredDepartment,
        cover_letter: CoverLetter,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          $("#ApplyFormsModal").modal("hide");
          canStudentApply();
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "An error occurred while submitting your application.", "error", 3000, "top-end", "8");
      },
    });
  });
});
