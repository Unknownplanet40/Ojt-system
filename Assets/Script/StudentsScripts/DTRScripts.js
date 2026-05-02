import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const modalEl = document.getElementById('dtrEntryModal');
const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
const ENDPOINTS = {
  get: '../../../process/dtr/get_dtr',
  submit: '../../../process/dtr/submit_dtr',
  edit: '../../../process/dtr/edit_dtr',
  delete: '../../../process/dtr/delete_dtr',
};

const state = {
  entries: [],
  summary: null,
  filters: {
    status: '',
    month: '',
    search: '',
  },
  mode: 'create',
  activeUuid: '',
};

function toast(icon, title) {
  if (window.Swal) {
    ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
  }
}

function setFieldError(id, message = '') {
  const el = document.getElementById(id);
  if (el) el.textContent = message || '';
}

function clearFieldErrors() {
  ['entryDateError', 'timeInError', 'timeOutError', 'lunchBreakMinutesError', 'activitiesError', 'backdateReasonError'].forEach((id) => setFieldError(id, ''));
}

function pad2(n) { return String(n).padStart(2, '0'); }
function getTodayYYYYMMDD() {
  const d = new Date();
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}
function getCurrentTimeHHMM() {
  const d = new Date();
  return `${pad2(d.getHours())}:${pad2(d.getMinutes())}`;
}

function formValues() {
  return {
    dtr_uuid: document.getElementById('dtrEntryUuid')?.value || '',
    entry_date: document.getElementById('entryDate')?.value || '',
    time_in: document.getElementById('timeIn')?.value || '',
    time_out: document.getElementById('timeOut')?.value || '',
    lunch_break_minutes: document.getElementById('lunchBreakMinutes')?.value || '60',
    activities: document.getElementById('activities')?.value ?? '',
    activities_performed: document.getElementById('activities')?.value ?? '',
    backdate_reason: document.getElementById('backdateReason')?.value || '',
  };
}

function setModalMode(mode, entry = null) {
  state.mode = mode;
  state.activeUuid = entry?.uuid || '';
  document.getElementById('dtrEntryModalTitle').textContent = mode === 'edit' ? 'Edit DTR entry' : 'Log DTR entry';
  document.getElementById('saveDtrEntryBtn').textContent = mode === 'edit' ? 'Update entry' : 'Save entry';
  document.getElementById('dtrEntryUuid').value = entry?.uuid || '';
  // If creating a new entry, default date/time to current values for convenience
  if (mode === 'create' && !entry) {
    document.getElementById('entryDate').value = getTodayYYYYMMDD();
    document.getElementById('timeIn').value = getCurrentTimeHHMM();
    document.getElementById('timeOut').value = '';
  } else {
    document.getElementById('entryDate').value = entry?.entry_date || '';
    document.getElementById('timeIn').value = entry?.time_in || '';
    document.getElementById('timeOut').value = entry?.time_out || '';
  }
  document.getElementById('lunchBreakMinutes').value = entry?.lunch_break_minutes ?? '60';
  document.getElementById('activities').value = entry?.activities ?? '';
  document.getElementById('backdateReason').value = entry?.backdate_reason || '';
  clearFieldErrors();
}

function statusBadge(entry) {
  const cls = entry.status === 'approved'
    ? 'bg-success-subtle text-success-emphasis'
    : entry.status === 'rejected'
      ? 'bg-danger-subtle text-danger-emphasis'
      : 'bg-warning-subtle text-warning-emphasis';
  return `<span class="badge rounded-pill ${cls}">${entry.status_label || entry.status}</span>`;
}

function matchesSearch(entry, term) {
  if (!term) return true;
  const haystack = [entry.entry_date_label, entry.time_in_label, entry.time_out_label, entry.activities ?? '', entry.status_label, entry.backdate_reason]
    .filter(Boolean)
    .join(' ')
    .toLowerCase();
  return haystack.includes(term.toLowerCase());
}

function renderSummary(summary) {
  if (!summary) return;
  $('#completionPercent').text(`${summary.percentage ?? 0}%`);
  $('#completionProgressBar').css('width', `${summary.percentage ?? 0}%`);
  $('#approvedHoursLabel').text(Number(summary.total_approved || 0).toFixed(2));
  $('#remainingHoursLabel').text(Number(summary.remaining_hours || 0).toFixed(2));
  $('#pendingCountLabel').text(summary.pending_count ?? 0);
  $('#backdatedCountLabel').text(summary.backdated_pending_count ?? 0);
}

function renderEntries() {
  const list = $('#studentDtrList');
  const empty = $('#studentDtrEmptyState');
  const term = $('#dtrSearchInput').val() || '';
  const status = $('#dtrStatusFilter').val() || '';
  const month = $('#dtrMonthFilter').val() || '';

  const filtered = state.entries.filter((entry) => {
    if (status && entry.status !== status) return false;
    if (month && !String(entry.entry_date || '').startsWith(month)) return false;
    if (!matchesSearch(entry, term)) return false;
    return true;
  });

  list.empty();
  if (!filtered.length) {
    empty.removeClass('d-none');
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const accent = entry.status === 'approved' ? 'success' : entry.status === 'rejected' ? 'danger' : entry.is_backdated ? 'warning' : 'info';
    const statusIcon = entry.status === 'approved' ? 'bi-check2-circle' : entry.status === 'rejected' ? 'bi-x-circle' : entry.is_backdated ? 'bi-clock-history' : 'bi-journal-text';
    const isBackdated = entry.is_backdated ? '<span class="badge rounded-pill bg-info-subtle text-info-emphasis">Backdated</span>' : '';
    const activity = entry.activities ?? '' ? entry.activities : '<span class="text-muted">No activities recorded</span>';

    list.append(`
      <div class="card dtr-entry-card bg-blur-5 bg-semi-transparent shadow-sm" data-accent="${accent}">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 dtr-entry-header">
            <div class="d-flex gap-3 align-items-start flex-grow-1">
              <div class="dtr-entry-icon bg-${accent}-subtle text-${accent}-emphasis">
                <i class="bi ${statusIcon} fs-5"></i>
              </div>
              <div class="dtr-entry-title flex-grow-1">
                <div class="dtr-chip-row mb-2">
                  <span class="dtr-chip"><i class="bi bi-calendar3"></i>${entry.entry_date_label}</span>
                  <span class="dtr-chip"><i class="bi bi-clock"></i>${entry.time_ago || 'Recently submitted'}</span>
                  ${entry.is_backdated ? '<span class="dtr-chip text-info-emphasis"><i class="bi bi-exclamation-triangle"></i>Backdated</span>' : ''}
                </div>
                <h5 class="mb-1 fw-semibold">${entry.status_label || entry.status}</h5>
                <p class="mb-0 text-muted dtr-entry-subtitle">${entry.time_in_label} - ${entry.time_out_label} · ${entry.hours_label}</p>
              </div>
            </div>
            <div class="text-lg-end">
              ${statusBadge(entry)}
            </div>
          </div>

          <div class="dtr-entry-meta mt-3">
            <div class="meta-box" data-importance="high"><span class="meta-label">Time in / out</span><span class="meta-value">${entry.time_in_label} - ${entry.time_out_label}</span></div>
            <div class="meta-box"><span class="meta-label">Hours rendered</span><span class="meta-value">${entry.hours_label}</span></div>
            <div class="meta-box"><span class="meta-label">Lunch break</span><span class="meta-value">${entry.lunch_break_minutes} min</span></div>
            <div class="meta-box"><span class="meta-label">Status</span><span class="meta-value">${entry.status_label || entry.status}</span></div>
          </div>

          <div class="dtr-activity-preview mt-3">
            <span class="meta-label mb-2">Activities performed</span>
            <div class="activity-text">${activity}</div>
            ${entry.backdate_reason ? `<small class="text-muted d-block mt-2">Reason: ${entry.backdate_reason}</small>` : ''}
          </div>

          <div class="d-flex justify-content-end flex-wrap gap-2 dtr-entry-actions mt-3">
            ${entry.can_edit ? `<button class="btn btn-sm btn-outline-success rounded-pill px-3" data-action="edit" data-uuid="${entry.uuid}">Edit</button>` : ''}
            ${entry.can_delete ? `<button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-action="delete" data-uuid="${entry.uuid}">Delete</button>` : ''}
          </div>
        </div>
      </div>
    `);
  });
}

function loadDtr() {
  $('#pageLoader').removeClass('d-none');
  $.ajax({
    url: ENDPOINTS.get,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      status: $('#dtrStatusFilter').val() || '',
      month: $('#dtrMonthFilter').val() || '',
    },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Failed to load DTR entries.');
        return;
      }
      state.entries = response.entries || [];
      state.summary = response.summary || null;
      renderSummary(state.summary);
      renderEntries();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => {
      $('#pageLoader').fadeOut(200);
    },
  });
}

function submitForm() {
  clearFieldErrors();
  const payload = formValues();
  const endpoint = state.mode === 'edit' ? ENDPOINTS.edit : ENDPOINTS.submit;

  $.ajax({
    url: endpoint,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, ...payload },
    success: (response) => {
      if (response.status !== 'success') {
        const errors = response.errors || {};
        if (errors.entry_date) setFieldError('entryDateError', errors.entry_date);
        if (errors.time_in) setFieldError('timeInError', errors.time_in);
        if (errors.time_out) setFieldError('timeOutError', errors.time_out);
        if (errors.lunch_break_minutes) setFieldError('lunchBreakMinutesError', errors.lunch_break_minutes);
        if (errors.activities) setFieldError('activitiesError', errors.activities);
        if (errors.backdate_reason) setFieldError('backdateReasonError', errors.backdate_reason);
        toast('error', response.message || 'Please review the form.');
        return;
      }
      toast('success', response.message || 'DTR entry saved.');
      modal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function deleteEntry(uuid) {
  Swal.fire({
    title: 'Delete entry? ',
    text: 'This will permanently remove the pending DTR entry.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Delete',
    cancelButtonText: 'Cancel',
  }).then((result) => {
    if (!result.isConfirmed) return;
    $.ajax({
      url: ENDPOINTS.delete,
      method: 'POST',
      dataType: 'json',
      data: { csrf_token: csrfToken, dtr_uuid: uuid },
      success: (response) => {
        if (response.status !== 'success') {
          toast('error', response.message || 'Unable to delete entry.');
          return;
        }
        toast('success', response.message || 'Entry deleted.');
        loadDtr();
      },
      error: (xhr, status, error) => Errors(xhr, status, error),
    });
  });
}

$(document).ready(() => {
  $('#pageLoader').fadeOut(300);
  loadDtr();

  $('#dashboardRefreshBtn').on('click', loadDtr);
  $('#newDtrEntryBtn, #emptyStateNewEntryBtn').on('click', () => {
    setModalMode('create');
    modal?.show();
  });

  $('#saveDtrEntryBtn').on('click', submitForm);
  $('#clearDtrFiltersBtn').on('click', () => {
    $('#dtrStatusFilter').val('');
    $('#dtrMonthFilter').val('');
    $('#dtrSearchInput').val('');
    renderEntries();
  });

  $('#dtrStatusFilter, #dtrMonthFilter, #dtrSearchInput').on('input change', renderEntries);

  $('#studentDtrList').on('click', 'button[data-action="edit"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (!entry) return;
    setModalMode('edit', entry);
    modal?.show();
  });

  $('#studentDtrList').on('click', 'button[data-action="delete"]', function () {
    deleteEntry($(this).data('uuid'));
  });
});
