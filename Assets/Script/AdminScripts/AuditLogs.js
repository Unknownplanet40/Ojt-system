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
<tr class="border-0">
  <td colspan="7" class="text-center py-4 bg-transparent border-0" style="cursor: wait;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="spinner-border text-secondary" role="status"><span class="visually-hidden">Loading...</span></div>
      <div>
        <p class="mb-2 text-body fw-semibold">Loading audit logs...</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Please wait while we fetch audit entries.</small>
      </div>
    </div>
  </td>
</tr>`;

const emptyRow = `
<tr class="border-0">
  <td colspan="7" class="text-center py-4 bg-transparent border-0" style="cursor: default;">
    <div class="d-flex flex-column align-items-center gap-2">
      <div class="rounded-circle bg-secondary-subtle text-secondary-emphasis d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
        <i class="bi bi-journal-x fa-lg"></i>
      </div>
      <div>
        <p class="mb-2 text-body fw-semibold">No audit logs found</p>
        <small class="text-muted d-block" style="font-size: 0.875rem;">Try adjusting your filters to see more activity.</small>
      </div>
    </div>
  </td>
</tr>`;

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
  const $tbody = $("#auditLogsTable tbody");
  $tbody.empty();
  auditLogCache.clear();

  if (!Array.isArray(logs) || logs.length === 0) {
    $tbody.html(emptyRow);
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
    const ipAddress = escapeHtml(log.ip_address || "");
    const failReason = escapeHtml(log.fail_reason || "");
    const isActivitySource = String(log.source || "") === "activity";
    const isLoginSource = String(log.source || "") === "login";
    const hasMeta =
      isActivitySource &&
      ((log.meta && typeof log.meta === "object" && Object.keys(log.meta).length > 0) ||
        (typeof log.meta_raw === "string" && log.meta_raw.trim() !== ""));
    const hasUserAgent = isLoginSource && typeof log.user_agent === "string" && log.user_agent.trim() !== "";

    const metaBadge = isActivitySource
      ? `<span class="badge rounded-pill ${hasMeta ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"}">Meta: ${hasMeta ? "Yes" : "No"}</span>`
      : '<span class="badge rounded-pill bg-light-subtle text-muted">Meta: N/A</span>';

    const userAgentBadge = isLoginSource
      ? `<span class="badge rounded-pill ${hasUserAgent ? "bg-success-subtle text-success" : "bg-secondary-subtle text-secondary"}">UA: ${hasUserAgent ? "Yes" : "No"}</span>`
      : '<span class="badge rounded-pill bg-light-subtle text-muted">UA: N/A</span>';

    const detailItems = [];
    if (targetUuid) {
      detailItems.push(`<small class="text-muted d-block">Target: <code>${targetUuid}</code></small>`);
    }
    if (ipAddress) {
      detailItems.push(`<small class="text-muted d-block">IP: <code>${ipAddress}</code></small>`);
    }
    if (failReason) {
      detailItems.push(`<small class="text-danger-emphasis d-block">Reason: ${failReason}</small>`);
    }

    const extraDetails = detailItems.join("");

    $tbody.append(`
      <tr>
        <td class="bg-blur-5 bg-semi-transparent border-0">
          <div class="vstack gap-0">
            <span class="fw-semibold">${occurredAt}</span>
            <small class="text-muted">${timeAgo}</small>
          </div>
        </td>
        <td class="bg-blur-5 bg-semi-transparent border-0">
          <div class="vstack gap-0">
            <span class="fw-semibold">${actorName}</span>
            <small class="text-muted">${actorEmail || actorRole}</small>
            <small class="text-muted d-lg-none">${eventLabel} • ${moduleLabel}</small>
          </div>
        </td>
        <td class="bg-blur-5 bg-semi-transparent border-0 d-none d-lg-table-cell">
          <span class="badge rounded-pill ${actionBadgeClass(log.event_type)}">${eventLabel}</span>
        </td>
        <td class="bg-blur-5 bg-semi-transparent border-0 d-none d-xl-table-cell">${moduleLabel}</td>
        <td class="bg-blur-5 bg-semi-transparent border-0">
          <div class="vstack gap-0">
            <span class="audit-description-text">${description}</span>
            <div class="hstack gap-2 mt-1 flex-wrap">
              ${metaBadge}
              ${userAgentBadge}
              <span class="badge rounded-pill ${sourceBadgeClass(log.source)} d-inline-flex d-md-none">Source: ${source}</span>
            </div>
            ${extraDetails}
          </div>
        </td>
        <td class="bg-blur-5 bg-semi-transparent border-0 text-center d-none d-md-table-cell">
          <span class="badge rounded-pill ${sourceBadgeClass(log.source)}">${source}</span>
        </td>
        <td class="bg-blur-5 bg-semi-transparent border-0 text-center">
          <button class="btn btn-sm btn-outline-secondary js-audit-detail-btn px-2 px-lg-3" data-row-id="${escapeHtml(rowId)}" title="View details">
            <i class="bi bi-eye"></i><span class="ms-1 d-none d-lg-inline">View</span>
          </button>
        </td>
      </tr>
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

  const ipAddress = isLoginSource ? log.ip_address || "—" : "Not applicable for activity logs";
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
  const $tbody = $("#auditLogsTable tbody");
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
      $tbody.html(loadingRow);
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
      $tbody.html(emptyRow);
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
