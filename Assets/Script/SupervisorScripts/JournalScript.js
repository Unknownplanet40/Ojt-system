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

        $('#viewStudentName').text(journal.full_name);
        $('#viewJournalWeekRange').text(journal.week_label + ' (' + journal.week_range + ')');
        
        $('#viewJournalStatusBadge').html(`<span class="badge px-3 py-2 rounded-pill fs-6" style="background-color: ${journal.status_bg}; color: ${journal.status_text};"><i class="bi bi-circle-fill small me-2"></i>${journal.status_label}</span>`);

        $('#viewAccomplishments').text(journal.accomplishments || '—');
        $('#viewSkillsLearned').text(journal.skills_learned || '—');
        $('#viewChallenges').text(journal.challenges || '—');
        $('#viewPlansNextWeek').text(journal.plans_next_week || '—');

        $('#viewJournalModal').modal('show');
    });

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
            <div class="col-12 col-xl-6">
                <div class="card h-100 bg-blur-5 bg-semi-transparent border-1 rounded-4 position-relative border-secondary-subtle" style="cursor: pointer;">
                    <div class="card-body p-4 view-journal-btn" data-uuid="${j.uuid}">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-md rounded-circle bg-primary bg-opacity-10 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px;">
                                    <span class="fs-5 fw-semibold text-primary"><i class="bi bi-person"></i></span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-body">${j.full_name}</h6>
                                    <p class="text-muted small mb-0">${j.student_number}</p>
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
