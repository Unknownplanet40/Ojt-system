$(document).ready(function () {
    const $container = $("#evaluationsContainer");
    const $selfSection = $("#selfEvalSection");
    const $selfContent = $("#selfEvalContent");

    loadEvaluations();

    function loadEvaluations() {
        $.ajax({
            url: "../../../Process/evaluation/get_evaluations",
            type: "POST",
            dataType: "json",
            data: { csrf_token: csrfToken },
            beforeSend: function () {
                $container.html(`
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="text-muted mt-2">Loading your evaluations...</p>
                    </div>
                `);
            },
            success: function (res) {
                if (res.status === "success") {
                    renderEvaluations(res.data);
                } else {
                    $container.html(`<div class="col-12"><div class="alert alert-danger border-0">${res.message}</div></div>`);
                }
            },
            error: function (xhr, status, error) {
                Errors(xhr, status, error);
                $container.html(`<div class="col-12"><div class="alert alert-danger border-0">Failed to load data.</div></div>`);
            }
        });
    }

    function renderStarRating(score) {
        let html = '<div class="star-rating readonly d-flex gap-1">';
        for (let i = 1; i <= 5; i++) {
            html += `<i class="bi bi-star-fill ${i <= score ? 'text-warning' : ''}"></i>`;
        }
        html += ` <span class="ms-2 fw-medium text-muted">${score} / 5</span></div>`;
        return html;
    }

    function renderEvaluationCard(evalData, type, unlockStatus) {
        if (!evalData) {
            // Not submitted yet
            return `
                <div class="col-12 col-md-6">
                    <div class="card h-100 border-0 shadow-sm bg-blur-5 bg-semi-transparent">
                        <div class="card-body p-4 text-center d-flex flex-column justify-content-center align-items-center">
                            <div class="avatar avatar-lg bg-secondary-subtle text-secondary rounded-circle mb-3 d-flex align-items-center justify-content-center" style="width:56px;height:56px;">
                                <i class="bi bi-clock fs-3"></i>
                            </div>
                            <h5 class="fw-bold">${type === 'midterm' ? 'Midterm' : 'Final'} Evaluation</h5>
                            <p class="text-muted small">Not yet submitted by your supervisor.</p>
                            ${unlockStatus && unlockStatus.unlocked 
                                ? `<span class="badge bg-info-subtle text-info-emphasis rounded-pill px-3">Ready for Supervisor</span>`
                                : `<span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill px-3" title="${unlockStatus ? unlockStatus.reason : ''}"><i class="bi bi-lock me-1"></i>Locked</span>`
                            }
                        </div>
                    </div>
                </div>
            `;
        }

        // Submitted
        let criteriaHtml = '';
        evalData.criteria.forEach(c => {
            criteriaHtml += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-medium text-body">${c.label}</span>
                    </div>
                    ${renderStarRating(c.score)}
                </div>
            `;
        });

        return `
            <div class="col-12 col-xl-6">
                <div class="card h-100 border-0 shadow-sm bg-blur-5 bg-semi-transparent">
                    <div class="card-header border-0 bg-transparent p-4 pb-0 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">${evalData.eval_label}</h5>
                        <span class="badge bg-success-subtle text-success-emphasis rounded-pill px-3 py-2"><i class="bi bi-check-circle me-1"></i>Submitted</span>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4 p-3 bg-body-tertiary rounded-3">
                            <div class="me-4 text-center">
                                <h2 class="mb-0 fw-bold text-primary">${evalData.total_score}</h2>
                                <span class="small text-muted">Total Score</span>
                            </div>
                            <div class="text-center border-start ps-4 border-2">
                                <h2 class="mb-0 fw-bold text-success">${evalData.percentage_label}</h2>
                                <span class="small text-muted">Percentage</span>
                            </div>
                        </div>
                        
                        <h6 class="fw-bold text-muted text-uppercase small mb-3">Criteria Ratings</h6>
                        ${criteriaHtml}

                        ${evalData.comments ? `
                            <div class="mt-4 p-3 bg-info-subtle text-info-emphasis rounded-3">
                                <h6 class="fw-bold small text-uppercase mb-2"><i class="bi bi-chat-quote me-1"></i>Supervisor Comments</h6>
                                <p class="mb-0 small fst-italic">"${evalData.comments}"</p>
                            </div>
                        ` : ''}
                    </div>
                    <div class="card-footer border-0 bg-transparent px-4 pb-4 pt-0">
                        <small class="text-muted"><i class="bi bi-clock me-1"></i>Submitted: ${evalData.submitted_at} (${evalData.time_ago})</small>
                    </div>
                </div>
            </div>
        `;
    }

    function renderEvaluations(data) {
        $container.empty();
        
        const midterm = renderEvaluationCard(data.evaluations.midterm, 'midterm', data.unlock_status.midterm);
        const final = renderEvaluationCard(data.evaluations.final, 'final', data.unlock_status.final);

        $container.append(midterm);
        $container.append(final);

        // Handle Self Evaluation
        renderSelfEvaluation(data.evaluations.self, data.unlock_status.self);
    }

    function renderSelfEvaluation(selfEval, unlockStatus) {
        $selfSection.show();

        if (selfEval) {
            // Already submitted
            let criteriaHtml = '';
            selfEval.criteria.forEach(c => {
                criteriaHtml += `
                    <div class="col-12 col-md-6 mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small fw-medium text-body">${c.label}</span>
                        </div>
                        ${renderStarRating(c.score)}
                    </div>
                `;
            });

            $selfContent.html(`
                <div class="alert alert-success border-0 bg-success-subtle text-success-emphasis d-flex align-items-center mb-4">
                    <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold">Self-Evaluation Completed</h6>
                        <p class="mb-0 small">You have successfully submitted your self-evaluation.</p>
                    </div>
                </div>

                <div class="row">
                    ${criteriaHtml}
                </div>

                <div class="mt-4 mb-3">
                    <h6 class="fw-bold small text-uppercase text-muted">Would Recommend OJT Company?</h6>
                    <span class="badge ${selfEval.would_recommend ? 'bg-success' : 'bg-danger'} rounded-pill px-3 py-2 mt-1 fs-6">
                        ${selfEval.would_recommend ? '<i class="bi bi-hand-thumbs-up me-1"></i> Yes' : '<i class="bi bi-hand-thumbs-down me-1"></i> No'}
                    </span>
                </div>

                ${selfEval.comments ? `
                    <div class="mt-4 p-3 bg-body-tertiary rounded-3">
                        <h6 class="fw-bold small text-uppercase mb-2 text-muted">Your Comments</h6>
                        <p class="mb-0 small">${selfEval.comments}</p>
                    </div>
                ` : ''}
            `);
        } else if (unlockStatus.unlocked) {
            // Show Form
            let formHtml = `
                <div class="alert alert-info border-0 bg-info-subtle text-info-emphasis d-flex align-items-center mb-4">
                    <i class="bi bi-info-circle me-3 fs-4"></i>
                    <div>
                        <h6 class="alert-heading mb-1 fw-bold">Reflect on your OJT Experience</h6>
                        <p class="mb-0 small">Please honestly rate your growth and experience. This will help us improve the program.</p>
                    </div>
                </div>
                <form id="selfEvalForm">
                    <div class="row g-4 mb-4">
            `;

            for (const [key, label] of Object.entries(selfCriteria)) {
                formHtml += `
                    <div class="col-12 col-md-6">
                        <label class="form-label fw-medium d-flex justify-content-between">
                            <span>${label}</span>
                            <span class="badge bg-secondary rounded-pill" id="badge-${key}">0 / 5</span>
                        </label>
                        <div class="star-rating interactive d-flex gap-2" data-input="${key}">
                            <i class="bi bi-star-fill" data-val="1"></i>
                            <i class="bi bi-star-fill" data-val="2"></i>
                            <i class="bi bi-star-fill" data-val="3"></i>
                            <i class="bi bi-star-fill" data-val="4"></i>
                            <i class="bi bi-star-fill" data-val="5"></i>
                        </div>
                        <input type="hidden" name="${key}" id="${key}" required>
                    </div>
                `;
            }

            formHtml += `
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Would you recommend this company to future OJT students?</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="wouldRecommend" name="would_recommend" value="1">
                            <label class="form-check-label" for="wouldRecommend">Yes, I recommend them.</label>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-medium">Additional Comments & Feedback (Optional)</label>
                        <textarea class="form-control bg-body-tertiary" name="comments" rows="3" placeholder="Share any specific thoughts..."></textarea>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-primary px-5" id="submitSelfEvalBtn">Submit Self-Evaluation</button>
                    </div>
                </form>
            `;

            $selfContent.html(formHtml);

            // Bind interactions
            $(".star-rating.interactive i").on("click", function() {
                const val = $(this).data("val");
                const container = $(this).parent();
                const inputId = container.data("input");
                
                container.find("i").removeClass("text-warning");
                container.find("i").each(function() {
                    if ($(this).data("val") <= val) {
                        $(this).addClass("text-warning");
                    }
                });
        
                $("#" + inputId).val(val);
                $("#badge-" + inputId).text(val + " / 5").removeClass("bg-secondary bg-danger").addClass("bg-primary");
            });

            $("#submitSelfEvalBtn").on("click", submitSelfEvaluation);

        } else {
            // Locked
            $selfContent.html(`
                <div class="text-center py-5">
                    <div class="avatar avatar-xl bg-secondary-subtle text-secondary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="bi bi-lock fs-2"></i>
                    </div>
                    <h5 class="fw-bold">Locked</h5>
                    <p class="text-muted mb-0">${unlockStatus.reason}</p>
                </div>
            `);
        }
    }

    function submitSelfEvaluation() {
        const $btn = $(this);
        const data = $("#selfEvalForm").serialize() + "&csrf_token=" + encodeURIComponent(csrfToken);

        // Validation
        let valid = true;
        $(".star-rating.interactive").each(function() {
            const inputId = $(this).data("input");
            if (!$("#" + inputId).val()) {
                valid = false;
                $("#badge-" + inputId).removeClass("bg-secondary bg-primary").addClass("bg-danger");
            }
        });

        if (!valid) {
            Swal.fire("Missing Ratings", "Please provide a rating for all criteria.", "warning");
            return;
        }

        $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Submitting...');

        $.ajax({
            url: "../../../Process/evaluation/submit_evaluation",
            type: "POST",
            data: data,
            dataType: "json",
            success: function (res) {
                if (res.status === "success") {
                    Swal.fire("Success", res.message, "success");
                    loadEvaluations();
                } else {
                    Swal.fire("Error", res.message, "error");
                    $btn.prop("disabled", false).text('Submit Self-Evaluation');
                }
            },
            error: function (xhr, status, error) {
                Errors(xhr, status, error);
                $btn.prop("disabled", false).text('Submit Self-Evaluation');
            }
        });
    }
});
