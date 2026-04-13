import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

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

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
    $("#PageMainContent").fadeIn(500);
  });

  $("#email, #password").on("keypress", function (e) {
    if (e.which === 13) {
      const email = $("#email").val().trim();
      const password = $("#password").val().trim();
      if (email && password) {
        $("#loginBtnText").click();
      }
    }
  });

  $("#email").on("input", function () {
    const email = $(this).val().trim();
    const emailFeedback = $("#emailFeedback");
    if (!EmailRegex.test(email)) {
      $(this).addClass("is-invalid").removeClass("is-valid");
      emailFeedback.text("Please enter a valid email address.");
      isEmailValid = false;
    } else {
      $(this).addClass("is-valid").removeClass("is-invalid");
      emailFeedback.text("");
      isEmailValid = true;
    }
    noFieldError();
    setTimeout(() => {
      $(this).removeClass("is-valid");
      emailFeedback.text("");
    }, 3000);
  });

  $("#password").on("input", function () {
    const password = $(this).val().trim();
    const passwordFeedback = $("#passwordFeedback");
    if (!PasswordRegex.test(password)) {
      $(this).addClass("is-invalid").removeClass("is-valid");
      passwordFeedback.text("Password must be at least 8 characters, include uppercase, lowercase, and a number.");
      isPasswordValid = false;
    } else {
      $(this).addClass("is-valid").removeClass("is-invalid");
      passwordFeedback.text("");
      isPasswordValid = true;
    }
    noFieldError();
    setTimeout(() => {
      $(this).removeClass("is-valid");
      passwordFeedback.text("");
    }, 3000);
  });


  $("#loginBtn").on("click", function () {
    const email = $("#email").val().trim();
    const password = $("#password").val().trim();

    if (!EmailRegex.test(email) || !PasswordRegex.test(password)) {
      ToastVersion(swalTheme, "Invalid email or password format!", "error", 3000, "top-end");
      return;
    }

    $.ajax({
      url: "../../process/auth/login",
      method: "POST",
      timeout: 5000,
      data: {
        email: email,
        password: password,
        _token: csrfToken,
      },
      beforeSend: function () {
        $(this).prop("disabled", true);
        $("#loginSpinner").removeClass("d-none");
        $("#loginBtnText").text("Signing In...");
      },
      success: function (response) {
        if (response.status === "success") {
            if (response.must_change_password) {
                ToastVersion(swalTheme, "You must change your password before proceeding.", "warning", 3000, "top-end");
                setTimeout(() => {
                    window.location.href = response.redirect_url;
                }, 3000);
            } else if (!response.has_submitted_requirements && response.role === "student") {
                ToastVersion(swalTheme, "Please submit your requirements to access the dashboard.", "info", 3000, "top-end");
                setTimeout(() => {
                    window.location.href = response.redirect_url;
                }, 3000);
            } else {
                window.location.href = response.redirect_url;
            }
        } else {
          ToastVersion(swalTheme, response.message || "Login failed. Please try again.", "error", 3000, "top-end");
          $("#loginBtn").prop("disabled", false);
          $("#loginSpinner").addClass("d-none");
          $("#loginBtnText").text("Sign In");
        }
      },
      error: function (xhr, status) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Login request timed out. Please check your connection and try again.", "error", 3000, "top-end");
        } else {
          ToastVersion(swalTheme, "An error occurred during login. Please try again.", "error", 3000, "top-end");
        }
        $("#loginBtn").prop("disabled", false);
        $("#loginSpinner").addClass("d-none");
        $("#loginBtnText").text("Sign In");
      },
    });
  });
});
