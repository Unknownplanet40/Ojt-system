import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

$("#pageLoader").fadeIn(2000);

function loadRecentActivity() {
  const recentActivityList = $("#recentActivityList");
  recentActivityList.empty();
  $.ajax({
    url: "../../../Assets/api/admin_dashboard_queries",
    method: "GET",
    data: { type: "recent_activity" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        response.data.forEach((activity) => {
          const iconClass =
            activity.event_type === "login_success" ? "bi-person-check text-success" : activity.event_type === "login_failure" ? "bi-person-x text-danger" : "bi-info-circle text-secondary";
          const listItem = `<li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi ${iconClass} me-3 fs-4"></i>
              <div>
              <div>${activity.description}</div>
              <small class="text-muted">${activity.actor_name} (${activity.actor_role.charAt(0).toUpperCase() + activity.actor_role.slice(1)}) - ${activity.time_ago}</small>
              </div>
            </div>
            </li>`;
          recentActivityList.append(listItem);
        });
      } else {
        ToastVersion(swalTheme, "Failed to load recent activity. Please try again.", "error", 3000);
        const listItem = `<li class="list-group-item bg-transparent">
          <div class="hstack">
            <i class="bi bi-exclamation-triangle text-warning me-3 fs-4"></i>
            <div>
              <div>Unable to load recent activity.</div>
              <small class="text-muted">Please refresh the page or try again later.</small>
            </div>
          </div>
        </li>`;
        recentActivityList.append(listItem);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching recent activity. Please try again.", "error", 3000);
      }
      const listItem = `<li class="list-group-item bg-transparent">
        <div class="hstack">
          <i class="bi bi-exclamation-triangle text-warning me-3 fs-4"></i>
          <div>
            <div>Unable to load recent activity.</div>
            <small class="text-muted">Please refresh the page or try again later.</small>
          </div>
        </div>
      </li>`;
      recentActivityList.append(listItem);
    },
  });
}

function loadRecentAccountActivity() {
  const recentAccountsList = $("#recentAccountsList");
  recentAccountsList.empty();
  $.ajax({
    url: "../../../Assets/api/admin_dashboard_queries",
    method: "GET",
    data: { type: "recent_accounts" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        response.data.forEach((account) => {
          const statusBadgeClass =
            account.status === "active"
              ? "bg-success-subtle text-success-emphasis"
              : account.status === "inactive"
                ? "bg-secondary-subtle text-secondary-emphasis"
                : "bg-warning-subtle text-warning-emphasis";
          const ProfileColor = account.status_label.no_profile ? "\/031633/6ea8fe" : account.status === "active" ? "\/0f5132/75b798" : account.status === "inactive" ? "\/343a40/f8f9fa" : "\/483a0f/c7983d";

          const statusBadgeText = account.status_label;
          const listItem = `<li class="list-group-item bg-transparent">
            <div class="hstack">
              <img src="https://placehold.co/600x400${ProfileColor}?text=${account.initials}&font=poppins" alt="Profile" class="rounded-circle me-3" style="width: 40px; height: 40px; object-fit: cover" />
              <div class="vstack">
          <span>${account.full_name || account.email}</span>
          <small class="text-muted" style="font-size: 0.7em">Added ${account.added_on}</small>
              </div>
              <span class="badge ${statusBadgeClass} rounded-pill ms-auto">${statusBadgeText}</span>
            </div>
          </li>`;
          recentAccountsList.append(listItem);
        });
      } else {
        ToastVersion(swalTheme, "Failed to load recent account activity. Please try again.", "error", 3000);
        const listItem = `<li class="list-group-item bg-transparent">
          <div class="hstack">
            <div>
              <div>Failed to load recent account activity.</div>
            </div>
          </div>
        </li>`;
        recentAccountsList.append(listItem);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching recent account activity. Please try again.", "error", 3000);
      }

      const listItem = `<li class="list-group-item bg-transparent">
        <div class="hstack">
          <div>
            <div>Failed to load recent account activity.</div>
          </div>
        </div>
      </li>`;
      recentAccountsList.append(listItem);
    },
  });
}

function loadUserbyRole() {
  $("#studentProgressBar").css("width", "0%");
  $("#supervisorProgressBar").css("width", "0%");
  $("#coordinatorProgressBar").css("width", "0%");
  $("#adminProgressBar").css("width", "0%");

  $.ajax({
    url: "../../../Assets/api/admin_dashboard_queries",
    method: "GET",
    data: { type: "users_by_role" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const breakdown = response.data.breakdown;
        breakdown.forEach((roleData) => {
          switch (roleData.role) {
            case "student":
              $("#studentProgressBar")
                .css("width", roleData.percentage + "%")
                .css("background-color", roleData.color);
              $("#studentsCount").text(roleData.total);
              break;
            case "supervisor":
              $("#supervisorProgressBar")
                .css("width", roleData.percentage + "%")
                .css("background-color", roleData.color);
              $("#supervisorCount").text(roleData.total);
              break;
            case "coordinator":
              $("#coordinatorProgressBar")
                .css("width", roleData.percentage + "%")
                .css("background-color", roleData.color);
              $("#coordinatorCount").text(roleData.total);
              break;
            case "admin":
              $("#adminProgressBar")
                .css("width", roleData.percentage + "%")
                .css("background-color", roleData.color);
              $("#adminCount").text(roleData.total);
              break;
          }
        });
      } else {
        ToastVersion(swalTheme, "Failed to load user count by role. Please try again.", "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching user count by role. Please try again.", "error", 3000);
      }
    },
  });
}

function loadNeedsAttention() {
  const needsAttentionList = $("#needsAttentionList");
  needsAttentionList.empty();

  $.ajax({
    url: "../../../Assets/api/admin_dashboard_queries",
    method: "GET",
    data: { type: "alerts" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        response.data.forEach((alert) => {
          const alertIconClass = alert.type === "info" ? "bi-info-circle text-info" : alert.type === "warning" ? "bi-exclamation-triangle text-warning" : "bi-x-circle text-danger";
          const listItem = `<li class="list-group-item bg-transparent">
                        <div class="hstack">
                            <i class="bi ${alertIconClass} me-3 fs-4"></i>
                            <div>
                                <div>${alert.message}</div>
                                <small class="text-muted" style="font-size: 0.7em"><a href="${alert.link}" class="text-decoration-none">View details</a></small>
                            </div>
                        </div>
                    </li>`;
          needsAttentionList.append(listItem);
        });
      } else {
        ToastVersion(swalTheme, "Failed to load alerts. Please try again.", "error", 3000, "8");
        const listItem = `<li class="list-group-item bg-transparent">
                    <div class="hstack">
                        <i class="bi bi-exclamation-triangle text-warning me-3 fs-4"></i>
                        <div>
                            <div>Unable to load alerts.</div>
                            <small class="text-muted">Please refresh the page or try again later.</small>
                        </div>
                    </div>
                </li>`;
        needsAttentionList.append(listItem);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000, "8");
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching alerts. Please try again.", "error", 3000, "8");
      }
      const listItem = `<li class="list-group-item bg-transparent">
                <div class="hstack">
                    <i class="bi bi-exclamation-triangle text-warning me-3 fs-4"></i>
                    <div>
                        <div>Unable to load alerts.</div>
                        <small class="text-muted">Please refresh the page or try again later.</small>
                    </div>
                </div>
            </li>`;
      needsAttentionList.append(listItem);
    },
  });
}

function loadDashboardStats() {
  const totalUsersCount = $("#totalUsersCounts");
  const totalUsersStatus = $("#totalUsersStatus");
  const studentsCount = $("#studentsCounts");
  const studentStatus = $("#studentStatus");
  const coordinatorCount = $("#coordinatorCounts");
  const coordinatorStatus = $("#coordinatorStatus");
  const companiesCount = $("#companiesCounts");
  const companiesStatus = $("#companiesStatus");

  $.ajax({
    url: "../../../Assets/api/admin_dashboard_queries",
    method: "GET",
    data: { type: "stat_cards" },
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        const stats = response.data;
        totalUsersCount.text(stats.total_users);
        $("#totalUsersBadge").text(stats.total_users);
        studentsCount.text(stats.total_students);
        coordinatorCount.text(stats.total_coordinators);
        companiesCount.text(stats.total_companies);
        totalUsersStatus.text("All roles combined.");
        if (stats.students_no_profile > 0) {
          studentStatus.text(`${stats.students_no_profile} Incomplete Profiles`).removeClass("text-success").addClass("text-warning");
        } else {
          studentStatus.text("All Complete").removeClass("text-warning").addClass("text-success");
        }
        coordinatorStatus.text("All Active");
        if (stats.moa_expiring > 0) {
          companiesStatus.text(`${stats.moa_expiring} MOA Expiring Soon`).removeClass("text-success").addClass("text-warning");
        } else {
          companiesStatus.text("All MOAs Valid").removeClass("text-warning").addClass("text-success");
        }
      } else {
        ToastVersion(swalTheme, "Failed to load dashboard stats. Please try again.", "error", 3000);
      }
    },
    error: function (xhr, status, error) {
      if (status === "timeout") {
        ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
      } else {
        ToastVersion(swalTheme, "An error occurred while fetching dashboard stats. Please try again.", "error", 3000);
      }
    },
  });
}

export function fetchUserData() {
    $.ajax({
      url: "../../../Assets/api/GET_userData",
      method: "GET",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          $("#userName").text(response.data.first_name + " " + response.data.middle_name.charAt(0) + ". " + response.data.last_name);
          $("#welcomeUserName").text(response.data.first_name);
          $("#dropdownMenuName").text(response.data.first_name + " " + response.data.last_name);
          switch (response.data.role) {
            case "admin":
              $("#userRole").text("Administrator");
              break;
            case "supervisor":
              $("#userRole").text("Supervisor");
              break;
            case "student":
              $("#userRole").text("Student");
              break;
            case "coordinator":
              $("#userRole").text("Coordinator");
              break;
            default:
              $("#userRole").text("User");
          }
          if (response.data.profile_path) {
            $("#navProfilePhoto").attr("src", "../../../" + response.data.profile_path);
            $("#dropdownProfilePhoto").attr("src", "../../../" + response.data.profile_path);
          } else {
            $("#navProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
            $("#dropdownProfilePhoto").attr("src", "https://placehold.co/30x30?text=No+Photo");
          }
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
        } else {
          ToastVersion(swalTheme, "An error occurred while fetching user data. Please try again.", "error", 3000);
        }
      },
    });
}

$(document).ready(function () {
  const userUuid = $("body").data("uuid");

  loadRecentActivity();
  loadRecentAccountActivity();
  loadUserbyRole();
  loadNeedsAttention();
  loadDashboardStats();
  fetchUserData();

  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  $("#navbarSideCollapse").on("click", function () {
    $(".offcanvas-collapse").toggleClass("open");
    if ($("#navbarSideCollapse i").hasClass("bi-list")) {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-list").addClass("bi-x").fadeIn(200);
      });
    } else {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-x").addClass("bi-list").fadeIn(200);
      });
    }
  });

  $(window).on("resize", function () {
    if ($(".offcanvas-collapse").hasClass("open")) {
      $(".offcanvas-collapse").removeClass("open");
      $("#navbarSideCollapse i").removeClass("bi-x").addClass("bi-list");
    }
    if ($(window).width() < 360) {
      window.resizeTo(360, 800);
    }
  });

  $(function () {
    $(".nav-link").on("click", function () {
      if (!$(this).hasClass("dropdown-toggle")) {
        $(".nav-link").removeClass("active");
        $(this).addClass("active");
      }
    });
  });

  $("#pageLoader").fadeOut(1000, function () {
    $(this).remove();
    $("#mainContent").fadeIn(1000, function () {
      $(this).removeClass("d-none");
    });
  });

  if (!userUuid) {
    window.location.href = "../../../Src/Pages/Login";
    return;
  }

  $("#quickCreateBatch").on("click", function () {
    window.location.href = "../Admin/batches?action=create";
  });

});
