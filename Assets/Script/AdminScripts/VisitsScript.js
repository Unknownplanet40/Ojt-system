import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

$(document).ready(function () {
    loadVisits();
    loadFilters();

    $('#statusFilter, #coordinatorFilter, #companyFilter').change(function () {
        loadVisits();
    });

    $('#clearFiltersBtn').click(function () {
        $('#statusFilter, #coordinatorFilter, #companyFilter').val('');
        loadVisits();
    });

    $('#refreshBtn').click(function () {
        loadVisits();
    });

    $('#exportVisitsBtn').click(function () {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        
        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Exporting...');
        
        const status = $('#statusFilter').val();
        const coordinator_uuid = $('#coordinatorFilter').val();
        const company_uuid = $('#companyFilter').val();
        
        
        const $form = $('<form>', {
            action: '../../../process/visits/export_visits_pdf',
            method: 'POST',
            target: '_blank'
        }).append($('<input>', {
            type: 'hidden',
            name: 'csrf_token',
            value: csrfToken
        })).append($('<input>', {
            type: 'hidden',
            name: 'status',
            value: status
        })).append($('<input>', {
            type: 'hidden',
            name: 'coordinator_uuid',
            value: coordinator_uuid
        })).append($('<input>', {
            type: 'hidden',
            name: 'company_uuid',
            value: company_uuid
        }));

        $('body').append($form);
        $form.submit();
        $form.remove();

        
        setTimeout(() => {
            $btn.prop('disabled', false).html(originalHtml);
            ToastVersion(swalTheme, 'Visit report generated successfully.', 'success', 2000, 'top-end');
        }, 2000);
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
                    
                    $('#viewCoordinatorName').text(v.coordinator_name);
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
});

function loadFilters() {
    
    
    
}

function loadVisits() {
    let status = $('#statusFilter').val();
    let coordinator_uuid = $('#coordinatorFilter').val();
    let company_uuid = $('#companyFilter').val();
    
    $.ajax({
        url: '../../../process/visits/get_visits',
        type: 'POST',
        data: {
            status: status,
            coordinator_uuid: coordinator_uuid,
            company_uuid: company_uuid,
            csrf_token: csrfToken
        },
        dataType: 'json',
        success: function (response) {
            if (response.status === 'success') {
                renderVisits(response.visits);
                updateStats(response.visits);
                updateFilterOptions(response.visits);
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

let filtersLoaded = false;
function updateFilterOptions(visits) {
    if (filtersLoaded) return;
    
    let coords = new Map();
    let comps = new Map();
    
    visits.forEach(v => {
        if (!coords.has(v.coordinator_name)) {
            coords.set(v.coordinator_name, v.coordinator_uuid || '');
        }
        if (!comps.has(v.company_name)) {
            comps.set(v.company_name, v.company_uuid);
        }
    });
    
    
    let sortedCoords = Array.from(coords.entries()).sort();
    let sortedComps = Array.from(comps.entries()).sort();
    
    sortedCoords.forEach(([name, uuid]) => {
        if (uuid) {
            $('#coordinatorFilter').append(`<option class="CustomOption" value="${uuid}">${name}</option>`);
        }
    });
    
    sortedComps.forEach(([name, uuid]) => {
        $('#companyFilter').append(`<option class="CustomOption" value="${uuid}">${name}</option>`);
    });
    
    filtersLoaded = true;
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
    
    visits.forEach(function (v, index) {
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
                    <p class="text-muted small mb-2"><i class="bi bi-geo-alt me-1"></i>${v.company_city}</p>
                    
                    <div class="d-flex align-items-center mb-3">
                        <i class="bi bi-person-badge text-muted me-2"></i>
                        <span class="small fw-medium">${v.coordinator_name}</span>
                    </div>
                    
                    <div class="p-3 rounded-3 mb-3 flex-grow-1" style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.05);">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-calendar-event text-primary me-2"></i>
                            <span class="fw-medium small">${v.visit_date_label}</span>
                        </div>
                        <div class="d-flex align-items-start mt-2 pt-2 border-top border-subtle">
                            <i class="bi bi-info-circle text-muted me-2 mt-1"></i>
                            <p class="small text-muted mb-0 lh-sm text-truncate" style="max-height: 40px;">${v.purpose}</p>
                        </div>
                    </div>
                    
                    <button class="btn btn-sm btn-outline-secondary w-100 btn-view-visit rounded-3" data-uuid="${v.uuid}">
                        <i class="bi bi-eye me-1"></i>View Details
                    </button>
                </div>
            </div>
        </div>
        `;
        
        container.append(card);
    });
    
    
    anime({
        targets: '.visit-card-anim',
        translateY: [20, 0],
        opacity: [0, 1],
        delay: anime.stagger(50),
        duration: 800,
        easing: 'easeOutExpo'
    });
}
