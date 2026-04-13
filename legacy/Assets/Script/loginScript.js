import { animate } from "../../libs/animejs/modules/animation/animation.js";
import { win } from "../../libs/animejs/modules/core/consts.js";
import { splitText } from "../../libs/animejs/modules/text/split.js";
import { stagger } from "../../libs/animejs/modules/utils/stagger.js";
import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const EmailRegex = /^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$/;
const PasswordRegex = /^(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[a-zA-Z]).{8,}$/;
let isEmailValid = false;
let isPasswordValid = false;

function noFieldError() {
  if (isEmailValid && isPasswordValid) {
    $("#loginBtn").prop("disabled", false);
  } else {
    $("#loginBtn").prop("disabled", true);
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

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
    $("#PageMainContent").fadeIn(500);
  });

  const $loginLeft = $('[name="login-left"]');
  const checkScreenSize = () => {
    $loginLeft.toggleClass("rounded-start-3", window.innerWidth >= 768).toggleClass("rounded-bottom-3", window.innerWidth < 768);
  };
  $(window).on("resize load", checkScreenSize);

  $("#email").on("input", function () {
    const email = $(this).val();
    if (!EmailRegex.test(email)) {
      $(this).addClass("is-invalid").removeClass("is-valid");
      $("#emailFeedback").text("Please enter a valid email address.");
    } else {
      $(this).addClass("is-valid").removeClass("is-invalid");
      $("#emailFeedback").text("");
      isEmailValid = true;
      noFieldError();
    }
    setTimeout(() => {
      $(this).removeClass("is-valid");
      $("#emailFeedback").text("");
    }, 3000);
  });

  $("#password").on("input", function () {
    const password = $(this).val();
    if (!PasswordRegex.test(password)) {
      $(this).addClass("is-invalid").removeClass("is-valid");
      $("#passwordFeedback").text("Password must be at least 8 characters long and include uppercase, lowercase, and a number.");
    } else {
      $(this).addClass("is-valid").removeClass("is-invalid");
      $("#passwordFeedback").text("");
      isPasswordValid = true;
      noFieldError();
    }
    setTimeout(() => {
      $(this).removeClass("is-valid");
      $("#passwordFeedback").text("");
    }, 3000);
  });

  $("#loginBtn").on("click", function () {
    const email = $("#email").val();
    const password = $("#password").val();

    if (!EmailRegex.test(email) || !PasswordRegex.test(password)) {
      ToastVersion(swalTheme, "Invalid email or password format!", "error", 3000, "top-end");
      return;
    }

    $(this).prop("disabled", true);

    $("#loginSpinner").removeClass("d-none");
    $("#loginBtnText").text("Signing In...");

    $.ajax({
      url: "../../Assets/api/loginProcess",
      method: "POST",
      timeout: 10000,
      data: {
        email: email,
        password: password,
      },
      success: function (response) {
        if (response.status === "success") {
          if (response.has_submitted_requirements) {
            window.location.href = "../../Src/Pages/Students/Requirements";
            return;
          }
          window.location.href = response.redirect_url || "../../Src/Pages/Login";
        } else if (response.status === "info") {
          ToastVersion(swalTheme, response.message, response.status, 3000, "top-end");
        } else {
          handleAjaxError(null, null, response.message || "Login failed! Please check your credentials and try again.");
        }
      },
      error: function (xhr, textStatus) {
        handleAjaxError(xhr, textStatus, "An error occurred. Please try again.");
      },
      complete: function () {
        $("#loginSpinner").addClass("d-none");
        $("#loginBtnText").text("Sign In");
        $("#loginBtn").prop("disabled", false);
      },
      statusCode: {
        404: function () {
          handleAjaxError(null, null, "Login endpoint not found! Please contact support.");
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

  $("#email, #password").on("keypress", function (e) {
    if (e.which === 13) {
      if ($("#email").val() && $("#password").val()) {
        $("#loginBtn").click();
      } else {
        ToastVersion(swalTheme, "Please fill in both email and password fields!", "warning", 3000, "top-end");
      }
    }
  });
});
