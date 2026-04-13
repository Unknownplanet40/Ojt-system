import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();

$("#pageLoader").fadeIn(2000);

const ActivityIcons = {
  other: "bi-activity",
  profile_completed: "bi-person-check",
  account_created: "bi-person-plus",
  account_deactivated: "bi-person-x",
  account_activated: "bi-person-check",
  password_changed: "bi-key",
  password_reset: "bi-key-fill",
  role_changed: "bi-shield-lock",
  login_success: "bi-box-arrow-in-right",
  login_failed: "bi-box-arrow-in-right text-danger",
  logout: "bi-box-arrow-right",
  application_submitted: "bi-file-earmark-text",
  application_approved: "bi-file-earmark-check",
  application_rejected: "bi-file-earmark-x",
  endorsement_issued: "bi-award",
  dtr_submitted: "bi-journal-text",
  dtr_approved: "bi-journal-check",
  dtr_rejected: "bi-journal-x",
  journal_submitted: "bi-journal-text",
  evaluation_submitted: "bi-clipboard-check",
  document_uploaded: "bi-cloud-upload",
  company_added: "bi-building",
  company_updated: "bi-building-up",
  moa_uploaded: "bi-file-earmark-arrow-up",
  batch_created: "bi-diagram-3",
  batch_closed: "bi-diagram-3-fill",
  program_created: "bi-collection",
  program_updated: "bi-collection-fill",
  program_disabled: "bi-collection-play",
  program_enabled: "bi-collection-play-fill",
};

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
        $("#activityCount").text(response.data.length);
        response.data.forEach((activity) => {
          const iconClass = ActivityIcons[activity.event_type] || "bi-activity";
          const listItem = `<li class="list-group-item bg-transparent">
            <div class="hstack">
              <i class="bi ${iconClass} text-${activity.icon_type} me-3"></i>
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
          const ProfileColor = account.status_label.no_profile
            ? "\/031633/6ea8fe"
            : account.status === "active"
              ? "\/0f5132/75b798"
              : account.status === "inactive"
                ? "\/343a40/f8f9fa"
                : "\/483a0f/c7983d";

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
                            <div class="vstack">
                                <div>${alert.message}</div>
                                <small class="text-muted" style="font-size: 0.7em">${alert.description || ""}</small>
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
        totalUsersStatus
          .text(stats.total_users > 0 ? "All systems operational" : "No users found")
          .removeClass("text-success text-danger")
          .addClass(stats.total_users > 0 ? "text-success" : "text-danger");
        studentsCount.text(stats.total_students);
        studentStatus
          .text(stats.students_no_profile > 0 ? `${stats.students_no_profile} students missing profiles` : "All students have profiles")
          .removeClass("text-success text-warning")
          .addClass(stats.students_no_profile > 0 ? "text-warning" : "text-success");
        coordinatorCount.text(stats.total_coordinators);
        coordinatorStatus
          .text(stats.total_coordinators > 0 ? "Coordinators active" : "No coordinators found")
          .removeClass("text-success text-danger")
          .addClass(stats.total_coordinators > 0 ? "text-success" : "text-danger");
        companiesCount.text(stats.total_companies);
        if (stats.moa_expiring > 0) {
          companiesStatus.addClass("text-warning").removeClass("text-success text-danger").text(`${stats.moa_expiring} MOAs expiring soon`);
        } else {
          companiesStatus
            .removeClass("text-warning")
            .addClass(stats.active_companies > 0 ? "text-success" : "text-danger")
            .text(stats.active_companies > 0 ? `${stats.active_companies} active companies` : "No active companies");
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

export function signOut() {
  $("#signOutBtn").on("click", function (e) {
    $.ajax({
      url: "../../../Assets/api/logout",
      method: "POST",
      timeout: 5000,
      success: function (response) {
        if (response.status === "success") {
          window.location.href = "../../../";
        } else {
          ToastVersion(swalTheme, "Failed to sign out. Please try again.", "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        if (status === "timeout") {
          ToastVersion(swalTheme, "Request timed out. Please try again.", "error", 3000);
        } else {
          ToastVersion(swalTheme, "An error occurred while signing out. Please try again.", "error", 3000);
        }
      },
    });
  });
}

export function DashbopardEsentialElements(userUuid) {
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
}

$(document).ready(function () {
  const userUuid = $("body").data("uuid");

  loadRecentActivity();
  loadRecentAccountActivity();
  loadUserbyRole();
  loadNeedsAttention();
  loadDashboardStats();
  fetchUserData();
  DashbopardEsentialElements(userUuid);
  signOut();

  $("#quickCreateBatch").on("click", function () {
    window.location.href = "../Admin/batches?action=create";
  });
});
