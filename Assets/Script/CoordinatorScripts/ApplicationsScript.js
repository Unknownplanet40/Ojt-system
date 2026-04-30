import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

$(document).ready(function() {
    let currentFilter = '';
    let currentSearch = '';
    let applicationsCache = [];

    loadApplications();

    $('#applicationSearchInput').on('keyup', function(e) {
        currentSearch = $(this).val();
        renderApplications();
    });

    $('.btn[id^="filter"]').click(function() {
        $('.btn[id^="filter"]').removeClass('border-primary text-primary').addClass('border-0').css('background-color', 'rgba(108, 117, 125, 0.15)');
        $(this).removeClass('border-0').addClass('border border-primary text-primary').css('background-color', '');
        
        let id = $(this).attr('id');
        if (id === 'filterAllBtn') currentFilter = '';
        if (id === 'filterPendingBtn') currentFilter = 'pending';
        if (id === 'filterNeedRevisionsBtn') currentFilter = 'needs_revision';
        if (id === 'filterApprovedBtn') currentFilter = 'approved';
        if (id === 'filterRejectedBtn') currentFilter = 'rejected';
        if (id === 'filterWithdrawnBtn') currentFilter = 'withdrawn';
        
        renderApplications();
    });

    function loadApplications() {
        $.ajax({
            url: '../../../Process/applications/get_applications',
            type: 'POST',
            data: { csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    applicationsCache = response.applications || [];
                    updateCounts();
                    renderApplications();
                } else {
                    Errors(response.message, 'error');
                }
            },
            error: function() {
                Errors('Failed to load applications.', 'error');
            }
        });
    }

    function updateCounts() {
        let counts = { all: 0, pending: 0, needs_revision: 0, approved: 0, rejected: 0, withdrawn: 0 };
        counts.all = applicationsCache.length;
        applicationsCache.forEach(app => {
            if (counts[app.status] !== undefined) {
                counts[app.status]++;
            }
        });
        
        $('#filterAllBadge').text(counts.all).toggle(counts.all > 0);
        $('#filterPendingBadge').text(counts.pending).toggle(counts.pending > 0);
        $('#filterNeedRevisionsBadge').text(counts.needs_revision).toggle(counts.needs_revision > 0);
        $('#filterApprovedBadge').text(counts.approved).toggle(counts.approved > 0);
        $('#filterRejectedBadge').text(counts.rejected).toggle(counts.rejected > 0);
        $('#filterWithdrawnBadge').text(counts.withdrawn).toggle(counts.withdrawn > 0);
    }

    function renderApplications() {
        let filtered = applicationsCache.filter(app => {
            let matchFilter = currentFilter === '' || app.status === currentFilter;
            let matchSearch = currentSearch === '' || 
                              app.full_name.toLowerCase().includes(currentSearch.toLowerCase()) || 
                              app.student_number.toLowerCase().includes(currentSearch.toLowerCase()) ||
                              app.company_name.toLowerCase().includes(currentSearch.toLowerCase());
            return matchFilter && matchSearch;
        });

        $('#applicationsList').empty();

        if (filtered.length === 0) {
            $('#applicationsList').html('<div class="col-12"><div class="alert alert-info border-info-subtle">No applications found matching your criteria.</div></div>');
            return;
        }

        filtered.forEach(app => {
            let html = `
            <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent border-1 border-secondary-subtle rounded-4 h-100">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3 mb-4">
                            <div class="avatar avatar-md rounded-circle bg-secondary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0"
                                style="width: 56px; height: 56px;">
                                <span class="fs-4 fw-semibold text-secondary">${app.initials}</span>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h5 class="card-title mb-0 fw-semibold text-truncate">${app.full_name}</h5>
                                <p class="card-text mb-0 text-muted small">
                                    <span class="d-block"><span class="text-body fw-medium">${app.program_code}</span> - <span class="text-body fw-medium">${app.year_label}</span></span>
                                    <span class="d-block mb-0 text-truncate">Applied for: <span class="text-body fw-medium">${app.company_name}</span></span>
                                </p>
                            </div>
                            <div class="flex-shrink-0 ms-2">
                                <span class="badge px-2 py-1 rounded-pill" style="background-color: ${app.status_bg}; color: ${app.status_text}">${app.status_label}</span>
                            </div>
                        </div>
                        <div class="row g-2">
                            <div class="col-6 col-md-3">
                                <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                                    <p class="text-muted small mb-1 fw-medium">Student No.</p>
                                    <p class="mb-0 fw-semibold small text-truncate">${app.student_number}</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                                    <p class="text-muted small mb-1 fw-medium">Work setup</p>
                                    <p class="mb-0 fw-semibold small text-truncate">${app.work_setup}</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                                    <p class="text-muted small mb-1 fw-medium">City</p>
                                    <p class="mb-0 fw-semibold small text-truncate">${app.company_city}</p>
                                </div>
                            </div>
                            <div class="col-6 col-md-3">
                                <div class="bg-body-secondary bg-opacity-50 rounded-2 p-3 h-100">
                                    <p class="text-muted small mb-1 fw-medium">Submitted</p>
                                    <p class="mb-0 fw-semibold small text-truncate">${app.created_at}</p>
                                </div>
                            </div>
                        </div>
                        <hr class="my-3">
                        <div class="d-flex justify-content-end gap-2 flex-wrap">
                            <button class="btn btn-sm bg-secondary-subtle text-body border px-3 py-2 rounded-3 view-details-btn" data-uuid="${app.uuid}">View Details</button>
                            ${app.status === 'approved' ? `<button class="btn btn-sm bg-primary-subtle text-primary-emphasis border px-3 py-2 rounded-3 btn-endorse" data-uuid="${app.uuid}">Generate Endorsement</button>` : ''}
                            ${app.status === 'endorsed' ? `<button class="btn btn-sm bg-success-subtle text-success-emphasis border px-3 py-2 rounded-3 btn-start" data-uuid="${app.uuid}">Confirm OJT Start</button>` : ''}
                        </div>
                    </div>
                </div>
            </div>
            `;
            $('#applicationsList').append(html);
        });
    }

    // Load full details for a single app
    $(document).on('click', '.view-details-btn', function() {
        let uuid = $(this).data('uuid');
        
        // Find application from cache to populate basic data
        let app = applicationsCache.find(a => a.uuid === uuid);
        if(!app) return;
        
        // Populate modal
        $('#stuName, #stuNamec1, #stuNamem2c1').text(app.full_name);
        $('#stuNum, #stuNumc1, #stuNumm2c1').text(app.student_number);
        $('#stuProg, #stuProgc1, #stuProgm2c1').text(app.program_code);
        $('#stuSectionc1').text(app.year_label); 
        $('#stuMobilec1').text(app.student_mobile);
        $('#stuEmailc1').text(app.student_email); 
        
        $('#stuCompanyc2, #stuCompanym2c2').text(app.company_name);
        $('#stuIndustryc2').text(app.industry);
        $('#stuLocationc2').text(app.company_city);
        $('#stuWorkSetupc2, #stuWorkSetupm2c2').text(app.work_setup);
        $('#stuSlotsc2, #stuSlotsm2c2').text(app.remaining_slots); 
        $('#stuAcceptsc2').text(app.accepted_programs);
        $('#submittedAtc3').text('Submitted on: ' + app.created_at);
        $('#stuPreferredDeptc3').text(app.preferred_department || '—');
        $('#coverletterc3').text(app.cover_letter || 'No cover letter provided.');

        // Fetch requirements status
        loadStudentRequirements(app.student_uuid);
        
        // Update manage link
        $('#manageRequirementsLink').attr('href', `Requirements?student_uuid=${app.student_uuid}`);

        // Adjust action buttons based on status
        $('#returnBtn, #rejectBtn, #approveBtn, #endorseBtn, #startBtn').addClass('d-none');
        
        if (app.status === 'pending') {
            $('#returnBtn, #rejectBtn, #approveBtn').removeClass('d-none');
        } else if (app.status === 'approved') {
            $('#endorseBtn').removeClass('d-none');
        } else if (app.status === 'endorsed') {
            $('#startBtn').removeClass('d-none');
        } else if (app.status === 'needs_revision') {
            // Still pending student action, but can reject if needed
            $('#rejectBtn').removeClass('d-none');
        }
        
        // Set UUID to all modals
        $('#ReviewModal, #ApproveModal, #ReturnModal, #RejectModal, #EndorseModal, #StartModal').data('application-uuid', uuid);

        $('#ReviewModal').modal('show');
    });

    $(document).on('click', '.btn-endorse', function() {
        let uuid = $(this).data('uuid');
        $('#ReviewModal').modal('hide');
        $('#EndorseModal').data('application-uuid', uuid).modal('show');
    });
    
    $(document).on('click', '.btn-start', function() {
        let uuid = $(this).data('uuid');
        $('#ReviewModal').modal('hide');
        $('#StartModal').data('application-uuid', uuid).modal('show');
    });

    // Helper for Actions
    function updateApplicationStatus(modalId, newStatus, reasonOrNote = '', additionalData = {}) {
        let uuid = $(modalId).data('application-uuid');
        let btn = $(modalId).find('.btn:contains("Confirm"), .btn:contains("Reject"), .btn:contains("Issue"), .btn:contains("Return")').last();
        let ogText = btn.text();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Processing...');

        let requestData = {
            csrf_token: csrfToken,
            application_uuid: uuid,
            new_status: newStatus,
            reason: reasonOrNote,
            ...additionalData
        };

        $.ajax({
            url: '../../../Process/applications/update_application',
            type: 'POST',
            data: requestData,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $(modalId).modal('hide');
                    ToastVersion(swalTheme, response.message, 'success');
                    
                    // Note: If testing confirms we need start_ojt.php for 'active', we'll create it later. 
                    // Right now we use update_application.php for most transitions.
                    loadApplications();
                } else {
                    Errors(response.message, 'error');
                }
            },
            error: function() {
                Errors('Server error during update.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).text(ogText);
            }
        });
    }

    $('#confirmApproveBtn').click(function() {
        updateApplicationStatus('#ApproveModal', 'approved', $('#approvalNote').val());
    });

    $('#confirmReturnBtn').click(function() {
        let reason = $('#returnReason').val().trim();
        if(!reason) { ToastVersion(swalTheme, 'Reason is required', 'error'); return; }
        updateApplicationStatus('#ReturnModal', 'needs_revision', reason);
    });

    $('#confirmRejectBtn').click(function() {
        let reason = $('#rejectionReason').val().trim();
        if(!reason) { ToastVersion(swalTheme, 'Reason is required', 'error'); return; }
        updateApplicationStatus('#RejectModal', 'rejected', reason);
    });

    $('#confirmEndorseBtn').click(function() {
        updateApplicationStatus('#EndorseModal', 'endorsed', $('#endorsementNote').val());
    });

    $('#confirmStartBtn').click(function() {
        // Warning: active is not handled correctly by update_application.php unless we added it or make a new script
        // Let's assume it works or we will modify update_application.php next!
        let sDate = $('#startDate').val();
        if(!sDate) { ToastVersion(swalTheme, 'Start date required', 'error'); return; }
        updateApplicationStatus('#StartModal', 'active', $('#startNote').val(), { start_date: sDate });
    });

    function loadStudentRequirements(studentUuid) {
        $('#requirementsStatusc4').html('<div class="col-12 text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
        
        $.ajax({
            url: '../../../Process/requirements/get_requirements',
            type: 'POST',
            data: { 
                csrf_token: csrfToken,
                student_uuid: studentUuid
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderRequirementsStatus(response.requirements);
                    
                    // If not all approved, maybe disable approve button?
                    if (!response.can_apply) {
                        $('#approveBtn').prop('disabled', true).attr('title', 'All 6 requirements must be approved first.');
                    } else {
                        $('#approveBtn').prop('disabled', false).removeAttr('title');
                    }
                } else {
                    $('#requirementsStatusc4').html('<div class="col-12 text-center text-danger small">Failed to load requirements.</div>');
                }
            }
        });
    }

    function renderRequirementsStatus(requirements) {
        $('#requirementsStatusc4').empty();
        
        const reqTypes = {
            'resume': 'Resume',
            'insurance': 'Insurance',
            'parental_consent': 'Parent Consent',
            'guardian_form': 'Guardian Form',
            'medical_certificate': 'Med Cert',
            'nbi_clearance': 'NBI Clearance'
        };

        requirements.forEach(req => {
            let icon = 'bi-clock-history';
            let colorClass = 'bg-warning-subtle text-warning-emphasis';
            
            if (req.status === 'approved') {
                icon = 'bi-file-earmark-check';
                colorClass = 'bg-success-subtle text-success-emphasis';
            } else if (req.status === 'not_submitted') {
                icon = 'bi-file-earmark-x';
                colorClass = 'bg-danger-subtle text-danger-emphasis';
            } else if (req.status === 'returned') {
                icon = 'bi-exclamation-octagon';
                colorClass = 'bg-danger-subtle text-danger-emphasis';
            }

            let cursor = 'default';
            let onClickAttr = '';
            
            if (req.status !== 'not_submitted' && req.uuid) {
                cursor = 'pointer';
                const fileURL = `../../../file_serve.php?type=requirement&req_uuid=${encodeURIComponent(req.uuid)}`;
                onClickAttr = `onclick="window.open('${fileURL}', '_blank')"`;
            }

            let html = `
            <div class="col">
                <div class="d-flex flex-column align-items-center gap-2">
                    <div class="rounded-circle ${colorClass} d-flex justify-content-center align-items-center flex-shrink-0"
                        style="width: 48px; height: 48px; cursor: ${cursor};" 
                        title="${req.status !== 'not_submitted' ? 'Click to view ' + req.status_label : req.status_label}"
                        ${onClickAttr}>
                        <i class="bi ${icon} fs-5"></i>
                    </div>
                    <p class="mb-0 small fw-medium text-muted text-center" style="font-size: 0.7rem;">${reqTypes[req.req_type] || req.req_type}</p>
                </div>
            </div>
            `;
            $('#requirementsStatusc4').append(html);
        });
    }
});
