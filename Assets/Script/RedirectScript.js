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

$(document).ready(function () {
  checkServer();

  $(window).on("resize", function () {
    if ($(window).width() < 360) {
      window.resizeTo(360, 800);
      $("#windowWidth").text($(window).width());
    }
  });
});
