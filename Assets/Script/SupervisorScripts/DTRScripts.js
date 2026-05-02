import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const decisionModalEl = document.getElementById('supervisorDecisionModal');
const decisionModal = decisionModalEl ? new bootstrap.Modal(decisionModalEl) : null;
const ENDPOINTS = {
  get: '../../../process/dtr/get_dtr',
  approve: '../../../process/dtr/approve_dtr',
};

const state = { entries: [], selected: new Set(), active: null };

function toast(icon, title) {
  if (window.Swal) ToastVersion(swalTheme, title, icon, 3000, 'top-end', '8');
}

function groupEntries(entries) {
  const groups = new Map();
  entries.forEach((entry) => {
    const key = entry.student_uuid || entry.student_number || entry.full_name;
    if (!groups.has(key)) {
      groups.set(key, { student_uuid: entry.student_uuid, full_name: entry.full_name, student_number: entry.student_number, program_code: entry.program_code, items: [] });
    }
    groups.get(key).items.push(entry);
  });
  return [...groups.values()];
}

function renderSummary() {
  const students = new Set(state.entries.map((e) => e.student_uuid || e.student_number));
  const backdated = state.entries.filter((e) => e.is_backdated).length;
  const hours = state.entries.reduce((sum, e) => sum + Number(e.hours_rendered || 0), 0);
  $('#supervisorPendingCount').text(state.entries.length);
  $('#supervisorStudentCount').text(students.size);
  $('#supervisorBackdatedCount').text(backdated);
  $('#supervisorPendingHours').text(hours.toFixed(2));
}

function renderEntries() {
  const list = $('#supervisorDtrList');
  const empty = $('#supervisorDtrEmptyState');
  const term = ($('#supervisorSearchInput').val() || '').toLowerCase();
  const status = $('#supervisorStatusFilter').val() || '';
  const month = $('#supervisorMonthFilter').val() || '';

  const filtered = state.entries.filter((entry) => {
    if (status && entry.status !== status) return false;
    if (month && !String(entry.entry_date || '').startsWith(month)) return false;
    const text = [entry.full_name, entry.student_number, entry.program_code, entry.activities, entry.entry_date_label].filter(Boolean).join(' ').toLowerCase();
    return text.includes(term);
  });

  list.empty();
  state.selected = new Set([...state.selected].filter((uuid) => filtered.some((entry) => entry.uuid === uuid)));

  if (!filtered.length) {
    empty.removeClass('d-none');
    $('#selectAllSupervisorEntries').prop('checked', false);
    return;
  }
  empty.addClass('d-none');

  filtered.forEach((entry) => {
    const checked = state.selected.has(entry.uuid) ? 'checked' : '';
    const activity = entry.activities || '<span class="text-muted">No activities recorded</span>';
    const accent = 'info';
    list.append(`
      <div class="card dtr-entry-card bg-blur-5 bg-semi-transparent shadow-sm" data-accent="${accent}">
        <div class="card-body">
          <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start gap-3 dtr-entry-header">
            <div class="d-flex gap-3 flex-grow-1 align-items-start">
              <div class="pt-1">
                <input class="form-check-input supervisor-entry-check" type="checkbox" data-uuid="${entry.uuid}" ${checked}>
              </div>
              <div class="dtr-entry-icon bg-info-subtle text-info-emphasis">
                <i class="bi bi-clipboard2-check fs-5"></i>
              </div>
              <div class="dtr-entry-title flex-grow-1">
                <div class="dtr-chip-row mb-2">
                  <span class="dtr-chip"><i class="bi bi-person"></i>${entry.full_name}</span>
                  <span class="dtr-chip"><i class="bi bi-mortarboard"></i>${entry.program_code || '—'}</span>
                  ${entry.is_backdated ? '<span class="badge rounded-pill bg-info-subtle text-info-emphasis">Backdated</span>' : ''}
                </div>
                <h5 class="mb-1 fw-semibold">${entry.full_name}</h5>
                <p class="mb-0 text-muted dtr-entry-subtitle">${entry.student_number || '—'} · ${entry.program_code || '—'}</p>
              </div>
            </div>
            <div class="text-lg-end">
              <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">Pending</span>
            </div>
          </div>

          <div class="dtr-entry-meta mt-3">
            <div class="meta-box" data-importance="high"><span class="meta-label">Date</span><span class="meta-value">${entry.entry_date_label}</span></div>
            <div class="meta-box"><span class="meta-label">Time</span><span class="meta-value">${entry.time_in_label} - ${entry.time_out_label}</span></div>
            <div class="meta-box"><span class="meta-label">Hours</span><span class="meta-value">${entry.hours_label}</span></div>
            <div class="meta-box"><span class="meta-label">Lunch break</span><span class="meta-value">${entry.lunch_break_minutes} min</span></div>
          </div>

          <div class="dtr-activity-preview mt-3">
            <span class="meta-label mb-2">Activity</span>
            <div class="activity-text">${activity}</div>
          </div>

          <div class="d-flex justify-content-end flex-wrap gap-2 dtr-entry-actions mt-3">
            <button class="btn btn-sm btn-outline-success rounded-pill px-3" data-action="review" data-uuid="${entry.uuid}">Review</button>
            <button class="btn btn-sm btn-outline-danger rounded-pill px-3" data-action="reject" data-uuid="${entry.uuid}">Reject</button>
          </div>
        </div>
      </div>
    `);
  });

  $('#selectAllSupervisorEntries').prop('checked', filtered.length > 0 && filtered.every((entry) => state.selected.has(entry.uuid)));
}

function loadDtr() {
  $.ajax({
    url: ENDPOINTS.get,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken },
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
  $('#decisionStudentName').text(`${entry.full_name} (${entry.student_number || '—'})`);
  $('#decisionEntryDate').text(entry.entry_date_label);
  $('#decisionTimeRange').text(`${entry.time_in_label} - ${entry.time_out_label}`);
  $('#decisionHours').text(entry.hours_label);
  $('#decisionStatus').text(entry.status_label || 'Pending');
  $('#decisionReason').val('');
  decisionModal?.show();
}

function approveEntry(entry) {
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      action: 'approve',
      dtr_uuid: entry.uuid,
    },
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
  const reason = $('#decisionReason').val().trim();
  if (!reason) {
    toast('error', 'Please provide a rejection reason.');
    return;
  }
  $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      action: 'reject',
      dtr_uuid: entry.uuid,
      reason,
    },
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
  const groupedByStudent = groupEntries(state.entries.filter((entry) => uuids.includes(entry.uuid)));
  const batchRequests = groupedByStudent.map((group) => $.ajax({
    url: ENDPOINTS.approve,
    method: 'POST',
    dataType: 'json',
    data: {
      csrf_token: csrfToken,
      action: 'bulk_approve',
      student_uuid: group.student_uuid,
      dtr_uuids: JSON.stringify(group.items.map((item) => item.uuid)),
    },
  }));

  Promise.all(batchRequests)
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
  $('#clearSupervisorFiltersBtn').on('click', () => {
    $('#supervisorSearchInput').val('');
    $('#supervisorStatusFilter').val('pending');
    $('#supervisorMonthFilter').val('');
    renderEntries();
  });

  $('#supervisorSearchInput, #supervisorStatusFilter, #supervisorMonthFilter').on('input change', renderEntries);

  $('#selectAllSupervisorEntries').on('change', function () {
    const checked = $(this).is(':checked');
    const visible = $('#supervisorDtrList .supervisor-entry-check').toArray().map((el) => $(el).data('uuid'));
    visible.forEach((uuid) => {
      if (checked) state.selected.add(uuid); else state.selected.delete(uuid);
    });
    renderEntries();
  });

  $('#supervisorDtrList').on('change', '.supervisor-entry-check', function () {
    const uuid = $(this).data('uuid');
    if ($(this).is(':checked')) state.selected.add(uuid); else state.selected.delete(uuid);
    renderEntries();
  });

  $('#supervisorDtrList').on('click', 'button[data-action="review"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (entry) openDecision(entry);
  });

  $('#supervisorDtrList').on('click', 'button[data-action="reject"]', function () {
    const entry = state.entries.find((item) => item.uuid === $(this).data('uuid'));
    if (!entry) return;
    openDecision(entry);
    $('#decisionReason').focus();
  });

  $('#decisionApproveBtn').on('click', () => {
    if (!state.active) return;
    approveEntry(state.active);
  });

  $('#decisionRejectBtn').on('click', () => {
    if (!state.active) return;
    rejectEntry(state.active);
  });
});
