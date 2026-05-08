import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
const pageOjtStartDate = document.body?.dataset?.ojtStartDate || '';

function pad2(n) {
    return String(n).padStart(2, '0');
}

function formatLocalDate(date) {
    return `${date.getFullYear()}-${pad2(date.getMonth() + 1)}-${pad2(date.getDate())}`;
}

function parseDateInput(value) {
    if (!value) return null;
    const [year, month, day] = value.split('-').map(Number);
    if (!year || !month || !day) return null;
    return new Date(year, month - 1, day);
}

function clampDateString(value, minValue) {
    if (!minValue || !value) return value;
    return value < minValue ? minValue : value;
}

function getWeekStartDefault() {
    const today = new Date();
    const monday = new Date(today);
    monday.setDate(today.getDate() - ((today.getDay() + 6) % 7));
    const defaultValue = formatLocalDate(monday);
    return clampDateString(defaultValue, pageOjtStartDate);
}

function addDays(dateValue, days) {
    const date = parseDateInput(dateValue);
    if (!date) return '';
    date.setDate(date.getDate() + days);
    return formatLocalDate(date);
}

function setJournalFieldError(field, message = '') {
    const map = {
        week_start: { errorId: 'weekStartError', inputId: 'weekStart' },
        week_end: { errorId: 'weekEndError', inputId: 'weekEnd' },
        accomplishments: { errorId: 'accomplishmentsError', inputId: 'accomplishments' },
    };

    const info = map[field];
    if (!info) return;

    const errEl = document.getElementById(info.errorId);
    const inputEl = document.getElementById(info.inputId);

    if (errEl) {
        errEl.textContent = message || '';
    }

    if (inputEl) {
        if (message) {
            inputEl.classList.add('is-invalid');
        } else {
            inputEl.classList.remove('is-invalid');
        }
    }
}

function clearJournalFieldErrors() {
    ['week_start', 'week_end', 'accomplishments'].forEach((field) => setJournalFieldError(field, ''));
}

$(document).ready(function () {
    let currentStatusFilter = '';
    let currentSearch = '';
    let journalsCache = [];

    if (pageOjtStartDate) {
        $('#weekStart').attr('min', pageOjtStartDate);
    }

    loadJournals();

    // Filtering
    $('#journalStatusFilter').change(function() {
        currentStatusFilter = $(this).val();
        renderJournals();
    });

    $('#journalSearchInput').on('keyup', function() {
        currentSearch = $(this).val().toLowerCase();
        renderJournals();
    });

    $('#dashboardRefreshBtn').click(function() {
        loadJournals();
    });

    // New Entry Modal
    $('#newJournalEntryBtn, #emptyStateNewJournalBtn').click(function() {
        $('#journalEntryForm')[0].reset();
        $('#journalEntryUuid').val('');
        $('#returnFeedbackContainer').addClass('d-none');
        $('#journalEntryModalTitle').text('Weekly Journal Entry');
        $('#saveJournalEntryBtn').text('Submit Journal').data('mode', 'new');
        clearJournalFieldErrors();

        const weekStart = getWeekStartDefault();
        $('#weekStart').attr('min', pageOjtStartDate || '');
        $('#weekStart').val(weekStart);
        $('#weekEnd').val(addDays(weekStart, 4));

        $('#journalEntryModal').modal('show');
    });

    // Save/Submit Journal
    $('#saveJournalEntryBtn').click(function() {
        let btn = $(this);
        let ogText = btn.text();
        let mode = btn.data('mode');

        clearJournalFieldErrors();
        
        let data = {
            csrf_token: csrfToken,
            week_start: $('#weekStart').val(),
            week_end: $('#weekEnd').val(),
            accomplishments: $('#accomplishments').val(),
            skills_learned: $('#skillsLearned').val(),
            challenges: $('#challenges').val(),
            plans_next_week: $('#plansNextWeek').val()
        };

        if(mode === 'edit') {
            data.journal_uuid = $('#journalEntryUuid').val();
        }

        let endpoint = mode === 'edit' ? '../../../Process/journal/edit_journal' : '../../../Process/journal/submit_journal';

        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: endpoint,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#journalEntryModal').modal('hide');
                    ToastVersion(swalTheme, 'Journal submitted successfully!', 'success');
                    loadJournals();
                } else {
                    if (response.errors) {
                        Object.entries(response.errors).forEach(([field, message]) => {
                            setJournalFieldError(field, message);
                        });
                    }

                    if (response.message) {
                        ToastVersion(swalTheme, response.message, 'error', 3500, 'top-end');
                    } else {
                        ToastVersion(swalTheme, 'Validation failed. Please review the highlighted fields.', 'error', 3500, 'top-end');
                    }
                }
            },
            error: function() {
                Errors('Failed to process request.', 'error');
            },
            complete: function() {
                btn.prop('disabled', false).text(ogText);
            }
        });
    });

    $('#weekStart').on('change input', function () {
        const weekStart = $(this).val();
        if (!weekStart) return;

        const normalizedStart = clampDateString(weekStart, pageOjtStartDate);
        if (normalizedStart !== weekStart) {
            $(this).val(normalizedStart);
        }

        const currentEnd = $('#weekEnd').val();
        const minEnd = addDays(normalizedStart, 0);
        const defaultEnd = addDays(normalizedStart, 4);
        $('#weekEnd').attr('min', minEnd);

        if (!currentEnd || currentEnd < normalizedStart) {
            $('#weekEnd').val(defaultEnd);
        }
    });

    $('#weekEnd').on('change input', function () {
        const weekStart = $('#weekStart').val();
        if (!weekStart || !$(this).val()) return;

        const maxAllowedEnd = addDays(weekStart, 6);
        if ($(this).val() > maxAllowedEnd) {
            $(this).val(maxAllowedEnd);
        }
    });

    // View Details
    $(document).on('click', '.view-journal-btn', function() {
        let uuid = $(this).data('uuid');
        let journal = journalsCache.find(j => j.uuid === uuid);
        
        if (!journal) return;

        $('#viewJournalWeekRange').text(journal.week_label + ' (' + journal.week_range + ')');
        $('#viewAccomplishments').text(journal.accomplishments || '—');
        $('#viewSkillsLearned').text(journal.skills_learned || '—');
        $('#viewChallenges').text(journal.challenges || '—');
        $('#viewPlansNextWeek').text(journal.plans_next_week || '—');
        
        $('#viewJournalStatusBadge').html(`<span class="badge px-3 py-2 rounded-pill fs-6" style="background-color: ${journal.status_bg}; color: ${journal.status_text};"><i class="bi bi-circle-fill small me-2"></i>${journal.status_label}</span>`);

        if (journal.coordinator_remarks) {
            $('#viewCoordinatorRemarks').text(journal.coordinator_remarks);
            $('#viewCoordinatorRemarksContainer').removeClass('d-none');
        } else {
            $('#viewCoordinatorRemarksContainer').addClass('d-none');
        }

        if (journal.can_edit) {
            $('#editReturnedJournalBtn').removeClass('d-none').data('uuid', uuid);
        } else {
            $('#editReturnedJournalBtn').addClass('d-none');
        }

        $('#viewJournalModal').modal('show');
    });

    // Edit Resubmit Button
    $('#editReturnedJournalBtn').click(function() {
        let uuid = $(this).data('uuid');
        let journal = journalsCache.find(j => j.uuid === uuid);
        if (!journal) return;

        $('#viewJournalModal').modal('hide');

        $('#journalEntryForm')[0].reset();
        $('#journalEntryUuid').val(uuid);
        $('#journalEntryModalTitle').text('Edit & Resubmit Journal');
        $('#saveJournalEntryBtn').text('Resubmit Journal').data('mode', 'edit');

        $('#weekStart').val(journal.week_start);
        $('#weekEnd').val(journal.week_end);
        $('#accomplishments').val(journal.accomplishments);
        $('#skillsLearned').val(journal.skills_learned);
        $('#challenges').val(journal.challenges);
        $('#plansNextWeek').val(journal.plans_next_week);

        if (journal.return_reason) {
            $('#returnReasonText').text(journal.return_reason);
            $('#returnFeedbackContainer').removeClass('d-none');
        }

        $('#journalEntryModal').modal('show');
    });

    // Export Journal Button
    $('#exportJournalBtn').click(function() {
        let currentJournalUuid = journalsCache.find(j => j.uuid === ($('.view-journal-btn').data('uuid') || ''))?.uuid;
        
        // Find the currently open modal's journal UUID
        let openJournal = null;
        journalsCache.forEach(journal => {
            if (journal.uuid) {
                let btn = $(`.view-journal-btn[data-uuid="${journal.uuid}"]`);
                if (btn.length) openJournal = journal;
            }
        });

        if (!openJournal) {
            ToastVersion(swalTheme, 'Unable to determine journal. Please try again.', 'error', 3000, 'top-end');
            return;
        }

        exportJournalPdf(openJournal.uuid);
    });

    function exportJournalPdf(journalUuid) {
        const $btn = $('#exportJournalBtn');
        const originalHTML = $btn.html();

        $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generating PDF...');

        $.ajax({
            url: '../../../process/journal/export_journal_pdf',
            method: 'POST',
            data: {
                csrf_token: csrfToken,
                journal_uuid: journalUuid
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response, status, xhr) {
                $btn.prop('disabled', false).html(originalHTML);

                const contentType = (xhr.getResponseHeader('Content-Type') || '').toLowerCase();
                if (contentType.includes('application/json')) {
                    const reader = new FileReader();
                    reader.onload = function() {
                        try {
                            const json = JSON.parse(String(reader.result || '{}'));
                            ToastVersion(swalTheme, json.message || 'Failed to generate PDF.', 'warning', 3500, 'top-end');
                        } catch {
                            ToastVersion(swalTheme, 'Unexpected server response.', 'error', 3500, 'top-end');
                        }
                    };
                    reader.readAsText(response);
                    return;
                }

                const contentDisposition = xhr.getResponseHeader('Content-Disposition') || '';
                const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|")?([^";]+)/i);
                const fileName = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : 'journal.pdf';

                const blob = response instanceof Blob ? response : new Blob([response], { type: 'application/pdf' });
                const blobUrl = window.URL.createObjectURL(blob);
                const link = document.createElement('a');
                link.href = blobUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                window.URL.revokeObjectURL(blobUrl);

                ToastVersion(swalTheme, 'Journal exported successfully!', 'success', 2500, 'top-end');
            },
            error: function() {
                $btn.prop('disabled', false).html(originalHTML);
                Errors('Failed to export journal as PDF', 'error');
            }
        });
    }

    function loadJournals() {
        $('#studentJournalList').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading journals...</p></div>');
        
        $.ajax({
            url: '../../../Process/journal/get_journals',
            type: 'POST',
            data: { csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    journalsCache = response.journals || [];
                    updateStats();
                    renderJournals();
                } else {
                    Errors(response.message || 'Failed to load journals', 'error');
                }
            },
            error: function() {
                Errors('Server error while loading journals', 'error');
            }
        });
    }

    function updateStats() {
        let stats = { total: journalsCache.length, approved: 0, submitted: 0, returned: 0 };
        
        journalsCache.forEach(j => {
            if (stats[j.status] !== undefined) {
                stats[j.status]++;
            }
        });

        $('#totalJournalsCount').text(stats.total);
        $('#approvedJournalsCount').text(stats.approved);
        $('#pendingJournalsCount').text(stats.submitted);
        $('#returnedJournalsCount').text(stats.returned);

        if (stats.total === 0) {
            $('#studentJournalEmptyState').removeClass('d-none');
            $('#studentJournalList').addClass('d-none');
        } else {
            $('#studentJournalEmptyState').addClass('d-none');
            $('#studentJournalList').removeClass('d-none');
        }
    }

    function renderJournals() {
        let filtered = journalsCache.filter(j => {
            let matchStatus = currentStatusFilter === '' || j.status === currentStatusFilter;
            let matchSearch = currentSearch === '' || 
                              (j.accomplishments || '').toLowerCase().includes(currentSearch) ||
                              (j.skills_learned || '').toLowerCase().includes(currentSearch) ||
                              (j.week_label || '').toLowerCase().includes(currentSearch);
            return matchStatus && matchSearch;
        });

        let list = $('#studentJournalList');
        list.empty();

        if (journalsCache.length > 0 && filtered.length === 0) {
            list.html('<div class="col-12 text-center py-4 text-muted">No journals match your filters.</div>');
            return;
        }

        filtered.forEach(j => {
            let needsAttention = j.status === 'returned';
            
            let html = `
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card h-100 bg-blur-5 bg-semi-transparent border border-light border-opacity-10 rounded-4 position-relative overflow-hidden shadow-sm view-journal-btn" data-uuid="${j.uuid}" style="cursor: pointer;">
                    ${needsAttention ? '<div class="position-absolute top-0 start-0 w-100 h-2 bg-danger"></div>' : ''}
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3 gap-2">
                            <div>
                                <h6 class="mb-1 fw-bold text-body">${j.week_label}</h6>
                                <p class="text-muted small mb-0"><i class="bi bi-calendar3 me-1"></i>${j.week_range}</p>
                            </div>
                            <span class="badge rounded-pill flex-shrink-0" style="background-color: ${j.status_bg}; color: ${j.status_text}; font-size: 0.75rem; padding: 0.5rem 0.75rem;">${j.status_label}</span>
                        </div>

                        <!-- Content Preview -->
                        <p class="card-text small text-body-secondary mb-3 lh-1.5 flex-grow-1" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            ${j.accomplishments || 'No accomplishments listed.'}
                        </p>

                        <!-- Footer -->
                        <div class="pt-3 border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-clock me-1"></i>${j.time_ago}</small>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-4 py-1 fw-medium">View</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            list.append(html);
        });
    }
});
