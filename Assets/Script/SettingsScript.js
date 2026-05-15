
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
        }
    });

    
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



