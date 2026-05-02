import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const decisionModalEl = document.getElementById('coordinatorDecisionModal');
const decisionModal = decisionModalEl ? new bootstrap.Modal(decisionModalEl) : null;
const ENDPOINTS = {
  get: '../../../process/dtr/get_dtr',
  approve: '../../../process/dtr/approve_dtr',
};

const state = { entries: [], selected: new Set(), active: null };

function toast(icon, title) {
  if (window.Swal) ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
}

function renderSummary() {
  $('#coordinatorEntriesCount').text(state.entries.length);
  $('#coordinatorStudentsCount').text(new Set(state.entries.map((e) => e.student_uuid || e.student_number)).size);
  $('#coordinatorPendingCount').text(state.entries.filter((e) => e.status === 'pending').length);
  $('#coordinatorBackdatedCount').text(state.entries.filter((e) => e.is_backdated).length);
}

function filteredEntries() {
  const term = ($('#coordinatorSearchInput').val() || '').toLowerCase();
  const status = $('#coordinatorStatusFilter').val() || '';
  const month = $('#coordinatorMonthFilter').val() || '';
  const backdated = $('#coordinatorBackdatedFilter').val();

  return state.entries.filter((entry) => {
    if (status && entry.status !== status) return false;
    if (month && !String(entry.entry_date || '').startsWith(month)) return false;
    if (backdated !== '' && Number(entry.is_backdated ? 1 : 0) !== Number(backdated)) return false;
    const text = [entry.full_name, entry.student_number, entry.program_code, entry.activities, entry.entry_date_label].filter(Boolean).join(' ').toLowerCase();
    return text.includes(term);
  });
}

function renderEntries() {
  const list = $('#coordinatorDtrList');
  const empty = $('#coordinatorDtrEmptyState');
  const filtered = filteredEntries();

  list.empty();
  state.selected = new Set([...state.selected].filter((uuid) => filtered.some((entry) => entry.uuid === uuid)));

  if (!filtered.length) {
    empty.removeClass('d-none');
    $('#selectAllCoordinatorEntries').prop('checked', false);
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const checked = state.selected.has(entry.uuid) ? 'checked' : '';
    const activity = entry.activities || '<span class="text-muted">No activities recorded</span>';
    const isPending = entry.status === 'pending';
    const accent = entry.status === 'approved' ? 'success' : entry.status === 'rejected' ? 'danger' : entry.is_backdated ? 'warning' : 'info';
    const statusIcon = entry.status === 'approved' ? 'bi-check-circle' : entry.status === 'rejected' ? 'bi-x-circle' : entry.is_backdated ? 'bi-clock-history' : 'bi-journal-text';

    list.append(`
      <div class="card dtr-entry-card bg-blur-5 bg-semi-transparent shadow-sm" data-accent="${accent}">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 dtr-entry-header">
            <div class="d-flex gap-3 flex-grow-1 align-items-start">
              <div class="pt-1">
                ${isPending ? `<input class="form-check-input coordinator-entry-check" type="checkbox" data-uuid="${entry.uuid}" ${checked}>` : `<span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary-emphasis" style="width: 1.2rem; height: 1.2rem;"><i class="bi bi-dot"></i></span>`}
              </div>
              <div class="dtr-entry-icon bg-${accent}-subtle text-${accent}-emphasis">
                <i class="bi ${statusIcon} fs-5"></i>
              </div>
              <div class="dtr-entry-title flex-grow-1">
                <div class="dtr-chip-row mb-2">
                  <span class="dtr-chip"><i class="bi bi-person"></i>${entry.full_name}</span>
                  <span class="dtr-chip"><i class="bi bi-activity"></i>${entry.status_label || entry.status}</span>
                  ${entry.is_backdated ? '<span class="dtr-chip text-info-emphasis"><i class="bi bi-exclamation-triangle"></i>Backdated</span>' : ''}
                </div>
                <h5 class="mb-1 fw-semibold">${entry.full_name}</h5>
                <p class="mb-0 text-muted dtr-entry-subtitle">${entry.student_number || '—'} · ${entry.program_code || '—'}</p>
              </div>
            </div>
            <div class="text-lg-end">
              <div class="fw-semibold">${entry.entry_date_label}</div>
              <small class="text-muted">Submitted ${entry.time_ago || ''}</small>
            </div>
          </div>

          <div class="dtr-entry-meta mt-3">
            <div class="meta-box" data-importance="high"><span class="meta-label">Time</span><span class="meta-value">${entry.time_in_label} - ${entry.time_out_label}</span></div>
            <div class="meta-box"><span class="meta-label">Hours</span><span class="meta-value">${entry.hours_label}</span></div>
            <div class="meta-box"><span class="meta-label">Activities</span><span class="meta-value">${entry.activities ? 'Recorded' : 'No activity'}</span></div>
            <div class="meta-box"><span class="meta-label">Backdated</span><span class="meta-value">${entry.is_backdated ? 'Yes' : 'No'}</span></div>
          </div>

          <div class="dtr-activity-preview mt-3">
            <span class="meta-label mb-2">Activity details</span>
            <div class="activity-text">${activity}</div>
          </div>

          <div class="d-flex justify-content-end flex-wrap gap-2 dtr-entry-actions mt-3">
            ${isPending ? `<button class="btn btn-sm btn-outline-success rounded-pill px-3" data-action="review" data-uuid="${entry.uuid}">Review</button><button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-action="reject" data-uuid="${entry.uuid}">Reject</button>` : `<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" data-action="view" data-uuid="${entry.uuid}">View</button>`}
          </div>
        </div>
      </div>
    `);
  });

  $('#selectAllCoordinatorEntries').prop('checked', filtered.filter((entry) => entry.status === 'pending').length > 0 && filtered.filter((entry) => entry.status === 'pending').every((entry) => state.selected.has(entry.uuid)));
}

function loadDtr() {
  $.ajax({
    url: ENDPOINTS.get,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      status: $('#coordinatorStatusFilter').val() || '',
      month: $('#coordinatorMonthFilter').val() || '',
      is_backdated: $('#coordinatorBackdatedFilter').val(),
    },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to load DTR entries.');
        return;
      }
      state.entries = response.entries || [];
      state.selected = new Set();
      renderSummary();
      renderEntries();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
    complete: () => $('#pageLoader').fadeOut(200),
  });
}

function openDecision(entry) {
  state.active = entry;
  $('#coordDecisionStudentName').text(entry.full_name);
  $('#coordDecisionStudentLabel').text(entry.full_name);
  $('#coordDecisionStudentNumber').text(entry.student_number || '—');
  $('#coordDecisionProgram').text(entry.program_code || '—');
  $('#coordDecisionEntryDate').text(entry.entry_date_label);
  $('#coordDecisionTimeRange').text(`${entry.time_in_label} - ${entry.time_out_label}`);
  $('#coordDecisionHours').text(entry.hours_label);
  $('#coordDecisionStatus').text(entry.status_label || 'Pending');
  $('#coordDecisionBackdated').text(entry.is_backdated ? 'Yes' : 'No');
  $('#coordDecisionActivities').text(entry.activities || '—');
  $('#coordDecisionSubmittedAt').text(entry.submitted_at || '—');
  $('#coordDecisionReason').val(entry.backdate_reason || '');
  decisionModal?.show();
}

function approveEntry(entry) {
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, action: 'approve', dtr_uuid: entry.uuid },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to approve entry.');
        return;
      }
      toast('success', response.message || 'Entry approved.');
      decisionModal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function rejectEntry(entry) {
  const reason = $('#coordDecisionReason').val().trim();
  if (!reason) {
    toast('error', 'Please provide a reason.');
    return;
  }
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, action: 'reject', dtr_uuid: entry.uuid, reason },
    success: (response) => {
      if (response.status !== 'success') {
        toast('error', response.message || 'Unable to reject entry.');
        return;
      }
      toast('success', response.message || 'Entry rejected.');
      decisionModal?.hide();
      loadDtr();
    },
    error: (xhr, status, error) => Errors(xhr, status, error),
  });
}

function approveSelected() {
  const uuids = [...state.selected];
  if (!uuids.length) {
    toast('error', 'Select at least one entry first.');
    return;
  }

  const grouped = new Map();
  state.entries.filter((entry) => uuids.includes(entry.uuid)).forEach((entry) => {
    if (!grouped.has(entry.student_uuid)) grouped.set(entry.student_uuid, []);
    grouped.get(entry.student_uuid).push(entry.uuid);
  });

  const requests = [...grouped.entries()].map(([studentUuid, dtrUuids]) => $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      action: 'bulk_approve',
      student_uuid: studentUuid,
      dtr_uuids: JSON.stringify(dtrUuids),
    },
  }));

  Promise.all(requests)
    .then(() => {
      toast('success', 'Selected entries approved.');
      loadDtr();
    })
    .catch((error) => {
      const message = error?.responseJSON?.message || 'Unable to approve selected entries.';
      toast('error', message);
    });
}

$(document).ready(() => {
  loadDtr();

  $('#dashboardRefreshBtn').on('click', loadDtr);
  $('#approveSelectedBtn').on('click', approveSelected);
  $('#clearCoordinatorFiltersBtn').on('click', () => {
    $('#coordinatorSearchInput').val('');
    $('#coordinatorStatusFilter').val('');
    $('#coordinatorMonthFilter').val('');
    $('#coordinatorBackdatedFilter').val('');
    renderEntries();
  });

  $('#coordinatorSearchInput, #coordinatorStatusFilter, #coordinatorMonthFilter, #coordinatorBackdatedFilter').on('input change', renderEntries);

  $('#selectAllCoordinatorEntries').on('change', function () {
    const checked = $(this).is(':checked');
    $('#coordinatorDtrList .coordinator-entry-check').toArray().forEach((el) => {
      const uuid = $(el).data('uuid');
      if (checked) state.selected.add(uuid); else state.selected.delete(uuid);
    });
    renderEntries();
  });

  $('#coordinatorDtrList').on('change', '.coordinator-entry-check', function () {
    const uuid = $(this).data('uuid');
    if ($(this).is(':checked')) state.selected.add(uuid); else state.selected.delete(uuid);
    renderEntries();
  });

  $('#coordinatorDtrList').on('click', 'button[data-action="review"], button[data-action="view"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
  });

  $('#coordinatorDtrList').on('click', 'button[data-action="reject"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
    $('#coordDecisionReason').focus();
  });

  $('#coordDecisionApproveBtn').on('click', () => {
    if (state.active) approveEntry(state.active);
  });

  $('#coordDecisionRejectBtn').on('click', () => {
    if (state.active) rejectEntry(state.active);
  });
});
