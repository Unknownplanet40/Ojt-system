import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const ACTION_STORAGE_KEY = "student_profile_action";
const urlParams = new URLSearchParams(window.location.search);
const actionFromUrl = urlParams.get("action");
let action = actionFromUrl || sessionStorage.getItem(ACTION_STORAGE_KEY);
let setupProfile = action === "edit" ? false : true;

function startStudentProfileTour() {
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
          title: "Student Profile Setup",
          description: "Welcome! This quick guide helps you complete your student profile correctly.",
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
          description: "Your selected student profile photo appears here.",
          side: "right",
          align: "center",
        },
      },
      {
        element: "#uploadPhotoBtn",
        popover: {
          title: "Upload Photo",
          description: "Click to choose an image file for your profile.",
          side: "bottom",
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
          description: "Optional: add your middle name if available.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#studentNumber",
        popover: {
          title: "Student Number",
          description: "Enter your official student number exactly as provided by your school.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#contactNumber",
        popover: {
          title: "Contact Number",
          description: "Add your active mobile number.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#emergencyContactNumber",
        popover: {
          title: "Emergency Contact Number",
          description: "Provide a reachable emergency contact number.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#homeAddress",
        popover: {
          title: "Home Address",
          description: "Enter your complete current home address.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#emergencyContactName",
        popover: {
          title: "Emergency Contact Name",
          description: "Provide the full name of the person to contact in emergencies.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#section",
        popover: {
          title: "Section",
          description: "Enter your current class section or group.",
          side: "top",
          align: "start",
        },
      },
      {
        element: "#saveProfileBtn",
        popover: {
          title: "Save & Continue",
          description: "After completing required fields, click here to save and continue.",
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
  const totalFields = 8; // Total required fields to fill for profile completion
  let filledFields = fill;

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;
  if ($("#studentNumber").val().trim()) filledFields++;
  if ($("#homeAddress").val().trim()) filledFields++;
  if ($("#emergencyContactNumber").val().trim()) filledFields++;
  if ($("#emergencyContactName").val().trim()) filledFields++;
  if ($("#section").val().trim()) filledFields++;

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
        $("#contactNumber").val(profile.mobile);
        //student number
        $("#studentNumber").val(profile.student_number);
        $("#homeAddress").val(profile.home_address);
        $("#emergencyContactNumber").val(profile.emergency_contact);
        $("#emergencyContactName").val(profile.emergency_phone);
        $("#section").val(profile.section);
        $("#yearLevel").val(profile.year_level);
        if (profile.program_name) {
          const programOption = $("<option>").val(profile.program_id).text(profile.program_name).addClass("CustomOption").prop("selected", true).prop("disabled", true);
          $("#program").append(programOption);
        }
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
    fetchProfileData();
    $("#backToDashboardLink").removeClass("d-none");
  } else {
    $("#backToDashboardLink").addClass("d-none");
    startStudentProfileTour();
  }

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

  $("#startTourLink").on("click", function (e) {
    e.preventDefault();
    startStudentProfileTour();
  });

  $("#firstName, #lastName, #contactNumber, #studentNumber, #homeAddress, #emergencyContactNumber, #emergencyContactName, #section").on("input", ProfileProgressBar);

  $("#saveProfileBtn").on("click", function () {
    const firstName = $("#firstName").val().trim();
    const lastName = $("#lastName").val().trim();
    const contactNumber = $("#contactNumber").val().trim();
    const studentNumber = $("#studentNumber").val().trim();
    const homeAddress = $("#homeAddress").val().trim();
    const emergencyContactNumber = $("#emergencyContactNumber").val().trim();
    const emergencyContactName = $("#emergencyContactName").val().trim();
    const section = $("#section").val().trim();
    const photoFile = $("#photoInput")[0].files[0];

    if (!firstName || !lastName || !contactNumber || !studentNumber || !homeAddress || !emergencyContactNumber || !emergencyContactName || !section) {
      ToastVersion(swalTheme, "Please fill out all required fields to complete your profile.", "warning", 3000, "top");
      return;
    }


    const formData = new FormData();
    formData.append("csrf_token", csrfToken);
    formData.append("firstName", firstName);
    formData.append("lastName", lastName);
    formData.append("contactNumber", contactNumber);
    formData.append("studentNumber", studentNumber);
    formData.append("homeAddress", homeAddress);
    formData.append("emergencyContact", emergencyContactNumber);
    formData.append("emergencyPhone", emergencyContactName);
    formData.append("section", section);
    formData.append("setupProfile", setupProfile);
    if (photoFile) {
      formData.append("profilePhoto", photoFile);
    }

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
          } else {
            window.location.href = "../../../Src/Pages/Students/StudentsDashboard.php";
          }
        } else {
          ToastVersion(swalTheme, response.message || "An error occurred while saving your profile. Please try again.", "error", 3000, "top");
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

  $("#changePasswordBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/ChangePassword";
  });
});
