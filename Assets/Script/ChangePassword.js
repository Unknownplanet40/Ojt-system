import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

if (!csrfToken) {
  window.location.href = "../../../Src/Pages/Login";
}

let redirectUrl = null;
function CardtoShow(cardId) {
  const cards = ["ForcePasswordChangeCard", "VoluntaryPasswordChangeCard", "SuccessCard"];
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

function Errors(xhr, status, error) {
  // xhr: The XMLHttpRequest object that was used to make the request.
  // status: A string describing the type of error that occurred. Possible values include "timeout", "error", "abort", and "parsererror".
  // error: An optional exception object, if one occurred.

  const payload = xhr?.responseJSON || null;

  if (payload?.code === "PROFILE_INCOMPLETE" && payload?.redirect_url) {
    ToastVersion(swalTheme, payload.message || "Complete your profile setup first.", "warning", 2000, "top-end");
    setTimeout(() => {
      window.location.href = payload.redirect_url;
    }, 250);
    return;
  }

  if (xhr.status === 403) {
    ModalVersion(swalTheme, "Access Denied", "Your session may have expired or you do not have permission to access this resource. Please refresh the page and try again.", "error", 0, "center");
    return;
  }

  if (xhr.status === 404) {
    ModalVersion(swalTheme, "Not Found", "The requested resource was not found.", "error", 0, "center");
    return;
  }

  if (xhr.status >= 498) {
    ModalVersion(swalTheme, "Token Error", "There was an issue with your session token. Please refresh the page and try again.", "error", 0, "center");
    return;
  }

  if (xhr.status >= 500) {
    ModalVersion(swalTheme, "Server Error", "An unexpected error occurred on the server. Please try again later.", "error", 0, "center");
    return;
  }

  if (status === "timeout") {
    ToastVersion(swalTheme, "The request timed out. Please check your internet connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "abort") {
    ToastVersion(swalTheme, "The request was aborted. Please try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "network") {
    ToastVersion(swalTheme, "A network error occurred. Please check your connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "parsererror") {
    ModalVersion(swalTheme, "Response Error", "The server returned an unexpected response. Please try again later.", "error", 0, "center");
    return;
  }

  if (error) {
    ModalVersion(swalTheme, "Error", "An unexpected error occurred: " + error, "error", 0, "center");
    return;
  }

  ModalVersion(swalTheme, "Unknown Error", "An unknown error occurred. Please try again later.", "error", 0, "center");
  console.error("An unknown error occurred:", { xhr, status, error });
}

const urlParams = new URLSearchParams(window.location.search);
const action = urlParams.get("action");
const uuid = urlParams.get("uuid");

const must_change_password = $("body").data("must-change");
const user_uuid = $("body").data("uuid");
const changepasswordmode = must_change_password ? "force" : action || "voluntary";

if (changepasswordmode === "force") {
  CardtoShow("ForcePasswordChangeCard");
} else if (changepasswordmode === "voluntary") {
  CardtoShow("VoluntaryPasswordChangeCard");
} else {
  CardtoShow("VoluntaryPasswordChangeCard");
}

$(document).ready(function () {
  const updateValidationStatus = (elementId, isValid) => {
    $(`#${elementId}`)
      .removeClass(isValid ? "text-secondary" : "text-success")
      .addClass(isValid ? "text-success" : "text-secondary");
  };

  $("#newPassword, #confirmPassword, #tempPassword")
    .on("input focus change", function () {
      $(this).attr("type", "text");
      const newPassword = $("#newPassword").val();
      const confirmPassword = $("#confirmPassword").val();
      const tempPassword = $("#tempPassword").val();

      if (newPassword.length !== 0 && confirmPassword.length !== 0 && tempPassword.length !== 0) {
        updateValidationStatus("matchCheck", newPassword === confirmPassword && newPassword !== tempPassword);
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

      const passwordStrength = (newPassword.length >= 8) + /[A-Z]/.test(newPassword) + /\d/.test(newPassword) + /[!@#$%^&*(),.?":{}|<>]/.test(newPassword);
      $("#passwordStrengthBar").css("width", `${(passwordStrength / 4) * 100}%`);

      if (newPassword.length >= 8 && /[A-Z]/.test(newPassword) && /\d/.test(newPassword) && /[!@#$%^&*(),.?":{}|<>]/.test(newPassword) && newPassword === confirmPassword) {
        $("#SetPasswordBtn").prop("disabled", false);
      }
    })
    .on("blur", function () {
      if ($(this).val() !== "") {
        $(this).attr("type", "password");
      }
    });

  $("#SetPasswordBtn").on("click", function (e) {
    const newPassword = $("#newPassword").val();
    const tempPassword = $("#tempPassword").val();
    const confirmPassword = $("#confirmPassword").val();

    if (newPassword === tempPassword) {
      ToastVersion(swalTheme, "New password cannot be the same as the temporary password.", "error", 3000, "top-end");
      return;
    }

    if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/\d/.test(newPassword) || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword) || newPassword !== confirmPassword) {
      ToastVersion(swalTheme, "New password does not meet the required criteria.", "error", 3000, "top-end");
      return;
    }

    $.ajax({
      url: "../../process/auth/changepass",
      method: "POST",
      data: {
        newPassword: newPassword,
        tempPassword: tempPassword,
        csrf_token: csrfToken,
      },
      beforeSend: function () {
        ModalVersion(swalTheme, "Updating Password", "Please wait while we update your password...", "info", 0, "center");
      },
      success: function (response) {
        if (response.status === "success") {
          Swal.close();
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
          redirectUrl = response.redirect || "../../Src/Pages/Login";
          CardtoShow("SuccessCard");
        } else {
          Swal.close();
          const errorMessage =
            response.message && typeof response.message === "object" ? Object.values(response.message).join(" ") : response.message || "An error occurred while changing the password.";
          ToastVersion(swalTheme, errorMessage, "error", 3000, "top-end");
          $("#SetPasswordBtn").prop("disabled", false);
        }
      },
      error: function (xhr, status, error) {
        Swal.close();
        Errors(xhr, status, error);
        $("#SetPasswordBtn").prop("disabled", false);
      },
    });
  });

  $("#currentPassword, #voluntaryNewPassword, #voluntaryConfirmPassword")
    .on("input focus change", function () {
      $(this).attr("type", "text");
      const newPassword = $("#voluntaryNewPassword").val();
      const confirmPassword = $("#voluntaryConfirmPassword").val();
      const currentPassword = $("#currentPassword").val();

      if (newPassword.length !== 0 && confirmPassword.length !== 0 && currentPassword.length !== 0) {
        updateValidationStatus("vmatchCheck", newPassword === confirmPassword && newPassword !== currentPassword);
        updateValidationStatus("vcharCheck", newPassword.length >= 8);
        updateValidationStatus("vupperCheck", /[A-Z]/.test(newPassword));
        updateValidationStatus("vnumberCheck", /\d/.test(newPassword));
        updateValidationStatus("vspecialCheck", /[!@#$%^&*(),.?":{}|<>]/.test(newPassword));
      } else {
        updateValidationStatus("vmatchCheck", false);
        updateValidationStatus("vcharCheck", false);
        updateValidationStatus("vupperCheck", false);
        updateValidationStatus("vnumberCheck", false);
        updateValidationStatus("vspecialCheck", false);
      }

      const passwordStrength = (newPassword.length >= 8) + /[A-Z]/.test(newPassword) + /\d/.test(newPassword) + /[!@#$%^&*(),.?":{}|<>]/.test(newPassword);
      $("#voluntaryPasswordStrengthBar").css("width", `${(passwordStrength / 4) * 100}%`);

      if (newPassword.length >= 8 && /[A-Z]/.test(newPassword) && /\d/.test(newPassword) && /[!@#$%^&*(),.?":{}|<>]/.test(newPassword) && newPassword === confirmPassword) {
        $("#updatePasswordBtn").prop("disabled", false);
      }
    })
    .on("blur", function () {
      if ($(this).val() !== "") {
        $(this).attr("type", "password");
      }
    });

  $("#CancelBtn").on("click", function () {
    window.location.href = "../../Src/Pages/Login";
  });

  $("#updatePasswordBtn").on("click", function (e) {
    const newPassword = $("#voluntaryNewPassword").val();
    const currentPassword = $("#currentPassword").val();
    const confirmPassword = $("#voluntaryConfirmPassword").val();
    if (newPassword === currentPassword) {
      ToastVersion(swalTheme, "New password cannot be the same as the current password.", "error", 3000, "top-end");
      return;
    }

    if (newPassword.length < 8 || !/[A-Z]/.test(newPassword) || !/\d/.test(newPassword) || !/[!@#$%^&*(),.?":{}|<>]/.test(newPassword) || newPassword !== confirmPassword) {
      ToastVersion(swalTheme, "New password does not meet the required criteria.", "error", 3000, "top-end");
      return;
    }

    $.ajax({
      url: "../../process/auth/changepass",
      method: "POST",
      data: {
        newPassword: newPassword,
        currentPassword: currentPassword,
        csrf_token: csrfToken,
      },
      beforeSend: function () {
        ModalVersion(swalTheme, "Updating Password", "Please wait while we update your password...", "info", 0, "center");
      },
      success: function (response) {
        if (response.status === "success") {
          Swal.close();
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
          redirectUrl = response.redirect || "../../Src/Pages/Login";
          CardtoShow("SuccessCard");
        } else {
          Swal.close();
          const errorMessage =
            response.message && typeof response.message === "object" ? Object.values(response.message).join(" ") : response.message || "An error occurred while changing the password.";
          ToastVersion(swalTheme, errorMessage, "error", 3000, "top-end");
          $("#updatePasswordBtn").prop("disabled", false);
        }
      },
      error: function (xhr, status, error) {
        Swal.close();
        Errors(xhr, status, error);
        $("#updatePasswordBtn").prop("disabled", false);
      },
    });
  });

  $("#GoToLoginBtn").on("click", function () {
    window.location.href = "../../Src/Pages/Login";
  });

  $("#redirectBtn").on("click", function () {
        if (redirectUrl) {
      window.location.href = redirectUrl;
    } else {
      window.location.href = "../../Src/Pages/Login"; 
    }
  });
});
