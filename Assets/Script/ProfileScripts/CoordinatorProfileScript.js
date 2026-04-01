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

const urlParams = new URLSearchParams(window.location.search);
const action = urlParams.get("action");
const userUuid = urlParams.get("uuid");

function ProfileProgressBar(fill = 0) {
  const totalFields = enableChangePassword ? 6 : 5; // Include new password field if enabled
  let filledFields = fill; // Start with the provided fill value

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#employeeId").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;
  if ($("#department").val().trim()) filledFields++;
  if (enableChangePassword && $("#newPassword").val().trim()) filledFields++;

  const progressPercent = (filledFields / totalFields) * 100;
  progressBar.css("width", progressPercent + "%");
  progressStatus.text(Math.round(progressPercent) + "%");

}

function getProfileData(uuid) {
  $("#profileprogressLabel").text("Updating Profile");
  $("#profileInfoText").text("Make changes to your profile information. Don't forget to save your changes.");
  $.ajax({
    url: "../../../Assets/api/coordinator_profile_functions",
    method: "POST",
    data: { action: "fetch_profile_data", uuid: uuid },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const data = response.data;
        const fill = data.profile.first_name && data.profile.last_name && data.profile.employee_id && data.profile.mobile && data.profile.department ? 5 : 0;
        ProfileProgressBar(fill);

        $("#firstName").val(data.profile.first_name);
        $("#lastName").val(data.profile.last_name);
        $("#middleName").val(data.profile.middle_name);
        $("#employeeId").val(data.profile.employee_id);
        $("#contactNumber").val(data.profile.mobile ? data.profile.mobile.replace(/\D/g, "").slice(0, 11) : "");
        $("#department").val(data.profile.department);

        if (data.profile.profile_path) {
          $("#adminProfilePhoto").attr("src", "../../../" + data.profile.profile_path);
        } else {
          $("#adminProfilePhoto").attr("src", "https://placehold.co/64x64/C1C1C1/000000/png?text=" + data.profile.initials + "&font=poppins");
        }
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching profile data. Please try again.", "error", 3000);
      }
    },
  });
}


$(document).ready(function () {
  $("#firstName, #lastName, #employeeId, #contactNumber, #department").on("input", function () {
    ProfileProgressBar();
  });

  if (action === "edit" && userUuid) {
    getProfileData(userUuid);
  }


  $("#uploadPhotoBtn").on("click", function () {
    $("#photoInput").click();
  });

  $("#photoInput").on("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      ToastVersion(swalTheme, "Invalid file type. Please select an image file.", "warning", 3000);
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
    let department = $("#department").val().trim();
    let ProfilePhoto = $("#adminProfilePhoto").attr("src");
    let newPassword = enableChangePassword ? $("#newPassword").val().trim() : null;

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

    const passwordPattern = /^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/;
    if (enableChangePassword && !passwordPattern.test(newPassword)) {
      ToastVersion(swalTheme, "Invalid password format. Password must be at least 8 characters long and contain both letters and numbers.", "warning", 3000);
      return;
    }

    if (!department) {
      ToastVersion(swalTheme, "Please enter your department.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/SaveProfile_Coordinator",
      method: "POST",
      data: {
        firstName: firstName,
        lastName: lastName,
        middleName: middleName,
        employeeId: employeeId,
        contactNumber: contactNumber,
        ProfilePhoto: ProfilePhoto,
        department: department,
        newPassword: newPassword,
      },
      dataType: "json",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          window.location.href = "../../../Src/Pages/Coordinator/CoordinatorDashboard.php";
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
        $("#firstName, #lastName, #middleName, #employeeId, #contactNumber").val("");
        $("#adminProfilePhoto").attr("src", "https://placehold.co/64x64?text=No+Photo");
      },
      statusCode: {
        400: function () {
          ToastVersion(swalTheme, "Bad Request. Please check your input and try again.", "error", 3000);
        },
        403: function () {
          window.location.href = "../../../Src/Pages";
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
