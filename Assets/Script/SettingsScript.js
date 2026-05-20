
import { ToastVersion, ConfirmVersion } from "./CustomSweetAlert.js";
import { GetThemeMode, SetThemeMode, SwalTheme } from "./SystemTheme.js";


const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
let swalTheme = SwalTheme();

$(document).ready(function () {
    let pendingTheme = GetThemeMode();

    
    initializeThemeUI();

    
    $('.settings-nav-item').on('click', function() {
        const paneId = $(this).data('pane');
        
        
        $('.settings-nav-item').removeClass('active');
        $(this).addClass('active');
        
        
        $('.pane').removeClass('active');
        $(`#pane-${paneId}`).addClass('active');

        
        if (paneId === 'sessions') {
            fetchLogHistory();
        } else if (paneId === 'security') {
            handleUserSearch();
        }
    });

    $("#userSearchInput").on("keyup", debounce(handleUserSearch, 500));
    $("#unlockAccountBtn").on("click", handleUnlockAccount);
    $(document).on("click", ".user-search-item", handleUserSelect);
    $(document).on("click", ".manual-lock-option", handleManualLock);

    
    $('.theme-card').on('click', function() {
        const theme = $(this).data('theme');
        pendingTheme = theme;
        
        
        $(`.theme-card`).removeClass('selected');
        $(this).addClass('selected');
    });

    
    $('#saveAppearance').on('click', function() {
        if (pendingTheme === 'light') {
            ConfirmVersion(
                swalTheme,
                'Light Mode Warning',
                'Light mode may have reduced contrast in some areas or incompatibility with certain features. Dark mode is recommended for optimal experience.',
                'warning',
                'Continue with Light Mode',
                'warning',
                'danger',
                'Cancel',
                'center'
            ).then((result) => {
                if (result.isConfirmed) {
                    handleThemeChange(pendingTheme);
                } else {
                    
                    pendingTheme = GetThemeMode();
                    initializeThemeUI();
                }
            });
        } else {
            handleThemeChange(pendingTheme);
        }
    });

    
    $('#resetAppearance').on('click', function() {
        pendingTheme = 'dark';
        $(`.theme-card`).removeClass('selected');
        $(`.theme-card[data-theme="dark"]`).addClass('selected');
        ToastVersion(swalTheme, "Selection reset to Dark Mode. Click Save Changes to apply.", "info", 2000, "top-end");
    });

    
    $(document).on('click', '.btn-danger', function() {
        if ($(this).closest('#pane-sessions').length) {
            ToastVersion(swalTheme, "Sign out feature will be available in the next security update.", "info", 2000, "top-end");
        }
    });
});


function fetchLogHistory(page = 1) {
    const $container = $('#logHistoryContainer');
    if (!$container.length) return;

    $.ajax({
        url: '../../../process/profile/get_user_logs',
        method: 'POST',
        dataType: 'json',
        data: {
            csrf_token: csrfToken,
            page: page,
            page_size: 20
        },
        beforeSend: function() {
            $container.html('<div class="text-center p-5 opacity-50"><span class="loader sm"></span><div class="mt-2 small">Loading activity logs...</div></div>');
        },
        success: function(response) {
            if (response.status === 'success') {
                renderLogs(response.logs);
            } else {
                $container.html(`<div class="text-center p-5 text-danger small">${response.message}</div>`);
            }
        },
        error: function() {
            $container.html('<div class="text-center p-5 text-danger small">Failed to load logs. Please try again.</div>');
        }
    });
}


function renderLogs(logs) {
    const $container = $('#logHistoryContainer');
    if (logs.length === 0) {
        $container.html('<div class="text-center p-5 text-muted small">No recent activity found.</div>');
        return;
    }

    let html = '';
    logs.forEach(log => {
        const isLogin = log.source === 'login';
        const isSuccess = log.login_success === 1 || !isLogin;
        const icon = isLogin ? (log.user_agent?.includes('Mobile') ? 'bi-phone' : 'bi-laptop') : 'bi-activity';
        const badgeClass = isSuccess ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
        const statusLabel = isLogin ? (isSuccess ? 'Login Success' : 'Login Failed') : 'Activity';
        
        
        const device = log.user_agent ? (log.user_agent.includes('Windows') ? 'Windows' : log.user_agent.includes('Mac') ? 'MacOS' : 'Mobile Device') : 'Unknown Device';
        const browser = log.user_agent ? (log.user_agent.includes('Chrome') ? 'Chrome' : log.user_agent.includes('Firefox') ? 'Firefox' : log.user_agent.includes('Safari') ? 'Safari' : 'Browser') : 'Unknown';

        html += `
            <div class="p-3 rounded-4 bg-secondary bg-opacity-10 border border-secondary border-opacity-10 d-flex align-items-center gap-3 mb-2">
                <div class="rounded-circle ${isSuccess ? 'bg-primary' : 'bg-danger'} bg-opacity-10 p-3 ${isSuccess ? 'text-primary' : 'text-danger'} fs-4 d-flex align-items-center justify-content-center" style="width:50px; height:50px;">
                    <i class="bi ${icon}"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-0 fw-bold">${device} &bull; ${browser}</h6>
                            <p class="small text-muted mb-0">${log.description}</p>
                            <p class="small text-muted mb-0 opacity-75">IP: ${log.ip_address || 'N/A'}</p>
                        </div>
                        <span class="badge ${badgeClass} rounded-pill">${statusLabel}</span>
                    </div>
                    <small class="text-muted d-block mt-1">${log.occurred_at_display} &bull; <span class="text-primary">${log.time_ago}</span></small>
                </div>
            </div>
        `;
    });
    $container.html(html);
}


function initializeThemeUI() {
    const currentTheme = GetThemeMode();
    $(`.theme-card`).removeClass('selected');
    $(`.theme-card[data-theme="${currentTheme}"]`).addClass('selected');
}

function handleThemeChange(theme) {
    $.ajax({
        url: '../../../process/profile/update_theme',
        method: 'POST',
        dataType: 'json',
        data: {
            csrf_token: csrfToken,
            theme: theme
        },
        success: function(response) {
            if (response.status === 'success') {
                const applied = SetThemeMode(theme);
                swalTheme = applied.swalTheme;
                
                $(`.theme-card`).removeClass('selected');
                $(`.theme-card[data-theme="${applied.mode}"]`).addClass('selected');
                
                ToastVersion(swalTheme, `Theme set to ${applied.mode}`, "success", 1500, "top-end");
            } else {
                ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
            }
        },
        error: function() {
            ToastVersion(swalTheme, "Failed to save theme preference to server.", "error", 3000, "top-end");
        }
    });
}


let selectedUserUuid = null;

function handleUserSearch() {
  const query = $("#userSearchInput").val().trim();
  
  if (query.length === 1) return;

  $.ajax({
    url: "../../../process/admin/get_users_lockout",
    type: "GET",
    data: { search: query },
    dataType: "json",
    beforeSend: function () {
      $("#userSearchResults").html(`
        <div class="text-center py-5">
          <span class="spinner-border spinner-border-sm text-primary"></span>
        </div>
      `);
    },
    success: function (response) {
      if (response.status === "success") {
        renderSearchResults(response.data, query);
      } else {
        $("#userSearchResults").html(`<div class="alert alert-danger mx-3 mt-3 small">${response.message}</div>`);
      }
    },
  });
}

function renderSearchResults(users, query = "") {
  if (users.length === 0) {
    const icon = query ? "bi-search" : "bi-shield-check";
    const message = query 
      ? `No users found matching "${query}"` 
      : "No active lockouts or failed attempts found";
    
    $("#userSearchResults").html(`
      <div class="text-center py-5 text-muted">
        <i class="bi ${icon}" style="font-size: 2rem; opacity: 0.3;"></i>
        <p class="mt-2 small">${message}</p>
      </div>
    `);
    return;
  }

  let html = query ? "" : '<div class="px-3 mb-2 mt-2"><small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Active Lockouts & Failed Attempts</small></div>';
  html += '<div class="list-group list-group-flush border-0">';
  
  users.forEach((user) => {
    const statusBadge = user.is_locked 
      ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-auto" style="font-size: 0.6rem;">LOCKED</span>' 
      : '<span class="badge bg-success-subtle text-success border border-success-subtle ms-auto" style="font-size: 0.6rem;">ACTIVE</span>';

    html += `
            <button type="button" class="list-group-item list-group-item-action user-search-item d-flex align-items-center gap-3 border-0 py-3 bg-transparent" 
                    data-uuid="${user.uuid}" data-user='${JSON.stringify(user)}'>
                <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px; font-size: 0.85rem;">
                    ${user.initials}
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="fw-bold text-truncate small" style="color: var(--bs-body-color);">${user.name}</div>
                    <div class="text-muted text-truncate" style="font-size: 0.75rem;">${user.email}</div>
                </div>
                ${statusBadge}
            </button>
        `;
  });
  html += "</div>";
  $("#userSearchResults").html(html);
}

function handleUserSelect() {
  const user = $(this).data("user");
  selectedUserUuid = user.uuid;

  $(".user-search-item").removeClass("active bg-primary bg-opacity-10");
  $(this).addClass("active bg-primary bg-opacity-10");

  $("#selectedUserInitials").text(user.initials);
  $("#selectedUserName").text(user.name);
  $("#selectedUserEmail").text(user.email);
  $("#selectedUserRole").text(user.role);

  const statusHtml = user.is_locked
    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size: 0.65rem;">LOCKED</span>'
    : '<span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">ACTIVE</span>';
  $("#selectedUserStatus").html(statusHtml);

  $("#failedAttemptsCount").text(user.login_attempts);

  if (user.is_locked) {
    $("#lockoutExpiryText").text(user.lockout_until);
    $("#unlockAccountBtn").prop("disabled", false);
  } else {
    $("#lockoutExpiryText").text("Not Locked");
    $("#unlockAccountBtn").prop("disabled", true);
  }

  $("#noUserSelected").hide();
  $("#userLockoutDetails").fadeIn(200);
}

function handleUnlockAccount() {
  if (!selectedUserUuid) return;

  ConfirmVersion(
    swalTheme,
    "Unlock Account",
    "Are you sure you want to manually unlock this account and reset failed attempts?",
    "question",
    "Yes, Unlock",
    "success",
    "secondary",
    "Cancel",
    "center"
  ).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../../process/admin/manage_lockout",
        type: "POST",
        data: {
          action: "unlock",
          user_uuid: selectedUserUuid,
          csrf_token: csrfToken,
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            handleUserSearch();
            $("#userLockoutDetails").hide();
            $("#noUserSelected").fadeIn();
          } else {
            ToastVersion(swalTheme, response.message, "error", 5000, "top-end");
          }
        },
      });
    }
  });
}

function handleManualLock(e) {
  e.preventDefault();
  if (!selectedUserUuid) return;

  const hours = $(this).data("hours");
  const durationText = $(this).text();

  ConfirmVersion(
    swalTheme,
    "Lock Account",
    `Are you sure you want to manually restrict this account for ${durationText}?`,
    "warning",
    "Yes, Restrict",
    "danger",
    "secondary",
    "Cancel",
    "center"
  ).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../../process/admin/manage_lockout",
        type: "POST",
        data: {
          action: "lock",
          user_uuid: selectedUserUuid,
          hours: hours,
          csrf_token: csrfToken,
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            handleUserSearch();
            $("#userLockoutDetails").hide();
            $("#noUserSelected").fadeIn();
          } else {
            ToastVersion(swalTheme, response.message, "error", 5000, "top-end");
          }
        },
      });
    }
  });
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
