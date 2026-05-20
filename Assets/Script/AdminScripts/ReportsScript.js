import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

let activityChart, placementChart, programChart, curvesChart, progCompletionChart, ratingDistChart;

function initCharts() {
    const ctxActivity = document.getElementById('activityChart').getContext('2d');
    const ctxPlacement = document.getElementById('placementChart').getContext('2d');
    const ctxProgram = document.getElementById('programChart').getContext('2d');
    const ctxCurves = document.getElementById('curvesChart').getContext('2d');
    const ctxProgCompletion = document.getElementById('progCompletionChart').getContext('2d');
    const ctxRatingDist = document.getElementById('ratingDistChart').getContext('2d');

    const isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    const textColor = isDark ? '#adb5bd' : '#495057';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.05)' : 'rgba(0, 0, 0, 0.05)';

    Chart.defaults.color = textColor;
    Chart.defaults.font.family = "'Inter', sans-serif";

    activityChart = new Chart(ctxActivity, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'System Activity',
                data: [],
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#0d6efd'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: gridColor }, beginAtZero: true },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    placementChart = new Chart(ctxPlacement, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Pending', 'Completed'],
            datasets: [{
                data: [0, 0, 0],
                backgroundColor: ['#198754', '#0dcaf0', '#6c757d'],
                borderWidth: 0,
                hoverOffset: 10
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            },
            cutout: '70%'
        }
    });

    programChart = new Chart(ctxProgram, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Students',
                data: [],
                backgroundColor: '#BA7517',
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { grid: { color: gridColor }, beginAtZero: true },
                y: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });

    curvesChart = new Chart(ctxCurves, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Enrolled Students',
                    data: [],
                    borderColor: '#0dcaf0',
                    backgroundColor: 'rgba(13, 202, 240, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#0dcaf0'
                },
                {
                    label: 'Completed Students',
                    data: [],
                    borderColor: '#198754',
                    backgroundColor: 'rgba(25, 135, 84, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#198754'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: gridColor }, beginAtZero: true },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: true, position: 'top' }
            }
        }
    });

    progCompletionChart = new Chart(ctxProgCompletion, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Completion Rate (%)',
                data: [],
                backgroundColor: '#198754',
                borderRadius: 8
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                x: { 
                    grid: { color: gridColor }, 
                    beginAtZero: true,
                    max: 100,
                    ticks: { callback: function(value) { return value + '%'; } }
                },
                y: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': ' + context.raw + '%';
                        }
                    }
                }
            }
        }
    });

    ratingDistChart = new Chart(ctxRatingDist, {
        type: 'bar',
        data: {
            labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
            datasets: [{
                label: 'Number of Evaluations',
                data: [0, 0, 0, 0, 0],
                backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#0dcaf0', '#198754'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { grid: { color: gridColor }, beginAtZero: true, ticks: { stepSize: 1 } },
                x: { grid: { display: false } }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
}

function fetchReportsData() {
    $.ajax({
        url: "../../../process/admin/get_reports_data",
        type: "POST",
        data: { csrf_token: csrfToken },
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
                const d = response.data;

                
                $("#statTotalPlacements").text(d.placement.total);
                $("#statActivePartners").text(d.companies.length);
                $("#statTotalHours").text(Math.round(d.progress.rendered).toLocaleString());

                const completionRate = d.progress.required > 0 ? (d.progress.rendered / d.progress.required) * 100 : 0;
                $("#statCompletionRate").text(completionRate.toFixed(1) + "%");
                $("#completionBar").css("width", completionRate + "%");

                
                activityChart.data.labels = d.activity.map(a => a.month_label);
                activityChart.data.datasets[0].data = d.activity.map(a => a.count);
                activityChart.update();

                
                placementChart.data.datasets[0].data = [
                    d.placement.active,
                    d.placement.pending,
                    d.placement.completed
                ];
                placementChart.update();

                
                programChart.data.labels = d.programs.map(p => p.code);
                programChart.data.datasets[0].data = d.programs.map(p => p.count);
                programChart.update();

                // Update Curves
                curvesChart.data.labels = d.curves.map(c => c.month);
                curvesChart.data.datasets[0].data = d.curves.map(c => c.enrolled);
                curvesChart.data.datasets[1].data = d.curves.map(c => c.completed);
                curvesChart.update();

                // Update Program Completions
                progCompletionChart.data.labels = d.program_completions.map(pc => pc.code);
                progCompletionChart.data.datasets[0].data = d.program_completions.map(pc => pc.rate);
                progCompletionChart.update();

                // Update Ratings
                ratingDistChart.data.datasets[0].data = [
                    d.ratings[1] || 0,
                    d.ratings[2] || 0,
                    d.ratings[3] || 0,
                    d.ratings[4] || 0,
                    d.ratings[5] || 0
                ];
                ratingDistChart.update();

                
                const companyContainer = $("#topCompaniesContainer");
                companyContainer.empty();
                
                if (d.companies.length > 0) {
                    d.companies.forEach(company => {
                        const rating = parseFloat(company.rating) || 0;
                        const fullStars = Math.floor(rating);
                        const hasHalfStar = rating % 1 >= 0.5;
                        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);

                        let starHtml = '';
                        for(let i=0; i<fullStars; i++) starHtml += '<i class="bi bi-star-fill text-warning"></i>';
                        if(hasHalfStar) starHtml += '<i class="bi bi-star-half text-warning"></i>';
                        for(let i=0; i<emptyStars; i++) starHtml += '<i class="bi bi-star text-body-secondary opacity-50"></i>';

                        companyContainer.append(`
                            <div class="col-12 col-md-6 col-xl-4">
                                <div class="card h-100 glass-ui border-body border-opacity-10 rounded-4 transition-all hover-translate-y shadow-sm">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="avatar avatar-md bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                                                <i class="bi bi-building fs-4"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <h6 class="fw-bold text-break mb-0">${company.name}</h6>
                                                <small class="text-body-secondary text-truncate d-block">${company.industry}</small>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="small text-body-secondary">Active Interns</span>
                                            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">${company.student_count}</span>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="small text-body-secondary">Performance</span>
                                            <div class="small">
                                                ${starHtml}
                                                <span class="ms-1 fw-bold">${rating > 0 ? rating.toFixed(1) : 'N/A'}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `);
                    });
                } else {
                    companyContainer.append('<div class="col-12 text-center py-5 text-body-secondary"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-25"></i>No active company data available for this batch.</div>');
                }

            } else {
                Errors(response.message);
            }
        },
        error: function () {
            Errors("An error occurred while fetching reports.");
        },
        complete: function () {
            $("#pageLoader").fadeOut();
        }
    });
}

$(document).ready(function () {
    initCharts();
    fetchReportsData();

    $("#refreshReports").on("click", function () {
        $("#pageLoader").fadeIn();
        fetchReportsData();
        ToastVersion(swalTheme, "Reports updated", "success", 2000, "top-end");
    });

    $("#exportPDF").on("click", function () {
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');
        
        
        const form = $('<form>', {
            action: "../../../process/admin/export_reports_pdf",
            method: "POST"
        }).append($('<input>', {
            type: "hidden",
            name: "csrf_token",
            value: csrfToken
        }));
        
        $('body').append(form);
        form.submit();
        form.remove();

        
        setTimeout(() => {
            btn.prop('disabled', false).html(originalHtml);
            ToastVersion(swalTheme, "Report generated successfully.", "success", 3000, "top-end");
        }, 3000);
    });
});
