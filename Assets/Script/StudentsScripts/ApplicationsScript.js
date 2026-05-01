import { ToastVersion, ModalVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

$(document).ready(function() {
    loadApplication();
    
    $('#dashboardRefreshBtn').click(function() {
        loadApplication();
    });

    function loadApplication() {
        $.ajax({
            url: '../../../Process/applications/get_application',
            type: 'POST',
            data: { csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    if (response.application) {
                        $('#noApplicationsContainer').addClass('d-none');
                        $('#applicationStatusContainer').removeClass('d-none');
                        $('#applicationDetailsContainer').removeClass('d-none');
                        $('#applyNowBtn').addClass('d-none');
                        
                        const app = response.application;
                        $('#detailCompanyName').text(app.company_name).data('company-uuid', app.company_uuid);
                        $('#detailIndustry').text(app.industry);
                        $('#detailLocation').text(app.company_city);
                        $('#detailWorkArrangement').text(app.work_setup);
                        $('#detailDepartmentPreference').text(app.preferred_department || '—');
                        $('#detailSubmitted').text(app.created_at);
                        
                        $('#statusText').text(app.status_label);
                        $('#statusLastUpdated').text('Last updated: ' + app.time_ago);
                        $('#currentStatusBadge').text(app.status_label);
                        $('#currentStatusBadge').css({
                            'background-color': app.status_bg,
                            'color': app.status_text,
                            'border': '1px solid rgba(0,0,0,0.04)'
                        });

                        $('#statusIconWrap').css({
                            'background-color': app.status_bg,
                            'color': app.status_text,
                            'border': '1px solid rgba(0,0,0,0.04)'
                        });

                        let icon = 'bi-clock-history';
                        if(app.status === 'approved' || app.status === 'endorsed' || app.status === 'active') icon = 'bi-check-circle';
                        if(app.status === 'rejected') icon = 'bi-x-circle';
                        if(app.status === 'needs_revision') icon = 'bi-pencil-square';
                        $('#statusIcon').html(`<i class="bi ${icon} fs-5"></i>`);

                        renderStatusStepper(app.status);
                        
                        if (app.status === 'needs_revision') {
                            $('#resubmitApplicationBtn').removeClass('d-none').data('uuid', app.uuid).data('cover-letter', app.cover_letter);
                        } else {
                            $('#resubmitApplicationBtn').addClass('d-none');
                        }

                        if (app.can_withdraw) {
                            $('#withdrawApplicationBtn').removeClass('d-none').data('uuid', app.uuid);
                        } else {
                            $('#withdrawApplicationBtn').addClass('d-none');
                        }

                        if (['approved', 'endorsed', 'active'].includes(app.status)) {
                            $('#downloadEndorsementBtn').removeClass('d-none').data('uuid', app.uuid);
                        } else {
                            $('#downloadEndorsementBtn').addClass('d-none').removeData('uuid');
                        }
                        
                        // Timeline - Tracker UI
                        $('#applicationStatusTimeline').empty();
                        if (response.history) {
                            response.history.forEach((hist, index) => {
                                let statusColors = getStatusColors(hist.to_status);
                                let isLast = index === response.history.length - 1;
                                let isFirst = index === 0;
                                
                                let initials = hist.actor_name
                                    .split(' ')
                                    .slice(0, 2)
                                    .map(word => word.charAt(0).toUpperCase())
                                    .join('');
                                
                                

                                let profileContent = '';
                                let path = '../../../Assets/Images/profiles/' + hist.profile_pic;
                                if (hist.profile_pic) {
                                    profileContent = `<img src="${path}" alt="${hist.actor_name}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`;
                                } else {
                                    profileContent = `<span style="font-size:14px;font-weight:600;color:${statusColors.text};">${initials}</span>`;
                                }
                                
                                let html = `
                                <div class="position-relative ps-sm-5">
                                    <!-- Vertical connector line -->
                                    ${!isLast ? `<div class="position-absolute d-none d-sm-block" style="left:17px;top:60px;width:2px;height:calc(100% + 30px);background:${statusColors.bg};opacity:0.3;"></div>` : ''}
                                    
                                    <!-- Step circle marker with profile -->
                                    <div class="position-absolute d-none d-sm-flex align-items-center justify-content-center rounded-circle overflow-hidden" style="width: 36px; height: 36px; background-color: ${statusColors.bg}; color: ${statusColors.text}; left: 0px; top: 0px; z-index: 2; border: 3px solid white; font-weight: 600; font-size: 12px; flex-shrink:0;">
                                        ${profileContent}
                                    </div>
                                    
                                    <!-- Content card -->
                                    <div class="p-4 border rounded-4 ${isLast ? 'bg-body bg-opacity-50 border-3' : 'bg-body-tertiary opacity-75'} h-100" style="transition: all 0.3s ease;">
                                        <!-- Status title -->
                                        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between gap-2 mb-3">
                                            <h6 class="mb-0 fw-semibold text-body" style="font-size:16px;">${formatStatus(hist.to_status)}</h6>
                                            <small class="text-muted fw-medium text-nowrap">${hist.time_ago}</small>
                                        </div>
                                        
                                        <!-- Actor info -->
                                        <div class="d-flex align-items-center gap-3 mb-3 bg-blur-5 bg-semi-transparent p-3 rounded-3 border">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden" style="width:40px;height:40px;background:${statusColors.bg};color:${statusColors.text};font-weight:600;font-size:13px;border: 2px solid rgba(0,0,0,0.05);flex-shrink:0;">
                                                ${hist.profile_pic ? `<img src="${path}" alt="${hist.actor_name}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">` : `<span>${initials}</span>`}
                                            </div>
                                            <div>
                                                <small class="text-body fw-medium d-block">${hist.actor_name}</small>
                                                <small class="text-muted d-block">${hist.actor_role}</small>
                                            </div>
                                        </div>
                                        
                                        <!-- Reason/description -->
                                        ${hist.reason ? `<div class="bg-body-secondary p-3 rounded-3 border-start border-4" style="border-color:${statusColors.bg}!important;"><small class="text-body">${hist.reason}</small></div>` : '<p class="mb-0 text-muted small"><em>No additional notes</em></p>'}
                                    </div>
                                </div>
                                `;
                                $('#applicationStatusTimeline').append(html);
                            });
                        }
                    } else {
                        $('#applicationStatusContainer').addClass('d-none');
                        $('#applicationDetailsContainer').addClass('d-none');
                        
                        if (response.requirements_complete) {
                            $('#noApplicationsContainer').removeClass('d-none');
                            $('#requirementsIncompleteContainer').addClass('d-none');
                            $('#applyNowBtn').removeClass('d-none');
                            
                            // Update the text in noApplicationsContainer to be more welcoming
                            $('#noApplicationsContainer h4').text('Ready to Start Your OJT?');
                            $('#noApplicationsContainer p').text('Your requirements are complete! You can now browse available companies and submit your application.');
                            $('#noApplicationsContainer .d-flex.flex-wrap').html(`
                                <button type="button" class="btn btn-primary rounded-3 px-5 py-3 fw-semibold shadow-sm hover-up" data-bs-toggle="modal" data-bs-target="#ApplyFormsModal">
                                    <i class="bi bi-send me-2"></i> Browse & Apply Now
                                </button>
                            `);
                            
                            loadAvailableCompanies();
                        } else {
                            $('#noApplicationsContainer').addClass('d-none');
                            $('#requirementsIncompleteContainer').removeClass('d-none');
                            $('#applyNowBtn').addClass('d-none');
                            // We keep the response requirements in a variable for the modal
                            window.currentRequirements = response.requirements;
                        }
                    }
                } else {
                    Errors(response.message, 'error');
                }
            },
            error: function() {
                Errors('Failed to connect to server.', 'error');
            }
        });
    }
    $('#resubmitApplicationBtn').click(function() {
        const uuid = $(this).data('uuid');
        const currentCover = $(this).data('cover-letter');
        const companyUuid = $('#detailCompanyName').data('company-uuid'); // We need to store this

        // Switch modal to resubmit mode
        $('#ApplyFormsModal').attr('data-mode', 'resubmit');
        $('#ApplyFormsModal').attr('data-application-uuid', uuid);
        $('#ApplyFormsModal h5').text('Edit & Resubmit Application');
        
        // Pre-fill Step 2
        $('#preferredDepartment').val($('#detailDepartmentPreference').text() === '—' ? '' : $('#detailDepartmentPreference').text());
        $('#coverLetter').val(currentCover);

        // Pre-select company in Step 1
        loadAvailableCompanies(companyUuid); 

        $('#ApplyFormsModal').modal('show');
    });

    function loadAvailableCompanies(preSelectUuid = null) {
        $.ajax({
            url: '../../../Process/applications/get_available_companies',
            type: 'POST',
            data: { csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#companyList').empty();
                    if (response.companies && response.companies.length > 0) {
                        response.companies.forEach((company, index) => {
                            let progBadges = company.accepted_programs.split(',').map(p => 
                                `<small class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">${p.trim()}</small>`
                            ).join(' ');
                            
                            let isSelected = false;
                            if (preSelectUuid) {
                                isSelected = company.uuid === preSelectUuid;
                            } else {
                                isSelected = index === 0;
                            }
                            
                            let selectClass = isSelected ? 'selected-card border-primary' : '';
                            
                            let html = `
                            <div class="col-12">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 h-100 comcard ${selectClass}"
                                    style="cursor: pointer;"
                                    data-uuid="${company.uuid}"
                                    data-name="${company.name}"
                                    data-industry="${company.industry}"
                                    data-location="${company.city}"
                                    data-worksetup="${company.work_setup_label}">
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
                                            </div>
                                        </div>
                                        <div class="mt-3 pt-3 border-top border-secondary-subtle">
                                            <div class="d-flex flex-wrap gap-2">
                                                ${progBadges}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            `;
                            $('#companyList').append(html);

                            if (isSelected) {
                                $('#step-2').attr('data-selected-company', company.uuid);
                                $('#selectedCompanyName').text(company.name);
                                $('#industryInfo').text(company.industry);
                                $('#locationInfo').text(company.city);
                                $('#confirmCompanyName').text(company.name);
                                $('#confirmCompanyMeta').text(company.industry + ' · ' + company.city);
                            }
                        });
                        
                        $('.comcard').click(function() {
                            $('.comcard').removeClass('selected-card border-primary');
                            $(this).addClass('selected-card border-primary');
                            
                            $('#step-2').attr('data-selected-company', $(this).data('uuid'));
                            $('#selectedCompanyName').text($(this).data('name'));
                            $('#industryInfo').text($(this).data('industry'));
                            $('#locationInfo').text($(this).data('location'));
                            
                            $('#confirmCompanyName').text($(this).data('name'));
                            $('#confirmCompanyMeta').text($(this).data('industry') + ' · ' + $(this).data('location'));
                        });
                    } else {
                        $('#companyList').html(`
                            <div class="col-12">
                                <div class="alert alert-info border-0 shadow-sm rounded-4 p-4 text-center">
                                    <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                    <p class="mb-0 fw-medium">No accredited companies are currently accepting applications for your program.</p>
                                    <small class="text-muted">Please contact your coordinator for more information.</small>
                                </div>
                            </div>
                        `);
                        $('#proceedToDetailsBtn').addClass('disabled');
                    }
                } else {
                    $('#companyList').html('<p class="text-center text-muted py-4">No companies available right now.</p>');
                    $('#proceedToDetailsBtn').prop('disabled', true);
                }
            }
        });
    }

    $('#applyNowBtn').click(function() {
        $('#ApplyFormsModal').attr('data-mode', 'new');
        $('#ApplyFormsModal h5').text('Apply for OJT');
        $('#preferredDepartment, #coverLetter').val('');
        loadAvailableCompanies();
    });

    $('#proceedToDetailsBtn').click(function() {
        if (!$('#step-2').attr('data-selected-company')) {
            ToastVersion(swalTheme, 'Please select a company first.', 'error');
            return;
        }
        $('#step-1').addClass('d-none');
        $('#step-2').removeClass('d-none');
        $('#step1ProgressBar').css('width', '100%');
        $('#step2Indicator').removeClass('bg-secondary-subtle text-secondary').addClass('bg-primary text-white');
    });

    $('#backToCompanySelectionBtn').click(function() {
        $('#step-2').addClass('d-none');
        $('#step-1').removeClass('d-none');
        $('#step1ProgressBar').css('width', '0%');
        $('#step2Indicator').removeClass('bg-primary text-white').addClass('bg-secondary-subtle text-secondary');
    });

    $('#submitApplicationBtn').click(function() {
        let preferredDept = $('#preferredDepartment').val();
        let coverLetter = $('#coverLetter').val();
        
        $('#confirmPreferredDepartment').text(preferredDept || '—');
        $('#confirmCoverLetter').text(coverLetter || 'No message provided.');
        
        $('#step-2').addClass('d-none');
        $('#step-3').removeClass('d-none');
        $('#step2ProgressBar').css('width', '100%');
        $('#step3Indicator').removeClass('bg-secondary-subtle text-secondary').addClass('bg-primary text-white');
    });

    $('#backToDetailsBtn').click(function() {
        $('#step-3').addClass('d-none');
        $('#step-2').removeClass('d-none');
        $('#step2ProgressBar').css('width', '0%');
        $('#step3Indicator').removeClass('bg-primary text-white').addClass('bg-secondary-subtle text-secondary');
    });

    $('#finalSubmitApplicationBtn').click(function() {
        let btn = $(this);
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...');
        
        let mode = $('#ApplyFormsModal').attr('data-mode') || 'new';
        let url = mode === 'resubmit' ? '../../../Process/applications/resubmit_application' : '../../../Process/applications/submit_application';
        
        let dept = $('#preferredDepartment').val().trim();
        let cover = $('#coverLetter').val().trim();

        let data = {
            csrf_token: csrfToken,
            cover_letter: cover,
            preferred_department: dept
        };

        if (mode === 'resubmit') {
            data.application_uuid = $('#ApplyFormsModal').attr('data-application-uuid');
            data.company_uuid = $('#step-2').attr('data-selected-company'); 
        } else {
            data.company_uuid = $('#step-2').attr('data-selected-company');
        }

        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#ApplyFormsModal').modal('hide');
                    
                    $('#step-3').addClass('d-none');
                    $('#step-1').removeClass('d-none');
                    $('#step1ProgressBar, #step2ProgressBar').css('width', '0%');
                    $('#step2Indicator, #step3Indicator').removeClass('bg-primary text-white').addClass('bg-secondary-subtle text-secondary');
                    $('#preferredDepartment, #coverLetter').val('');
                    
                    if (mode === 'resubmit') {
                        ToastVersion(swalTheme, response.message, 'success');
                    } else {
                        $('#ApplicationSubmittedModal').modal('show');
                    }
                    loadApplication();
                } else {
                    if (response.message.includes('requirements')) {
                        $('#ApplyFormsModal').modal('hide');
                        $('#IncompleteRequirementsModal').modal('show');
                    } else {
                        Errors(response.message, 'error');
                    }
                }
            },
            error: function() {
                Errors('Server error during submission.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).html('<i class="bi bi-check2-circle"></i> Confirm & submit');
            }
        });
    });

    $('#viewReqStatusBtn').click(function() {
        if (window.currentRequirements) {
            showIncompleteRequirements(window.currentRequirements);
        }
    });

    function showIncompleteRequirements(requirements) {
        $('#IncompleteRequirementsModal').modal('show');
        
        requirements.forEach(req => {
            let statusEl = $(`#${req.req_type}Status`);
            let iconEl = $(`#${req.req_type}Icon`);
            
            if (statusEl.length) {
                statusEl.text(req.status_label);
                statusEl.removeClass('bg-warning-subtle text-warning bg-success-subtle text-success bg-danger-subtle text-danger');
                
                if (req.status === 'approved') {
                    statusEl.addClass('bg-success-subtle text-success');
                    iconEl.removeClass('bi-clock-history bi-exclamation-octagon text-warning text-danger').addClass('bi-check-circle text-success');
                } else if (req.status === 'not_submitted') {
                    statusEl.addClass('bg-danger-subtle text-danger');
                    iconEl.removeClass('bi-clock-history bi-check-circle text-warning text-success').addClass('bi-exclamation-octagon text-danger');
                } else {
                    statusEl.addClass('bg-warning-subtle text-warning');
                    iconEl.removeClass('bi-check-circle bi-exclamation-octagon text-success text-danger').addClass('bi-clock-history text-warning');
                }
            }
        });
    }

    $('#withdrawApplicationBtn').click(function() {
        let uuid = $(this).data('uuid');
        
        ConfirmVersion(swalTheme, 
            'Withdraw Application?', 
            "This will cancel your application and release the slot.", 
            'warning', 
            'Yes, withdraw it', 
            'No, keep it'
        ).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../../../Process/applications/update_application',
                    type: 'POST',
                    data: {
                        csrf_token: csrfToken,
                        application_uuid: uuid,
                        new_status: 'withdrawn',
                        reason: 'Withdrawn by student'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            ToastVersion(swalTheme, 'Application withdrawn successfully.', 'success');
                            loadApplication();
                        } else {
                            Errors(response.message, 'error');
                        }
                    }
                });
            }
        });
    });

    $('#downloadEndorsementBtn').click(function() {
        let uuid = $(this).data('uuid');
        if (!uuid) {
            Errors('Application not found.', 'error');
            return;
        }

        const url = `../../../process/endorsement/download_letter?application_uuid=${encodeURIComponent(uuid)}`;
        window.open(url, '_blank');

        setTimeout(function() {
            loadApplication();
        }, 800);
    });

    function renderStatusStepper(currentStatus) {
        const steps = [
            { key: 'pending', label: 'Pending', icon: 'bi-clock-history' },
            { key: 'approved', label: 'Approved', icon: 'bi-check2' },
            { key: 'endorsed', label: 'Endorsed', icon: 'bi-file-earmark-text' },
            { key: 'active', label: 'Active', icon: 'bi-play-circle' }
        ];

        const container = $('#statusStepper');
        if (!container.length) return;
        container.empty();

        const currentIndex = steps.findIndex(s => s.key === currentStatus);

        const stepperWrapper = $('<div class="d-flex flex-row gap-2 gap-sm-3 overflow-x-auto overflow-y-hidden pb-2" style="flex-wrap:nowrap;align-items:flex-start;"></div>');

        steps.forEach((step, idx) => {
            const colors = getStatusColors(step.key);
            const isComplete = idx < currentIndex;
            const isActive = idx === currentIndex;
            const bgColor = (isActive || isComplete) ? colors.bg : '#f3f4f6';
            const textColor = (isActive || isComplete) ? colors.text : '#6b7280';

            if (idx > 0) {
                const connectorBg = idx <= currentIndex ? getStatusColors(steps[Math.min(idx, currentIndex)].key).bg : '#e9ecef';
                const connector = $(`<div style="flex:0 0 20px;height:2px;background:${connectorBg};align-self:center;margin:0 -4px;"></div>`);
                stepperWrapper.append(connector);
            }

            const stepEl = $(`
                <div class="d-flex flex-column align-items-center flex-shrink-0" data-step="${step.key}">
                    <div class="rounded-circle d-flex align-items-center justify-content-center mb-2" style="width:44px;height:44px;min-width:44px;background:${bgColor};color:${textColor};border:1px solid rgba(0,0,0,0.04);transition:all 0.3s ease;">
                        <i class="bi ${step.icon}" style="font-size:18px;"></i>
                    </div>
                    <small style="font-size:11px;font-weight:${isActive ? 700 : 600};color:${textColor};text-align:center;white-space:nowrap;width:60px;">${step.label}</small>
                </div>
            `);

            stepperWrapper.append(stepEl);
        });

        container.append(stepperWrapper);
    }

    function getStatusColors(status) {
        const colors = {
            'pending': { bg: '#EFF6FF', text: '#185FA5' },
            'approved': { bg: '#E1F5EE', text: '#0F6E56' },
            'endorsed': { bg: '#E1F5EE', text: '#0F6E56' },
            'active': { bg: '#E1F5EE', text: '#0F6E56' },
            'needs_revision': { bg: '#FEF9EE', text: '#BA7517' },
            'rejected': { bg: '#FEF2F2', text: '#DC2626' },
            'withdrawn': { bg: '#F3F4F6', text: '#6B7280' }
        };
        return colors[status] || { bg: '#F3F4F6', text: '#6B7280' };
    }

    function formatStatus(status) {
        return status.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
    }
});