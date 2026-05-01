import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";
let swalTheme = SwalTheme();

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";

const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

const notFoundMessage = `<div class="col-md=12">
                                <div class="card bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-0 h-100">
                                    <div class="card-body d-flex flex-column align-items-center justify-content-center p-4">
                                        <i class="bi bi-building-x fs-1 text-muted mb-3"></i>
                                        <h5 class="card-title mb-2 text-muted">No companies found</h5>
                                        <p class="card-text text-muted small">Try adjusting your search or filter to find what you're looking for.</p>
                                    </div>
                                </div>
                            </div>`;

let companiesCache = [];
let batchCache = null;
let filterEventsBound = false;
let programFilterLookup = {};

function normalizeText(value) {
  return String(value || "").toLowerCase().trim();
}

function extractAcceptedPrograms(company) {
  if (!company?.accepted_programs) return [];
  return company.accepted_programs
    .split(",")
    .map((program) => program.trim())
    .filter(Boolean);
}

function getCompanyStatusValue(company) {
  if (company?.moa_status === "expiring") return "moa_expiring";
  if (company?.moa_status === "none") return "no_moa";
  return normalizeText(company?.accreditation_status);
}

function getCurrentFilterState() {
  return {
    search: normalizeText($("#searchInput").val()),
    status: normalizeText($("#filterStatus").val()) || "all",
    setup: normalizeText($("#filterSetup").val()) || "all",
    program: normalizeText($("#filterProgram").val()) || "all",
  };
}

function tokensLooselyMatch(left, right) {
  const a = normalizeText(left);
  const b = normalizeText(right);
  if (!a || !b) return false;
  return a === b || a.includes(b) || b.includes(a);
}

function companyMatchesProgramFilter(company, selectedProgramKey) {
  const selectedProgram = programFilterLookup[selectedProgramKey];
  if (!selectedProgram) return false;

  const companyPrograms = extractAcceptedPrograms(company).map((program) => normalizeText(program));
  const aliases = selectedProgram.aliases || [];

  return companyPrograms.some((companyProgram) => aliases.some((alias) => tokensLooselyMatch(companyProgram, alias)));
}

function populateCompanyFilters(companies = [], programs = [], previousState = null) {
  const statusSelect = $("#filterStatus");
  const setupSelect = $("#filterSetup");
  const programSelect = $("#filterProgram");

  statusSelect.html(`
    <option class="CustomOption" value="all">All Status</option>
    <option class="CustomOption" value="active">Active</option>
    <option class="CustomOption" value="inactive">Inactive</option>
    <option class="CustomOption" value="blacklisted">Blacklisted</option>
    <option class="CustomOption" value="moa_expiring">MOA Expiring</option>
    <option class="CustomOption" value="no_moa">No MOA</option>
  `);

  const setupValues = [...new Set(companies.map((company) => normalizeText(company.work_setup)).filter(Boolean))].sort();
  setupSelect.html(`<option class="CustomOption" value="all">All Work Setup</option>`);
  setupValues.forEach((setup) => {
    setupSelect.append(`<option class="CustomOption" value="${setup}">${setup.charAt(0).toUpperCase() + setup.slice(1)}</option>`);
  });

  programFilterLookup = {};
  programs.forEach((program) => {
    const programName = String(program?.name || "").trim();
    const programCode = String(program?.code || "").trim();
    const key = normalizeText(programCode || programName);

    if (!key) return;

    const label = programCode ? `${programName} (${programCode})` : programName;
    const aliases = [normalizeText(programName), normalizeText(programCode), normalizeText(label)].filter(Boolean);

    programFilterLookup[key] = {
      label,
      aliases: [...new Set(aliases)],
    };
  });

  const companyPrograms = companies.flatMap((company) => extractAcceptedPrograms(company)).filter(Boolean);
  companyPrograms.forEach((rawProgram) => {
    const normalizedProgram = normalizeText(rawProgram);
    if (!normalizedProgram) return;

    const existingKey = Object.keys(programFilterLookup).find((key) =>
      (programFilterLookup[key].aliases || []).some((alias) => tokensLooselyMatch(alias, normalizedProgram))
    );

    if (existingKey) {
      const aliases = new Set(programFilterLookup[existingKey].aliases || []);
      aliases.add(normalizedProgram);
      programFilterLookup[existingKey].aliases = [...aliases];
      return;
    }

    programFilterLookup[normalizedProgram] = {
      label: rawProgram,
      aliases: [normalizedProgram],
    };
  });

  const sortedProgramOptions = Object.entries(programFilterLookup).sort((a, b) => a[1].label.localeCompare(b[1].label));
  programSelect.html(`<option class="CustomOption" value="all">All Programs</option>`);
  sortedProgramOptions.forEach(([programKey, programMeta]) => {
    programSelect.append(`<option class="CustomOption" value="${programKey}">${programMeta.label}</option>`);
  });

  if (previousState) {
    statusSelect.val(previousState.status || "all");
    setupSelect.val(previousState.setup || "all");
    programSelect.val(previousState.program || "all");

    if (!statusSelect.val()) statusSelect.val("all");
    if (!setupSelect.val()) setupSelect.val("all");
    if (!programSelect.val()) programSelect.val("all");
  }
}

function applyCompanyFilters(companies = []) {
  const state = getCurrentFilterState();

  return companies.filter((company) => {
    const searchableText = [company.name, company.industry, company.city, company.status_label, company.work_setup, ...extractAcceptedPrograms(company)]
      .map((value) => normalizeText(value))
      .join(" ");

    const matchesSearch = !state.search || searchableText.includes(state.search);
    const matchesStatus = state.status === "all" || getCompanyStatusValue(company) === state.status;
    const matchesSetup = state.setup === "all" || normalizeText(company.work_setup) === state.setup;
    const matchesProgram = state.program === "all" || companyMatchesProgramFilter(company, state.program);

    return matchesSearch && matchesStatus && matchesSetup && matchesProgram;
  });
}

function renderCompanyCards(companies = [], batch = null) {
  const companiesContainer = $("#companiesContainer");
  companiesContainer.empty();

  if (!companies || companies.length === 0) {
    companiesContainer.html(notFoundMessage);
    return;
  }

  companies.forEach((company) => {
    const moaProgress = company.moa_days_left ? Math.min(100, Math.max(0, (company.moa_days_left / 30) * 100)) : 0;
    const progressBarColor = company.moa_days_left <= 0 ? "bg-danger" : company.moa_days_left <= 30 ? "bg-warning" : "bg-success";

    const statusColor =
      company.moa_status === "expiring"
        ? "bg-warning-subtle text-warning"
        : company.moa_status === "none"
          ? "bg-secondary"
          : company.accreditation_status === "active"
            ? "bg-success"
            : company.accreditation_status === "inactive"
              ? "bg-danger"
              : "bg-warning";

    const companyCard = `<div class="col">
                            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-0 overflow-hidden"
                                style="--blur-lvl: 0.60; transition: box-shadow 0.3s ease, transform 0.3s ease;">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <div class="flex-grow-1 min-w-0">
                                            <h5 class="card-title mb-1 fw-bold text-white">${company.name}</h5>
                                            <p class="text-muted mb-0 small" style="font-size: 0.85rem;">${company.industry} · ${company.city}</p>
                                        </div>
                                        <span class="badge ${statusColor} rounded-pill fw-medium flex-shrink-0">${company.moa_status === "expiring" ? "MOA Expiring" : company.moa_status === "none" ? "No MOA" : company.status_label}</span>
                                    </div>

                                    <div class="row row-cols-2 row-cols-lg-4 g-2 mb-4">
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100" style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Slots</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white">${company.filled_slots}/${company.total_slots}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100" style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Remaining</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white"><span>${company.remaining_slots}</span></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100" style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Work Setup</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white text-capitalize">${company.work_setup}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100" style="--blur-lvl: 0.40">
                                                <div class="card-body p-3">
                                                    <p class="card-title mb-2 text-muted small fw-medium">MOA Expiry</p>
                                                    <p class="card-text mb-2 fw-bold fs-6 text-white small">${company.moa_days_left ? company.moa_days_left + " days" : company.moa_expiry || "N/A"}</p>
                                                    <div class="progress ${company.moa_status !== "none" ? "" : "d-none"}" style="height: 3px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill ${company.moa_status !== "none" ? progressBarColor : "d-none"}"
                                                            role="progressbar" style="width: ${moaProgress}%;" aria-valuenow="${moaProgress}"
                                                            aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="my-3 opacity-50">

                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex align-items-center gap-2 mb-1 justify-content-between">
                                            <div class="d-flex gap-2 flex-wrap">
                                                ${company.accepted_programs
                                                  .split(",")
                                                  .map((program) => `<span class="badge bg-dark text-white rounded-pill fw-medium border border-secondary small">${program.trim()}</span>`)
                                                  .join("")}
                                            </div>
                                            <div class="d-flex align-items-center gap-2">
                                                <button class="btn btn-sm btn-outline-light border text-nowrap" id="viewCompanyBtn-${company.uuid}">
                                                    <i class="bi bi-eye me-1"></i>
                                                    <span class="d-none d-sm-inline">View</span>
                                                </button>
                                                <button class="btn btn-sm btn-outline-light border text-nowrap" id="editCompanyBtn-${company.uuid}">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                    <span class="d-none d-sm-inline">Edit</span>
                                                </button>
                                                ${
                                                  company.moa_status === "none"
                                                    ? `<button class="btn btn-sm btn-outline-light border text-nowrap" id="uploadMoABtn-${company.uuid}">
                                                    <i class="bi bi-upload me-1"></i>
                                                    <span class="d-none d-sm-inline">MOA</span>
                                                </button>`
                                                    : ""
                                                }
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>`;

    companiesContainer.append(companyCard);

    const activeBatchUuid = batch?.uuid || $("#activeBatchLabel").attr("data-batch-uuid") || "";
    const activeBatchLabel = batch?.label || "";

    $(`#viewCompanyBtn-${company.uuid}`).off("click").on("click", function () {
      viewCompanydetails(company.uuid, activeBatchUuid, activeBatchLabel);
    });

    $(`#editCompanyBtn-${company.uuid}`).off("click").on("click", function () {
      editCompany(company.uuid, activeBatchUuid);
    });

    $(`#uploadMoABtn-${company.uuid}`).off("click").on("click", function () {
      uploadDocument(company.uuid, activeBatchUuid, "moa");
    });
  });
}

function refreshFilteredCompanies() {
  const filteredCompanies = applyCompanyFilters(companiesCache);
  renderCompanyCards(filteredCompanies, batchCache);
  $("#totalcompanies").text(filteredCompanies.length);
}

function bindCompanyFilterEvents() {
  if (filterEventsBound) return;

  $("#searchInput").on("input", function () {
    refreshFilteredCompanies();
  });

  $("#filterStatus, #filterSetup, #filterProgram").on("change", function () {
    refreshFilteredCompanies();
  });

  filterEventsBound = true;
}

function getCompanies() {
  const companiesContainer = $("#companiesContainer");
  companiesContainer.empty();

  $.ajax({
    url: "../../../process/companies/get_companies",
    type: "POST",
    data: {
      csrf_token: csrfToken,
    },
    dataType: "json",
    timeout: 5000,
    beforeSend: function () {
      companiesContainer.html(`<div class="d-flex align-items-center justify-content-center w-100" style="height: 200px;">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                        </div>`);
    },
    success: function (response) {
      if (response.status === "success") {
        companiesContainer.empty();
        const companies = response.companies;
        const batch = response.batch;
        const programs = response.programs;
        $("#activeBatchLabel").text(batch ? `Batch: ${batch.label}` : "No Active Batch");
        $("#activeBatchLabel").attr("data-batch-uuid", batch ? batch.uuid : "");
        const acceptedProgramscontainer = $("#acceptedProgramsContainer");

        const previousFilterState = getCurrentFilterState();
        companiesCache = companies || [];
        batchCache = batch || null;

        // create accepted programs checkboxes for create company modal
        acceptedProgramscontainer.empty();
        if (programs && programs.length > 0) {
          programs.forEach((program) => {
            const programElement = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${program.uuid}" id="programCheckbox-${program.uuid}">
                        <label class="form-check-label" for="programCheckbox-${program.uuid}">
                            ${program.name} (${program.code})
                        </label>
                    </div>
                `;
            acceptedProgramscontainer.append(programElement);
          });
        } else {
          acceptedProgramscontainer.append('<div class="text-muted"><small>No available programs to accept</small></div>');
        }

        populateCompanyFilters(companiesCache, programs || [], previousFilterState);
        refreshFilteredCompanies();
      } else if (response.status === "critical") {
        companiesContainer.html(notFoundMessage);
        ToastVersion(swalTheme, response.details, "error", 3000, "top-end", "8");
      } else {
        companiesContainer.html(notFoundMessage);
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
      companiesContainer.html(notFoundMessage);
    },
  });
}

function editCompany(uuid, batchUuid) {
  if (!uuid) {
    ToastVersion(swalTheme, "Invalid company identifier.", "error", 3000, "top-end", "8");
    return;
  }

  if (!batchUuid) {
    ToastVersion(swalTheme, "No active batch found. Please set an active batch to edit company details.", "warning", 3000, "top-end", "8");
    return;
  }

  $.ajax({
    url: "../../../process/companies/get_company",
    type: "POST",
    data: {
      csrf_token: csrfToken,
      company_uuid: uuid,
      batch_uuid: batchUuid,
      forEdit: true,
    },
    dataType: "json",
    timeout: 5000,
    success: function (response) {
      if (response.status === "success") {
        $("#EditCompanyModal").modal("show");
        const supervisor = (response.data.supervisors && response.data.supervisors.length > 0)
          ? response.data.supervisors[0]
          : null;

        $("#EditcompanyName").val(response.data.company.name);
        $("#Editcompanyindustry").val(response.data.company.industry);
        $("#Editcompanyemail").val(response.data.company.email);
        $("#Editcompanycontact").val(response.data.company.phone);
        $("#Editcompanyaddress").val(response.data.company.address);
        $("#Editcompanycity").val(response.data.company.city);
        $("#Editcompanywebsite").val(response.data.company.website);
        $("#Editcompanyworksetup").val(response.data.company.work_setup);
        $("#Editcompanyaccreditationstatus").val(response.data.company.accreditation_status);
        $("#Editcompanytotalslots").val(response.data.total_slots);
        const primaryContact = (response.data.contacts && response.data.contacts.length > 0) ? response.data.contacts[0] : null;
        $("#Editcompanycontactname").val(primaryContact ? primaryContact.name : "");
        $("#Editcompanycontactemail").val(primaryContact ? primaryContact.email : "");
        $("#Editcompanycontactnumber").val(primaryContact ? primaryContact.phone : "");
        $("#Editcompanyposition").val(primaryContact ? primaryContact.position : "");
        $("#Editcompanyblocklistedreason").val(response.data.company.blacklist_reason);
        $("#EditsupervisorProfileUuid").val(supervisor ? supervisor.profile_uuid : "");
        $("#Editsupervisorfirstname").val(supervisor ? supervisor.first_name : "");
        $("#Editsupervisorlastname").val(supervisor ? supervisor.last_name : "");
        $("#Editsupervisoremail").val(supervisor ? supervisor.email : "");
        $("#Editsupervisormobile").val(supervisor ? supervisor.mobile : "");
        $("#Editsupervisorposition").val(supervisor ? supervisor.position : "");
        $("#Editsupervisordepartment").val(supervisor ? supervisor.department : "");
        if (response.data.company.accreditation_status === "blacklisted") {
          $("#Editcompanyblocklistedreason").closest(".mb-3").removeClass("d-none");
        } else {
          $("#Editcompanyblocklistedreason").closest(".mb-3").addClass("d-none");
        }

        const editAcceptedProgramsContainer = $("#EditacceptedProgramsContainer");
        editAcceptedProgramsContainer.empty();

        if (response.programs && response.programs.length > 0) {
          response.programs.forEach((program) => {
            const isChecked = response.data.program_uuids.includes(program.uuid) ? "checked" : "";
            const programElement = `
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${program.uuid}" id="editProgramCheckbox-${program.uuid}" ${isChecked}>
                        <label class="form-check-label" for="editProgramCheckbox-${program.uuid}">
                            ${program.name} (${program.code})
                        </label>
                    </div>
                `;
            editAcceptedProgramsContainer.append(programElement);
          });
        } else {
          editAcceptedProgramsContainer.append('<div class="text-muted"><small>No available programs to accept</small></div>');
        }

        $("#viewBlacklistReasonCard").toggleClass("d-none", response.data.company.accreditation_status !== "blacklisted");
        $("#viewBlacklistReasonText").text(response.data.company.blacklist_reason || "No reason specified");

        $("#EditCompanyModal")
          .off("hidden.bs.modal")
          .on("hidden.bs.modal", function () {
            $(this).find("input[type='text'], input[type='email'], input[type='tel'], select").val("");
            editAcceptedProgramsContainer.empty();
          });

        $("#EditsaveCompanyBtn")
          .off("click")
          .on("click", function () {
            const acceptedProgramUuids = [];
            editAcceptedProgramsContainer.find("input[type='checkbox']:checked").each(function () {
              acceptedProgramUuids.push($(this).val());
            });

            const companyData = {
              name: $("#EditcompanyName").val().trim(),
              email: $("#Editcompanyemail").val().trim(),
              work_setup: $("#Editcompanyworksetup").val(),
              accreditation_status: $("#Editcompanyaccreditationstatus").val(),

              industry: $("#Editcompanyindustry").val().trim(),
              address: $("#Editcompanyaddress").val().trim(),
              city: $("#Editcompanycity").val().trim(),
              phone: $("#Editcompanycontact").val().trim(),
              website: $("#Editcompanywebsite").val().trim(),
              blacklist_reason: $("#Editcompanyblocklistedreason").val().trim(),

              program_uuids: acceptedProgramUuids,

              total_slots: $("#Editcompanytotalslots").val(),
            };

            const supervisorData = {
              supervisor_profile_uuid: $("#EditsupervisorProfileUuid").val().trim(),
              supervisor_first_name: $("#Editsupervisorfirstname").val().trim(),
              supervisor_last_name: $("#Editsupervisorlastname").val().trim(),
              supervisor_email: $("#Editsupervisoremail").val().trim(),
              supervisor_mobile: $("#Editsupervisormobile").val().trim(),
              supervisor_position: $("#Editsupervisorposition").val().trim(),
              supervisor_department: $("#Editsupervisordepartment").val().trim(),
            };

            SaveChanges(uuid, batchUuid, { ...companyData, ...supervisorData }, "update");
          });
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function SaveChanges(uuid, batchUuid, data, type = "update") {
  if (!uuid) {
    ToastVersion(swalTheme, "Invalid company identifier.", "error", 3000, "top-end", "8");
    return;
  }

  if (!batchUuid) {
    ToastVersion(swalTheme, "No active batch found. Please set an active batch to save company details.", "warning", 3000, "top-end", "8");
    return;
  }

  $.ajax({
    url: url = "../../../process/companies/update_company",
    type: "POST",
    data: {
      csrf_token: csrfToken,
      company_uuid: uuid,
      batch_uuid: batchUuid,
      name: data.name,
      email: data.email,
      work_setup: data.work_setup,
      accreditation_status: data.accreditation_status,
      industry: data.industry,
      address: data.address,
      city: data.city,
      phone: data.phone,
      website: data.website,
      blacklist_reason: data.blacklist_reason,
      program_uuids: data.program_uuids,
      total_slots: data.total_slots,
      supervisor_profile_uuid: data.supervisor_profile_uuid,
      supervisor_first_name: data.supervisor_first_name,
      supervisor_last_name: data.supervisor_last_name,
      supervisor_email: data.supervisor_email,
      supervisor_mobile: data.supervisor_mobile,
      supervisor_position: data.supervisor_position,
      supervisor_department: data.supervisor_department,
    },
    dataType: "json",
    timeout: 5000,
    beforeSend: function () {
      ModalVersion(swalTheme, "Please wait...", "We are saving the company details.", "info", 0, "center");
    },
    success: function (response) {
      swal.close();
      if (response.status === "success") {
        ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
        $("#EditCompanyModal").modal("hide");
        getCompanies();
        viewCompanydetails(uuid, batchUuid, $("#activeBatchLabel").text());
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.Details, "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function viewCompanydetails(uuid, batchUuid, batchLabel) {
  if (!uuid) {
    ToastVersion(swalTheme, "Invalid company identifier.", "error", 3000, "top-end", "8");
    return;
  }

  if (!batchUuid) {
    ToastVersion(swalTheme, "No active batch found. Please set an active batch to view company details.", "warning", 3000, "top-end", "8");
  }

  $.ajax({
    url: "../../../process/companies/get_company",
    type: "POST",
    data: {
      csrf_token: csrfToken,
      company_uuid: uuid,
      batch_uuid: batchUuid,
    },
    dataType: "json",
    timeout: 5000,
    beforeSend: function () {
      if (!batchUuid) {
        ToastVersion(swalTheme, "No active batch found. Please set an active batch to view company details.", "warning", 3000, "top-end", "8");
        $("#ViewCompanyModal").modal("hide");
        return false;
      }
      ModalVersion(swalTheme, "Please wait...", "We are loading the company details.", "info", 0, "center");
    },
    success: function (response) {
      swal.close();
      if (response.status === "success") {
        $("#ViewCompanyModal").modal("show");
        $("#viewCompanyName").text(response.data.company.name);
        $("#viewCompanyIndustry").text(response.data.company.industry);
        $("#viewCompanyEmail").text(response.data.company.email);
        $("#viewCompanyContactNumber").text(response.data.company.phone);
        $("#viewCompanyAddress").text(response.data.company.address);
        $("#viewCompanyWebsite").attr("href", response.data.company.website).text(response.data.company.website);
        const badgeClass =
          response.data.company.moa_status === "expiring"
            ? "bg-warning-subtle text-warning"
            : response.data.company.accreditation_status === "active"
              ? "bg-success-subtle text-success"
              : response.data.company.accreditation_status === "inactive"
                ? "bg-danger-subtle text-danger"
                : "bg-warning-subtle text-warning";
        const badgeText = response.data.company.moa_status === "expiring" ? "MOA Expiring" : response.data.company.status_label;
        $("#viewCompanyStatus").removeClass("bg-success bg-danger bg-warning").addClass(badgeClass).text(badgeText);
        $("#viewCompanyBatch").text(batchLabel ? `${batchLabel}` : "No Active Batch");
        $("#currentBatchLabel").text(batchLabel ? `(${batchLabel})` : "No Active Batch");
        $("#viewCompanyTotalSlots").text(response.data.total_slots);
        $("#viewCompanyFilledSlots").text(response.data.filled_slots);
        $("#viewCompanyRemainingSlots").text(response.data.remaining_slots);
        $("#viewCompanyMOAExpiry").text(response.data.company.moa_expiry ? response.data.company.moa_expiry : "Not Available");
        $("#viewContactName").text(response.data.contacts[0].name);
        $("#viewContactEmail").text(response.data.contacts[0].email);
        $("#viewContactNumber").text(response.data.contacts[0].phone);
        $("#viewContactPosition").text(response.data.contacts[0].position);

        $("#editCompanyBtn")
          .off("click")
          .on("click", function () {
            $("#ViewCompanyModal").modal("hide");
            editCompany(uuid, batchUuid);
          });

        $("#uploadMoABtn")
          .off("click")
          .on("click", function () {
            $("#ViewCompanyModal").modal("hide");
            uploadDocument(uuid, batchUuid, "moa");
          });

        const documentsContainer = $("#viewCompanyDocuments");
        documentsContainer.empty();
        if (response.data.documents && response.data.documents.length > 0) {
          const filenameMap = {
            moa: "Memorandum of Agreement (MOA)",
            nda: "Non-Disclosure Agreement (NDA)",
            insurance: "Insurance Certificate",
            bir_cert: "BIR Certificate",
            sec_dti: "SEC/DTI Registration",
            other: "Other Document",
          };
          response.data.documents.forEach((doc) => {
            const docElement = `
                  <div class="alert bg-blur-5 bg-semi-transparent bg-secondary-subtle text-body border d-flex align-items-center gap-2 mb-2" role="alert">
                    <i class="mx-1 bi ${doc.doc_type === "moa" ? "bi-file-earmark-text" : doc.doc_type === "nda" ? "bi-file-lock" : doc.doc_type === "insurance" ? "bi-shield-check" : doc.doc_type === "bir_cert" ? "bi-receipt-cutoff" : doc.doc_type === "sec_dti" ? "bi-building" : "bi-file-earmark"} flex-shrink-0"></i>
                    <div class="d-flex flex-column flex-grow-1">
                      <span class="fw-bold"><a href="../../../file_serve.php?uuid=${doc.uuid}&action=inline" target="_blank" class="text-decoration-none text-primary">${filenameMap[doc.doc_type] || doc.file_name}</a></span>
                      <small class="text-muted">${doc.doc_type.toUpperCase()} · Created ${new Date(doc.created_at).toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })}</small>
                      ${doc.valid_from && doc.valid_until ? `<small class="text-muted">Valid: ${new Date(doc.valid_from).toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })} to ${new Date(doc.valid_until).toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" })}</small>` : ""}
                    </div>
                  </div>
                `;
            documentsContainer.append(docElement);
          });
        } else {
          documentsContainer.append('<div class="text-muted"><small>No documents available</small></div>');
        }

        const viewCompanyStudentsContainer = $("#viewCompanyStudents");
        viewCompanyStudentsContainer.empty();
        if (response.students && response.students.length > 0) {
          response.students.forEach((student) => {
            const studentElement = `
                  <div class="alert bg-blur-5 bg-semi-transparent bg-secondary-subtle text-body d-flex align-items-center gap-2 mb-2 py-2 px-3" role="alert">
                    <i class="bi bi-person flex-shrink-0"></i>
                    <div class="d-flex flex-column flex-grow-1 min-w-0">
                      <span class="fw-medium small">${student.name}</span>
                      <small class="text-muted">${student.program} - ${student.year_level}</small>
                    </div>
                  </div>
                `;
            viewCompanyStudentsContainer.append(studentElement);
          });
        } else {
          viewCompanyStudentsContainer.append('<div class="text-muted"><small>No students assigned to this company</small></div>');
        }

        const viewCompanyAcceptedProgramsContainer = $("#viewCompanyAcceptedProgramsContainer");
        viewCompanyAcceptedProgramsContainer.empty();

        if (response.data.accepted_programs && response.data.accepted_programs.length > 0) {
          response.data.accepted_programs.forEach((program) => {
            const programElement = `
                <div class="col">
                  <div class="alert bg-blur-5 bg-semi-transparent bg-secondary-subtle text-body border d-flex align-items-center gap-2 mb-2 py-2 px-3" role="alert">
                    <i class="bi bi-mortarboard flex-shrink-0 mx-2"></i>
                    <div class="d-flex flex-column flex-grow-1">
                      <span class="fw-medium small">${program.name}</span>
                      <small class="text-muted">${program.code}</small>
                    </div>
                  </div>
                </div>
                `;
            viewCompanyAcceptedProgramsContainer.append(programElement);
          });
        } else {
          viewCompanyAcceptedProgramsContainer.append('<div class="text-muted"><small>No accepted programs specified</small></div>');
        }
      } else if (response.status === "critical") {
        ToastVersion(swalTheme, response.details, "error", 3000, "top-end", "8");
      } else {
        ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function uploadDocument(companyUuid, batchUuid, docType) {
  if (!companyUuid) {
    ToastVersion(swalTheme, "Invalid company identifier.", "error", 3000, "top-end", "8");
    return;
  }

  if (!batchUuid) {
    ToastVersion(swalTheme, "No active batch found. Please set an active batch to upload documents.", "warning", 3000, "top-end", "8");
    return;
  }

  $("#documentType").val(docType);

  $("#documentFile").change(function () {
    const file = this.files[0];
    if (file) {
      const allowedTypes = ["application/pdf"];
      if (!allowedTypes.includes(file.type)) {
        ToastVersion(swalTheme, "Invalid file type. Please upload a PDF file.", "error", 3000, "top-end", "8");
        $(this).val("");
        return;
      }

      if (file.size > 10 * 1024 * 1024) {
        ToastVersion(swalTheme, "File size exceeds the 10MB limit. Please upload a smaller file.", "error", 3000, "top-end", "8");
        $(this).val("");
        return;
      }
    } else {
      ToastVersion(swalTheme, "No file selected. Please choose a file to upload.", "warning", 3000, "top-end", "8");
    }
  });

  $("#ValidFrom").change(function () {
    const validFromDate = $(this).val();
    $("#ValidUntil").attr("min", validFromDate);
  });

  $("#Docupload").modal("show");

  $("#uploadDocumentBtn")
    .off("click")
    .on("click", function () {
      const fileInput = $("#documentFile")[0];
      const file = fileInput.files[0];
      const validFrom = $("#ValidFrom").val();
      const validUntil = $("#ValidUntil").val();

      if (!file) {
        ToastVersion(swalTheme, "Please select a file to upload.", "warning", 3000, "top-end", "8");
        return;
      }

      const formData = new FormData();
      formData.append("csrf_token", csrfToken);
      formData.append("company_uuid", companyUuid);
      formData.append("batch_uuid", batchUuid);
      formData.append("doc_type", $("#documentType").val());
      formData.append("valid_from", validFrom);
      formData.append("valid_until", validUntil);
      formData.append("document_file", file);

      $.ajax({
        url: "../../../process/companies/upload_document",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: "json",
        timeout: 10000,
        beforeSend: function () {
          $("#uploadDocumentBtn").prop("disabled", true).text("Uploading...");
        },
        success: function (response) {
          $("#uploadDocumentBtn").prop("disabled", false).text("Upload");
          if (response.status === "success") {
            ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
            $("#Docupload").modal("hide");
            getCompanies();
            viewCompanydetails(companyUuid, batchUuid, $("#activeBatchLabel").text());
          } else if (response.status === "critical") {
            ToastVersion(swalTheme, response.Details, "error", 3000, "top-end", "8");
          } else {
            ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
          }
        },
        error: function (xhr, status, error) {
          $("#uploadDocumentBtn").prop("disabled", false).text("Upload");
          Errors(xhr, status, error);
        },
      });
    });
}

$(document).ready(function () {
  bindCompanyFilterEvents();
  getCompanies();

  function escapeHtml(value) {
    return String(value ?? "").replace(/[&<>'\"]/g, function (char) {
      return ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        "'": "&#39;",
        '"': "&quot;",
      })[char];
    });
  }

  $("#saveCompanyBtn").click(function () {
    const batchUuid = $("#activeBatchLabel").attr("data-batch-uuid");
    if (!batchUuid) {
      ToastVersion(swalTheme, "No active batch found. Please set an active batch to create a new company.", "warning", 3000, "top-end", "8");
      return;
    }

    const name = $("#companyName").val().trim();
    const industry = $("#companyindustry").val().trim();
    const email = $("#companyemail").val().trim();
    const phone = $("#companycontact").val().trim();
    const address = $("#companyaddress").val().trim();
    const city = $("#companycity").val().trim();
    const website = $("#companywebsite").val().trim();
    const workSetup = $("#companyworksetup").val();
    const accreditationStatus = $("#companyaccreditationstatus").val();
    const totalSlots = $("#companytotalslots").val();
    const blacklistReason = $("#companyblocklistedreason").val().trim();
    const acceptedProgramUuids = [];
    $("#acceptedProgramsContainer").find("input[type='checkbox']:checked").each(function () {
      acceptedProgramUuids.push($(this).val());
    });
    const contactName = $("#companycontactname").val().trim();
    const contactPosition = $("#companyposition").val().trim();
    const contactEmail = $("#companycontactemail").val().trim();
    const contactPhone = $("#companycontactnumber").val().trim();
    const supervisorFirstName = $("#supervisorfirstname").val().trim();
    const supervisorLastName = $("#supervisorlastname").val().trim();
    const supervisorEmail = $("#supervisoremail").val().trim();
    const supervisorMobile = $("#supervisormobile").val().trim();
    const supervisorPosition = $("#supervisorposition").val().trim();
    const supervisorDepartment = $("#supervisordepartment").val().trim();

    if (!name || !industry || !email || !phone || !address || !city || !website || !workSetup || !accreditationStatus || !totalSlots) {
      ToastVersion(swalTheme, "Please fill in all required fields.", "warning", 3000, "top-end", "8");
      return;
    }

    if (!supervisorFirstName || !supervisorLastName || !supervisorEmail || !supervisorMobile || !supervisorPosition || !supervisorDepartment) {
      ToastVersion(swalTheme, "Please complete the supervisor account details.", "warning", 3000, "top-end", "8");
      return;
    }

    if (accreditationStatus === "blacklisted" && !blacklistReason) {
      ToastVersion(swalTheme, "Please provide a reason for blacklisting the company.", "warning", 3000, "top-end", "8");
      return;
    }

    const companyData = {
      name: name,
      industry: industry,
      email: email,
      phone: phone,
      address: address,
      city: city,
      website: website,
      work_setup: workSetup,
      accreditation_status: accreditationStatus,
      total_slots: totalSlots,
      blacklist_reason: blacklistReason,
      program_uuids: acceptedProgramUuids,
      contact_name: contactName,
      contact_position: contactPosition,
      contact_email: contactEmail,
      contact_phone: contactPhone,
      supervisor_first_name: supervisorFirstName,
      supervisor_last_name: supervisorLastName,
      supervisor_email: supervisorEmail,
      supervisor_mobile: supervisorMobile,
      supervisor_position: supervisorPosition,
      supervisor_department: supervisorDepartment,
    };

    $.ajax({
      url: "../../../process/companies/create_company",
      type: "POST",
      data: {
        csrf_token: csrfToken,
        batch_uuid: batchUuid,
        ...companyData,
      },
      dataType: "json",
      timeout: 5000,
      beforeSend: function () {
        $("#saveCompanyBtn").prop("disabled", true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...');
      },
      success: function (response) {
        $("#saveCompanyBtn").prop("disabled", false).text("Save Company");
        if (response.status === "success") {
          $("#NewCompanyModal").modal("hide");
          getCompanies();
          viewCompanydetails(response.uuid, batchUuid, $("#activeBatchLabel").text());

          const tempPassword = escapeHtml(response.supervisor_temp_password || "—");
          const supervisorName = escapeHtml(response.supervisor_full_name || "—");
          const supervisorEmailSafe = escapeHtml(response.supervisor_email || "—");

          Swal.fire({
            icon: "success",
            title: "Company and supervisor created",
            html: `
              <div class="text-start">
                <p class="mb-2"><strong>Supervisor:</strong> ${supervisorName}</p>
                <p class="mb-2"><strong>Email:</strong> ${supervisorEmailSafe}</p>
                <p class="mb-0"><strong>Temporary password:</strong> <code>${tempPassword}</code></p>
              </div>
            `,
            confirmButtonText: "Got it",
            customClass: {
              popup: "bg-blur-5 bg-semi-transparent border-1 rounded-3 shadow-lg",
              container: "overflow-hidden",
              confirmButton: "btn btn-success px-4 py-2 rounded-3"
            },
            buttonsStyling: false,
            showClass: { popup: "bounce-in-fwd" },
            hideClass: { popup: "slide-out-blurred-bottom" },
          });
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
        } else if (response.status === "critical") {
          ToastVersion(swalTheme, response.Details, "error", 3000, "top-end", "8");
        } else {
          ToastVersion(swalTheme, response.message, "warning", 3000, "top-end", "8");
        }
      },
      error: function (xhr, status, error) {
        $("#saveCompanyBtn").prop("disabled", false).text("Save Company");
        Errors(xhr, status, error);
      }
    });
  });

  $("#cancelNewCompanyBtn").click(function () {
    $("#NewCompanyModal").find("input[type='text'], input[type='email'], input[type='tel'], select").val("");
    $("#acceptedProgramsContainer").empty();
  });
});
