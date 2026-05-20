import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";

if (!csrfToken || !userRole || userRole !== "supervisor") {
  window.location.href = "../../../Src/Pages/Login";
}

function viewSupervisorProfile() {
  $.ajax({
    url: "../../../process/profile/get_supervisor_profile_view",
    type: "POST",
    data: {
      csrf_token: csrfToken,
    },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const p = response.profile;
        const students = response.students;

        
        $("#ProfilePicture").attr("src", response.profileImage);
        $("#FullName").text(p.full_name);
        $("#Company").text(p.company_name || 'No Company Linked');
        $("#Status").text(p.status_label);
        $("#RoleBadge").text("Supervisor");

        
        $("#StudentCount").text(response.studentCount);
        $("#CompanyNameCard").text(p.company_name || "N/A");
        $("#lastLogin").text(p.last_login || "Never");

        
        $("#PIFullName").text(p.full_name);
        $("#PICompany").text(p.company_name || "N/A");
        $("#PIMobileNumber").text(p.mobile || 'N/A');
        $("#PIAccountCreated").text(p.created_at_label);
        $("#Email").text(p.email);

        
        const listContainer = $("#studentList");
        listContainer.empty();

        if (students.length > 0) {
          students.forEach((s) => {
            const studentImg = s.profile_name ? `../../../Assets/Images/profiles/${s.profile_name}` : `https://placehold.co/40x40/C1C1C1/000000/png?text=${s.initials}&font=poppins`;
            const studentItem = `
              <li class="list-group-item bg-transparent px-0 py-3 border-light border-opacity-10">
                <div class="d-flex align-items-center gap-3">
                  <img src="${studentImg}" 
                       alt="${s.full_name}" 
                       class="rounded-circle border border-light-subtle shadow-sm object-fit-cover"
                       style="width: 40px; height: 40px;">
                  <div class="flex-grow-1 min-w-0">
                    <h6 class="mb-0 text-truncate">${s.full_name}</h6>
                    <small class="text-body-secondary d-block text-truncate">${s.program} &bull; ${s.student_number}</small>
                  </div>
                  <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill">
                    Active
                  </span>
                </div>
              </li>
            `;
            listContainer.append(studentItem);
          });
        } else {
          listContainer.append('<li class="list-group-item bg-transparent text-center text-body-secondary py-4">No active students under your supervision.</li>');
        }
      } else {
        Errors(response.message);
      }
    },
    error: function () {
      Errors("An error occurred while fetching profile data.");
    },
    complete: function () {
        $("#pageLoader").fadeOut();
    }
  });
}

$(document).ready(function () {
  viewSupervisorProfile();

  $("#editprofileBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/Supervisor/Supervisor_Profile.php?action=edit";
  });

  $("#changepasswordBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/ChangePassword";
  });
});
