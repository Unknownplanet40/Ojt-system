import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const ACTION_STORAGE_KEY = "admin_profile_action";
const urlParams = new URLSearchParams(window.location.search);
const actionFromUrl = urlParams.get("action");
let action = actionFromUrl || sessionStorage.getItem(ACTION_STORAGE_KEY);
let setupProfile = action === "edit" ? false : true;

function startAdminProfileTour() {
  if (!window.driver?.js?.driver || typeof driver !== "function") {
    ToastVersion(swalTheme, "Guided tour is currently unavailable.", "warning", 3000);
    return;
  }

  const profileTour = driver({
    showProgress: true,
    animate: true,
    smoothScroll: true,
    allowClose: true,
    doneBtnText: "Finish",
    nextBtnText: "&#187;",
    prevBtnText: "&#171;",
    popoverClass: "bg-blur-10 bg-semi-transparent text-body",
    overlayColor: "rgba(0, 0, 0, 0.80)",
    steps: [
      {
        element: ".admin-profile-card",
        popover: {
          title: "Administrator Profile Setup",
          description: "Welcome! This quick guide walks you through completing your account profile.",
          side: "over",
          align: "center",
        },
      },
      {
        element: ".progress",
        popover: {
          title: "Progress Tracker",
          description: "Track completion here as you fill out required account details.",
          side: "bottom",
          align: "start",
        },
      },
      {
        element: "#adminProfilePhoto",
        popover: {
          title: "Profile Photo Preview",
          description: "Your selected profile photo appears here.",
          side: "right",
          align: "center",
        },
      },
      {
        element: ".emailInfo",
        popover: {
          title: "Email & Role",
          description: "Your registered email and role are displayed here for reference. These details are pulled from your account information and cannot be edited here.",
          side: "right",
          align: "center",
        },
      },
      {
        element: "#uploadPhotoBtn",
        popover: {
          title: "Upload Photo",
          description: "Click to choose an image file for your profile.",
          side: "left",
          align: "center",
        },
      },
      {
        element: "#firstName",
        popover: {
          title: "First Name",
          description: "Enter your first name. This field is required.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#lastName",
        popover: {
          title: "Last Name",
          description: "Enter your last name. This field is required.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#middleName",
        popover: {
          title: "Middle Name",
          description: "You can optionally add your middle name for clearer identification.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#contactNumber",
        popover: {
          title: "Contact Number",
          description: "Add an active contact number so users can reach the administrator when needed.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#saveProfileBtn",
        popover: {
          title: "Save & Continue",
          description: "Once all required fields are complete, click here to save your profile.",
          side: "top",
          align: "center",
        },
      },
      {
        element: "#startTourLink",
        popover: {
          title: "Need a Refresher?",
          description: "Use this link anytime to replay the setup guide.",
          side: "top",
          align: "center",
        },
      },
    ],
  });

  profileTour.drive();
}

if (actionFromUrl) {
  sessionStorage.setItem(ACTION_STORAGE_KEY, actionFromUrl);
  action = actionFromUrl;

  const newUrl = window.location.href.split("?")[0];
  window.history.replaceState({}, document.title, newUrl);
}


function ProfileProgressBar(fill = 0) {
  const progressBar = $("#profileProgressBar");
  const progressStatus = $("#profileprogressStatus");
  const totalFields = 3;
  let filledFields = fill;

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;

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
        $("#contactNumber").val(profile.contact_number);
        ProfileProgressBar();
      } else {
        ToastVersion(swalTheme, response.message || "An error occurred while fetching your profile data. Please try again.", "error", 3000, "top");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

$(document).ready(function () {
  if (action === "edit") {
    $("#backToDashboardLink").removeClass("d-none");
    fetchProfileData();
  } else {
    $("#backToDashboardLink").addClass("d-none");
    startAdminProfileTour();
  }

  $("#startTourLink").on("click", function (e) {
    e.preventDefault();
    startAdminProfileTour();
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

  $("#contactNumber").on("input", function () {
    let value = $(this).val().replace(/\D/g, "");
    if (value.length > 11) {
      value = value.slice(0, 11);
    }
    $(this).val(value);
  });

  $("#firstName, #lastName, #contactNumber").on("input", ProfileProgressBar);

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

  $("#saveProfileBtn").on("click", function () {
    sessionStorage.removeItem(ACTION_STORAGE_KEY);
    const firstName = $("#firstName").val().trim();
    const lastName = $("#lastName").val().trim();
    const middleName = $("#middleName").val().trim();
    const contactNumber = $("#contactNumber").val().trim();
    const photoFile = $("#photoInput")[0].files[0];

    if (!firstName) {
      showError("#firstName", "First name is required.");
      return;
    }

    if (!lastName) {
      showError("#lastName", "Last name is required.");
      return;
    }

    if (!contactNumber) {
      showError("#contactNumber", "Contact number is required.");
      return;
    }

    const formData = new FormData();
    formData.append("firstName", firstName);
    formData.append("lastName", lastName);
    formData.append("middleName", middleName);
    formData.append("contactNumber", contactNumber);
    formData.append("setupProfile", setupProfile);
    if (photoFile) {
      formData.append("profilePhoto", photoFile);
    }
    formData.append("csrf_token", csrfToken);

    $.ajax({
      url: "../../../process/profile/save_profile",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      dataType: "json",
      beforeSend: function () {
        $(this).prop("disabled", true).text("Saving...");
      },
      success: function (response) {
        if (response.status === "success") {
            ToastVersion(swalTheme, response.message || "Profile saved successfully.", "success", 3000);
            fetchProfileData();
            if (response.redirect_url && setupProfile) {
                setTimeout(() => {
                    window.location.href = response.redirect_url;
                }, 3000);
            }
        } else {
          ToastVersion(swalTheme, response.message || "An error occurred while saving your profile. Please try again.", "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
      complete: function () {
        $("#saveProfileBtn").prop("disabled", false).text("Save & Continue");
      },
    });
  });
});
