import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

$(document).ready(function () {
    let currentStatusFilter = '';
    let currentSearch = '';
    let journalsCache = [];

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
        
        // Auto-select week start (Monday) and week end (Friday) for convenience (user can change it)
        const today = new Date();
        const monday = new Date(today);
        monday.setDate(monday.getDate() - monday.getDay() + 1); // Get Monday
        const friday = new Date(monday);
        friday.setDate(friday.getDate() + 4); // Get Friday

        $('#weekStart').val(monday.toISOString().split('T')[0]);
        $('#weekEnd').val(friday.toISOString().split('T')[0]);

        $('#journalEntryModal').modal('show');
    });

    // Save/Submit Journal
    $('#saveJournalEntryBtn').click(function() {
        let btn = $(this);
        let ogText = btn.text();
        let mode = btn.data('mode');
        
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
                        let errorMsg = Object.values(response.errors).join('<br>');
                        Errors(errorMsg, 'error');
                    } else if (response.message) {
                        Errors(response.message, 'error');
                    } else {
                        Errors('Validation failed.', 'error');
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
            let attentionPulse = needsAttention ? '<span class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-light rounded-circle"><span class="visually-hidden">New alerts</span></span>' : '';
            
            let html = `
            <div class="col">
                <div class="card h-100 bg-blur-5 bg-semi-transparent border-1 rounded-4 position-relative ${needsAttention ? 'border-danger border-opacity-50' : 'border-secondary-subtle'}" style="cursor: pointer;">
                    ${attentionPulse}
                    <div class="card-body p-4 view-journal-btn" data-uuid="${j.uuid}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h6 class="mb-1 fw-bold text-body">${j.week_label}</h6>
                                <p class="text-muted small mb-0"><i class="bi bi-calendar3 me-1"></i>${j.week_range}</p>
                            </div>
                            <span class="badge rounded-pill" style="background-color: ${j.status_bg}; color: ${j.status_text}">${j.status_label}</span>
                        </div>
                        
                        <p class="card-text small text-body-secondary mb-3" style="display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden;">
                            ${j.accomplishments || 'No accomplishments listed.'}
                        </p>
                        
                        <hr class="my-3 opacity-25">
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-clock me-1"></i>${j.time_ago}</small>
                            <button class="btn btn-sm btn-light border rounded-pill px-3 py-1">View Details</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            list.append(html);
        });
    }
});
