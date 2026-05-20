import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
const swalTheme = SwalTheme();

const ENDPOINTS = {
  getCertificates: '../../../process/student/get-certificates',
  downloadCertificate: '../../../process/student/download-certificate',
};

const state = {
  certificates: [],
  isLoading: false,
  currentModal: null,
};

function toast(icon, title) {
  if (window.Swal) {
    ToastVersion(swalTheme, title, icon, 2800, 'top-end', '8');
  }
}

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatDateTime(dateString) {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

function escapeHtml(text) {
  if (!text) return '';
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

function resetContainerLayout() {
  const $container = $('#certificatesContainer');
  if (!$container.length) return;
  $container.parent().addClass('p-4 p-md-5').removeClass('p-0');
  $container.closest('.card').addClass('bg-blur-5 bg-semi-transparent shadow-sm').removeClass('bg-transparent shadow-none border-0');
}

function loadCertificates() {
  if (state.isLoading) return;
  state.isLoading = true;
  
  resetContainerLayout();

  $('#certificatesContainer').html(`
    <div class="text-center py-5">
      <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Loading...</span></div>
      <p class="text-muted mb-0">Loading your certificates...</p>
    </div>
  `);

  $.ajax({
    url: ENDPOINTS.getCertificates,
    method: 'GET',
    dataType: 'json',
  }).done(res => {
    if (res.status === 'success') {
      state.certificates = res.data || [];
      renderCertificates();
    } else {
      toast('error', res.message || 'Failed to load certificates.');
      renderEmpty();
    }
  }).fail((xhr, status, error) => {
    Errors(xhr, status, error);
    renderEmpty();
  }).always(() => {
    state.isLoading = false;
  });
}

function renderEmpty() {
  resetContainerLayout();

  $('#certificatesContainer').html(`
    <div class="text-center py-5">
      <div class="display-4 text-muted mb-3"><i class="bi bi-award"></i></div>
      <h4 class="fw-bold mb-2 text-white-85">No Certificates Earned Yet</h4>
      <p class="text-muted mb-0 small">Once you complete your OJT hours and your coordinator issues your certificate, it will appear here.</p>
    </div>
  `);
}

function renderCertificates() {
  const $container = $('#certificatesContainer');
  if (!$container.length) return;

  if (!state.certificates || state.certificates.length === 0) {
    renderEmpty();
    return;
  }

  // Seamlessly adjust parent card design to let inner glassmorphic dashboard sit on animated background
  $container.parent().removeClass('p-4 p-md-5').addClass('p-0');
  $container.closest('.card').removeClass('bg-blur-5 bg-semi-transparent shadow-sm').addClass('bg-transparent shadow-none border-0');

  // Display the main/latest certificate in a beautiful split-panel dashboard layout
  const cert = state.certificates[0];
  const gradeNum = parseFloat(cert.weighted_score) || 0;
  const gradeClass = gradeNum >= 75 ? 'success' : gradeNum >= 60 ? 'warning' : 'danger';
  const statusText = cert.is_revoked ? 'REVOKED' : 'VALID & AUTHENTIC';
  const statusBadgeClass = cert.is_revoked ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-success-subtle text-success border-success-subtle';
  const studentName = `${cert.first_name || ''} ${cert.last_name || ''}`.trim() || 'Student';
  const baseUrl = window.location.origin;
  const verificationUrl = `${baseUrl}/Ojt-system/Src/Pages/Public/VerifyCertificate.php?token=${encodeURIComponent(cert.verification_token)}`;

  $container.html(`
    <div class="row g-4">
      <!-- Left side: Hero Badge and Download Actions -->
      <div class="col-12 col-lg-5">
        <div class="card bg-blur-15 border border-white-10 rounded-4 text-center p-4 p-md-5 h-100 position-relative overflow-hidden shadow-lg" 
             style="background: linear-gradient(135deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);">
          <!-- Ambient Light Overlay -->
          <div class="position-absolute top-0 start-0 w-100 h-100 z-n1 opacity-50" style="background: radial-gradient(circle at 50% 0%, var(--bs-primary) 0%, transparent 60%);"></div>
          
          <div class="text-muted small text-uppercase fw-bold tracking-wider mb-4">Credential Status</div>
          
          <!-- Outer circle ring -->
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-2 mb-4 mx-auto shadow-lg"
               style="width: 170px; height: 170px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
            <div class="d-flex flex-column align-items-center justify-content-center rounded-circle text-white w-100 h-100 shadow-inner animate-pulse">
              <img src="p../../../../../Assets/Images/Verified.gif" alt="OJT CERTIFIED" class="img-fluid w-auto" style="max-height: 80px;">
              <span class="text-white fw-bold tracking-wider d-none" style="font-size: 0.8rem;">OJT CERTIFIED</span>
            </div>
          </div>
          
          <div class="mb-4">
            <h4 class="fw-bold mb-2 text-white-85 text-break" style="font-family: 'Outfit', sans-serif;">${escapeHtml(cert.certificate_number)}</h4>
            <span class="badge ${statusBadgeClass} border rounded-pill px-3 py-1.5 fw-semibold small">
              <i class="bi bi-shield-fill-check me-1"></i>${statusText}
            </span>
          </div>

          <div class="d-flex flex-column gap-2 mb-4">
            <button class="btn btn-primary rounded-pill py-2.5 w-100 js-download-btn d-flex align-items-center justify-content-center gap-2 fw-semibold" data-cert-id="${cert.uuid}" ${cert.is_revoked ? 'disabled' : ''}>
              <i class="bi bi-download"></i> Download Official PDF
            </button>
            <button class="btn btn-outline-light rounded-pill py-2.5 w-100 js-preview-btn border-white-10 d-flex align-items-center justify-content-center gap-2" data-cert-id="${cert.uuid}">
              <i class="bi bi-eye"></i> View Full Template
            </button>
          </div>

          <hr class="border-white-10 my-4">

          <div class="row g-2 text-start">
            <div class="col-6">
              <small class="text-muted d-block mb-1">Issue Date</small>
              <h6 class="fw-semibold mb-0 text-white-85 small">${formatDate(cert.completion_date)}</h6>
            </div>
            <div class="col-6 border-start border-white-10 ps-3">
              <small class="text-muted d-block mb-1">Hours Completed</small>
              <h6 class="fw-semibold mb-0 text-white-85 small">${cert.hours_completed} Hours</h6>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Certificate Details & Verification Options -->
      <div class="col-12 col-lg-7">
        <div class="card bg-blur-15 border border-white-10 rounded-4 p-4 h-100 shadow-lg"
             style="background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);">
          
          <h5 class="fw-bold mb-4 text-white-85">Credential Details & Verification</h5>

          <!-- Student Profile Section -->
          <div class="p-3 rounded-4 border border-white-5 mb-4" style="background: rgba(255, 255, 255, 0.015);">
            <div class="d-flex align-items-center gap-3">
              <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                <i class="bi bi-person-fill fs-4"></i>
              </div>
              <div>
                <span class="text-muted small d-block">Certified Student</span>
                <h5 class="fw-bold text-white mb-0" style="font-family: 'Outfit', sans-serif;">${escapeHtml(studentName)}</h5>
              </div>
            </div>
          </div>

          <!-- Metadata Information Cards -->
          <div class="d-flex flex-column gap-3 mb-4">
            <div class="p-3 rounded-4 border border-white-5 hover-lift transition-all" style="background: rgba(255, 255, 255, 0.015);">
              <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-info-subtle text-info d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-building fs-5"></i>
                  </div>
                  <div>
                    <h6 class="fw-semibold mb-0 small text-white-85">Host Company / Institution</h6>
                    <span class="text-muted small">Assigned OJT Partner</span>
                  </div>
                </div>
                <div class="text-end">
                  <span class="fw-bold text-white-85 small text-break">${escapeHtml(cert.company_name || 'N/A')}</span>
                </div>
              </div>
            </div>

            <div class="p-3 rounded-4 border border-white-5 hover-lift transition-all" style="background: rgba(255, 255, 255, 0.015);">
              <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                  <div class="rounded-3 bg-success-subtle text-success d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                    <i class="bi bi-award fs-5"></i>
                  </div>
                  <div>
                    <h6 class="fw-semibold mb-0 small text-white-85">Grade Equivalent</h6>
                    <span class="text-muted small">Final OJT performance rating</span>
                  </div>
                </div>
                <div class="text-end">
                  <span class="badge bg-${gradeClass}-subtle text-${gradeClass}-emphasis rounded-pill px-3 py-1 fw-bold small">${cert.grade_equivalent || 'N/A'} (${cert.weighted_score}%)</span>
                </div>
              </div>
            </div>
          </div>

          <hr class="border-white-10 my-4">

          <h6 class="fw-bold text-white-85 mb-3">Quick Verification & Share</h6>
          <div class="row g-4 align-items-center">
            <div class="col-12 col-sm-4 text-center">
              <div class="p-2.5 bg-white rounded-4 d-inline-block shadow-sm">
                <img src="../../../process/certificate/qr-code.php?token=${encodeURIComponent(cert.verification_token)}&format=png&size=110" 
                     alt="Verification QR Code" 
                     style="width: 110px; height: 110px; border: none; display: block;" 
                     onerror="this.onerror=null; this.src='https://api.qrserver.com/v1/create-qr-code/?size=110x110&data='+encodeURIComponent('${verificationUrl}');" />
              </div>
              <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">Scan to verify</small>
            </div>
            
            <div class="col-12 col-sm-8 d-flex flex-column justify-content-between">
              <div class="bg-white-5 p-3 rounded-4 border border-white-5 mb-2.5">
                <span class="text-muted d-block small mb-1" style="font-size: 0.7rem;">VERIFICATION LINK</span>
                <code class="d-block word-break-all bg-dark bg-opacity-20 p-2 rounded-3 text-info small" style="font-size: 0.72rem;">
                  ${escapeHtml(verificationUrl)}
                </code>
              </div>
              <div class="d-flex gap-2 mt-2">
                <button class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm flex-grow-1 border-white-10 d-flex align-items-center justify-content-center gap-1.5" onclick="window.CertificateManager.copyToClipboard('${verificationUrl}')">
                  <i class="bi bi-clipboard px-1"></i> <span>Copy</span>
                </button>
                <button class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm flex-grow-1 border-white-10 d-flex align-items-center justify-content-center gap-1.5" onclick="window.CertificateManager.shareVia('email', '${verificationUrl}')">
                  <i class="bi bi-envelope px-1"></i> <span>Email</span>
                </button>
                <button class="btn btn-outline-light rounded-pill px-3 py-2 btn-sm flex-grow-1 border-white-10 d-flex align-items-center justify-content-center gap-1.5" onclick="window.CertificateManager.shareVia('facebook', '${verificationUrl}')">
                  <i class="bi bi-facebook px-1"></i> <span>Share</span>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  `);
}

function openModal(modalEl) {
  $(modalEl).modal('show');
  state.currentModal = modalEl;
}

function showPreviewModal(certId) {
  const cert = state.certificates.find(c => c.uuid === certId);
  if (!cert) {
    toast('error', 'Certificate not found.');
    return;
  }

  const studentName = `${cert.first_name || ''} ${cert.last_name || ''}`.trim() || 'Student';
  const gradeNum = parseFloat(cert.weighted_score) || 0;
  const gradeClass = gradeNum >= 75 ? 'success' : gradeNum >= 60 ? 'warning' : 'danger';

  $('#previewModalBody').html(`
    <div class="p-1">
      <!-- High Fidelity Certificate Mockup Frame -->
      <div class="certificate-mockup p-4 border border-2 border-warning border-opacity-50 rounded-4 text-center bg-dark bg-opacity-50 position-relative mb-4 overflow-hidden shadow-inner" 
           style="background-image: radial-gradient(circle at center, rgba(255, 215, 0, 0.04) 0%, transparent 80%);">
        
        <!-- Elegant Corner Accents -->
        <div class="position-absolute top-0 start-0 m-3 border-top border-start border-warning border-opacity-25" style="width: 24px; height: 24px;"></div>
        <div class="position-absolute top-0 end-0 m-3 border-top border-end border-warning border-opacity-25" style="width: 24px; height: 24px;"></div>
        <div class="position-absolute bottom-0 start-0 m-3 border-bottom border-start border-warning border-opacity-25" style="width: 24px; height: 24px;"></div>
        <div class="position-absolute bottom-0 end-0 m-3 border-bottom border-end border-warning border-opacity-25" style="width: 24px; height: 24px;"></div>

        <!-- Verified Watermark overlay -->
        <div class="position-absolute top-50 start-50 translate-middle pointer-events-none opacity-5 text-warning fw-bold tracking-widest text-uppercase" 
             style="transform: translate(-50%, -50%) rotate(-30deg) !important;font-size: 2.8rem;border: 4px double rgba(255, 193, 7, 0.13);padding: 8px 16px;color: rgba(255, 193, 7, .3) !important;">
          VERIFIED SYSTEM CREDENTIAL
        </div>
        
        <div class="mb-2 text-warning fw-bold tracking-widest uppercase" style="font-size: 0.65rem; letter-spacing: 2px;">CERTIFICATE OF COMPLETION</div>
        <div class="text-muted small mb-3">PRESENTED TO</div>
        
        <h4 class="mb-2 fw-bold text-white" style="font-family: 'Outfit', sans-serif;">${escapeHtml(studentName)}</h4>
        <div class="border-bottom border-warning border-opacity-20 mx-auto mb-3" style="width: 140px;"></div>
        
        <p class="text-muted mb-2" style="font-size: 0.8rem;">for outstanding compliance and completion of the required</p>
        <h3 class="text-primary fw-bold mb-2" style="font-family: 'Outfit', sans-serif;">${cert.hours_completed} Hours</h3>
        
        <p class="text-muted mb-2" style="font-size: 0.8rem;">of On-the-Job Training under</p>
        <h6 class="text-info fw-bold mb-4">${escapeHtml(cert.company_name || 'N/A')}</h6>
        
        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-white-5 text-start">
          <div>
            <span class="text-muted d-block" style="font-size: 0.65rem;">GRADE EQUIVALENT</span>
            <span class="fw-bold text-success" style="font-size: 0.85rem;">${cert.grade_equivalent || 'N/A'}</span>
          </div>
          <div class="text-end">
            <span class="text-muted d-block" style="font-size: 0.65rem;">CREDENTIAL ID</span>
            <span class="font-monospace text-white-50" style="font-size: 0.7rem;">${escapeHtml(cert.certificate_number)}</span>
          </div>
        </div>
      </div>

      <dl class="row g-2 mb-4 text-white-85 px-2">
        <dt class="col-6 text-muted small text-uppercase mb-1">Issue Date</dt>
        <dd class="col-6 fw-semibold text-end text-white-85 small mb-1">${formatDate(cert.completion_date)}</dd>
        
        <dt class="col-6 text-muted small text-uppercase mb-1">Generated On</dt>
        <dd class="col-6 fw-semibold text-end text-white-85 small mb-1">${formatDateTime(cert.generated_at)}</dd>
        
        <dt class="col-6 text-muted small text-uppercase mb-1">Weighted Score</dt>
        <dd class="col-6 fw-semibold text-end text-white-85 small mb-1">${cert.weighted_score || '0.00'}%</dd>
      </dl>
      
      ${cert.is_revoked ? `
      <div class="alert alert-danger border-0 rounded-4 mb-4">
        <div class="d-flex gap-2">
          <i class="bi bi-x-circle fs-5"></i>
          <div>
            <h6 class="fw-bold mb-0">Certificate Revoked</h6>
            <small>This certificate is no longer valid or authentic.</small>
          </div>
        </div>
      </div>
      ` : ''} 

      <div class="d-flex gap-2 w-100">
        <button class="btn btn-primary rounded-pill px-4 py-2.5 flex-grow-1 fw-semibold" onclick="window.CertificateManager.downloadCertificate('${cert.uuid}')" ${cert.is_revoked ? 'disabled' : ''}>
          <i class="bi bi-download me-1"></i> Download PDF
        </button>
        <button class="btn btn-outline-light rounded-pill px-4 py-2.5 flex-grow-1 border-white-10" data-bs-dismiss="modal">
          Close
        </button>
      </div>
    </div>
  `);

  openModal(document.getElementById('previewModal'));
}

function downloadCertificate(certId) {
  const cert = state.certificates.find(c => c.uuid === certId);
  if (!cert) {
    toast('error', 'Certificate not found.');
    return;
  }

  if (cert.is_revoked) {
    toast('error', 'Cannot download revoked certificate.');
    return;
  }

  const link = document.createElement('a');
  link.href = ENDPOINTS.downloadCertificate + '?certificate_uuid=' + certId;
  link.download = cert.certificate_number + '.pdf';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);

  toast('success', 'Certificate download initiated.');
}

function copyToClipboard(text) {
  navigator.clipboard.writeText(text).then(() => {
    toast('success', 'Copied to clipboard!');
  }).catch(err => {
    console.error('Failed to copy:', err);
    toast('error', 'Failed to copy to clipboard.');
  });
}

function shareVia(platform, url, text) {
  text = text || 'Check out my OJT certificate';
  let shareUrl = '';

  switch(platform) {
    case 'email':
      shareUrl = `mailto:?subject=My OJT Certificate&body=${encodeURIComponent(text + '\n\n' + url)}`;
      break;
    case 'facebook':
      shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
      break;
    case 'twitter':
      shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}`;
      break;
    default:
      return;
  }

  if (platform === 'email') {
    window.location.href = shareUrl;
  } else {
    window.open(shareUrl, 'Share', 'width=600,height=400');
  }
}

$(document).ready(() => {
  loadCertificates();

  $('#certificateRefreshBtn').on('click', function () {
    const $icon = $(this).find('i');
    $icon.addClass('spin');
    setTimeout(() => $icon.removeClass('spin'), 600);
    loadCertificates();
  });

  $(document).on('click', '.js-preview-btn', function () {
    showPreviewModal($(this).data('cert-id'));
  });

  $(document).on('click', '.js-download-btn', function () {
    downloadCertificate($(this).data('cert-id'));
  });
});

window.CertificateManager = {
  downloadCertificate,
  copyToClipboard,
  shareVia,
};

const style = document.createElement('style');
style.textContent = `
  @keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
  }
  .bi.spin {
    animation: spin 0.6s linear;
  }
  .hover-lift {
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1), border-color 0.25s ease !important;
  }
  .hover-lift:hover {
    transform: translateY(-5px);
    border-color: rgba(255, 255, 255, 0.15) !important;
    box-shadow: 0 12px 25px rgba(0, 0, 0, 0.2) !important;
    background: rgba(255, 255, 255, 0.05) !important;
  }
  .bg-white-5 {
    background: rgba(255, 255, 255, 0.05) !important;
  }
  .border-white-10 {
    border-color: rgba(255, 255, 255, 0.1) !important;
  }
  .border-white-5 {
    border-color: rgba(255, 255, 255, 0.05) !important;
  }
  .text-white-85 {
    color: rgba(255, 255, 255, 0.85) !important;
  }
  .word-break-all {
    word-break: break-all !important;
  }
  .certificate-mockup {
    box-shadow: inset 0 0 15px rgba(0, 0, 0, 0.4);
  }
  @keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
  }
  .animate-pulse {
    animation: pulse-subtle 2s infinite ease-in-out;
  }
`;
document.head.appendChild(style);
