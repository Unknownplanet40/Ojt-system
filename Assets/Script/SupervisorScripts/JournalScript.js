import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

$(document).ready(function () {
    let currentSearch = '';
    let journalsCache = [];

    loadJournals();

    $('#journalSearchInput').on('keyup', function() {
        currentSearch = $(this).val().toLowerCase();
        renderJournals();
    });

    $('#dashboardRefreshBtn').click(function() {
        loadJournals();
    });

    // View Details
    $(document).on('click', '.view-journal-btn', function() {
        let uuid = $(this).data('uuid');
        let journal = journalsCache.find(j => j.uuid === uuid);
        if (!journal) return;

        // Store the current journal UUID for export
        $('#viewJournalModal').data('journalUuid', uuid);

        $('#viewStudentName').text(journal.full_name);
        $('#viewJournalWeekRange').text(journal.week_label + ' (' + journal.week_range + ')');
        
        $('#viewJournalStatusBadge').html(`<span class="badge px-3 py-2 rounded-pill fs-6" style="background-color: ${journal.status_bg}; color: ${journal.status_text};"><i class="bi bi-circle-fill small me-2"></i>${journal.status_label}</span>`);

        $('#viewAccomplishments').text(journal.accomplishments || '—');
        $('#viewSkillsLearned').text(journal.skills_learned || '—');
        $('#viewChallenges').text(journal.challenges || '—');
        $('#viewPlansNextWeek').text(journal.plans_next_week || '—');

        $('#viewJournalModal').modal('show');
    });

    // Export Journal Button
    $('#exportJournalBtn').click(function() {
        let uuid = $('#viewJournalModal').data('journalUuid');
        if (!uuid) {
            ToastVersion(swalTheme, 'Unable to determine journal. Please try again.', 'error', 3000, 'top-end');
            return;
        }
        exportJournalPdf(uuid);
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
        $('#supervisorJournalList').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading student journals...</p></div>');
        
        $.ajax({
            url: '../../../Process/journal/get_journals',
            type: 'POST',
            data: { csrf_token: csrfToken },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    journalsCache = response.journals || [];
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

    function renderJournals() {
        let filtered = journalsCache.filter(j => {
            let matchSearch = currentSearch === '' || 
                              (j.full_name || '').toLowerCase().includes(currentSearch) ||
                              (j.student_number || '').toLowerCase().includes(currentSearch) ||
                              (j.accomplishments || '').toLowerCase().includes(currentSearch);
            return matchSearch;
        });

        let list = $('#supervisorJournalList');
        list.empty();

        if (journalsCache.length === 0 && currentSearch === '') {
            $('#supervisorJournalEmptyState').removeClass('d-none');
            $('#supervisorJournalList').addClass('d-none');
            return;
        } else {
            $('#supervisorJournalEmptyState').addClass('d-none');
            $('#supervisorJournalList').removeClass('d-none');
        }

        if (filtered.length === 0) {
            list.html('<div class="col-12 text-center py-5 text-muted">No journals match your search.</div>');
            return;
        }

        filtered.forEach(j => {
            let html = `
            <div class="col-12 col-lg-6">
                <div class="card h-100 bg-blur-5 bg-semi-transparent border border-light border-opacity-10 rounded-4 position-relative overflow-hidden shadow-sm view-journal-btn cursor-pointer" data-uuid="${j.uuid}" style="cursor: pointer;">
                    <div class="card-body p-4 d-flex flex-column h-100">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-4 gap-2">
                            <div class="d-flex align-items-center gap-3 flex-grow-1 min-w-0">
                                <div class="avatar avatar-md rounded-circle bg-primary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0 text-primary" style="width: 48px; height: 48px;">
                                    <i class="bi bi-person fw-bold"></i>
                                </div>
                                <div class="min-w-0">
                                    <h6 class="mb-0 fw-bold text-body text-truncate">${j.full_name}</h6>
                                    <p class="text-muted small mb-0 text-truncate">${j.student_number}</p>
                                </div>
                            </div>
                            <span class="badge rounded-pill flex-shrink-0" style="background-color: ${j.status_bg}; color: ${j.status_text}; font-size: 0.75rem; padding: 0.5rem 0.75rem;">${j.status_label}</span>
                        </div>

                        <!-- Content Preview -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
                                <span class="fw-semibold small text-primary"><i class="bi bi-calendar3 me-1"></i>${j.week_label}</span>
                                <span class="text-muted small">${j.week_range}</span>
                            </div>
                            <p class="small text-body-secondary mb-0 lh-1.5" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                                ${j.accomplishments || 'No accomplishments listed.'}
                            </p>
                        </div>

                        <!-- Footer -->
                        <div class="mt-auto pt-3 border-top border-light border-opacity-10 d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-clock me-1"></i>${j.time_ago}</small>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-4 py-1 fw-medium">View Details</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            list.append(html);
        });
    }
});
