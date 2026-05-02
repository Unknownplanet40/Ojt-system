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

    // Filters
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active bg-primary bg-opacity-10 text-primary border-primary');
        $(this).addClass('active bg-primary bg-opacity-10 text-primary border-primary');
        currentStatusFilter = $(this).data('filter');
        renderJournals();
    });

    $('#journalSearchInput').on('keyup', function() {
        currentSearch = $(this).val().toLowerCase();
        renderJournals();
    });

    $('#dashboardRefreshBtn').click(function() {
        loadJournals();
    });

    // View Details for Review
    $(document).on('click', '.review-journal-btn', function() {
        let uuid = $(this).data('uuid');
        let journal = journalsCache.find(j => j.uuid === uuid);
        if (!journal) return;

        $('#reviewJournalUuid').val(uuid);
        $('#viewStudentName').text(journal.full_name);
        $('#viewJournalWeekRange').text(journal.week_label + ' (' + journal.week_range + ')');
        
        $('#viewJournalStatusBadge').html(`<span class="badge px-3 py-2 rounded-pill fs-6" style="background-color: ${journal.status_bg}; color: ${journal.status_text};"><i class="bi bi-circle-fill small me-2"></i>${journal.status_label}</span>`);

        $('#viewAccomplishments').text(journal.accomplishments || '—');
        $('#viewSkillsLearned').text(journal.skills_learned || '—');
        $('#viewChallenges').text(journal.challenges || '—');
        $('#viewPlansNextWeek').text(journal.plans_next_week || '—');

        $('#coordinatorRemarks').val(journal.coordinator_remarks || '');
        $('#returnReason').val(journal.return_reason || '');

        $('#btnApproveJournal, #btnReturnJournal, #btnSaveRemarks').removeClass('d-none');
        $('#returnReasonContainer').addClass('d-none');

        // Logic for returning
        $('#btnReturnJournal').off('click').on('click', function(e) {
            if ($('#returnReasonContainer').hasClass('d-none')) {
                // Show return reason box first
                e.preventDefault();
                $('#returnReasonContainer').removeClass('d-none').hide().slideDown();
                $('#btnApproveJournal, #btnSaveRemarks').addClass('d-none');
                return;
            }
            // If already shown, it will proceed to submit action
        });

        // If approved, hide approve and return buttons
        if (journal.status === 'approved') {
            $('#btnApproveJournal, #btnReturnJournal').addClass('d-none');
        }

        $('#reviewJournalModal').modal('show');
    });

    // Handle Review Actions
    $('.action-btn').click(function(e) {
        if ($(this).attr('id') === 'btnReturnJournal' && $('#returnReasonContainer').hasClass('d-none')) {
            return; // handled by the toggle logic above
        }

        let action = $(this).data('action');
        let uuid = $('#reviewJournalUuid').val();
        let remarks = $('#coordinatorRemarks').val();
        let returnReason = $('#returnReason').val();

        if (action === 'return' && returnReason.trim() === '') {
            $('#returnReason').addClass('is-invalid');
            $('#returnReasonError').text('Return reason is required.');
            return;
        }

        let btn = $(this);
        let ogText = btn.html();
        $('.action-btn').prop('disabled', true);
        btn.html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

        $.ajax({
            url: '../../../Process/journal/review_journal',
            type: 'POST',
            data: {
                csrf_token: csrfToken,
                journal_uuid: uuid,
                action: action,
                remarks: remarks,
                return_reason: returnReason
            },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    $('#reviewJournalModal').modal('hide');
                    ToastVersion(swalTheme, response.message, 'success');
                    loadJournals();
                } else {
                    Errors(response.message || response.error, 'error');
                }
            },
            error: function() {
                Errors('Server error during review processing.', 'error');
            },
            complete: function() {
                $('.action-btn').prop('disabled', false);
                btn.html(ogText);
            }
        });
    });

    // Reset validation state
    $('#returnReason').on('input', function() {
        $(this).removeClass('is-invalid');
    });


    function loadJournals() {
        $('#coordinatorJournalList').html('<div class="col-12 text-center py-5"><div class="spinner-border text-primary"></div><p class="mt-3">Loading student journals...</p></div>');
        
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

        $('#statTotal').text(stats.total);
        $('#statApproved').text(stats.approved);
        $('#statPending').text(stats.submitted);
        $('#statReturned').text(stats.returned);

        if (stats.submitted > 0) {
            $('#badgePending').text(stats.submitted).removeClass('d-none');
        } else {
            $('#badgePending').addClass('d-none');
        }

        if (stats.total === 0 && currentStatusFilter === '' && currentSearch === '') {
            $('#coordinatorJournalEmptyState').removeClass('d-none');
            $('#coordinatorJournalList').addClass('d-none');
        } else {
            $('#coordinatorJournalEmptyState').addClass('d-none');
            $('#coordinatorJournalList').removeClass('d-none');
        }
    }

    function renderJournals() {
        let filtered = journalsCache.filter(j => {
            let matchStatus = currentStatusFilter === '' || j.status === currentStatusFilter;
            let matchSearch = currentSearch === '' || 
                              (j.full_name || '').toLowerCase().includes(currentSearch) ||
                              (j.student_number || '').toLowerCase().includes(currentSearch) ||
                              (j.accomplishments || '').toLowerCase().includes(currentSearch);
            return matchStatus && matchSearch;
        });

        let list = $('#coordinatorJournalList');
        list.empty();

        if (journalsCache.length > 0 && filtered.length === 0) {
            list.html('<div class="col-12 text-center py-5 text-muted">No journals match your filters.</div>');
            return;
        }

        filtered.forEach(j => {
            let isPending = j.status === 'submitted';
            
            let html = `
            <div class="col-12 col-xl-6">
                <div class="card h-100 bg-blur-5 bg-semi-transparent border-1 rounded-4 position-relative ${isPending ? 'border-info border-opacity-50' : 'border-secondary-subtle'}" style="cursor: pointer;">
                    <div class="card-body p-4 review-journal-btn" data-uuid="${j.uuid}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-md rounded-circle bg-primary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <span class="fs-5 fw-semibold text-primary">${j.initials || '—'}</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-body">${j.full_name}</h6>
                                    <p class="text-muted small mb-0">${j.program_code} • ${j.student_number}</p>
                                </div>
                            </div>
                            <span class="badge rounded-pill" style="background-color: ${j.status_bg}; color: ${j.status_text}">${j.status_label}</span>
                        </div>
                        
                        <div class="bg-body-tertiary bg-opacity-50 rounded-3 p-3 mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-semibold small">${j.week_label}</span>
                                <span class="text-muted small"><i class="bi bi-calendar3 me-1"></i>${j.week_range}</span>
                            </div>
                            <p class="small text-body-secondary mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                ${j.accomplishments || 'No accomplishments listed.'}
                            </p>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><i class="bi bi-clock me-1"></i>Submitted ${j.time_ago}</small>
                            <button class="btn btn-sm btn-light border rounded-pill px-3 py-1">Review</button>
                        </div>
                    </div>
                </div>
            </div>
            `;
            list.append(html);
        });
    }
});
