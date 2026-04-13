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
      url: "../../process/auth/validate_reset_token",
      method: "POST",
      timeout: 5000,
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
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

  $("#ResendLinkBtn").click(function () {
    const email = $("#emailDisplay").text().trim();
    $("#email").val(email);
    $("#SendLinkBtn").trigger("click");
    $(this).prop("disabled", true).text("Resending...");
  });

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
      url: "../../process/auth/send_reset_link",
      method: "POST",
      timeout: 10000,
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      data: {
        email: email,
      },
      beforeSend: function () {
        $("#SendLinkBtn").prop("disabled", true).text("Please wait...");
      },
      success: function (response) {
        if (response.status === "success") {
          $("#SendLinkBtn").prop("disabled", false).text("Send Reset Link");
          $("#emailDisplay").text(email);
          CardtoShow("EmailSentCard");
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
          $("#ResendLinkBtn").prop("disabled", false).text("Resend Link");
        } else if (response.status === "info") {
          ToastVersion(swalTheme, response.message, response.status, 3000, "top-end");
        } else {
          handleAjaxError(null, null, response.message || "Failed to send reset link! Please try again later.");
          $("#SendLinkBtn").prop("disabled", false).text("Send Reset Link");
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus, "An error occurred while sending the reset link. Please try again.");
        $("#SendLinkBtn").prop("disabled", false).text("Send Reset Link");
      },
      statusCode: {
        404: function () {
          handleAjaxError(null, null, "Reset link endpoint not found! Please contact support.");
        },
        405: function () {
          handleAjaxError(null, null, "Method not allowed! Please contact support.");
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

  $("#ResetPasswordBtn").click(function (e) {
    e.preventDefault();

    const newPassword = $("#newPassword").val();
    const confirmPassword = $("#confirmPassword").val();

    if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/\d/.test(newPassword) || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword)) {
      ToastVersion(swalTheme, "Password does not meet the required criteria!", "info", 3000, "top-end");
      return;
    }

    if (newPassword !== confirmPassword) {
      ToastVersion(swalTheme, "Passwords do not match!", "info", 3000, "top-end");
      return;
    }

    $(this).prop("disabled", true);

    $.ajax({
      url: "../../process/auth/reset_password",
      method: "POST",
      timeout: 10000,
      dataType: "json",
      headers: {
        "X-Requested-With": "XMLHttpRequest",
      },
      data: {
        token: token,
        new_password: newPassword,
        confirm_password: confirmPassword,
      },
      success: function (response) {
        if (response.status === "success") {
          CardtoShow("SuccessCard");
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
          setTimeout(() => {
            window.location.href = response.redirect_url || "../../Src/Pages/Login";
          }, 1500);
        } else {
          ToastVersion(swalTheme, response.message || "Failed to reset password! Please try again.", "error", 3000, "top-end");
          $("#ResetPasswordBtn").prop("disabled", false);
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus, "An error occurred while resetting password. Please try again.");
        $("#ResetPasswordBtn").prop("disabled", false);
      },
      statusCode: {
        404: function () {
          handleAjaxError(null, null, "Reset password endpoint not found! Please contact support.");
          $("#ResetPasswordBtn").prop("disabled", false);
        },
        500: function () {
          handleAjaxError(null, null, "Server error! Please try again later.");
          $("#ResetPasswordBtn").prop("disabled", false);
        },
        403: function () {
          window.location.href = "../../Src/Pages/";
        },
      },
    });
  });
});
