import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

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
  const totalFields = 10;
  let filledFields = fill;

  if ($("#firstName").val().trim()) filledFields++;
  if ($("#lastName").val().trim()) filledFields++;
  if ($("#studentNumber").val().trim()) filledFields++;
  if ($("#contactNumber").val().trim()) filledFields++;
  if ($("#homeAddress").val().trim()) filledFields++;
  if ($("#emergencyContactName").val().trim()) filledFields++;
  if ($("#emergencyContactNumber").val().trim()) filledFields++;
  if ($("#program").val()) filledFields++;
  if ($("#yearLevel").val()) filledFields++;
  if ($("#section").val()) filledFields++;

  const maxPercent = enableChangePassword ? 50 : 100;
  const progressPercent = (filledFields / totalFields) * maxPercent;
  progressBar.css("width", progressPercent + "%");
  progressStatus.text(Math.round(progressPercent) + "%");

  if (progressPercent < 25) {
    progressBar.removeClass("bg-success bg-warning").addClass("bg-danger");
  } else if (progressPercent < 50) {
    progressBar.removeClass("bg-success bg-danger").addClass("bg-warning");
  } else {
    progressBar.removeClass("bg-danger bg-warning").addClass("bg-success");
  }
}

function getProfileData(uuid) {
  $("#profileprogressLabel").text("Updating Profile");
  $("#profileInfoText").text("Make changes to your profile information. Don't forget to save your changes.");
  $.ajax({
    url: "../../../Assets/api/SaveProfile_Students",
    method: "POST",
    data: { action: "fetch_profile_data", uuid: uuid },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const data = response.data;
        $("#firstName").val(data.first_name);
        $("#lastName").val(data.last_name);
        $("#middleName").val(data.middle_name);
        $("#studentNumber").val(data.student_number);
        $("#contactNumber").val((data.mobile || "").replace(/\D/g, ""));
        $("#homeAddress").val(data.home_address);
        $("#emergencyContactName").val((data.emergency_contact || "").trim());
        $("#emergencyContactNumber").val(data.emergency_phone ? data.emergency_phone.replace(/\D/g, "") : "");
        $("#program").val(data.program_uuid).trigger("change");
        $("#yearLevel").val(data.year_level).trigger("change");
        $("#section").val(data.section);
        if (data.profile_path) {
          $("#adminProfilePhoto").attr("src", "../../../" + data.profile_path);
        } else {
          const initials = (data.first_name.charAt(0) + data.last_name.charAt(0)).toUpperCase();
          const placeholderUrl = `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`;
          $("#adminProfilePhoto").attr("src", placeholderUrl);
        }

        ProfileProgressBar();
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

function academicinfoDropdown(onLoaded) {
  const programSelect = $("#program");
  const yearLevelSelect = $("#yearLevel");
  const sectioninput = $("#section");

  $.ajax({
    url: "../../../Assets/api/academic_info_function",
    method: "POST",
    data: { action: "fetch_academic_info" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        if (response.data.programs.length === 0) {
          programSelect.empty().append('<option class="CustomOption" selected hidden disabled value="">No programs available</option>');
          yearLevelSelect.empty().append('<option class="CustomOption" selected hidden disabled value="">No year levels available</option>');
          sectioninput.val("").attr("placeholder", "No sections available").prop("disabled", true);
          if (typeof onLoaded === "function") onLoaded();
          return;
        }
        const programs = response.data.programs;
        programSelect.prop("disabled", false);
        programSelect.empty().append('<option class="CustomOption" selected hidden disabled value="">Select your program</option>');
        programs.forEach((program) => {
          programSelect.append('<option class="CustomOption" value="' + program.uuid + '">' + program.label + "</option>");
        });
        if (typeof onLoaded === "function") onLoaded();
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching academic info. Please try again.", "error", 3000);
      }
    },
  });
}

$(document).ready(function () {
  $("#firstName, #lastName, #middleName, #studentNumber, #contactNumber, #homeAddress, #emergencyContactName, #emergencyContactNumber, #program, #yearLevel, #section").on("input change", function () {
    ProfileProgressBar();
  });

  academicinfoDropdown(function () {
    if (action === "edit" && userUuid) {
      history.replaceState(null, "", window.location.pathname);
      getProfileData(userUuid);
      $("#backBtn").removeClass("d-none");
    } else {
      $("#profileprogressLabel").text("Complete Your Profile");
      $("#profileInfoText").text("Please fill in your profile information to complete your registration.");
      $("#backBtn").remove();
    }
  });

  $("#program").on("change", function () {
    $("#yearLevel").prop("disabled", false);
  });

  $("#yearLevel").on("change", function () {
    $("#section").prop("disabled", false);
  });

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

  $("#studentNumber").on("input", function () {
    let value = $(this).val().replace(/\D/g, "");
    if (value.length > 9) {
      value = value.slice(0, 9);
    }
    $(this).val(value);
  });

  $("#saveProfileBtn").on("click", function () {
    const firstName = $("#firstName").val().trim();
    const lastName = $("#lastName").val().trim();
    const middleName = $("#middleName").val().trim();
    const studentNumber = $("#studentNumber").val().trim();
    const contactNumber = $("#contactNumber").val().trim();
    const homeAddress = $("#homeAddress").val().trim();
    const emergencyContactName = $("#emergencyContactName").val().trim();
    const emergencyContactNumber = $("#emergencyContactNumber").val().trim();
    const program = $("#program").val();
    const yearLevel = $("#yearLevel").val();
    const section = $("#section").val();
    const ProfilePhoto = $("#adminProfilePhoto").attr("src");

    if (!firstName || !lastName || !studentNumber || !contactNumber || !homeAddress || !emergencyContactName || !emergencyContactNumber || !program || !yearLevel || !section) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    if (contactNumber.length < 11 || contactNumber.length > 11) {
      ToastVersion(swalTheme, "Contact number must be exactly 11 digits.", "warning", 3000);
      return;
    }

    if (studentNumber.length < 9 || studentNumber.length > 9) {
      ToastVersion(swalTheme, "Student number must be exactly 9 digits.", "warning", 3000);
      return;
    }

    if (emergencyContactNumber.length < 11 || emergencyContactNumber.length > 11) {
      ToastVersion(swalTheme, "Emergency contact number must be exactly 11 digits.", "warning", 3000);
      return;
    }

    const sectionPattern = /^[A-Z]{1,3}$/;
    if (!sectionPattern.test(section)) {
      ToastVersion(swalTheme, "Section must be 1 to 3 uppercase letters (A-Z).", "warning", 3000);
      return;
    }
    if (homeAddress.length > 255) {
      ToastVersion(swalTheme, "Home address must not exceed 255 characters.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/saveProfile_Students",
      method: "POST",
      timeout: 5000,
      data: {
        action: "save_profile_data",
        firstName: firstName,
        lastName: lastName,
        middleName: middleName,
        studentNumber: studentNumber,
        contactNumber: contactNumber,
        homeAddress: homeAddress,
        emergencyContactName: emergencyContactName,
        emergencyContactNumber: emergencyContactNumber,
        program: program,
        yearLevel: yearLevel,
        section: section,
        profilePhoto: ProfilePhoto,
      },
      success: function (response) {
        if (response.status === "success") {
          if (enableChangePassword) {
            window.location.href = "../../../Src/Pages/ChangePassword";
            return;
          }

          if (response.data.hasSubmittedRequirements) {
            window.location.href = "../../../Src/Pages/Students/Requirements";
            return;
          }

          window.location.href = "../../../Src/Pages/Students/StudentsDashboard";
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
        } else {
          ToastVersion(swalTheme, "An error occurred while saving profile data. Please try again.", "error", 3000);
        }
      },
    });
  });

  const profileEditState = history.state && history.state.profileEdit;

  if (!action && !userUuid && profileEditState?.uuid) {
    window.location.replace(`${window.location.pathname}?action=edit&uuid=${encodeURIComponent(profileEditState.uuid)}`);
    return;
  }

  if (action === "edit" && userUuid) {
    const persistEditState = () => {
      history.replaceState({ profileEdit: { uuid: userUuid } }, "", window.location.pathname);
    };

    $(document).one("ajaxComplete.profileEditState", function (_event, _xhr, settings) {
      if (settings?.url && settings.url.includes("academic_info_function")) {
        persistEditState();
      }
    });
  }

  $("#backBtn").on("click", function () {
    window.history.back();
  });
});
