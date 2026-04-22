import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

const ENDPOINTS = {
  getRequirements: '../../../process/requirements/get_requirements',
  uploadRequirement: '../../../process/requirements/upload_requirement',
};

const REQUIREMENT_UI = {
  resume: { key: 'Resume', type: 'resume' },
  insurance: { key: 'PersonalAccidentInsurance', type: 'insurance' },
  parental_consent: { key: 'ParentConsent', type: 'parental_consent' },
  guardian_form: { key: 'ParentalGuardianInfo', type: 'guardian_form' },
  medical_certificate: { key: 'MedCert', type: 'medical_certificate' },
  nbi_clearance: { key: 'NbiClearance', type: 'nbi_clearance' },
};

function toast(icon, title) {
  if (!window.Swal) return;
  ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
}

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

async function postFile(url, formData) {
  return new Promise((resolve, reject) => {
    $.ajax({
      url,
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      timeout: 15000,
      success: (json) => {
        if (json?.status === 'success') {
          resolve(json);
          return;
        }
        reject(new Error(json?.message || 'Upload failed.'));
      },
      error: (xhr) => {
        const message = xhr?.responseJSON?.message || 'Upload failed.';
        reject(new Error(message));
      },
    });
  });
}

function updateProgressBar(approvedCount, totalCount) {
  const percent = totalCount ? (approvedCount / totalCount) * 100 : 0;
  const bar = document.getElementById('overallProgressBar');
  if (bar) {
    bar.style.width = `${percent}%`;
    bar.setAttribute('aria-valuenow', String(percent));
  }
  const submittedCount = document.getElementById('submittedCount');
  const total = document.getElementById('totalCount');
  if (submittedCount) submittedCount.textContent = String(approvedCount);
  if (total) total.textContent = String(totalCount);
}

function statusMeta(status) {
  const map = {
    approved: { badge: 'bg-success-subtle text-success-emphasis', border: 'border-success', label: 'Approved' },
    submitted: { badge: 'bg-warning-subtle text-warning-emphasis', border: 'border-warning', label: 'Submitted' },
    returned: { badge: 'bg-danger-subtle text-danger-emphasis', border: 'border-danger', label: 'Returned' },
    not_submitted: { badge: 'bg-secondary-subtle text-secondary-emphasis', border: 'border-secondary', label: 'Not submitted' },
  };
  return map[status] || map.not_submitted;
}

function bindLocalFilePreview(uiKey) {
  const fileInput = document.getElementById(`${uiKey}FileInputNS`);
  const uploadArea = document.getElementById(`upload${uiKey}AreaNS`);
  const selectedInfo = document.getElementById(`selected${uiKey}InfoNS`);
  const selectedFileName = document.getElementById(`selected${uiKey}FileNameNS`);
  const viewBtn = document.getElementById(`viewSelected${uiKey}BtnNS`);
  const removeBtn = document.getElementById(`removeSelected${uiKey}BtnNS`);

  if (!fileInput || !uploadArea || !selectedInfo || !selectedFileName || !viewBtn || !removeBtn) return;

  fileInput.onchange = () => {
    const file = fileInput.files?.[0];
    if (!file) {
      selectedInfo.classList.add('d-none');
      uploadArea.classList.remove('d-none');
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      toast('error', 'File size exceeds 5MB limit.');
      fileInput.value = '';
      return;
    }

    if (!file.name.toLowerCase().endsWith('.pdf')) {
      toast('error', 'Only PDF files are allowed.');
      fileInput.value = '';
      return;
    }

    selectedFileName.textContent = file.name;
    selectedInfo.classList.remove('d-none');
    uploadArea.classList.add('d-none');

    viewBtn.onclick = () => {
      const fileURL = URL.createObjectURL(file);
      window.open(fileURL, '_blank');
    };

    removeBtn.onclick = () => {
      fileInput.value = '';
      selectedInfo.classList.add('d-none');
      uploadArea.classList.remove('d-none');
    };
  };
}

function setRequirementView(req) {
  const cfg = REQUIREMENT_UI[req.req_type];
  if (!cfg) return;

  const uiKey = cfg.key;
  const container = document.getElementById(`${uiKey}Container`);
  const submittedCard = document.getElementById(`Submitted${uiKey}Card`);
  const notSubmittedCard = document.getElementById(`NotSubmitted${uiKey}Card`);
  if (!container || !submittedCard || !notSubmittedCard) return;

  container.setAttribute('data-requirement-uuid', req.uuid || '');

  const labelS = document.getElementById(`${uiKey}LabelS`);
  const descriptionS = document.getElementById(`${uiKey}DescriptionS`);
  const statusS = document.getElementById(`${uiKey}StatusS`);
  const fileNameS = document.getElementById(`${uiKey}FileNameS`);
  const submittedDateS = document.getElementById(`${uiKey}SubmittedDateS`);
  const studentNoteS = document.getElementById(`${uiKey}StudentNoteS`);
  const studentNoteContentS = document.getElementById(`${uiKey}StudentNoteContentS`);
  const coordinatorNoteS = document.getElementById(`${uiKey}CoordinatorNoteS`);
  const coordinatorNoteContentS = document.getElementById(`${uiKey}CoordinatorNoteContentS`);
  const uploadBtnS = document.getElementById(`upload${uiKey}BtnS`);
  const viewBtnS = document.getElementById(`view${uiKey}BtnS`);

  const labelNS = document.getElementById(`${uiKey}LabelNS`);
  const descriptionNS = document.getElementById(`${uiKey}DescriptionNS`);
  const cancelBtnNS = document.getElementById(`Cancel${uiKey}BtnNS`);
  const submitBtnNS = document.getElementById(`submit${uiKey}BtnNS`);
  const noteInputNS = document.getElementById(`${uiKey}NoteInputNS`);
  const fileInputNS = document.getElementById(`${uiKey}FileInputNS`);

  if (labelS) labelS.textContent = req.req_label || 'Requirement';
  if (descriptionS) descriptionS.textContent = req.req_label || '';
  if (labelNS) labelNS.textContent = req.req_label || 'Requirement';
  if (descriptionNS) descriptionNS.textContent = req.req_label || '';

  const meta = statusMeta(req.status);
  if (statusS) {
    statusS.className = `badge rounded-pill ${meta.badge}`;
    statusS.textContent = req.status_label || meta.label;
  }

  submittedCard.classList.remove('border-danger', 'border-warning', 'border-success', 'border-secondary');
  submittedCard.classList.add(meta.border);

  if (req.can_view) {
    submittedCard.classList.remove('d-none');
    notSubmittedCard.classList.add('d-none');

    if (fileNameS) fileNameS.textContent = req.file_name || 'No file submitted';
    if (submittedDateS) submittedDateS.textContent = req.submitted_at || 'N/A';

    if (viewBtnS) {
      viewBtnS.onclick = () => {
        if (!req.uuid) return;
        const fileURL = `../../../file_serve.php?type=requirement&req_uuid=${encodeURIComponent(req.uuid)}`;
        window.open(fileURL, '_blank');
      };
    }

    if (studentNoteS && studentNoteContentS) {
      if (req.student_note) {
        studentNoteS.classList.remove('d-none');
        studentNoteContentS.textContent = req.student_note;
      } else {
        studentNoteS.classList.add('d-none');
        studentNoteContentS.textContent = 'N/A';
      }
    }

    if (coordinatorNoteS && coordinatorNoteContentS) {
      if (req.return_reason) {
        coordinatorNoteS.classList.remove('d-none');
        coordinatorNoteContentS.textContent = req.return_reason;
      } else {
        coordinatorNoteS.classList.add('d-none');
        coordinatorNoteContentS.textContent = 'N/A';
      }
    }
  } else {
    submittedCard.classList.add('d-none');
    notSubmittedCard.classList.remove('d-none');
  }

  if (uploadBtnS) {
    uploadBtnS.classList.toggle('d-none', !req.can_upload);
    uploadBtnS.onclick = () => {
      submittedCard.classList.add('d-none');
      notSubmittedCard.classList.remove('d-none');
      if (cancelBtnNS) cancelBtnNS.classList.remove('d-none');
    };
  }

  if (cancelBtnNS) {
    cancelBtnNS.onclick = () => {
      if (req.can_view) {
        notSubmittedCard.classList.add('d-none');
        submittedCard.classList.remove('d-none');
      }
      cancelBtnNS.classList.add('d-none');
      if (fileInputNS) fileInputNS.value = '';
      const selectedInfo = document.getElementById(`selected${uiKey}InfoNS`);
      const uploadArea = document.getElementById(`upload${uiKey}AreaNS`);
      selectedInfo?.classList.add('d-none');
      uploadArea?.classList.remove('d-none');
    };
  }

  if (submitBtnNS) {
    submitBtnNS.onclick = async () => {
      const file = fileInputNS?.files?.[0];
      if (!file) {
        toast('warning', 'Please select a PDF file first.');
        return;
      }

      const fd = new FormData();
      fd.append('csrf_token', csrfToken);
      fd.append('req_type', req.req_type);
      fd.append('student_note', noteInputNS?.value?.trim() || '');
      fd.append('document', file);

      try {
        submitBtnNS.disabled = true;
        await postFile(ENDPOINTS.uploadRequirement, fd);
        toast('success', `${req.req_label} uploaded successfully.`);
        await loadRequirements();
      } catch (error) {
        toast('error', error.message);
      } finally {
        submitBtnNS.disabled = false;
      }
    };
  }

  bindLocalFilePreview(uiKey);

  if (req.status === 'not_submitted') {
    submittedCard.classList.add('d-none');
    notSubmittedCard.classList.remove('d-none');
  }
}

async function loadRequirements() {
  const data = await postForm(ENDPOINTS.getRequirements);
  const requirements = data.requirements || [];

  requirements.forEach((req) => setRequirementView(req));

  updateProgressBar(data.approved_count || 0, data.total || requirements.length || 6);

  const applyBtn = document.getElementById('applyNowBtn');
  if (applyBtn) {
    applyBtn.disabled = !data.can_apply;
    applyBtn.classList.toggle('disabled', !data.can_apply);
  }
}

function init() {
  document.getElementById('dashboardRefreshBtn')?.addEventListener('click', async () => {
    try {
      await loadRequirements();
      toast('success', 'Requirements refreshed.');
    } catch (error) {
      toast('error', error.message);
    }
  });

  loadRequirements()
    .catch((error) => toast('error', error.message || 'Failed to load requirements.'))
    .finally(() => {
      const loader = document.getElementById('pageLoader');
      if (loader) loader.classList.add('d-none');
    });
}

init();
