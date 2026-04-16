import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";

if (!csrfToken || !userRole || userRole !== "coordinator") {
  window.location.href = "../../../Src/Pages/Login";
}

function startCoordinatorProfileTour() {
  if (!window.driver?.js?.driver || typeof driver !== "function") {
    ToastVersion(swalTheme, "Guided tour is currently unavailable.", "warning", 3000);
    return;
  }
  const profileTour = driver({
    showProgress: true,
    animate: true,
    smoothScroll: true,
    allowClose: false,
    doneBtnText: "Finish",
    nextBtnText: "&#187;",
    prevBtnText: "&#171;",
    popoverClass: "bg-blur-10 bg-semi-transparent text-body",
    overlayColor: "rgba(0, 0, 0, 0.80)",
    steps: [
      {
        element: ".admin-profile-card",
        popover: {
          title: "Coordinator Profile Setup",
          description: "Welcome! This quick tour shows the important sections so you can finish your profile faster.",
          side: "over",
          align: "center",
        },
      },
      {
        element: ".progress",
        popover: {
          title: "Progress Tracker",
          description: "This bar updates as you complete required fields.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#adminProfilePhoto",
        popover: {
          title: "Profile Photo",
          description: "Upload a clear photo so students and supervisors can easily identify you.",
          side: "right",
          align: "center",
        },
      },
      {
        element: "#uploadPhotoBtn",
        popover: {
          title: "Upload Button",
          description: "Click here to choose a new photo from your device.",
          side: "left",
          align: "center",
        },
      },
      {
        element: ".fname-group",
        popover: {
          title: "Required Information",
          description: "Enter your first name here. This is required for your profile to be complete.",
          side: "top",
          align: "start",
        },
      },
      {
        element: ".lname-group",
        popover: {
          title: "Required Information",
          description: "Enter your last name here. This is also required for your profile to be complete.",
          side: "top",
          align: "start",
        },
      },
      {
        element: ".mname-group",
        popover: {
          title: "Optional Information",
          description: "Enter your middle name here. This is optional but helps with identification.",
          side: "top",
          align: "start",
        },
      },
      {
        element: ".employeeId-group",
        popover: {
          title: "Required Information",
          description: "Enter your employee ID here. Use the ID provided by your institution.",
          side: "top",
          align: "start",
        },
      },
      {
        element: ".department-group",
        popover: {
          title: "Required Information",
          description: "Select your department. This helps students know which department you belong to.",
          side: "top",
          align: "start",
        },
      },
      {
        element: ".contactNumber-group",
        popover: {
          title: "Required Information",
          description: "Enter your contact number here. This allows students and supervisors to reach you if needed.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#saveProfileBtn",
        popover: {
          title: "Save Your Profile",
          description: "After filling out all required fields, click here to save your profile information.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "#startTourLink",
        popover: {
          title: "Retake the Tour",
          description: "Click here anytime to retake this tour and review the profile setup steps.",
          side: "top",
          align: "center",
        },
      },
    ],
  });

  profileTour.drive();
}

const ACTION_STORAGE_KEY = "coordinator_profile_action";
const urlParams = new URLSearchParams(window.location.search);
const actionFromUrl = urlParams.get("action");
let action = actionFromUrl || sessionStorage.getItem(ACTION_STORAGE_KEY);
let setupProfile = action === "edit" ? false : true;

if (actionFromUrl) {
  sessionStorage.setItem(ACTION_STORAGE_KEY, actionFromUrl);
  action = actionFromUrl;

  const newUrl = window.location.href.split("?")[0];
  window.history.replaceState({}, document.title, newUrl);
}

function showError(inputSelector, message) {
  if (!inputSelector) {
    ToastVersion(swalTheme, message, "info", 3000, "top");
    return;
  }

  if (inputSelector === "#photoInput") {
    ToastVersion(swalTheme, "Invalid file. Please select a valid image file (jpg, png, gif) that is less than 5MB.", "info", 3000, "top");
    return;
  } else {
    $(inputSelector).addClass("is-invalid");
    ToastVersion(swalTheme, message, "info", 3000, "top");
    setTimeout(() => {
      $(inputSelector).removeClass("is-invalid");
    }, 3000);
    return;
  }
}

function ProfileProgressBar(fill = 0) {
  const progressBar = $("#profileProgressBar");
  const progressStatus = $("#profileprogressStatus");
  const totalFields = 5;
  let filledFields = fill;

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#employeeId").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;
  if ($("#department").val().trim()) filledFields++;

  const progressPercent = (filledFields / totalFields) * 100;
  progressBar.css("width", progressPercent + "%");
  progressStatus.text(Math.round(progressPercent) + "%");
}

function fetchProfileData() {
  $.ajax({
    url: "../../../process/profile/get_profile",
    type: "POST",
    data: { csrf_token: csrfToken },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const profile = response.profile;

        if (profile.profile_name) {
          $("#adminProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
        } else {
          const initials = profile.initials || "NA";
          $("#adminProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
        }

        $("#firstName").val(profile.first_name);
        $("#lastName").val(profile.last_name);
        $("#middleName").val(profile.middle_name);
        $("#employeeId").val(profile.employee_id);
        $("#department").val(profile.department);
        $("#contactNumber").val(profile.mobile);
        ProfileProgressBar();
      } else {
        ToastVersion(swalTheme, response.message || "An error occurred while fetching your profile data. Please try again.", "error", 3000, "top");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
    complete: function () {
      $("#saveProfileBtn").prop("disabled", false).text("Save Profile");
    },
  });
}

$(document).ready(function () {
  $("#startTourLink").on("click", function (e) {
    e.preventDefault();
    startCoordinatorProfileTour();
  });

  if (action === "edit") {
    $("#backToDashboardLink").removeClass("d-none");
    fetchProfileData();
  } else {
    $("#backToDashboardLink").addClass("d-none");
    startCoordinatorProfileTour();
  }

  $("#backToDashboardLink").on("click", function () {
    sessionStorage.removeItem(ACTION_STORAGE_KEY);
  });

  $("#photoInput").on("change", function (event) {
    const file = event.target.files[0];
    if (!file) return;

    if (!file.type.startsWith("image/")) {
      showError("#photoInput", "Invalid file type. Please select an image.");
      return;
    }

    if (file.size > 10 * 1024 * 1024) {
      showError("#photoInput", "File size exceeds 10MB. Please select a smaller image.");
      return;
    }

    const reader = new FileReader();
    reader.onload = function (e) {
      const img = new Image();
      img.onload = function () {
        if (img.width > 3000 || img.height > 3000) {
          showError("#photoInput", "Image dimensions exceed 3000x3000. Please select a smaller image.");
          return;
        }
        $("#adminProfilePhoto").attr("src", e.target.result);
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });

  $("#firstName, #lastName, #employeeId, #contactNumber, #department").on("input", function () {
    ProfileProgressBar();
  });

  $("#contactNumber").on("input", function () {
    let value = $(this).val().replace(/\D/g, "");
    if (value.length > 11) {
      value = value.slice(0, 11);
    }
    $(this).val(value);
  });

  $("#saveProfileBtn").on("click", function () {
    sessionStorage.removeItem(ACTION_STORAGE_KEY);

    const firstName = $("#firstName").val().trim();
    const lastName = $("#lastName").val().trim();
    const middleName = $("#middleName").val().trim();
    const employeeId = $("#employeeId").val().trim();
    const department = $("#department").val();
    const contactNumber = $("#contactNumber").val().trim();
    const photoInput = $("#photoInput")[0];

    if (!firstName) {
      showError("#firstName", "First name is required.");
      return;
    }

    if (!lastName) {
      showError("#lastName", "Last name is required.");
      return;
    }

    if (!employeeId) {
      showError("#employeeId", "Employee ID is required.");
      return;
    }

    if (!department) {
      showError("#department", "Please select a department.");
      return;
    }

    if (!contactNumber) {
      showError("#contactNumber", "Contact number is required.");
      return;
    }

    $("#saveProfileBtn").prop("disabled", true).text("Saving...");

    const formData = new FormData();
    formData.append("firstName", firstName);
    formData.append("lastName", lastName);
    formData.append("middleName", middleName);
    formData.append("employeeId", employeeId);
    formData.append("department", department);
    formData.append("contactNumber", contactNumber);
    if (photoInput.files.length > 0) {
      formData.append("profilePhoto", photoInput.files[0]);
    }
    formData.append("csrf_token", csrfToken);
    formData.append("setupProfile", setupProfile);

    $.ajax({
      url: "../../../process/profile/save_profile",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      beforeSend: function () {
        $("#saveProfileBtn").prop("disabled", true).text("Saving...");
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message || "Profile saved successfully.", "success", 3000);
          fetchProfileData();
          if (response.redirect_url && setupProfile) {
            setTimeout(() => {
              window.location.href = response.redirect_url;
            }, 3000);
          } else {
            window.location.href = "../../../Src/Pages/Coordinator/viewProfile.php";
          }
        } else {
          ToastVersion(swalTheme, response.message || "An error occurred while saving your profile. Please try again.", "error", 3000, "top");
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });
});
