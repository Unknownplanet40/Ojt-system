import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const ENDPOINTS = {
    getStudentApplication: '../../../process/applications/get_student_application',
    getAvailableCompanies: '../../../process/applications/get_available_companies',
    submitApplication: '../../../process/applications/submit_application',
    withdrawApplication: '../../../process/applications/withdraw_application',
    downloadEndorsement: '../../../process/applications/download_endorsement',
};

const requirementMap = {
    resume: { icon: '#resumeIcon', status: '#resumeStatus' },
    insurance: { icon: '#paiIcon', status: '#paiStatus' },
    parental_consent: { icon: '#waiverIcon', status: '#waiverStatus' },
    guardian_form: { icon: '#guardianInfoIcon', status: '#guardianInfoStatus' },
    medical_certificate: { icon: '#medicalCertIcon', status: '#medicalCertStatus' },
    nbi_clearance: { icon: '#nbiIcon', status: '#nbiStatus' },
};

let pageState = {
    requirementsApproved: false,
    canApply: false,
    requirements: {},
    companies: [],
    selectedCompany: null,
    application: null,
    statusLog: [],
};

const statusMeta = {
    pending: { badge: 'bg-warning-subtle text-warning border-warning-subtle', icon: 'bi-hourglass-split', line: 'border-warning-subtle' },
    approved: { badge: 'bg-primary-subtle text-primary border-primary-subtle', icon: 'bi-check2-circle', line: 'border-primary-subtle' },
    endorsed: { badge: 'bg-info-subtle text-info border-info-subtle', icon: 'bi-file-earmark-check', line: 'border-info-subtle' },
    active: { badge: 'bg-success-subtle text-success border-success-subtle', icon: 'bi-rocket-takeoff', line: 'border-success-subtle' },
    needs_revision: { badge: 'bg-secondary-subtle text-secondary border-secondary-subtle', icon: 'bi-arrow-repeat', line: 'border-secondary-subtle' },
    rejected: { badge: 'bg-danger-subtle text-danger border-danger-subtle', icon: 'bi-x-octagon', line: 'border-danger-subtle' },
    withdrawn: { badge: 'bg-dark-subtle text-dark border-dark-subtle', icon: 'bi-box-arrow-left', line: 'border-dark-subtle' },
};

async function postForm(url, payload = {}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url,
            method: 'POST',
            data: { csrf_token: csrfToken, ...payload },
            dataType: 'json',
            timeout: 10000,
            success: (json) => {
                if (json?.status === 'success') {
                    resolve(json);
                    return;
                }

                reject(new Error(json?.message || 'Request failed.'));
            },
            error: (xhr) => {
                const message = xhr?.responseJSON?.message || 'Request failed.';
                reject(new Error(message));
            },
        });
    });
}

function toast(icon, title) {
  if (!window.Swal) return;
  ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
}

function applyRequirementStatus(status) {
    const normalized = (status || 'not_submitted').toLowerCase();
    if (normalized === 'approved') {
        return { icon: 'bi-check-circle-fill text-success', badge: 'bg-success-subtle text-success', label: 'Approved' };
    }
    if (normalized === 'submitted') {
        return { icon: 'bi-hourglass-split text-warning', badge: 'bg-warning-subtle text-warning', label: 'Submitted' };
    }
    return { icon: 'bi-x-circle-fill text-danger', badge: 'bg-danger-subtle text-danger', label: 'Not submitted' };
}

function renderRequirementsChecklist(requirements = {}) {
    Object.entries(requirementMap).forEach(([reqType, selectors]) => {
        const ui = applyRequirementStatus(requirements[reqType]);
        const iconEl = document.querySelector(selectors.icon);
        const statusEl = document.querySelector(selectors.status);
        if (!iconEl || !statusEl) return;

        iconEl.className = `bi fs-6 flex-shrink-0 ${ui.icon}`;
        statusEl.className = `badge rounded-pill flex-shrink-0 text-nowrap ${ui.badge}`;
        statusEl.textContent = ui.label;
    });
}

function renderCompanies(companies = []) {
    const list = document.getElementById('companyList');
    if (!list) return;

    if (!companies.length) {
        list.innerHTML = `
            <div class="col-12">
                <div class="alert alert-warning mb-0">No companies with available slots were found for your program.</div>
            </div>
        `;
        return;
    }

    list.innerHTML = companies.map((company, index) => `
        <div class="col-12">
            <div class="card bg-blur-5 bg-semi-transparent border shadow-sm rounded-4 h-100 comcard ${index === 0 ? 'selected-card' : ''}"
                style="cursor:pointer;"
                data-company-uuid="${company.uuid}">
                <div class="card-body p-3 p-sm-4">
                    <div class="d-flex flex-column flex-md-row align-items-start gap-3">
                        <div class="flex-grow-1 min-w-0">
                            <h5 class="mb-1 fw-semibold text-body text-break">${company.name}</h5>
                            <p class="mb-0 text-muted small">${company.industry || '—'} &middot; ${company.city || '—'}</p>
                        </div>
                        <div class="ms-md-auto">
                            <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle px-3 py-2 fw-medium text-nowrap">
                                ${company.remaining_slots} slot${company.remaining_slots === 1 ? '' : 's'} left
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 pt-3 border-top border-secondary-subtle">
                        <div class="d-flex flex-wrap gap-2">
                            ${(company.accepted_programs || []).map((code) => `<small class="badge bg-secondary-subtle text-secondary border rounded-pill px-3 py-2 fw-normal">${code}</small>`).join('')}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `).join('');

    pageState.selectedCompany = companies[0] || null;
    bindCompanyCardEvents();
}

function bindCompanyCardEvents() {
    document.querySelectorAll('.comcard').forEach((card) => {
        card.addEventListener('click', () => {
            document.querySelectorAll('.comcard').forEach((el) => el.classList.remove('selected-card'));
            card.classList.add('selected-card');
            const companyUuid = card.getAttribute('data-company-uuid');
            pageState.selectedCompany = pageState.companies.find((c) => c.uuid === companyUuid) || null;
        });
    });
}

function goToStep(stepNumber) {
    ['step-1', 'step-2', 'step-3'].forEach((id, idx) => {
        const el = document.getElementById(id);
        if (!el) return;
        el.classList.toggle('d-none', idx + 1 !== stepNumber);
    });

    const step2Indicator = document.getElementById('step2Indicator');
    const step3Indicator = document.getElementById('step3Indicator');
    const step1ProgressBar = document.getElementById('step1ProgressBar');
    const step2ProgressBar = document.getElementById('step2ProgressBar');

    if (step1ProgressBar) step1ProgressBar.style.width = stepNumber >= 2 ? '100%' : '0%';
    if (step2ProgressBar) step2ProgressBar.style.width = stepNumber >= 3 ? '100%' : '0%';

    if (step2Indicator) {
        step2Indicator.className = `d-flex align-items-center justify-content-center rounded-circle fw-bold shadow-sm ${stepNumber >= 2 ? 'text-bg-success' : 'bg-secondary-subtle text-secondary'}`;
    }

    if (step3Indicator) {
        step3Indicator.className = `d-flex align-items-center justify-content-center rounded-circle fw-bold shadow-sm ${stepNumber >= 3 ? 'text-bg-success' : 'bg-secondary-subtle text-secondary'}`;
    }
}

function fillStep2Company() {
    const company = pageState.selectedCompany;
    if (!company) return;

    const name = document.getElementById('selectedCompanyName');
    const industry = document.getElementById('industryInfo');
    const location = document.getElementById('locationInfo');

    if (name) name.textContent = company.name || '—';
    if (industry) industry.textContent = company.industry || '—';
    if (location) location.textContent = company.city || '—';
}

function fillStep3Review() {
    const company = pageState.selectedCompany;
    const preferredDepartment = document.getElementById('preferredDepartment')?.value.trim() || '—';
    const coverLetter = document.getElementById('coverLetter')?.value.trim() || 'No message provided.';

    document.getElementById('confirmCompanyName').textContent = company?.name || '—';
    document.getElementById('confirmCompanyMeta').textContent = `${company?.industry || '—'} · ${company?.city || '—'}`;
    document.getElementById('confirmPreferredDepartment').textContent = preferredDepartment;
    document.getElementById('confirmCoverLetter').textContent = coverLetter;
}

function renderTimeline(statusLog = []) {
    const timeline = document.getElementById('applicationStatusTimeline');
    if (!timeline) return;

    if (!statusLog.length) {
        timeline.innerHTML = '<div class="text-muted small">No status updates yet.</div>';
        return;
    }

    timeline.innerHTML = statusLog.map((log) => {
        const meta = statusMeta[log.to_status] || statusMeta.pending;
        const note = log.note ? `<div class="text-muted small mt-1">${log.note}</div>` : '';
        return `
            <div class="d-flex align-items-start gap-3 p-3 border rounded-4 ${meta.line}">
                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0 ${meta.badge}" style="width: 36px; height: 36px;">
                    <i class="bi ${meta.icon}"></i>
                </span>
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <strong class="text-body">${(log.to_status || '').replace('_', ' ')}</strong>
                        <small class="text-muted">${log.date || ''}</small>
                    </div>
                    <div class="text-muted small">Updated by ${log.changed_by || 'System'}</div>
                    ${note}
                </div>
            </div>
        `;
    }).join('');
}

function renderApplicationDetails(application) {
    const noApp = document.getElementById('noApplicationsContainer');
    const statusBox = document.getElementById('applicationStatusContainer');
    const detailsBox = document.getElementById('applicationDetailsContainer');
    const applyNowBtn = document.getElementById('applyNowBtn');

    if (!application) {
        noApp?.classList.remove('d-none');
        statusBox?.classList.add('d-none');
        detailsBox?.classList.add('d-none');
        if (applyNowBtn) applyNowBtn.classList.remove('d-none');
        return;
    }

    noApp?.classList.add('d-none');
    statusBox?.classList.remove('d-none');
    detailsBox?.classList.remove('d-none');

    document.getElementById('detailCompanyName').textContent = application.company_name || '—';
    document.getElementById('detailIndustry').textContent = application.industry || '—';
    document.getElementById('detailLocation').textContent = application.city || '—';
    document.getElementById('detailWorkArrangement').textContent = application.work_setup || '—';
    document.getElementById('detailDepartmentPreference').textContent = application.preferred_dept || '—';
    document.getElementById('detailSubmitted').textContent = application.submitted_at || '—';

    const meta = statusMeta[application.status] || statusMeta.pending;
    const statusIcon = document.getElementById('statusIcon');
    statusIcon.className = `d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0 ${meta.badge}`;
    statusIcon.innerHTML = `<i class="bi ${meta.icon}"></i>`;

    document.getElementById('statusText').textContent = application.status_label || 'Unknown';
    document.getElementById('statusLastUpdated').textContent = application.reviewed_at ? `Last updated ${application.reviewed_at}` : 'Awaiting review';

    const badge = document.getElementById('currentStatusBadge');
    badge.className = `badge rounded-pill border px-3 py-2 fw-medium ${meta.badge}`;
    badge.textContent = application.status_label || 'Unknown';

    const withdrawBtn = document.getElementById('withdrawApplicationBtn');
    if (withdrawBtn) {
        withdrawBtn.classList.toggle('d-none', !application.can_withdraw);
        withdrawBtn.dataset.applicationUuid = application.uuid || '';
    }

    const downloadBtn = document.getElementById('downloadEndorsementBtn');
    if (downloadBtn) {
        downloadBtn.classList.toggle('d-none', !application.can_download_endorsement);
    }

    if (applyNowBtn) {
        applyNowBtn.classList.toggle('d-none', ['pending', 'approved', 'endorsed', 'active'].includes(application.status));
    }
}

async function loadPageData() {
    const [applicationRes, companiesRes] = await Promise.all([
        postForm(ENDPOINTS.getStudentApplication),
        postForm(ENDPOINTS.getAvailableCompanies),
    ]);

    pageState.application = applicationRes.application || null;
    pageState.statusLog = applicationRes.status_log || [];
    pageState.requirements = applicationRes.requirements || {};
    pageState.requirementsApproved = !!applicationRes.requirements_approved;
    pageState.canApply = !!applicationRes.can_apply;
    pageState.companies = companiesRes.companies || [];

    renderRequirementsChecklist(pageState.requirements);
    renderTimeline(pageState.statusLog);
    renderApplicationDetails(pageState.application);
    renderCompanies(pageState.companies);
}

function openIncompleteRequirementsModal() {
    const modalEl = document.getElementById('IncompleteRequirementsModal');
    if (!modalEl) return;
    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

function triggerEndorsementDownload() {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = ENDPOINTS.downloadEndorsement;
    form.target = '_blank';

    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = 'csrf_token';
    csrfInput.value = csrfToken;

    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
    form.remove();
}

function bindEvents() {
    document.getElementById('dashboardRefreshBtn')?.addEventListener('click', async () => {
        try {
            await loadPageData();
            toast('success', 'Application data refreshed');
        } catch (error) {
            toast('error', error.message);
        }
    });

    document.getElementById('applyNowBtn')?.addEventListener('click', (event) => {
        if (!pageState.canApply) {
            event.preventDefault();
            event.stopPropagation();
            openIncompleteRequirementsModal();
            return;
        }

        goToStep(1);
    });

    document.getElementById('proceedToDetailsBtn')?.addEventListener('click', () => {
        if (!pageState.selectedCompany) {
            toast('warning', 'Please select a company first.');
            return;
        }

        fillStep2Company();
        goToStep(2);
    });

    document.getElementById('backToCompanySelectionBtn')?.addEventListener('click', () => goToStep(1));

    document.getElementById('submitApplicationBtn')?.addEventListener('click', () => {
        fillStep3Review();
        goToStep(3);
    });

    document.getElementById('backToDetailsBtn')?.addEventListener('click', () => goToStep(2));

    document.getElementById('finalSubmitApplicationBtn')?.addEventListener('click', async () => {
        if (!pageState.selectedCompany) {
            toast('warning', 'Please select a company first.');
            return;
        }

        const preferredDept = document.getElementById('preferredDepartment')?.value.trim() || '';
        const coverLetter = document.getElementById('coverLetter')?.value.trim() || '';

        const submitBtn = document.getElementById('finalSubmitApplicationBtn');
        if (submitBtn) submitBtn.disabled = true;

        try {
            await postForm(ENDPOINTS.submitApplication, {
                company_uuid: pageState.selectedCompany.uuid,
                preferred_dept: preferredDept,
                cover_letter: coverLetter,
            });

            const applyModalEl = document.getElementById('ApplyFormsModal');
            const submittedModalEl = document.getElementById('ApplicationSubmittedModal');
            bootstrap.Modal.getOrCreateInstance(applyModalEl).hide();
            bootstrap.Modal.getOrCreateInstance(submittedModalEl).show();

            document.getElementById('preferredDepartment').value = '';
            document.getElementById('coverLetter').value = '';

            await loadPageData();
        } catch (error) {
            toast('error', error.message);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    document.getElementById('withdrawApplicationBtn')?.addEventListener('click', async () => {
        const appUuid = document.getElementById('withdrawApplicationBtn')?.dataset?.applicationUuid;
        if (!appUuid) return;

        const confirm = await Swal.fire({
            icon: 'warning',
            title: 'Withdraw application?',
            text: 'This will mark your application as withdrawn.',
            showCancelButton: true,
            confirmButtonText: 'Yes, withdraw',
            cancelButtonText: 'Cancel',
        });

        if (!confirm.isConfirmed) return;

        try {
            await postForm(ENDPOINTS.withdrawApplication, { application_uuid: appUuid });
            toast('success', 'Application withdrawn');
            await loadPageData();
        } catch (error) {
            toast('error', error.message);
        }
    });

    document.getElementById('downloadEndorsementBtn')?.addEventListener('click', () => {
        triggerEndorsementDownload();
    });
}

(async function init() {
    try {
        bindEvents();
        await loadPageData();
    } catch (error) {
        toast('error', error.message || 'Unable to load application data.');
    } finally {
        const loader = document.getElementById('pageLoader');
        if (loader) loader.classList.add('d-none');
    }
})();
