import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

function CardtoShow(cardId) {
  const cards = ["SendResetLinkCard", "EmailSentCard", "ResetPasswordCard", "ExpiredLinkCard", "SuccessCard"];
  cards.forEach((id) => {
    if (id === cardId) {
      $(`#${id}`).removeClass("d-none");
    } else {
      $(`#${id}`).addClass("d-none");
    }
  });

  switch (cardId) {
    case "SendResetLinkCard":
      BGcircleTheme(true, "primary", "slow");
      break;
    case "EmailSentCard":
      BGcircleTheme(true, "success", "slow");
      break;
    case "ResetPasswordCard":
      BGcircleTheme(true, "warning", "fast");
      break;
    case "ExpiredLinkCard":
      BGcircleTheme(true, "danger", "fast");
      break;
    case "SuccessCard":
      BGcircleTheme(true, "success", "fast");
      break;
    default:
      BGcircleTheme(true);
  }
}

function handleAjaxError(xhr, textStatus, customMessage = null) {
  let errorMessage = customMessage || "An error occurred. Please try again.";

  if (textStatus === "timeout") {
    errorMessage = "Request timed out! Please check your connection and try again.";
  } else if (xhr && xhr.status) {
    errorMessage = `Error ${xhr.status}: ${xhr.statusText}`;
  }

  ToastVersion(swalTheme, errorMessage, "error", 3000, "top-end");
}

const urlParams = new URLSearchParams(window.location.search);
const token = urlParams.get("token");

$(document).ready(function () {
  const updateValidationStatus = (elementId, isValid) => {
    $(`#${elementId}`)
      .removeClass(isValid ? "text-secondary" : "text-success")
      .addClass(isValid ? "text-success" : "text-secondary");
  };

  $("#newPassword, #confirmPassword")
    .on("input focus change", function () {
      $(this).attr("type", "text");
      const newPassword = $("#newPassword").val();
      const confirmPassword = $("#confirmPassword").val();

      if (newPassword.length !== 0 && confirmPassword.length !== 0) {
        updateValidationStatus("matchCheck", newPassword === confirmPassword);
        updateValidationStatus("charCheck", newPassword.length >= 8);
        updateValidationStatus("upperCheck", /[A-Z]/.test(newPassword));
        updateValidationStatus("numberCheck", /\d/.test(newPassword));
        updateValidationStatus("specialCheck", /[!@#$%^&*(),.?":{}|<>]/.test(newPassword));
      } else {
        updateValidationStatus("matchCheck", false);
        updateValidationStatus("charCheck", false);
        updateValidationStatus("upperCheck", false);
        updateValidationStatus("numberCheck", false);
        updateValidationStatus("specialCheck", false);
      }

      if (newPassword.length >= 8 && /[A-Z]/.test(newPassword) && /\d/.test(newPassword) && /[!@#$%^&*(),.?":{}|<>]/.test(newPassword) && newPassword === confirmPassword) {
        $("#ResetPasswordBtn").prop("disabled", false);
      }
    })
    .on("blur", function () {
      if ($(this).val() !== "") {
        $(this).attr("type", "password");
      }
    });

  if (token) {
    $.ajax({
      url: "../../Assets/api/validateResetToken",
      method: "POST",
      timeout: 5000,
      data: {
        token: token,
      },
      success: function (response) {
        if (response.status === "success") {
          CardtoShow("ResetPasswordCard");
          const expiresAt = new Date(response.expires_at);
          const countdownElement = $("#countdown");
          const updateCountdown = () => {
            const now = new Date();
            const timeRemaining = expiresAt - now;
            if (timeRemaining > 0) {
              const minutes = Math.floor((timeRemaining % (1000 * 60 * 60)) / (1000 * 60));
              const seconds = Math.floor((timeRemaining % (1000 * 60)) / 1000);
              if (minutes === 0 && seconds <= 60) {
                countdownElement.text(`${seconds} seconds.`);
              } else {
                countdownElement.text(`${minutes} minute(s) and ${seconds} second(s).`);
              }
            } else {
              countdownElement.text("0 seconds.");
              CardtoShow("ExpiredLinkCard");
            }
          };
          updateCountdown();
          const countdownInterval = setInterval(updateCountdown, 1000);
          const checkExpired = setInterval(() => {
            if (new Date() >= expiresAt) {
              clearInterval(countdownInterval);
              clearInterval(checkExpired);
            }
          }, 1000);
        } else {
          CardtoShow("ExpiredLinkCard");
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus, "An error occurred while validating the reset token. Please try again.");
      },
    });
  } else {
    CardtoShow("SendResetLinkCard");
  }

  $("#SendLinkBtn").click(function () {
    const email = $("#email").val().trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (email.length === 0) {
      ToastVersion(swalTheme, "Please enter your email address!", "info", 3000, "top-end");
      return;
    }

    if (!emailRegex.test(email)) {
      ToastVersion(swalTheme, "Please enter a valid email address!", "info", 3000, "top-end");
      return;
    }

    $.ajax({
      url: "../../Assets/api/sendResetLinkProcess",
      method: "POST",
      timeout: 10000,
      data: {
        email: email,
      },
      success: function (response) {
        if (response.status === "success") {
          $("#emailDisplay").text(email);
          CardtoShow("EmailSentCard");
        } else if (response.status === "info") {
          ToastVersion(swalTheme, response.message, response.status, 3000, "top-end");
        } else {
          handleAjaxError(null, null, response.message || "Failed to send reset link! Please try again later.");
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus, "An error occurred while sending the reset link. Please try again.");
      },
      statusCode: {
        404: function () {
          handleAjaxError(null, null, "Reset link endpoint not found! Please contact support.");
        },
        500: function () {
          handleAjaxError(null, null, "Server error! Please try again later.");
        },
        403: function () {
          window.location.href = "../../Src/Pages/";
        },
      },
    });
  });

  $("#RequestNewLinkBtn").click(function () {
    const newUrl = window.location.origin + window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
    CardtoShow("SendResetLinkCard");
  });

  $("#GoToLoginBtn").click(function () {
    window.location.href = "Login.php";
  });

  $("#ResetPasswordBtn").click(function () {
    const newPassword = $("#newPassword").val();

    if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/\d/.test(newPassword) || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
      ToastVersion(swalTheme, "Password does not meet the required criteria!", "info", 3000, "top-end");
      return;
    }
  });
});
