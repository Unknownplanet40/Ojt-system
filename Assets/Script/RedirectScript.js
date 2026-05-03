import { ToastVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "primary", "normal");

let retryCount = 0;

function checkServer() {
  $("#version1").removeClass("d-none");
  $("#version2").addClass("d-none");
  $.ajax({
    url: "./config/serverStatus",
    method: "GET",
    dataType: "json",
    timeout: 5000,
  })
    .done(function (response, jqXHR) {
      if (response.status === "success" || response.status === "info") {
        $("#status").text(response.message);
        if (response.status === "success") {
          BGcircleTheme(true, "success", "slow");
          $("#dot").text("We are redirecting you please wait...").removeClass("d-none");
          $("#version1").fadeOut(1000, function () {
            $(this).addClass("d-none");
          });
          window.location.href = "./Src/Pages/Login.php";
        } else {
          BGcircleTheme(true, "info", "normal");
          ToastVersion(swalTheme, response.message, response.status === "success" ? "success" : "info", response.status === "success" ? 3000 : 5000, "top-end");
        }
      } else if (response.status === "critical") {
        window.location.href = "./Src/Pages/ErrorPage.php?error=CE00";
      } else {
        jqXHR.status = 500;
        jqXHR.responseJSON = response;
        jqXHR.fail();
      }
    })
    .fail(function (xhr, textStatus) {
      let errorMessage = "An error occurred while checking server status.";
      let isDatabaseExistError = false;
      if (textStatus === "timeout") {
        errorMessage = "Request timed out. Please check your network connection.";
      } else if (xhr.status === 0) {
        errorMessage = "Network error. Please check your internet connection.";
      } else if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMessage = xhr.responseJSON.message;
        isDatabaseExistError = xhr.responseJSON.isDatabaseExistError || false;
      }

      if (isDatabaseExistError) {
        // goto setup page if database does not exist
        $("#status").text("Database not found. Redirecting to setup page...");
        $("#dot").addClass("d-none");
        ToastVersion(swalTheme, "Database not found. Redirecting to setup page...", "warning");
        BGcircleTheme(true, "warning", "fast");
      } else {
        $("#status").text(errorMessage);
        $("#dot").addClass("d-none");
        ToastVersion(swalTheme, errorMessage, "error");
        BGcircleTheme(true, "danger", "fast");
        setTimeout(function () {
          $("#status").text("Please wait a moment while we try to fix the issue...");
        }, 5000);
        setTimeout(function () {
          if (retryCount < 3) {
            retryCount++;
            checkServer();
          } else {
            $("#status").text("It seems we are having trouble connecting to the server. Please try again later.");
          }
        }, 10000);
      }
    });
}

// detecting host environment
function detectHostingEnvironment() {
  const hostname = window.location.hostname;

  if (hostname === 'localhost' || hostname === '127.0.0.1') {
    return 'local';
  }

  if (hostname.endsWith('.ngrok.io') || hostname.endsWith('.ngrok-free.app')) {
    return 'ngrok';
  }

  if (hostname.endsWith('.vercel.app')) {
    return 'vercel';
  }

  if (hostname === 'github.io' || hostname.endsWith('.github.io')) {
    return 'github';
  }

  return 'production';
}

$(document).ready(function () {

  const hostingEnvironment = detectHostingEnvironment();
  if (hostingEnvironment === 'github') {
    $("#status").text("It seems you are running this application on GitHub Pages, which does not support server-side functionality. Please run this application on a local server or a hosting service that supports PHP.");
    $("#dot").addClass("d-none");
    $("#version1").removeClass("d-none");
    BGcircleTheme(true, "warning", "fast");
  } else if (hostingEnvironment === 'ngrok' || hostingEnvironment === 'vercel') {
    $("#status").text("It seems you are running this application on a hosting service that may have restrictions on server-side functionality. Please ensure that your hosting service supports PHP and that the server is properly configured.");
    $("#dot").addClass("d-none");
    $("#version1").removeClass("d-none");
    BGcircleTheme(true, "warning", "fast");
    setTimeout(function () {
      checkServer();
    }, 10000);
  } else if (hostingEnvironment === 'local') {
    window.location.href = "./Src/Pages/Login";
  } else {
    checkServer();
  }

  $(window).on("resize", function () {
    if ($(window).width() < 360) {
      window.resizeTo(360, 800);
      $("#windowWidth").text($(window).width());
    }
  });
});
