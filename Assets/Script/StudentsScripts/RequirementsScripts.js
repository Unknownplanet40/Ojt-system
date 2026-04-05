import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

function setupFileUploadHandlerNS(Doc) {
  const fileInput = $(`#${Doc}FileInputNS`);
  const uploadArea = $(`#upload${Doc}AreaNS`);
  const selectedInfo = $(`#selected${Doc}InfoNS`);
  const selectedFileName = $(`#selected${Doc}FileNameNS`);
  const viewBtn = $(`#viewSelected${Doc}BtnNS`);
  const removeBtn = $(`#removeSelected${Doc}BtnNS`);

  fileInput.on("change", function () {
    const file = this.files && this.files[0] ? this.files[0] : null;
    if (file) {
      if (file.size > 5 * 1024 * 1024) {
        ToastVersion(swalTheme, "File size exceeds 5MB limit. Please choose a smaller file.", "error", 3000);
        fileInput.val("");
        return;
      }
      if (!file.name.toLowerCase().endsWith(".pdf")) {
        ToastVersion(swalTheme, "Invalid file type. Please select a PDF file.", "error", 3000);
        fileInput.val("");
        return;
      }
      selectedFileName.text(file.name);
      selectedInfo.removeClass("d-none");
      uploadArea.addClass("d-none");
      viewBtn.off("click").on("click", function () {
        const fileURL = URL.createObjectURL(file);
        window.open(fileURL, "_blank");
      });
      removeBtn.off("click").on("click", function () {
        fileInput.val("");
        selectedInfo.addClass("d-none");
        uploadArea.removeClass("d-none");
      });
    } else {
      selectedInfo.addClass("d-none");
      uploadArea.removeClass("d-none");
    }
  });
}

function updateProgressBar(submittedCount, totalCount) {
  if (totalCount === 0) {
    $("#overallProgressBar").css("width", "0%").attr("aria-valuenow", 0);
    $("#submittedCount").text(0);
    $("#totalCount").text(0);
    return;
  }

  if (submittedCount > totalCount) {
    submittedCount = totalCount;
  }

  const percentage = (submittedCount / totalCount) * 100;
  $("#overallProgressBar")
    .css("width", percentage + "%")
    .attr("aria-valuenow", percentage);
  $("#submittedCount").text(submittedCount);
  $("#totalCount").text(totalCount);
}

function ReturnRequrementStatus(status, maincontainerid, submittedCardId, notSubmittedCardId, Doc, data) {
  const Container = $(`#${maincontainerid}`);
  const submittedCard = $(`#${submittedCardId}`);
  const notSubmittedCard = $(`#${notSubmittedCardId}`);
  // submitted Card
  const labelS = $(`#${Doc}LabelS`);
  const descriptionS = $(`#${Doc}DescriptionS`);
  const DocStatus = $(`#${Doc}StatusS`);
  const fileNameS = $(`#${Doc}FileNameS`);
  const submittedDateS = $(`#${Doc}SubmittedDateS`);
  const viewBtnS = $(`#view${Doc}BtnS`);
  const studentNoteS = $(`#${Doc}StudentNoteS`);
  const studentNoteContentS = $(`#${Doc}StudentNoteContentS`);
  const coordinatorNoteS = $(`#${Doc}CoordinatorNoteS`);
  const coordinatorNoteContentS = $(`#${Doc}CoordinatorNoteContentS`);
  const uploadBtnS = $(`#upload${Doc}BtnS`);

  // not submitted card
  const labelNS = $(`#${Doc}LabelNS`);
  const descriptionNS = $(`#${Doc}DescriptionNS`);
  const uploadAreaNS = $(`#upload${Doc}AreaNS`);
  const fileInputNS = $(`#${Doc}FileInputNS`);
  const selectedInfoNS = $(`#selected${Doc}InfoNS`);
  const selectedFileNameNS = $(`#selected${Doc}FileNameNS`);
  const viewBtnNS = $(`#view${Doc}BtnNS`);
  const removeBtnNS = $(`#remove${Doc}BtnNS`);
  const noteInputNS = $(`#${Doc}NoteInputNS`);
  const submitBtnNS = $(`#submit${Doc}BtnNS`);
  const cancelBtnNS = $(`#Cancel${Doc}BtnNS`);

  const BadgeColors = {
    submitted: "bg-info-subtle text-info-emphasis",
    under_review: "bg-warning-subtle text-warning-emphasis",
    approved: "bg-success-subtle text-success-emphasis",
    returned: "bg-danger-subtle text-danger-emphasis",
    not_submitted: "bg-secondary-subtle text-secondary-emphasis",
  };

  const borderColors = {
    submitted: "border-info",
    under_review: "border-warning",
    approved: "border-success",
    returned: "border-danger",
    not_submitted: "border-secondary",
  };

  DocStatus.removeClass(Object.values(BadgeColors).join(" "));
  submittedCard.removeClass(Object.values(borderColors).join(" "));

  const badgeClass = BadgeColors[status] || "bg-secondary-subtle text-secondary-emphasis";
  const borderClass = borderColors[status] || "border-secondary";
  DocStatus.addClass(badgeClass).text(data.status_label);
  submittedCard.addClass(borderClass);

  if (!data.student_note) {
    studentNoteS.addClass("d-none");
  } else {
    studentNoteS.removeClass("d-none");
    studentNoteContentS.text(data.student_note || "No student note provided.");
  }

  if (!data.coordinator_note) {
    coordinatorNoteS.addClass("d-none");
  } else {
    coordinatorNoteS.removeClass("d-none");
    coordinatorNoteContentS.text(data.coordinator_note || "No coordinator note provided.");
  }

  uploadBtnS.off("click").on("click", function () {
    Container.attr("data-requirement-uuid", data.uuid);
    submittedCard.addClass("d-none");
    notSubmittedCard.removeClass("d-none");
    labelNS.text(data.label);
    descriptionNS.text(data.description);
    setupFileUploadHandlerNS(Doc);
    cancelBtnNS.removeClass("d-none");
  });

  cancelBtnNS.off("click").on("click", function () {
    Container.attr("data-requirement-uuid", data.uuid);
    notSubmittedCard.addClass("d-none");
    submittedCard.removeClass("d-none");
    cancelBtnNS.addClass("d-none");
  });

  if (status === "submitted" || status === "under_review" || status === "approved") {
    uploadBtnS.addClass("d-none");
  } else {
    uploadBtnS.removeClass("d-none");
  }

  if (status === "submitted" || status === "under_review" || status === "approved" || status === "returned") {
    Container.attr("data-requirement-uuid", data.uuid);
    notSubmittedCard.addClass("d-none");
    submittedCard.removeClass("d-none");
    labelS.text(data.label);
    descriptionS.text(data.description);
    if (data.file_name) {
      fileNameS.text(data.file_name);
      viewBtnS.off("click").on("click", function () {
        const RequirementUUID = Container.data("requirement-uuid");
        const fileURL = `../../../file_serve?uuid=${RequirementUUID}&for=studentView`;
        window.open(fileURL, "_blank");
      });
    } else {
      fileNameS.text("No file submitted");
      viewBtnS.off("click").on("click", function () {
        ToastVersion(swalTheme, "No file available to view.", "info", 3000);
      });
    }
    if (data.submitted_at) {
      const submittedDate = new Date(data.submitted_at);
      submittedDateS.text(submittedDate.toLocaleString());
    } else {
      submittedDateS.text("N/A");
    }
  } else if (status === "not_submitted") {
    Container.attr("data-requirement-uuid", data.uuid);
    submittedCard.addClass("d-none");
    notSubmittedCard.removeClass("d-none");
    labelNS.text(data.label);
    descriptionNS.text(data.description);
    setupFileUploadHandlerNS(Doc);
  } else {
    Container.attr("data-requirement-uuid", data.uuid);
    submittedCard.addClass("d-none");
    notSubmittedCard.removeClass("d-none");
    labelNS.text(data.label);
    descriptionNS.text(data.description);
    setupFileUploadHandlerNS(Doc);
  }
}

function getStudentRequirements(studentId) {
  if (!studentId) {
    ToastVersion(swalTheme, "Invalid student ID.", "error", 3000);
    return;
  }

  $.ajax({
    url: "../../../Assets/api/requirements_functions",
    method: "POST",
    data: {
      action: "get_requirements_status",
      studentId: studentId,
    },
    dataType: "json",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const requirements = response.data || [];
        let submittedCount = 0;
        const totalCount = requirements.length;
        requirements.forEach((req) => {
          if (req.status === "approved") {
            submittedCount++;
          }

          const IDlist = {
            resume: "Resume",
            parental_consent: "ParentConsent",
            insurance: "PersonalAccidentInsurance",
            guardian_form: "ParentalGuardianInfo",
            medical_certificate: "MedCert",
            nbi_clearance: "NbiClearance",
          };

          const baseId = IDlist[req.req_type];
          if (!baseId) return;

          const submittedContainerId = `Submitted${baseId}Card`;
          const notSubmittedContainerId = `NotSubmitted${baseId}Card`;
          const Container = `${baseId}Container`;
          ReturnRequrementStatus(req.status, Container, submittedContainerId, notSubmittedContainerId, baseId, req);
        });
        updateProgressBar(submittedCount, totalCount);
      } else {
        ToastVersion(swalTheme, response.message || "Failed to retrieve requirements status.", "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while retrieving requirements status. Please try again.", "error", 3000);
      }
    },
  });
}

function uploadRequrements(requirementId, file, note) {
  if (!requirementId || !file) {
    ToastVersion(swalTheme, "Missing required information for uploading requirement.", "error", 3000);
    return;
  }

  const formData = new FormData();
  formData.append("action", "upload_requirement");
  formData.append("requirementId", requirementId);
  formData.append("file", file);
  formData.append("note", note);

  $.ajax({
    url: "../../../Assets/api/requirements_functions",
    method: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    timeout: 10000,
    success: function (response) {
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message || "Requirement uploaded successfully.", "success", 3000);
        const studentId = $("body").data("uuid");
        getStudentRequirements(studentId);
      } else {
        ToastVersion(swalTheme, response.message || "Failed to upload requirement. Please try again.", "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while uploading requirement. Please try again.", "error", 3000);
      }
    },
  });
}

$(document).ready(function () {
  const studentId = $("body").data("uuid");
  getStudentRequirements(studentId);

  $("#dashboardRefreshBtn").on("click", function () {
    getStudentRequirements(studentId);
    $("#dashboardContent").stop(true, true).fadeTo(500, 0.5).fadeTo(500, 1);
  });

  $("#submitResumeBtnNS").on("click", function () {
    const requirementId = $("#ResumeContainer").data("requirement-uuid");
    const fileInput = $("#ResumeFileInputNS")[0];
    const noteInput = $("#ResumeNoteInputNS").val().trim();
    if (fileInput.files.length === 0) {
      ToastVersion(swalTheme, "Please select a file to upload.", "error", 3000);
      return;
    }
    const file = fileInput.files[0];
    uploadRequrements(requirementId, file, noteInput);
  });
});
