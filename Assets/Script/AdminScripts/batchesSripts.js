import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { fetchUserData, signOut } from "../DashboardScripts/AdminDashboardScript.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);
AOS.init();
$("#pageLoader").fadeIn(2000);

fetchUserData();

const randomConformationWord = ["CONFIRM", "AGREE", "YES", "OK", "PROCEED", "ACCEPT", "VALIDATE", "APPROVE", "ACKNOWLEDGE", "CONSENT"];

function loadBatches() {
  const batchesContainer = $("#batchesContainer");
  batchesContainer.empty();

  $.ajax({
    url: "../../../Assets/api/batch_functions",
    method: "POST",
    data: { action: "get_batches" },
    success: function (response) {
      batchesContainer.empty();
      if (response.status === "success") {
        const batches = response.data.batches;
        const activeBatch = response.data.active_batch;

        const sortedBatches = batches.sort((a, b) => {
          if (a.status === "active" && b.status !== "active") return -1;
          if (a.status !== "active" && b.status === "active") return 1;
          return 0;
        });

        sortedBatches.forEach((batch) => {
          const batchCard = `
            <div class="col card-container">
              <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm ${batch.status === "active" ? "border-success border-2" : batch.status === "closed" ? "border-danger border-2" : ""}" style="--blur-lvl: 0.60">
          <div class="card-body py-3 px-4">
            <div class="hstack">
              <p class="card-title mb-0 fw-bold">${batch.label}</p>
              <span class="badge bg-white text-success rounded-pill ms-auto fw-medium ${batch.status === "active" ? "d-block" : "d-none"}">Active</span>
              <span class="badge bg-dark text-muted rounded-pill ms-auto fw-medium border ${batch.status !== "active" ? "d-block" : "d-none"}">Upcoming</span>
            </div>
            <small class="text-muted" style="font-size: 0.875rem;">${batch.start_date} — ${batch.end_date}</small>
            <div class="row row-cols-md-4 g-3 mt-2">
              <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent shadow-sm border-0 h-100">
            <div class="card-body p-2">
              <p class="card-title mb-0 text-muted" style="font-size: .75rem;">Students</p>
              <h6 class="card-subtitle mt-1 mb-0 text-muted">${batch.student_count}</h6>
            </div>
                </div>
              </div>
              <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent shadow-sm border-0 h-100">
            <div class="card-body p-2">
              <p class="card-title mb-0 text-muted" style="font-size: .75rem;">Required Hours</p>
              <h6 class="card-subtitle mt-1 mb-0 text-muted">${batch.required_hours}</h6>
            </div>
                </div>
              </div>
              <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent shadow-sm border-0 h-100">
            <div class="card-body p-2">
              <p class="card-title mb-0 text-muted" style="font-size: .75rem;">Activated</p>
              <h6 class="card-subtitle mt-1 mb-0 text-muted">${batch.activated_at ? batch.activated_at : "&mdash;"}</h6>
            </div>
                </div>
              </div>
              <div class="col">
                <div class="card bg-blur-5 bg-semi-transparent shadow-sm border-0 h-100">
            <div class="card-body p-2">
              <p class="card-title mb-0 text-muted" style="font-size: .75rem;">Created</p>
              <h6 class="card-subtitle mt-1 mb-0 text-muted">${batch.created_by}</h6>
            </div>
                </div>
              </div>
            </div>
            <hr class="my-2">
            <div class="hstack">
              <span class="text-muted" style="font-size: 0.80rem;">Created ${batch.created_at}</span>
              <div class="gap-2 ms-auto ${batch.status === "active" ? "d-block" : "d-none"}">
                <button class="btn btn-sm btn-outline-dark text-white border border-light px-3 rounded-2">
            <span class="d-sm-none"><i class="bi bi-eye"></i></span>
            <span class="d-none d-sm-block">View Students</span>
                </button>
                <button class="btn btn-sm btn-outline-dark text-white border border-light px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#EditBatchModal" id="editBatchBtnc-${batch.uuid}">
            <span class="d-sm-none"><i class="bi bi-pencil"></i></span>
            <span class="d-none d-sm-block">Edit</span>
                </button>
                <button class="btn btn-sm btn-outline-dark text-white border border-light px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#closeBatchModal" id="closeBatchBtn-${batch.uuid}">
            <span class="d-sm-none"><i class="bi bi-x-lg"></i></span>
            <span class="d-none d-sm-block">Close Batch</span>
                </button>
              </div>
              <div class="gap-2 ms-auto ${batch.status !== "active" ? "d-block" : "d-none"}">
                <button class="btn btn-sm btn-outline-dark text-white border border-light px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#EditBatchModal" id="editBatchBtna-${batch.uuid}">
            <span class="d-sm-none"><i class="bi bi-pencil"></i></span>
            <span class="d-none d-sm-block">Edit</span>
                </button>
                <button class="btn btn-sm btn-outline-dark text-white border border-light px-3 rounded-2" data-bs-toggle="modal" data-bs-target="#ActivateBatchModal" id="activateBatchBtn-${batch.uuid}">
            <span class="d-sm-none"><i class="bi bi-check-lg"></i></span>
            <span class="d-none d-sm-block">Activate</span>
                </button>
              </div>
            </div>
          </div>
              </div>
            </div>
          `;
          batchesContainer.append(batchCard);

          $("#activeBatchLabel").html(`● <span class="fw-bold">${activeBatch ? activeBatch.label : "N/A"}</span> is currently active`);

          $(`#closeBatchBtn-${batch.uuid}`).click(function () {
            $("#closeBatchModal").attr("data-closebatch-uuid", batch.uuid);
            $("#batchToCloseName").text(batch.label);

            const randomWord = randomConformationWord[Math.floor(Math.random() * randomConformationWord.length)];
            $("#closeBatchNameConfirm").text(randomWord);
            $("#closeBatchInput").val("").prop("placeholder", `Type ${randomWord} to confirm`);
            $("#confirmCloseBatchBtn").prop("disabled", true);
          });

          $(`#activateBatchBtn-${batch.uuid}`).click(function () {
            $("#ActivateBatchModal").attr("data-activatebatch-uuid", batch.uuid);
            $("#batchToActivateName").text(batch.label);
            $("#currentActiveBatchName").text(activeBatch ? activeBatch.label : "N/A");
          });

          $(`#editBatchBtnc-${batch.uuid}`).click(function () {
            $("#EditBatchModal").attr("data-editbatch-uuid", batch.uuid);
            $("#EditschoolYearInput").val(batch.school_year);
            $("#EditstartDateInput").val(new Date(batch.start_date).toISOString().split("T")[0]);
            $("#EditendDateInput").val(new Date(batch.end_date).toISOString().split("T")[0]);
            $("#EditrequiredHoursInput").val(batch.required_hours);
            $("#EditactivateImmediatelySwitch").prop("checked", false);

            $("#EditsemesterInput option").each(function () {
              console.log("Option value:", $(this).val(), "Batch semester:", batch.semester);
              if ($(this).val().toLowerCase() === batch.semester.toLowerCase()) {
                $(this).prop("selected", true);
              } else {
                $(this).prop("selected", false);
              }
            });
          });

          $(`#editBatchBtna-${batch.uuid}`).click(function () {
            $("#EditBatchModal").attr("data-editbatch-uuid", batch.uuid);
            $("#EditschoolYearInput").val(batch.school_year);
            $("#EditstartDateInput").val(new Date(batch.start_date).toISOString().split("T")[0]);
            $("#EditendDateInput").val(new Date(batch.end_date).toISOString().split("T")[0]);
            $("#EditrequiredHoursInput").val(batch.required_hours);
            $("#EditactivateImmediatelySwitch").prop("checked", false);

            $("#EditsemesterInput option").each(function () {
              if ($(this).val().toLowerCase() === batch.semester.toLowerCase()) {
                $(this).prop("selected", true);
              } else {
                $(this).prop("selected", false);
              }
            });
          });
        });
      } else {
        const errorCard = `
          <div class="col">
            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-danger" style="--blur-lvl: 0.60">
              <div class="card-body py-3 px-4">
                <div class="hstack">
                  <p class="card-title mb-0 fw-bold text-danger">Error loading batches</p>
                </div>
                <small class="text-muted" style="font-size: 0.875rem;">Please try again later.</small>
              </div>
            </div>
          </div>
        `;
        batchesContainer.append(errorCard);
      }
    },
    error: function (xhr, status, error) {
      batchesContainer.empty();
      const errorCard = `
          <div class="col">
            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-danger" style="--blur-lvl: 0.60">
              <div class="card-body py-3 px-4">
                <div class="hstack">
                  <p class="card-title mb-0 fw-bold text-danger">Error loading batches</p>
                </div>
                <small class="text-muted" style="font-size: 0.875rem;">Please try again later.</small>
              </div>
            </div>
          </div>
        `;
      batchesContainer.append(errorCard);
    },
  });
}

// get action from url parameter if exists and trigger corresponding button click
function triggerActionFromURL() {
  const urlParams = new URLSearchParams(window.location.search);
  const action = urlParams.get("action");
  const batchUuid = urlParams.get("batch");

  if (action === "create") {
    $("#NewBatchModal").modal("show");
  } else if (action === "edit" && batchUuid) {
    setTimeout(() => {
      $(`#editBatchBtna-${batchUuid}`).click();
      $(`#editBatchBtnc-${batchUuid}`).click();
    }, 100);
  }
  
  if (action) {
    urlParams.delete("action");
    urlParams.delete("batch");
    const newUrl = `${window.location.pathname}?${urlParams.toString()}`;
    window.history.replaceState({}, document.title, newUrl);
  }
}

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  loadBatches();
  triggerActionFromURL();
  signOut();

  $("#cancelActivateBatchBtn").click(function () {
    $("#ActivateBatchModal").removeAttr("data-activatebatch-uuid");
  });

  $("#closeBatchInput").on("input", function () {
    const requiredWord = $("#closeBatchNameConfirm").text();
    const inputVal = $(this).val().trim().toUpperCase();

    if (inputVal === requiredWord) {
      $("#confirmCloseBatchBtn").prop("disabled", false);
    } else {
      $("#confirmCloseBatchBtn").prop("disabled", true);
    }
  });

  $("#cancelCloseBatchBtn").click(function () {
    $("#closeBatchModal").removeAttr("data-closebatch-uuid");
  });

  $("#cancelEditBatchBtn").on("hidden.bs.modal", function () {
    $("#EditBatchModal").removeAttr("data-editbatch-uuid");
    $("#EditschoolYearInput").val("");
    $("#EditstartDateInput").val("");
    $("#EditendDateInput").val("");
    $("#EditrequiredHoursInput").val("");
    $("#EditactivateImmediatelySwitch").prop("checked", false);
    $("#EditsemesterInput").val("");
  });

  $("#confirmCloseBatchBtn").click(function () {
    const batchUuid = $("#closeBatchModal").attr("data-closebatch-uuid");

    $.ajax({
      url: "../../../Assets/api/batch_functions",
      method: "POST",
      data: {
        action: "close_batch",
        batch_uuid: batchUuid,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Batch closed successfully!", "success", 3000);
          $("#closeBatchModal").removeAttr("data-closebatch-uuid");
          $("#closeBatchInput").val("").prop("placeholder", "Type CLOSE to confirm");
          $("#confirmCloseBatchBtn").prop("disabled", true);
          $("#closeBatchModal").modal("hide");
          loadBatches();
        } else {
          ToastVersion(swalTheme, "Error closing batch: " + response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "An error occurred while closing the batch. Please try again.", "error", 3000);
      },
    });
  });

  $("#confirmActivateBatchBtn").click(function () {
    const batchUuid = $("#ActivateBatchModal").attr("data-activatebatch-uuid");
    $.ajax({
      url: "../../../Assets/api/batch_functions",
      method: "POST",
      data: {
        action: "activate_batch",
        batch_uuid: batchUuid,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Batch activated successfully!", "success", 3000);
          $("#ActivateBatchModal").removeAttr("data-activatebatch-uuid");
          $("#ActivateBatchModal").modal("hide");
          loadBatches();
        } else {
          ToastVersion(swalTheme, "Error activating batch: " + response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "An error occurred while activating the batch. Please try again.", "error", 3000);
      },
    });
  });

  $("#saveNewBatchBtn").click(function () {
    const schoolYear = $("#schoolYearInput").val();
    const semester = $("#semesterInput").val();
    const startDate = $("#startDateInput").val();
    const endDate = $("#endDateInput").val();
    const requiredHours = $("#requiredHoursInput").val();
    const activateImmediately = $("#activateImmediatelySwitch").is(":checked");

    if (!schoolYear || !semester || !startDate || !endDate || !requiredHours) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    const schoolYearPattern = /^\d{4}-\d{4}$/;
    if (!schoolYearPattern.test(schoolYear)) {
      ToastVersion(swalTheme, "School year must be in the format YYYY-YYYY.", "warning", 3000);
      return;
    }

    if (semester !== "1st" && semester !== "2nd" && semester !== "summer") {
      ToastVersion(swalTheme, "Please select a valid semester.", "warning", 3000);
      console.log("Invalid semester value:", semester);
      return;
    }

    if (startDate >= endDate) {
      ToastVersion(swalTheme, "Start date must be before end date.", "warning", 3000);
      return;
    }

    if (requiredHours <= 0) {
      ToastVersion(swalTheme, "Required hours must be a positive number.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/batch_functions",
      method: "POST",
      data: {
        action: "create_batch",
        school_year: schoolYear,
        semester: semester,
        start_date: startDate,
        end_date: endDate,
        required_hours: requiredHours,
        activate_immediately: activateImmediately ? 1 : 0,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Batch created successfully!", "success", 3000);
          $("#NewBatchModal").modal("hide");
          loadBatches();
        } else {
          ToastVersion(swalTheme, "Error creating batch: " + response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "An error occurred while creating the batch. Please try again.", "error", 3000);
      },
    });
  });

  $("#cancelNewBatchBtn").click(function () {
    $("#schoolYearInput").val("");
    $("#semesterInput").val("");
    $("#startDateInput").val("");
    $("#endDateInput").val("");
    $("#requiredHoursInput").val("");
    $("#activateImmediatelySwitch").prop("checked", false);
  });

  $("#saveEditBatchBtn").click(function () {
    const batchUuid = $("#EditBatchModal").attr("data-editbatch-uuid");
    const schoolYear = $("#EditschoolYearInput").val();
    const semester = $("#EditsemesterInput").val();
    const startDate = $("#EditstartDateInput").val();
    const endDate = $("#EditendDateInput").val();
    const requiredHours = $("#EditrequiredHoursInput").val();
    const activateImmediately = $("#EditactivateImmediatelySwitch").is(":checked");

    if (!schoolYear || !semester || !startDate || !endDate || !requiredHours) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000);
      return;
    }

    const schoolYearPattern = /^\d{4}-\d{4}$/;
    if (!schoolYearPattern.test(schoolYear)) {
      ToastVersion(swalTheme, "School year must be in the format YYYY-YYYY.", "warning", 3000);
      return;
    }

    if (semester !== "1st" && semester !== "2nd" && semester !== "summer") {
      ToastVersion(swalTheme, "Please select a valid semester.", "warning", 3000);
      console.log("Invalid semester value:", semester);
      return;
    }

    if (startDate >= endDate) {
      ToastVersion(swalTheme, "Start date must be before end date.", "warning", 3000);
      return;
    }

    if (requiredHours <= 0) {
      ToastVersion(swalTheme, "Required hours must be a positive number.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/batch_functions",
      method: "POST",
      data: {
        action: "edit_batch",
        batch_uuid: batchUuid,
        school_year: schoolYear,
        semester: semester,
        start_date: startDate,
        end_date: endDate,
        required_hours: requiredHours,
        activate_immediately: activateImmediately ? 1 : 0,
      },
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "Batch updated successfully!", "success", 3000);
          $("#EditBatchModal").removeAttr("data-editbatch-uuid");
          $("#EditBatchModal").modal("hide");
          $("#EditschoolYearInput").val("");
          $("#EditsemesterInput").val("");
          $("#EditstartDateInput").val("");
          $("#EditendDateInput").val("");
          $("#EditrequiredHoursInput").val("");
          $("#EditactivateImmediatelySwitch").prop("checked", false);
          loadBatches();
        } else {
          ToastVersion(swalTheme, "Error updating batch: " + response.message, "error", 3000);
        }
      },
      error: function (xhr, status, error) {
        ToastVersion(swalTheme, "An error occurred while updating the batch. Please try again.", "error", 3000);
      },
    });
  });
});
