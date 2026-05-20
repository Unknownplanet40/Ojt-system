
import { ToastVersion, ModalVersion } from './CustomSweetAlert.js';

function appRoot() {
    const m = document.querySelector('meta[name="app-root"]');
    return m ? m.getAttribute('content').replace(/\/$/, '') : '';
}

function swalTheme() {
    const t = document.documentElement.getAttribute('data-bs-theme');
    return (t === 'dark') ? 'bootstrap-5-dark' : 'bootstrap-5-light';
}

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

const ICONS = {
    info:    'bi-info-circle-fill',
    success: 'bi-check-circle-fill',
    warning: 'bi-exclamation-triangle-fill',
    danger:  'bi-x-octagon-fill',
};

const SWAL_ICON = {
    info:    'info',
    success: 'success',
    warning: 'warning',
    danger:  'error',
};

function persistDismiss(alertId) {
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    const root = appRoot();
    $.ajax({
        url: `${root}/process/alerts/dismiss_alert`,
        method: 'POST',
        data: { alert_id: alertId, csrf_token: csrf },
    }).fail(() => { /* silent fail */ });
}

function renderBanner(alert) {
    const wrap = document.getElementById('systemAlertBannerWrap');
    if (!wrap) return;

    const ico = ICONS[alert.alert_type] || ICONS.info;

    const strip = document.createElement('div');
    strip.className = 'system-alert-strip';
    strip.setAttribute('data-alert-id', alert.id);
    strip.setAttribute('data-type', alert.alert_type); // CSS handles all colour

    strip.innerHTML = `
        <span class="sas-icon" aria-hidden="true">
            <i class="bi ${ico}"></i>
        </span>
        <span class="sas-body">
            <strong class="sas-title">${escapeHtml(alert.title)}</strong>
            <span class="sas-msg">${escapeHtml(alert.message)}</span>
        </span>
        ${alert.dismissible
            ? `<button class="sas-close" aria-label="Dismiss alert" data-id="${alert.id}">
                   <i class="bi bi-x-lg"></i>
               </button>`
            : ''}
    `;

    const closeBtn = strip.querySelector('.sas-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function () {
            const id = parseInt(this.dataset.id, 10);
            strip.classList.add('sas-hide');
            strip.addEventListener('transitionend', () => {
                strip.remove();
                if (!wrap.querySelector('.system-alert-strip')) {
                    wrap.style.display = 'none';
                }
            }, { once: true });
            if (alert.dismissible) persistDismiss(id);
        });
    }

    wrap.appendChild(strip);
    wrap.style.display = 'block';

    requestAnimationFrame(() => {
        requestAnimationFrame(() => strip.classList.add('sas-visible'));
    });
}

function renderModal(alert) {
    const theme = swalTheme();
    const icon  = SWAL_ICON[alert.alert_type] || 'info';
    const isDark = theme === 'bootstrap-5-dark';

    Swal.fire({
        theme: theme,
        title: alert.title,
        html: alert.message,
        icon: icon,
        confirmButtonText: 'Got it',
        customClass: {
            popup: 'glass-ui glass-ui-strong rounded-3',
            confirmButton: 'btn btn-primary px-4 py-2 rounded-3'
        },
        willClose: () => {
            persistDismiss(alert.id);
        }
    });
}
function renderToast(alert) {
    const theme = swalTheme();
    const icon  = SWAL_ICON[alert.alert_type] || 'info';

    const timer = alert.dismissible ? 0 : 8000;
    ToastVersion(theme, alert.title, icon, timer, 'top-end', '8');
    persistDismiss(alert.id);
}

function boot() {
    const root = appRoot();
    $.ajax({
        url: `${root}/process/alerts/get_active_alerts`,
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            if (!data || data.status !== 'success') return;

            (data.alerts || []).forEach(alert => {
                switch (alert.display_type) {
                    case 'banner': renderBanner(alert); break;
                    case 'modal':  renderModal(alert);  break;
                    case 'toast':  renderToast(alert);  break;
                }
            });
        },
    }).fail(() => { /* non-critical — silent fail */ });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
} else {
    boot();
}
