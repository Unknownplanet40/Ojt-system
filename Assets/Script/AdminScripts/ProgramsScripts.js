import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "default", "fast");
let showInactive = false;
let allPrograms = [];
let currentSearchTerm = "";
let currentDepartment = "";

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const randomConformationWord = ["CONFIRM", "AGREE", "YES", "OK", "PROCEED", "ACCEPT", "VALIDATE", "APPROVE", "ACKNOWLEDGE", "CONSENT"];

const emptyProgramsRow = `<tr class="border-0">
                            <td colspan="7" class="text-center py-5 bg-transparent border-0" style="cursor: wait;">
                                <div class="d-flex flex-column align-items-center gap-3">
                                    <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex justify-content-center align-items-center" style="width: 48px; height: 48px;">
                                        <i class="bi bi-inbox fw-bold fs-4"></i>
                                    </div>
                                    <div>
                                        <p class="mb-2 text-body fw-semibold">No programs found</p>
                                        <small class="text-muted d-block" style="font-size: 0.875rem;">Click "add program" to create a new program</small>
                                    </div>
                                </div>
                            </td>
                        </tr>`;

function normalizeValue(value) {
  return (value || "").toString().trim();
}

function populateDepartmentFilter(programs) {
  const select = $("#departmentFilterSelect");
  const selectedValue = currentDepartment;

  const departments = [...new Set(programs.map((program) => normalizeValue(program.department)).filter(Boolean))].sort((a, b) =>
    a.localeCompare(b)
  );

  select.empty();
  select.append('<option value="" class="CustomOption">All departments</option>');

  departments.forEach((department) => {
    select.append($("<option></option>").addClass("CustomOption").val(department).text(department));
  });

  if (selectedValue && departments.includes(selectedValue)) {
    select.val(selectedValue);
  } else {
    currentDepartment = "";
    select.val("");
  }
}

function renderProgramsTable() {
  const programsTable = $("#programsTable tbody");
  programsTable.empty();

  const keyword = currentSearchTerm.toLowerCase();
  const filteredPrograms = allPrograms.filter((program) => {
    const code = normalizeValue(program.code);
    const name = normalizeValue(program.name);
    const department = normalizeValue(program.department);

    const matchesSearch = !keyword || code.toLowerCase().includes(keyword) || name.toLowerCase().includes(keyword) || department.toLowerCase().includes(keyword);
    const matchesDepartment = !currentDepartment || department === currentDepartment;

    return matchesSearch && matchesDepartment;
  });

  if (filteredPrograms.length === 0) {
    programsTable.html(emptyProgramsRow);
    return;
  }

  filteredPrograms.forEach(function (program) {
    const statusBgClass = program.is_active ? "bg-success" : "bg-danger";
    const row = `<tr class="align-middle border-0">
                  <td class="ps-4 py-3 bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">${program.code}</td>
                  <td class="py-3 bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">${program.name}</td>
                  <td class="py-3 d-none d-md-table-cell bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">${program.department}</td>
                  <td class="py-3 text-center d-none d-lg-table-cell bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">${program.required_hours}</td>
                  <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">${program.student_count}</td>
                  <td class="py-3 text-center bg-blur-5 bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem;">
                    <span class="badge ${statusBgClass} bg-opacity-25 text-${program.is_active ? "success" : "danger"}-emphasis px-3 py-2 rounded-pill" style="font-size: 0.75rem;">${program.status_label}</span>
                  </td>
                  <td class="py-3 text-center bg-semi-transparent border-0 shadow-sm" style="--blur-lvl: 0.55; font-size: 0.875rem; width: 50px;">
                    <div class="position-relative">
                      <button class="btn btn-sm btn-outline-secondary border-0 py-1 px-2 rounded-3 ddbtn" type="button" data-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                      </button>
                      <ul class="customDropdown bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.55; display: none; position: absolute; top: 100%; right: 0; min-width: 160px; z-index: 1000; list-style: none; margin: 0; padding: 0;">
                    <li><button class="dropdown-item text-body" id="editProgramBtn-${program.uuid}" style="padding: 8px 12px; font-size: 0.875rem; background-color: transparent; border: none; width: 100%; text-align: left; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#EditProgramModal"><i class="bi bi-pencil-square me-2"></i>Edit details</button></li>
                    <li><button class="dropdown-item text-danger" id="disableProgramBtn-${program.uuid}" style="padding: 8px 12px; font-size: 0.875rem; background-color: transparent; border: none; width: 100%; text-align: left; cursor: pointer; display: ${program.is_active ? "block" : "none"};" data-bs-toggle="modal" data-bs-target="#disableProgramModal"><i class="bi bi-x-octagon-fill me-2"></i>Disable program</button></li>
                    <li><button class="dropdown-item text-success" id="enableProgramBtn-${program.uuid}" style="padding: 8px 12px; font-size: 0.875rem; background-color: transparent; border: none; width: 100%; text-align: left; cursor: pointer; display: ${program.is_active ? "none" : "block"};" data-bs-toggle="modal" data-bs-target="#enableProgramModal"><i class="bi bi-check-circle me-2"></i>Enable program</button></li>
                      </ul>
                    </div>
                  </td>
                </tr>`;
    programsTable.append(row);

    $(`#editProgramBtn-${program.uuid}`).on("click", function () {
      getProgramDetails(program.uuid);
    });

    $(`#disableProgramBtn-${program.uuid}`).on("click", function () {
      $("#programToDisableName").text(program.name);
      $("#disableProgramModal").attr("data-disableprogram-uuid", program.uuid);
      const randomWord = randomConformationWord[Math.floor(Math.random() * randomConformationWord.length)];
      $("#disableProgramNameConfirm").text(randomWord);
      $("#disableProgramInput").attr("placeholder", `Type "${randomWord}" to confirm`).val("").removeClass("is-invalid");

      $("#disableProgramModal")
        .off("hidden.bs.modal")
        .on("hidden.bs.modal", function () {
          $("#programToDisableName").text("");
          $("#disableProgramNameConfirm").text("");
          $("#disableProgramInput").val("").removeClass("is-invalid");
          $(this).removeAttr("data-disableprogram-uuid");
        });
    });

    $(`#enableProgramBtn-${program.uuid}`).on("click", function () {
      $("#programToEnableName").text(program.name);
      $("#enableProgramModal").attr("data-enableprogram-uuid", program.uuid);
      const randomWord = randomConformationWord[Math.floor(Math.random() * randomConformationWord.length)];
      $("#enableProgramNameConfirm").text(randomWord);
      $("#enableProgramInput").attr("placeholder", `Type "${randomWord}" to confirm`).val("").removeClass("is-invalid");

      $("#enableProgramModal")
        .off("hidden.bs.modal")
        .on("hidden.bs.modal", function () {
          $("#programToEnableName").text("");
          $("#enableProgramNameConfirm").text("");
          $("#enableProgramInput").val("").removeClass("is-invalid");
          $(this).removeAttr("data-enableprogram-uuid");
        });
    });
  });
}

function getProgramDetails(programUuid) {
  if (!programUuid) {
    ToastVersion(swalTheme, "Program identifier is missing.", "error", 3000);
    return;
  }

  $.ajax({
    url: "../../../process/programs/get_program",
    method: "POST",
    data: { csrf_token: csrfToken, program_uuid: programUuid },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const program = response.program;
        $("#editProgramCodeInput").val(program.code);
        $("#editProgramNameInput").val(program.name);
        $("#editProgramDepartmentInput").val(program.department);
        $("#editRequiredHoursInput").val(program.required_hours);
        $("#EditProgramModal").attr("data-editprogram-uuid", program.uuid);
        $("#editActivateImmediatelySwitch").prop("checked", program.is_active ? true : false);

        $("#EditProgramModal")
          .off("hidden.bs.modal")
          .on("hidden.bs.modal", function () {
            $(this).removeAttr("data-editprogram-uuid");
            $("#editProgramCodeInput").val("");
            $("#editProgramNameInput").val("");
            $("#editProgramDepartmentInput").val("");
            $("#editRequiredHoursInput").val("");
            $("#editActivateImmediatelySwitch").prop("checked", false);
          });
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000);
      } else {
        ToastVersion(swalTheme, response.Details || "An error occurred while fetching program details", "error", 5000);
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function getPrograms(activeOnly = true) {
  const programsTable = $("#programsTable tbody");
  programsTable.empty();
  programsTable.html(emptyProgramsRow);

  $.ajax({
    url: "../../../process/programs/get_programs",
    method: "POST",
    data: { csrf_token: csrfToken, active_only: activeOnly ? 1 : 0 },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        allPrograms = Array.isArray(response.programs) ? response.programs : [];
        populateDepartmentFilter(allPrograms);
        renderProgramsTable();
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 5000);
      } else {
        ToastVersion(swalTheme, response.Details || "An error occurred while fetching programs", "error", 5000);
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

$(document).ready(function () {
  $(document).on("click", function (e) {
    const target = $(e.target);
    const toggleBtn = target.closest('[data-toggle="dropdown"]');
    const dropdownItem = target.closest(".dropdown-item");

    if (toggleBtn.length) {
      const menu = toggleBtn.next(".customDropdown");
      $(".customDropdown").not(menu).hide();

      // Toggle state
      const willOpen = !menu.is(":visible");
      if (!willOpen) {
        menu.hide();
        return;
      }

      // Find if this is the last visible row
      const row = toggleBtn.closest("tr");
      const tbody = row.parent();
      const isLastVisibleRow = row.is(tbody.children("tr:visible").last());

      // Measure space above/below button
      menu.css({ display: "block", visibility: "hidden", top: "100%", bottom: "auto" });
      const menuHeight = menu.outerHeight() || 0;
      const btnRect = toggleBtn[0].getBoundingClientRect();
      const spaceBelow = window.innerHeight - btnRect.bottom;
      const spaceAbove = btnRect.top;

      // Open upward if last row OR not enough space below
      const openUp = isLastVisibleRow || (spaceBelow < menuHeight && spaceAbove > spaceBelow);

      if (openUp) {
        menu.css({ top: "auto", bottom: "100%", visibility: "visible" });
      } else {
        menu.css({ top: "100%", bottom: "auto", visibility: "visible" });
      }
    } else if (dropdownItem.length) {
      dropdownItem.closest(".customDropdown").hide();
    } else {
      $(".customDropdown").hide();
    }
  });

  $("#programSearchInput").on("input", function () {
    currentSearchTerm = $(this).val().trim();
    renderProgramsTable();
  });

  $("#departmentFilterSelect").on("change", function () {
    currentDepartment = $(this).val() || "";
    renderProgramsTable();
  });

  getPrograms(showInactive);

  $("#saveEditProgramBtn").on("click", function () {
    const programUuid = $("#EditProgramModal").attr("data-editprogram-uuid");
    const code = $("#editProgramCodeInput").val().trim();
    const name = $("#editProgramNameInput").val().trim();
    const department = $("#editProgramDepartmentInput").val().trim();
    const requiredHours = $("#editRequiredHoursInput").val().trim();
    const activateImmediately = $("#editActivateImmediatelySwitch").is(":checked");

    if (!code || !name || !department || !requiredHours) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    if (isNaN(requiredHours) || parseInt(requiredHours) < 0) {
      ToastVersion(swalTheme, "Required hours must be a non-negative number.", "warning", 3000);
      return;
    }

    if (!programUuid) {
      ToastVersion(swalTheme, "Program identifier is missing.", "error", 3000);
      return;
    }

    $.ajax({
      url: "../../../process/programs/update_program",
      method: "POST",
      data: {
        csrf_token: csrfToken,
        program_uuid: programUuid,
        code: code,
        name: name,
        department: department,
        required_hours: requiredHours,
        is_active: activateImmediately ? 1 : 0,
      },
      dataType: "json",
      beforeSend: function () {
        $("#saveEditProgramBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program updated successfully.", "success", 3000);
          $("#EditProgramModal").modal("hide");
          getPrograms(showInactive);
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 5000);
        } else {
          ToastVersion(swalTheme, response.Details || "An error occurred while updating the program.", "error", 5000);
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
      complete: function () {
        $("#saveEditProgramBtn").prop("disabled", false).html("Save changes");
      },
    });
  });

  $("#disableProgramInput").on("input", function () {
    const confirmationWord = $("#disableProgramNameConfirm").text();
    const inputVal = $(this).val().trim().toUpperCase();
    if (inputVal === confirmationWord) {
      $(this).removeClass("is-invalid").addClass("is-valid");
      $("#confirmDisableProgramBtn").prop("disabled", false);
    } else {
      $(this).removeClass("is-valid").addClass("is-invalid");
      $("#confirmDisableProgramBtn").prop("disabled", true);
    }
  });

  $("#confirmDisableProgramBtn").on("click", function () {
    const programUuid = $("#disableProgramModal").attr("data-disableprogram-uuid");
    if (!programUuid) {
      ToastVersion(swalTheme, "Program identifier is missing.", "error", 3000);
      return;
    }

    $.ajax({
      url: "../../../process/programs/toggle_program",
      method: "POST",
      data: { csrf_token: csrfToken, program_uuid: programUuid },
      dataType: "json",
      beforeSend: function () {
        $("#confirmDisableProgramBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Disabling...');
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program disabled successfully.", "success", 3000);
          $("#disableProgramModal").modal("hide");
          getPrograms(showInactive);
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 5000);
        } else {
          ToastVersion(swalTheme, response.Details || "An error occurred while disabling the program.", "error", 5000);
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });

  $("#enableProgramInput").on("input", function () {
    const confirmationWord = $("#enableProgramNameConfirm").text();
    const inputVal = $(this).val().trim().toUpperCase();
    if (inputVal === confirmationWord) {
      $(this).removeClass("is-invalid").addClass("is-valid");
      $("#confirmEnableProgramBtn").prop("disabled", false);
    } else {
      $(this).removeClass("is-valid").addClass("is-invalid");
      $("#confirmEnableProgramBtn").prop("disabled", true);
    }
  });

  $("#confirmEnableProgramBtn").on("click", function () {
    const programUuid = $("#enableProgramModal").attr("data-enableprogram-uuid");
    if (!programUuid) {
      ToastVersion(swalTheme, "Program identifier is missing.", "error", 3000);
      return;
    }

    $.ajax({
      url: "../../../process/programs/toggle_program",
      method: "POST",
      data: { csrf_token: csrfToken, program_uuid: programUuid },
      dataType: "json",
      beforeSend: function () {
        $("#confirmEnableProgramBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Enabling...');
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program enabled successfully.", "success", 3000);
          $("#enableProgramModal").modal("hide");
          getPrograms(showInactive);
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 5000);
        } else {
          ToastVersion(swalTheme, response.Details || "An error occurred while enabling the program.", "error", 5000);
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
    });
  });

  $("#toggleInactiveProgramsBtn").on("click", function () {
    const datashowInactive = $(this).attr("data-show-inactive");
    if (datashowInactive === "false") {
        showInactive = true;
      getPrograms(showInactive);
      $(this).attr("data-show-inactive", "true");
      $(this).html('<i class="bi bi-eye me-1"></i><span>Show inactive</span>');
    } else {
        showInactive = false;
      getPrograms(showInactive);
      $(this).attr("data-show-inactive", "false");
      $(this).html('<i class="bi bi-eye-slash me-1"></i><span>Hide inactive</span>');
    }
  });

  $("#saveNewProgramBtn").on("click", function () {
    const code = $("#programCodeInput").val().trim();
    const name = $("#programNameInput").val().trim();
    const department = $("#programDepartmentInput").val().trim();
    const requiredHours = $("#requiredHoursInput").val().trim();
    //activateImmediatelySwitch
    const activateImmediately = $("#activateImmediatelySwitch").is(":checked");

    if (!code || !name || !department || !requiredHours) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    if (isNaN(requiredHours) || parseInt(requiredHours) < 0) {
      ToastVersion(swalTheme, "Required hours must be a non-negative number.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../process/programs/create_program",
      method: "POST",
      data: {
        csrf_token: csrfToken,
        code: code,
        name: name,
        department: department,
        required_hours: requiredHours,
        is_active: activateImmediately ? 1 : 0,
      },
      dataType: "json",
      beforeSend: function () {
        $("#saveNewProgramBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program created successfully.", "success", 3000);
          $("#NewProgramModal").modal("hide");
          getPrograms(showInactive);
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 5000);
        } else {
          ToastVersion(swalTheme, response.Details || "An error occurred while creating the program.", "error", 5000);
        }
      },
      error: function (xhr, status, error) {
        Errors(xhr, status, error);
      },
      complete: function () {
        $("#saveNewProgramBtn").prop("disabled", false).html("Save program");
      },
    });
  });
});
