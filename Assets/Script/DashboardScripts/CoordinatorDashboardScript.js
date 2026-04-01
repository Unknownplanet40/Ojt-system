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

/* {
    "status": "success",
    "message": "Dashboard data fetched successfully.",
    "data": {
        "active_batch": {
            "uuid": "ba100000-0000-0000-0000-000000000001",
            "label": "AY 2025-2026 1st Semester",
            "required_hours": 486
        },
        "stats": {
            "total_students": 0,
            "active_ojt": 0,
            "not_started": 0,
            "pending_approvals": 0,
            "pending_dtr": 0,
            "pending_applications": 0,
            "pending_requirements": 0,
            "avg_hours": 0
        },
        "needs_action": [
            {
                "type": "danger",
                "message": "Pending OJT applications to review",
                "count": 0,
                "link": "\/coordinator\/applications?status=pending",
                "module": "applications"
            },
            {
                "type": "warning",
                "message": "DTR entries awaiting approval",
                "count": 0,
                "link": "\/coordinator\/dtr?status=pending",
                "module": "dtr"
            },
            {
                "type": "info",
                "message": "Pre-OJT requirements pending review",
                "count": 0,
                "link": "\/coordinator\/requirements?status=pending",
                "module": "requirements"
            },
            {
                "type": "warning",
                "message": "Weekly journals submitted \u2014 not yet reviewed",
                "count": 0,
                "link": "\/coordinator\/journals?status=submitted",
                "module": "journals"
            }
        ],
        "my_students": [],
        "hours_progress": [],
        "companies": [
            {
                "uuid": "co100000-0000-0000-0000-000000000001",
                "name": "Accenture Philippines, Inc.",
                "work_setup": "hybrid",
                "total_slots": 10,
                "filled_slots": 0,
                "moa_days_left": 29,
                "moa_warning": true
            },
            {
                "uuid": "d2b556e8-17db-4768-9c33-f0090bad4901",
                "name": "DOST - Science Education Institute",
                "work_setup": "on-site",
                "total_slots": 15,
                "filled_slots": 0,
                "moa_days_left": 19,
                "moa_warning": true
            },
            {
                "uuid": "co200000-0000-0000-0000-000000000002",
                "name": "Globe Telecom, Inc.",
                "work_setup": "remote",
                "total_slots": 5,
                "filled_slots": 0,
                "moa_days_left": null,
                "moa_warning": false
            }
        ],
        "upcoming_visits": []
    }
} */

export function fetchUserData() {
  $.ajax({
    url: "../../../Assets/api/GET_userData",
    method: "GET",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
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
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching user data. Please try again.", "error", 3000);
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
          ToastVersion(swalTheme, "Failed to sign out. Please try again.", "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
        } else {
          ToastVersion(swalTheme, "An error occurred while signing out. Please try again.", "error", 3000);
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

  if (!userUuid) {
    window.location.href = "../../../Src/Pages/Login";
    return;
  }
}

function Row1(data) {
  $("#totalUsersCount").text(data.total_students);
  $("#TotalUsersStatus")
    .text(data.total_students > 0 ? "This Semester" : "No students yet")
    .removeClass("text-success text-danger")
    .addClass(data.total_students > 0 ? "text-success" : "text-danger");
  $("#activeOjtCount").text(data.active_ojt);
  $("#activeOjtStatus")
    .text(data.not_started > 0 ? "OJT in progress" : "No active OJT")
    .removeClass("text-success text-danger")
    .addClass(data.active_ojt > 0 ? "text-success" : "text-danger");
  $("#pendingApprovalsCount").text(data.pending_approvals);
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
        <li class="list-group-item bg-transparent">
          <div class="hstack">
            <i class="bi bi-check-circle-fill text-success me-2 fs-6"></i>
            <div class="vstack">
              <div>All caught up! No pending items.</div>
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
      };
      const listItem = $(`
            <li class="list-group-item bg-transparent">
                <div class="hstack">
                    <i class="bi ${icons[item.module]} text-${item.type} me-2 fs-6"></i>
                    <div class="vstack">
                        <div>${item.message}</div>
                        <small class="text-muted" style="font-size: 0.7em"><a href="${item.link}" class="text-decoration-none">View details</a></small>
                    </div>
                </div>
            </li>
        `);
      needActionList.append(listItem);
    });
  }
  if (data2.my_students.length === 0) {
    myStudentsList.append(`
        <li class="list-group-item bg-transparent">
          <div class="hstack">
            <i class="bi bi-people-fill text-secondary me-2 fs-6"></i>
            <div class="vstack">
              <div>No students assigned yet.</div>
            </div>
          </div>
        </li>
      `);
  } else {
    data2.my_students.forEach((student) => {
      const statusBadges = {
        approved: '<small class="badge bg-success-subtle text-success-emphasis ms-auto">OJT Approved</small>',
        pending: '<small class="badge bg-warning-subtle text-warning-emphasis ms-auto">OJT Pending</small>',
        not_started: '<small class="badge bg-secondary-subtle text-secondary-emphasis ms-auto">Not Started</small>',
      };
      const listItem = $(`
            <li class="list-group-item bg-transparent">
                <div class="hstack">
                    <i class="bi bi-person-fill text-primary me-2 fs-6"></i>
                    <div class="vstack">
                        <div>${student.name}</div>
                        <small class="text-muted" style="font-size: 0.7em">${student.program} &dot; ${student.hours_rendered} hrs rendered</small>
                    </div>
                    ${statusBadges[student.ojt_status] || ""}
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
        <li class="list-group-item bg-transparent">
          <div class="hstack">
            <i class="bi bi-graph-up-arrow text-secondary me-2 fs-6"></i>
            <div class="vstack">
              <div>No OJT hours progress to show.</div>
            </div>
          </div>
        </li>
      `);
  } else {
    data1.hours_progress.forEach((item) => {
      const progressPercent = item.required_hours > 0 ? (item.hours_rendered / item.required_hours) * 100 : 0;
      const listItem = $(`
            <li class="list-group-item bg-transparent">
                <div class="vstack">
                    <div class="d-flex justify-content-between">
                        <span>${item.student_name}</span>
                        <span>${item.hours_rendered}/${item.required_hours} hrs</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar" role="progressbar" style="width: ${progressPercent}%;"></div>
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
        <li class="list-group-item bg-transparent">
          <div class="hstack">
            <i class="bi bi-building text-secondary me-2 fs-6"></i>
            <div class="vstack">
              <div>No partner companies yet.</div>
            </div>
          </div>
        </li>
      `);
  } else {
    data2.companies.forEach((company) => {
      const moaBadge = company.moa_warning ? '<small class="badge bg-danger-subtle text-danger-emphasis ms-auto">MOA expiring soon</small>' : "";
      const listItem = $(`
            <li class="list-group-item bg-transparent">
                <div class="hstack">
                    <i class="bi bi-building text-primary me-2 fs-6"></i>
                    <div class="vstack">
                        <div>${company.name}</div>
                        <small class="text-muted" style="font-size: 0.7em">${company.filled_slots} / ${company.total_slots} slots &dot; ${company.work_setup}</small>
                    </div>
                    ${moaBadge}
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
        Row1(stats);
        Row2({ needs_action: needsAction }, { my_students: myStudents });
        Row3({ hours_progress: hoursProgress }, { companies: companies }, { upcoming_visits: upcomingVisits });
        $("#currentSemester").text(response.data.active_batch ? response.data.active_batch.label : "No active batch");
      } else {
        ToastVersion(swalTheme, "Failed to fetch dashboard data. Please try again.", "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching dashboard data. Please try again.", "error", 3000);
      }
    },
  });
}

$(document).ready(function () {
  const userUuid = $("body").data("uuid");

  DashboardEsentialElements(userUuid);
  fetchUserData();
  signOut();
  dashboardData();

  $("#dashboardRefreshBtn").on("click", function () {
    fetchUserData();
    dashboardData();
    ToastVersion(swalTheme, "Dashboard refreshed!", "success", 2000);
  });
});
