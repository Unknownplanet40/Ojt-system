import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
let letPageLoad = true;

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userUUID = $('meta[name="user-UUID"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";
const Onlypage = $("body").data("only") || "";

if (!csrfToken || !userUUID || !userRole || userRole !== "coordinator") {
  window.location.href = "../../../Src/Pages/Login";
  letPageLoad = false;
}

function DashboardEsentialElements(mainContentSelector = "#PageMainContent") {
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
    $(mainContentSelector).fadeIn(1000, function () {
      $(this).removeClass("d-none");
    });
  });

  $("#signOutBtn").on("click", function () {
    SignOut();
  });
}

function fetchProfile() {
  $.ajax({
    url: "../../../process/profile/get_profile",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    success: function (response) {
      if (response.status === "success") {
        const profile = response.profile;
        if (!profile.profile_name) {
          const initials = profile.initials || "NA";
          $("#navProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
          $("#dropdownProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
        } else {
          $("#navProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
          $("#dropdownProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
        }

        $("#userName").text(profile.first_name + " " + profile.last_name);
        $("#welcomeUserName").text(profile.first_name);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },

    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function SignOut() {
  $.ajax({
    url: "../../../process/auth/logout",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      ModalVersion(swalTheme, "Signing Out", "Please wait while we sign you out...", "info", 0, "center");
    },
    success: function (response) {
      if (response.status === "success") {
        localStorage.removeItem("ojt_theme_mode");
        Swal.close();
        window.location.href = response.redirect_url;
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function fetchDashboardData() {
  return $.ajax({
    url: "../../../process/coordinator/get_dashboard",
    method: "GET",
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const data = response.data;
        
        // Update Stats
        $("#TotalUsersCounts").text(data.stats.total_students);
        $("#TotalUsersStatus").text("Assigned students");
        
        $("#activeOjtCounts").text(data.stats.active_ojt);
        $("#activeOjtStatus").text("Currently deployed");
        
        $("#pendingApprovalsCounts").text(data.stats.pending_approvals);
        $("#pendingApprovalsStatus").text("Awaiting review");
        
        $("#avgHoursRendered").text(data.stats.avg_hours);
        $("#avgHoursRenderedStatus").text("Avg. per student");

        // Render Actions
        renderActions(data.actions);
        
        // Render Recent Students
        renderRecentStudents(data.recent_students);
        
        // Render Progress
        renderHoursProgress(data.progress);
        
        // Render Companies
        renderPartnerCompanies(data.companies);
        
        // Render Visits
        renderUpcomingVisits(data.visits);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function renderActions(actions) {
  const list = $("#needActionList");
  if (actions.length === 0) {
    list.html('<p class="text-muted text-center mt-5">All caught up! No actions needed.</p>');
    return;
  }

  const items = actions.map(action => `
    <li class="list-group-item bg-transparent border-0 px-0">
      <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3 p-3 rounded-3 border bg-body-tertiary shadow-sm transition-all hover-shadow-md">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-${action.type}-subtle text-${action.type} flex-shrink-0" style="width: 40px; height: 40px;">
          <i class="bi bi-${action.type === 'warning' ? 'exclamation-triangle' : 'info-circle'}-fill fs-6"></i>
        </div>
        <div class="flex-grow-1 min-w-0">
          <div class="fw-semibold text-break">${action.title}</div>
          <small class="text-muted d-block mt-1">${action.description}</small>
        </div>
        <a href="${action.link}" class="btn btn-sm btn-outline-${action.type === 'warning' ? 'warning' : 'success'} text-nowrap align-self-stretch align-self-sm-center">
          View details
        </a>
      </div>
    </li>
  `);
  list.html(items.join(""));
}

function renderRecentStudents(students) {
  const list = $("#myStudentsList");
  if (students.length === 0) {
    list.html('<p class="text-muted text-center mt-5">No students assigned yet.</p>');
    return;
  }

  const items = students.map(student => {
    const initials = student.first_name.charAt(0) + student.last_name.charAt(0);
    const avatar = student.profile_name 
      ? `<img src="../../../Assets/Images/profiles/${student.profile_name}" class="rounded-circle object-fit-cover shadow-sm" style="width: 40px; height: 40px; min-width: 40px;">`
      : `<div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; min-width: 40px;">${initials}</div>`;
      
    return `
      <li class="list-group-item bg-transparent border-0 px-0 pb-3">
        <div class="d-flex align-items-center gap-3">
          ${avatar}
          <div class="min-w-0">
            <div class="fw-bold text-truncate">${student.full_name}</div>
            <small class="text-muted d-block">${student.student_number} • ${student.program}</small>
          </div>
          <a href="./viewStudentProfile.php?uuid=${student.uuid}" class="ms-auto btn btn-sm btn-light border rounded-pill px-3">View</a>
        </div>
      </li>
    `;
  });
  list.html(items.join(""));
}


function renderHoursProgress(progress) {
  const list = $("#hoursProgressList");
  if (progress.length === 0) {
    list.html('<p class="text-muted text-center mt-5">No progress data available.</p>');
    return;
  }

  const items = progress.map(p => {
    const percent = Math.min((p.rendered / 600) * 100, 100);
    return `
      <div class="mb-3">
        <div class="d-flex justify-content-between mb-1">
          <small class="fw-bold text-truncate">${p.full_name}</small>
          <small class="text-muted">${p.rendered} / 600h</small>
        </div>
        <div class="progress" style="height: 6px;">
          <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: ${percent}%"></div>
        </div>
      </div>
    `;
  });
  list.html(items.join(""));
}

function renderPartnerCompanies(companies) {
  const list = $("#partnerCompaniesList");
  if (companies.length === 0) {
    list.html('<p class="text-muted text-center mt-5">No active partner companies.</p>');
    return;
  }

  const items = companies.map(c => `
    <li class="list-group-item bg-transparent border-0 px-0 pb-2">
      <div class="hstack gap-3">
        <div class="bg-secondary bg-opacity-10 p-2 rounded-3 text-secondary">
          <i class="bi bi-building fs-5"></i>
        </div>
        <div class="min-w-0">
          <div class="fw-bold text-truncate">${c.name}</div>
          <small class="text-muted">${c.student_count} Students deployed</small>
        </div>
      </div>
    </li>
  `);
  list.html(items.join(""));
}

function renderUpcomingVisits(visits) {
  const list = $("#upcomingVisitsList");
  const empty = $("#noVisitsScheduled");
  
  if (visits.length === 0) {
    list.addClass("d-none");
    empty.removeClass("d-none");
    return;
  }

  list.removeClass("d-none");
  empty.addClass("d-none");

  const items = visits.map(v => `
    <li class="list-group-item bg-transparent border-0 px-0 pb-3">
      <div class="p-3 rounded-3 border bg-body-tertiary">
        <div class="hstack mb-2">
          <span class="badge bg-info-subtle text-info-emphasis rounded-pill">${v.formatted_date}</span>
          <small class="ms-auto text-muted small">${v.visit_type === 'scheduled' ? 'Scheduled' : 'Unscheduled'}</small>
        </div>
        <div class="fw-bold text-truncate mb-1">${v.company_name}</div>
        <small class="text-muted d-block mb-2 text-truncate">${v.purpose || 'No purpose specified'}</small>
        <div class="hstack gap-2 mt-2">
          <a href="./Visits.php?uuid=${v.uuid}&action=manage" class="btn btn-sm btn-light border w-100 py-1 rounded-pill">Manage</a>
          <a href="./Visits.php?uuid=${v.uuid}&action=complete" class="btn btn-sm btn-outline-success w-100 py-1 rounded-pill">Complete</a>
        </div>
      </div>
    </li>
  `);
  list.html(items.join(""));
}


$(document).ready(function () {
  if (!letPageLoad) return;
  DashboardEsentialElements();
  fetchProfile();
  fetchDashboardData();

  if (Onlypage === "CoordinatorDashboard") {
    $("#dashboardRefreshBtn").on("click", function () {
      const btn = $(this);
      btn.addClass("bi-spin");
      fetchDashboardData().always(() => {
        setTimeout(() => btn.removeClass("bi-spin"), 500);
      });
    });

    $("#scheduleVisitBtn, #upcomingVisitsBtn").on("click", function () {
      window.location.href = "./Visits.php";
    });
    
    $("#myStudentsBtn").on("click", function () {
      window.location.href = "./MyStudents.php";
    });

    $("#partnerCompaniesBtn").on("click", function () {
      window.location.href = "./Companies.php";
    });
  }
});

