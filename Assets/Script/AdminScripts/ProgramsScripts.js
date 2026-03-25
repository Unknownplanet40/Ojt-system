import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { fetchUserData } from "../DashboardScripts/AdminDashboardScript.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();
$("#pageLoader").fadeIn(2000);

const randomConformationWord = ["CONFIRM", "AGREE", "YES", "OK", "PROCEED", "ACCEPT", "VALIDATE", "APPROVE", "ACKNOWLEDGE", "CONSENT"];

fetchUserData();

function programToggle(programUuid) {
  $.ajax({
    url: "../../../Assets/api/program_functions",
    method: "POST",
    data: {
      action: "program_toggle",
      program_uuid: programUuid,
    },
    success: function (response) {
      if (response.status === "success") {
        loadPrograms();
      }
    },
    error: function (xhr, status, error) {
      ToastVersion(swalTheme, "Failed to toggle program status", "error", 3000);
    },
  });
}

function loadPrograms() {
  const programsContainer = $("#programsContainer");
  programsContainer.empty();

  $.ajax({
    url: "../../../Assets/api/program_functions",
    method: "POST",
    data: { action: "fetch_programs" },
    dataType: "json",
    success: function (response) {
      if (response.status === "success" && response.programs.length > 0) {
        response.programs.forEach((program) => {
          const programCol = $(`<div class="col">
                                    <div class="card bg-blur-5 bg-semi-transparent border shadow-sm h-100 border-muted" style="--blur-lvl: 0.2">
                                        <div class="card-body d-flex flex-column">
                                            <div class="hstack gap-2">
                                                <div>
                                                    <h5 class="card-title mb-0"><span>${program.code}</span> - <span>${program.name}</span></h5>
                                                    <small class="text-muted"><span>${program.department}</span> - <span>${program.required_hours}</span> required OJT hours - <span class="${program.is_active == 1 ? "text-success" : "text-danger"}">${program.status_label}</span></small>
                                                </div>
                                                <div class="ms-auto">
                                                    <div class="d-grid">
                                                        <button class="btn btn-sm btn-outline-light" data-bs-toggle="modal" data-bs-target="#EditProgramModal" id="editProgramBtn-${program.uuid}">
                                                            <i class="bi bi-pencil-fill d-sm-none"></i>
                                                            <span class="d-none d-sm-inline"></i>Edit</span>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-dark mt-1 text-light border ${program.is_active == 0 ? "d-none" : ""}" id="disableProgramBtn-${program.uuid}" data-bs-toggle="modal" data-bs-target="#disableProgramModal">
                                                            <i class="bi bi-trash-fill d-sm-none"></i>
                                                            <span class="d-none d-sm-inline">Disable</span>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-success mt-1 text-light border ${program.is_active == 1 ? "d-none" : ""}" id="enableProgramBtn-${program.uuid}">
                                                            <i class="bi bi-check2-circle-fill d-sm-none"></i>
                                                            <span class="d-none d-sm-inline">Enable</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`);
          programsContainer.append(programCol);

          $(`#editProgramBtn-${program.uuid}`).on("click", function () {
            $("#EditProgramModal").attr("data-editprogram-uuid", program.uuid);
            $("#editProgramCodeInput").val(program.code);
            $("#editProgramNameInput").val(program.name);
            $("#editProgramDepartmentInput").val(program.department);
            $("#editRequiredHoursInput").val(program.required_hours);
            if (program.is_active) {
              $("#editActivateImmediatelySwitch").prop("checked", true).parent().show();
            } else {
              $("#editActivateImmediatelySwitch").prop("checked", false).parent().show();
            }
          });

          $(`#disableProgramBtn-${program.uuid}`).on("click", function () {
            $("#disableProgramModal").attr("data-disableprogram-uuid", program.uuid);
            $("#programToDisableName").text(`${program.code} - ${program.name}`);

            const randomWord = randomConformationWord[Math.floor(Math.random() * randomConformationWord.length)];
            $("#disableProgramNameConfirm").text(randomWord);
            $("#disableProgramInput").val("").prop("disabled", false);
            $("#disableProgramInput").attr("placeholder", `Type "${randomWord}" to confirm`).focus();
            $("#confirmDisableProgramBtn").prop("disabled", true);
          });

          $(`#enableProgramBtn-${program.uuid}`).on("click", function () {
            programToggle(program.uuid);
          });
        });
      } else {
        const noProgramsCol = $(`<div class="col">
                                    <div class="card bg-blur-5 bg-semi-transparent border shadow-sm h-100 border-danger bprder-2"
                                        style="--blur-lvl: 0.8">
                                        <div class="card-body d-flex flex-column">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-building fs-3 text-muted"></i>
                                                <div>
                                                    <h5 class="card-title mb-0">No active programs</h5>
                                                    <small class="text-muted">Create programs to set specific OJT hour requirements for different courses.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>`);
        programsContainer.append(noProgramsCol);
      }
    },
    error: function (xhr, status, error) {
      const errorCol = $(`<div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent border shadow-sm h-100 border-danger bprder-2"
                                style="--blur-lvl: 0.8">
                                <div class="card-body d-flex flex-column">
                                    <div class="hstack gap-2">
                                        <i class="bi bi-building fs-3 text-muted"></i>
                                        <div>
                                            <h5 class="card-title mb-0">Failed to load programs</h5>
                                            <small class="text-muted"><span class="text-danger">Error:</span> ${error}. Please try again later.</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`);
      programsContainer.append(errorCol);
    },
  });
}

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  loadPrograms();

  $("#cancelEditProgramBtn").on("click", function () {
    $("#EditProgramModal").removeAttr("data-editprogram-uuid");
    $("#editProgramCodeInput").val("");
    $("#editProgramNameInput").val("");
    $("#editProgramDepartmentInput").val("");
    $("#editRequiredHoursInput").val("");
    $("#editActivateImmediatelySwitch").prop("checked", false).parent().hide();
  });

  $("#cancelDisableProgramBtn").on("click", function () {
    $("#disableProgramModal").removeAttr("data-disableprogram-uuid");
    $("#programToDisableName").text("");
  });

  $("#disableProgramInput").on("input", function () {
    const inputVal = $(this).val().trim().toUpperCase();
    const requiredWord = $("#disableProgramNameConfirm").text().trim();
    if (inputVal === requiredWord) {
      $("#confirmDisableProgramBtn").prop("disabled", false);
    } else {
      $("#confirmDisableProgramBtn").prop("disabled", true);
    }
  });

  $("#confirmDisableProgramBtn").on("click", function () {
    const programUuid = $("#disableProgramModal").attr("data-disableprogram-uuid");
    programToggle(programUuid);
    $("#disableProgramModal").removeAttr("data-disableprogram-uuid").modal("hide");
    $("#programToDisableName").text("");
  });

  $("#saveNewProgramBtn").on("click", function () {
    const code = $("#programCodeInput").val().trim();
    const name = $("#programNameInput").val().trim();
    const department = $("#programDepartmentInput").val().trim();
    const requiredHours = parseInt($("#requiredHoursInput").val().trim());
    const activateImmediately = $("#activateImmediatelySwitch").is(":checked") ? 1 : 0;

    if (code.length === 0 || name.length === 0 || department.length === 0 || isNaN(requiredHours) || requiredHours <= 0) {
      ToastVersion(swalTheme, "Please fill in all fields with valid data", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/program_functions",
      method: "POST",
      data: {
        action: "program_create",
        code: code,
        name: name,
        department: department,
        required_hours: requiredHours,
        activate_immediately: activateImmediately,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program created successfully", "success", 3000);
          $("#NewProgramModal").modal("hide");
          $("#programCodeInput").val("");
          $("#programNameInput").val("");
          $("#programDepartmentInput").val("");
          $("#requiredHoursInput").val("");
          $("#activateImmediatelySwitch").prop("checked", false);
          loadPrograms();
        } else {
          ToastVersion(swalTheme, response.message || "Failed to create program", "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "Failed to create program", "error", 3000);
      },
    });
  });

  $("#saveEditProgramBtn").on("click", function () {
    const programUuid = $("#EditProgramModal").attr("data-editprogram-uuid");
    const code = $("#editProgramCodeInput").val().trim();
    const name = $("#editProgramNameInput").val().trim();
    const department = $("#editProgramDepartmentInput").val().trim();
    const requiredHours = parseInt($("#editRequiredHoursInput").val().trim());
    const activateImmediately = $("#editActivateImmediatelySwitch").is(":checked") ? 1 : 0;

    if (code.length === 0 || name.length === 0 || department.length === 0 || isNaN(requiredHours) || requiredHours <= 0) {
      ToastVersion(swalTheme, "Please fill in all fields with valid data", "warning", 3000);
      return;
    }

    if (!programUuid) {
      ToastVersion(swalTheme, "No program selected for editing", "error", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/program_functions",
      method: "POST",
      data: {
        action: "program_edit",
        program_uuid: programUuid,
        code: code,
        name: name,
        department: department,
        required_hours: requiredHours,
        activate_immediately: activateImmediately,
      },
      success: function (response) { 
        if (response.status === "success") {
          ToastVersion(swalTheme, "Program updated successfully", "success", 3000);
          $("#EditProgramModal").removeAttr("data-editprogram-uuid").modal("hide");
          $("#editProgramCodeInput").val("");
          $("#editProgramNameInput").val("");
          $("#editProgramDepartmentInput").val("");
          $("#editRequiredHoursInput").val("");
          $("#editActivateImmediatelySwitch").prop("checked", false).parent().hide();
          loadPrograms();
        } else {
          ToastVersion(swalTheme, response.message || "Failed to update program", "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "Failed to update program", "error", 3000);
      },
    });
  });

});
