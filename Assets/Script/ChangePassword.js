import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

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

function handleAjaxError(xhr, textStatus, customMessage = null) {
  let errorMessage = customMessage || "An error occurred. Please try again.";

  if (textStatus === "timeout") {
    errorMessage = "Request timed out! Please check your connection and try again.";
  } else if (xhr && xhr.status) {
    errorMessage = `Error ${xhr.status}: ${xhr.statusText}`;
  }

  ToastVersion(swalTheme, errorMessage, "error", 3000, "top-end");
}

let changepasswordmode = $("body").data("changepasswordmode");
const userUuid = $("body").data("user-uuid");

const urlParams = new URLSearchParams(window.location.search);
const action = urlParams.get("action");
const uuid = urlParams.get("uuid");

if (userUuid === "Unauthenticated") {
  window.location.href = "../../Src/Pages/Login";
}

if (action && uuid) {
  if (action === "forced") {
    CardtoShow("ForcePasswordChangeCard");
  } else if (action === "voluntary") {
    CardtoShow("VoluntaryPasswordChangeCard");
  }

  if (changepasswordmode === "none") {
    changepasswordmode = null;
  }
} else {
  if (changepasswordmode === "forced") {
    CardtoShow("ForcePasswordChangeCard");
    CardtoShow("VoluntaryPasswordChangeCard");
  } else if (changepasswordmode === "voluntary") {
    CardtoShow("VoluntaryPasswordChangeCard");
  }
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

    $(this).prop("disabled", true);
    $.ajax({
      url: "../../Assets/api/ChangePasswordProcess",
      method: "POST",
      data: {
        newPassword: newPassword,
        tempPassword: tempPassword,
        type: changepasswordmode,
      },
      success: function (response) {
        if (response.status === "success") {
          CardtoShow("SuccessCard");
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
          $("#SetPasswordBtn").prop("disabled", false);
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus);
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
    const destination = $(this).data("distination");
    if (destination && destination !== "none") {
      window.location.href = destination;
    } else {
      window.location.href = "../../";
    }
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

    $(this).prop("disabled", true);
    $.ajax({
      url: "../../Assets/api/ChangePasswordProcess",
      method: "POST",
      data: {
        newPassword: newPassword,
        currentPassword: currentPassword,
        type: changepasswordmode || action || "voluntary",
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
          CardtoShow("SuccessCard");
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
          $("#updatePasswordBtn").prop("disabled", false);
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus);
        $("#updatePasswordBtn").prop("disabled", false);
      },
    });
  });

  $("#GoToLoginBtn").on("click", function () {
    window.location.href = "../../Src/Pages/Login";
  });
});
