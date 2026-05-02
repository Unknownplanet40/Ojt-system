$(document).ready(function () {
    const $container = $("#evaluationsContainer");
    const $detailsModal = new bootstrap.Modal(document.getElementById("evalDetailsModal"));
    
    let evaluationsData = [];

    loadEvaluations();

    function loadEvaluations() {
        $.ajax({
            url: "../../../Process/evaluation/get_evaluations",
            type: "POST",
            dataType: "json",
            data: { csrf_token: csrfToken },
            success: function (res) {
                if (res.status === "success") {
                    evaluationsData = res.evaluations || [];
                    renderEvaluations(evaluationsData);
                } else {
                    $container.html(`<div class="col-12"><div class="alert alert-danger text-center border-0">${res.message}</div></div>`);
                }
            },
            error: function (xhr, status, error) {
                Errors(xhr, status, error);
                $container.html(`<div class="col-12"><div class="alert alert-danger text-center border-0">Failed to load data.</div></div>`);
            }
        });
    }

    function renderEvaluations(evals) {
        $container.empty();

        if (evals.length === 0) {
            $container.html(`
                <div class="col-12 text-center py-5">
                    <div class="avatar avatar-xl bg-secondary-subtle text-secondary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="bi bi-file-earmark-x fs-2"></i>
                    </div>
                    <h5 class="fw-bold mb-1">No Evaluations Found</h5>
                    <p class="text-muted mb-0">There are no submitted evaluations for this batch yet.</p>
                </div>
            `);
            return;
        }

        evals.forEach((e, index) => {
            let badgeClass = 'bg-primary-subtle text-primary-emphasis';
            if (e.eval_type === 'final') badgeClass = 'bg-success-subtle text-success-emphasis';
            if (e.eval_type === 'self') badgeClass = 'bg-info-subtle text-info-emphasis';

            let typeIcon = 'bi-clipboard-data';
            if (e.eval_type === 'final') typeIcon = 'bi-clipboard-check';
            if (e.eval_type === 'self') typeIcon = 'bi-person-vcard';

            $container.append(`
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm bg-blur-5 bg-semi-transparent">
                        <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                            <span class="badge ${badgeClass} rounded-pill px-3 py-2"><i class="bi ${typeIcon} me-1"></i>${e.eval_label}</span>
                            <span class="badge bg-light text-dark border">${e.program_code}</span>
                        </div>
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-4">
                                <div class="avatar bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 48px; height: 48px;">
                                    ${e.full_name.charAt(0)}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-body">${e.full_name}</h6>
                                    <small class="text-muted">${e.student_number}</small>
                                </div>
                            </div>
                            
                            <div class="d-flex align-items-center mb-4 p-3 bg-body-tertiary rounded-3">
                                <div class="me-4 text-center">
                                    <h3 class="mb-0 fw-bold text-primary">${e.total_score}</h3>
                                    <span class="small text-muted">/ 5 Score</span>
                                </div>
                                <div class="text-center border-start ps-4 border-2">
                                    <h3 class="mb-0 fw-bold text-success">${e.grade_equivalent}</h3>
                                    <span class="small text-muted">Grade</span>
                                </div>
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Percentage</span>
                                    <span class="fw-medium">${e.percentage_label}</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar ${e.percentage >= 75 ? 'bg-success' : 'bg-warning'}" style="width: ${e.percentage}%"></div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-end mt-auto">
                                <div class="d-flex flex-column">
                                    <span class="small text-muted mb-1">Submitted by:</span>
                                    <span class="small fw-medium">${e.submitted_by_role.toUpperCase()}</span>
                                    <small class="text-muted" style="font-size: 0.7em">${e.time_ago}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3 view-btn" data-index="${index}">
                                    <i class="bi bi-eye me-1"></i> View Details
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    $(document).on("click", ".view-btn", function() {
        const idx = $(this).data("index");
        const e = evaluationsData[idx];

        $("#detailTitle").text(e.eval_label);
        $("#detailStudentName").text(e.full_name + " • " + e.student_number);

        let criteriaHtml = '';
        e.criteria.forEach(c => {
            let stars = '';
            for (let i = 1; i <= 5; i++) {
                stars += `<i class="bi bi-star-fill ${i <= c.score ? 'text-warning' : 'text-secondary opacity-25'}"></i>`;
            }
            criteriaHtml += `
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="small fw-medium">${c.label}</span>
                    <div class="d-flex align-items-center gap-2">
                        <div class="star-rating d-flex gap-1" style="font-size: 1.1rem;">${stars}</div>
                        <span class="badge bg-light text-dark border ms-2" style="width: 40px">${c.score} / 5</span>
                    </div>
                </div>
            `;
        });

        let summaryHtml = `
            <div class="row g-4 mb-4">
                <div class="col-6">
                    <div class="p-3 bg-body-tertiary rounded-3 text-center h-100">
                        <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Total Score</span>
                        <h2 class="mb-0 fw-bold text-primary">${e.total_score}</h2>
                    </div>
                </div>
                <div class="col-6">
                    <div class="p-3 bg-body-tertiary rounded-3 text-center h-100">
                        <span class="d-block text-muted small text-uppercase fw-semibold mb-1">Equivalent Grade</span>
                        <h2 class="mb-0 fw-bold text-success">${e.grade_equivalent}</h2>
                    </div>
                </div>
            </div>

            <h6 class="fw-bold text-muted text-uppercase small mb-3">Criteria Ratings</h6>
            <div class="p-3 border rounded-3 mb-4 bg-body-tertiary bg-opacity-50">
                ${criteriaHtml}
            </div>
        `;

        if (e.eval_type === 'self' && e.would_recommend !== null) {
            summaryHtml += `
                <div class="mb-4">
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">Would Recommend Company?</h6>
                    <span class="badge ${e.would_recommend ? 'bg-success' : 'bg-danger'} rounded-pill px-3 py-2 fs-6">
                        ${e.would_recommend ? '<i class="bi bi-hand-thumbs-up me-1"></i> Yes' : '<i class="bi bi-hand-thumbs-down me-1"></i> No'}
                    </span>
                </div>
            `;
        }

        if (e.comments) {
            summaryHtml += `
                <div>
                    <h6 class="fw-bold text-muted text-uppercase small mb-2">Comments & Feedback</h6>
                    <div class="p-3 bg-info-subtle text-info-emphasis rounded-3">
                        <p class="mb-0 small fst-italic">"${e.comments}"</p>
                    </div>
                </div>
            `;
        }

        $("#detailContent").html(summaryHtml);
        $detailsModal.show();
    });
});
