import { ToastVersion, ConfirmVersion } from "../CustomSweetAlert.js";
import { GetThemeMode, SetThemeMode, SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
import { animate, stagger } from "../../../libs/animejs/bundles/anime.esm.min.js";

let swalTheme = SwalTheme();
const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

let pendingClears = {
  activityLog: false,
  loginLog: false,
};

function initializeSettingsPage() {
  const loader = document.getElementById("pageLoader");
  if (loader) loader.classList.add("d-none");

  bindSettingsEvents();
  loadSavedSettings();
  loadSystemInfo();
  initializeImportFilesState();
  initializeExportFilesState();
}

$(document).ready(initializeSettingsPage);

function bindSettingsEvents() {
  $('input[name="theme"]').on("change", function () {
    const selectedTheme = this.value;
    if (selectedTheme === "light") {
      const currentTheme = GetThemeMode();
      ConfirmVersion(
        swalTheme,
        "Light Mode Warning",
        "Light mode may have reduced contrast in some areas. Dark mode is recommended.",
        "warning",
        "Continue",
        "warning",
        "danger",
        "Cancel",
        "center",
      ).then((result) => {
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

  $("#saveSettingsBtn").on("click", handleSaveSettingsClick);
  $("#resetSettingsBtn").on("click", handleResetSettingsClick);
  $("#emailTestBtn").on("click", handleEmailTestClick);
  $("#settings-system-tab").on("shown.bs.tab", handleSystemTabShown);
  $("#settings-database-tab").on("shown.bs.tab", handleDatabaseTabShown);

  $("#instLogo1").on("change", function () {
    handleLogoPreview(this, "#logo1Preview");
  });
  $("#instLogo2").on("change", function () {
    handleLogoPreview(this, "#logo2Preview");
  });

  $("#optimizeDbBtn").on("click", function () {
    const btn = $(this);
    const originalHtml = btn.html();

    ConfirmVersion(
      swalTheme,
      "Optimize Database?",
      "This will defragment tables and reclaim unused space. The system may be slightly slower during this process.",
      "info",
      "Yes, Optimize Now",
      "primary",
      "secondary",
      "Cancel",
    ).then((result) => {
      if (result.isConfirmed) {
        btn
          .prop("disabled", true)
          .html('<span class="spinner-border spinner-border-sm" role="status"></span> Optimizing...');

        $.ajax({
          url: "../../../process/admin/optimize_database",
          method: "POST",
          success: function (response) {
            if (response.status === "success") {
              ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
              if (response.data && response.data.newSize) {
                $("#dbSizeValue").text(response.data.newSize);
              }
            } else {
              ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
            }
          },
          error: function (xhr) {
            ToastVersion(
              swalTheme,
              "Optimization failed: " + (xhr.responseJSON ? xhr.responseJSON.message : xhr.statusText),
              "error",
              3000,
              "top-end",
            );
          },
          complete: function () {
            btn.prop("disabled", false).html(originalHtml);
          },
        });
      }
    });
  });

  const sqlDropZone          = $("#sqlDropZone");
  const sqlFileInput          = $("#sqlFileInput");
  const sqlFileNameDisplay    = $("#sqlFileNameDisplay");
  const validateSqlBtn        = $("#validateSqlBtn");
  const dryRunBtn             = $("#dryRunBtn");
  const importDatabaseBtn     = $("#importDatabaseBtn");
  const sqlValidationResult   = $("#sqlValidationResult");
  const sqlValidationContent  = $("#sqlValidationContent");
  const dryRunResult          = $("#dryRunResult");
  const dryRunContent         = $("#dryRunContent");

  let sqlValidationPassed = false;
  let dryRunPassed        = false;

  const zipDropZone          = $("#zipDropZone");
  const zipFileInput          = $("#zipFileInput");
  const zipFileNameDisplay    = $("#zipFileNameDisplay");
  const validateZipBtn        = $("#validateZipBtn");
  const importZipBtn          = $("#importZipBtn");
  const zipValidationResult   = $("#zipValidationResult");
  const zipValidationContent  = $("#zipValidationContent");

  let zipValidationPassed = false;


  sqlDropZone.on("click", function () {
    sqlFileInput.click();
  });

  sqlFileInput.on("change", function () {
    if (this.files && this.files.length > 0) {
      handleSqlFileSelect(this.files[0]);
    }
  });

  sqlDropZone.on("dragover", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-warning-rgb), 0.1)",
      "border-color": "var(--bs-warning)",
    });
  });

  sqlDropZone.on("dragleave", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-warning-rgb), 0.05)",
      "border-color": "rgba(var(--bs-warning-rgb), 0.3)",
    });
  });

  sqlDropZone.on("drop", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-warning-rgb), 0.05)",
      "border-color": "rgba(var(--bs-warning-rgb), 0.3)",
    });

    const files = e.originalEvent.dataTransfer.files;
    if (files && files.length > 0) {
      const file = files[0];
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      sqlFileInput[0].files = dataTransfer.files;
      handleSqlFileSelect(file);
    }
  });

  function handleSqlFileSelect(file) {
    if (!file) return;

    if (!file.name.toLowerCase().endsWith(".sql")) {
      ToastVersion(swalTheme, "Only .sql files are allowed", "error", 3000, "top-end");
      sqlFileInput.val("");
      validateSqlBtn.prop("disabled", true);
      importDatabaseBtn.prop("disabled", true);
      sqlFileNameDisplay.html("Click or Drag SQL Backup Here");
      sqlValidationResult.addClass("d-none");
      sqlValidationPassed = false;
      return;
    }

    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    sqlFileNameDisplay.html(
      `<span class="text-primary fw-bold">${file.name}</span> <br> <small class="text-muted">Size: ${fileSize} MB</small>`,
    );

    sqlValidationPassed = false;
    dryRunPassed        = false;
    importDatabaseBtn
      .prop("disabled", true)
      .css({ opacity: "0.45", cursor: "not-allowed" })
      .attr("title", "Validate and Dry Run first.");
    dryRunBtn
      .prop("disabled", true)
      .css({ opacity: "0.45", cursor: "not-allowed" })
      .attr("title", "Validate the SQL file first.");
    validateSqlBtn.prop("disabled", false);
    sqlValidationResult.addClass("d-none");
    sqlValidationContent.html("");
    dryRunResult.addClass("d-none");
    dryRunContent.html("");
  }

  zipDropZone.on("click", function () {
    zipFileInput.click();
  });

  zipFileInput.on("change", function () {
    if (this.files && this.files.length > 0) {
      handleZipFileSelect(this.files[0]);
    }
  });

  zipDropZone.on("dragover", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-info-rgb), 0.1)",
      "border-color": "var(--bs-info)",
    });
  });

  zipDropZone.on("dragleave", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-info-rgb), 0.05)",
      "border-color": "rgba(var(--bs-info-rgb), 0.3)",
    });
  });

  zipDropZone.on("drop", function (e) {
    e.preventDefault();
    e.stopPropagation();
    $(this).css({
      background: "rgba(var(--bs-info-rgb), 0.05)",
      "border-color": "rgba(var(--bs-info-rgb), 0.3)",
    });

    const files = e.originalEvent.dataTransfer.files;
    if (files && files.length > 0) {
      const file = files[0];
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      zipFileInput[0].files = dataTransfer.files;
      handleZipFileSelect(file);
    }
  });

  function handleZipFileSelect(file) {
    if (!file) return;

    if (!file.name.toLowerCase().endsWith(".zip")) {
      ToastVersion(swalTheme, "Only .zip files are allowed", "error", 3000, "top-end");
      zipFileInput.val("");
      validateZipBtn.prop("disabled", true);
      importZipBtn.prop("disabled", true);
      zipFileNameDisplay.html("Click or Drag ZIP Backup Here");
      zipValidationResult.addClass("d-none");
      zipValidationPassed = false;
      return;
    }

    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    zipFileNameDisplay.html(
      `<span class="text-info fw-bold">${file.name}</span> <br> <small class="text-muted">Size: ${fileSize} MB</small>`,
    );

    zipValidationPassed = false;
    importZipBtn
      .prop("disabled", true)
      .css({ opacity: "0.45", cursor: "not-allowed" })
      .attr("title", "Please validate the ZIP file first.");
    validateZipBtn.prop("disabled", false);
    zipValidationResult.addClass("d-none");
    zipValidationContent.html("");
  }

  validateZipBtn.on("click", function () {
    const fileInput = document.getElementById("zipFileInput");
    const file = fileInput.files[0];

    if (!file) {
      ToastVersion(swalTheme, "Please select a ZIP file first.", "warning", 3000, "top-end");
      return;
    }

    const $btn = $(this);
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Validating...');
    importZipBtn
      .prop("disabled", true)
      .css({ opacity: "0.45", cursor: "not-allowed" })
      .attr("title", "Please validate the ZIP file first.");
    zipValidationPassed = false;

    const formData = new FormData();
    formData.append("zip_file", file);

    $.ajax({
      url: "../../../process/admin/validate_zip",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        renderZipValidationResult(response);
      },
      error: function (xhr) {
        const msg = xhr.responseJSON ? xhr.responseJSON.message : "Validation request failed.";
        renderZipValidationResult({ status: "invalid", message: msg, errors: [msg], warnings: [], info: [] });
      },
      complete: function () {
        $btn.prop("disabled", false).html('<i class="bi bi-shield-check"></i> Validate ZIP');
      },
    });
  });

  function renderZipValidationResult(response) {
    const isValid   = response.status === "valid";
    const isInvalid = response.status === "invalid";
    const errors    = response.errors   || [];
    const warnings  = response.warnings || [];
    const info      = response.info     || [];

    let borderColor, bgColor, titleIcon, titleText;

    if (isInvalid) {
      borderColor = "var(--bs-danger)";
      bgColor     = "rgba(var(--bs-danger-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-x-circle-fill text-danger me-2"></i>';
      titleText   = '<span class="text-danger fw-bold">Validation Failed — Import blocked</span>';
    } else if (warnings.length > 0) {
      borderColor = "rgba(var(--bs-warning-rgb), 0.6)";
      bgColor     = "rgba(var(--bs-warning-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>';
      titleText   = '<span class="text-warning-emphasis fw-bold">Validation Passed with Warnings</span>';
    } else {
      borderColor = "var(--bs-info)";
      bgColor     = "rgba(var(--bs-info-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-check-circle-fill text-info me-2"></i>';
      titleText   = '<span class="text-info fw-bold">Validation Passed — Ready to Import</span>';
    }

    let html = `<div class="mb-2">${titleIcon}${titleText}</div>`;

    if (info.length > 0) {
      html += '<ul class="list-unstyled mb-2 text-muted">';
      info.forEach(i => { html += `<li><i class="bi bi-info-circle me-1"></i>${i}</li>`; });
      html += '</ul>';
    }

    if (errors.length > 0) {
      html += '<ul class="list-unstyled mb-2">';
      errors.forEach(e => { html += `<li class="text-danger"><i class="bi bi-x-circle me-1"></i>${e}</li>`; });
      html += '</ul>';
    }

    if (warnings.length > 0) {
      html += '<ul class="list-unstyled mb-0">';
      warnings.forEach(w => { html += `<li class="text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>${w}</li>`; });
      html += '</ul>';
    }

    zipValidationContent.html(html).css({
      "border-color": borderColor,
      "background":   bgColor,
    });
    zipValidationResult.removeClass("d-none");

    if (isInvalid) {
      zipValidationPassed = false;
      importZipBtn
        .prop("disabled", true)
        .css({ opacity: "0.45", cursor: "not-allowed" })
        .attr("title", "Fix all validation errors before importing.");
    } else {
      zipValidationPassed = true;
      importZipBtn
        .prop("disabled", false)
        .css({ opacity: "1", cursor: "pointer" })
        .removeAttr("title");
    }
  }

  validateSqlBtn.on("click", function () {
    const fileInput = document.getElementById("sqlFileInput");
    const file = fileInput.files[0];

    if (!file) {
      ToastVersion(swalTheme, "Please select a SQL file first.", "warning", 3000, "top-end");
      return;
    }

    const $btn = $(this);
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Validating...');
    importDatabaseBtn
      .prop("disabled", true)
      .css({ opacity: "0.45", cursor: "not-allowed" })
      .attr("title", "Please validate the SQL file first.");
    sqlValidationPassed = false;

    const formData = new FormData();
    formData.append("sql_file", file);

    $.ajax({
      url: "../../../process/admin/validate_sql",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        renderValidationResult(response);
      },
      error: function (xhr) {
        const msg = xhr.responseJSON ? xhr.responseJSON.message : "Validation request failed.";
        renderValidationResult({ status: "invalid", message: msg, errors: [msg], warnings: [], info: [] });
      },
      complete: function () {
        $btn.prop("disabled", false).html('<i class="bi bi-shield-check"></i> Validate SQL');
      },
    });
  });

  function renderValidationResult(response) {
    const isValid   = response.status === "valid";
    const isInvalid = response.status === "invalid";
    const errors    = response.errors   || [];
    const warnings  = response.warnings || [];
    const info      = response.info     || [];

    let borderColor, bgColor, titleIcon, titleText;

    if (isInvalid) {
      borderColor = "var(--bs-danger)";
      bgColor     = "rgba(var(--bs-danger-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-x-circle-fill text-danger me-2"></i>';
      titleText   = '<span class="text-danger fw-bold">Validation Failed — Import blocked</span>';
    } else if (warnings.length > 0) {
      borderColor = "rgba(var(--bs-warning-rgb), 0.6)";
      bgColor     = "rgba(var(--bs-warning-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>';
      titleText   = '<span class="text-warning-emphasis fw-bold">Validation Passed with Warnings</span>';
    } else {
      borderColor = "var(--bs-success)";
      bgColor     = "rgba(var(--bs-success-rgb), 0.05)";
      titleIcon   = '<i class="bi bi-check-circle-fill text-success me-2"></i>';
      titleText   = '<span class="text-success fw-bold">Validation Passed — Ready to Import</span>';
    }

    let html = `<div class="mb-2">${titleIcon}${titleText}</div>`;

    if (info.length > 0) {
      html += '<ul class="list-unstyled mb-2 text-muted">';
      info.forEach(i => { html += `<li><i class="bi bi-info-circle me-1"></i>${i}</li>`; });
      html += '</ul>';
    }

    if (errors.length > 0) {
      html += '<ul class="list-unstyled mb-2">';
      errors.forEach(e => { html += `<li class="text-danger"><i class="bi bi-x-circle me-1"></i>${e}</li>`; });
      html += '</ul>';
    }

    if (warnings.length > 0) {
      html += '<ul class="list-unstyled mb-0">';
      warnings.forEach(w => { html += `<li class="text-warning-emphasis"><i class="bi bi-exclamation-triangle me-1"></i>${w}</li>`; });
      html += '</ul>';
    }

    sqlValidationContent.html(html).css({
      "border-color": borderColor,
      "background":   bgColor,
    });
    sqlValidationResult.removeClass("d-none");

    if (isInvalid) {
      sqlValidationPassed = false;
      dryRunPassed        = false;
      importDatabaseBtn
        .prop("disabled", true)
        .css({ opacity: "0.45", cursor: "not-allowed" })
        .attr("title", "Fix all validation errors before importing.");
      dryRunBtn
        .prop("disabled", true)
        .css({ opacity: "0.45", cursor: "not-allowed" })
        .attr("title", "Fix validation errors first.");
    } else {
      sqlValidationPassed = true;
      dryRunPassed        = false;
      dryRunBtn
        .prop("disabled", false)
        .css({ opacity: "1", cursor: "pointer" })
        .removeAttr("title");
      importDatabaseBtn
        .prop("disabled", true)
        .css({ opacity: "0.45", cursor: "not-allowed" })
        .attr("title", "Run Dry Run first to preview changes.");
    }
  }

  dryRunBtn.on("click", function () {
    const fileInput = document.getElementById("sqlFileInput");
    const file = fileInput.files[0];
    if (!file) { ToastVersion(swalTheme, "Select a SQL file first.", "warning", 2500, "top-end"); return; }
    if (!sqlValidationPassed) { ToastVersion(swalTheme, "Validate the file before running a dry run.", "warning", 2500, "top-end"); return; }

    const $btn = $(this);
    $btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Running...');
    dryRunPassed = false;
    importDatabaseBtn.prop("disabled", true).css({ opacity: "0.45", cursor: "not-allowed" });

    const formData = new FormData();
    formData.append("sql_file", file);
    formData.append("dry_run", "1");

    $.ajax({
      url: "../../../process/admin/import_database",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        renderDryRunResult(response);
      },
      error: function (xhr) {
        const msg = xhr.responseJSON ? xhr.responseJSON.message : "Dry run request failed.";
        dryRunContent.html(`<div class="text-danger"><i class="bi bi-x-circle-fill me-2"></i><strong>Dry Run Error:</strong> ${msg}</div>`);
        dryRunResult.removeClass("d-none");
      },
      complete: function () {
        $btn.prop("disabled", false).html('<i class="bi bi-eye"></i> Dry Run');
      },
    });
  });

  function renderDryRunResult(response) {
    const s = response.summary || {};
    const tables  = s.tables_to_create || [];
    const inserts = s.insert_counts    || {};

    let html = `<div class="mb-2"><i class="bi bi-eye-fill text-info me-2"></i><strong class="text-info">Dry Run Complete — No changes made</strong></div>`;
    html += `<ul class="list-unstyled mb-2 text-muted">`;
    html += `<li><i class="bi bi-server me-1"></i>Database: <strong>${s.database || '?'}</strong></li>`;
    html += `<li><i class="bi bi-hdd me-1"></i>File size: <strong>${s.file_size_kb || '?'} KB</strong></li>`;
    html += `<li><i class="bi bi-table me-1"></i>Tables to create: <strong>${s.total_tables || 0}</strong></li>`;
    html += `<li><i class="bi bi-database-add me-1"></i>Total rows to insert: <strong>${s.total_inserts || 0}</strong></li>`;
    html += `</ul>`;

    if (tables.length > 0) {
      html += `<div class="text-muted small mb-1">Tables: ${tables.map(t => `<code>${t}</code>`).join(', ')}</div>`;
    }

    html += `<div class="mt-2 text-success small"><i class="bi bi-check-circle me-1"></i>Review the summary above, then click <strong>Start Import</strong> to proceed.</div>`;

    dryRunContent.html(html);
    dryRunResult.removeClass("d-none");

    dryRunPassed = true;
    importDatabaseBtn
      .prop("disabled", false)
      .css({ opacity: "1", cursor: "pointer" })
      .removeAttr("title");
  }

  $("#importDatabaseBtn").on("click", function () {
    const fileInput = document.getElementById("sqlFileInput");
    const file = fileInput.files[0];

    if (!file) {
      ToastVersion(swalTheme, "Please select a valid SQL file first", "warning", 3000, "top-end");
      return;
    }

    if (!sqlValidationPassed) {
      ToastVersion(swalTheme, "Please validate the SQL file before importing.", "warning", 3000, "top-end");
      return;
    }

    if (!dryRunPassed) {
      ToastVersion(swalTheme, "Please complete a Dry Run before importing.", "warning", 3000, "top-end");
      return;
    }

    ConfirmVersion(
      swalTheme,
      "Critical Restore Operation",
      "This will PERMANENTLY overwrite all current system data. Are you absolutely sure you want to proceed? We strongly recommend exporting your current data first.",
      "warning",
      "Yes, Restore Now",
      "danger",
      "secondary",
      "Cancel",
    ).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("sql_file", file);

        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
          theme: swalTheme,
          title: "Restoring Database...",
          html: 'Please do not close this window. <br><br> <div class="progress" style="height: 10px; border-radius: 5px; background: rgba(0,0,0,0.1);"><div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-warning" role="progressbar" style="width: 0%"></div></div><div id="importStatusText" class="small mt-2 text-muted">Uploading...</div>',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          customClass: {
            popup: "glass-ui glass-ui-strong border border-2 rounded-3",
            container: "overflow-hidden",
          },
          didOpen: () => {
            Swal.showLoading();
          },
        });

        $.ajax({
          url: "../../../process/admin/import_database",
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          xhr: function () {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener(
              "progress",
              function (evt) {
                if (evt.lengthComputable) {
                  const percentComplete = (evt.loaded / evt.total) * 100;
                  $("#importProgressBar").css("width", percentComplete + "%");
                  if (percentComplete === 100) {
                    $("#importStatusText").text("Processing SQL... This may take a moment.");
                  } else {
                    $("#importStatusText").text("Uploading: " + Math.round(percentComplete) + "%");
                  }
                }
              },
              false,
            );
            return xhr;
          },
          success: function (response) {
            if (response.status === "success") {
              Swal.fire({
                theme: swalTheme,
                title: "Success!",
                text: response.message,
                icon: "success",
                confirmButtonText: "Great",
                customClass: {
                  popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                  container: "overflow-hidden",
                  confirmButton: "btn btn-success px-4 py-2 rounded-3 me-2",
                },
              }).then(() => {
                localStorage.setItem('database_newly_imported', 'true');
                window.location.reload();
              });
            } else {
              Swal.fire({
                theme: swalTheme,
                title: "Error",
                text: response.message,
                icon: "error",
                customClass: {
                  popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                  container: "overflow-hidden",
                  confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
                },
              });
            }
          },
          error: function (xhr) {
            const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Import failed due to server error";
            Swal.fire({
              theme: swalTheme,
              title: "Import Failed",
              text: errorMsg,
              icon: "error",
              customClass: {
                popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                container: "overflow-hidden",
                confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
              },
            });
          },
        });
      }
    });
  });

  $("#importZipBtn").on("click", function () {
    const fileInput = document.getElementById("zipFileInput");
    const file = fileInput.files[0];

    if (!file) {
      ToastVersion(swalTheme, "Please select a valid ZIP file first", "warning", 3000, "top-end");
      return;
    }

    if (!zipValidationPassed) {
      ToastVersion(swalTheme, "Please validate the ZIP file before importing.", "warning", 3000, "top-end");
      return;
    }

    ConfirmVersion(
      swalTheme,
      "Restore Backup Files",
      "This will overwrite uploaded student files, certificates, and profile pictures that match filenames in the backup zip. Proceed?",
      "warning",
      "Yes, Restore Files",
      "info",
      "secondary",
      "Cancel",
    ).then((result) => {
      if (result.isConfirmed) {
        const formData = new FormData();
        formData.append("zip_file", file);

        const btn = $(this);

        Swal.fire({
          theme: swalTheme,
          title: "Restoring Files...",
          html: 'Please do not close this window. <br><br> <div class="progress" style="height: 10px; border-radius: 5px; background: rgba(0,0,0,0.1);"><div id="importProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%"></div></div><div id="importStatusText" class="small mt-2 text-muted">Uploading...</div>',
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          customClass: {
            popup: "glass-ui glass-ui-strong border border-2 rounded-3",
            container: "overflow-hidden",
          },
          didOpen: () => {
            Swal.showLoading();
          },
        });

        $.ajax({
          url: "../../../process/admin/import_zip",
          method: "POST",
          data: formData,
          processData: false,
          contentType: false,
          xhr: function () {
            const xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener(
              "progress",
              function (evt) {
                if (evt.lengthComputable) {
                  const percentComplete = (evt.loaded / evt.total) * 100;
                  $("#importProgressBar").css("width", percentComplete + "%");
                  if (percentComplete === 100) {
                    $("#importStatusText").text("Extracting ZIP archive... This may take a moment.");
                  } else {
                    $("#importStatusText").text("Uploading: " + Math.round(percentComplete) + "%");
                  }
                }
              },
              false,
            );
            return xhr;
          },
          success: function (response) {
            if (response.status === "success") {
              Swal.fire({
                theme: swalTheme,
                title: "Success!",
                text: response.message,
                icon: "success",
                confirmButtonText: "Great",
                customClass: {
                  popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                  container: "overflow-hidden",
                  confirmButton: "btn btn-success px-4 py-2 rounded-3 me-2",
                },
              }).then(() => {
                localStorage.removeItem('database_newly_imported');
                window.location.reload();
              });
            } else {
              Swal.fire({
                theme: swalTheme,
                title: "Error",
                text: response.message,
                icon: "error",
                customClass: {
                  popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                  container: "overflow-hidden",
                  confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
                },
              });
            }
          },
          error: function (xhr) {
            const errorMsg = xhr.responseJSON ? xhr.responseJSON.message : "Import failed due to server error";
            Swal.fire({
              theme: swalTheme,
              title: "Import Failed",
              text: errorMsg,
              icon: "error",
              customClass: {
                popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                container: "overflow-hidden",
                confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
              },
            });
          },
        });
      }
    });
  });

  $("#clearActivityLogBtn").on("click", function () {
    ConfirmVersion(
      swalTheme,
      "Clear Activity Log?",
      "This will mark all activity logs for deletion. Logs will be permanently cleared once you click 'Save All Settings'.",
      "warning",
      "Yes, mark for clearing",
      "danger",
      "secondary",
      "Cancel",
    ).then((result) => {
      if (result.isConfirmed) {
        pendingClears.activityLog = true;
        $(this)
          .html('<i class="bi bi-clock-history"></i> Pending Clear')
          .css("background", "rgba(255, 193, 7, 0.2)")
          .css("color", "#ffc107")
          .css("border-color", "rgba(255, 193, 7, 0.3)");
        ToastVersion(swalTheme, "Activity log marked for deletion.", "info", 2000, "top-end");
      }
    });
  });

  $("#clearLoginLogBtn").on("click", function () {
    ConfirmVersion(
      swalTheme,
      "Clear Login Audit Log?",
      "This will mark all login audit logs for deletion. Logs will be permanently cleared once you click 'Save All Settings'.",
      "warning",
      "Yes, mark for clearing",
      "danger",
      "secondary",
      "Cancel",
    ).then((result) => {
      if (result.isConfirmed) {
        pendingClears.loginLog = true;
        $(this)
          .html('<i class="bi bi-clock-history"></i> Pending Clear')
          .css("background", "rgba(255, 193, 7, 0.2)")
          .css("color", "#ffc107")
          .css("border-color", "rgba(255, 193, 7, 0.3)");
        ToastVersion(swalTheme, "Login audit log marked for deletion.", "info", 2000, "top-end");
      }
    });
  });

  $("#systemResetBtn").on("click", function () {
    // Phase 1: Administrator Password Verification
    Swal.fire({
      theme: swalTheme,
      title: "Verify Admin Identity",
      text: "Please enter your administrator password to initiate the system reset process:",
      input: "password",
      inputPlaceholder: "Enter password",
      showCancelButton: true,
      confirmButtonText: "Next Step",
      confirmButtonColor: "#dc3545",
      cancelButtonText: "Abort",
      customClass: {
        popup: "glass-ui glass-ui-strong border border-2 rounded-3",
        confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
        cancelButton: "btn btn-secondary px-4 py-2 rounded-3"
      },
      preConfirm: (password) => {
        if (!password) {
          Swal.showValidationMessage("Password is required.");
          return false;
        }
        return $.ajax({
          url: "../../../process/admin/verify_admin_password",
          method: "POST",
          data: { password: password }
        }).then(response => {
          if (response.status === "success") {
            return true;
          }
          Swal.showValidationMessage("Verification failed.");
          return false;
        }).catch(xhr => {
          const msg = xhr.responseJSON ? xhr.responseJSON.message : "Incorrect administrator password.";
          Swal.showValidationMessage(msg);
          return false;
        });
      }
    }).then((pRes) => {
      if (!pRes.isConfirmed) return;

      // Phase 2: Acknowledgment Checkbox List
      Swal.fire({
        theme: swalTheme,
        title: "Acknowledge System Impact",
        html: `
          <p class="small text-muted mb-3">Please review and check all items to acknowledge the destructive impact of this reset:</p>
          <div class="text-start small p-3 rounded-4 bg-dark bg-opacity-25 border border-secondary" style="font-size: 0.85rem;">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="checkDbWipe">
              <label class="form-check-label text-white-50" for="checkDbWipe">
                I understand that all database records (students, coordinators, journals, and custom settings) will be permanently deleted.
              </label>
            </div>
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" id="checkFilesWipe">
              <label class="form-check-label text-white-50" for="checkFilesWipe">
                I understand that all uploaded files (certificates, selfies, NDAs, and profile photos) will be purged.
              </label>
            </div>
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="checkLogoutWipe">
              <label class="form-check-label text-white-50" for="checkLogoutWipe">
                I understand that my administrator account will be wiped and I will be redirected to configure the setup wizard.
              </label>
            </div>
          </div>
        `,
        showCancelButton: true,
        confirmButtonText: "Next Step",
        confirmButtonColor: "#dc3545",
        cancelButtonText: "Abort",
        customClass: {
          popup: "glass-ui glass-ui-strong border border-2 rounded-3",
          confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
          cancelButton: "btn btn-secondary px-4 py-2 rounded-3"
        },
        preConfirm: () => {
          const dbWiped = $("#checkDbWipe").is(":checked");
          const filesWiped = $("#checkFilesWipe").is(":checked");
          const logoutWiped = $("#checkLogoutWipe").is(":checked");
          
          if (!dbWiped || !filesWiped || !logoutWiped) {
            Swal.showValidationMessage("You must read and tick all acknowledgment checkboxes to proceed.");
            return false;
          }
          return true;
        }
      }).then((ackRes) => {
        if (!ackRes.isConfirmed) return;

        // Phase 3: Confirmation Word Typing
        Swal.fire({
          theme: swalTheme,
          title: "Final Confirmation Challenge",
          text: "Type the phrase 'WIPE MY SYSTEM' in the box below to execute the reset:",
          input: "text",
          inputPlaceholder: "WIPE MY SYSTEM",
          showCancelButton: true,
          confirmButtonText: "Execute Reset",
          confirmButtonColor: "#dc3545",
          cancelButtonText: "Abort",
          customClass: {
            popup: "glass-ui glass-ui-strong border border-2 rounded-3",
            confirmButton: "btn btn-danger px-4 py-2 rounded-3 me-2",
            cancelButton: "btn btn-secondary px-4 py-2 rounded-3"
          },
          preConfirm: (value) => {
            if (value !== "WIPE MY SYSTEM") {
              Swal.showValidationMessage("You must type 'WIPE MY SYSTEM' exactly.");
              return false;
            }
            return true;
          }
        }).then((challengeRes) => {
          if (!challengeRes.isConfirmed) return;

          // Perform System Reset via AJAX
          Swal.fire({
            theme: swalTheme,
            title: "Performing Fresh Start...",
            html: "Wiping all databases and files. Please wait...",
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            customClass: {
              popup: "glass-ui glass-ui-strong border border-2 rounded-3"
            },
            didOpen: () => {
              Swal.showLoading();
            }
          });

          $.ajax({
            url: "../../../process/admin/reset_system",
            method: "POST",
            success: function (response) {
              if (response.status === "success") {
                localStorage.removeItem('database_newly_imported');
                localStorage.removeItem('database_newly_exported');
                Swal.fire({
                  theme: swalTheme,
                  title: "System Reset Completed!",
                  text: response.message,
                  icon: "success",
                  confirmButtonText: "Launch Setup Wizard",
                  customClass: {
                    popup: "glass-ui glass-ui-strong border border-2 rounded-3",
                    confirmButton: "btn btn-success px-4 py-2 rounded-3"
                  }
                }).then(() => {
                  window.location.href = response.redirect_url || "../../../Src/Pages/Setup";
                });
              } else {
                Swal.fire({
                  theme: swalTheme,
                  title: "Reset Failed",
                  text: response.message,
                  icon: "error",
                  customClass: {
                    popup: "glass-ui glass-ui-strong border border-2 rounded-3"
                  }
                });
              }
            },
            error: function (xhr) {
              const msg = xhr.responseJSON ? xhr.responseJSON.message : "Reset failed due to server error.";
              Swal.fire({
                theme: swalTheme,
                title: "System Reset Failed",
                text: msg,
                icon: "error",
                customClass: {
                  popup: "glass-ui glass-ui-strong border border-2 rounded-3"
                }
              });
            }
          });
        });
      });
    });
  });

  $("#exportDatabaseBtn").on("click", function () {
    const structure = $("#export-structure").is(":checked") ? 1 : 0;
    const data = $("#export-data").is(":checked") ? 1 : 0;
    const drop = $("#export-drop").is(":checked") ? 1 : 0;

    ToastVersion(swalTheme, "Preparing database export...", "info", 2000, "top-end");

    localStorage.setItem('database_newly_exported', 'true');
    initializeExportFilesState();

    setTimeout(() => {
      window.location.href = `../../../process/admin/export_database.php?structure=${structure}&data=${data}&drop=${drop}`;

      setTimeout(loadDbStats, 1500);
    }, 1000);
  });

  $("#exportUploadsZipBtn").on("click", function () {
    ToastVersion(swalTheme, "Zipping uploads directory... This may take a few moments.", "info", 3000, "top-end");

    localStorage.removeItem('database_newly_exported');

    setTimeout(() => {
      initializeExportFilesState();
      window.location.href = "../../../process/admin/export_uploads_zip.php";
      
      setTimeout(loadDbStats, 2000);
    }, 1000);
  });

  // Security Tab Event Listeners
  $("#userSearchInput").on("keyup", debounce(handleUserSearch, 500));
  $("#unlockAccountBtn").on("click", handleUnlockAccount);
  $(document).on("click", ".user-search-item", handleUserSelect);
  $(document).on("click", ".manual-lock-option", handleManualLock);

  // Initialize security tab data when shown
  $('button[data-bs-target="#settings-security"]').on('shown.bs.tab', function() {
    handleUserSearch();
  });

  // Maintenance Tab Event Listeners
  $("#disableDtrSubmissionToggle").on("change", function () {
    const disabled = this.checked;
    $("#dtrToggleLabel").text(disabled ? "DISABLED" : "ENABLED")
      .removeClass("text-success text-danger")
      .addClass(disabled ? "text-danger" : "text-success");
    if (disabled) {
      $("#dtrReasonContainer").hide().removeClass("d-none").fadeIn(250);
    } else {
      $("#dtrReasonContainer").fadeOut(250, function() { $(this).addClass("d-none"); });
    }
  });

  $("#disableJournalSubmissionToggle").on("change", function () {
    const disabled = this.checked;
    $("#journalToggleLabel").text(disabled ? "DISABLED" : "ENABLED")
      .removeClass("text-success text-danger")
      .addClass(disabled ? "text-danger" : "text-success");
    if (disabled) {
      $("#journalReasonContainer").hide().removeClass("d-none").fadeIn(250);
    } else {
      $("#journalReasonContainer").fadeOut(250, function() { $(this).addClass("d-none"); });
    }
  });

  $("#disableEvaluationSubmissionToggle").on("change", function () {
    const disabled = this.checked;
    $("#evaluationToggleLabel").text(disabled ? "DISABLED" : "ENABLED")
      .removeClass("text-success text-danger")
      .addClass(disabled ? "text-danger" : "text-success");
    if (disabled) {
      $("#evaluationReasonContainer").hide().removeClass("d-none").fadeIn(250);
    } else {
      $("#evaluationReasonContainer").fadeOut(250, function() { $(this).addClass("d-none"); });
    }
  });
}

function handleLogoPreview(input, previewId) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function (e) {
      $(previewId).attr("src", e.target.result);
    };
    reader.readAsDataURL(input.files[0]);
  }
}

function handleDatabaseTabShown() {
  loadDbStats();
  animateSystemInfoCards();
}

function loadDbStats() {
  $.ajax({
    url: "../../../process/admin/get_db_stats",
    method: "GET",
    success: function (response) {
      if (response.status === "success") {
        $("#dbSizeValue").text(response.data.size);
        $("#dbTablesValue").text(response.data.tables);
        $("#dbRecordsValue").text(response.data.records);

        if (response.data.history) {
          renderExportHistory(response.data.history);
        }
      }
    },
    error: function (xhr) {
      console.error("Failed to load database stats:", xhr.responseText);
    },
  });
}

function renderExportHistory(history) {
  const container = $("#exportHistoryContainer");
  if (!history || history.length === 0) {
    container
      .addClass("text-center py-4 d-flex flex-column align-items-center justify-content-center h-75")
      .html(
        '<i class="bi bi-archive" style="font-size: 2.5rem; color: var(--settings-muted); opacity: 0.5;"></i><p class="mt-3 text-muted small">No recent exports found in this session</p>',
      );
    return;
  }

  container
    .removeClass("text-center py-4 d-flex flex-column align-items-center justify-content-center h-75")
    .addClass("mt-2")
    .html("");

  history.forEach((item) => {
    const historyItem = `
            <div class="d-flex align-items-center justify-content-between w-100 p-2 mb-2" style="background: rgba(var(--bs-body-color-rgb), 0.05); border-radius: 6px; border: 1px solid rgba(var(--bs-body-color-rgb), 0.1);">
                <div class="d-flex align-items-center">
                    <i class="bi bi-file-earmark-arrow-down text-primary me-3" style="font-size: 1.2rem;"></i>
                    <div class="text-start">
                        <div style="font-size: 0.85rem; font-weight: 500; color: var(--bs-body-color);">${item.type} Generated</div>
                        <div style="font-size: 0.75rem; color: var(--bs-secondary-color);">${item.date} at ${item.time}</div>
                    </div>
                </div>
                <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.7rem;">${item.status}</span>
            </div>
        `;
    container.append(historyItem);
  });
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
  $(`input[name="theme"][value="${mode}"]`).prop("checked", true);
}

function loadSavedSettings() {
  updateThemeSelection();

  $.ajax({
    url: "../../../process/admin/get_settings",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const settings = response.settings;

        if (settings.theme) {
          const appliedTheme = SetThemeMode(settings.theme, false);
          swalTheme = appliedTheme.swalTheme;
          updateThemeSelection(appliedTheme.mode);
        }

        if (settings.email) {
          $("#emailSmtpHost").val(settings.email.host);
          $("#emailSmtpPort").val(settings.email.port);
          $("#emailSmtpUser").val(settings.email.user);
          $("#emailSmtpPass").val(settings.email.pass);
          $("#emailSmtpCrypto").val(settings.email.crypto);
          $("#emailFromEmail").val(settings.email.from_email);
          $("#emailFromName").val(settings.email.from_name);
        }

        if (settings.institutional) {
          $("#instLongTitle").val(settings.institutional.long_title);
          $("#instShortTitle").val(settings.institutional.short_title);
          $("#instSystemDescription").val(settings.institutional.system_description);
          $("#instAuthor").val(settings.institutional.author);
          $("#instSchoolName").val(settings.institutional.school_name);
          $("#instSchoolMotto").val(settings.institutional.school_motto);
          $("#instSchoolAddress").val(settings.institutional.school_address);
          $("#instSchoolWebsite").val(settings.institutional.school_website);
          $("#instSchoolEmail").val(settings.institutional.school_email);
          $("#instSchoolPhone").val(settings.institutional.school_phone);
          $("#instFooterNote").val(settings.institutional.footer_note);
          $("#instVerificationNote").val(settings.institutional.verification_note);
          $("#instPageLink").val(settings.institutional.page_link);

          if (settings.institutional.logo_1) $("#logo1Preview").attr("src", settings.institutional.logo_1);
          if (settings.institutional.logo_2) $("#logo2Preview").attr("src", settings.institutional.logo_2);
        }

        if (settings.lockout_threshold) $("#lockoutAttemptsThreshold").val(settings.lockout_threshold);
        if (settings.lockout_duration) $("#lockoutInitialDuration").val(settings.lockout_duration);
        if (settings.lockout_notify_admin) $("#notifyAdminOnLockout").prop("checked", settings.lockout_notify_admin == "1");

        const formatDateTimeLocal = (val) => {
          if (!val) return "";
          if (val.includes("T")) return val.substring(0, 16);
          const parts = val.split(" ");
          if (parts.length >= 2) {
            return `${parts[0]}T${parts[1].substring(0, 5)}`;
          }
          return val;
        };

        if (settings.disable_dtr_submission !== undefined) {
          const isDtrDisabled = settings.disable_dtr_submission == "1";
          $("#disableDtrSubmissionToggle").prop("checked", isDtrDisabled);
          $("#dtrToggleLabel").text(isDtrDisabled ? "DISABLED" : "ENABLED")
            .removeClass("text-success text-danger")
            .addClass(isDtrDisabled ? "text-danger" : "text-success");
          if (isDtrDisabled) $("#dtrReasonContainer").removeClass("d-none");
          else $("#dtrReasonContainer").addClass("d-none");
        }
        if (settings.dtr_disable_reason !== undefined) {
          $("#dtrDisableReasonInput").val(settings.dtr_disable_reason);
        }
        if (settings.dtr_maintenance_start !== undefined) {
          $("#dtrMaintenanceStartInput").val(formatDateTimeLocal(settings.dtr_maintenance_start));
        }
        if (settings.dtr_maintenance_end !== undefined) {
          $("#dtrMaintenanceEndInput").val(formatDateTimeLocal(settings.dtr_maintenance_end));
        }

        if (settings.disable_journal_submission !== undefined) {
          const isJournalDisabled = settings.disable_journal_submission == "1";
          $("#disableJournalSubmissionToggle").prop("checked", isJournalDisabled);
          $("#journalToggleLabel").text(isJournalDisabled ? "DISABLED" : "ENABLED")
            .removeClass("text-success text-danger")
            .addClass(isJournalDisabled ? "text-danger" : "text-success");
          if (isJournalDisabled) $("#journalReasonContainer").removeClass("d-none");
          else $("#journalReasonContainer").addClass("d-none");
        }
        if (settings.journal_disable_reason !== undefined) {
          $("#journalDisableReasonInput").val(settings.journal_disable_reason);
        }
        if (settings.journal_maintenance_start !== undefined) {
          $("#journalMaintenanceStartInput").val(formatDateTimeLocal(settings.journal_maintenance_start));
        }
        if (settings.journal_maintenance_end !== undefined) {
          $("#journalMaintenanceEndInput").val(formatDateTimeLocal(settings.journal_maintenance_end));
        }

        if (settings.disable_evaluation_submission !== undefined) {
          const isEvalDisabled = settings.disable_evaluation_submission == "1";
          $("#disableEvaluationSubmissionToggle").prop("checked", isEvalDisabled);
          $("#evaluationToggleLabel").text(isEvalDisabled ? "DISABLED" : "ENABLED")
            .removeClass("text-success text-danger")
            .addClass(isEvalDisabled ? "text-danger" : "text-success");
          if (isEvalDisabled) $("#evaluationReasonContainer").removeClass("d-none");
          else $("#evaluationReasonContainer").addClass("d-none");
        }
        if (settings.evaluation_disable_reason !== undefined) {
          $("#evaluationDisableReasonInput").val(settings.evaluation_disable_reason);
        }
        if (settings.evaluation_maintenance_start !== undefined) {
          $("#evaluationMaintenanceStartInput").val(formatDateTimeLocal(settings.evaluation_maintenance_start));
        }
        if (settings.evaluation_maintenance_end !== undefined) {
          $("#evaluationMaintenanceEndInput").val(formatDateTimeLocal(settings.evaluation_maintenance_end));
        }
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function handleSaveSettingsClick() {
  const selectedTheme = $('input[name="theme"]:checked').val();
  const emailData = {
    host: $("#emailSmtpHost").val(),
    port: $("#emailSmtpPort").val(),
    user: $("#emailSmtpUser").val(),
    pass: $("#emailSmtpPass").val(),
    crypto: $("#emailSmtpCrypto").val(),
    from_email: $("#emailFromEmail").val(),
    from_name: $("#emailFromName").val(),
  };

  const instData = {
    long_title: $("#instLongTitle").val(),
    short_title: $("#instShortTitle").val(),
    system_description: $("#instSystemDescription").val(),
    author: $("#instAuthor").val(),
    school_name: $("#instSchoolName").val(),
    school_motto: $("#instSchoolMotto").val(),
    school_address: $("#instSchoolAddress").val(),
    school_website: $("#instSchoolWebsite").val(),
    school_email: $("#instSchoolEmail").val(),
    school_phone: $("#instSchoolPhone").val(),
    footer_note: $("#instFooterNote").val(),
    verification_note: $("#instVerificationNote").val(),
    page_link: $("#instPageLink").val(),
  };

  const dbData = {
    log_retention: $("#logRetentionPolicy").val(),
    auto_optimize: $("#autoOptimizeToggle").is(":checked"),
  };

  const securityData = {
    threshold: $("#lockoutAttemptsThreshold").val(),
    duration: $("#lockoutInitialDuration").val(),
    notify: $("#notifyAdminOnLockout").is(":checked") ? "1" : "0",
  };

  const maintenanceData = {
    disable_dtr_submission: $("#disableDtrSubmissionToggle").is(":checked") ? "1" : "0",
    dtr_disable_reason: $("#dtrDisableReasonInput").val(),
    dtr_maintenance_start: $("#dtrMaintenanceStartInput").val(),
    dtr_maintenance_end: $("#dtrMaintenanceEndInput").val(),
    disable_journal_submission: $("#disableJournalSubmissionToggle").is(":checked") ? "1" : "0",
    journal_disable_reason: $("#journalDisableReasonInput").val(),
    journal_maintenance_start: $("#journalMaintenanceStartInput").val(),
    journal_maintenance_end: $("#journalMaintenanceEndInput").val(),
    disable_evaluation_submission: $("#disableEvaluationSubmissionToggle").is(":checked") ? "1" : "0",
    evaluation_disable_reason: $("#evaluationDisableReasonInput").val(),
    evaluation_maintenance_start: $("#evaluationMaintenanceStartInput").val(),
    evaluation_maintenance_end: $("#evaluationMaintenanceEndInput").val(),
  };

  const formData = new FormData();
  formData.append("csrf_token", csrfToken);
  formData.append("theme", selectedTheme);
  formData.append("email_settings", JSON.stringify(emailData));
  formData.append("institutional_settings", JSON.stringify(instData));
  formData.append("database_settings", JSON.stringify(dbData));
  formData.append("security_settings", JSON.stringify(securityData));
  formData.append("maintenance_settings", JSON.stringify(maintenanceData));
  formData.append("clear_activity_log", pendingClears.activityLog);
  formData.append("clear_login_log", pendingClears.loginLog);

  if ($("#instLogo1")[0].files[0]) formData.append("logo_1", $("#instLogo1")[0].files[0]);
  if ($("#instLogo2")[0].files[0]) formData.append("logo_2", $("#instLogo2")[0].files[0]);

  ConfirmVersion(
    swalTheme,
    `Save Settings`,
    `This will save all changes including theme, institutional profile, email configuration, and destructive actions. Proceed?`,
    "question",
    "Yes, save all",
    "success",
    "danger",
    "Cancel",
    "center",
  ).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../../process/admin/save_settings",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        beforeSend: function () {
          $("#saveSettingsBtn")
            .prop("disabled", true)
            .html('<span class="spinner-border spinner-border-sm" role="status"></span> Saving...');
        },
        success: function (response) {
          if (response.status === "success") {
            SetThemeMode(selectedTheme, true);
            pendingClears.activityLog = false;
            pendingClears.loginLog = false;
            resetDangerButtons();
            ToastVersion(swalTheme, "All settings saved and actions executed.", "success", 2000, "top-end");

            $("#instLogo1").val("");
            $("#instLogo2").val("");
            loadSavedSettings();
          } else {
            ToastVersion(swalTheme, response.message || "Unable to save settings.", "warning", 3000, "top-end");
          }
        },
        error: function (xhr, status, error) {
          Errors(xhr, status, error);
        },
        complete: function () {
          $("#saveSettingsBtn")
            .prop("disabled", false)
            .html('<i class="bi bi-check-circle"></i> <span>Save All Settings</span>');
        },
      });
    }
  });
}

function resetDangerButtons() {
  $("#clearActivityLogBtn")
    .html('<i class="bi bi-trash"></i> Clear')
    .css("background", "")
    .css("color", "")
    .css("border-color", "");
  $("#clearLoginLogBtn")
    .html('<i class="bi bi-trash"></i> Clear')
    .css("background", "")
    .css("color", "")
    .css("border-color", "");
}

function handleResetSettingsClick() {
  ConfirmVersion(
    swalTheme,
    `Reset Settings`,
    `This will reset all settings to their default values. Are you sure you want to proceed?`,
    "warning",
    "Yes, reset",
    "success",
    "danger",
    "Cancel",
    "center",
  ).then((result) => {
    if (result.isConfirmed) {
      handleThemeModeChange("dark");
      updateThemeSelection("dark");
      $("#emailForm")[0].reset();
      pendingClears.activityLog = false;
      pendingClears.loginLog = false;
      resetDangerButtons();
      ToastVersion(swalTheme, "Settings reset in view. Click 'Save All Settings' to persist.", "info", 3000, "top-end");
    }
  });
}

function handleEmailTestClick() {
  const emailData = {
    host: $("#emailSmtpHost").val(),
    port: $("#emailSmtpPort").val(),
    user: $("#emailSmtpUser").val(),
    pass: $("#emailSmtpPass").val(),
    crypto: $("#emailSmtpCrypto").val(),
    from_email: $("#emailFromEmail").val(),
    from_name: $("#emailFromName").val(),
  };

  if (!emailData.host || !emailData.user || !emailData.pass) {
    ToastVersion(swalTheme, "Please fill in Host, Username, and Password to test.", "warning", 3000, "top-end");
    return;
  }

  ConfirmVersion(
    swalTheme,
    `Send Test Email`,
    `This will attempt to send a test email to ${emailData.from_email}. Proceed?`,
    "question",
    "Yes, send it",
    "success",
    "danger",
    "Cancel",
    "center",
  ).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../../process/admin/test_email_connection",
        type: "POST",
        dataType: "json",
        data: {
          csrf_token: csrfToken,
          ...emailData,
        },
        beforeSend: function () {
          $("#emailTestBtn")
            .prop("disabled", true)
            .html('<span class="spinner-border spinner-border-sm" role="status"></span> Testing...');
        },
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, "Test email sent successfully!", "success", 3000, "top-end");
          } else {
            ToastVersion(swalTheme, response.message || "Connection test failed.", "error", 5000, "top-end");
          }
        },
        error: function (xhr, status, error) {
          Errors(xhr, status, error);
        },
        complete: function () {
          $("#emailTestBtn").prop("disabled", false).html('<i class="bi bi-play-circle"></i> Test Connection');
        },
      });
    }
  });
}

function getStatusMeta(status) {
  const normalized = String(status || "").toLowerCase();
  const meta = {
    ok: { icon: "check-circle-fill", label: "Healthy", className: "is-ok" },
    warning: { icon: "exclamation-triangle-fill", label: "Review", className: "is-warning" },
    error: { icon: "x-circle-fill", label: "Issue", className: "is-error" },
  };
  return meta[normalized] || { icon: "info-circle-fill", label: "Info", className: "is-info" };
}

function getStatusBadgeHtml(status, message) {
  const meta = getStatusMeta(status);
  return `<span class="system-status-pill ${meta.className}"><i class="bi bi-${meta.icon}"></i> <span>${message || meta.label}</span></span>`;
}

function renderSystemInfoCard({ label, value, status, message, icon, wide = false }) {
  const meta = getStatusMeta(status);
  return `<article class="system-info-card ${wide ? "system-info-card-wide" : ""}" data-status="${meta.className}"><div class="system-info-card-top"><span class="system-info-icon ${meta.className}"><i class="bi bi-${icon}"></i></span><span class="system-status-dot ${meta.className}"></span></div><div class="system-info-body"><span class="system-info-label">${label}</span><strong class="system-info-value">${value}</strong></div>${getStatusBadgeHtml(status, message)}</article>`;
}

function animateSystemInfoCards() {
  const cards = document.querySelectorAll(
    '#settings-system .system-info-card:not([data-animated="true"]), #settings-database .system-info-card:not([data-animated="true"])',
  );
  if (!cards.length) return;
  cards.forEach((card) => (card.dataset.animated = "true"));
  animate(cards, { opacity: [0, 1], y: [18, 0], scale: [0.98, 1], duration: 620, delay: stagger(55), ease: "out(3)" });
}

function animateStorageMeters() {
  const meters = document.querySelectorAll('#storageInfoGrid .system-storage-meter-fill:not([data-animated="true"])');
  if (!meters.length) return;
  meters.forEach((meter) => {
    const targetWidth = meter.dataset.width || "0%";
    meter.dataset.animated = "true";
    meter.style.width = "0%";
    animate(meter, { width: targetWidth, duration: 850, ease: "out(3)" });
  });
}

function loadSystemInfo() {
  $.ajax({
    url: "../../../process/admin/get_system_info",
    type: "GET",
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        renderSystemInfo(response.data);
      }
    },
  });
}

function renderSystemInfo(data) {
  const $envGrid = $("#environmentInfoGrid");
  if (!$envGrid.length) return;
  let envHtml = "";
  if (data.php)
    envHtml += renderSystemInfoCard({
      label: "PHP Version",
      value: data.php.value,
      status: data.php.status,
      message: data.php.message,
      icon: "code-slash",
    });
  if (data.database)
    envHtml += renderSystemInfoCard({
      label: "Database",
      value: data.database.value,
      status: data.database.status,
      message: data.database.message,
      icon: "database-check",
      wide: true,
    });
  if (data.operatingSystem)
    envHtml += renderSystemInfoCard({
      label: "Operating System",
      value: data.operatingSystem.value,
      status: data.operatingSystem.status,
      message: data.operatingSystem.message,
      icon: "pc-display",
      wide: true,
    });
  if (data.modRewrite)
    envHtml += renderSystemInfoCard({
      label: "mod_rewrite",
      value: data.modRewrite.value,
      status: data.modRewrite.status,
      message: data.modRewrite.message,
      icon: "shuffle",
    });
  if (data.serverSoftware)
    envHtml += renderSystemInfoCard({
      label: "Server Software",
      value: data.serverSoftware.value,
      status: data.serverSoftware.status,
      message: data.serverSoftware.message,
      icon: "hdd-network",
      wide: true,
    });
  if (data.diskSpace)
    envHtml += renderSystemInfoCard({
      label: "Disk Space",
      value: data.diskSpace.value,
      status: data.diskSpace.status,
      message: data.diskSpace.message,
      icon: "device-hdd",
    });
  if (data.memoryUsage)
    envHtml += renderSystemInfoCard({
      label: "Memory Usage",
      value: data.memoryUsage.value,
      status: data.memoryUsage.status,
      message: data.memoryUsage.message,
      icon: "memory",
    });
  if (data.extensions)
    envHtml += renderSystemInfoCard({
      label: "PHP Extensions",
      value: data.extensions.value,
      status: data.extensions.missing.length === 0 ? "ok" : "warning",
      message: data.extensions.message,
      icon: "puzzle",
    });
  $envGrid.html(envHtml);
  renderStorageInfo(data);
}

function renderStorageInfo(data) {
  const $storageGrid = $("#storageInfoGrid");
  if (!$storageGrid.length || !data.storage) return;
  let storageHtml = "";
  if (data.fileUpload) {
    storageHtml += `<article class="system-info-card"><div class="system-info-card-top"><span class="system-info-icon ${getStatusMeta(data.fileUpload.status).className}"><i class="bi bi-cloud-arrow-up"></i></span><span class="system-status-dot ${getStatusMeta(data.fileUpload.status).className}"></span></div><div class="system-info-body"><span class="system-info-label">File Upload Limits</span><strong class="system-info-value">${data.fileUpload.value}</strong><small class="system-info-note">${data.fileUpload.message}</small></div>${getStatusBadgeHtml(data.fileUpload.status, "Configured")}</article>`;
  }
  data.storage.forEach((dir, index) => {
    const maxSize = 500 * 1024 * 1024;
    const percentage = Math.min(Math.round((dir.size / maxSize) * 100), 100);
    const cardClass = index === 0 ? "system-info-card-wide" : "";
    const dirStatusMeta = getStatusMeta(dir.status);
    storageHtml += `<article class="system-info-card ${cardClass}"><div class="system-info-card-top"><span class="system-info-icon ${dirStatusMeta.className}"><i class="bi bi-folder2-open"></i></span><span class="system-status-dot ${dirStatusMeta.className}"></span></div><div class="system-info-body"><span class="system-info-label">${dir.name}</span><strong class="system-info-value text-break">${dir.path}</strong><div class="system-storage-meta"><span>${dir.sizeFormatted} used</span><span>${percentage}%</span></div><div class="system-storage-meter"><div class="system-storage-meter-fill" data-width="${percentage}%"></div></div></div>${getStatusBadgeHtml(dir.status, dir.message)}</article>`;
  });
  $storageGrid.html(storageHtml);
  animateSystemSectionIfVisible();
}

function animateSystemSectionIfVisible() {
  if (!$("#settings-system").hasClass("active")) return;
  animateSystemInfoCards();
  animateStorageMeters();
}
let selectedUserUuid = null;

function handleUserSearch() {
  const query = $("#userSearchInput").val().trim();
  
  // Don't search if only 1 character is typed
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
        $("#userSearchResults").html(`<div class="alert alert-danger mx-3 mt-3">${response.message}</div>`);
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
  html += '<div class="list-group list-group-flush">';
  users.forEach((user) => {
    const statusBadge = user.is_locked 
      ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle ms-auto">LOCKED</span>' 
      : '<span class="badge bg-success-subtle text-success border border-success-subtle ms-auto">ACTIVE</span>';
    
    html += `
      <a href="#" class="list-group-item list-group-item-action user-search-item d-flex align-items-center py-3 border-color-opacity-1" 
         data-user='${JSON.stringify(user).replace(/'/g, "&apos;")}'>
        <div class="rounded-circle d-flex align-items-center justify-content-center me-3" 
             style="width: 40px; height: 40px; background: rgba(var(--bs-primary-rgb), 0.1); color: var(--bs-primary); font-weight: 600; font-size: 0.9rem;">
          ${user.initials}
        </div>
        <div class="overflow-hidden">
          <div class="fw-bold text-truncate" style="color: var(--bs-body-color);">${user.name}</div>
          <div class="text-muted small text-truncate">${user.email}</div>
        </div>
        ${statusBadge}
      </a>
    `;
  });
  html += "</div>";
  $("#userSearchResults").html(html);
}

function handleUserSelect(e) {
  e.preventDefault();
  const userData = $(this).data("user");
  selectedUserUuid = userData.uuid;

  $("#noUserSelectedPlaceholder").hide();
  $("#userLockoutDetails").fadeIn();

  $("#selectedUserInitials").text(userData.initials);
  $("#selectedUserName").text(userData.name);
  $("#selectedUserEmail").text(userData.email);
  $("#selectedUserRole").text(userData.role);
  $("#failedAttemptsCount").text(userData.login_attempts);

  const statusBadge = userData.is_locked 
    ? '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">LOCKED</span>' 
    : '<span class="badge bg-success-subtle text-success border border-success-subtle">ACTIVE</span>';
  $("#selectedUserStatus").html(statusBadge);

  if (userData.is_locked) {
    $("#lockoutExpiryText").text(userData.lockout_until);
    $("#unlockAccountBtn").prop("disabled", false);
    $("#lockoutInfoBox").css("background", "rgba(var(--bs-danger-rgb), 0.05)");
  } else {
    $("#lockoutExpiryText").text("Not Locked");
    $("#unlockAccountBtn").prop("disabled", true);
    $("#lockoutInfoBox").css("background", "rgba(var(--bs-body-color-rgb), 0.05)");
  }

  $(".user-search-item").removeClass("active bg-primary-subtle");
  $(this).addClass("bg-primary-subtle");
}

function handleUnlockAccount() {
  if (!selectedUserUuid) return;

  ConfirmVersion(
    swalTheme,
    "Unlock Account",
    "This will clear all failed attempts and remove any active lockouts. Proceed?",
    "question",
    "Yes, Unlock",
    "success",
    "danger",
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
          csrf_token: csrfToken
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            handleUserSearch(); // Refresh list
            $("#userLockoutDetails").hide();
            $("#noUserSelectedPlaceholder").show();
          } else {
            ToastVersion(swalTheme, response.message, "error", 5000, "top-end");
          }
        }
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
    `Are you sure you want to manually lock this account for ${durationText}?`,
    "warning",
    "Yes, Lock",
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
          csrf_token: csrfToken
        },
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end");
            handleUserSearch(); // Refresh list
            $("#userLockoutDetails").hide();
            $("#noUserSelectedPlaceholder").show();
          } else {
            ToastVersion(swalTheme, response.message, "error", 5000, "top-end");
          }
        }
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

const ALERT_TYPE_META = {
  info:    { label: 'Info',    badge: 'bg-primary-subtle text-primary border-primary-subtle',   icon: 'bi-info-circle-fill' },
  success: { label: 'Success', badge: 'bg-success-subtle text-success border-success-subtle',   icon: 'bi-check-circle-fill' },
  warning: { label: 'Warning', badge: 'bg-warning-subtle text-warning-emphasis border-warning-subtle', icon: 'bi-exclamation-triangle-fill' },
  danger:  { label: 'Danger',  badge: 'bg-danger-subtle text-danger border-danger-subtle',      icon: 'bi-x-octagon-fill' },
};

const DISPLAY_TYPE_LABEL = { banner: 'Banner', modal: 'Modal', toast: 'Toast' };

function initAlertsTab() {
  $("#alertCreateBtn").on("click", handleAlertCreate);
  $('button[data-bs-target="#settings-alerts"]').on('shown.bs.tab', loadAlertList);
  loadAlertList();
}

function loadAlertList() {
  $.ajax({
    url: "../../../process/admin/manage_alerts?action=list",
    method: "GET",
    success: function (response) {
      if (response.status === "success") {
        renderAlertList(response.alerts);
      } else {
        $("#alertListContainer").html('<p class="text-danger small">Failed to load alerts.</p>');
      }
    },
    error: function () {
      $("#alertListContainer").html('<p class="text-danger small">Server error loading alerts.</p>');
    },
  });
}

function renderAlertList(alerts) {
  const container = $("#alertListContainer");
  if (!alerts || alerts.length === 0) {
    container.html(`
      <div class="text-center py-5 text-muted">
        <i class="bi bi-megaphone" style="font-size:2.5rem;opacity:.3;"></i>
        <p class="mt-3 small">No alerts created yet. Use the form above to broadcast one.</p>
      </div>
    `);
    return;
  }

  let html = '<div class="vstack gap-3">';
  alerts.forEach((a) => {
    const meta = ALERT_TYPE_META[a.alert_type] || ALERT_TYPE_META.info;
    const displayLabel = DISPLAY_TYPE_LABEL[a.display_type] || a.display_type;
    const expiresLabel = a.expires_at ? `Expires: ${a.expires_at}` : 'No expiry';
    const dismissLabel = a.dismissible ? 'Dismissible' : 'Permanent';
    const toggleLabel  = a.is_active ? 'Deactivate' : 'Activate';
    const toggleIcon   = a.is_active ? 'bi-toggle-on text-success' : 'bi-toggle-off text-secondary';
    const activePill   = a.is_active
      ? '<span class="badge bg-success-subtle text-success border border-success-subtle">Active</span>'
      : '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">Inactive</span>';

    html += `
      <div class="d-flex align-items-start gap-3 p-3 rounded-3 border"
           style="background:rgba(var(--bs-body-color-rgb),.02); border-color:rgba(var(--bs-body-color-rgb),.1)!important;"
           data-alert-id="${a.id}">
        <span class="badge border ${meta.badge} d-flex align-items-center gap-1 flex-shrink-0 mt-1">
          <i class="bi ${meta.icon}"></i> ${meta.label}
        </span>
        <div class="flex-grow-1 min-width-0">
          <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
            <strong style="color:var(--bs-body-color);">${escapeHtmlSettings(a.title)}</strong>
            ${activePill}
            <span class="badge bg-info-subtle text-info border border-info-subtle">${displayLabel}</span>
          </div>
          <p class="mb-1 small" style="opacity:.8;white-space:pre-line;">${escapeHtmlSettings(a.message)}</p>
          <div class="d-flex gap-3 flex-wrap" style="font-size:.75rem;color:var(--bs-secondary-color);">
            <span><i class="bi bi-people"></i> ${a.target_roles}</span>
            <span><i class="bi bi-calendar-x"></i> ${expiresLabel}</span>
            <span><i class="bi bi-hand-index"></i> ${dismissLabel}</span>
            <span><i class="bi bi-graph-down"></i> ${a.dismiss_count} dismissed</span>
            <span><i class="bi bi-clock"></i> ${a.created_at}</span>
          </div>
        </div>
        <div class="d-flex flex-column gap-2 flex-shrink-0">
          <button class="btn btn-sm btn-outline-secondary rounded-3 alert-toggle-btn"
                  data-id="${a.id}" title="${toggleLabel}">
            <i class="bi ${toggleIcon}"></i>
          </button>
          <button class="btn btn-sm btn-outline-danger rounded-3 alert-delete-btn"
                  data-id="${a.id}" title="Delete">
            <i class="bi bi-trash"></i>
          </button>
        </div>
      </div>
    `;
  });
  html += '</div>';
  container.html(html);

  // Bind dynamic events
  $(document).off("click", ".alert-toggle-btn").on("click", ".alert-toggle-btn", handleAlertToggle);
  $(document).off("click", ".alert-delete-btn").on("click", ".alert-delete-btn", handleAlertDelete);
}

function handleAlertCreate() {
  const title        = $("#alertTitle").val().trim();
  const message      = $("#alertMessage").val().trim();
  const alertType    = $("#alertType").val();
  const displayType  = $("#alertDisplayType").val();
  const targetRoles  = $("#alertTargetRoles").val();
  const expiresAt    = $("#alertExpiresAt").val();
  const dismissible  = $("#alertDismissible").is(":checked") ? "on" : "";

  if (!title || !message) {
    ToastVersion(swalTheme, "Title and message are required.", "warning", 3000, "top-end");
    return;
  }

  const btn = $("#alertCreateBtn");
  const orig = btn.html();
  btn.prop("disabled", true).html('<span class="spinner-border spinner-border-sm"></span> Sending...');

  $.ajax({
    url: "../../../process/admin/manage_alerts",
    method: "POST",
    data: {
      action: "create",
      csrf_token: csrfToken,
      title, message,
      alert_type:   alertType,
      display_type: displayType,
      target_roles: targetRoles,
      expires_at:   expiresAt,
      dismissible,
    },
    success: function (response) {
      if (response.status === "success") {
        ToastVersion(swalTheme, "Alert broadcast successfully!", "success", 3000, "top-end");
        $("#alertCreateForm")[0].reset();
        loadAlertList();
      } else {
        ToastVersion(swalTheme, response.message || "Failed to create alert.", "error", 3000, "top-end");
      }
    },
    error: function (xhr) {
      Errors(xhr, "error", "Create alert failed");
    },
    complete: function () {
      btn.prop("disabled", false).html(orig);
    },
  });
}

function handleAlertToggle() {
  const id  = $(this).data("id");
  const btn = $(this);

  $.ajax({
    url: "../../../process/admin/manage_alerts",
    method: "POST",
    data: { action: "toggle", csrf_token: csrfToken, id },
    success: function (response) {
      if (response.status === "success") {
        const active = response.is_active;
        btn.find("i")
           .removeClass("bi-toggle-on bi-toggle-off text-success text-secondary")
           .addClass(active ? "bi-toggle-on text-success" : "bi-toggle-off text-secondary");
        btn.attr("title", active ? "Deactivate" : "Activate");
        // Refresh the active pill
        loadAlertList();
        ToastVersion(swalTheme, active ? "Alert activated." : "Alert deactivated.", "info", 2000, "top-end");
      }
    },
    error: function (xhr) { Errors(xhr, "error", "Toggle failed"); },
  });
}

function handleAlertDelete() {
  const id = $(this).data("id");

  ConfirmVersion(
    swalTheme,
    "Delete Alert?",
    "This will permanently delete the alert and all dismissal records. This cannot be undone.",
    "warning", "Yes, delete", "danger", "secondary", "Cancel", "center"
  ).then((result) => {
    if (result.isConfirmed) {
      $.ajax({
        url: "../../../process/admin/manage_alerts",
        method: "POST",
        data: { action: "delete", csrf_token: csrfToken, id },
        success: function (response) {
          if (response.status === "success") {
            ToastVersion(swalTheme, "Alert deleted.", "success", 2000, "top-end");
            loadAlertList();
          }
        },
        error: function (xhr) { Errors(xhr, "error", "Delete failed"); },
      });
    }
  });
}

function escapeHtmlSettings(str) {
  return String(str)
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;");
}

// Wire up alerts tab on page init
$(document).ready(function () {
  initAlertsTab();
});

function initializeImportFilesState() {
  const isDbImported = localStorage.getItem('database_newly_imported') === 'true';
  const section = $("#importFilesSection");
  const dropZone = $("#zipDropZone");
  const validateZipBtn = $("#validateZipBtn");
  const importZipBtn = $("#importZipBtn");

  $("#dbImportBadge").remove();
  $("#dbImportAlert").remove();

  if (isDbImported) {
    section.css({ opacity: "1" });
    dropZone.css({ "pointer-events": "auto", "cursor": "pointer", "opacity": "1" });
    validateZipBtn.css({ "pointer-events": "auto" });
    importZipBtn.css({ "pointer-events": "auto" });
    section.find(".section-header-text h5").append('<span class="badge bg-success ms-2 align-middle" id="dbImportBadge" style="font-size: 0.75rem;"><i class="bi bi-check-circle me-1"></i>Database Restored</span>');
    section.find(".section-header").after(`
      <div class="alert alert-info border-0 rounded-4 my-2 d-flex align-items-center gap-2 p-2 small" id="dbImportAlert" style="background: rgba(var(--bs-info-rgb), 0.08); border-left: 4px solid var(--bs-info) !important;">
        <i class="bi bi-info-circle-fill text-info"></i>
        <span>Database successfully restored! You can now upload the files .zip backup to sync profile pictures and certificate files.</span>
      </div>
    `);
  } else {
    section.css({ opacity: "0.85" });
    dropZone.css({ "pointer-events": "none", "cursor": "not-allowed", "opacity": "0.5" });
    validateZipBtn.css({ "pointer-events": "none" }).prop("disabled", true);
    importZipBtn.css({ "pointer-events": "none" }).prop("disabled", true);
    section.find(".section-header-text h5").append('<span class="badge bg-secondary ms-2 align-middle" id="dbImportBadge" style="font-size: 0.75rem;"><i class="bi bi-lock-fill me-1"></i>Locked</span>');
    section.find(".section-header").after(`
      <div class="alert alert-secondary border-0 rounded-4 my-2 d-flex align-items-center gap-2 p-2 small" id="dbImportAlert" style="background: rgba(var(--bs-body-color-rgb), 0.05); border-left: 4px solid var(--bs-secondary) !important;">
        <i class="bi bi-lock-fill text-secondary"></i>
        <span>Please import/restore a database (.sql) first. Once complete, this section will unlock to let you restore matching uploads.</span>
      </div>
    `);
  }
}

function initializeExportFilesState() {
  const isDbExported = localStorage.getItem('database_newly_exported') === 'true';
  const zipBtn = $("#exportUploadsZipBtn");

  if (isDbExported) {
    zipBtn.prop("disabled", false)
          .css({ opacity: "1", "pointer-events": "auto", "cursor": "pointer" })
          .removeAttr("title");
  } else {
    zipBtn.prop("disabled", true)
          .css({ opacity: "0.5", "pointer-events": "none", "cursor": "not-allowed" })
          .attr("title", "Please export the database first before downloading uploads.");
  }
}
