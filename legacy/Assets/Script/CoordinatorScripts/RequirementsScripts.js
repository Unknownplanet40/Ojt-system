import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { DashboardEsentialElements } from "../DashboardScripts/CoordinatorDashboardScript.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

function ModalContent(data) {
  $("#modalDocType").text(data.label || "Document");
  $("#modalStudentName").text(data.student_full_name || "Student");
  $("#modalStudentDocumentName").text(data.file_name || "No file submitted");
  $("#modalStudentDocumentStatus").text(data.status_label || "No status");
  $("#documentFileName").text(data.file_name || "No file submitted");
  $("#documentdate").text(data.submitted_at ? `Submitted on: ${data.submitted_at}` : "No submission date");

  $("#viewDocumentBtn")
    .off("click")
    .on("click", function () {
      if (data.file_path) {
        const fileURL = `../../../file_serve?uuid=${data.uuid}&for=coordinatorView`;
        window.open(fileURL, "_blank");
      } else {
        ToastVersion(swalTheme, "No file available to view.", "info", 3000, "top-end", 8);
      }
    });
  $("#downloadDocumentBtn")
    .off("click")
    .on("click", function () {
      if (data.file_path) {
        const fileURL = `../../../file_serve?uuid=${data.uuid}&for=coordinatorView&action=download`;
        const link = document.createElement("a");
        link.href = fileURL;
        link.download = "";
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
      } else {
        ToastVersion(swalTheme, "No file available to download.", "info", 3000, "top-end", 8);
      }
    });
  $("#studentNotesContent").text(data.student_note || "No notes provided by the student.");
  if ($("#reviewActionButtonsContainer").length) {
    $("#reviewActionButtonsContainer").attr("data-document-uuid", data.uuid || "");
    $("#reviewActionButtonsContainer").attr("data-student-uuid", data.student_uuid || "");
  }
}

function getRequirementDetails(studentUuid) {
  $.ajax({
    url: `../../../Assets/api/requirements_functions`,
    method: "POST",
    data: {
      action: "get_requirement_details",
      studentUuid: studentUuid,
    },
    dataType: "json",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const details = response.data || {};
        details.student_uuid = studentUuid;
        $("#requirementReviewModal").modal("show");
        ModalContent(details);
      } else {
        ToastVersion(swalTheme, response.message || "Failed to fetch requirement details.", "error", 10000, "top-end", 8);
      }
    },
    error: function () {
      ToastVersion(swalTheme, "An error occurred while fetching requirement details.", "error", 3000, "top-end", 8);
    },
  });
}

function fetchStudents(userUuid) {
  const requirementsContainer = $("#requirementsContainer");
  requirementsContainer.empty();

  $.ajax({
    url: `../../../Assets/api/requirements_functions`,
    method: "POST",
    data: {
      action: "fetch_students",
      userUuid: userUuid,
    },
    dataType: "json",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const students = Array.isArray(response.data) ? response.data : [];
        const activeBatch = response.active_batch || {};

        $("#CurrentBatch").text(activeBatch.school_year && activeBatch.semester ? `AY ${activeBatch.school_year} - ${activeBatch.semester} Semester` : "No active batch");
        $("#StudentCount").text(response.student_count ? `Total Students: ${response.student_count}` : "No students found");

        if (students.length === 0) {
          requirementsContainer.append(`
            <div class="col">
              <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
                <div class="card-body text-center">
                  <p class="mb-0">No students found.</p>
                </div>
              </div>
            </div>
          `);
          return;
        }

        students.forEach((student) => {
          const total = Number(student.total || 0);
          const approved = Number(student.approved || 0);
          const pending = Number(student.pending || 0);
          const returned = Number(student.returned || 0);
          const notSubmitted = Math.max(total - (approved + pending + returned), 0);
          const percentage = Number(student.percentage || 0);

          const requirementStatus = student.can_apply ? "Ready to Apply" : `Not Ready (${percentage}%)`;
          const badgeClass = student.can_apply
            ? "bg-success-subtle text-success-emphasis"
            : pending > 0
              ? "bg-warning-subtle text-warning-emphasis"
              : returned > 0
                ? "bg-danger-subtle text-danger-emphasis"
                : "bg-secondary-subtle text-secondary-emphasis";

          const dot = (cls) => `<span class="${cls}">&#11044;</span>`;

          const dotsHtml = [
            [notSubmitted, "text-secondary-emphasis"],
            [approved, "text-success-emphasis"],
            [pending, "text-warning-emphasis"],
            [returned, "text-danger-emphasis"],
          ]
            .map(([count, cls]) => (count > 0 ? dot(cls).repeat(count) : ""))
            .join("");

          requirementsContainer.append(`
            <div class="col">
              <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4" style="--blur-lvl: <?= $opacitylvl ?>">
              <div class="card-body">
                <div class="hstack">
                <img
                  src="https://placehold.co/64x64/483a0f/c7993d/png?text=${student.initials}&font=poppins"
                  alt="profile picture"
                  class="rounded-circle m-2 mx-3 me-3"
                  style="width: 32px; height: 32px;"
                >
                <div class="vstack">
                  <h6 class="card-title mb-0">${student.full_name}</h6>
                  <p class="card-text mb-0">${student.program_code} - ${student.year_label}</p>
                  <div class="hstack gap-1">
                    ${dotsHtml || `<span class="text-secondary-emphasis">&#11044;</span>`.repeat(6)}
                  </div>
                </div>
                <span class="badge ${badgeClass} rounded-pill ms-auto" id="requirementStatus">
                  ${requirementStatus}
                </span>
                <button
                  class="btn btn-sm btn-outline-secondary text-light ms-3 px-4 py-2 rounded-2 ${total === 0 ? "disabled" : ""}"
                  id="reviewBtn-${student.student_uuid}" data-student-uuid="${student.student_uuid}">Review</button>
                </div>
              </div>
              </div>
              <hr>
            </div>
            `);

          if (student.pending === 0) {
              $(`#reviewBtn-${student.student_uuid}`).addClass("disabled btn-outline-dark d-none").removeClass("btn-outline-secondary").attr("disabled", true).text("No Pending");
            } else {
              $(`#reviewBtn-${student.student_uuid}`).removeClass("disabled btn-outline-dark d-none").addClass("btn-outline-secondary").attr("disabled", false).text("Review");
            }
          
          $(`#reviewBtn-${student.student_uuid}`).on("click", function () {
            const studentUuid = $(this).data("student-uuid");
            getRequirementDetails(studentUuid);
          });
        });
      } else {
        $("#requirementReviewModal").modal("hide");
        ToastVersion(swalTheme, response.message || "Failed to fetch students.", "error", 3000, "top-end", 8);
      }
    },
    error: function () {
      ToastVersion(swalTheme, "An error occurred while fetching students.", "error", 3000, "top-end", 8);
    },
  });
}

$(document).ready(function () {
  const userUuid = $("body").data("uuid");

  DashboardEsentialElements(userUuid);
  fetchStudents(userUuid);

  $("#closeModalBtn").on("click", function () {
    $("#modalDocType").text("Document Type");
    $("#modalStudentName").text("Student Name");
    $("#modalStudentDocumentName").text("Document Name");
    $("#modalStudentDocumentStatus").text("Status");
    $("#documentFileName").text("No file submitted");
    $("#documentdate").text("");
    $("#studentNotesContent").text("No notes provided by the student.");
    if ($("#reviewActionButtonsContainer").length) {
      $("#reviewActionButtonsContainer").removeAttr("data-document-uuid").removeAttr("data-student-uuid");
    }
    $("#viewDocumentBtn").off("click");
    $("#downloadDocumentBtn").off("click");
  });

  $("#approveBtn").on("click", function () {
    const documentUuid = $("#reviewActionButtonsContainer").data("document-uuid");
    const studentUuid = $("#reviewActionButtonsContainer").data("student-uuid");
    const coordinatorNote = $("#reviewNote").val().trim();

    if (!documentUuid || !studentUuid) {
      ToastVersion(swalTheme, "Missing document or student information.", "error", 3000, "top-end", 8);
      return;
    }
    const $approveButton = $(this);
    const isConfirmedProceed = $approveButton.data("confirmedProceed") === true;

    if (coordinatorNote.length > 0 && !isConfirmedProceed) {
      Swal.fire({
      theme: swalTheme,
      title: "Note will not be saved",
      text: "The note you entered will not be saved when approving the document. Do you want to proceed?",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Yes, approve",
      cancelButtonText: "No, go back",
      customClass: {
        popup: "bg-blur-5 bg-semi-transparent border-1 rounded-3",
        confirmButton: "btn btn-outline-secondary text-light px-4 py-2 rounded-2",
        cancelButton: "btn btn-outline-secondary text-light px-4 py-2 rounded-2",
      },
      didOpen: (popup) => {
        popup.classList.remove("bounce-in-top", "bounce-in-left", "bounce-in-right", "bounce-in-bottom", "bounce-in-fwd");
        void popup.offsetWidth;
        popup.classList.add("bounce-in-top");
      },
      }).then((result) => {
      if (result.isConfirmed) {
        $approveButton.data("confirmedProceed", true);
        $approveButton.trigger("click");
        $("#reviewNote").val("");
      }
      });

      return;
    }

    $approveButton.data("confirmedProceed", false);

    $.ajax({
      url: `../../../Assets/api/requirements_functions`,
      method: "POST",
      data: {
        action: "approve_document",
        documentUuid: documentUuid,
      },
      dataType: "json",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message || "Document approved successfully.", "success", 3000, "top-end", 8);
          fetchStudents(userUuid);
          $("#closeModalBtn").trigger("click");
          setTimeout(() => {
            $(`#reviewBtn-${studentUuid}`).trigger("click");
          }, 500);
        } else {
          ToastVersion(swalTheme, response.message || "Failed to approve document.", "error", 3000, "top-end", 8);
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred while approving the document.", "error", 3000, "top-end", 8);
      },
    });
  });

  $("#returnBtn").on("click", function () {
    const documentUuid = $("#reviewActionButtonsContainer").data("document-uuid");
    const studentUuid = $("#reviewActionButtonsContainer").data("student-uuid");
    const coordinatorNote = $("#reviewNote").val().trim();

    if (!documentUuid || !studentUuid) {
      ToastVersion(swalTheme, "Missing document or student information.", "error", 3000, "top-end", 8);
      return;
    }

    if (coordinatorNote.length === 0) {
      ToastVersion(swalTheme, "Please enter a note explaining the reason for returning the document.", "warning", 3000, "top-end", 8);
      return;
    }

    $.ajax({
      url: `../../../Assets/api/requirements_functions`,
      method: "POST",
      data: {
        action: "return_document",
        documentUuid: documentUuid,
        coordinatorNote: coordinatorNote,
      },
      dataType: "json",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message || "Document returned successfully.", "success", 3000, "top-end", 8);
          fetchStudents(userUuid);
          $("#closeModalBtn").trigger("click");
          setTimeout(() => {
            $(`#reviewBtn-${studentUuid}`).trigger("click");
          }, 500);
        } else {
          ToastVersion(swalTheme, response.message || "Failed to return document.", "error", 3000, "top-end", 8);
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred while returning the document.", "error", 3000, "top-end", 8);
      },
    });
  });
});
