import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";

if (!csrfToken || !userRole || userRole !== "admin") {
  window.location.href = "../../../Src/Pages/Login";
}

function viewAdminProfile() {
  $.ajax({
    url: "../../../process/profile/get_admin_profile_view",
    type: "POST",
    data: {
      csrf_token: csrfToken,
    },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const p = response.profile;
        const s = response.stats;
        const logs = response.logs;

        // Header Info
        $("#ProfilePicture").attr("src", response.profileImage);
        $("#FullName").text(p.full_name);
        $("#EmailHeader").text(p.email);
        $("#Status").text(p.status_label);
        $("#RoleBadge").text("Administrator");

        // Stats Cards
        $("#TotalUsers").text(s.total_users);
        $("#TotalStudents").text(s.total_students);
        $("#TotalCompanies").text(s.total_companies);

        // Personal Information Section
        $("#PIFullName").text(p.full_name);
        $("#PIEmail").text(p.email);
        $("#PIEmployeeID").text(p.employee_id || 'N/A');
        $("#PIContact").text(p.contact_number || 'N/A');
        $("#PIAccountCreated").text(p.created_at_label);
        $("#lastLogin").text(p.last_login_label || "Never");

        // Audit Logs List
        const listContainer = $("#auditLogsList");
        listContainer.empty();

        if (logs.length > 0) {
          logs.forEach((log) => {
            const logItem = `
              <li class="list-group-item bg-transparent px-0 py-3 border-body border-opacity-10">
                <div class="d-flex align-items-start gap-3">
                  <div class="rounded-circle bg-body-secondary text-primary d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; min-width: 32px;">
                    <i class="bi bi-activity small"></i>
                  </div>
                  <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <h6 class="mb-0 small fw-bold">${log.event_type.replace(/_/g, ' ').toUpperCase()}</h6>
                      <small class="text-body-secondary" style="font-size: 0.7rem;">${log.time_ago}</small>
                    </div>
                    <p class="text-body-secondary mb-0 small text-truncate">${log.description}</p>
                  </div>
                </div>
              </li>
            `;
            listContainer.append(logItem);
          });
        } else {
          listContainer.append('<li class="list-group-item bg-transparent text-center text-body-secondary py-4">No recent activity logs.</li>');
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
  viewAdminProfile();

  $("#editprofileBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/Admin/Admin_Profile?action=edit";
  });

  $("#changepasswordBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/ChangePassword";
  });
});
