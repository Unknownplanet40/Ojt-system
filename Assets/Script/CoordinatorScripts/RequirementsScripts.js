import { ToastVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const ENDPOINTS = {
    getOverview: '../../../process/requirements/get_requirements_overview',
    getRequirements: '../../../process/requirements/get_requirements',
    approveRequirement: '../../../process/requirements/approve_requirement',
    returnRequirement: '../../../process/requirements/return_requirement',
};

let state = {
    selectedStudentUuid: null,
    selectedBatchUuid: null,
    selectedRequirement: null,
    studentRequirements: []
};

const loadingRow = `
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-secondary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Loading students...</p>
    </div>`;

const emptyRow = `
    <div class="col-12 text-center py-5">
        <i class="bi bi-people display-4 text-muted"></i>
        <p class="mt-2 text-muted">No students assigned to you in this batch.</p>
    </div>`;

function dotClass(status) {
    switch (status) {
        case 'approved': return 'text-success-emphasis';
        case 'submitted': return 'text-warning-emphasis';
        case 'returned': return 'text-danger-emphasis';
        default: return 'text-secondary-emphasis';
    }
}

function getBadgeStatus(student) {
    if (student.all_approved) {
        return { text: 'Ready to Apply', cls: 'bg-success-subtle text-success-emphasis' };
    }
    if (student.has_pending) {
        return { text: 'Pending Review', cls: 'bg-warning-subtle text-warning-emphasis' };
    }
    if (student.has_returned) {
        return { text: 'Returned Docs', cls: 'bg-danger-subtle text-danger-emphasis' };
    }
    return { text: 'Not Ready', cls: 'bg-secondary-subtle text-secondary-emphasis' };
}

function getOverview() {
    const container = $("#requirementsContainer");
    $.ajax({
        url: ENDPOINTS.getOverview,
        method: "POST",
        dataType: "json",
        data: { csrf_token: csrfToken },
        beforeSend: function () {
            container.html(loadingRow);
        },
        success: function (response) {
            if (response.status === "success") {
                renderStudents(response.overview);
                $("#StudentCount").text(`Total Students: ${response.total || 0}`);
                $("#CurrentBatch").text('Active Batch');
            } else {
                container.html(emptyRow);
            }
        },
        error: function (xhr, status, error) {
            container.html(emptyRow);
            Errors(xhr, status, error);
        }
    });
}

function renderStudents(students) {
    const container = $("#requirementsContainer");
    container.empty();

    if (!students || students.length === 0) {
        container.html(emptyRow);
        return;
    }

    students.forEach(student => {
        const statuses = student.doc_statuses || {};
        const badge = getBadgeStatus(student);
        const canReview = Number(student.submitted_count || 0) > 0;

        const dots = [
            statuses.resume,
            statuses.guardian_form,
            statuses.parental_consent,
            statuses.medical_certificate,
            statuses.insurance,
            statuses.nbi_clearance,
        ].map((s) => `<span class="${dotClass(s)}">&#11044;</span>`).join('');

        const studentRow = `
            <div class="col-12">
            <div class="card border-0 rounded-4 mb-3 shadow-sm bg-blur-5 bg-semi-transparent">
                <div class="card-body p-3 p-md-4">
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-start align-items-sm-center gap-3">
                    <div class="rounded-circle overflow-hidden border border-secondary-subtle flex-shrink-0 shadow-sm" style="width: 44px; height: 44px;">
                        <img src="https://placehold.co/64x64/483a0f/c7993d/png?text=${student.initials || 'ST'}&font=poppins" class="img-fluid" alt="${student.full_name}">
                    </div>

                    <div class="flex-grow-1 min-w-0">
                        <h6 class="mb-1 fw-semibold text-truncate">${student.full_name}</h6>
                        <small class="text-muted d-block">${student.program_code} - ${student.year_label}</small>
                    </div>

                    <div class="d-none d-md-flex align-items-center gap-1 ms-2" aria-label="Document status indicators">
                        ${dots}
                    </div>
                    </div>

                    <div class="d-flex d-md-none align-items-center gap-1 ps-1" aria-label="Document status indicators">
                    ${dots}
                    </div>

                    <div class="d-flex flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-sm-end gap-2 gap-sm-3 pt-1">
                    <span class="badge ${badge.cls} rounded-pill px-3 py-2 text-wrap text-center">${badge.text}</span>
                    <button class="btn btn-sm ${canReview ? 'btn-outline-secondary text-light' : 'btn-outline-dark text-secondary disabled'} rounded-3 js-review-btn px-3"
                        data-student-uuid="${student.student_uuid}" 
                        data-student-name="${student.full_name}"
                        ${canReview ? '' : 'disabled'}>
                        ${canReview ? `Review (${student.submitted_count})` : student.all_approved ? 'All Approved' : 'No Submissions'}
                    </button>
                    </div>
                </div>
                </div>
            </div>
            </div>
        `;
        container.append(studentRow);
    });

    $(".js-review-btn").on("click", function() {
        const studentUuid = $(this).data("student-uuid");
        const studentName = $(this).data("student-name");
        openReviewModal(studentUuid, studentName);
    });
}

function openReviewModal(studentUuid, studentName) {
    state.selectedStudentUuid = studentUuid;
    $.ajax({
        url: ENDPOINTS.getRequirements,
        method: "POST",
        dataType: "json",
        data: {
            csrf_token: csrfToken,
            student_uuid: studentUuid
        },
        success: function (response) {
            if (response.status === "success") {
                state.studentRequirements = response.requirements || [];
                const submitted = state.studentRequirements.filter(r => r.status === 'submitted');
                
                if (submitted.length === 0) {
                    ToastVersion(swalTheme, "No documents pending review for this student.", "info", 3000, "top-end");
                    getOverview();
                    return;
                }
                setupReviewUI(submitted, studentName);
            }
        },
        error: function (xhr, status, error) {
            Errors(xhr, status, error);
        }
    });
}

function setupReviewUI(submittedReqs, studentName) {
    const req = submittedReqs[0];
    state.selectedRequirement = req;

    $("#modalDocType").text(req.req_label);
    $("#modalStudentName").text(studentName);
    $("#modalStudentDocumentName").text(req.file_name);
    $("#modalStudentDocumentStatus").text(req.status_label);
    $("#documentFileName").text(req.file_name);
    $("#documentdate").text(req.submitted_at ? `Submitted on: ${req.submitted_at}` : "N/A");
    $("#studentNotesContent").text(req.student_note || "No notes provided.");
    $("#reviewNote").val("");

    const fileURL = `../../../file_serve.php?type=requirement&req_uuid=${encodeURIComponent(req.uuid)}`;
    $("#viewDocumentBtn").off("click").on("click", () => window.open(fileURL, '_blank'));
    $("#downloadDocumentBtn").off("click").on("click", () => window.open(`${fileURL}&action=download`, '_blank'));

    $("#requirementReviewModal").modal("show");
}

function handleApprove() {
    if (!state.selectedRequirement) return;

    $.ajax({
        url: ENDPOINTS.approveRequirement,
        method: "POST",
        dataType: "json",
        data: {
            csrf_token: csrfToken,
            req_uuid: state.selectedRequirement.uuid
        },
        beforeSend: function() {
            $("#approveBtn").prop("disabled", true);
        },
        success: function (response) {
            if (response.status === "success") {
                ToastVersion(swalTheme, response.message || "Document approved.", "success", 3000, "top-end");
                $("#requirementReviewModal").modal("hide");
                getOverview();
            } else {
                ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
            }
        },
        error: function (xhr, status, error) {
            Errors(xhr, status, error);
        },
        complete: function() {
            $("#approveBtn").prop("disabled", false);
        }
    });
}

function handleReturn() {
    if (!state.selectedRequirement) return;

    const reason = $("#reviewNote").val().trim();
    if (!reason) {
        ToastVersion(swalTheme, "Please provide a reason for returning the document.", "warning", 3000, "top-end");
        return;
    }

    $.ajax({
        url: ENDPOINTS.returnRequirement,
        method: "POST",
        dataType: "json",
        data: {
            csrf_token: csrfToken,
            req_uuid: state.selectedRequirement.uuid,
            return_reason: reason
        },
        beforeSend: function() {
            $("#returnBtn").prop("disabled", true);
        },
        success: function (response) {
            if (response.status === "success") {
                ToastVersion(swalTheme, response.message || "Document returned to student.", "success", 3000, "top-end");
                $("#requirementReviewModal").modal("hide");
                getOverview();
            } else {
                ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
            }
        },
        error: function (xhr, status, error) {
            Errors(xhr, status, error);
        },
        complete: function() {
            $("#returnBtn").prop("disabled", false);
        }
    });
}

$(document).ready(function () {
    getOverview();

    $("#approveBtn").on("click", handleApprove);
    $("#returnBtn").on("click", handleReturn);

    const loader = document.getElementById('pageLoader');
    if (loader) loader.classList.add('d-none');
});
