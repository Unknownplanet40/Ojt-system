import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

$(document).ready(function () {
    loadVisits();
    loadVisitableCompanies();

    $('#statusFilter, #companyFilter').change(function () {
        loadVisits();
    });

    $('#clearFiltersBtn').click(function () {
        $('#statusFilter, #companyFilter').val('');
        loadVisits();
    });

    $('#refreshBtn').click(function () {
        loadVisits();
    });

    $('#scheduleVisitForm').submit(function (e) {
        e.preventDefault();
        let btn = $('#btnSaveSchedule');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Scheduling...');

        $.ajax({
            url: '../../../process/visits/schedule_visit',
            type: 'POST',
            data: $(this).serialize() + `&csrf_token=${csrfToken}`,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    ToastVersion(swalTheme, 'Visit scheduled successfully', 'success');
                    $('#ScheduleVisitModal').modal('hide');
                    $('#scheduleVisitForm')[0].reset();
                    loadVisits();
                } else {
                    let errorMsg = response.error || 'Failed to schedule visit';
                    if (response.errors) {
                        errorMsg = Object.values(response.errors).join('<br>');
                    }
                    Errors(errorMsg, 'error');
                }
            },
            error: function () {
                Errors('Server error. Try again.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#logUnscheduledForm').submit(function (e) {
        e.preventDefault();
        let btn = $('#btnSaveUnscheduled');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '../../../process/visits/schedule_visit',
            type: 'POST',
            data: $(this).serialize() + `&csrf_token=${csrfToken}`,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    ToastVersion(swalTheme, 'Unscheduled visit logged successfully', 'success');
                    $('#LogUnscheduledModal').modal('hide');
                    $('#logUnscheduledForm')[0].reset();
                    loadVisits();
                } else {
                    let errorMsg = response.error || 'Failed to log visit';
                    if (response.errors) {
                        errorMsg = Object.values(response.errors).join('<br>');
                    }
                    Errors(errorMsg, 'error');
                }
            },
            error: function () {
                Errors('Server error. Try again.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $('#completeVisitForm').submit(function (e) {
        e.preventDefault();
        let btn = $('#btnSubmitComplete');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

        $.ajax({
            url: '../../../process/visits/complete_visit',
            type: 'POST',
            data: $(this).serialize() + `&csrf_token=${csrfToken}`,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    ToastVersion(swalTheme, 'Visit marked as completed', 'success');
                    $('#CompleteVisitModal').modal('hide');
                    $('#completeVisitForm')[0].reset();
                    loadVisits();
                } else {
                    let errorMsg = response.error || 'Failed to complete visit';
                    if (response.errors) {
                        errorMsg = Object.values(response.errors).join('<br>');
                    }
                    Errors(errorMsg, 'error');
                }
            },
            error: function () {
                Errors('Server error. Try again.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $(document).on('click', '.btn-complete-visit', function () {
        let uuid = $(this).data('uuid');
        let company = $(this).data('company');
        let date = $(this).data('date');

        $('#completeVisitUuid').val(uuid);
        $('#completeCompanyName').text(company);
        $('#completeVisitDate').text(date);
    });

    $(document).on('click', '.btn-cancel-visit', function () {
        let uuid = $(this).data('uuid');
        $('#cancelVisitUuid').val(uuid);
        $('#cancelReason').val('');
        $('#CancelVisitModal').modal('show');
    });

    $('#cancelVisitForm').submit(function (e) {
        e.preventDefault();
        let btn = $('#btnSubmitCancel');
        let originalText = btn.html();
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Canceling...');

        $.ajax({
            url: '../../../process/visits/cancel_visit',
            type: 'POST',
            data: $(this).serialize() + `&csrf_token=${csrfToken}`,
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success') {
                    ToastVersion(swalTheme, 'Visit cancelled', 'success');
                    $('#CancelVisitModal').modal('hide');
                    $('#cancelVisitForm')[0].reset();
                    loadVisits();
                } else {
                    let errorMsg = response.error || 'Failed to cancel visit';
                    if (response.errors) {
                        errorMsg = Object.values(response.errors).join('<br>');
                    }
                    Errors(errorMsg, 'error');
                }
            },
            error: function () {
                Errors('Server error. Try again.', 'error');
            },
            complete: function () {
                btn.prop('disabled', false).html(originalText);
            }
        });
    });

    $(document).on('click', '.btn-view-visit', function () {
        let uuid = $(this).data('uuid');
        
        $.ajax({
            url: '../../../process/visits/get_visit',
            type: 'POST',
            data: { visit_uuid: uuid, csrf_token: csrfToken },
            dataType: 'json',
            success: function (response) {
                if (response.status === 'success' && response.visit) {
                    let v = response.visit;
                    
                    $('#viewCompanyName').text(v.company_name);
                    $('#viewVisitDate').text(v.visit_date_label);
                    $('#viewVisitStatus').text(v.status_label).css({
                        'background-color': v.status_bg,
                        'color': v.status_text
                    });
                    
                    $('#viewPurpose').text(v.purpose);
                    $('#viewVisitType').text(v.visit_type_label);
                    
                    if (v.findings) {
                        $('#viewFindingsContainer').removeClass('d-none');
                        $('#viewFindings').text(v.findings);
                    } else {
                        $('#viewFindingsContainer').addClass('d-none');
                    }
                    
                    if (v.recommendations) {
                        $('#viewRecommendationsContainer').removeClass('d-none');
                        $('#viewRecommendations').text(v.recommendations);
                    } else {
                        $('#viewRecommendationsContainer').addClass('d-none');
                    }
                    
                    if (v.cancel_reason && v.status === 'cancelled') {
                        $('#viewCancelReasonContainer').removeClass('d-none');
                        $('#viewCancelReason').text(v.cancel_reason);
                    } else {
                        $('#viewCancelReasonContainer').addClass('d-none');
                    }
                    
                    $('#ViewVisitModal').modal('show');
                } else {
                    Errors('Failed to load visit details');
                }
            }
        });
    });

    // Handle URL parameters for direct actions from dashboard
    const urlParams = new URLSearchParams(window.location.search);
    const visitUuid = urlParams.get('uuid');
    const action = urlParams.get('action');

    if (visitUuid && action) {
        setTimeout(() => {
            if (action === 'complete') {
                $.ajax({
                    url: '../../../process/visits/get_visit',
                    type: 'POST',
                    data: { visit_uuid: visitUuid, csrf_token: csrfToken },
                    dataType: 'json',
                    success: function (response) {
                        if (response.status === 'success' && response.visit) {
                            let v = response.visit;
                            $('#completeVisitUuid').val(v.uuid);
                            $('#completeCompanyName').text(v.company_name);
                            $('#completeVisitDate').text(v.visit_date_label);
                            $('#CompleteVisitModal').modal('show');
                        }
                    }
                });
            } else if (action === 'manage') {
                $('.btn-view-visit[data-uuid="' + visitUuid + '"]').first().click();
            }
        }, 1000); // Wait for visits to load
    }
});

function loadVisitableCompanies() {
    $.ajax({
        url: '../../../process/visits/get_visitable_companies',
        type: 'POST',
        data: { csrf_token: csrfToken },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success' && response.companies) {
                let options = '<option class="CustomOption" value="" selected disabled hidden>Select Company</option>';
                let filterOptions = '<option class="CustomOption" value="">All Companies</option>';
                
                response.companies.forEach(function (c) {
                    options += `<option class="CustomOption" value="${c.uuid}" data-students="${c.student_count}">${c.label}</option>`;
                    filterOptions += `<option class="CustomOption" value="${c.uuid}">${c.name}</option>`;
                });
                
                $('#scheduleCompany').html(options);
                $('#unscheduledCompany').html(options);
                $('#companyFilter').html(filterOptions);
                
                $('#scheduleCompany').change(function() {
                    let count = $(this).find('option:selected').data('students');
                    $('#companyStudentCountLabel').html(`<i class="bi bi-people-fill me-1"></i> ${count} assigned student${count > 1 ? 's' : ''}`);
                });
            }
        }
    });
}

function loadVisits() {
    let status = $('#statusFilter').val();
    let company_uuid = $('#companyFilter').val();
    
    $.ajax({
        url: '../../../process/visits/get_visits',
        type: 'POST',
        data: {
            status: status,
            company_uuid: company_uuid,
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                renderVisits(response.visits);
                updateStats(response.visits);
            } else {
                Errors('Failed to load visits data');
            }
            $('#pageLoader').fadeOut();
        },
        error: function () {
            Errors('Server error while fetching visits');
            $('#pageLoader').fadeOut();
        }
    });
}

function updateStats(visits) {
    let total = visits.length;
    let upcoming = 0;
    let completed = 0;
    let overdue = 0;
    
    visits.forEach(v => {
        if (v.status === 'completed') completed++;
        if (v.status === 'scheduled') {
            if (v.is_overdue) overdue++;
            else upcoming++;
        }
    });
    
    $('#totalVisitsCount').text(total);
    $('#upcomingVisitsCount').text(upcoming);
    $('#completedVisitsCount').text(completed);
    $('#overdueVisitsCount').text(overdue);
}

function renderVisits(visits) {
    let container = $('#visitsGrid');
    container.empty();
    
    if (visits.length === 0) {
        $('#emptyState').removeClass('d-none');
        return;
    }
    
    $('#emptyState').addClass('d-none');
    
    let html = '';
    
    visits.forEach(function (v, index) {
        let actionButtons = '';
        
        if (v.status === 'scheduled') {
            actionButtons = `
                <div class="d-flex gap-2 w-100 mt-3 pt-3 border-top border-subtle">
                    <button class="btn btn-sm btn-outline-success flex-grow-1 btn-complete-visit rounded-3" 
                        data-bs-toggle="modal" data-bs-target="#CompleteVisitModal" 
                        data-uuid="${v.uuid}" data-company="${v.company_name}" data-date="${v.visit_date_label}">
                        <i class="bi bi-check2-circle me-1"></i>Complete
                    </button>
                    <button class="btn btn-sm btn-outline-danger flex-grow-1 btn-cancel-visit rounded-3" data-uuid="${v.uuid}">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </button>
                </div>
            `;
        }
        
        let overdueBadge = v.is_overdue ? `<span class="badge bg-danger rounded-pill position-absolute top-0 end-0 mt-3 me-3" style="font-size: 0.65rem;">OVERDUE</span>` : '';
        
        let card = `
        <div class="col-12 col-md-6 col-xl-4 visit-card-anim" style="opacity: 0; transform: translateY(20px);">
            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm position-relative overflow-hidden" style="transition: all 0.3s ease;">
                <div class="position-absolute top-0 start-0 w-100" style="height: 4px; background-color: ${v.status_text}"></div>
                ${overdueBadge}
                
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge rounded-pill fw-medium" style="background-color: ${v.status_bg}; color: ${v.status_text}; font-size: 0.75rem;">
                            ${v.status_label}
                        </span>
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>${v.time_ago}</small>
                    </div>
                    
                    <h5 class="fw-bold mb-1 text-break">${v.company_name}</h5>
                    <p class="text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i>${v.company_city}</p>
                    
                    <div class="p-3 rounded-3 mb-3 flex-grow-1" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-calendar-event text-primary me-2"></i>
                            <span class="fw-medium small">${v.visit_date_label}</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-people text-info me-2"></i>
                            <span class="fw-medium small">${v.assigned_students} Student(s) Assigned</span>
                        </div>
                        <div class="d-flex align-items-start mt-3 pt-3 border-top border-subtle">
                            <i class="bi bi-info-circle text-muted me-2 mt-1"></i>
                            <p class="small text-muted mb-0 lh-sm text-truncate" style="max-height: 40px;">${v.purpose}</p>
                        </div>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-secondary w-100 btn-view-visit rounded-3" data-uuid="${v.uuid}">
                        <i class="bi bi-eye me-1"></i>View Details
                    </button>
                    
                    ${actionButtons}
                </div>
            </div>
        </div>
        `;
        
        container.append(card);
    });
    
    // Animate cards
    anime({
        targets: '.visit-card-anim',
        translateY: [20, 0],
        opacity: [0, 1],
        delay: anime.stagger(50),
        duration: 800,
        easing: 'easeOutExpo'
    });
}
