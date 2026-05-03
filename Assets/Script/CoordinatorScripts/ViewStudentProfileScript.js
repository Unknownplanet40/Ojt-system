import { ToastVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const studentUuid = $("body").data("student-uuid");

function loadStudentDetails() {
  $.ajax({
    url: "../../../process/coordinators/get_student_full_details",
    type: "POST",
    data: {
      csrf_token: csrfToken,
      student_uuid: studentUuid,
    },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        renderStudentInfo(response.student, response.stats);
        renderRequirements(response.requirements);
        renderDtr(response.recentDtr);
        renderJournals(response.journals);
      } else {
        Errors(response.message);
        setTimeout(() => {
          window.location.href = "./MyStudents";
        }, 2000);
      }
    },
    error: function () {
      Errors("Failed to fetch student details.");
    },
    complete: function () {
      $("#pageLoader").fadeOut();
    },
  });
}

function renderStudentInfo(s, stats) {
  $("#studentName").text(s.full_name);
  $("#studentNumber").text(s.student_number);
  $("#studentProgram").text(s.program_code);
  $("#studentYearSection").text(`${s.year_label} - ${s.section}`);
  $("#studentBatch").text(s.batch_label);
  $("#studentEmail").text(s.email);
  $("#studentMobile").text(s.mobile || "N/A");
  $("#studentAddress").text(s.home_address || "N/A");

  if (s.profile_name) {
    $("#studentPhoto").attr("src", `../../../Assets/Images/profiles/${s.profile_name}`);
  } else {
    $("#studentPhoto").attr(
      "src",
      `https://placehold.co/128x128/C1C1C1/000000/png?text=${s.initials}&font=poppins`
    );
  }

  const statusBadge = $("#studentStatusBadge");
  statusBadge
    .text(s.status_label)
    .removeClass("bg-success bg-secondary bg-danger bg-warning text-dark");
  if (s.account_status === "active") statusBadge.addClass("bg-success");
  else if (s.account_status === "never_logged_in")
    statusBadge.addClass("bg-warning text-dark");
  else statusBadge.addClass("bg-secondary");

  // Stats
  $("#hoursRendered").text(stats.totalHours);
  $("#hoursGoal").text(`of ${s.required_hours || 486} hrs`);
  $("#reqsCompleted").text(stats.reqsCompleted);
  $("#journalsSubmitted").text(stats.totalJournals);

  // Placement
  $("#companyName").text(s.company_name || "Not Assigned Yet");
  $("#companyAddress").text(s.company_address || "---");
  if (s.supervisor_first_name) {
    $("#supervisorName").text(`${s.supervisor_first_name} ${s.supervisor_last_name}`);
    $("#supervisorContact").text(`${s.supervisor_email} | ${s.supervisor_mobile}`);
  } else {
    $("#supervisorName").text("---");
    $("#supervisorContact").text("---");
  }
}

function renderRequirements(reqs) {
  const list = $("#requirementsList");
  list.empty();

  if (reqs.length === 0) {
    list.append(
      '<div class="p-3 text-center text-muted">No requirements found.</div>'
    );
    return;
  }

  reqs.forEach((r) => {
    let statusClass = "text-muted";
    let iconClass = "bi-circle";

    if (r.status === "approved") {
      statusClass = "text-success";
      iconClass = "bi-check-circle-fill";
    } else if (r.status === "submitted" || r.status === "under_review") {
      statusClass = "text-warning";
      iconClass = "bi-clock-history";
    } else if (r.status === "returned") {
      statusClass = "text-danger";
      iconClass = "bi-exclamation-circle-fill";
    }

    const item = `
            <div class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi ${iconClass} ${statusClass} fs-5"></i>
                    <div class="vstack">
                        <span class="fw-medium">${formatReqName(r.req_type)}</span>
                        <small class="text-muted">${r.status.replace("_", " ")}</small>
                    </div>
                </div>
                ${
                  r.file_path
                    ? `<a href="../../../file_serve.php?type=requirement&req_uuid=${r.uuid}" target="_blank" class="btn btn-sm btn-outline-light rounded-pill"><i class="bi bi-eye me-1"></i>View File</a>`
                    : ""
                }
            </div>
        `;
    list.append(item);
  });
}

function formatReqName(type) {
  return type
    .split("_")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function renderDtr(dtr) {
  const list = $("#recentDtrList");
  list.empty();

  if (dtr.length === 0) {
    list.append(
      '<div class="p-3 text-center text-muted">No DTR records found.</div>'
    );
    return;
  }

  dtr.forEach((entry) => {
    let statusClass = "bg-secondary";
    if (entry.status === "approved") statusClass = "bg-success";
    else if (entry.status === "rejected") statusClass = "bg-danger";

    const d = new Date(entry.entry_date);
    const month = d.toLocaleDateString("en-US", { month: "short" });
    const day = d.toLocaleDateString("en-US", { day: "2-digit" });

    const item = `
            <div class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center justify-content-between border-light border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                    <div class="text-center bg-white bg-opacity-10 rounded-3 p-1 d-flex flex-column justify-content-center" style="width: 50px; height: 50px; border: 1px solid rgba(255,255,255,0.1);">
                        <div class="text-uppercase fw-bold text-primary" style="font-size: 0.6rem;">${month}</div>
                        <div class="fw-bold fs-5" style="line-height: 1;">${day}</div>
                    </div>
                    <div class="vstack">
                        <span class="fw-medium text-light">${formatTime(entry.time_in)} — ${formatTime(entry.time_out)}</span>
                        <small class="text-muted">${entry.hours_rendered} hours rendered</small>
                    </div>
                </div>
                <div class="text-end">
                    <span class="badge ${statusClass} rounded-pill px-3 fw-normal">${entry.status.charAt(0).toUpperCase() + entry.status.slice(1)}</span>
                </div>
            </div>
        `;
    list.append(item);
  });
}

function renderJournals(journals) {
  const list = $("#journalsList");
  list.empty();

  if (journals.length === 0) {
    list.append(
      '<div class="p-3 text-center text-muted">No journals submitted yet.</div>'
    );
    return;
  }

  journals.forEach((j) => {
    const item = `
            <div class="list-group-item bg-transparent px-0 py-3 d-flex align-items-center justify-content-between">
                <div class="vstack">
                    <span class="fw-medium">Week ${j.week_number}</span>
                    <small class="text-muted">${formatDate(j.week_start)} - ${formatDate(j.week_end)}</small>
                </div>
                <span class="badge bg-info-subtle text-info rounded-pill">${j.status}</span>
            </div>
        `;
    list.append(item);
  });
}

function formatDate(dateStr) {
  if (!dateStr) return "---";
  const date = new Date(dateStr);
  return date.toLocaleDateString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
  });
}

function formatTime(timeStr) {
  if (!timeStr) return "---";
  const [hours, minutes] = timeStr.split(":");
  let hh = parseInt(hours);
  const ampm = hh >= 12 ? "PM" : "AM";
  hh = hh % 12 || 12;
  return `${hh}:${minutes} ${ampm}`;
}

$(document).ready(function () {
  loadStudentDetails();
});
