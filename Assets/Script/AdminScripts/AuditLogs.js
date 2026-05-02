import { ToastVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const paginationState = {
  page: 1,
  pageSize: 25,
  total: 0,
  totalPages: 0,
};
const auditLogCache = new Map();

const loadingRow = `
<div class="col-12 text-center py-5">
  <div class="d-flex flex-column align-items-center gap-2">
    <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
    <div>
      <p class="mb-2 text-body fw-semibold">Loading audit logs...</p>
      <small class="text-muted d-block" style="font-size: 0.875rem;">Please wait while we fetch audit entries.</small>
    </div>
  </div>
</div>`;

const emptyRow = `
<div class="col-12 text-center py-5">
  <div class="d-flex flex-column align-items-center gap-2">
    <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex justify-content-center align-items-center" style="width: 64px; height: 64px;">
      <i class="bi bi-journal-x fs-1"></i>
    </div>
    <div>
      <p class="mb-2 text-body fw-semibold">No audit logs found</p>
      <small class="text-muted d-block" style="font-size: 0.875rem;">Try adjusting your filters to see more activity.</small>
    </div>
  </div>
</div>`;

function escapeHtml(value = "") {
  return String(value)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

function sourceBadgeClass(source) {
  switch (source) {
    case "activity":
      return "bg-primary-subtle text-primary";
    case "login":
      return "bg-info-subtle text-info";
    default:
      return "bg-secondary-subtle text-secondary";
  }
}

function actionBadgeClass(eventType = "") {
  const value = String(eventType).toLowerCase();
  if (value.includes("success") || value.includes("created") || value.includes("activated") || value.includes("approved")) {
    return "bg-success-subtle text-success";
  }
  if (value.includes("failed") || value.includes("deactivated") || value.includes("rejected") || value.includes("error")) {
    return "bg-danger-subtle text-danger";
  }
  if (value.includes("updated") || value.includes("reset") || value.includes("submitted")) {
    return "bg-warning-subtle text-warning";
  }
  return "bg-secondary-subtle text-secondary";
}

function humanizeMetaKey(key = "") {
  return String(key)
    .replaceAll("_", " ")
    .replace(/\s+/g, " ")
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase());
}

function formatMetaValue(value) {
  if (value === null || typeof value === "undefined") {
    return '<span class="badge rounded-pill bg-secondary-subtle text-secondary">null</span>';
  }

  if (typeof value === "boolean") {
    return `<span class="badge rounded-pill ${value ? "bg-success-subtle text-success" : "bg-warning-subtle text-warning"}">${value}</span>`;
  }

  if (typeof value === "number") {
    return `<code>${escapeHtml(String(value))}</code>`;
  }

  if (Array.isArray(value) || typeof value === "object") {
    return `<code class="text-wrap d-inline-block">${escapeHtml(JSON.stringify(value))}</code>`;
  }

  const stringValue = String(value);
  const looksLikeUrl = /^https?:\/\//i.test(stringValue);
  if (looksLikeUrl) {
    return `<a href="${escapeHtml(stringValue)}" target="_blank" rel="noopener noreferrer">${escapeHtml(stringValue)}</a>`;
  }

  return escapeHtml(stringValue);
}

function renderMetaDetails(log) {
  const isLoginSource = String(log.source || "") === "login";
  if (isLoginSource) {
    return '<p class="mb-0 text-muted">Not applicable for login audit logs.</p>';
  }

  const meta = log.meta && typeof log.meta === "object" ? log.meta : null;
  if (!meta || Object.keys(meta).length === 0) {
    return '<p class="mb-0 text-muted">No meta data attached to this record.</p>';
  }

  const items = Object.entries(meta)
    .map(
      ([key, value]) => `
        <div class="audit-meta-item rounded-3 border p-2">
          <small class="text-muted fw-semibold text-uppercase d-block mb-1">${escapeHtml(humanizeMetaKey(key))}</small>
          <div class="audit-meta-value">${formatMetaValue(value)}</div>
        </div>
      `,
    )
    .join("");

  return `<div class="audit-meta-grid">${items}</div>`;
}

function renderAuditRows(logs = []) {
  const $container = $("#auditLogsContainer");
  $container.empty();
  auditLogCache.clear();

  if (!Array.isArray(logs) || logs.length === 0) {
    $container.html(emptyRow);
    return;
  }

  logs.forEach((log) => {
    const rowId = String(log.row_id || "");
    if (rowId) {
      auditLogCache.set(rowId, log);
    }

    const occurredAt = escapeHtml(log.occurred_at_display || "—");
    const timeAgo = escapeHtml(log.time_ago || "");
    const actorName = escapeHtml(log.actor_name || "System");
    const actorRole = escapeHtml(log.actor_role_label || "System");
    const actorEmail = escapeHtml(log.actor_email || "");
    const eventLabel = escapeHtml(log.event_label || "Unknown");
    const moduleLabel = escapeHtml(log.module_label || "System");
    const fullDescription = String(log.description || "—");
    const description = escapeHtml(fullDescription.length > 160 ? `${fullDescription.slice(0, 157)}...` : fullDescription);
    const source = escapeHtml(log.source || "activity");
    const targetUuid = escapeHtml(log.target_uuid || "");
    let rawIp = log.ip_address || "";
    if (rawIp === "::1" || rawIp === "127.0.0.1") rawIp = "localhost";
    const ipAddress = escapeHtml(rawIp);
    const failReason = escapeHtml(log.fail_reason || "");
    const isActivitySource = String(log.source || "") === "activity";
    const isLoginSource = String(log.source || "") === "login";
    
    // Icons
    let moduleIcon = 'bi-box';
    let moduleColor = 'text-primary';
    if(log.module_label === 'auth') { moduleIcon = 'bi-shield-lock'; moduleColor = 'text-danger'; }
    if(log.module_label === 'users') { moduleIcon = 'bi-people'; moduleColor = 'text-info'; }

    $container.append(`
      <div class="col-12 col-xl-6">
        <div class="card h-100 bg-blur-5 bg-semi-transparent border-1 rounded-4 position-relative border-secondary-subtle">
          <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start mb-3">
              <div class="d-flex align-items-center gap-3">
                <div class="avatar avatar-md rounded-circle bg-body-tertiary bg-opacity-50 d-flex justify-content-center align-items-center flex-shrink-0" style="width: 48px; height: 48px;">
                  <span class="fs-5 ${moduleColor}"><i class="bi ${moduleIcon}"></i></span>
                </div>
                <div>
                  <h6 class="mb-0 fw-bold text-body">${moduleLabel} <span class="badge rounded-pill fw-normal ms-1 ${actionBadgeClass(log.event_type)}">${eventLabel}</span></h6>
                  <p class="text-muted small mb-0">${actorName} (${actorRole})</p>
                </div>
              </div>
              <span class="badge rounded-pill ${sourceBadgeClass(log.source)}">${source}</span>
            </div>
            
            <div class="bg-body-tertiary bg-opacity-50 rounded-3 p-3 mb-3">
              <p class="small text-body-secondary mb-0" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                ${description}
              </p>
              ${failReason ? `<p class="small text-danger mt-2 mb-0">Reason: ${failReason}</p>` : ''}
              ${targetUuid ? `<small class="text-muted d-block mt-2">Target: <code>${targetUuid}</code></small>` : ''}
            </div>
            
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted"><i class="bi bi-clock me-1"></i>${occurredAt} <span class="ms-1">(${timeAgo})</span></small>
              <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 js-audit-detail-btn" data-row-id="${escapeHtml(rowId)}">Details</button>
            </div>
          </div>
        </div>
      </div>
    `);
  });
}

function openAuditDetailModal(log) {
  if (!log) {
    ToastVersion(swalTheme, "Unable to open log details.", "warning", 2500, "top-end");
    return;
  }

  const occurredAt = log.occurred_at_display || "—";
  const actor = log.actor_name || "System";
  const role = log.actor_role_label || "System";
  const eventLabel = log.event_label || "Unknown";
  const moduleLabel = log.module_label || "System";
  const source = log.source || "activity";
  const target = log.target_uuid || "—";
  const isLoginSource = String(log.source || "") === "login";
  const isActivitySource = String(log.source || "") === "activity";

  let rawDetailIp = log.ip_address || "—";
  if (rawDetailIp === "::1" || rawDetailIp === "127.0.0.1") rawDetailIp = "localhost";
  const ipAddress = isLoginSource ? rawDetailIp : "Not applicable for activity logs";
  const loginResult =
    !isLoginSource || log.login_success === null || typeof log.login_success === "undefined"
      ? "Not applicable for activity logs"
      : Number(log.login_success) === 1
        ? "Success"
        : "Failed";
  const failReason = isLoginSource ? log.fail_reason || "—" : "Not applicable for activity logs";
  const description = log.description || "—";
  const userAgent = isLoginSource ? log.user_agent || "—" : "Not applicable for activity logs";

  const metaHtml = renderMetaDetails(log);

  $("#auditDetailOccurredAt").text(occurredAt);
  $("#auditDetailActor").text(actor);
  $("#auditDetailRole").text(role);
  $("#auditDetailEvent").text(eventLabel);
  $("#auditDetailModule").text(moduleLabel);
  $("#auditDetailSource").text(source);
  $("#auditDetailTarget").text(target);
  $("#auditDetailIp").text(ipAddress);
  $("#auditDetailLoginResult").text(loginResult);
  $("#auditDetailFailReason").text(failReason);
  $("#auditDetailDescription").text(description);
  $("#auditDetailUserAgent").text(userAgent);
  $("#auditDetailMeta").html(metaHtml);

  $("#auditLogDetailsModal").modal("show");
}

async function exportAuditLogsCsv() {
  const filters = collectFilters();
  const payload = {
    csrf_token: csrfToken,
    ...filters,
  };

  try {
    const response = await fetch("../../../process/audit_logs/export_audit_logs_csv", {
      method: "POST",
      credentials: "same-origin",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
        "X-Requested-With": "XMLHttpRequest",
      },
      body: new URLSearchParams(payload),
    });

    const contentType = (response.headers.get("Content-Type") || "").toLowerCase();

    if (!response.ok || contentType.includes("application/json")) {
      let message = "Failed to export audit logs.";
      try {
        const json = await response.json();
        message = json.message || message;
      } catch {
        const text = await response.text();
        message = text || message;
      }
      ToastVersion(swalTheme, message, "error", 3500, "top-end");
      return;
    }

    const contentDisposition = response.headers.get("Content-Disposition") || "";
    const fileNameMatch = contentDisposition.match(/filename\*?=(?:UTF-8''|\")?([^\";]+)/i);
    const fileNameFromHeader = fileNameMatch ? decodeURIComponent(fileNameMatch[1].trim()) : "";
    const fileName = fileNameFromHeader || `audit_logs_${new Date().toISOString().slice(0, 19).replace(/[:T]/g, "_")}.csv`;

    const blob = await response.blob();
    const blobUrl = window.URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.href = blobUrl;
    link.download = fileName;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(blobUrl);

    ToastVersion(swalTheme, "Filtered audit logs exported successfully.", "success", 2500, "top-end");
  } catch (error) {
    Errors({ status: 0 }, "error", error?.message || error);
  }
}

function upsertSelectOptions(selector, options, emptyLabel, selectedValue = "") {
  const $select = $(selector);
  const existingValue = selectedValue || $select.val() || "";

  $select.empty();
  $select.append(`<option value="" class="CustomOption">${escapeHtml(emptyLabel)}</option>`);

  (options || []).forEach((opt) => {
    const value = String(opt.value ?? "");
    const label = String(opt.label ?? value);
    const selected = existingValue === value ? "selected" : "";
    $select.append(`<option class="CustomOption" value="${escapeHtml(value)}" ${selected}>${escapeHtml(label)}</option>`);
  });

  $select.val(existingValue);
}

function upsertSourceOptions(selector, options, selectedValue = "all") {
  const $select = $(selector);
  const currentValue = selectedValue || $select.val() || "all";
  $select.empty();

  (options || []).forEach((opt) => {
    const value = String(opt.value ?? "all");
    const label = String(opt.label ?? value);
    const selected = currentValue === value ? "selected" : "";
    $select.append(`<option class="CustomOption" value="${escapeHtml(value)}" ${selected}>${escapeHtml(label)}</option>`);
  });

  if (!$select.find("option").length) {
    $select.append('<option value="all" class="CustomOption" selected>All Sources</option>');
  }

  $select.val(currentValue);
}

function collectFilters() {
  return {
    date_from: $("#auditDateFrom").val(),
    date_to: $("#auditDateTo").val(),
    source: $("#auditSourceFilter").val() || "all",
    module: $("#auditModuleFilter").val() || "",
    event_type: $("#auditEventFilter").val() || "",
    user_uuid: $("#auditActorFilter").val() || "",
    search: $.trim($("#auditSearchInput").val() || ""),
  };
}

function toggleClearFilters(filters) {
  const hasFilters = Boolean(
    filters.date_from ||
      filters.date_to ||
      (filters.source && filters.source !== "all") ||
      filters.module ||
      filters.event_type ||
      filters.user_uuid ||
      filters.search,
  );

  $("#clearAuditFiltersBtn").toggleClass("d-none", !hasFilters);
}

function renderPagination(pagination = {}) {
  paginationState.page = Number.parseInt(pagination.page, 10) || 1;
  paginationState.pageSize = Number.parseInt(pagination.page_size, 10) || 25;
  paginationState.total = Number.parseInt(pagination.total, 10) || 0;
  paginationState.totalPages = Number.parseInt(pagination.total_pages, 10) || 0;

  const pageDisplay = paginationState.totalPages > 0 ? `Page ${paginationState.page} of ${paginationState.totalPages}` : "Page 0 of 0";
  $("#auditPageInfo").text(pageDisplay);
  $("#auditLogCount").text(paginationState.total);

  $("#auditPrevBtn").prop("disabled", paginationState.page <= 1);
  $("#auditNextBtn").prop("disabled", paginationState.page >= paginationState.totalPages || paginationState.totalPages === 0);
  $("#auditPageSize").val(String(paginationState.pageSize));
}

function loadAuditLogs(page = 1) {
  const $container = $("#auditLogsContainer");
  const filters = collectFilters();

  $.ajax({
    url: "../../../process/audit_logs/get_audit_logs",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
      page,
      page_size: paginationState.pageSize,
      ...filters,
    },
    beforeSend: function () {
      $container.html(loadingRow);
    },
    success: function (response) {
      if (response.status !== "success") {
        $tbody.html(emptyRow);
        ToastVersion(swalTheme, response.message || "Failed to load audit logs.", "warning", 3000, "top-end");
        return;
      }

      const filterOptions = response.filter_options || {};
      const selectedFilters = response.filters || {};

      upsertSourceOptions("#auditSourceFilter", filterOptions.sources || [], selectedFilters.source || "all");
      upsertSelectOptions("#auditModuleFilter", filterOptions.modules || [], "All Modules", selectedFilters.module || "");
      upsertSelectOptions("#auditEventFilter", filterOptions.event_types || [], "All Actions", selectedFilters.event_type || "");
      upsertSelectOptions("#auditActorFilter", filterOptions.actors || [], "All Users", selectedFilters.user_uuid || "");

      $("#auditDateFrom").val(selectedFilters.date_from || "");
      $("#auditDateTo").val(selectedFilters.date_to || "");
      $("#auditSearchInput").val(selectedFilters.search || "");
      renderAuditRows(response.logs || []);
      renderPagination(response.pagination || {});
      toggleClearFilters(selectedFilters);
    },
    error: function (xhr, status, error) {
      const $container = $("#auditLogsContainer");
      $container.html(emptyRow);
      Errors(xhr, status, error);
    },
  });
}

function resetFilters() {
  $("#auditDateFrom").val("");
  $("#auditDateTo").val("");
  $("#auditSourceFilter").val("all");
  $("#auditModuleFilter").val("");
  $("#auditEventFilter").val("");
  $("#auditActorFilter").val("");
  $("#auditSearchInput").val("");
}

$(document).ready(function () {
  loadAuditLogs(1);

  $(document)
    .off("click", ".js-audit-detail-btn")
    .on("click", ".js-audit-detail-btn", function () {
      const rowId = String($(this).attr("data-row-id") || "");
      const log = auditLogCache.get(rowId);
      openAuditDetailModal(log);
    });

  $("#auditDateFrom, #auditDateTo, #auditSourceFilter, #auditModuleFilter, #auditEventFilter, #auditActorFilter").on("change", function () {
    loadAuditLogs(1);
  });

  $("#auditSearchInput").on("input", function () {
    clearTimeout($(this).data("searchTimeout"));
    $(this).data(
      "searchTimeout",
      setTimeout(function () {
        loadAuditLogs(1);
      }, 450),
    );
  });

  $("#clearAuditFiltersBtn").on("click", function () {
    resetFilters();
    loadAuditLogs(1);
  });

  $("#refreshAuditLogsBtn").on("click", function () {
    loadAuditLogs(paginationState.page || 1);
  });

  $("#auditPageSize").on("change", function () {
    const selected = Number.parseInt($(this).val(), 10);
    paginationState.pageSize = Number.isFinite(selected) ? selected : 25;
    loadAuditLogs(1);
  });

  $("#exportAuditCsvBtn").on("click", function () {
    exportAuditLogsCsv();
  });

  $("#auditPrevBtn").on("click", function () {
    if (paginationState.page > 1) {
      loadAuditLogs(paginationState.page - 1);
    }
  });

  $("#auditNextBtn").on("click", function () {
    if (paginationState.page < paginationState.totalPages) {
      loadAuditLogs(paginationState.page + 1);
    }
  });
});
