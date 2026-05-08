import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { GetThemeMode, SetThemeMode, SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
import { animate, stagger } from "../../../libs/animejs/bundles/anime.esm.min.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

function initializeSettingsPage() {
  const loader = document.getElementById("pageLoader");
  if (loader) loader.classList.add("d-none");
  
  bindSettingsEvents();
  loadSavedSettings();
  loadSystemInfo();
}

$(document).ready(initializeSettingsPage);

function getStatusMeta(status) {
    const normalized = String(status || '').toLowerCase();
    const meta = {
        ok: {
            icon: 'check-circle-fill',
            label: 'Healthy',
            className: 'is-ok',
        },
        warning: {
            icon: 'exclamation-triangle-fill',
            label: 'Review',
            className: 'is-warning',
        },
        error: {
            icon: 'x-circle-fill',
            label: 'Issue',
            className: 'is-error',
        },
    };

    return meta[normalized] || {
        icon: 'info-circle-fill',
        label: 'Info',
        className: 'is-info',
    };
}

function getStatusBadgeHtml(status, message) {
    const meta = getStatusMeta(status);
    return `
        <span class="system-status-pill ${meta.className}">
            <i class="bi bi-${meta.icon}"></i>
            <span>${message || meta.label}</span>
        </span>
    `;
}

function renderSystemInfoCard({ label, value, status, message, icon, wide = false }) {
    const meta = getStatusMeta(status);

    return `
        <article class="system-info-card ${wide ? 'system-info-card-wide' : ''}" data-status="${meta.className}">
            <div class="system-info-card-top">
                <span class="system-info-icon ${meta.className}">
                    <i class="bi bi-${icon}"></i>
                </span>
                <span class="system-status-dot ${meta.className}" aria-label="${meta.label}"></span>
            </div>
            <div class="system-info-body">
                <span class="system-info-label">${label}</span>
                <strong class="system-info-value">${value}</strong>
            </div>
            ${getStatusBadgeHtml(status, message)}
        </article>
    `;
}

function animateSystemInfoCards(scopeSelector = '#settings-system') {
    const cards = document.querySelectorAll(`${scopeSelector} .system-info-card:not([data-animated="true"])`);
    if (!cards.length) return;

    cards.forEach((card) => {
        card.dataset.animated = 'true';
    });

    animate(cards, {
        opacity: [0, 1],
        y: [18, 0],
        scale: [0.98, 1],
        duration: 620,
        delay: stagger(55),
        ease: 'out(3)',
    });
}

function animateStorageMeters() {
    const meters = document.querySelectorAll('#storageInfoGrid .system-storage-meter-fill:not([data-animated="true"])');
    if (!meters.length) return;

    meters.forEach((meter) => {
        const targetWidth = meter.dataset.width || '0%';
        meter.dataset.animated = 'true';
        meter.style.width = '0%';

        animate(meter, {
            width: targetWidth,
            duration: 850,
            ease: 'out(3)',
        });
    });
}

function animateSystemSectionIfVisible() {
    if (!$('#settings-system').hasClass('active')) return;

    animateSystemInfoCards();
    animateStorageMeters();
}

// Load system information on page load
function loadSystemInfo() {
    $.ajax({
        url: '../../../process/admin/get_system_info',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                renderSystemInfo(response.data);
            } else {
                console.error('Failed to load system info:', response.message);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading system info:', error);
        }
    });
}

function renderSystemInfo(data) {
    const $envGrid = $('#environmentInfoGrid');
    if (!$envGrid.length) return;

    let envHtml = '';

    if (data.php) {
        envHtml += renderSystemInfoCard({
            label: 'PHP Version',
            value: data.php.value,
            status: data.php.status,
            message: data.php.message,
            icon: 'code-slash',
        });
    }

    if (data.database) {
        envHtml += renderSystemInfoCard({
            label: 'Database',
            value: data.database.value,
            status: data.database.status,
            message: data.database.message,
            icon: 'database-check',
            wide: true,
        });
    }

    if (data.operatingSystem) {
        envHtml += renderSystemInfoCard({
            label: 'Operating System',
            value: data.operatingSystem.value,
            status: data.operatingSystem.status,
            message: data.operatingSystem.message,
            icon: 'pc-display',
            wide: true,
        });
    }

    if (data.modRewrite) {
        envHtml += renderSystemInfoCard({
            label: 'mod_rewrite',
            value: data.modRewrite.value,
            status: data.modRewrite.status,
            message: data.modRewrite.message,
            icon: 'shuffle',
        });
    }

    if (data.serverSoftware) {
        envHtml += renderSystemInfoCard({
            label: 'Server Software',
            value: data.serverSoftware.value,
            status: data.serverSoftware.status,
            message: data.serverSoftware.message,
            icon: 'hdd-network',
            wide: true,
        });
    }

    if (data.diskSpace) {
        envHtml += renderSystemInfoCard({
            label: 'Disk Space',
            value: data.diskSpace.value,
            status: data.diskSpace.status,
            message: data.diskSpace.message,
            icon: 'device-hdd',
        });
    }

    if (data.memoryUsage) {
        envHtml += renderSystemInfoCard({
            label: 'Memory Usage',
            value: data.memoryUsage.value,
            status: data.memoryUsage.status,
            message: data.memoryUsage.message,
            icon: 'memory',
        });
    }

    if (data.extensions) {
        const extStatus = data.extensions.missing.length === 0 ? 'ok' : 'warning';
        envHtml += renderSystemInfoCard({
            label: 'PHP Extensions',
            value: data.extensions.value,
            status: extStatus,
            message: data.extensions.message,
            icon: 'puzzle',
        });
    }

    $envGrid.html(envHtml);
    
    renderStorageInfo(data);
    animateSystemSectionIfVisible();
}

// Render storage information cards
function renderStorageInfo(data) {
    const $storageGrid = $('#storageInfoGrid');
    if (!$storageGrid.length || !data.storage) return;

    let storageHtml = '';

    if (data.fileUpload) {
        storageHtml += `
            <article class="system-info-card">
                <div class="system-info-card-top">
                    <span class="system-info-icon ${getStatusMeta(data.fileUpload.status).className}">
                        <i class="bi bi-cloud-arrow-up"></i>
                    </span>
                    <span class="system-status-dot ${getStatusMeta(data.fileUpload.status).className}" aria-label="Upload status"></span>
                </div>
                <div class="system-info-body">
                    <span class="system-info-label">File Upload Limits</span>
                    <strong class="system-info-value">${data.fileUpload.value}</strong>
                    <small class="system-info-note">${data.fileUpload.message}</small>
                </div>
                ${getStatusBadgeHtml(data.fileUpload.status, 'Configured')}
            </article>
        `;
    }

    data.storage.forEach((dir, index) => {
        const maxSize = 500 * 1024 * 1024;
        const percentage = Math.min(Math.round((dir.size / maxSize) * 100), 100);
        const isUploadsDir = index === 0;
        const cardClass = isUploadsDir ? 'system-info-card-wide' : '';
        const dirStatusMeta = getStatusMeta(dir.status);

        storageHtml += `
            <article class="system-info-card ${cardClass}">
                <div class="system-info-card-top">
                    <span class="system-info-icon ${dirStatusMeta.className}">
                        <i class="bi bi-folder2-open"></i>
                    </span>
                    <span class="system-status-dot ${dirStatusMeta.className}" aria-label="${dirStatusMeta.label}"></span>
                </div>
                <div class="system-info-body">
                    <span class="system-info-label">${dir.name}</span>
                    <strong class="system-info-value text-break">${dir.path}</strong>
                    <div class="system-storage-meta">
                        <span>${dir.sizeFormatted} used</span>
                        <span>${percentage}%</span>
                    </div>
                    <div class="system-storage-meter" aria-hidden="true">
                        <div class="system-storage-meter-fill" data-width="${percentage}%"></div>
                    </div>
                </div>
                ${getStatusBadgeHtml(dir.status, dir.message)}
            </article>
        `;
    });

    $storageGrid.html(storageHtml);
    animateSystemSectionIfVisible();
}

const $opacitySlider = $('#opacityLevel');
const $opacityValue = $('#opacityValue');

if ($opacitySlider.length && $opacityValue.length) {
    $opacitySlider.on('input', function() {
        const percentage = Math.round(this.value * 100);
        $opacityValue.text(percentage + '%');
    });
}

function bindSettingsEvents() {
    $('input[name="theme"]').on('change', function() {
        const selectedTheme = this.value;
        
        // Show warning for light theme selection
        if (selectedTheme === 'light') {
            const currentTheme = GetThemeMode();
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
                    handleThemeModeChange(selectedTheme);
                } else {
                    // Revert to previous theme
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

    $('.settings-section .btn-action[style*="220"]').on('click', function() {
        const label = $(this).parent().find('div:first').text();
        ConfirmVersion(swalTheme, `Confirm Action`, `This will perform the action related to ${label}. Are you sure you want to continue?`, 'warning', 'Yes, proceed', 'success', 'danger', 'Cancel', 'center')
        .then((result) => {
            if (result.isConfirmed) {
              // TODO: Implement the action logic here
            }
        });
    });
}

function handleSystemTabShown() {
    animateSystemInfoCards();
    animateStorageMeters();
}

function handleThemeModeChange(theme) {
    const appliedTheme = SetThemeMode(theme);
    swalTheme = appliedTheme.swalTheme;
    saveThemeSetting(appliedTheme.mode);
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
            const theme = response?.settings?.theme;
            if (response.status === 'success' && theme) {
                const appliedTheme = SetThemeMode(theme);
                swalTheme = appliedTheme.swalTheme;
                updateThemeSelection(appliedTheme.mode);
            }
        },
        error: function(xhr, status, error) {
            Errors(xhr, status, error);
        }
    });
}

// Save theme setting via AJAX
function saveThemeSetting(theme) {
    $.ajax({
        url: '../../../process/admin/save_settings',
        type: 'POST',
        dataType: 'json',
        data: {
            csrf_token: csrfToken,
            theme: theme
        },
        success: function(response) {
            if (response.status === 'success') {
                ToastVersion(swalTheme, "Theme mode saved.", "success", 1800, "top-end");
            } else {
                ToastVersion(swalTheme, response.message || "Unable to save theme mode.", "warning", 3000, "top-end");
                updateThemeSelection();
            }
        },
        error: function(xhr, status, error) {
            Errors(xhr, status, error);
            updateThemeSelection();
        }
    });
}

function handleSaveSettingsClick() {
    ConfirmVersion(swalTheme, `Save Settings`, `This will save all your current settings. Do you want to proceed?`, 'question', 'Yes, save it', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
          ToastVersion(swalTheme, "Settings are already saved.", "info", 2000, "top-end");
        }
    });
}

function handleResetSettingsClick() {
    ConfirmVersion(swalTheme, `Reset Settings`, `This will reset all settings to their default values. Are you sure you want to proceed?`, 'warning', 'Yes, reset', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
          handleThemeModeChange('dark');
        }
    });
}

function handleEmailTestClick() {
    ConfirmVersion(swalTheme, `Send Test Email`, `This will send a test email to the configured address to verify the connection. Do you want to proceed?`, 'question', 'Yes, send it', 'success', 'danger', 'Cancel', 'center')
    .then((result) => {
        if (result.isConfirmed) {
          //TODO: Implement the email test logic here
        }
    });
}

