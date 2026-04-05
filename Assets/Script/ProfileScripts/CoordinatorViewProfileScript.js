import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { signOut, DashboardEsentialElements } from "../DashboardScripts/CoordinatorDashboardScript.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

$("#pageLoader").fadeIn(2000);

function fetchProfileData(uuid) {
  $.ajax({
    url: "../../../Assets/api/coordinator_profile_functions",
    method: "POST",
    data: { action: "fetch_profile_data", uuid: uuid },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const data = response.data;
        
        $("#FullName").text(data.profile.full_name);
        if (data.profile.profile_path) {
          $("#ProfilePicture").attr("src", "../../../" + data.profile.profile_path);
        } else {
            $("#ProfilePicture").attr("src", "https://placehold.co/64x64/C1C1C1/000000/png?text=" + data.profile.initials + "&font=poppins");
        }
        $("#Department").text(data.profile.department);

        const isActive = data.profile.is_active === 1;
        $("#Status").text(isActive ? "Active" : "Inactive");
        const badgeClass = isActive ? "bg-success-subtle text-success-emphasis border-success-subtle" : "bg-danger-subtle text-danger-emphasis border-danger-subtle";
        $("#StatusBadge").attr("class", "badge " + badgeClass);
        $("#EmployeeID").text(data.profile.employee_id || "Unassigned");

        $("#StudentCount").text(data.stats.total_students);
        $("#activeBatch").text(data.stats.batch_label + " Semester" || "No active batch");
        $("#lastLogin").text(data.profile.last_login || "N/A");
        $("#PIEmployeeID").text(data.profile.employee_id || "Unassigned");
        $("#PIFullName").text(data.profile.full_name || "N/A");
        $("#PIDepartment").text(data.profile.department || "N/A");
        $("#PIMobileNumber").text(data.profile.mobile || "N/A");
        $("#PIAccountCreated").text(data.profile.created_at || "N/A");
        $("#BatchInfo").text(data.stats.batch_label + " Semester" || "No active batch");

        if (data.students && data.students.length > 0) {
            const studentList = $("#studentList");
            studentList.empty();
            data.students.forEach(student => {
              const status = [
                { label: "Active", class: "bg-success-subtle text-success-emphasis border-success-subtle" },
                { label: "Inactive", class: "bg-danger-subtle text-danger-emphasis border-danger-subtle" },
                { label: "Never Logged In", class: "bg-secondary-subtle text-secondary-emphasis border-secondary-subtle" },
                { label: "Unknown", class: "bg-warning-subtle text-warning-emphasis border-warning-subtle" }
              ]
                const studentItem = `
                    <li class="list-group-item bg-transparent">
                        <div class="hstack">
                            <img src="${student.profile_path ? '../../../' + student.profile_path : 'https://placehold.co/40x40/C1C1C1/000000/png?text=' + student.initials + '&font=poppins'}"
                                alt="profile picture" class="rounded-circle me-3"
                                style="width: 40px; height: 40px;">
                            <div>
                                <div class="fw-bold">${student.full_name}</div>
                                <small class="text-muted">${student.student_number} - ${student.program_code}, ${student.year_label}</small>
                            </div>
                            <span class="badge ${status.find(s => s.label === student.status_label)?.class || 'bg-secondary-subtle text-secondary-emphasis border-secondary-subtle'} rounded-pill ms-auto align-self-start">${student.status_label}</span>
                        </div>
                    </li>
                `;
                studentList.append(studentItem);
            });
        } else {
            $("#studentList").html('<li class="list-group-item bg-transparent"><div class="text-center text-muted">No students assigned yet.</div></li>');
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
  const userUuid = $("body").data("uuid");

  DashboardEsentialElements(userUuid);
  fetchProfileData(userUuid);
  signOut();

  $("#changepasswordBtn").on("click", function () {
    const uuid = $("body").data("uuid");
    window.location.href = `../../../Src/Pages/ChangePassword?action=voluntary&uuid=${uuid}`;
  });

  $("#editprofileBtn").on("click", function () {
    const uuid = $("body").data("uuid");
    window.location.href = `../../../Src/Pages/Coordinator/Coordinator_Profile.php?action=edit&uuid=${uuid}`;
  });
});
