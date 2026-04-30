import { ToastVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const ENDPOINTS = {
    getOverview: '../../../process/requirements/get_requirements_overview',
    getRequirements: '../../../process/requirements/get_requirements',
};

const loadingRow = `
    <div class="col-12 text-center py-5">
        <div class="spinner-border text-secondary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <p class="mt-2 text-muted">Fetching all student requirements...</p>
    </div>`;

const emptyRow = `
    <div class="col-12 text-center py-5">
        <i class="bi bi-folder-x display-4 text-muted"></i>
        <p class="mt-2 text-muted">No requirement records found.</p>
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

let allStudents = [];

function getRequirementsOverview() {
    const container = $("#requirementsContainer");

    $.ajax({
        url: ENDPOINTS.getOverview,
        method: "POST",
        dataType: "json",
        data: {
            csrf_token: csrfToken
        },
        beforeSend: function () {
            container.html(loadingRow);
        },
        success: function (response) {
            if (response.status === "success") {
                allStudents = response.overview || [];
                renderRequirements(allStudents);
            } else {
                container.html(emptyRow);
                ToastVersion(swalTheme, response.message || "Failed to load requirements", "warning", 3000, "top-end");
            }
        },
        error: function (xhr, status, error) {
            container.html(emptyRow);
            Errors(xhr, status, error);
        }
    });
}

function handleSearch() {
    const search = $("#requirementSearchInput").val().toLowerCase().trim();
    if (!search) {
        renderRequirements(allStudents);
        return;
    }

    const filtered = allStudents.filter(student => 
        student.full_name.toLowerCase().includes(search) || 
        student.student_number.toLowerCase().includes(search) ||
        student.program_code.toLowerCase().includes(search)
    );

    renderRequirements(filtered);
}

function renderRequirements(overview) {
    const container = $("#requirementsContainer");
    container.empty();

    if (!overview || overview.length === 0) {
        container.html(emptyRow);
        return;
    }

    overview.forEach(student => {
        const statuses = student.doc_statuses || {};
        const badge = getBadgeStatus(student);

        const dots = [
            statuses.resume,
            statuses.guardian_form,
            statuses.parental_consent,
            statuses.medical_certificate,
            statuses.insurance,
            statuses.nbi_clearance,
        ].map((s) => `<span class="${dotClass(s)}">&#11044;</span>`).join('');

        let hasSubmitted = false;

        if (student.approved_count != 0 || student.returned_count != 0 || student.submitted_count != 0) {
            hasSubmitted = true;
        }

        const studentCard = `
            <div class="col-12 mb-3">
                <div class="card border-0 shadow-sm rounded-4 bg-dark bg-opacity-75 h-100 transition-hover">
                    <div class="card-body py-3 px-3 px-md-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-3">
                            <div class="flex-shrink-0 d-flex align-items-center justify-content-center rounded-circle overflow-hidden border border-2 border-secondary-subtle shadow-sm" style="width: 48px; height: 48px; background: #23272b;">
                                <img src="https://placehold.co/64x64/483a0f/c7993d/png?text=${student.initials || 'ST'}&font=poppins" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <div class="d-flex flex-column flex-md-row align-items-md-center gap-1">
                                    <div class="flex-grow-1 min-w-0">
                                        <h6 class="mb-1 fw-semibold text-white text-truncate" style="font-size: 1.05rem;">${student.full_name}</h6>
                                        <div class="small text-muted text-truncate" style="font-size: 0.93rem;">
                                            ${student.student_number} &bull; ${student.program_code}
                                        </div>
                                    </div>
                                    <div class="d-none d-md-flex align-items-center gap-2 ms-md-3">
                                    ${dots}
                                </div>
                            </div>
                            <div class="d-flex d-md-none align-items-center gap-2 mt-2">
                            ${dots}
                        </div>
                    </div>
                    <div class="d-flex flex-column flex-md-row align-items-stretch align-items-md-center gap-2 ms-md-auto mt-2 mt-md-0">
                        <span class="badge ${badge.cls} rounded-pill px-3 py-2 fw-semibold shadow-sm" style="font-size: 0.85rem; letter-spacing: 0.03em;">
                            ${badge.text}
                        </span>
                    <button class="btn btn-sm btn-outline-secondary rounded-2 js-view-student-reqs ${!hasSubmitted ? 'disabled' : ''} d-inline-flex align-items-center justify-content-center px-3"
                        style="min-width: 140px;" data-student-uuid="${student.student_uuid}" data-student-name="${student.full_name}">
                        <i class="bi ${hasSubmitted ? 'bi-eye' : 'bi-eye-slash'} me-1"></i> 
                        <span class="d-none d-sm-inline">${hasSubmitted ? 'View Documents' : 'No Submissions'}</span>
                        <span class="d-inline d-sm-none">${hasSubmitted ? 'View' : 'None'}</span>
                    </button>
                    </div>
                </div>
                </div>
            </div>
            </div>
        `;
        container.append(studentCard);
    });

    $(".js-view-student-reqs").off("click").on("click", function() {
        const studentUuid = $(this).data("student-uuid");
        const studentName = $(this).data("student-name");
        showStudentRequirements(studentUuid, studentName);
    });
}

function showStudentRequirements(studentUuid, studentName) {
    $("#modalStudentName").text(studentName);
    $("#adminRequirementsList").html('<div class="col-12 text-center py-5"><div class="spinner-border text-success" role="status"></div></div>');
    $("#requirementViewModal").modal("show");

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
                const requirements = response.requirements;
                const list = $("#adminRequirementsList");
                list.empty();

                requirements.forEach(req => {
                    const statusMeta = {
                        approved: { badge: 'bg-success', icon: 'bi-check-circle-fill', text: 'Approved', theme: 'success' },
                        submitted: { badge: 'bg-warning text-dark', icon: 'bi-clock-history', text: 'Pending Review', theme: 'warning' },
                        returned: { badge: 'bg-danger', icon: 'bi-exclamation-triangle-fill', text: 'Returned', theme: 'danger' },
                        not_submitted: { badge: 'bg-secondary', icon: 'bi-dash-circle', text: 'Missing', theme: 'secondary' }
                    };

                    const meta = statusMeta[req.status] || statusMeta.not_submitted;
                    const fileURL = req.uuid ? `../../../file_serve.php?type=requirement&req_uuid=${encodeURIComponent(req.uuid)}` : null;

                    const row = `
                        <div class="col-12 col-xl-6">
                            <div class="card bg-dark bg-opacity-50 border border-secondary-subtle rounded-4 h-100 shadow-sm transition-hover">
                                <div class="card-header border-bottom border-secondary-subtle bg-transparent py-3 px-4">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0 fw-bold text-white">${req.req_label}</h6>
                                        <span class="badge ${meta.badge} rounded-pill px-3 py-2 fw-semibold shadow-sm" style="font-size: 0.7rem;">
                                            <i class="bi ${meta.icon} me-1"></i> ${meta.text}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <!-- Document Section -->
                                    <div class="mb-4">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="bi bi-file-earmark-text text-success"></i>
                                            <span class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Document Details</span>
                                        </div>
                                        ${req.status !== 'not_submitted' ? `
                                            <div class="bg-dark bg-opacity-75 border border-secondary-subtle rounded-3 p-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="p-2 bg-danger bg-opacity-10 rounded-3">
                                                        <i class="bi bi-file-pdf-fill fs-4 text-danger"></i>
                                                    </div>
                                                    <div class="vstack min-w-0" style="flex: 1;">
                                                        <span class="text-white text-truncate fw-medium d-block" 
                                                              style="max-width: 250px;"
                                                              data-bs-toggle="tooltip" 
                                                              data-bs-placement="top"
                                                              title="${req.file_name}">${req.file_name}</span>
                                                        <small class="text-muted">Submitted ${req.submitted_at}</small>
                                                    </div>
                                                </div>
                                                <div class="mt-3 hstack gap-2">
                                                    <a href="${fileURL}" target="_blank" class="btn btn-sm btn-outline-success flex-grow-1 rounded-2">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                    <a href="${fileURL}&action=download" class="btn btn-sm btn-success flex-grow-1 rounded-2">
                                                        <i class="bi bi-download me-1"></i> Download
                                                    </a>
                                                </div>
                                            </div>
                                        ` : `
                                            <div class="bg-secondary bg-opacity-10 border border-secondary-subtle border-dashed rounded-3 p-3 text-center">
                                                <i class="bi bi-cloud-slash text-muted fs-4 d-block mb-1"></i>
                                                <small class="text-muted italic">No document has been uploaded for this requirement.</small>
                                            </div>
                                        `}
                                    </div>
                                    <div class="row g-3 mt-1">
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-chat-left-text text-primary"></i>
                                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Student Notes</span>
                                            </div>
                                            <div class="bg-dark bg-opacity-25 rounded-3 p-3 border min-w-0">
                                                <p class="mb-0 text-light-emphasis text-break">${req.student_note || '<span class="text-muted">No notes.</span>'}</p>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <i class="bi bi-clipboard-check text-danger"></i>
                                                <span class="text-uppercase text-muted fw-bold" style="font-size: 0.65rem; letter-spacing: 0.05em;">Coordinator Feedback</span>
                                            </div>
                                            <div class="bg-dark bg-opacity-25 rounded-3 p-3 border min-w-0">
                                                <p class="mb-0 text-light-emphasis text-break">${req.return_reason || req.coordinator_note || '<span class="text-muted italic">No remarks.</span>'}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    list.append(row);
                });

                // Initialize Tooltips
                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });

                $("#requirementViewModal").modal("show");
            } else {
                ToastVersion(swalTheme, response.message || "Failed to fetch student requirements", "error", 3000, "top-end");
            }
        },
        error: function (xhr, status, error) {
            Errors(xhr, status, error);
        }
    });
}

$(document).ready(function () {
    getRequirementsOverview();

    $("#requirementSearchInput").on("keyup", function() {
        handleSearch();
    });

    const loader = document.getElementById('pageLoader');
    if (loader) loader.classList.add('d-none');
});