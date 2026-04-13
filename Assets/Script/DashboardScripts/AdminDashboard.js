import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userUUID = $('meta[name="user-UUID"]').attr("content") || "";
const Onlypage = $("body").data("only") || "";

function fetchProfile() {

  if (!csrfToken || !userUUID) {
    window.location.href = "../../../Src/Pages/Login.php";
    return;
  }

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

        if (profile.user_uuid !== userUUID) {
          ToastVersion(swalTheme, "Profile data mismatch. Please refresh the page.", "error", 3000, "top-end");
          SignOut();
          return;
        }

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

function DashboardEsentialElements() {
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

  $("#signOutBtn").on("click", function (e) {
    e.preventDefault();
    SignOut();
  });
}

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
    $("#PageMainContent").fadeIn(500);
  });
  
  console.log("User UUID:", userUUID);
  console.log("CSRF Token:", csrfToken);

  fetchProfile();
  DashboardEsentialElements();

  if (Onlypage === "AdminDashboard") {
  }
});
