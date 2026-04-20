import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "default", "fast");
let letPageLoad = true;
let latestNeedsAttention = [];

function formatLastUpdated(date = new Date()) {
  return date.toLocaleString("en-PH", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function setNeedsAttentionRefreshState(isLoading = false) {
  const $btn = $("#refreshNeedsAttentionBtn");
  if (!$btn.length) return;

  if (isLoading) {
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Refreshing');
  } else {
    $btn.prop("disabled", false).html('<i class="bi bi-arrow-clockwise me-1"></i>Refresh');
  }
}

function updateNeedsAttentionTimestamp(date = new Date()) {
  const $stamp = $("#needsAttentionLastUpdated");
  if (!$stamp.length) return;
  $stamp.text(`Last updated: ${formatLastUpdated(date)}`);
}

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userUUID = $('meta[name="user-UUID"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";
const Onlypage = $("body").data("only") || "";

if (!csrfToken || !userUUID || !userRole || userRole !== "admin") {
  window.location.href = "../../../Src/Pages/Login";
  letPageLoad = false;
}

function fetchProfile() {
  $.ajax({
    url: "../../../process/profile/get_profile",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    success: function (response) {
      if (response.status === "success") {
        const profile = response.profile;
        const activeBatch = response.activeBatch;
        $("#activebatchthissemester").text(activeBatch ? `${activeBatch.label}` : "No active batch this semester");
        $("#activebatchthissemester").attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");
        $("#StudactiveBatch")
          .val(activeBatch ? `${activeBatch.label}` : "No active batch this semester")
          .attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");
        $("#editActiveBatch")
          .val(activeBatch ? `${activeBatch.label}` : "No active batch this semester")
          .attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");

        if (profile.user_uuid !== userUUID) {
          ToastVersion(swalTheme, "Profile data mismatch. Please refresh the page.", "error", 3000, "top-end");
          SignOut();
          return;
        }

        if (!profile.profile_name) {
          const initials = profile.initials || "NA";
          $("#navProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
          $("#dropdownProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
        } else {
          $("#navProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
          $("#dropdownProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
        }

        $("#userName").text(profile.first_name + " " + profile.last_name);
        $("#welcomeUserName").text(profile.first_name);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },

    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function SignOut() {
  $.ajax({
    url: "../../../process/auth/logout",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      ModalVersion(swalTheme, "Signing Out", "Please wait while we sign you out...", "info", 0, "center");
    },
    success: function (response) {
      if (response.status === "success") {
        Swal.close();
        window.location.href = response.redirect_url;
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function DashboardEsentialElements() {
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

  $("#signOutBtn").on("click", function (e) {
    e.preventDefault();
    SignOut();
  });
}

function tableDropdown() {
  const MENU_Z_INDEX = 1030;
  const VIEWPORT_PADDING = 8;

  const ensureDropdownLink = (toggleBtn, menu) => {
    if (!toggleBtn.length || !menu.length) return;

    let dropdownId = toggleBtn.attr("data-dropdown-id") || menu.attr("data-dropdown-id");
    if (!dropdownId) {
      dropdownId = `dd-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
    }

    toggleBtn.attr("data-dropdown-id", dropdownId);
    menu.attr("data-dropdown-id", dropdownId);
  };

  const resolveMenuForToggle = (toggleBtn) => {
    let menu = toggleBtn.next(".customDropdown");
    if (menu.length) {
      ensureDropdownLink(toggleBtn, menu);
      return menu;
    }

    const dropdownId = toggleBtn.attr("data-dropdown-id");
    if (!dropdownId) return $();

    menu = $(`.customDropdown[data-dropdown-id="${dropdownId}"]`).first();
    return menu;
  };

  const restoreMenuToOrigin = (menu) => {
    const placeholder = menu.data("dropdown-placeholder");
    if (placeholder && placeholder.length) {
      placeholder.before(menu);
      placeholder.remove();
    } else {
      const originParent = menu.data("dropdown-origin-parent");
      if (originParent && originParent.length) {
        originParent.append(menu);
      }
    }

    menu
      .css({
        position: "",
        top: "",
        left: "",
        right: "",
        bottom: "",
        zIndex: "",
        visibility: "",
        display: "none",
      })
      .removeData("dropdown-placeholder")
      .removeData("dropdown-origin-parent");
  };

  const positionMenuNearButton = (menu, toggleBtn) => {
    const btnRect = toggleBtn[0].getBoundingClientRect();
    const menuHeight = menu.outerHeight() || 0;
    const menuWidth = menu.outerWidth() || 170;

    const spaceBelow = window.innerHeight - btnRect.bottom;
    const spaceAbove = btnRect.top;
    const openUp = spaceBelow < menuHeight + 8 && spaceAbove >= spaceBelow;

    const top = openUp ? Math.max(VIEWPORT_PADDING, btnRect.top - menuHeight - 4) : Math.min(window.innerHeight - menuHeight - VIEWPORT_PADDING, btnRect.bottom + 4);

    const left = Math.min(window.innerWidth - menuWidth - VIEWPORT_PADDING, Math.max(VIEWPORT_PADDING, btnRect.right - menuWidth));

    menu.css({
      position: "fixed",
      top: `${top}px`,
      left: `${left}px`,
      right: "auto",
      bottom: "auto",
      zIndex: String(MENU_Z_INDEX),
      visibility: "visible",
      display: "block",
    });
  };

  const resetDropdownLayering = () => {
    $(".customDropdown").each(function () {
      restoreMenuToOrigin($(this));
    });

    $(".customDropdown").closest(".table-responsive").css({ overflowY: "" });
    $(".customDropdown").closest("td, th").css({ position: "", zIndex: "" });
  };

  $(document).on("click", function (e) {
    const target = $(e.target);
    const toggleBtn = target.closest('[data-toggle="dropdown"]');
    const dropdownItem = target.closest(".dropdown-item");

    if (toggleBtn.length) {
      const menu = resolveMenuForToggle(toggleBtn);
      if (!menu.length) {
        resetDropdownLayering();
        return;
      }

      const parentCell = toggleBtn.closest("td, th");
      const responsiveHost = toggleBtn.closest(".table-responsive");

      // reset all previous open dropdown layers first
      $(".customDropdown").not(menu).hide().closest("td, th").css({ position: "", zIndex: "" });

      const willOpen = !menu.is(":visible");
      if (!willOpen) {
        resetDropdownLayering();
        return;
      }

      if (responsiveHost.length) {
        // prevent clipping inside Bootstrap table-responsive wrappers
        responsiveHost.css({ overflowY: "visible" });
      }

      // elevate current table cell above neighboring rows
      parentCell.css({ position: "relative", zIndex: "20" });

      if (!menu.data("dropdown-origin-parent")) {
        menu.data("dropdown-origin-parent", menu.parent());
      }

      if (!menu.data("dropdown-placeholder")) {
        const placeholder = $('<span class="d-none dropdown-menu-placeholder"></span>');
        menu.after(placeholder);
        menu.data("dropdown-placeholder", placeholder);
      }

      $("body").append(menu);
      positionMenuNearButton(menu, toggleBtn);
    } else if (dropdownItem.length) {
      resetDropdownLayering();
    } else {
      resetDropdownLayering();
    }
  });
}

function escapeHtml(value = "") {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function renderStatCards(statCards = {}) {
  const totalUsers = Number.parseInt(statCards.total_users, 10) || 0;
  const totalStudents = Number.parseInt(statCards.total_students, 10) || 0;
  const totalCoordinators = Number.parseInt(statCards.total_coordinators, 10) || 0;
  const totalCompanies = Number.parseInt(statCards.total_companies, 10) || 0;
  const expiringMoas = Number.parseInt(statCards.expiring_moas, 10) || 0;

  $("#totalUsersCounts").text(totalUsers);
  $("#studentsCounts").text(totalStudents);
  $("#coordinatorCounts").text(totalCoordinators);
  $("#companiesCounts").text(totalCompanies);

  $("#totalUsersStatus").text(totalUsers > 0 ? "Active user accounts across all roles" : "No active users yet");
  $("#studentStatus").text(totalStudents > 0 ? `${totalStudents} active student${totalStudents > 1 ? "s" : ""} in current batch` : "No active students in current batch");
  $("#coordinatorStatus").text(totalCoordinators > 0 ? `${totalCoordinators} coordinator${totalCoordinators > 1 ? "s" : ""} currently active` : "No active coordinators yet");
  $("#companiesStatus").text(expiringMoas > 0 ? `${expiringMoas} MOA${expiringMoas > 1 ? "s" : ""} expiring within 30 days` : "All MOAs are healthy beyond 30 days");
}

function renderRoleBreakdown(roleBreakdown = {}) {
  const breakdown = Array.isArray(roleBreakdown.breakdown) ? roleBreakdown.breakdown : [];
  const byRole = {
    student: { count: 0, percentage: 0 },
    supervisor: { count: 0, percentage: 0 },
    coordinator: { count: 0, percentage: 0 },
    admin: { count: 0, percentage: 0 },
  };

  breakdown.forEach((entry) => {
    const role = String(entry.role || "").toLowerCase();
    if (byRole[role]) {
      byRole[role] = {
        count: Number.parseInt(entry.count, 10) || 0,
        percentage: Math.max(0, Math.min(100, Number.parseInt(entry.percentage, 10) || 0)),
      };
    }
  });

  $("#studentsCount").text(byRole.student.count);
  $("#supervisorCount").text(byRole.supervisor.count);
  $("#coordinatorCount").text(byRole.coordinator.count);
  $("#adminCount").text(byRole.admin.count);

  $("#studentProgressBar").css("width", `${byRole.student.percentage}%`).attr("aria-valuenow", byRole.student.percentage);
  $("#supervisorProgressBar").css("width", `${byRole.supervisor.percentage}%`).attr("aria-valuenow", byRole.supervisor.percentage);
  $("#coordinatorProgressBar").css("width", `${byRole.coordinator.percentage}%`).attr("aria-valuenow", byRole.coordinator.percentage);
  $("#adminProgressBar").css("width", `${byRole.admin.percentage}%`).attr("aria-valuenow", byRole.admin.percentage);
}

function getAlertVisuals(type = "") {
  const value = String(type || "").toLowerCase();
  if (value === "danger") {
    return {
      itemClass: "type-danger",
      iconClass: "text-danger",
      badgeClass: "text-bg-danger",
      actionClass: "btn-outline-danger",
      label: "Critical",
    };
  }

  if (value === "warning") {
    return {
      itemClass: "type-warning",
      iconClass: "text-warning",
      badgeClass: "text-bg-warning",
      actionClass: "btn-outline-warning",
      label: "High",
    };
  }

  if (value === "success") {
    return {
      itemClass: "type-success",
      iconClass: "text-success",
      badgeClass: "text-bg-success",
      actionClass: "btn-outline-success",
      label: "Healthy",
    };
  }

  return {
    itemClass: "type-info",
    iconClass: "text-info",
    badgeClass: "text-bg-info",
    actionClass: "btn-outline-info",
    label: "Medium",
  };
}

function updateNeedsAttentionHeader(alerts = []) {
  const safeAlerts = Array.isArray(alerts) ? alerts : [];
  const $status = $("#needsAttentionStatus");
  const $summary = $("#needsAttentionSummary");

  $summary.empty();

  if (safeAlerts.length === 0) {
    $status.text("All clear. No immediate blockers detected.");
    $summary.append('<span class="badge rounded-pill text-bg-success">All clear</span>');
    return;
  }

  const counts = { danger: 0, warning: 0, info: 0, success: 0 };
  safeAlerts.forEach((alert) => {
    const type = String(alert.type || "info").toLowerCase();
    if (Object.prototype.hasOwnProperty.call(counts, type)) {
      counts[type] += 1;
    } else {
      counts.info += 1;
    }
  });

  const criticalCount = counts.danger;
  const highCount = counts.warning;
  $status.text(`${safeAlerts.length} item${safeAlerts.length > 1 ? "s" : ""} require attention${criticalCount > 0 ? " — prioritize critical alerts" : ""}.`);

  if (criticalCount > 0) {
    $summary.append(`<span class="badge rounded-pill text-bg-danger">${criticalCount} Critical</span>`);
  }
  if (highCount > 0) {
    $summary.append(`<span class="badge rounded-pill text-bg-warning">${highCount} High</span>`);
  }
  if (counts.info > 0) {
    $summary.append(`<span class="badge rounded-pill text-bg-info">${counts.info} Medium</span>`);
  }
  if (counts.success > 0) {
    $summary.append(`<span class="badge rounded-pill text-bg-success">${counts.success} Healthy</span>`);
  }
}

function renderNeedsAttention(alerts = []) {
  const $list = $("#needsAttentionList");
  latestNeedsAttention = Array.isArray(alerts) ? alerts : [];

  updateNeedsAttentionHeader(latestNeedsAttention);
  $list.empty();

  if (latestNeedsAttention.length === 0) {
    $list.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-2 needs-attention-item type-success">
        <div class="d-flex align-items-center gap-2 needs-attention-content">
          <i class="bi bi-check-circle-fill text-success"></i>
          <span class="text-muted">No urgent alerts right now. Nice work, team.</span>
        </div>
      </li>
    `);
    return;
  }

  const iconMap = {
    calendar: "bi-calendar-event",
    user: "bi-person-exclamation",
    file: "bi-file-earmark-text",
    building: "bi-building",
  };

  latestNeedsAttention.forEach((alert) => {
    const visuals = getAlertVisuals(alert.type || "info");
    const iconClass = iconMap[String(alert.icon || "").toLowerCase()] || "bi-exclamation-circle";
    const severity = escapeHtml(alert.severity || visuals.label);
    const category = escapeHtml(alert.category || "Operational");
    const priority = Number.parseInt(alert.priority, 10) || 0;
    const affectedCount = Number.parseInt(alert.affected_count, 10);
    const message = escapeHtml(alert.message || "Alert requires review.");
    const action = escapeHtml(alert.action || "Open");
    const link = String(alert.link || "");
    const metadata = [];

    if (Number.isFinite(affectedCount) && affectedCount > 0) {
      metadata.push(`${affectedCount} affected`);
    }
    if (priority > 0) {
      metadata.push(`Priority ${priority}`);
    }

    $list.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-2 needs-attention-item ${visuals.itemClass}">
        <div class="needs-attention-content">
          <div class="d-flex gap-3 align-items-start">
            <span class="needs-attention-icon d-inline-flex align-items-center justify-content-center flex-shrink-0">
              <i class="bi ${iconClass} ${visuals.iconClass}"></i>
            </span>
            <div class="flex-grow-1 min-w-0">
              <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                <span class="badge rounded-pill ${visuals.badgeClass}">${severity}</span>
                <span class="badge rounded-pill text-bg-secondary">${category}</span>
              </div>
              <p class="mb-1 small fw-medium">${message}</p>
              <div class="text-muted small d-flex align-items-center gap-2 flex-wrap">
                ${metadata.length ? `<span>${escapeHtml(metadata.join(" • "))}</span>` : "<span>Review recommended</span>"}
              </div>
            </div>
            <button class="btn btn-sm ${visuals.actionClass} py-1 px-2 js-dashboard-link" data-link="${escapeHtml(link)}">${action}</button>
          </div>
        </div>
      </li>
    `);
  });
}

function getRoleBadgeClass(role = "") {
  switch (String(role).toLowerCase()) {
    case "admin":
      return "bg-danger-subtle text-danger-emphasis";
    case "coordinator":
      return "bg-warning-subtle text-warning-emphasis";
    case "student":
      return "bg-primary-subtle text-primary-emphasis";
    case "supervisor":
      return "bg-success-subtle text-success-emphasis";
    default:
      return "bg-secondary-subtle text-secondary-emphasis";
  }
}

function renderRecentAccounts(accounts = []) {
  const $list = $("#recentAccountsList");
  $list.empty();

  if (!Array.isArray(accounts) || accounts.length === 0) {
    $list.append('<li class="list-group-item bg-transparent border-0 px-0 py-3 text-muted">No recent account activity.</li>');
    return;
  }

  accounts.forEach((account) => {
    const initials = escapeHtml(account.initials || "??");
    const name = escapeHtml(account.full_name || "No profile yet");
    const email = escapeHtml(account.email || "—");
    const roleLabel = escapeHtml(account.role_label || "Unknown");
    const timeAgo = escapeHtml(account.time_ago || account.created_at || "Recently");
    const badgeClass = getRoleBadgeClass(account.role || "");

    $list.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex gap-3 align-items-start">
          <span class="rounded-circle bg-dark bg-opacity-25 d-inline-flex align-items-center justify-content-center text-body fw-bold" style="width: 36px; height: 36px; min-width: 36px;">${initials}</span>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
              <span class="fw-semibold text-truncate" style="max-width: 180px;">${name}</span>
              <span class="badge rounded-pill ${badgeClass}">${roleLabel}</span>
            </div>
            <small class="text-muted d-block text-truncate">${email}</small>
            <small class="text-muted">${timeAgo}</small>
          </div>
        </div>
      </li>
    `);
  });
}

function getActivityIconClass(eventType = "") {
  const value = String(eventType).toLowerCase();
  if (value.includes("created") || value.includes("approved") || value.includes("activated") || value.includes("success")) {
    return "bi-check-circle-fill text-success";
  }
  if (value.includes("updated") || value.includes("reset") || value.includes("submitted") || value.includes("issued")) {
    return "bi-arrow-repeat text-primary";
  }
  if (value.includes("failed") || value.includes("deactivated") || value.includes("rejected") || value.includes("disabled")) {
    return "bi-exclamation-triangle-fill text-danger";
  }
  return "bi-clock-history text-secondary";
}

function renderRecentActivity(activities = []) {
  const $list = $("#recentActivityList");
  $list.empty();

  const safeActivities = Array.isArray(activities) ? activities : [];
  $("#activityCount").text(safeActivities.length);

  if (safeActivities.length === 0) {
    $list.append('<li class="list-group-item bg-transparent border-0 px-0 py-3 text-muted">No recent activity yet.</li>');
    return;
  }

  safeActivities.forEach((activity) => {
    const actor = escapeHtml(activity.actor_name || "System");
    const description = escapeHtml(activity.description || "No description provided.");
    const timeAgo = escapeHtml(activity.time_ago || "Recently");
    const module = escapeHtml(activity.module || "system");
    const iconClass = getActivityIconClass(activity.event_type || "");

    $list.append(`
      <li class="list-group-item bg-transparent border-0 px-0 py-3 border-bottom border-secondary border-opacity-25">
        <div class="d-flex gap-3 align-items-start">
          <i class="bi ${iconClass}"></i>
          <div class="flex-grow-1">
            <p class="mb-1 small"><span class="fw-semibold">${actor}</span> — ${description}</p>
            <small class="text-muted">${module} • ${timeAgo}</small>
          </div>
        </div>
      </li>
    `);
  });
}

function goToDashboardLink(link = "") {
  const normalized = String(link || "").trim();
  if (!normalized) return;

  const map = {
    Batches: "../../../Src/Pages/Admin/Batches",
    Companies: "../../../Src/Pages/Admin/Companies",
    Coordinators: "../../../Src/Pages/Admin/Coordinators",
    Students: "../../../Src/Pages/Admin/Students",
    AuditLogs: "../../../Src/Pages/Admin/AuditLogs",
    Reports: "../../../Src/Pages/Admin/Reports",
  };

  const target = map[normalized] || map[normalized.replace(/^\/+|\/+$/g, "")] || "";
  if (target) {
    window.location.href = target;
  }
}

function fetchDashboardData(options = {}) {
  const { showSuccessToast = false, isManualRefresh = false } = options;

  $.ajax({
    url: "../../../process/admin/get_dashboard",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      if (isManualRefresh) {
        setNeedsAttentionRefreshState(true);
      }
    },
    success: function (response) {
      if (response.status !== "success") {
        ToastVersion(swalTheme, response.message || "Failed to load dashboard data.", "warning", 3000, "top-end");
        return;
      }

      const data = response.data || {};
      renderStatCards(data.stat_cards || {});
      renderRoleBreakdown(data.role_breakdown || {});
      renderNeedsAttention(data.needs_attention || []);
      renderRecentAccounts(data.recent_accounts || []);
      renderRecentActivity(data.recent_activity || []);
      updateNeedsAttentionTimestamp(new Date());

      if (showSuccessToast) {
        ToastVersion(swalTheme, "Dashboard insights refreshed.", "success", 1800, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
    complete: function () {
      if (isManualRefresh) {
        setNeedsAttentionRefreshState(false);
      }
    },
  });
}

function wireAdminDashboardQuickActions() {
  $("#dashboardnewCoordinatorBtn, #quickAddCoordinator").on("click", function (e) {
    e.preventDefault();
    window.location.href = "../../../Src/Pages/Admin/Coordinators";
  });

  $("#quickViewAuditLogs").on("click", function (e) {
    e.preventDefault();
    window.location.href = "../../../Src/Pages/Admin/AuditLogs";
  });

  $("#quickExportSemReports").on("click", function (e) {
    e.preventDefault();
    window.location.href = "../../../Src/Pages/Admin/Reports";
  });

  $("#needsAttentionViewAll").on("click", function (e) {
    e.preventDefault();
    const firstActionable = latestNeedsAttention.find((item) => item && item.link);
    if (firstActionable) {
      goToDashboardLink(firstActionable.link);
      return;
    }

    window.location.href = "../../../Src/Pages/Admin/AuditLogs";

      $("#refreshNeedsAttentionBtn").on("click", function (e) {
        e.preventDefault();
        fetchDashboardData({ showSuccessToast: true, isManualRefresh: true });
      });
  });

  $(document)
    .off("click", ".js-dashboard-link")
    .on("click", ".js-dashboard-link", function () {
      goToDashboardLink($(this).data("link"));
    });
}

$(document).ready(function () {
  if (!letPageLoad) return;
  
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
    $("#PageMainContent").fadeIn(500);
  });

  fetchProfile();
  DashboardEsentialElements();
  tableDropdown();

  if (Onlypage === "AdminDashboard") {
    fetchDashboardData();
    wireAdminDashboardQuickActions();

    $("#quickCreateBatch").on("click", function (e) {
      e.preventDefault();
      window.location.href = "../../../Src/Pages/Admin/Batches?action=create";
    });
  }
});
