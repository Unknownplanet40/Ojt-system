import { ToastVersion } from "../CustomSweetAlert.js";
import { BGcircleTheme, MatchsystemThemes, SwalTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
BGcircleTheme(true);
let swalTheme = SwalTheme();

function loadCompanies() {
  const filters = {
    search: $("#searchInput").val(),
    status: $("#statusFilter").val(),
  };

  $.ajax({
    url: "../../../process/coordinators/get_companies",
    type: "POST",
    data: filters,
    dataType: "json",
    success: function (response) {
      if (response.status === "success") {
        window.allCompanies = response.companies;
        renderCompanies(response.companies);
      } else {
        Errors(response.message);
      }
    },
    error: function () {
      Errors("Failed to fetch companies data.");
    },
    complete: function () {
      $("#pageLoader").fadeOut();
    },
  });
}

function renderCompanies(companies) {
  const grid = $("#companyGrid");
  const emptyState = $("#emptyState");
  grid.empty();

  const search = $("#searchInput").val().toLowerCase();
  const statusFilter = $("#statusFilter").val();

  const filtered = companies.filter((c) => {
    const matchesSearch =
      c.name.toLowerCase().includes(search) ||
      (c.industry && c.industry.toLowerCase().includes(search)) ||
      (c.city && c.city.toLowerCase().includes(search));
    const matchesStatus = statusFilter === "" || c.accreditation_status === statusFilter;
    return matchesSearch && matchesStatus;
  });

  if (filtered.length === 0) {
    emptyState.removeClass("d-none");
    return;
  }

  emptyState.addClass("d-none");

  filtered.forEach((c) => {
    let statusBadge = "bg-secondary";
    if (c.accreditation_status === "active") statusBadge = "bg-success";
    else if (c.accreditation_status === "pending") statusBadge = "bg-warning text-dark";
    else if (c.accreditation_status === "expired") statusBadge = "bg-danger";

    const slotsFilled = parseInt(c.filled_slots || 0);
    const totalSlots = parseInt(c.total_slots || 0);
    const progress = totalSlots > 0 ? (slotsFilled / totalSlots) * 100 : 0;

    const card = `
            <div class="col-12 col-md-6 col-xl-4">
                <div class="card h-100 border shadow-sm rounded-4 glass-ui glass-ui-strong overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 mb-4">
                            <div class="rounded-3 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary" style="width: 50px; height: 50px;">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                            <div class="min-w-0 flex-grow-1">
                                <h6 class="mb-1 fw-bold text-wrap text-white">${c.name}</h6>
                                <p class="mb-0 text-white-50 small text-truncate">${c.industry || "General Industry"}</p>
                            </div>
                            <div class="align-self-start">
                                <span class="badge ${statusBadge} rounded-pill px-2 py-1 x-small">${c.accreditation_status.toUpperCase()}</span>
                            </div>
                        </div>

                        <div class="vstack gap-2 mb-4">
                            <div class="d-flex align-items-center gap-2 text-white-50 small">
                                <i class="bi bi-geo-alt text-primary"></i>
                                <span class="text-truncate">${c.city || "N/A"}, ${c.address || ""}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-white-50 small">
                                <i class="bi bi-envelope text-primary"></i>
                                <span class="text-truncate">${c.email || "N/A"}</span>
                            </div>
                            <div class="d-flex align-items-center gap-2 text-white-50 small">
                                <i class="bi bi-briefcase text-primary"></i>
                                <span class="text-truncate">${c.work_setup ? c.work_setup.toUpperCase() : "N/A"}</span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-white-50">OJT Capacity</small>
                                <small class="text-white fw-bold">${slotsFilled}/${totalSlots} Slots</small>
                            </div>
                            <div class="progress bg-white bg-opacity-10" style="height: 6px;">
                                <div class="progress-bar bg-primary" role="progressbar" style="width: ${progress}%"></div>
                            </div>
                        </div>

                        <div class="mt-auto">
                            <button class="btn btn-outline-light w-100 rounded-pill shadow-sm py-2 view-details" data-uuid="${c.uuid}">
                                <i class="bi bi-info-circle me-2"></i>View Details
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    grid.append(card);
  });
}

$(document).ready(function () {
  loadCompanies();

  $("#companyGrid").on("click", ".view-details", function () {
    const uuid = $(this).data("uuid");
    $("#pageLoader").show();

    $.ajax({
      url: "../../../process/coordinators/get_company_full_details",
      type: "POST",
      data: { uuid: uuid },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          const companyData = response.company;
          const c = companyData.company;
          const students = response.students;

          $("#detCompanyName").text(c.name);
          $("#detCompanyIndustry").text(c.industry || "General Industry");
          $("#detCompanyAddress").text(`${c.address}, ${c.city}`);

          if (c.website && c.website !== "—") {
            $("#detCompanyWebsite")
              .text(c.website)
              .attr("href", c.website.startsWith("http") ? c.website : "https://" + c.website);
          } else {
            $("#detCompanyWebsite").text("---").attr("href", "#");
          }

          $("#detCompanySetup").text(c.work_setup ? c.work_setup.toUpperCase() : "N/A");

          const progContainer = $("#detCompanyPrograms");
          progContainer.empty();
          const programs = companyData.accepted_programs || [];
          if (programs.length > 0) {
            programs.forEach((p) => {
              progContainer.append(
                `<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 fw-normal">${p.code}</span>`,
              );
            });
          } else {
            progContainer.append('<small class="text-white-50 italic">No programs listed</small>');
          }

          const supervisorContainer = $("#detSupervisorList");
          supervisorContainer.empty();
          const supervisors = companyData.supervisors || [];
          if (supervisors.length > 0) {
            supervisors.forEach((s) => {
              supervisorContainer.append(`
                <div>
                  <div class="text-white small fw-medium">${s.first_name} ${s.last_name}</div>
                  <div class="text-white-50 x-small">${s.position || "Supervisor"} • ${s.department || "N/A"}</div>
                </div>
              `);
            });
          } else {
            supervisorContainer.append('<div class="text-white-50 small italic">No supervisors assigned</div>');
          }

          const contactContainer = $("#detContactInfo");
          contactContainer.empty();
          const contacts = companyData.contacts || [];
          if (contacts.length > 0) {
            contacts.forEach((con) => {
              contactContainer.append(`
                <div>
                  <div class="fw-bold text-white small">${con.name}</div>
                  <div class="text-white-50 x-small">${con.position || "Contact Person"}</div>
                  <div class="text-info x-small mt-1"><i class="bi bi-envelope me-1"></i>${con.email}</div>
                </div>
              `);
            });
          } else {
            contactContainer.append('<div class="text-white-50 small italic">No contact info</div>');
          }

          const moaContainer = $("#detMoaStatus");
          moaContainer.empty();
          const moaStatus = c.moa_status || "none";
          const moaExpiry = c.moa_expiry || "N/A";

          if (moaStatus !== "none") {
            const statusClass =
              moaStatus === "expired" ? "text-danger"
              : moaStatus === "expiring" ? "text-warning"
              : "text-success";
            moaContainer.append(`
              <div class="d-flex align-items-center gap-2">
                <i class="bi bi-check-circle-fill ${statusClass}"></i>
                <div>
                  <div class="text-white small">${moaStatus.toUpperCase()} MOA</div>
                  <div class="text-white-50 x-small">Expires: ${moaExpiry}</div>
                </div>
              </div>
            `);
          } else {
            moaContainer.append('<div class="text-white-50 small">No MOA on file</div>');
          }

          $("#detStudentCount").text(students.length);
          const studentContainer = $("#detStudentList");
          studentContainer.empty();

          $("#currentCompanyUuid").val(c.uuid);

          // Populate Slots Display and Input
          const slotsFilled = parseInt(companyData.filled_slots || 0);
          const totalSlots = parseInt(companyData.total_slots || 0);
          const remainingSlots = Math.max(0, totalSlots - slotsFilled);
          $("#detSlotsDisplay").text(`${slotsFilled} / ${totalSlots} Slots (${remainingSlots} Remaining)`);
          $("#detSlotsInput").val(totalSlots);

          // Populate Geofencing limits
          const latitude = c.latitude !== null ? c.latitude : null;
          const longitude = c.longitude !== null ? c.longitude : null;
          const geofenceRadius = c.geofence_radius !== null ? c.geofence_radius : 100;
          $("#detLatitudeDisplay").text(latitude !== null ? latitude : "Not Set");
          $("#detLongitudeDisplay").text(longitude !== null ? longitude : "Not Set");
          $("#detRadiusDisplay").text(latitude !== null ? `${geofenceRadius} meters` : "Not Set");

          $("#detLatitudeInput").val(latitude !== null ? latitude : "");
          $("#detLongitudeInput").val(longitude !== null ? longitude : "");
          $("#detRadiusInput").val(geofenceRadius);

          if (students.length > 0) {
            students.forEach((s) => {
              const profileImg =
                s.profile_name ?
                  `../../../Assets/Images/profiles/${s.profile_name}`
                : `https://placehold.co/36x36/483a0f/c6983d/png?text=${s.first_name.charAt(0)}${s.last_name.charAt(0)}&font=poppins`;

              studentContainer.append(`
                <div class="list-group-item bg-transparent border-0 border-bottom border-white border-opacity-10 py-3 d-flex align-items-center justify-content-between px-3">
                  <div class="d-flex align-items-center gap-3 min-w-0">
                    <img src="${profileImg}" class="rounded-circle border border-2 border-primary-subtle shadow-sm flex-shrink-0" style="width: 36px; height: 36px; object-fit: cover;">
                    <div class="vstack min-w-0">
                      <div class="text-white fw-semibold mb-0 text-truncate">${s.first_name} ${s.last_name}</div>
                      <div class="text-white-50 x-small text-truncate">${s.program_code}</div>
                    </div>
                  </div>
                  <a href="./viewStudentProfile?uuid=${s.profile_uuid}&from=companies" class="btn btn-sm btn-outline-primary rounded-pill px-3 x-small flex-shrink-0 ms-2">View Profile</a>
                </div>
              `);
            });
          } else {
            studentContainer.append(
              '<div class="p-4 text-center text-white-50 small italic">No students placed here yet.</div>',
            );
          }

          $("#companyDetailsModal").modal("show");
        } else {
          Errors(response.message);
        }
      },
      error: function () {
        Errors("Failed to fetch company details.");
      },
      complete: function () {
        $("#pageLoader").fadeOut();
      },
    });
  });

  $("#uploadMoABtn").on("click", function () {
    $("#moaFileInput").click();
  });

  $("#detUpdateSlotsBtn").on("click", function () {
    const companyUuid = $("#currentCompanyUuid").val();
    const totalSlots = parseInt($("#detSlotsInput").val() || 0);
    const csrfToken = $("#csrfToken").val();

    if (totalSlots < 0) {
      ToastVersion(swalTheme, "Total slots must be a positive number.", "warning", 3000, "top-end", "8");
      return;
    }

    $("#pageLoader").show();

    $.ajax({
      url: "../../../process/coordinators/update_company_slots",
      type: "POST",
      data: {
        company_uuid: companyUuid,
        total_slots: totalSlots,
        csrf_token: csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          loadCompanies();
          $("#companyGrid").find(`[data-uuid="${companyUuid}"]`).trigger("click");
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred during slots update.", "error", 3000, "top-end", "8");
      },
      complete: function () {
        $("#pageLoader").fadeOut();
      },
    });
  });

  $("#detUpdateGeofenceBtn").on("click", function () {
    const companyUuid = $("#currentCompanyUuid").val();
    const latitude = $("#detLatitudeInput").val();
    const longitude = $("#detLongitudeInput").val();
    const geofenceRadius = parseInt($("#detRadiusInput").val() || 100);
    const csrfToken = $("#csrfToken").val();

    if (geofenceRadius < 10) {
      ToastVersion(swalTheme, "Geofence radius must be at least 10 meters.", "warning", 3000, "top-end", "8");
      return;
    }

    $("#pageLoader").show();

    $.ajax({
      url: "../../../process/coordinators/update_company_geofence",
      type: "POST",
      data: {
        company_uuid: companyUuid,
        latitude: latitude,
        longitude: longitude,
        geofence_radius: geofenceRadius,
        csrf_token: csrfToken,
      },
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, response.message, "success", 3000, "top-end", "8");
          loadCompanies();
          $("#companyGrid").find(`[data-uuid="${companyUuid}"]`).trigger("click");
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred during geofence limits update.", "error", 3000, "top-end", "8");
      },
      complete: function () {
        $("#pageLoader").fadeOut();
      },
    });
  });

  $("#moaFileInput").on("change", function () {
    const file = this.files[0];
    const companyUuid = $("#currentCompanyUuid").val();
    const csrfToken = $("#csrfToken").val();

    if (!file) return;

    if (file.type !== "application/pdf") {
      ToastVersion(swalTheme, "Only PDF files are allowed.", "warning", 3000, "top-end", "8");
      $(this).val("");
      return;
    }

    if (file.size > 5 * 1024 * 1024) {
      ToastVersion(swalTheme, "File size too large. Max 5MB allowed.", "warning", 3000, "top-end", "8");
      $(this).val("");
      return;
    }

    const formData = new FormData();
    formData.append("document_file", file);
    formData.append("company_uuid", companyUuid);
    formData.append("doc_type", "moa");
    formData.append("csrf_token", csrfToken);

    $("#pageLoader").show();

    $.ajax({
      url: "../../../process/companies/upload_document",
      type: "POST",
      data: formData,
      processData: false,
      contentType: false,
      success: function (response) {
        if (response.status === "success") {
          ToastVersion(swalTheme, "MOA uploaded successfully!", "success", 3000, "top-end", "8");
          $("#companyGrid").find(`[data-uuid="${companyUuid}"]`).trigger("click");
        } else {
          ToastVersion(swalTheme, response.message, "error", 3000, "top-end", "8");
        }
      },
      error: function () {
        ToastVersion(swalTheme, "An error occurred during upload.", "error", 3000, "top-end", "8");
      },
      complete: function () {
        $("#pageLoader").fadeOut();
        $("#moaFileInput").val("");
      },
    });
  });

  $("#refreshBtn").on("click", function () {
    $("#pageLoader").show();
    loadCompanies();
  });

  $("#searchInput").on("input", function () {
    renderCompanies(window.allCompanies || []);
  });

  $("#statusFilter").on("change", function () {
    renderCompanies(window.allCompanies || []);
  });

  $("#clearFiltersBtn").on("click", function () {
    $("#searchInput").val("");
    $("#statusFilter").val("");
    renderCompanies(window.allCompanies || []);
  });
});
