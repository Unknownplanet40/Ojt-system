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
          $("#DashboardProfilePhoto").attr("src", "../../../" + response.data.profile_path);
        } else {
          $("#navProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
          $("#dropdownProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
          $("#DashboardProfilePhoto").attr("src", "https://placehold.co/80x80?text=No+Photo");
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

  if (!userUuid) {
    window.location.href = "../../../Src/Pages/Login";
    return;
  }
}


$(document).ready(function () {
  fetchUserData();
  DashboardEsentialElements($("body").data("uuid"));
  signOut();

  $("#dashboardRefreshBtn").on("click", function () {
    $("#dashboardContent").stop(true, true).fadeTo(500, 0.5).fadeTo(500, 1);
  });
});
