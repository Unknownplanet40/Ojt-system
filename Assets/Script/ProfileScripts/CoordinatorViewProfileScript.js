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

function viewCoordinatorProfile() {
  $.ajax({
    url: "../../../process/profile/get_coordinator_profile_view",
    type: "POST",
    data: {
      csrf_token: csrfToken,
    },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const p = response.profile;
        const b = response.activeBatch;
        const students = response.students;

        // Header Info
        $("#ProfilePicture").attr("src", response.profileImage);
        $("#FullName").text(p.full_name);
        $("#Department").text(p.department);
        $("#Status").text(p.status_label);
        $("#EmployeeID").text(p.employee_id);

        // Stats
        $("#StudentCount").text(p.assigned_students);
        $("#activeBatch").text(b ? `AY ${b.school_year} ${b.semester} Sem` : "No Active Batch");
        $("#lastLogin").text(p.last_login || "Never");

        // Personal Information Section
        $("#PIEmployeeID").text(p.employee_id);
        $("#PIFullName").text(p.full_name);
        $("#PIDepartment").text(p.department);
        $("#PIMobileNumber").text(p.mobile);
        $("#PIAccountCreated").text(p.created_at);

        // Student List
        $("#BatchInfo").text(b ? `AY ${b.school_year} ${b.semester} Sem` : "N/A");
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
                       class="rounded-circle border border-light-subtle shadow-sm"
                       style="width: 40px; height: 40px;">
                  <div class="flex-grow-1 min-w-0">
                    <h6 class="mb-0 text-truncate">${s.full_name}</h6>
                    <small class="text-muted d-block text-truncate">${s.program_code} &bull; ${s.student_number}</small>
                  </div>
                  <span class="badge ${s.account_status === 'active' ? 'bg-success' : 'bg-secondary'} rounded-pill">
                    ${s.status_label}
                  </span>
                </div>
              </li>
            `;
            listContainer.append(studentItem);
          });
        } else {
          listContainer.append('<li class="list-group-item bg-transparent text-center text-muted py-4">No students assigned yet.</li>');
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
  viewCoordinatorProfile();

  $("#editprofileBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/Coordinator/Coordinator_Profile?action=edit";
  });

  $("#changepasswordBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/ChangePassword";
  });
});
