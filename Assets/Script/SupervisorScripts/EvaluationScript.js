$(document).ready(function () {
    const $container = $("#studentsContainer");
    const $evalModal = new bootstrap.Modal(document.getElementById("evaluationModal"));

    loadStudents();

    function loadStudents() {
        $.ajax({
            url: "../../../Process/evaluation/get_evaluations",
            type: "POST",
            dataType: "json",
            data: { csrf_token: csrfToken },
            beforeSend: function () {
                $container.html(`
                    <div class="col-12 text-center py-5">
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                        <p class="text-muted mt-2">Loading students...</p>
                    </div>
                `);
            },
            success: function (res) {
                if (res.status === "success") {
                    renderStudents(res.students);
                } else {
                    $container.html(`<div class="col-12"><div class="alert alert-danger">${res.message}</div></div>`);
                }
            },
            error: function (xhr, status, error) {
                Errors(xhr, status, error);
                $container.html(`<div class="col-12"><div class="alert alert-danger">Failed to load data.</div></div>`);
            }
        });
    }

    function renderStudents(students) {
        $container.empty();
        
        if (!students || students.length === 0) {
            $container.html(`
                <div class="col-12 text-center py-5">
                    <div class="avatar avatar-xl bg-secondary-subtle text-secondary rounded-circle mb-3 d-inline-flex align-items-center justify-content-center" style="width:64px;height:64px;">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <h5 class="fw-bold">No Students Assigned</h5>
                    <p class="text-muted">You do not have any students assigned to you for evaluation.</p>
                </div>
            `);
            return;
        }

        students.forEach(s => {
            const midtermBtn = s.midterm_done 
                ? `<button class="btn btn-sm btn-success w-100 mb-2 disabled"><i class="bi bi-check-circle me-1"></i>Midterm Done</button>`
                : (s.midterm_unlocked 
                    ? `<button class="btn btn-sm btn-primary w-100 mb-2 evaluate-btn" data-uuid="${s.student_uuid}" data-name="${s.full_name}" data-type="midterm">Evaluate Midterm</button>`
                    : `<button class="btn btn-sm btn-secondary w-100 mb-2 disabled" title="Requires 50% hours">Midterm Locked</button>`);

            const finalBtn = s.final_done 
                ? `<button class="btn btn-sm btn-success w-100 disabled"><i class="bi bi-check-circle me-1"></i>Final Done</button>`
                : (s.final_unlocked 
                    ? `<button class="btn btn-sm btn-primary w-100 evaluate-btn" data-uuid="${s.student_uuid}" data-name="${s.full_name}" data-type="final">Evaluate Final</button>`
                    : `<button class="btn btn-sm btn-secondary w-100 disabled" title="Requires 100% hours">Final Locked</button>`);

            $container.append(`
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card h-100 border-0 shadow-sm bg-blur-5 bg-semi-transparent">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3" style="width: 48px; height: 48px;">
                                    ${s.full_name.charAt(0)}
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">${s.full_name}</h6>
                                    <small class="text-muted">${s.student_number}</small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="text-muted">Progress</span>
                                    <span class="fw-medium">${s.approved_hours} / ${s.required_hours} hrs</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar ${s.percentage >= 100 ? 'bg-success' : 'bg-primary'}" role="progressbar" style="width: ${s.percentage}%;" aria-valuenow="${s.percentage}" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>

                            <div class="d-flex flex-column">
                                ${midtermBtn}
                                ${finalBtn}
                            </div>
                        </div>
                    </div>
                </div>
            `);
        });
    }

    // Star Rating Logic
    $(".star-rating i").on("click", function() {
        const val = $(this).data("val");
        const container = $(this).parent();
        const inputId = container.data("input");
        
        // Update stars visually
        container.find("i").removeClass("text-warning");
        container.find("i").each(function() {
            if ($(this).data("val") <= val) {
                $(this).addClass("text-warning");
            }
        });

        // Set hidden input value
        $("#" + inputId).val(val);
        $("#badge-" + inputId).text(val + " / 5").removeClass("bg-secondary bg-danger").addClass("bg-primary");
    });

    // Reset stars helper
    function resetStars() {
        $(".star-rating i").removeClass("text-warning");
        $(".star-rating").each(function() {
            const inputId = $(this).data("input");
            $("#" + inputId).val("");
            $("#badge-" + inputId).text("0 / 5").removeClass("bg-primary bg-danger").addClass("bg-secondary");
        });
    }

    // Open Modal
    $(document).on("click", ".evaluate-btn", function () {
        const studentUuid = $(this).data("uuid");
        const studentName = $(this).data("name");
        const evalType = $(this).data("type");

        $("#evaluationForm")[0].reset();
        resetStars();

        $("#evalStudentUuid").val(studentUuid);
        $("#evalType").val(evalType);
        $("#evalStudentName").text(studentName);
        $("#evalModalTitle").text(evalType === 'midterm' ? "Midterm Evaluation" : "Final Evaluation");

        $evalModal.show();
    });

    // Submit Evaluation
    $("#submitEvalBtn").on("click", function () {
        const $btn = $(this);
        const data = $("#evaluationForm").serialize() + "&csrf_token=" + encodeURIComponent(csrfToken);

        // Validation
        let valid = true;
        $(".star-rating").each(function() {
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
                    $evalModal.hide();
                    Swal.fire("Success", res.message, "success");
                    loadStudents();
                } else {
                    Swal.fire("Error", res.message, "error");
                }
            },
            error: function (xhr, status, error) {
                Errors(xhr, status, error);
            },
            complete: function () {
                $btn.prop("disabled", false).text('Submit Evaluation');
            }
        });
    });
});
