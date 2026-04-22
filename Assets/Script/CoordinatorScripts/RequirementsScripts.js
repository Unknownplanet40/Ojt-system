import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

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

function renderStudents(students = []) {
  const container = document.getElementById('requirementsContainer');
  if (!container) return;

  if (!students.length) {
    container.innerHTML = '<div class="col"><div class="alert alert-secondary mb-0">No students found in this batch.</div></div>';
    return;
  }

  container.innerHTML = students.map((student) => {
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

    const canReview = Number(student.submitted_count || 0) > 0;

    return `
      <div class="col">
        <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4">
          <div class="card-body">
            <div class="hstack">
              <img src="https://placehold.co/64x64/483a0f/c7993d/png?text=${student.initials || 'ST'}&font=poppins"
                alt="profile picture" class="rounded-circle m-2 mx-3 me-3" style="width: 26px; height: 26px;">
              <div class="vstack">
                <h6 class="card-title mb-0">${student.full_name || 'Student'}</h6>
                <p class="card-text mb-0">${student.program_code || '—'} - ${student.year_label || '—'}</p>
                <div class="hstack gap-1">${dots}</div>
              </div>
              <span class="badge ${badge.cls} rounded-pill ms-auto">${badge.text}</span>
              <button class="btn btn-sm ms-3 px-4 py-2 rounded-2 ${canReview ? 'btn-outline-secondary text-light' : 'btn-outline-dark text-secondary disabled'}"
                data-student-uuid="${student.student_uuid}" data-action="review" ${canReview ? '' : 'disabled'}>
                ${canReview ? 'Review' : 'No Pending'}
              </button>
            </div>
          </div>
        </div>
        <hr>
      </div>
    `;
  }).join('');

  container.querySelectorAll('[data-action="review"]').forEach((btn) => {
    btn.addEventListener('click', () => openReviewModal(btn.getAttribute('data-student-uuid')));
  });
}

function fillModal(req, studentUuid) {
  state.selectedRequirement = req;
  state.selectedStudentUuid = studentUuid;

  document.getElementById('modalDocType').textContent = req.req_label || 'Requirement';
  document.getElementById('modalStudentName').textContent = req.student_name || 'Student';
  document.getElementById('modalStudentDocumentName').textContent = req.file_name || 'No file';
  document.getElementById('modalStudentDocumentStatus').textContent = req.status_label || req.status || 'Unknown';
  document.getElementById('documentFileName').textContent = req.file_name || 'No file submitted';
  document.getElementById('documentdate').textContent = req.submitted_at ? `Submitted on: ${req.submitted_at}` : 'No submission date';
  document.getElementById('studentNotesContent').textContent = req.student_note || 'No notes provided by the student.';
  document.getElementById('reviewNote').value = '';

  const fileURL = `../../../file_serve.php?type=requirement&req_uuid=${encodeURIComponent(req.uuid)}`;
  document.getElementById('viewDocumentBtn').onclick = () => window.open(fileURL, '_blank');
  document.getElementById('downloadDocumentBtn').onclick = () => window.open(`${fileURL}&action=download`, '_blank');
}

async function openReviewModal(studentUuid) {
  try {
    const data = await postForm(ENDPOINTS.getRequirements, {
      student_uuid: studentUuid,
      batch_uuid: state.selectedBatchUuid || '',
    });

    const submitted = (data.requirements || []).filter((r) => r.status === 'submitted');
    if (!submitted.length) {
      toast('info', 'No submitted document to review for this student.');
      await loadOverview();
      return;
    }

    const req = submitted[0];
    req.student_name = document.querySelector(`[data-action="review"][data-student-uuid="${studentUuid}"]`)?.closest('.hstack')?.querySelector('.card-title')?.textContent?.trim() || 'Student';
    fillModal(req, studentUuid);

    const modalEl = document.getElementById('requirementReviewModal');
    bootstrap.Modal.getOrCreateInstance(modalEl).show();
  } catch (error) {
    toast('error', error.message);
  }
}

async function loadOverview() {
  const data = await postForm(ENDPOINTS.getOverview);
  renderStudents(data.overview || []);

  const studentCount = document.getElementById('StudentCount');
  if (studentCount) studentCount.textContent = `Total Students: ${data.total || 0}`;

  const currentBatch = document.getElementById('CurrentBatch');
  if (currentBatch) currentBatch.textContent = 'Active Batch';
}

async function handleApprove() {
  if (!state.selectedRequirement?.uuid) return;

  await postForm(ENDPOINTS.approveRequirement, { req_uuid: state.selectedRequirement.uuid });
  toast('success', 'Document approved.');

  const modalEl = document.getElementById('requirementReviewModal');
  bootstrap.Modal.getOrCreateInstance(modalEl).hide();
  await loadOverview();
}

async function handleReturn() {
  if (!state.selectedRequirement?.uuid) return;

  const reason = (document.getElementById('reviewNote').value || '').trim();
  if (!reason) {
    toast('warning', 'Return reason is required.');
    return;
  }

  await postForm(ENDPOINTS.returnRequirement, {
    req_uuid: state.selectedRequirement.uuid,
    return_reason: reason,
  });

  toast('success', 'Document returned to student.');
  const modalEl = document.getElementById('requirementReviewModal');
  bootstrap.Modal.getOrCreateInstance(modalEl).hide();
  await loadOverview();
}

function bindEvents() {
  document.getElementById('approveBtn')?.addEventListener('click', () => {
    handleApprove().catch((error) => toast('error', error.message));
  });

  document.getElementById('returnBtn')?.addEventListener('click', () => {
    handleReturn().catch((error) => toast('error', error.message));
  });

  document.getElementById('closeModalBtn')?.addEventListener('click', () => {
    state.selectedRequirement = null;
    document.getElementById('reviewNote').value = '';
  });
}

(function init() {
  bindEvents();
  loadOverview()
    .catch((error) => toast('error', error.message || 'Failed to load overview.'))
    .finally(() => {
      const loader = document.getElementById('pageLoader');
      if (loader) loader.classList.add('d-none');
    });
})();
