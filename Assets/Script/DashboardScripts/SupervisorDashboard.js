import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

const csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
const userUUID = $('meta[name="user-UUID"]').attr('content') || '';
const userRole = $('meta[name="user-Role"]').attr('content') || '';

if (!csrfToken || !userUUID || !userRole || userRole !== 'supervisor') {
  window.location.href = '../../../Src/Pages/Login';
}

function fetchProfile() {
  $.ajax({
    url: '../../../process/profile/get_profile',
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken },
    success: function (response) {
      if (response.status === 'success') {
        const profile = response.profile;
        const activeBatch = response.activeBatch;
        if (!profile.profile_name) {
          const initials = profile.initials || 'NA';
          $('#navProfilePhoto').attr('src', `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
          $('#dropdownProfilePhoto').attr('src', `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
        } else {
          $('#navProfilePhoto').attr('src', '../../../Assets/Images/profiles/' + profile.profile_name);
          $('#dropdownProfilePhoto').attr('src', '../../../Assets/Images/profiles/' + profile.profile_name);
        }
        $('#userName').text(profile.first_name + ' ' + profile.last_name);
        $('#currentSemester').text(activeBatch ? `${activeBatch.semester} Semester` : 'No active batch');
      } else {
        ToastVersion(swalTheme, response.message, 'error', 3000, 'top-end');
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function signOut() {
  $.ajax({
    url: '../../../process/auth/logout',
    method: 'POST',
    dataType: 'json',
    data: { csrf_token: csrfToken },
    beforeSend: function () {
      ModalVersion(swalTheme, 'Signing Out', 'Please wait while we sign you out...', 'info', 0, 'center');
    },
    success: function (response) {
      if (response.status === 'success') {
        Swal.close();
        window.location.href = response.redirect_url;
      } else {
        ToastVersion(swalTheme, response.message, 'error', 3000, 'top-end');
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

$(document).ready(function () {
  fetchProfile();
  $('#signOutBtn').on('click', signOut);
  $('#pageLoader').fadeOut(500, function () {
    $(this).remove();
  });
});
