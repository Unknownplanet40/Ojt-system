import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

$("#pageLoader").fadeIn(2000);

const ActivityIcons = {
  other: "bi-activity",
  profile_completed: "bi-person-check",
  account_created: "bi-person-plus",
  account_deactivated: "bi-person-x",
  account_activated: "bi-person-check",
  password_changed: "bi-key",
  password_reset: "bi-key-fill",
  role_changed: "bi-shield-lock",
  login_success: "bi-box-arrow-in-right",
  login_failed: "bi-box-arrow-in-right text-danger",
  logout: "bi-box-arrow-right",
  application_submitted: "bi-file-earmark-text",
  application_approved: "bi-file-earmark-check",
  application_rejected: "bi-file-earmark-x",
  endorsement_issued: "bi-award",
  dtr_submitted: "bi-journal-text",
  dtr_approved: "bi-journal-check",
  dtr_rejected: "bi-journal-x",
  journal_submitted: "bi-journal-text",
  evaluation_submitted: "bi-clipboard-check",
  document_uploaded: "bi-cloud-upload",
  company_added: "bi-building",
  company_updated: "bi-building-up",
  moa_uploaded: "bi-file-earmark-arrow-up",
  batch_created: "bi-diagram-3",
  batch_closed: "bi-diagram-3-fill",
  program_created: "bi-collection",
  program_updated: "bi-collection-fill",
  program_disabled: "bi-collection-play",
  program_enabled: "bi-collection-play-fill",
};

export function fetchUserData() {
  $.ajax({
    url: "../../../Assets/api/GET_userData",
    method: "GET",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        $("body").attr("data-uuid", response.data.uuid);
        $("#userName").text(response.data.first_name + " " + response.data.middle_name.charAt(0) + ". " + response.data.last_name);
        $("#welcomeUserName").text(response.data.first_name);
        $("#dropdownMenuName").text(response.data.first_name + " " + response.data.last_name);
        switch (response.data.role) {
          case "admin":
            $("#userRole").text("Administrator");
            break;
          case "supervisor":
            $("#userRole").text("Supervisor");
            break;
          case "student":
            $("#userRole").text("Student");
            break;
          case "coordinator":
            $("#userRole").text("Coordinator");
            break;
          default:
            $("#userRole").text("User");
        }
        if (response.data.profile_path) {
          $("#navProfilePhoto").attr("src", "../../../" + response.data.profile_path);
          $("#dropdownProfilePhoto").attr("src", "../../../" + response.data.profile_path);
        } else {
          $("#navProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
          $("#dropdownProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
        }
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching user data. Please try again.", "error", 3000, "top-end", "8");  
      }
    },
  });
}

export function signOut() {
  $("#signOutBtn").on("click", function (e) {
    $.ajax({
      url: "../../../Assets/api/logout",
      method: "POST",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          window.location.href = "../../../";
        } else {
          ToastVersion(swalTheme, "Failed to sign out. Please try again.", "error", 3000, "top-end", "8");
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
        } else {
          ToastVersion(swalTheme, "An error occurred while signing out. Please try again.", "error", 3000, "top-end", "8");
        }
      },
    });
  });
}

export function DashboardEsentialElements(userUuid) {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  $("#navbarSideCollapse").on("click", function () {
    $(".offcanvas-collapse").toggleClass("open");
    if ($("#navbarSideCollapse i").hasClass("bi-list")) {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-list").addClass("bi-x").fadeIn(200);
      });
    } else {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-x").addClass("bi-list").fadeIn(200);
      });
    }
  });

  $(window).on("resize", function () {
    if ($(".offcanvas-collapse").hasClass("open")) {
      $(".offcanvas-collapse").removeClass("open");
      $("#navbarSideCollapse i").removeClass("bi-x").addClass("bi-list");
    }
    if ($(window).width() < 360) {
      window.resizeTo(360, 800);
    }
  });

  $(function () {
    $(".nav-link").on("click", function () {
      if (!$(this).hasClass("dropdown-toggle")) {
        $(".nav-link").removeClass("active");
        $(this).addClass("active");
      }
    });
  });

  $("#pageLoader").fadeOut(1000, function () {
    $(this).remove();
    $("#mainContent").fadeIn(1000, function () {
      $(this).removeClass("d-none");
    });
  });

/*   if (!userUuid) {
    window.location.href = "../../../Src/Pages/Login";
    return;
  } */
}

function Row1(data) {
  $("#TotalUsersCounts").text(data.total_students);
  $("#TotalUsersStatus")
    .text(data.total_students > 0 ? "This Semester" : "No students yet")
    .removeClass("text-success text-danger")
    .addClass(data.total_students > 0 ? "text-success" : "text-danger");
  $("#activeOjtCounts").text(data.active_ojt);
  $("#activeOjtStatus")
    .text(data.not_started > 0 ? "OJT in progress" : "No active OJT")
    .removeClass("text-success text-danger")
    .addClass(data.active_ojt > 0 ? "text-success" : "text-danger");
  $("#pendingApprovalsCounts").text(data.pending_approvals);
  $("#pendingApprovalsStatus")
    .text(data.pending_approvals > 0 ? "Pending approvals" : "No pending approvals")
    .removeClass("text-success text-danger")
    .addClass(data.pending_approvals > 0 ? "text-danger" : "text-success");
  $("#avgHoursRendered").text(data.avg_hours);
  $("#avgHoursRenderedStatus")
    .text(data.avg_hours > 0 ? "Average hours rendered" : "No hours rendered yet")
    .removeClass("text-success text-danger")
    .addClass(data.avg_hours > 0 ? "text-success" : "text-danger");
}

function Row2(data1, data2) {
  const needActionList = $("#needActionList");
  const myStudentsList = $("#myStudentsList");
  needActionList.empty();
  myStudentsList.empty();

  if (data1.needs_action.length === 0) {
    needActionList.append(`
      <li class="list-group-item bg-transparent border-0 px-0">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 p-3 rounded-3 border bg-body-tertiary">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success flex-shrink-0"
               style="width: 40px; height: 40px;">
            <i class="bi bi-check-circle-fill fs-6"></i>
          </div>

          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold text-break">All caught up! No pending items.</div>
            <small class="text-muted d-block mt-1">No action needed</small>
          </div>
        </div>
      </li>
    `);
  } else {
    data1.needs_action.forEach((item) => {
      const icons = {
        applications: "bi-file-earmark-text",
        dtr: "bi-journal-text",
        requirements: "bi-clipboard-check",
        journals: "bi-journal-text",
        students: "bi-person-fill",
      };

      const toneMap = {
        danger: { bubble: "bg-danger-subtle text-danger", btn: "bg-danger-subtle text-danger" },
        warning: { bubble: "bg-warning-subtle text-warning", btn: "bg-warning-subtle text-warning" },
        info: { bubble: "bg-info-subtle text-info", btn: "bg-info-subtle text-info" },
        success: { bubble: "bg-success-subtle text-success", btn: "bg-success-subtle text-success" },
        primary: { bubble: "bg-primary-subtle text-primary", btn: "bg-primary-subtle text-primary" },
      };

      const tone = toneMap[item.type] || toneMap.info;
      const icon = icons[item.module] || "bi-activity";
      const link = item.link || "#";

      const listItem = $(`
        <li class="list-group-item bg-transparent border-0 px-0 py-1">
          <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 p-3 rounded-3 border bg-blur-5 bg-semi-transparent">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle ${tone.bubble} flex-shrink-0"
                 style="width: 40px; height: 40px;">
              <i class="bi ${icon} fs-6"></i>
            </div>

            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold text-break">${item.message}</div>
              <small class="text-muted d-block mt-1">Action needed</small>
            </div>

            <a href="${link}" class="btn btn-sm ${tone.btn} text-nowrap align-self-stretch align-self-sm-center">
              View <i class="bi bi-arrow-right ms-1"></i>
            </a>
          </div>
        </li>
      `);

      needActionList.append(listItem);
    });
  }

  if (data2.my_students.length === 0) {
    myStudentsList.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-1">
        <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 p-3 rounded-3 border bg-body-tertiary">
          <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-secondary-subtle text-secondary flex-shrink-0"
               style="width: 42px; height: 42px;">
            <i class="bi bi-people-fill"></i>
          </div>

          <div class="flex-grow-1 min-w-0">
            <div class="fw-semibold">No students assigned yet.</div>
            <small class="text-muted d-block">Waiting for student assignment</small>
          </div>
        </div>
      </li>
    `);
  } else {
    const statusConfig = {
      not_started: {
        label: "Not Started",
        cls: "bg-secondary-subtle text-secondary-emphasis",
      },
      ojt_active: {
        label: "OJT Active",
        cls: "bg-primary-subtle text-primary-emphasis",
      },
      never_logged_in: {
        label: "Never Logged In",
        cls: "bg-danger-subtle text-danger-emphasis",
      },
      unknown: {
        label: "Unknown Status",
        cls: "bg-secondary-subtle text-secondary-emphasis",
      },
    };

    data2.my_students.forEach((student) => {
      const cfg = statusConfig[student.ojt_status] || statusConfig.unknown;
      const program = student.program_code || "N/A";
      const hours = Number(student.ojt_hours_rendered) || 0;

      const listItem = $(`
        <li class="list-group-item bg-transparent border-0 px-0 py-1">
          <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 p-3 rounded-3 border bg-blur-5 bg-semi-transparent">
            <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary flex-shrink-0"
                 style="width: 42px; height: 42px;">
              <i class="bi bi-person-fill"></i>
            </div>

            <div class="flex-grow-1 min-w-0">
              <div class="fw-semibold text-truncate">${student.full_name}</div>
              <small class="text-muted d-block text-wrap">${program} &bull; ${hours} hrs rendered</small>
            </div>

            <span class="badge rounded-pill ${cfg.cls} ms-sm-auto">
              ${cfg.label}
            </span>
          </div>
        </li>
      `);

      myStudentsList.append(listItem);
    });
  }
}

function Row3(data1, data2, data3) {
  const hoursProgressList = $("#hoursProgressList");
  hoursProgressList.empty();

  if (data1.hours_progress.length === 0) {
    hoursProgressList.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-3">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-secondary-subtle text-secondary fw-semibold d-flex align-items-center justify-content-center"
               style="width: 40px; height: 40px; flex: 0 0 40px;">
            <i class="bi bi-graph-up-arrow"></i>
          </div>
          <div class="flex-grow-1">
            <div class="fw-semibold">No OJT hours progress to show.</div>
            <small class="text-muted">Student intern</small>
          </div>
        </div>
      </li>
    `);
  } else {
    const colorSets = [
      { avatarBg: "bg-success-subtle", avatarText: "text-success", bar: "bg-success" },
      { avatarBg: "bg-primary-subtle", avatarText: "text-primary", bar: "bg-primary" },
      { avatarBg: "bg-warning-subtle", avatarText: "text-warning", bar: "bg-warning" },
      { avatarBg: "bg-info-subtle", avatarText: "text-info", bar: "bg-info" },
    ];

    data1.hours_progress.forEach((item, index) => {
      const set = colorSets[index % colorSets.length];
      const fullName = item.name || "Unknown Student";
      const initials = fullName
        .split(" ")
        .filter(Boolean)
        .slice(0, 2)
        .map((n) => n[0].toUpperCase())
        .join("");

      const hoursRendered = Number(item.hours_rendered) || 0;
      const requiredHours = Number(item.required_hours) || 0;
      const percentage =
        requiredHours > 0
          ? Math.min(Math.max((hoursRendered / requiredHours) * 100, 0), 100)
          : Number(item.percentage) || 0;

      const listItem = $(`
        <li class="list-group-item bg-transparent border-0 px-0 py-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-circle ${set.avatarBg} ${set.avatarText} fw-semibold d-flex align-items-center justify-content-center"
                 style="width: 40px; height: 40px; flex: 0 0 40px;">
              ${initials}
            </div>
            <div class="flex-grow-1">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <div>
                  <div class="fw-semibold">${fullName}</div>
                  <small class="text-muted">Student intern</small>
                </div>
                <span class="badge text-bg-light border">${hoursRendered} / ${requiredHours} hrs</span>
              </div>
              <div class="progress" style="height: 7px;">
                <div class="progress-bar ${set.bar}" role="progressbar" style="width: ${percentage}%;"
                     aria-valuenow="${Math.round(percentage)}" aria-valuemin="0" aria-valuemax="100"></div>
              </div>
            </div>
          </div>
        </li>
      `);

      hoursProgressList.append(listItem);
    });
  }

  const partnerCompaniesList = $("#partnerCompaniesList");
  partnerCompaniesList.empty();

  if (data2.companies.length === 0) {
    partnerCompaniesList.append(`
        <li class="list-group-item bg-transparent border-0 px-0 py-2">
          <div class="d-flex align-items-start gap-3 p-3 rounded-3 border bg-body-tertiary shadow-sm">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-secondary-subtle text-secondary"
                style="width: 40px; height: 40px;">
              <i class="bi bi-building fs-6"></i>
            </div>

            <div class="flex-grow-1">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <h6 class="mb-0 fw-semibold">No partner companies yet.</h6>
              </div>
            </div>
          </div>
        </li>
      `);
  } else {
    data2.companies.forEach((company) => {
      const filledSlots = Number(company.filled_slots) || 0;
      const totalSlots = Number(company.total_slots) || 0;
      const progressPercent = totalSlots > 0 ? Math.min((filledSlots / totalSlots) * 100, 100) : 0;
      const moaBadge = company.moa_warning
        ? '<span class="badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle">MOA expiring soon</span>'
        : "";
      const workSetupBadge = company.work_setup
        ? `<span class="badge text-bg-light border">${company.work_setup}</span>`
        : "";

      const listItem = $(`
        <li class="list-group-item bg-transparent border-0 px-0 py-2">
          <div class="d-flex align-items-start gap-3 p-3 rounded-3 border bg-blur-5 bg-semi-transparent shadow-sm">
            <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="min-width: 40px; min-height: 40px;">
              <i class="bi bi-building fs-6"></i>
            </div>

            <div class="flex-grow-1">
              <div class="d-flex flex-wrap align-items-center gap-2">
                <h6 class="mb-0 fw-semibold">${company.name}</h6>
              </div>
              <small class="text-muted d-block mt-1">Slots filled: <strong>${filledSlots}</strong> / ${totalSlots}</small>
              <div class="progress my-2" role="progressbar" aria-label="Slots filled progress" aria-valuenow="${filledSlots}"
                  aria-valuemin="0" aria-valuemax="${totalSlots}" style="height: 6px;">
                <div class="progress-bar bg-success" style="width: ${progressPercent}%;"></div>
              </div>
              <div class="hstack gap-2">
                ${workSetupBadge}
                ${moaBadge}
              </div>
            </div>
          </div>
        </li>
      `);
      partnerCompaniesList.append(listItem);
    });
  }

  const upcomingVisitsList = $("#upcomingVisitsList");
  upcomingVisitsList.empty();

  if (data3.upcoming_visits.length === 0) {
    $("#noVisitsScheduled").removeClass("d-none");
    upcomingVisitsList.addClass("d-none");
  } else {
    // temporary until the schedule visit button is functional
    $("#noVisitsScheduled").addClass("d-none");
    upcomingVisitsList.removeClass("d-none");
    data3.upcoming_visits.forEach((visit) => {
      const listItem = $(`
                <li class="list-group-item bg-transparent">
                    <div class="hstack">
                        <i class="bi bi-calendar-event text-info me-2 fs-6"></i>
                        <div class="vstack">
                            <div>${visit.company_name} Visit</div>
                            <small class="text-muted" style="font-size: 0.7em">${visit.date} &dot; ${visit.student_count} students</small>
                        </div>
                        <small class="badge bg-primary-subtle text-primary-emphasis ms-auto">${visit.time}</small>
                    </div>
                </li>
            `);
      upcomingVisitsList.append(listItem);
    });
  }
}

function dashboardData() {
  $.ajax({
    url: "../../../Assets/api/coordinator_dashboard_queries",
    method: "POST",
    data: { action: "fetch_dashboard_data" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const stats = response.data.stats;
        const needsAction = response.data.needs_action;
        const myStudents = response.data.my_students;
        const hoursProgress = response.data.hours_progress;
        const companies = response.data.companies;
        const upcomingVisits = response.data.upcoming_visits;
        myStudents.forEach((student) => {
          const progress = hoursProgress.find((h) => h.name === student.full_name);
          student.ojt_hours_rendered = progress ? Number(progress.hours_rendered) || 0 : 0;
        });
        Row1(stats);
        Row2({ needs_action: needsAction }, { my_students: myStudents });
        Row3({ hours_progress: hoursProgress }, { companies: companies }, { upcoming_visits: upcomingVisits });
        $("#currentSemester").text(response.data.active_batch ? response.data.active_batch.label : "No active batch");
        $("#AYshoolyear").text(response.data.active_batch ? response.data.active_batch.label : "No active batch");
        $("#applicationsCountDisplay").text(response.data.stats.total_students);
      } else {
        ToastVersion(swalTheme, "Failed to fetch dashboard data. Please try again.", "error", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching dashboard data. Please try again.", "error", 3000, "top-end", "8");
      }
    },
  });
}

$(document).ready(function () {
  fetchUserData();
  DashboardEsentialElements($("body").data("uuid"));
  signOut();
  dashboardData();

  $("#dashboardRefreshBtn").on("click", function () {
    fetchUserData();
    dashboardData();
    $("#dashboardContent").stop(true, true).fadeTo(500, 0.5).fadeTo(500, 1);
  });
});
