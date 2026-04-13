import { animate } from "../../../libs/animejs/modules/animation/animation.js";
import { splitText } from "../../../libs/animejs/modules/text/split.js";
import { stagger } from "../../../libs/animejs/modules/utils/stagger.js";
import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const progressBar = $("#profileProgressBar");
const progressStatus = $("#profileprogressStatus");
const enableChangePassword = $("body").data("enable-changepassword") === "true";

function ProfileProgressBar() {
  const totalFields = 4;
  let filledFields = 0;

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#employeeId").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;

  const progressPercent = (filledFields / totalFields) * 100;
  progressBar.css("width", progressPercent + "%");
  progressStatus.text(Math.round(progressPercent) + "%");
}

$(document).ready(function () {
  $("#firstName, #lastName, #employeeId, #contactNumber").on("input", ProfileProgressBar);

  $("#uploadPhotoBtn").on("click", function () {
    $("#photoInput").click();
  });

  $("#photoInput").on("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      ToastVersion(swalTheme, "Ivalid file type. Please select an image file.", "warning", 3000);
      return;
    }

    if (file.size > 10 * 1024 * 1024) {
      ToastVersion(swalTheme, "File size exceeds 10MB. Please select a smaller image.", "warning", 3000);
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      const img = new Image();
      img.onload = function () {
        if (img.width > 3000 || img.height > 3000) {
          ToastVersion(swalTheme, "Image dimensions exceed 3000x3000. Please select a smaller image.", "warning", 3000);
          return;
        }
        $("#adminProfilePhoto").attr("src", e.target.result);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  $("#contactNumber").on("input", function () {
    let value = $(this).val().replace(/\D/g, "");
    if (value.length > 11) {
      value = value.slice(0, 11);
    }
    $(this).val(value);
  });

  $("#employeeId").on("input", function () {
    // pattern: EMP-0000-00000000
    let value = $(this)
      .val()
      .toUpperCase()
      .replace(/[^A-Z0-9-]/g, "");
    if (value.length > 17) {
      value = value.slice(0, 17);
    }
    $(this).val(value);
  });

  $("#saveProfileBtn").on("click", function () {
    let firstName = $("#firstName").val().trim();
    let lastName = $("#lastName").val().trim();
    let middleName = $("#middleName").val().trim();
    let employeeId = $("#employeeId").val().trim();
    let contactNumber = $("#contactNumber").val().trim();
    let ProfilePhoto = $("#adminProfilePhoto").attr("src");

    if (!firstName || !lastName || !employeeId || !contactNumber) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    if (enableChangePassword && !newPassword) {
      ToastVersion(swalTheme, "Please enter a new password.", "warning", 3000);
      return;
    }

    const contactPattern = /^09\d{9}$/;
    if (!contactPattern.test(contactNumber)) {
      ToastVersion(swalTheme, "Invalid contact number format. Please enter a valid 11-digit number starting with 09.", "warning", 3000);
      return;
    }

    const employeeIdPattern = /^EMP-\d{4}-\d{8}$/;
    if (!employeeIdPattern.test(employeeId)) {
      ToastVersion(swalTheme, "Invalid employee ID format. Please use the format EMP-0000-00000000.", "warning", 3000);
      return;
    }

    const namePattern = /^[A-Za-z\s]{2,50}$/;
    if (!namePattern.test(firstName) || !namePattern.test(lastName)) {
      ToastVersion(swalTheme, "Invalid name format. Names must be between 2 and 50 characters and can only contain letters and spaces.", "warning", 3000);
      return;
    }


    $.ajax({
      url: "../../../Assets/api/SaveProfile_Admin",
      method: "POST",
      data: {
        firstName: firstName,
        lastName: lastName,
        middleName: middleName,
        employeeId: employeeId,
        contactNumber: contactNumber,
        ProfilePhoto: ProfilePhoto,
      },
      dataType: "json",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          if (enableChangePassword) {
            window.location.href = "../../../Src/Pages/ChangePassword.php";
            return;
          }
          window.location.href = "../../../Src/Pages/Admin/AdminDashboard.php";
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
        } else {
          ToastVersion(swalTheme, "An error occurred: " + error, "error", 3000);
        }
      },
      complete: function () {
        $("#FirstName, #LastName, #MiddleName, #EmployeeId, #ContactNumber").val("");
        $("#adminProfilePhoto").attr("src", "https://placehold.co/64x64?text=No+Photo");
      },
      statusCode: {
        400: function () {
          ToastVersion(swalTheme, "Bad Request. Please check your input and try again.", "error", 3000);
        },
        403: function () {
          windows.location.href = "../../../Src/Pages";
        },
        404: function () {
          ToastVersion(swalTheme, "Endpoint not found. Please contact support.", "error", 3000);
        },
        500: function () {
          ToastVersion(swalTheme, "Server Error. Please try again later.", "error", 3000);
        },
      },
    });
  });
});
