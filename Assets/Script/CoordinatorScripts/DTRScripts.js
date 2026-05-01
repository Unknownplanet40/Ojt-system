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
  const tbody = $('#coordinatorDtrTableBody');
  const empty = $('#coordinatorDtrEmptyState');
  const filtered = filteredEntries();

  tbody.empty();
  state.selected = new Set([...state.selected].filter((uuid) => filtered.some((entry) => entry.uuid === uuid)));

  if (!filtered.length) {
    empty.removeClass('d-none');
    $('#selectAllCoordinatorEntries').prop('checked', false);
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const checked = state.selected.has(entry.uuid) ? 'checked' : '';
    tbody.append(`
      <tr>
        <td class="ps-4"><input class="form-check-input coordinator-entry-check" type="checkbox" data-uuid="${entry.uuid}" ${checked}></td>
        <td>
          <div class="fw-semibold">${entry.full_name}</div>
          <small class="text-muted">${entry.student_number || '—'} · ${entry.program_code || '—'}</small>
        </td>
        <td>
          <div class="fw-semibold">${entry.entry_date_label}</div>
          ${entry.is_backdated ? '<span class="badge rounded-pill bg-info-subtle text-info-emphasis mt-1">Backdated</span>' : ''}
        </td>
        <td>
          <div class="small fw-semibold">${entry.time_in_label} - ${entry.time_out_label}</div>
          <small class="text-muted">Submitted ${entry.time_ago || ''}</small>
        </td>
        <td><span class="fw-semibold">${entry.hours_label}</span></td>
        <td><div class="text-truncate" style="max-width: 320px;">${entry.activities || '<span class="text-muted">No activities recorded</span>'}</div></td>
        <td><span class="badge rounded-pill ${entry.status === 'approved' ? 'bg-success-subtle text-success-emphasis' : entry.status === 'rejected' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-warning-subtle text-warning-emphasis'}">${entry.status_label || entry.status}</span></td>
        <td class="text-end pe-4">
          <div class="btn-group btn-group-sm">
            ${entry.status === 'pending' ? `<button class="btn btn-outline-success" data-action="review" data-uuid="${entry.uuid}">Review</button><button class="btn btn-outline-danger" data-action="reject" data-uuid="${entry.uuid}">Reject</button>` : `<button class="btn btn-outline-secondary" data-action="view" data-uuid="${entry.uuid}">View</button>`}
          </div>
        </td>
      </tr>
    `);
  });

  $('#selectAllCoordinatorEntries').prop('checked', filtered.length > 0 && filtered.every((entry) => state.selected.has(entry.uuid)));
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
    filteredEntries().forEach((entry) => {
      if (checked) state.selected.add(entry.uuid); else state.selected.delete(entry.uuid);
    });
    renderEntries();
  });

  $('#coordinatorDtrTableBody').on('change', '.coordinator-entry-check', function () {
    const uuid = $(this).data('uuid');
    if ($(this).is(':checked')) state.selected.add(uuid); else state.selected.delete(uuid);
    renderEntries();
  });

  $('#coordinatorDtrTableBody').on('click', 'button[data-action="review"], button[data-action="view"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
  });

  $('#coordinatorDtrTableBody').on('click', 'button[data-action="reject"]', function () {
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
