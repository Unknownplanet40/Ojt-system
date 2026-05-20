import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
const swalTheme = SwalTheme();

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const role = document.body?.dataset?.role || '';
const pageType = document.body?.dataset?.pageType || '';

const ENDPOINTS = {
  overview: '../../../process/grades/get_grading_overview',
  compute: '../../../process/grades/compute_grade',
  save: '../../../process/grades/save_grade',
  finalize: '../../../process/grades/finalize_grade',
  getGrade: '../../../process/grades/get_grade',
  allGrades: '../../../process/grades/get_all_grades',
};

const DEFAULT_WEIGHTS = {
  hours: 20,
  midterm: 20,
  final: 40,
  journal: 10,
  self: 10,
};

const state = {
  overview: [],
  grades: [],
  selectedStudent: null,
  selectedGrade: null,
  readiness: null,
};

function toast(icon, title) {
  if (window.Swal) {
    ToastVersion(swalTheme, title, icon, 2800, 'top-end', '8');
  }
}

function apiRequest(url, data) {
  return $.ajax({
    url,
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken, ...data },
  });
}

function badgeForStatus(status) {
  switch (status) {
    case 'finalized': return 'bg-success-subtle text-success-emphasis';
    case 'computed': return 'bg-primary-subtle text-primary-emphasis';
    case 'ready': return 'bg-warning-subtle text-warning-emphasis';
    default: return 'bg-secondary-subtle text-secondary-emphasis';
  }
}

function statusLabel(status) {
  switch (status) {
    case 'finalized': return 'Finalized';
    case 'computed': return 'Computed';
    case 'ready': return 'Ready';
    default: return 'Incomplete';
  }
}

function fmt(n) {
  return Number(n || 0).toFixed(2);
}

function readinessList(readiness) {
  if (!readiness) return '<div class="text-muted small">No readiness information available.</div>';

  const issues = readiness.issues || [];
  if (!issues.length) {
    return `
      <div class="alert alert-success border-0 rounded-4 mb-0">
        <div class="d-flex gap-2 align-items-center">
          <i class="bi bi-check-circle fs-5"></i>
          <div>
            <h6 class="fw-bold mb-0">Ready for grading</h6>
            <small>All required components are complete and approved.</small>
          </div>
        </div>
      </div>
    `;
  }

  return `
    <div class="alert alert-warning border-0 rounded-4 mb-0">
      <div class="d-flex gap-2 align-items-start">
        <i class="bi bi-exclamation-triangle fs-5 flex-shrink-0 mt-1"></i>
        <div>
          <h6 class="fw-bold mb-2">Incomplete items</h6>
          <ul class="mb-0 ps-3 small">
            ${issues.map((issue) => `<li class="mb-1">${issue}</li>`).join('')}
          </ul>
        </div>
      </div>
    </div>
  `;
}

function componentCard(title, value, meta, color = 'primary') {
  return `
    <div class="col-12 col-md-6 col-lg-4">
      <div class="p-4 rounded-4 border bg-body-tertiary h-100">
        <div class="text-muted small text-uppercase fw-semibold mb-2">${title}</div>
        <div class="d-flex align-items-end justify-content-between gap-3">
          <div>
            <div class="fw-bold display-6 text-${color}">${value}</div>
            <div class="small text-muted">${meta}</div>
          </div>
        </div>
      </div>
    </div>
  `;
}

function buildWeightInputs(weights) {
  const w = { ...DEFAULT_WEIGHTS, ...(weights || {}) };
  const colorClasses = {
    hours: 'info',
    midterm: 'primary',
    final: 'success',
    journal: 'warning',
    self: 'secondary',
  };
  
  return `
    <div class="row g-3">
      ${[['hours','Hours', 'DTR & hours completed'],['midterm','Midterm', 'Mid-term evaluation'],['final','Final', 'Final evaluation'],['journal','Journal', 'Approved entries'],['self','Self', 'Self evaluation']].map(([key, label, desc]) => `
        <div class="col-12 col-sm-6 col-lg-4">
          <label class="form-label small fw-semibold text-uppercase text-muted mb-2">${label}</label>
          <div class="input-group rounded-3 overflow-hidden">
            <input type="number" min="0" max="100" step="0.1" class="form-control grade-weight-input border-0" data-weight="${key}" value="${w[key]}" aria-label="${label} weight">
            <span class="input-group-text bg-${colorClasses[key]}-subtle text-${colorClasses[key]} border-0 fw-semibold">%</span>
          </div>
          <small class="text-muted d-block mt-1">${desc}</small>
        </div>
      `).join('')}
    </div>
  `;
}

function currentWeights() {
  const weights = {};
  $('.grade-weight-input').each(function () {
    weights[$(this).data('weight')] = parseFloat($(this).val()) || 0;
  });
  return weights;
}

function showWeightTotal(weights) {
  const total = Object.values(weights).reduce((sum, n) => sum + Number(n || 0), 0);
  const label = $('#weightTotalLabel');
  const badge = $('#weightTotalBadge');
  
  label.text(`${total.toFixed(2)}%`);
  
  if (badge.length) {
    const isValid = Math.abs(total - 100) <= 0.01;
    badge
      .removeClass('bg-danger text-danger')
      .removeClass('bg-warning text-warning')
      .toggleClass('bg-success-subtle text-success-emphasis', isValid)
      .toggleClass('bg-warning-subtle text-warning-emphasis', !isValid && total !== 0)
      .toggleClass('bg-danger-subtle text-danger-emphasis', total === 0);
  }
}

$(document).ready(() => {
  
  if (role === 'student') {
    apiRequest(ENDPOINTS.getGrade, { student_uuid: '' })
      .done((res) => {
        if (res.status === 'success') renderStudentGrade(res.grade);
      })
      .fail((xhr, status, error) => Errors(xhr, status, error));
  } else if (role === 'admin') {
    apiRequest(ENDPOINTS.allGrades, {})
      .done((res) => {
        if (res.status === 'success') {
          state.grades = res.grades || [];
          $('#gradesCountLabel').text(state.grades.length);
          renderAdminGrades();
        } else {
          $('#finalizedGradesList').html(`<div class="alert alert-danger border-0 rounded-4"><i class="bi bi-exclamation-circle me-2"></i>${res.message || 'Failed to load grades.'}</div>`);
        }
      })
      .fail((xhr, status, error) => Errors(xhr, status, error));
  } else if (role === 'coordinator') {
    apiRequest(ENDPOINTS.overview, {})
      .done((res) => {
        if (res.status === 'success') {
          state.overview = res.overview || [];
          const summary = res.summary || {};
          $('#gradeSummaryTotal').text(summary.total || 0);
          $('#gradeSummaryReady').text(summary.ready || 0);
          $('#gradeSummaryComputed').text(summary.computed || 0);
          $('#gradeSummaryFinalized').text(summary.finalized || 0);
          $('#gradeSummaryIncomplete').text(summary.incomplete || 0);
          renderCoordinatorOverview();
        } else {
          $('#gradingOverviewList').html(`<div class="alert alert-danger border-0 rounded-4"><i class="bi bi-exclamation-circle me-2"></i>${res.message || 'Failed to load grading overview.'}</div>`);
        }
      })
      .fail((xhr, status, error) => Errors(xhr, status, error));
  }

  
  $(document).on('click', '.js-open-workbench', function () {
    const studentUuid = $(this).data('student-uuid');
    apiRequest(ENDPOINTS.getGrade, { student_uuid: studentUuid })
      .done((res) => {
        if (res.status !== 'success') {
          toast('error', res.message || 'Unable to load grade data.');
          return;
        }
        state.selectedStudent = res.grade ? { ...res.grade, student_uuid: studentUuid } : state.overview.find((s) => s.student_uuid === studentUuid) || { student_uuid: studentUuid };
        state.selectedGrade = res.grade || null;
        state.readiness = res.readiness || null;

        const student = state.overview.find((s) => s.student_uuid === studentUuid) || state.selectedStudent;
        $('#gradeModalStudentName').text(student.full_name || 'Student');
        $('#gradeModalStudentNumber').text(student.student_number || '—');
        $('#gradeModalProgram').text(student.program_code || '—');
        $('#gradeModalStatusBadge').html(`<span class="badge rounded-pill ${badgeForStatus(student.grade_status || (res.grade ? 'finalized' : 'ready'))}">${statusLabel(student.grade_status || (res.grade ? 'finalized' : 'ready'))}</span>`);
        $('#gradeModalReadiness').html(readinessList(state.readiness));

        const weights = state.selectedGrade ? {
          hours: state.selectedGrade.hours_weight,
          midterm: state.selectedGrade.midterm_weight,
          final: state.selectedGrade.final_weight,
          journal: state.selectedGrade.journal_weight,
          self: state.selectedGrade.self_weight,
        } : DEFAULT_WEIGHTS;

        $('#gradeWeightsContainer').html(buildWeightInputs(weights));
        showWeightTotal(weights);
        $('#gradeCoordinatorNotes').val(state.selectedGrade?.coordinator_notes || '');

        renderPreviewFromGradeOrCompute();
        $('#gradeWorkbenchModal').modal('show');
      })
      .fail((xhr, status, error) => Errors(xhr, status, error));
  });

  $(document).on('click', '.js-view-grade', function () {
    const gradeUuid = $(this).data('grade-uuid');
    const grade = state.grades.find((g) => g.uuid === gradeUuid);
    if (!grade) return;
    $('#gradeDetailsStudentName').text(grade.full_name);
    $('#gradeDetailsStudentNumber').text(grade.student_number);
    $('#gradeDetailsStatus').html(`<span class="badge rounded-pill bg-success-subtle text-success-emphasis">Finalized</span>`);
    $('#gradeDetailsContent').html(`
      <div class="row g-3">
        ${componentCard('Weighted score', `${grade.weighted_score_label}`, 'Locked final score', 'success')}
        ${componentCard('Equivalent', grade.grade_equivalent, grade.remarks, 'primary')}
        ${componentCard('Finalized by', grade.finalized_by_name || '—', grade.finalized_at || '—', 'secondary')}
        ${componentCard('Hours weight', `${fmt(grade.hours_weight)}%`, `${fmt(grade.hours_contribution)}% contribution`, 'info')}
        ${componentCard('Midterm weight', `${fmt(grade.midterm_weight)}%`, `${fmt(grade.midterm_contribution)}% contribution`, 'warning')}
        ${componentCard('Final weight', `${fmt(grade.final_weight)}%`, `${fmt(grade.final_contribution)}% contribution`, 'success')}
        ${componentCard('Journal weight', `${fmt(grade.journal_weight)}%`, `${fmt(grade.journal_contribution)}% contribution`, 'primary')}
        ${componentCard('Self weight', `${fmt(grade.self_weight)}%`, `${fmt(grade.self_contribution)}% contribution`, 'secondary')}
      </div>
    `);
    $('#gradeDetailsModal').modal('show');
  });

  $(document).on('input change', '.grade-weight-input', function () {
    const weights = currentWeights();
    showWeightTotal(weights);
    clearTimeout(window.__gradePreviewTimer);
    window.__gradePreviewTimer = setTimeout(renderPreviewFromGradeOrCompute, 250);
  });

  $(document).on('click', '#saveGradeBtn', function () {
    saveGrade(false);
  });

  $(document).on('click', '#finalizeGradeBtn', function () {
    if (typeof swal !== 'undefined') {
      swal.fire({
        title: 'Finalize Grade',
        text: 'Finalized grades are locked and cannot be edited. Confirm?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Finalize',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        theme: swalTheme,
        customClass: {
          popup: 'bg-blur-5 bg-semi-transparent border-1 rounded-2',
          confirmButton: 'btn btn-success py-2 px-3 rounded-3',
          cancelButton: 'btn btn-secondary py-2 px-3 rounded-3',
        },
      }).then((result) => {
        if (result.isConfirmed) saveGrade(true);
      });
    } else {
      saveGrade(true);
    }
  });

  $(document).on('input', '#gradeSearchInput', function () {
    const term = $(this).val().toLowerCase();
    if (role === 'admin') {
      $('#finalizedGradesList .card').each(function () {
        $(this).toggle($(this).text().toLowerCase().includes(term));
      });
    } else if (role === 'coordinator') {
      $('#gradingOverviewList .card').each(function () {
        $(this).toggle($(this).text().toLowerCase().includes(term));
      });
    }
  });

  $('#gradeRefreshBtn').on('click', function () {
    $(this).find('i').addClass('spin');
    setTimeout(() => $(this).find('i').removeClass('spin'), 600);
    if (role === 'student') {
      apiRequest(ENDPOINTS.getGrade, { student_uuid: '' }).done((res) => { if (res.status === 'success') renderStudentGrade(res.grade); });
    } else if (role === 'admin') {
      apiRequest(ENDPOINTS.allGrades, {}).done((res) => { if (res.status === 'success') { state.grades = res.grades || []; $('#gradesCountLabel').text(state.grades.length); renderAdminGrades(); } });
    } else if (role === 'coordinator') {
      apiRequest(ENDPOINTS.overview, {}).done((res) => { if (res.status === 'success') { state.overview = res.overview || []; const s = res.summary || {}; $('#gradeSummaryTotal').text(s.total || 0); $('#gradeSummaryReady').text(s.ready || 0); $('#gradeSummaryComputed').text(s.computed || 0); $('#gradeSummaryFinalized').text(s.finalized || 0); $('#gradeSummaryIncomplete').text(s.incomplete || 0); renderCoordinatorOverview(); } });
    }
  });
});


function renderCoordinatorOverview() {
  const $list = $('#gradingOverviewList');
  if (!$list.length) return;

  if (!state.overview.length) {
    $list.html(`
      <div class="text-center py-5">
        <div class="display-5 text-muted mb-3"><i class="bi bi-journal-x"></i></div>
        <h5 class="fw-bold mb-1">No students to grade</h5>
        <p class="text-muted mb-0 small">Students will appear here once all required components are complete.</p>
      </div>
    `);
    return;
  }

  const sorted = [...state.overview].sort((a, b) => {
    const statusOrder = { ready: 0, computed: 1, finalized: 2, incomplete: 3 };
    return (statusOrder[a.grade_status] || 3) - (statusOrder[b.grade_status] || 3);
  });

  $list.empty();

  sorted.forEach((student) => {
    const canOpen = student.ready_for_grading || student.grade_status === 'computed' || student.grade_status === 'finalized';
    const btnText = student.grade_status === 'finalized' ? 'View' : 'Grade';
    const statusColors = { finalized: 'success', computed: 'primary', ready: 'warning', incomplete: 'secondary' };
    const statusColor = statusColors[student.grade_status] || 'secondary';

    $list.append(`
      <div class="card glass-ui rounded-4 border-0 shadow-sm mb-3">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex flex-column flex-md-row gap-3 align-items-start align-items-md-center justify-content-between">
            <div class="d-flex gap-3 align-items-start flex-grow-1">
              <div class="rounded-circle bg-${statusColor}-subtle text-${statusColor} d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; min-width: 44px;">
                <i class="bi bi-mortarboard fs-5"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                  <h6 class="mb-0 fw-bold text-break">${student.full_name}</h6>
                  <span class="badge rounded-pill ${badgeForStatus(student.grade_status)}">${statusLabel(student.grade_status)}</span>
                </div>
                <div class="text-muted small mb-2">${student.student_number} • ${student.program_code}</div>
                <div class="row g-2 small">
                  <div class="col-6 col-sm-4 col-lg-3">
                    <div class="p-2 rounded-3 border h-100">
                      <span class="text-muted d-block" style="font-size: 0.75rem;">Hours</span>
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>${fmt(student.approved_hours)}/${fmt(student.required_hours)}</strong>
                      </div>
                      <div class="progress" style="height: 6px;">
                        ${(() => {
                          const percent = student.required_hours > 0 ? (student.approved_hours / student.required_hours) * 100 : 0;
                          let barColor = 'bg-danger';
                          if (percent >= 90) barColor = 'bg-success';
                          else if (percent >= 70) barColor = 'bg-warning';
                          else if (percent >= 50) barColor = 'bg-info';
                          return `<div class="progress-bar ${barColor}" role="progressbar" style="width: ${Math.min(100, percent)}%" aria-valuenow="${percent}" aria-valuemin="0" aria-valuemax="100"></div>`;
                        })()}
                      </div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-4 col-lg-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block" style="font-size: 0.75rem;">Evaluations</span><strong>${student.has_midterm && student.has_final && student.has_self ? 'Complete' : 'Incomplete'}</strong></div></div>
                  <div class="col-6 col-sm-4 col-lg-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block" style="font-size: 0.75rem;">Journals</span><strong>${student.approved_journals || 0}</strong></div></div>
                  <div class="col-6 col-sm-4 col-lg-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block" style="font-size: 0.75rem;">Current Grade</span><strong>${student.grade_equivalent || '—'}</strong></div></div>
                </div>
              </div>
            </div>
            <button class="btn btn-${canOpen ? 'primary' : 'outline-secondary'} btn-sm rounded-pill px-4 js-open-workbench" data-student-uuid="${student.student_uuid}" ${canOpen ? '' : 'disabled'}>
              <i class="bi bi-calculator me-2"></i>${btnText}
            </button>
          </div>
        </div>
      </div>
    `);
  });
}

function renderAdminGrades() {
  const $list = $('#finalizedGradesList');
  if (!$list.length) return;

  if (!state.grades.length) {
    $list.html(`
      <div class="text-center py-5">
        <div class="display-5 text-muted mb-3"><i class="bi bi-award"></i></div>
        <h5 class="fw-bold mb-1">No finalized grades yet</h5>
        <p class="text-muted mb-0 small">Finalized grades will appear here as coordinators lock them.</p>
      </div>
    `);
    return;
  }

  $list.empty();
  state.grades.forEach((grade) => {
    $list.append(`
      <div class="card glass-ui rounded-4 border-0 shadow-sm mb-3">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center justify-content-between">
            <div class="d-flex gap-3 align-items-start flex-grow-1">
              <div class="rounded-circle bg-success-subtle text-success d-flex align-items-center justify-content-center flex-shrink-0" style="width: 52px; height: 52px;">
                <i class="bi bi-patch-check fs-4"></i>
              </div>
              <div class="flex-grow-1 min-w-0">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-1">
                  <h5 class="mb-0 fw-bold text-break">${grade.full_name}</h5>
                  <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Finalized</span>
                </div>
                <div class="text-muted small mb-2">${grade.student_number} • ${grade.program_code}</div>
                <div class="row g-2 small">
                  <div class="col-6 col-md-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block">Score</span><strong>${grade.weighted_score_label}</strong></div></div>
                  <div class="col-6 col-md-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block">Grade</span><strong>${grade.grade_equivalent}</strong></div></div>
                  <div class="col-6 col-md-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block">Remarks</span><strong>${grade.remarks}</strong></div></div>
                  <div class="col-6 col-md-3"><div class="p-2 rounded-3 border h-100"><span class="text-muted d-block">By</span><strong>${grade.finalized_by_name || '—'}</strong></div></div>
                </div>
              </div>
            </div>
            <button class="btn btn-outline-light rounded-pill px-4 js-view-grade btn-sm" data-grade-uuid="${grade.uuid}"><i class="bi bi-eye me-2"></i>View</button>
          </div>
        </div>
      </div>
    `);
  });
}

function renderStudentGrade(grade) {
  const $container = $('#studentGradeContainer');
  if (!$container.length) return;

  if (!grade) {
    $container.html(`
      <div class="text-center py-5">
        <div class="display-4 text-muted mb-3"><i class="bi bi-hourglass-split"></i></div>
        <h4 class="fw-bold mb-2 text-white-85">Your grade is pending</h4>
        <p class="text-muted mb-0 small">The coordinator is finalizing your grade. Check back later.</p>
      </div>
    `);
    return;
  }

  // Seamlessly adjust parent card design to let inner glassmorphic dashboard sit on animated background
  $container.parent().removeClass('p-4 p-md-5').addClass('p-0');
  $container.closest('.card').removeClass('bg-blur-5 bg-semi-transparent shadow-sm').addClass('bg-transparent shadow-none border-0');

  $container.html(`
    <div class="row g-4">
      <!-- Left side: Hero Badge and Main Grade -->
      <div class="col-12 col-lg-5">
        <div class="card bg-blur-15 border border-white-10 rounded-4 text-center p-4 p-md-5 h-100 position-relative overflow-hidden shadow-lg" 
             style="background: linear-gradient(135deg, rgba(255,255,255,0.06) 0%, rgba(255,255,255,0.02) 100%);">
          <!-- Ambient Light Overlay -->
          <div class="position-absolute top-0 start-0 w-100 h-100 z-n1 opacity-50" style="background: radial-gradient(circle at 50% 0%, ${grade.grade_color} 0%, transparent 60%);"></div>
          
          <div class="text-muted small text-uppercase fw-bold tracking-wider mb-4">Academic Performance</div>
          
          <!-- Outer circle ring -->
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle p-2 mb-4 mx-auto shadow-lg"
               style="width: 170px; height: 170px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255,255,255,0.1);">
            <div class="d-flex flex-column align-items-center justify-content-center rounded-circle text-white w-100 h-100 shadow-inner"
                 style="background: ${grade.grade_color}; border: 1px solid rgba(255,255,255,0.15);">
              <span class="display-4 fw-black mb-0" style="letter-spacing: -2px; font-weight: 800; font-family: 'Outfit', sans-serif;">${grade.grade_equivalent}</span>
              <span class="text-white-75 small fw-semibold text-uppercase tracking-wider" style="font-size: 0.7rem;">${grade.remarks}</span>
            </div>
          </div>
          
          <div class="mb-4">
            <h2 class="fw-bold mb-2 text-white-85" style="font-family: 'Outfit', sans-serif;">${grade.weighted_score_label}</h2>
            <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-1.5 fw-semibold small">
              <i class="bi bi-patch-check-fill me-1"></i>Finalized & Locked
            </span>
          </div>

          <hr class="border-white-10 my-4">

          <div class="row g-2 text-start">
            <div class="col-6">
              <small class="text-muted d-block mb-1">Finalized Date</small>
              <h6 class="fw-semibold mb-0 text-white-85 small">${grade.finalized_at || '—'}</h6>
            </div>
            <div class="col-6 border-start border-white-10 ps-3">
              <small class="text-muted d-block mb-1">Finalized By</small>
              <h6 class="fw-semibold mb-0 text-white-85 small">${grade.finalized_by_name || 'Coordinator'}</h6>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side: Score Breakdown & Details -->
      <div class="col-12 col-lg-7">
        <div class="card bg-blur-15 border border-white-10 rounded-4 p-4 h-100 shadow-lg"
             style="background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);">
          <div class="d-flex align-items-center justify-content-between mb-4">
            <h5 class="fw-bold mb-0 text-white-85">Grading Component Breakdown</h5>
            <span class="badge bg-white-5 text-muted border border-white-5 rounded-pill px-2.5 py-1 small" style="font-size: 0.72rem;">Sum of weights: 100%</span>
          </div>

          <div class="d-flex flex-column gap-3">
            ${[
              { name: 'Hours & DTR', key: 'hours', icon: 'bi-clock-history', color: 'info' },
              { name: 'Midterm Evaluation', key: 'midterm', icon: 'bi-journal-check', color: 'primary' },
              { name: 'Final Evaluation', key: 'final', icon: 'bi-award', color: 'success' },
              { name: 'Weekly Journals', key: 'journal', icon: 'bi-journal-richtext', color: 'warning' },
              { name: 'Self Evaluation', key: 'self', icon: 'bi-person-bounding-box', color: 'secondary' }
            ].map(c => {
              const score = grade[`${c.key}_score`] || 0;
              const weight = grade[`${c.key}_weight`] || 0;
              const contribution = grade[`${c.key}_contribution`] || 0;
              return `
                <div class="p-3 rounded-4 border border-white-5 hover-lift transition-all" style="background: rgba(255, 255, 255, 0.015);">
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <div class="d-flex align-items-center gap-3">
                      <div class="rounded-3 bg-${c.color}-subtle text-${c.color} d-flex align-items-center justify-content-center" style="width: 38px; height: 38px; min-width: 38px;">
                        <i class="bi ${c.icon} fs-5"></i>
                      </div>
                      <div>
                        <h6 class="fw-semibold mb-0 small text-white-85">${c.name}</h6>
                        <span class="text-muted" style="font-size: 0.7rem;">Weight: ${weight}%</span>
                      </div>
                    </div>
                    <div class="text-end">
                      <div class="fw-bold text-white-85 small">${fmt(score)}%</div>
                      <span class="text-${c.color} fw-medium" style="font-size: 0.72rem;">+${fmt(contribution)}% to total</span>
                    </div>
                  </div>
                  <div class="progress rounded-pill bg-white-5" style="height: 6px;">
                    <div class="progress-bar bg-${c.color} progress-bar-striped progress-bar-animated rounded-pill" role="progressbar" style="width: ${score}%" aria-valuenow="${score}" aria-valuemin="0" aria-valuemax="100"></div>
                  </div>
                </div>
              `;
            }).join('')}
          </div>
        </div>
      </div>
    </div>

    <!-- Bottom: Coordinator Remarks (if any) -->
    ${grade.coordinator_notes ? `
      <div class="row mt-4">
        <div class="col-12">
          <div class="card bg-blur-15 border border-white-10 rounded-4 p-4 shadow-lg"
               style="background: linear-gradient(135deg, rgba(255,255,255,0.04) 0%, rgba(255,255,255,0.01) 100%);">
            <div class="d-flex align-items-start gap-3">
              <div class="rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center flex-shrink-0 animate-pulse" style="width: 44px; height: 44px;">
                <i class="bi bi-chat-left-quote fs-5"></i>
              </div>
              <div class="flex-grow-1">
                <h6 class="fw-bold mb-1 text-white-85">Coordinator Remarks & Feedback</h6>
                <p class="text-muted mb-0 small text-break" style="line-height: 1.6; font-style: italic; font-size: 0.85rem;">
                  "${grade.coordinator_notes}"
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    ` : ''}
  `);
}

function renderPreviewFromGradeOrCompute() {
  const studentUuid = state.selectedStudent?.student_uuid;
  if (!studentUuid) return;

  const weights = currentWeights();
  showWeightTotal(weights);

  apiRequest(ENDPOINTS.compute, {
    student_uuid: studentUuid,
    batch_uuid: '',
    ...Object.fromEntries(Object.entries(weights).map(([k, v]) => [`${k}_weight`, v])),
  }).done((res) => {
    if (res.status !== 'success') {
      $('#gradePreviewPanel').html(`<div class="alert alert-warning border-0 mb-0 rounded-4"><i class="bi bi-exclamation-triangle me-2"></i>${res.message || 'Unable to compute preview.'}</div>`);
      return;
    }
    const c = res.computed;
    $('#gradePreviewPanel').html(`
      <div class="row g-3">
        ${componentCard('Hours', `${fmt(c.hours_score)}%`, `${fmt(c.dtr_summary.total_approved)} / ${fmt(c.dtr_summary.required_hours)} hours`, 'info')}
        ${componentCard('Midterm', `${fmt(c.midterm_score)}%`, `Raw ${fmt(c.eval_summary.midterm_score)} / 5.0`, 'primary')}
        ${componentCard('Final', `${fmt(c.final_score)}%`, `Raw ${fmt(c.eval_summary.final_score)} / 5.0`, 'success')}
        ${componentCard('Journal', `${fmt(c.journal_score)}%`, `${c.dtr_summary.approved_count || 0} approved entries`, 'warning')}
        ${componentCard('Self', `${fmt(c.self_score)}%`, `Raw ${fmt(c.eval_summary.self_score)} / 5.0`, 'secondary')}
        <div class="col-12 col-lg-4"><div class="p-4 rounded-4 border bg-success-subtle text-success-emphasis h-100"><div class="small text-uppercase fw-semibold mb-2">Final Score</div><div class="display-5 fw-bold mb-2">${fmt(c.weighted_score)}%</div><div class="small"><strong>${c.grade_equivalent}</strong> — ${c.remarks}</div></div></div>
      </div>
    `);
  }).fail((xhr, status, error) => Errors(xhr, status, error));
}

function saveGrade(finalize = false) {
  if (role !== 'coordinator') return;
  const studentUuid = state.selectedStudent?.student_uuid;
  if (!studentUuid) return;

  const weights = currentWeights();
  const totalWeight = Object.values(weights).reduce((sum, n) => sum + Number(n || 0), 0);
  
  if (Math.abs(totalWeight - 100) > 0.01) {
    toast('warning', `Weights must total 100%. Current: ${totalWeight.toFixed(2)}%`);
    return;
  }

  const payload = {
    student_uuid: studentUuid,
    batch_uuid: '',
    ...Object.fromEntries(Object.entries(weights).map(([k, v]) => [`${k}_weight`, v])),
    coordinator_notes: $('#gradeCoordinatorNotes').val().trim(),
  };

  const endpoint = finalize ? ENDPOINTS.finalize : ENDPOINTS.save;
  const $btn = finalize ? $('#finalizeGradeBtn') : $('#saveGradeBtn');
  const original = $btn.html();
  $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

  apiRequest(endpoint, payload)
    .done((res) => {
      if (res.status !== 'success') {
        toast('error', res.message || 'Could not save grade.');
        $btn.prop('disabled', false).html(original);
        return;
      }
      toast('success', res.message || (finalize ? 'Grade finalized and locked.' : 'Grade draft saved.'));
      setTimeout(() => {
        $('#gradeWorkbenchModal').modal('hide');
        location.reload();
      }, 800);
    })
    .fail((xhr, status, error) => {
      $btn.prop('disabled', false).html(original);
      Errors(xhr, status, error);
    });
}


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
    transform: translateY(-3px);
    border-color: rgba(255, 255, 255, 0.15) !important;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2) !important;
    background: rgba(255, 255, 255, 0.03) !important;
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
  .tracking-wider {
    letter-spacing: 0.05em;
  }
  .fw-black {
    font-weight: 900 !important;
  }
  @keyframes pulse-subtle {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.85; }
  }
  .animate-pulse {
    animation: pulse-subtle 2s infinite ease-in-out;
  }
`;
document.head.appendChild(style);
