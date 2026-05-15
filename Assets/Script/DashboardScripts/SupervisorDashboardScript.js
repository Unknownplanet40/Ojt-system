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

if (!csrfToken || !userUUID || !userRole || userRole !== "supervisor") {
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
  });

  $("#signOutBtn").on("click", function () {
    SignOut();
  });

  $("#dashboardRefreshBtn").on("click", function () {
    const btn = $(this);
    btn.addClass("bi-spin");
    fetchDashboardData().always(() => {
      setTimeout(() => btn.removeClass("bi-spin"), 500);
    });
  });
}

function fetchProfile() {
  return $.ajax({
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
          const initials = profile.initials || "SV";
          $("#navProfilePhoto").attr("src", `https:
          $("#dropdownProfilePhoto").attr("src", `https:
        } else {
          $("#navProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
          $("#dropdownProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
        }

        $("#userName").text(profile.first_name + " " + profile.last_name);
        $("#welcomeUserName").text(profile.first_name);
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function fetchDashboardData() {
  return $.ajax({
    url: "../../../process/supervisor/get_dashboard",
    method: "GET",
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const data = response.data;
        $("#totalStudentsCount").text(data.stats.total_students);
        $("#activeOjtCount").text(data.stats.active_ojt);
        $("#pendingDtrCount").text(data.stats.pending_dtr);
        $("#pendingEvalsCount").text(data.stats.pending_evaluations);

        renderStudentsList(data.recent_students);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function renderStudentsList(students) {
  const list = $("#recentStudentsList");
  if (students.length === 0) {
    list.html(`
      <div class="text-center py-5">
        <p class="text-muted mb-0">No active supervised students found.</p>
      </div>
    `);
    return;
  }

  const items = students.map((student) => {
    const progress = Math.min((student.total_hours / 600) * 100, 100);
    const initials = student.first_name.charAt(0) + student.last_name.charAt(0);
    const avatar = student.profile_name 
      ? `<img src="../../../Assets/Images/profiles/${student.profile_name}" class="rounded-circle object-fit-cover shadow-sm" style="width: 48px; height: 48px; min-width: 48px;">`
      : `<div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 48px; height: 48px; min-width: 48px;">${initials}</div>`;
    
    return `
      <div class="p-3 rounded-4 border bg-body-tertiary transition-all hover-shadow-sm">
        <div class="row align-items-center g-3">
          <div class="col-auto">
            ${avatar}
          </div>
          <div class="col">
            <div class="fw-bold text-body mb-0">${student.full_name}</div>
            <small class="text-muted d-block">${student.student_number} • ${student.program}</small>
          </div>
          <div class="col-md-3">
            <div class="d-flex align-items-center justify-content-between mb-1">
              <small class="text-muted small fw-bold">Progress</small>
              <small class="text-success small fw-bold">${student.total_hours} / 600 hrs</small>
            </div>
            <div class="progress" style="height: 6px;">
              <div class="progress-bar bg-success rounded-pill" role="progressbar" style="width: ${progress}%"></div>
            </div>
          </div>
          <div class="col-auto">
            <a href="./DTR.php?student=${student.uuid}" class="btn btn-sm btn-light rounded-pill border px-3">
              Review DTR
            </a>
          </div>
        </div>
      </div>
    `;
  });


  list.html(items.join(""));
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

$(document).ready(function () {
  if (!letPageLoad) return;
  DashboardEsentialElements();
  fetchProfile();
  fetchDashboardData();
});

