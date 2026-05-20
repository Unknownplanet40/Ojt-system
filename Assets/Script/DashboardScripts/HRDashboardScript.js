import { SwalTheme } from "../SystemTheme.js";

const appRoot = document.querySelector('meta[name="app-root"]')?.content || '/Ojt-system';
const currentUserUuid = document.body.dataset.uuid || '';

const HRDashboard = {
    supervisorsData: [],

    init() {
        this.cacheDOM();
        this.bindEvents();
        this.fetchDashboardData();
    },

    bindEvents() {
        this.elSupervisorsList.addEventListener('click', (e) => {
            const btn = e.target.closest('button[data-uuid]');
            if (btn && !btn.hasAttribute('disabled')) {
                this.handleManageClick(btn.dataset.uuid);
            }
        });

        // Initialize Bootstrap Modals
        if (typeof bootstrap !== 'undefined') {
            if (document.getElementById('uploadDocModal')) {
                this.uploadDocModal = new bootstrap.Modal(document.getElementById('uploadDocModal'));
                this.elUploadDocBtn?.addEventListener('click', () => {
                    document.getElementById('uploadDocForm').reset();
                    this.uploadDocModal.show();
                });
                document.getElementById('uploadDocForm')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleDocumentUpload(e.target);
                });
            }

            if (document.getElementById('requestSlotsModal')) {
                this.requestSlotsModal = new bootstrap.Modal(document.getElementById('requestSlotsModal'));
                const elRequestBtn = document.getElementById('hrRequestSlotsBtn');
                elRequestBtn?.addEventListener('click', () => {
                    document.getElementById('requestSlotsForm').reset();
                    this.requestSlotsModal.show();
                });
                document.getElementById('requestSlotsForm')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleSlotRequest(e.target);
                });
            }

            if (document.getElementById('addSupervisorModal')) {
                this.addSupervisorModal = new bootstrap.Modal(document.getElementById('addSupervisorModal'));
                const elAddBtn = document.getElementById('hrAddSupervisorBtn');
                elAddBtn?.addEventListener('click', () => {
                    document.getElementById('addSupervisorForm').reset();
                    this.addSupervisorModal.show();
                });
                document.getElementById('addSupervisorForm')?.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleAddSupervisor(e.target);
                });
            }
        }
    },

    async handleDocumentUpload(form) {
        try {
            const submitBtn = document.getElementById('uploadDocSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Uploading...';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            
            const response = await fetch(`${appRoot}/process/supervisor/hr_upload_document`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.status === 'success') {
                this.uploadDocModal.hide();
                Swal.fire({ theme: SwalTheme(), icon: 'success', title: 'Uploaded!', text: result.message, timer: 2000, showConfirmButton: false, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
                this.fetchDashboardData();
            } else {
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Upload Failed', text: result.message, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            }
        } catch (error) {
            console.error('Upload Error:', error);
            Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: 'A network error occurred while uploading.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        } finally {
            const submitBtn = document.getElementById('uploadDocSubmitBtn');
            submitBtn.innerHTML = '<i class="bi bi-cloud-upload me-2"></i>Upload Document';
            submitBtn.disabled = false;
        }
    },

    async handleSlotRequest(form) {
        try {
            const submitBtn = document.getElementById('requestSlotsSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Sending...';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            
            const response = await fetch(`${appRoot}/process/supervisor/hr_request_slots`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.status === 'success') {
                this.requestSlotsModal.hide();
                Swal.fire({ theme: SwalTheme(), icon: 'success', title: 'Request Sent', text: result.message, timer: 3000, showConfirmButton: false, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            } else {
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Request Failed', text: result.message, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            }
        } catch (error) {
            console.error('Request Error:', error);
            Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: 'A network error occurred while submitting the request.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        } finally {
            const submitBtn = document.getElementById('requestSlotsSubmitBtn');
            submitBtn.innerHTML = '<i class="bi bi-send me-2"></i>Submit Request';
            submitBtn.disabled = false;
        }
    },

    async handleAddSupervisor(form) {
        try {
            const submitBtn = document.getElementById('addSupervisorSubmitBtn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving...';
            submitBtn.disabled = true;

            const formData = new FormData(form);
            
            const response = await fetch(`${appRoot}/process/supervisor/create_supervisor`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();
            
            if (result.status === 'success') {
                this.addSupervisorModal.hide();
                
                const exportData = {
                    full_name: `${formData.get('first_name')} ${formData.get('last_name')}`,
                    temp_password: result.temp_password,
                    email: formData.get('email'),
                    company_name: this.companyData?.name || 'Your Company',
                    position: formData.get('position'),
                    department: formData.get('department'),
                    mobile: formData.get('mobile')
                };

                Swal.fire({
                    theme: SwalTheme(),
                    icon: 'success',
                    title: 'Supervisor Created!',
                    html: `
                        <p class="mb-3">${result.message}</p>
                        <div class="alert bg-primary bg-opacity-10 border border-primary-subtle text-start p-3 rounded-3">
                            <div class="mb-1 text-muted small">Supervisor Email (Login Username):</div>
                            <div class="fw-bold mb-2">${formData.get('email')}</div>
                            <div class="mb-1 text-muted small">Temporary Password:</div>
                            <div class="fs-5 fw-bold text-primary font-monospace">${result.temp_password}</div>
                        </div>
                        <p class="small text-danger mt-3 mb-0"><i class="bi bi-exclamation-circle me-1"></i>Please copy this password or export the details now. It will not be shown again!</p>
                    `,
                    showDenyButton: true,
                    confirmButtonText: '<i class="bi bi-check-circle me-1"></i> Got It!',
                    denyButtonText: '<i class="bi bi-file-pdf me-1"></i> Export PDF',
                    customClass: {
                        popup: 'glass-ui glass-ui-strong rounded-4 border-0 shadow-lg',
                        actions: 'w-100 d-flex flex-column gap-2 px-3',
                        confirmButton: 'btn btn-primary w-100 rounded-pill py-2 shadow-sm',
                        denyButton: 'btn btn-outline-primary w-100 rounded-pill py-2 shadow-sm'
                    }
                }).then((sweetResult) => {
                    if (sweetResult.isDenied) {
                        this.exportSupervisorPdf(exportData);
                    }
                });
                this.fetchDashboardData();
            } else {
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Failed to Create', text: result.message || 'Validation error occurred.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            }
        } catch (error) {
            console.error('Create Supervisor Error:', error);
            Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: 'A network error occurred while creating the supervisor.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        } finally {
            const submitBtn = document.getElementById('addSupervisorSubmitBtn');
            submitBtn.innerHTML = '<i class="bi bi-save me-2"></i>Save Supervisor';
            submitBtn.disabled = false;
        }
    },

    async exportSupervisorPdf(exportData) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const response = await fetch(`${appRoot}/process/supervisors/export_supervisor_pdf`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    csrf_token: csrfToken,
                    supervisor_data: JSON.stringify(exportData)
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const contentType = response.headers.get('Content-Type') || '';
            if (contentType.includes('application/json')) {
                const result = await response.json();
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Export Failed', text: result.message, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
                return;
            }

            const pdfBlob = await response.blob();
            
            const safeName = (exportData && exportData.full_name ? exportData.full_name : 'Supervisor').replace(/[^a-zA-Z0-9_]/g, '_');
            let fileName = `${safeName}_Supervisor_Account_Details.pdf`;

            const pdfFileBlob = new Blob([pdfBlob], { type: 'application/pdf' });

            const blobUrl = window.URL.createObjectURL(pdfFileBlob);
            const link = document.createElement('a');
            link.href = blobUrl;
            link.download = fileName;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(blobUrl);

            Swal.fire({ theme: SwalTheme(), icon: 'success', title: 'Success', text: 'PDF export started.', timer: 2000, showConfirmButton: false, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        } catch (error) {
            console.error('Export Error:', error);
            Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Export Failed', text: 'An error occurred while exporting the PDF.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        }
    },

    async handleManageClick(uuid) {
        if (typeof Swal === 'undefined') return;

        const sup = this.supervisorsData.find(s => s.uuid === uuid);
        if (!sup) return;

        const isHr = parseInt(sup.is_hr_admin) === 1;
        const isActive = parseInt(sup.is_active) === 1;

        const result = await Swal.fire({
            theme: SwalTheme(),
            title: `Manage ${sup.first_name} ${sup.last_name}`,
            text: 'Select an action to perform:',
            icon: 'question',
            showCancelButton: true,
            showDenyButton: true,
            confirmButtonText: isHr ? 'Revoke HR Access' : 'Promote to HR Admin',
            denyButtonText: isActive ? 'Deactivate Account' : 'Activate Account',
            cancelButtonText: 'Cancel',
            customClass: {
                popup: 'glass-ui glass-ui-strong rounded-4 shadow-lg border-0',
                actions: 'w-100 d-flex flex-column gap-2 px-3',
                confirmButton: `btn ${isHr ? 'btn-danger' : 'btn-primary'} w-100 rounded-pill py-2 shadow-sm`,
                denyButton: `btn ${isActive ? 'btn-danger' : 'btn-success'} w-100 rounded-pill py-2 shadow-sm`,
                cancelButton: 'btn btn-secondary w-100 rounded-pill py-2 shadow-sm'
            }
        });

        if (result.isConfirmed) {
            this.executeManageAction(uuid, 'toggle_hr');
        } else if (result.isDenied) {
            this.executeManageAction(uuid, 'toggle_active');
        }
    },

    async executeManageAction(uuid, actionType) {
        try {
            const formData = new FormData();
            formData.append('target_uuid', uuid);
            formData.append('action', actionType);
            
            const response = await fetch(`${appRoot}/process/supervisor/manage_supervisor`, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            const result = await response.json();
            if (result.status === 'success') {
                Swal.fire({ theme: SwalTheme(), icon: 'success', title: 'Success', text: result.message, timer: 2000, showConfirmButton: false, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
                this.fetchDashboardData(); 
            } else {
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: result.message, customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            }
        } catch (err) {
            console.error(err);
            Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: 'Network error occurred.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
        }
    },

    cacheDOM() {
        this.elTotalSupervisors = document.getElementById('hrTotalSupervisors');
        this.elTotalInterns = document.getElementById('hrTotalInterns');
        this.elRemainingSlots = document.getElementById('hrRemainingSlots');
        this.elTotalSlotsText = document.getElementById('hrTotalSlotsText');
        this.elSupervisorsList = document.getElementById('hrSupervisorsList');
        
        this.elInternsList = document.getElementById('hrInternsList');
        this.elDocumentsList = document.getElementById('hrDocumentsList');
        this.elUploadDocBtn = document.getElementById('hrUploadDocBtn');
    },

    async fetchDashboardData() {
        try {
            const response = await fetch(`${appRoot}/process/supervisor/get_hr_analytics`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error('Failed to fetch HR data');
            }

            const result = await response.json();

            if (result.status === 'success') {
                this.supervisorsData = result.supervisors || [];
                this.companyData = result.company || null;
                this.documentsData = result.documents || [];
                this.studentsData = result.students || [];

                this.renderAnalytics(result.analytics);
                this.renderSupervisors(this.supervisorsData);
                this.renderInterns(this.studentsData);
                this.renderDocuments(this.documentsData);
            } else {
                throw new Error(result.message || 'Unknown error occurred');
            }
        } catch (error) {
            console.error(error);
            if (typeof Swal !== 'undefined') {
                Swal.fire({ theme: SwalTheme(), icon: 'error', title: 'Error', text: 'Unable to load dashboard data. Please try again.', customClass: { popup: 'glass-ui glass-ui-strong rounded-4 border-0' } });
            } else {
                alert('Unable to load dashboard data. Please try again.');
            }
            
            this.elTotalSupervisors.textContent = '—';
            this.elTotalInterns.textContent = '—';
            this.elRemainingSlots.textContent = '—';
            this.elTotalSlotsText.textContent = 'Error loading data';
            this.elSupervisorsList.innerHTML = `<div class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load supervisors list.</div>`;
            if(this.elInternsList) this.elInternsList.innerHTML = `<div class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load interns list.</div>`;
            if(this.elDocumentsList) this.elDocumentsList.innerHTML = `<div class="text-center py-4 text-danger"><i class="bi bi-exclamation-triangle me-2"></i>Failed to load documents list.</div>`;
        }
    },

    renderAnalytics(analytics) {
        this.elTotalSupervisors.textContent = analytics.total_supervisors;
        this.elTotalInterns.textContent = analytics.total_interns;
        this.elRemainingSlots.textContent = analytics.remaining_slots;
        this.elTotalSlotsText.textContent = `Out of ${analytics.total_slots} total slots`;
    },

    renderSupervisors(supervisors) {
        if (supervisors.length === 0) {
            this.elSupervisorsList.innerHTML = `
                <div class="col-12 text-center py-5">
                    <div class="text-muted"><i class="bi bi-people fs-1 d-block mb-2"></i>No supervisors found in your company.</div>
                </div>
            `;
            return;
        }
        this.elSupervisorsList.className = 'row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 m-0';

        let html = '';
        supervisors.forEach(sup => {
            const name = `${sup.first_name} ${sup.last_name}`;
            
            const roleBadge = parseInt(sup.is_hr_admin) === 1 
                ? '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle px-2 py-1 shadow-sm"><i class="bi bi-shield-lock me-1"></i>HR Admin</span>' 
                : '<span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle px-2 py-1 shadow-sm">Staff</span>';
                
            const statusBadge = parseInt(sup.is_active) === 1
                ? '<span class="badge bg-success bg-opacity-10 text-success border border-success-subtle px-2 py-1 shadow-sm"><i class="bi bi-circle-fill small me-1" style="font-size: 0.5rem;"></i>Active</span>'
                : '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle px-2 py-1 shadow-sm"><i class="bi bi-circle-fill small me-1" style="font-size: 0.5rem;"></i>Inactive</span>';
            
            const avatar = sup.profile_path ? `${appRoot}/${sup.profile_path}` : `https://placehold.co/80x80?text=No+Photo`;

            const isOwnAccount = sup.user_uuid === currentUserUuid;
            const manageBtnAttr = isOwnAccount ? 'disabled title="You cannot manage your own account from here."' : '';
            const manageBtnClass = isOwnAccount ? 'btn-outline-secondary opacity-50' : 'btn-outline-primary hover-lift';
            const manageBtnText = isOwnAccount ? '<i class="bi bi-person-check me-1"></i> Active User' : '<i class="bi bi-gear-fill me-1"></i> Manage';

            html += `
                <div class="col">
                    <div class="card h-100 bg-body-tertiary border border-opacity-10 rounded-4 transition-all hover-shadow-lg text-center overflow-hidden">
                        <div class="bg-primary bg-opacity-10 py-4 d-flex justify-content-center align-items-center position-relative">
                            <img src="${avatar}" alt="${name}" class="rounded-circle object-fit-cover shadow-sm border border-3 border-white z-1" style="width: 80px; height: 80px;" onerror="this.onerror=null; this.src='https://placehold.co/80x80?text=No+Photo';">
                        </div>
                        <div class="card-body mt-2">
                            <h5 class="fw-bold text-body mb-1">${name}</h5>
                            <small class="text-muted d-block mb-3"><i class="bi bi-briefcase me-1"></i>${sup.position} &bull; ${sup.department}</small>
                            <div class="d-flex justify-content-center flex-wrap gap-2 mb-4">
                                ${roleBadge}
                                ${statusBadge}
                            </div>
                            <button class="btn btn-sm ${manageBtnClass} rounded-pill px-4 shadow-sm w-100" data-uuid="${sup.uuid}" ${manageBtnAttr}>
                                ${manageBtnText}
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        this.elSupervisorsList.innerHTML = html;
    },

    renderInterns(students) {
        if (!this.elInternsList) return;
        
        if (!students || students.length === 0) {
            this.elInternsList.innerHTML = `
                <div class="text-center py-4">
                    <div class="text-muted"><i class="bi bi-mortarboard fs-2 d-block mb-2"></i>No deployed interns.</div>
                </div>
            `;
            return;
        }

        let html = '';
        students.forEach(student => {
            const studentName = student.full_name || [student.first_name, student.last_name].filter(Boolean).join(" ");
            const avatar = student.profile_path ? `${appRoot}/${student.profile_path}` : `https://placehold.co/40x40?text=User`;
            
            let subtitleParts = [];
            if (student.program && student.program.trim() !== "") subtitleParts.push(student.program);
            if (student.year_level && String(student.year_level).trim() !== "") subtitleParts.push(`Year ${student.year_level}`);
            const studentSubtitle = subtitleParts.length > 0 ? subtitleParts.join(" - ") : "Not Specified";

            html += `
                <div class="alert bg-blur-5 bg-semi-transparent bg-secondary-subtle text-body border d-flex align-items-center gap-3 mb-2 py-2 px-3 rounded-4 shadow-sm" role="alert">
                    <img src="${avatar}" alt="Avatar" class="rounded-circle shadow-sm object-fit-cover" width="45" height="45" onerror="this.onerror=null; this.src='https://placehold.co/45x45?text=User';">
                    <div class="d-flex flex-column flex-grow-1 min-w-0">
                        <span class="fw-bold small text-truncate">${studentName}</span>
                        <small class="text-muted text-truncate">${studentSubtitle}</small>
                    </div>
                </div>
            `;
        });
        
        this.elInternsList.innerHTML = html;
    },

    renderDocuments(documents) {
        if (!this.elDocumentsList) return;

        if (!documents || documents.length === 0) {
            this.elDocumentsList.innerHTML = `
                <div class="text-center py-5 h-100 d-flex flex-column justify-content-center">
                    <div class="text-muted"><i class="bi bi-file-earmark-x fs-1 d-block mb-2"></i>No documents found.</div>
                </div>
            `;
            return;
        }

        const filenameMap = {
            moa: "Memorandum of Agreement",
            nda: "Non-Disclosure Agreement",
            insurance: "Insurance Certificate",
            bir_cert: "BIR Certificate",
            sec_dti: "SEC/DTI Registration",
            other: "Other Document"
        };

        let html = '';
        documents.forEach(doc => {
            let icon = "bi-file-earmark";
            if(doc.doc_type === "moa") icon = "bi-file-earmark-text text-primary";
            else if(doc.doc_type === "nda") icon = "bi-file-lock text-danger";
            else if(doc.doc_type === "insurance") icon = "bi-shield-check text-success";
            
            const validUntilStr = doc.valid_until ? new Date(doc.valid_until).toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" }) : "Indefinite";
            const isExpiring = doc.valid_until && (new Date(doc.valid_until) - new Date()) / (1000 * 60 * 60 * 24) < 30;

            html += `
                <div class="alert bg-blur-5 bg-semi-transparent bg-secondary-subtle text-body border d-flex flex-column gap-2 mb-2 p-3 rounded-4 shadow-sm" role="alert">
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi ${icon} fs-3 flex-shrink-0"></i>
                        <div class="d-flex flex-column flex-grow-1 min-w-0">
                            <span class="fw-bold small text-truncate">
                                <a href="${appRoot}/file_serve.php?uuid=${doc.uuid}&action=inline" target="_blank" class="text-decoration-none text-body-emphasis">
                                    ${filenameMap[doc.doc_type] || doc.file_name}
                                </a>
                            </span>
                            <small class="text-muted">${doc.doc_type.toUpperCase()}</small>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mt-1 border-top border-secondary border-opacity-10 pt-2">
                        <small class="${isExpiring ? 'text-danger fw-medium' : 'text-muted'}">Expires: ${validUntilStr}</small>
                    </div>
                </div>
            `;
        });
        
        this.elDocumentsList.innerHTML = html;
    }
};

HRDashboard.init();
