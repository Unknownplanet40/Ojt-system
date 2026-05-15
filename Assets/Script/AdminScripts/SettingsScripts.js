import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { GetThemeMode, SetThemeMode, SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
import { animate, stagger } from "../../../libs/animejs/bundles/anime.esm.min.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

let pendingClears = {
    activityLog: false,
    loginLog: false
};

function initializeSettingsPage() {
  const loader = document.getElementById("pageLoader");
  if (loader) loader.classList.add("d-none");
  
  bindSettingsEvents();
  loadSavedSettings();
  loadSystemInfo();
}

$(document).ready(initializeSettingsPage);

function bindSettingsEvents() {
    $('input[name="theme"]').on('change', function() {
        const selectedTheme = this.value;
        if (selectedTheme === 'light') {
            const currentTheme = GetThemeMode();
            ConfirmVersion(swalTheme, 'Light Mode Warning', 'Light mode may have reduced contrast in some areas. Dark mode is recommended.', 'warning', 'Continue', 'warning', 'danger', 'Cancel', 'center')
            .then((result) => {
                if (result.isConfirmed) {
                    handleThemeModeChange(selectedTheme);
                } else {
                    updateThemeSelection(currentTheme);
                }
            });
        } else {
            handleThemeModeChange(selectedTheme);
        }
    });

    $('#saveSettingsBtn').on('click', handleSaveSettingsClick);
    $('#resetSettingsBtn').on('click', handleResetSettingsClick);
    $('#emailTestBtn').on('click', handleEmailTestClick);
    $('#settings-system-tab').on('shown.bs.tab', handleSystemTabShown);

    
    $('#instLogo1').on('change', function() { handleLogoPreview(this, '#logo1Preview'); });
    $('#instLogo2').on('change', function() { handleLogoPreview(this, '#logo2Preview'); });

    $('#clearActivityLogBtn').on('click', function() {
        ConfirmVersion(swalTheme, "Clear Activity Log?", "This will mark all activity logs for deletion. Logs will be permanently cleared once you click 'Save All Settings'.", "warning", "Yes, mark for clearing", "danger", "secondary", "Cancel")
        .then((result) => {
            if (result.isConfirmed) {
                pendingClears.activityLog = true;
                $(this).html('<i class="bi bi-clock-history"></i> Pending Clear').css('background', 'rgba(255, 193, 7, 0.2)').css('color', '#ffc107').css('border-color', 'rgba(255, 193, 7, 0.3)');
                ToastVersion(swalTheme, "Activity log marked for deletion.", "info", 2000, "top-end");
            }
        });
    });

    $('#clearLoginLogBtn').on('click', function() {
        ConfirmVersion(swalTheme, "Clear Login Audit Log?", "This will mark all login audit logs for deletion. Logs will be permanently cleared once you click 'Save All Settings'.", "warning", "Yes, mark for clearing", "danger", "secondary", "Cancel")
        .then((result) => {
            if (result.isConfirmed) {
                pendingClears.loginLog = true;
                $(this).html('<i class="bi bi-clock-history"></i> Pending Clear').css('background', 'rgba(255, 193, 7, 0.2)').css('color', '#ffc107').css('border-color', 'rgba(255, 193, 7, 0.3)');
                ToastVersion(swalTheme, "Login audit log marked for deletion.", "info", 2000, "top-end");
            }
        });
    });
}

function handleLogoPreview(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            $(previewId).attr('src', e.target.result);
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function handleSystemTabShown() {
    animateSystemInfoCards();
    animateStorageMeters();
}

function handleThemeModeChange(theme) {
    const appliedTheme = SetThemeMode(theme, false); 
    swalTheme = appliedTheme.swalTheme;
}

function updateThemeSelection(theme) {
    const mode = theme || GetThemeMode();
    $(`input[name="theme"][value="${mode}"]`).prop('checked', true);
}

function loadSavedSettings() {
    updateThemeSelection();

    $.ajax({
        url: '../../../process/admin/get_settings',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                const settings = response.settings;
                
                if (settings.theme) {
                    const appliedTheme = SetThemeMode(settings.theme, false);
                    swalTheme = appliedTheme.swalTheme;
                    updateThemeSelection(appliedTheme.mode);
                }

                if (settings.email) {
                    $('#emailSmtpHost').val(settings.email.host);
                    $('#emailSmtpPort').val(settings.email.port);
                    $('#emailSmtpUser').val(settings.email.user);
                    $('#emailSmtpPass').val(settings.email.pass);
                    $('#emailSmtpCrypto').val(settings.email.crypto);
                    $('#emailFromEmail').val(settings.email.from_email);
                    $('#emailFromName').val(settings.email.from_name);
                }

                if (settings.institutional) {
                    $('#instLongTitle').val(settings.institutional.long_title);
                    $('#instShortTitle').val(settings.institutional.short_title);
                    $('#instSystemDescription').val(settings.institutional.system_description);
                    $('#instAuthor').val(settings.institutional.author);
                    $('#instSchoolName').val(settings.institutional.school_name);
                    $('#instSchoolMotto').val(settings.institutional.school_motto);
                    $('#instSchoolAddress').val(settings.institutional.school_address);
                    $('#instSchoolWebsite').val(settings.institutional.school_website);
                    $('#instSchoolEmail').val(settings.institutional.school_email);
                    $('#instSchoolPhone').val(settings.institutional.school_phone);
                    $('#instFooterNote').val(settings.institutional.footer_note);
                    $('#instVerificationNote').val(settings.institutional.verification_note);
                    $('#instPageLink').val(settings.institutional.page_link);

                    if (settings.institutional.logo_1) $('#logo1Preview').attr('src', settings.institutional.logo_1);
                    if (settings.institutional.logo_2) $('#logo2Preview').attr('src', settings.institutional.logo_2);
                }
            }
        },
        error: function(xhr, status, error) {
            Errors(xhr, status, error);
        }
    });
}

function handleSaveSettingsClick() {
    const selectedTheme = $('input[name="theme"]:checked').val();
    const emailData = {
        host: $('#emailSmtpHost').val(),
        port: $('#emailSmtpPort').val(),
        user: $('#emailSmtpUser').val(),
        pass: $('#emailSmtpPass').val(),
        crypto: $('#emailSmtpCrypto').val(),
        from_email: $('#emailFromEmail').val(),
        from_name: $('#emailFromName').val()
    };

    const instData = {
        long_title: $('#instLongTitle').val(),
        short_title: $('#instShortTitle').val(),
        system_description: $('#instSystemDescription').val(),
        author: $('#instAuthor').val(),
        school_name: $('#instSchoolName').val(),
        school_motto: $('#instSchoolMotto').val(),
        school_address: $('#instSchoolAddress').val(),
        school_website: $('#instSchoolWebsite').val(),
        school_email: $('#instSchoolEmail').val(),
        school_phone: $('#instSchoolPhone').val(),
        footer_note: $('#instFooterNote').val(),
        verification_note: $('#instVerificationNote').val(),
        page_link: $('#instPageLink').val()
    };

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('theme', selectedTheme);
    formData.append('email_settings', JSON.stringify(emailData));
    formData.append('institutional_settings', JSON.stringify(instData));
    formData.append('clear_activity_log', pendingClears.activityLog);
    formData.append('clear_login_log', pendingClears.loginLog);

    
    if ($('#instLogo1')[0].files[0]) formData.append('logo_1', $('#instLogo1')[0].files[0]);
    if ($('#instLogo2')[0].files[0]) formData.append('logo_2', $('#instLogo2')[0].files[0]);

    ConfirmVersion(swalTheme, `Save Settings`, `This will save all changes including theme, institutional profile, email configuration, and destructive actions. Proceed?`, 'question', 'Yes, save all', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../../process/admin/save_settings',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                beforeSend: function() {
                    $('#saveSettingsBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
                },
                success: function(response) {
                    if (response.status === 'success') {
                        SetThemeMode(selectedTheme, true);
                        pendingClears.activityLog = false;
                        pendingClears.loginLog = false;
                        resetDangerButtons();
                        ToastVersion(swalTheme, "All settings saved and actions executed.", "success", 2000, "top-end");
                        
                        $('#instLogo1').val('');
                        $('#instLogo2').val('');
                    } else {
                        ToastVersion(swalTheme, response.message || "Unable to save settings.", "warning", 3000, "top-end");
                    }
                },
                error: function(xhr, status, error) {
                    Errors(xhr, status, error);
                },
                complete: function() {
                    $('#saveSettingsBtn').prop('disabled', false).html('<i class="bi bi-check-circle"></i> <span>Save All Settings</span>');
                }
            });
        }
    });
}

function resetDangerButtons() {
    $('#clearActivityLogBtn').html('<i class="bi bi-trash"></i> Clear').css('background', '').css('color', '').css('border-color', '');
    $('#clearLoginLogBtn').html('<i class="bi bi-trash"></i> Clear').css('background', '').css('color', '').css('border-color', '');
}

function handleResetSettingsClick() {
    ConfirmVersion(swalTheme, `Reset Settings`, `This will reset all settings to their default values. Are you sure you want to proceed?`, 'warning', 'Yes, reset', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
            handleThemeModeChange('dark');
            updateThemeSelection('dark');
            $('#emailForm')[0].reset();
            pendingClears.activityLog = false;
            pendingClears.loginLog = false;
            resetDangerButtons();
            ToastVersion(swalTheme, "Settings reset in view. Click 'Save All Settings' to persist.", "info", 3000, "top-end");
        }
    });
}

function handleEmailTestClick() {
    const emailData = {
        host: $('#emailSmtpHost').val(),
        port: $('#emailSmtpPort').val(),
        user: $('#emailSmtpUser').val(),
        pass: $('#emailSmtpPass').val(),
        crypto: $('#emailSmtpCrypto').val(),
        from_email: $('#emailFromEmail').val(),
        from_name: $('#emailFromName').val()
    };

    if (!emailData.host || !emailData.user || !emailData.pass) {
        ToastVersion(swalTheme, "Please fill in Host, Username, and Password to test.", "warning", 3000, "top-end");
        return;
    }

    ConfirmVersion(swalTheme, `Send Test Email`, `This will attempt to send a test email to ${emailData.from_email}. Proceed?`, 'question', 'Yes, send it', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: '../../../process/admin/test_email_connection',
                type: 'POST',
                dataType: 'json',
                data: {
                    csrf_token: csrfToken,
                    ...emailData
                },
                beforeSend: function() {
                    $('#emailTestBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status"></span> Testing...');
                },
                success: function(response) {
                    if (response.status === 'success') {
                        ToastVersion(swalTheme, "Test email sent successfully!", "success", 3000, "top-end");
                    } else {
                        ToastVersion(swalTheme, response.message || "Connection test failed.", "error", 5000, "top-end");
                    }
                },
                error: function(xhr, status, error) {
                    Errors(xhr, status, error);
                },
                complete: function() {
                    $('#emailTestBtn').prop('disabled', false).html('<i class="bi bi-play-circle"></i> Test Connection');
                }
            });
        }
    });
}


function getStatusMeta(status) {
    const normalized = String(status || '').toLowerCase();
    const meta = {
        ok: { icon: 'check-circle-fill', label: 'Healthy', className: 'is-ok' },
        warning: { icon: 'exclamation-triangle-fill', label: 'Review', className: 'is-warning' },
        error: { icon: 'x-circle-fill', label: 'Issue', className: 'is-error' },
    };
    return meta[normalized] || { icon: 'info-circle-fill', label: 'Info', className: 'is-info' };
}

function getStatusBadgeHtml(status, message) {
    const meta = getStatusMeta(status);
    return `<span class="system-status-pill ${meta.className}"><i class="bi bi-${meta.icon}"></i> <span>${message || meta.label}</span></span>`;
}

function renderSystemInfoCard({ label, value, status, message, icon, wide = false }) {
    const meta = getStatusMeta(status);
    return `<article class="system-info-card ${wide ? 'system-info-card-wide' : ''}" data-status="${meta.className}"><div class="system-info-card-top"><span class="system-info-icon ${meta.className}"><i class="bi bi-${icon}"></i></span><span class="system-status-dot ${meta.className}"></span></div><div class="system-info-body"><span class="system-info-label">${label}</span><strong class="system-info-value">${value}</strong></div>${getStatusBadgeHtml(status, message)}</article>`;
}

function animateSystemInfoCards() {
    const cards = document.querySelectorAll('#settings-system .system-info-card:not([data-animated="true"])');
    if (!cards.length) return;
    cards.forEach(card => card.dataset.animated = 'true');
    animate(cards, { opacity: [0, 1], y: [18, 0], scale: [0.98, 1], duration: 620, delay: stagger(55), ease: 'out(3)' });
}

function animateStorageMeters() {
    const meters = document.querySelectorAll('#storageInfoGrid .system-storage-meter-fill:not([data-animated="true"])');
    if (!meters.length) return;
    meters.forEach(meter => {
        const targetWidth = meter.dataset.width || '0%';
        meter.dataset.animated = 'true';
        meter.style.width = '0%';
        animate(meter, { width: targetWidth, duration: 850, ease: 'out(3)' });
    });
}

function loadSystemInfo() {
    $.ajax({
        url: '../../../process/admin/get_system_info',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                renderSystemInfo(response.data);
            }
        }
    });
}

function renderSystemInfo(data) {
    const $envGrid = $('#environmentInfoGrid');
    if (!$envGrid.length) return;
    let envHtml = '';
    if (data.php) envHtml += renderSystemInfoCard({ label: 'PHP Version', value: data.php.value, status: data.php.status, message: data.php.message, icon: 'code-slash' });
    if (data.database) envHtml += renderSystemInfoCard({ label: 'Database', value: data.database.value, status: data.database.status, message: data.database.message, icon: 'database-check', wide: true });
    if (data.operatingSystem) envHtml += renderSystemInfoCard({ label: 'Operating System', value: data.operatingSystem.value, status: data.operatingSystem.status, message: data.operatingSystem.message, icon: 'pc-display', wide: true });
    if (data.modRewrite) envHtml += renderSystemInfoCard({ label: 'mod_rewrite', value: data.modRewrite.value, status: data.modRewrite.status, message: data.modRewrite.message, icon: 'shuffle' });
    if (data.serverSoftware) envHtml += renderSystemInfoCard({ label: 'Server Software', value: data.serverSoftware.value, status: data.serverSoftware.status, message: data.serverSoftware.message, icon: 'hdd-network', wide: true });
    if (data.diskSpace) envHtml += renderSystemInfoCard({ label: 'Disk Space', value: data.diskSpace.value, status: data.diskSpace.status, message: data.diskSpace.message, icon: 'device-hdd' });
    if (data.memoryUsage) envHtml += renderSystemInfoCard({ label: 'Memory Usage', value: data.memoryUsage.value, status: data.memoryUsage.status, message: data.memoryUsage.message, icon: 'memory' });
    if (data.extensions) envHtml += renderSystemInfoCard({ label: 'PHP Extensions', value: data.extensions.value, status: data.extensions.missing.length === 0 ? 'ok' : 'warning', message: data.extensions.message, icon: 'puzzle' });
    $envGrid.html(envHtml);
    renderStorageInfo(data);
}

function renderStorageInfo(data) {
    const $storageGrid = $('#storageInfoGrid');
    if (!$storageGrid.length || !data.storage) return;
    let storageHtml = '';
    if (data.fileUpload) {
        storageHtml += `<article class="system-info-card"><div class="system-info-card-top"><span class="system-info-icon ${getStatusMeta(data.fileUpload.status).className}"><i class="bi bi-cloud-arrow-up"></i></span><span class="system-status-dot ${getStatusMeta(data.fileUpload.status).className}"></span></div><div class="system-info-body"><span class="system-info-label">File Upload Limits</span><strong class="system-info-value">${data.fileUpload.value}</strong><small class="system-info-note">${data.fileUpload.message}</small></div>${getStatusBadgeHtml(data.fileUpload.status, 'Configured')}</article>`;
    }
    data.storage.forEach((dir, index) => {
        const maxSize = 500 * 1024 * 1024;
        const percentage = Math.min(Math.round((dir.size / maxSize) * 100), 100);
        const cardClass = index === 0 ? 'system-info-card-wide' : '';
        const dirStatusMeta = getStatusMeta(dir.status);
        storageHtml += `<article class="system-info-card ${cardClass}"><div class="system-info-card-top"><span class="system-info-icon ${dirStatusMeta.className}"><i class="bi bi-folder2-open"></i></span><span class="system-status-dot ${dirStatusMeta.className}"></span></div><div class="system-info-body"><span class="system-info-label">${dir.name}</span><strong class="system-info-value text-break">${dir.path}</strong><div class="system-storage-meta"><span>${dir.sizeFormatted} used</span><span>${percentage}%</span></div><div class="system-storage-meter"><div class="system-storage-meter-fill" data-width="${percentage}%"></div></div></div>${getStatusBadgeHtml(dir.status, dir.message)}</article>`;
    });
    $storageGrid.html(storageHtml);
    animateSystemSectionIfVisible();
}

function animateSystemSectionIfVisible() {
    if (!$('#settings-system').hasClass('active')) return;
    animateSystemInfoCards();
    animateStorageMeters();
}
