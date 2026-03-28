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

let allCompanies = [];

function viewCompanyDetails(uuid) {
  $("#ViewCompanyModal").attr("data-company-uuid", uuid);
  $.ajax({
    url: "../../../Assets/api/company_functions",
    method: "POST",
    data: { action: "fetch_company_details", uuid: uuid },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const company = response.data.company;
        const slots = response.data.slots && response.data.slots.length > 0 ? response.data.slots[0] : null;
        const status = company.moa_status && company.moa_status !== "none" ? company.moa_status : company.accreditation_status;
        const badgeColor = status === "active" ? "success" : status === "pending" ? "warning" : status === "expired" || status === "expiring" ? "danger" : "secondary";
        const statusLabel = status.charAt(0).toUpperCase() + status.slice(1);

        $("#viewCompanyName").text(company.name || "━");
        $("#viewCompanyIndustry").text(company.industry || "━");
        $("#viewCompanyEmail").text(company.email || "━");
        $("#viewCompanyContactNumber").text(company.phone || "━");
        $("#viewCompanyAddress").text(company.address || "━");
        $("#viewCompanyCity").text(company.city || "━");
        $("#viewCompanyWebsite").text(company.website || "━");
        $("#viewCompanyStatus").html(`<span class="badge bg-${badgeColor} text-white rounded-pill fw-medium">${statusLabel}</span>`);
        $("#viewCompanyBatch").text(slots ? `${slots.school_year} ${slots.semester} Semester` : "━");
        $("#viewCompanyTotalSlots").text(slots ? slots.total_slots : "━");
        $("#viewCompanyFilledSlots").text(slots ? slots.filled_slots : "━");
        $("#viewCompanyRemainingSlots").text(slots ? slots.total_slots - slots.filled_slots : "━");
        const moaDocument = response.data.documents && response.data.documents.length > 0 ? response.data.documents.find((doc) => doc.doc_type === "moa") : null;
        $("#viewCompanyMOAExpiry").text(
          moaDocument && moaDocument.valid_until ? new Date(moaDocument.valid_until).toLocaleDateString("en-US", { year: "numeric", month: "long", day: "numeric" }) : "━",
        );

        $("#viewContactName").text(response.data.contacts && response.data.contacts.length > 0 ? response.data.contacts[0].name : "━");
        $("#viewContactPosition").text(response.data.contacts && response.data.contacts.length > 0 ? response.data.contacts[0].position : "━");
        $("#viewContactEmail").text(response.data.contacts && response.data.contacts.length > 0 ? response.data.contacts[0].email : "━");
        $("#viewContactNumber").text(response.data.contacts && response.data.contacts.length > 0 ? response.data.contacts[0].phone : "━");

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
                  <div class="alert alert-secondary d-flex align-items-center gap-2 mb-2" role="alert">
                    <i class="bi bi-file-earmark me-2"></i>
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

        $("#currentBatchLabel").text(slots ? `${slots.school_year} ${slots.semester} Semester` : "N/A");

        const studentsContainer = $("#viewCompanyStudents");
        studentsContainer.empty();
        if (response.data.students && response.data.students.length > 0) {
          response.data.students.forEach((student) => {
            const studentElement = `
                            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0" role="alert">
                                <i class="bi bi-person me-2"></i>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold">${student.name}</span>
                                    <small>${student.program} - ${student.year_level}</small>
                                </div>
                            </div>
                        `;
            studentsContainer.append(studentElement);
          });
        } else {
          studentsContainer.append('<div class="text-muted"><small>No students currently placed at this company</small></div>');
        }
      }
    },
    error: function () {
      ToastVersion(swalTheme, "An error occurred while fetching company details.", "error", 3000);
    },
  });
}

function renderCompanies(companies) {
  const companycontainer = $("#companiesContainer");
  companycontainer.empty();

  if (!Array.isArray(companies) || companies.length === 0) {
    const noResultCard = `
            <div class="col">
                <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border border-muted" style="--blur-lvl: 0.60">
                    <div class="card-body py-4 px-4 text-center">
                        <i class="bi bi-building fs-3 text-muted"></i>
                        <h6 class="mt-2 mb-1">No companies found</h6>
                        <small class="text-muted">Try adjusting your search or filters.</small>
                    </div>
                </div>
            </div>
        `;
    companycontainer.append(noResultCard);
    return;
  }

  companies.forEach((company) => {
    const acceptedPrograms = (company.accepted_programs || "")
      .split(",")
      .map((program) => program.trim())
      .filter((program) => program.length > 0);

    const companyCard = `
                  <div class="col">
                    <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm" style="--blur-lvl: 0.60">
                      <div class="card-body py-3 px-4">
                        <div class="hstack">
                          <p class="card-title mb-0 fw-bold">${company.name}</p>
                          <span class="badge bg-white text-success rounded-pill ms-auto fw-medium ${company.moa_status === "valid" || company.moa_status === "none" ? "d-block" : "d-none"}">${company.moa_status === "none" ? (company.accreditation_status === "active" ? "Active" : company.accreditation_status === "pending" ? "Pending" : company.accreditation_status === "expired" ? "Expired" : "N/A") : "Acredeited"}</span>
                          <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill ms-auto fw-medium border ${company.moa_status === "expiring" ? "d-block" : "d-none"}">MOA Expiring</span>
                          <span class="badge bg-danger-subtle text-danger-emphasis rounded-pill ms-auto fw-medium border ${company.moa_status === "expired" ? "d-block" : "d-none"}">MOA Expired</span>
                        </div>
                        <small class="text-muted" style="font-size: 0.875rem;">${company.industry} · ${company.city}</small>
                        <div class="row row-cols-md-4 g-3 mt-2">
                          <div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 bprder-0 shadow-sm px-2 h-100" style="--blur-lvl: 0.40">
                              <div class="card-body p-2">
                                <p class="card-title mb-0 text-muted">Slots</p>
                                <p class="card-text mb-0 fw-bold fs-5"><span>${company.filled_slots}</span>/<span>${company.total_slots}</span></p>
                              </div>
                            </div>
                          </div>
                          <div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 bprder-0 shadow-sm px-2 h-100" style="--blur-lvl: 0.40">
                              <div class="card-body p-2">
                                <p class="card-title mb-0 text-muted">Remaining</p>
                                <p class="card-text mb-0 fw-bold fs-5"><span>${company.remaining_slots}</span></p>
                              </div>
                            </div>
                          </div>
                          <div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 bprder-0 shadow-sm px-2 h-100" style="--blur-lvl: 0.40">
                              <div class="card-body p-2">
                                <p class="card-title mb-0 text-muted">Work Setup</p>
                                <p class="card-text mb-0 fw-bold fs-5"><span>${company.work_setup.charAt(0).toUpperCase() + company.work_setup.slice(1)}</span></p>
                              </div>
                            </div>
                          </div>
                          <div class="col">
                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 bprder-0 shadow-sm px-2 h-100" style="--blur-lvl: 0.40">
                              <div class="card-body p-2">
                                <p class="card-title mb-0 text-muted">Moa expiry</p>
                                <p class="card-text mb-0 fw-bold fs-5"><span>${company.moa_expiry ? company.moa_expiry : "N/A"}</span></p>
                                ${
                                  company.moa_expiry
                                    ? `<div class="progress mt-1" style="height: 4px;">
                                  <div class="progress-bar progress-bar-striped progress-bar-animated rounded ${(() => {
                                    const percentage = Math.min(100, Math.max(0, (company.moa_days_left / 30) * 100));
                                    if (percentage <= 33) return "bg-danger";
                                    if (percentage <= 66) return "bg-warning";
                                    return "bg-success";
                                  })()}" role="progressbar" style="width: ${Math.min(100, Math.max(0, (company.moa_days_left / 30) * 100))}%;" aria-valuenow="${company.moa_days_left}" aria-valuemin="0" aria-valuemax="30"></div>
                                </div>`
                                    : ""
                                }
                              </div>
                            </div>
                          </div>
                        </div>
                        <hr class="my-3">
                        <div class="hstack gap-2">
                          <div id="badge">
                            ${acceptedPrograms.map((program) => `<span class="badge bg-dark text-white rounded-pill fw-medium border">${program}</span>`).join(" ")}
                          </div>
                          <div class="ms-auto">
                            <button class="btn btn-sm btn-outline-dark border text-light ms-auto text-nowrap" data-bs-toggle="modal" data-bs-target="#ViewCompanyModal" id="viewCompanyBtn-${company.uuid}">
                              <i class="bi bi-eye me-sm-1 px-3 px-sm-0"></i>
                              <span class="d-none d-sm-inline">View</span>
                            </button>
                            <button class="btn btn-sm btn-outline-dark border text-light ms-auto text-nowrap" data-bs-toggle="modal" data-bs-target="#EditCompanyModal" id="editCompanyBtn-${company.uuid}">
                              <i class="bi bi-pencil-square me-sm-1 px-3 px-sm-0"></i>
                              <span class="d-none d-sm-inline">Edit</span>
                            </button>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                `;
    companycontainer.append(companyCard);

    $(`#viewCompanyBtn-${company.uuid}`).on("click", function () {
      viewCompanyDetails(company.uuid);
    });

    $(`#editCompanyBtn-${company.uuid}`).on("click", function () {
      getcompanydata(company);
    });
  });
}

function getcompanydata(company, callback) {
  $.ajax({
    url: "../../../Assets/api/company_functions",
    method: "POST",
    data: { action: "fetch_company_details", uuid: company.uuid },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        const company = response.data.company;
        const program = response.data.programs;
        const contact = response.data.contacts;
        $("#EditcompanyName").val(company.name || "");
        $("#Editcompanyindustry").val(company.industry || "");
        $("#Editcompanyemail").val(company.email || "");
        $("#Editcompanycontact").val(company.phone || "");
        $("#Editcompanyaddress").val(company.address || "");
        $("#Editcompanycity").val(company.city || "");
        $("#Editcompanywebsite").val(company.website || "");
        $("#EditcompanyTotalSlots").val(response.data.slots && response.data.slots.length > 0 ? response.data.slots[0].total_slots : "");
        $("#Editcompanyworksetup").val(company.work_setup || "");
        $("#Editcompanyaccreditationstatus").val(company.accreditation_status || "");
        $("#Editcompanytotalslots").val(response.data.slots && response.data.slots.length > 0 ? response.data.slots[0].total_slots : "");

        if (program && Array.isArray(program)) {
          program.forEach((prog) => {
            const checkbox = $(`#Editprogram${prog.uuid}`);
            if (checkbox.length) {
              checkbox.prop("checked", true);
            }
          });
        } else {
          $("#EditacceptedProgramsContainer input[type=checkbox]").prop("checked", false);
        }

        $("#Editcompanycontactname").val(contact && contact.length > 0 ? contact[0].name : "");
        $("#Editcompanyposition").val(contact && contact.length > 0 ? contact[0].position : "");
        $("#Editcompanycontactemail").val(contact && contact.length > 0 ? contact[0].email : "");
        $("#Editcompanycontactnumber").val(contact && contact.length > 0 ? contact[0].phone : "");

        $("#EditCompanyModal").attr("data-company-uuid", company.uuid);
        if (callback) callback();
      } else {
        ToastVersion(swalTheme, response.message || "Failed to fetch company details.", "error", 3000);
      }
    },
    error: function () {
      ToastVersion(swalTheme, "An error occurred while fetching company details.", "error", 3000);
    },
  });
}

function applySearchAndFilters() {
  const searchValue = ($("#searchInput").val() || "").toLowerCase().trim();
  const statusValue = ($("#filterStatus").val() || "").toLowerCase().trim();
  const setupValue = ($("#filterSetup").val() || "").toLowerCase().trim();
  const programValue = ($("#filterProgram").val() || "").toLowerCase().trim();

  const filteredCompanies = allCompanies.filter((company) => {
    const companyName = (company.name || "").toLowerCase();
    const companyIndustry = (company.industry || "").toLowerCase();
    const companyCity = (company.city || "").toLowerCase();
    const companySetup = (company.work_setup || "").toLowerCase();
    const companyPrograms = (company.accepted_program_uuids || "")
      .toLowerCase()
      .split(",")
      .map((p) => p.trim());

    let companyStatus = (company.moa_status && company.moa_status !== "none" ? company.moa_status : company.accreditation_status || "").toLowerCase();
    if (companyStatus === "valid") companyStatus = "active";

    const matchesSearch = !searchValue || companyName.includes(searchValue) || companyIndustry.includes(searchValue) || companyCity.includes(searchValue);

    const matchesStatus = !statusValue || companyStatus === statusValue;
    const matchesSetup = !setupValue || companySetup === setupValue;
    const matchesProgram = !programValue || companyPrograms.includes(programValue);

    return matchesSearch && matchesStatus && matchesSetup && matchesProgram;
  });

  renderCompanies(filteredCompanies);
}

function loadCompanies() {
  $.ajax({
    url: "../../../Assets/api/company_functions",
    method: "POST",
    data: { action: "fetch_companies" },
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        allCompanies = Array.isArray(response.data) ? response.data : [];
        const activeBatch = response.active_batch;
        $("#totalcompanies").text(allCompanies.length);
        applySearchAndFilters();

        const programFilter = $("#filterProgram");
        const programs = Array.isArray(response.programs) ? response.programs : [];
        programFilter.html(
          ['<option class="CustomOption" value="">All Programs</option>']
            .concat(
              programs.length
                ? programs.map(({ code }) => `<option class="CustomOption" value="${String(code).toLowerCase()}">${code}</option>`)
                : ['<option class="CustomOption" value="" disabled>No programs available</option>'],
            )
            .join(""),
        );

        const acceptedProgramsContainer = $("#acceptedProgramsContainer");
        acceptedProgramsContainer.html(
          programs.length
            ? programs.map(
                ({ uuid, code, name }) =>
                  `<div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${uuid}" id="program${uuid}">
                        <label class="form-check-label" for="program${uuid}"><small>${code} - ${name}</small></label>
                    </div>`,
              )
            : '<div class="text-muted"><small>No programs available</small></div>',
        );

        const editAcceptedProgramsContainer = $("#EditacceptedProgramsContainer");
        editAcceptedProgramsContainer.html(
          programs.length
            ? programs.map(
                ({ uuid, code, name }) =>
                  `<div class="form-check">
                        <input class="form-check-input" type="checkbox" value="${uuid}" id="Editprogram${uuid}">
                        <label class="form-check-label" for="Editprogram${uuid}"><small>${code} - ${name}</small></label>
                    </div>`,
              )
            : '<div class="text-muted"><small>No programs available</small></div>',
        );

        const statusFilter = $("#filterStatus");
        const statuses = Array.isArray(response.statuses) ? response.statuses : [];
        statusFilter.html(
          ['<option class="CustomOption" value="">All Statuses</option>']
            .concat(
              statuses.length
                ? statuses.map((status) => `<option class="CustomOption" value="${String(status).toLowerCase()}">${status.charAt(0).toUpperCase() + status.slice(1)}</option>`)
                : ['<option class="CustomOption" value="" disabled>No statuses available</option>'],
            )
            .join(""),
        );

        const setupFilter = $("#filterSetup");
        const setups = Array.isArray(response.work_setups) ? response.work_setups : [];
        setupFilter.html(
          ['<option class="CustomOption" value="">All Setups</option>']
            .concat(
              setups.length
                ? setups.map((setup) => `<option class="CustomOption" value="${String(setup).toLowerCase()}">${setup.charAt(0).toUpperCase() + setup.slice(1)}</option>`)
                : ['<option class="CustomOption" value="" disabled>No work setups available</option>'],
            )
            .join(""),
        );

        $("#activeBatchLabel").text("AY " + (activeBatch ? `${activeBatch.school_year} ${activeBatch.semester} Semester` : "N/A"));
      } else {
        ToastVersion(swalTheme, response.message || "Failed to fetch companies.", "error", 3000);
      }
    },
    error: function () {
      ToastVersion(swalTheme, "An error occurred while fetching companies.", "error", 3000);
    },
  });
}

function checkUrlForCompanyUuid() {
  const urlParams = new URLSearchParams(window.location.search);
  const companyUuidFromUrl = urlParams.get("company_uuid");
  if (companyUuidFromUrl) {
    viewCompanyDetails(companyUuidFromUrl);
    const newUrl = window.location.origin + window.location.pathname;
    window.history.replaceState({}, document.title, newUrl);
    $("#ViewCompanyModal").modal("show");
  }
}

$(document).ready(function () {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  loadCompanies();
  signOut();
  checkUrlForCompanyUuid();

  $("#searchInput").on("input", applySearchAndFilters);
  $("#filterStatus").on("change", applySearchAndFilters);
  $("#filterSetup").on("change", applySearchAndFilters);
  $("#filterProgram").on("change", applySearchAndFilters);

  $("#cancelEditCompanyBtn").on("click", function () {
    $("#EditCompanyModal").removeAttr("data-company-uuid");
    $("#EditacceptedProgramsContainer input[type=checkbox]").prop("checked", false);
    $("#EditCompanyModal").find("input[type=text], input[type=email], input[type=tel], textarea").val("");
    $("#Editcompanyworksetup").val("");
    $("#Editcompanyaccreditationstatus").val("");

    if ($("#EditCompanyModal").attr("data-opened-from-view") === "true") {
      $("#EditCompanyModal").removeAttr("data-opened-from-view");
      $("#ViewCompanyModal").modal("show");
    }
  });

  $("#editCompanyBtn").on("click", function () {
    const companyUuid = $("#ViewCompanyModal").attr("data-company-uuid");
    if (!companyUuid) {
      ToastVersion(swalTheme, "No company selected for editing.", "error", 3000);
      return;
    } else {
      getcompanydata({ uuid: companyUuid });
      $("#ViewCompanyModal").modal("hide");
      $("#EditCompanyModal").modal("show");
      $("#EditCompanyModal").attr("data-opened-from-view", "true");
    }
  });

  $("#saveCompanyBtn").on("click", function () {
    const companyName = ($("#companyName").val() || "").trim();
    const companyindustry = ($("#companyindustry").val() || "").trim();
    const companyemail = ($("#companyemail").val() || "").trim();
    const companycontact = ($("#companycontact").val() || "").trim();
    const companyaddress = ($("#companyaddress").val() || "").trim();
    const companycity = ($("#companycity").val() || "").trim();
    const companywebsite = ($("#companywebsite").val() || "").trim();
    const companytotalSlots = ($("#companytotalslots").val() || "").trim();
    const companyworksetup = ($("#companyworksetup").val() || "").trim();
    const companyaccreditationstatus = ($("#companyaccreditationstatus").val() || "").trim();
    const acceptedPrograms = [];
    $("#acceptedProgramsContainer input[type=checkbox]:checked").each(function () {
      acceptedPrograms.push($(this).val());
    });
    const companycontactname = ($("#companycontactname").val() || "").trim();
    const companyposition = ($("#companyposition").val() || "").trim();
    const companycontactemail = ($("#companycontactemail").val() || "").trim();
    const companycontactnumber = ($("#companycontactnumber").val() || "").trim();

    const data = {
      name: companyName,
      email: companyemail,
      work_setup: companyworksetup,
      accreditation_status: companyaccreditationstatus,
      industry: companyindustry,
      address: companyaddress,
      city: companycity,
      phone: companycontact,
      website: companywebsite,
      contact_name: companycontactname,
      contact_position: companyposition,
      contact_email: companycontactemail,
      contact_phone: companycontactnumber,
      total_slots: companytotalSlots,
      program_uuids: acceptedPrograms,
    };

    if (!companyName || !companyemail || !companycontact || !companyaddress || !companycity || !companywebsite || !companyworksetup) {
      ToastVersion(swalTheme, "Please fill in all Basic Information fields.", "warning", 3000);
      return;
    }

    if (companyaccreditationstatus === "active" && (!companytotalSlots || isNaN(companytotalSlots) || parseInt(companytotalSlots) <= 0)) {
      ToastVersion(swalTheme, "Please enter a valid number of total slots for active companies.", "warning", 3000);
      return;
    }

    if (acceptedPrograms.length === 0) {
      ToastVersion(swalTheme, "Please select at least one accepted program.", "warning", 3000);
      return;
    }

    if (!companycontactname || !companyposition || !companycontactemail || !companycontactnumber) {
      ToastVersion(swalTheme, "Please fill in all contact person fields.", "warning", 3000);
      return;
    }

    if (!/^09\d{9}$/.test(companycontactnumber.replace(/\D/g, "")) || !/^09\d{9}$/.test(companycontact.replace(/\D/g, ""))) {
      ToastVersion(swalTheme, "Please enter a valid contact number (09XXXXXXXXX).", "warning", 3000);
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(companyemail) || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(companycontactemail)) {
      ToastVersion(swalTheme, "Please enter a valid email address.", "warning", 3000);
      return;
    }

    const validWorkSetups = ["on-site", "remote", "hybrid"];
    if (companyworksetup && !validWorkSetups.includes(companyworksetup.toLowerCase())) {
      ToastVersion(swalTheme, "Please select a valid work setup.", "warning", 3000);
      return;
    }

    if (companyaccreditationstatus && !["active", "pending", "expired"].includes(companyaccreditationstatus.toLowerCase())) {
      ToastVersion(swalTheme, "Please select a valid accreditation status.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/company_functions",
      method: "POST",
      data: { action: "add_company", data: data },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#NewCompanyModal").modal("hide");
          $("#NewCompanyModal").find("input[type=text], input[type=email], input[type=tel], textarea").val("");
          $("#companyworksetup").val("");
          $("#companyaccreditationstatus").val("");
          $("#acceptedProgramsContainer input[type=checkbox]").prop("checked", false);
          ToastVersion(swalTheme, response.message || "Company added successfully.", "success", 3000);
          loadCompanies();
        } else {
          ToastVersion(swalTheme, response.message || "Failed to add company.", "error", 3000);
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred while adding the company.", "error", 3000);
      },
    });
  });

  $("#closeViewCompanyBtn").on("click", function () {
    $("#ViewCompanyModal").removeAttr("data-company-uuid");
  });

  $("#uploadMoABtn").on("click", function () {
    const companyUuid = $("#ViewCompanyModal").attr("data-company-uuid");
    $("#Docupload").attr("data-company-uuid", companyUuid);
  });

  $("#cancelUploadBtn").on("click", function () {
    const companyUuid = $("#Docupload").attr("data-company-uuid");
    const viewcompanyUuid = $("#ViewCompanyModal").attr("data-company-uuid");
    $("#Docupload").removeAttr("data-company-uuid");

    if (companyUuid && viewcompanyUuid && companyUuid === viewcompanyUuid) {
      $("#Docupload").modal("hide");
      $("#Docupload").on("hidden.bs.modal", function () {
        $("#ViewCompanyModal").modal("show");
      });
    } else {
      $("#Docupload").modal("hide");
    }
  });

  $("#documentType").on("change", function () {
    if ($(this).val() === "moa") {
      $("#moaValidityFields").removeClass("d-none");
    } else {
      $("#moaValidityFields").addClass("d-none");
      $("#moaValidFrom").val("");
      $("#moaValidUntil").val("");
    }
  });

  $("#uploadDocumentBtn").on("click", function () {
    const companyUuid = $("#Docupload").attr("data-company-uuid");

    const documentType = $("#documentType").val();
    const validDocumentTypes = ["moa", "nda", "insurance", "bir_cert", "sec_dti", "other"];
    const fileInput = $("#documentFile")[0];
    const moaValidFrom = $("#moaValidFrom").val();
    const moaValidUntil = $("#moaValidUntil").val();
    if (!companyUuid) {
      ToastVersion(swalTheme, "No company selected for document upload.", "error", 3000);
      return;
    }
    if (!documentType || !validDocumentTypes.includes(documentType)) {
      ToastVersion(swalTheme, "Please select a valid document type.", "warning", 3000);
      return;
    }

    if (!fileInput || fileInput.files.length === 0) {
      ToastVersion(swalTheme, "Please select a file to upload.", "warning", 3000);
      return;
    }

    const file = fileInput.files[0];
    if (file.size > 10 * 1024 * 1024) {
      ToastVersion(swalTheme, "File size must be less than 10MB.", "warning", 3000);
      return;
    }

    if (documentType === "moa") {
      if (!moaValidFrom || !moaValidUntil) {
        ToastVersion(swalTheme, "Please provide valid from and until dates for the MOA.", "warning", 3000);
        return;
      }
      if (new Date(moaValidFrom) >= new Date(moaValidUntil)) {
        ToastVersion(swalTheme, "MOA valid from date must be earlier than valid until date.", "warning", 3000);
        return;
      }
    }

    const formData = new FormData();
    formData.append("action", "upload_company_document");
    formData.append("company_uuid", companyUuid);
    formData.append("document_type", documentType);
    formData.append("moa_valid_from", moaValidFrom);
    formData.append("moa_valid_until", moaValidUntil);
    formData.append("document_file", file);

    $.ajax({
      url: "../../../Assets/api/company_functions",
      method: "POST",
      data: formData,
      dataType: "json",
      contentType: false,
      processData: false,
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message || "Document uploaded successfully.", "success", 3000);
          $("#Docupload").modal("hide");
          loadCompanies();
        } else {
          ToastVersion(swalTheme, response.message || "Failed to upload document.", "error", 3000);
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred while uploading the document.", "error", 3000);
      },
    });
  });

  $("#Editcompanyaccreditationstatus").on("change", function () {
    if ($(this).val() !== "blacklisted") {
      $("#BlocklistedReasonContainer").addClass("d-none");
      $("#Editcompanyblocklistedreason").val("");
    } else {
      $("#BlocklistedReasonContainer").removeClass("d-none");
    }
  });

  $("#EditsaveCompanyBtn").on("click", function () {
    const companyUuid = $("#EditCompanyModal").attr("data-company-uuid");
    if (!companyUuid) {
      ToastVersion(swalTheme, "No company selected for editing.", "error", 3000);
      return;
    }

    const companyName = ($("#EditcompanyName").val() || "").trim();
    const companyindustry = ($("#Editcompanyindustry").val() || "").trim();
    const companyemail = ($("#Editcompanyemail").val() || "").trim();
    const companycontact = ($("#Editcompanycontact").val() || "").trim();
    const companyaddress = ($("#Editcompanyaddress").val() || "").trim();
    const companycity = ($("#Editcompanycity").val() || "").trim();
    const companywebsite = ($("#Editcompanywebsite").val() || "").trim();
    const companytotalSlots = ($("#Editcompanytotalslots").val() || "").trim();
    const companyworksetup = ($("#Editcompanyworksetup").val() || "").trim();
    const companyaccreditationstatus = ($("#Editcompanyaccreditationstatus").val() || "").trim();
    const acceptedPrograms = [];
    $("#EditacceptedProgramsContainer input[type=checkbox]:checked").each(function () {
      acceptedPrograms.push($(this).val());
    });
    const companycontactname = ($("#Editcompanycontactname").val() || "").trim();
    const companyposition = ($("#Editcompanyposition").val() || "").trim();
    const companycontactemail = ($("#Editcompanycontactemail").val() || "").trim();
    const companycontactnumber = ($("#Editcompanycontactnumber").val() || "").trim();
    const companyblocklistedreason = ($("#Editcompanyblocklistedreason").val() || "").trim();

    const data = {
      uuid: companyUuid,
      name: companyName,
      email: companyemail,
      work_setup: companyworksetup,
      accreditation_status: companyaccreditationstatus,
      industry: companyindustry,
      address: companyaddress,
      city: companycity,
      phone: companycontact,
      website: companywebsite,
      contact_name: companycontactname,
      contact_position: companyposition,
      contact_email: companycontactemail,
      contact_phone: companycontactnumber,
      total_slots: companytotalSlots,
      program_uuids: acceptedPrograms,
      blacklist_reason: companyblocklistedreason,
    };

    if (!companyName || !companyemail || !companycontact || !companyaddress || !companycity || !companywebsite || !companyworksetup) {
      ToastVersion(swalTheme, "Please fill in all Basic Information fields.", "warning", 3000);
      return;
    }

    if (companyaccreditationstatus === "active" && (!companytotalSlots || isNaN(companytotalSlots) || parseInt(companytotalSlots) <= 0)) {
      ToastVersion(swalTheme, "Please enter a valid number of total slots for active companies.", "warning", 3000);
      console.log("Invalid total slots:", companytotalSlots);
      return;
    }

    if (acceptedPrograms.length === 0) {
      ToastVersion(swalTheme, "Please select at least one accepted program.", "warning", 3000);
      return;
    }

    if (!companycontactname || !companyposition || !companycontactemail || !companycontactnumber) {
      ToastVersion(swalTheme, "Please fill in all contact person fields.", "warning", 3000);
      return;
    }

    if (!/^09\d{9}$/.test(companycontactnumber.replace(/\D/g, "")) || !/^09\d{9}$/.test(companycontact.replace(/\D/g, ""))) {
      ToastVersion(swalTheme, "Please enter a valid contact number (09XXXXXXXXX).", "warning", 3000);
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(companyemail) || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(companycontactemail)) {
      ToastVersion(swalTheme, "Please enter a valid email address.", "warning", 3000);
      return;
    }

    const validWorkSetups = ["on-site", "remote", "hybrid"];
    if (companyworksetup && !validWorkSetups.includes(companyworksetup.toLowerCase())) {
      ToastVersion(swalTheme, "Please select a valid work setup.", "warning", 3000);
      return;
    }

    if (companyaccreditationstatus && !["active", "pending", "expired"].includes(companyaccreditationstatus.toLowerCase())) {
      ToastVersion(swalTheme, "Please select a valid accreditation status.", "warning", 3000);
      return;
    }

    if (companyaccreditationstatus === "blacklisted" && !companyblocklistedreason) {
      ToastVersion(swalTheme, "Please provide a reason for blocklisting the company.", "warning", 3000);
      return;
    }

    $.ajax({
      url: "../../../Assets/api/company_functions",
      method: "POST",
      data: { action: "edit_company", data: data },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          $("#EditCompanyModal").modal("hide");
          $("#EditCompanyModal").removeAttr("data-company-uuid");
          ToastVersion(swalTheme, response.message || "Company updated successfully.", "success", 3000);
          loadCompanies();

          $("#EditCompanyModal").find("input[type=text], input[type=email], input[type=tel], textarea").val("");
          $("#Editcompanyworksetup").val("");
          $("#Editcompanyaccreditationstatus").val("");
          $("#EditacceptedProgramsContainer input[type=checkbox]").prop("checked", false);
          $("#BlocklistedReasonContainer").addClass("d-none");
          $("#Editcompanyblocklistedreason").val("");
        } else {
          ToastVersion(swalTheme, response.message || "Failed to update company.", "error", 3000);
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred while updating the company.", "error", 3000);
      },
    });
  });
});
