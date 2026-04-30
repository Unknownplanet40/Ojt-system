import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userUUID = $('meta[name="user-UUID"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";
const Onlypage = $("body").data("only") || "";

if (!csrfToken || !userUUID || !userRole || userRole !== "student") {
  window.location.href = "../../../Src/Pages/Login";
}

function DashboardEsentialElements(mainContentSelector = "#PageMainContent") {
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
  });

  $("#navbarSideCollapse").on("click", function () {
    $(".offcanvas-collapse").toggleClass("open");
    if ($("#navbarSideCollapse i").hasClass("bi-list")) {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-list").addClass("bi-x").fadeIn(200);
      });
    } else {
      $("#navbarSideCollapse i").fadeOut(200, function () {
        $(this).removeClass("bi-x").addClass("bi-list").fadeIn(200);
      });
    }
  });

  $(window).on("resize", function () {
    if ($(".offcanvas-collapse").hasClass("open")) {
      $(".offcanvas-collapse").removeClass("open");
      $("#navbarSideCollapse i").removeClass("bi-x").addClass("bi-list");
    }
    if ($(window).width() < 360) {
      window.resizeTo(360, 800);
    }
  });

  $(function () {
    $(".nav-link").on("click", function () {
      if (!$(this).hasClass("dropdown-toggle")) {
        $(".nav-link").removeClass("active");
        $(this).addClass("active");
      }
    });
  });

  $("#pageLoader").fadeOut(1000, function () {
    $(this).remove();
    $(mainContentSelector).fadeIn(1000, function () {
      $(this).removeClass("d-none");
    });
  });

  $("#signOutBtn").on("click", function () {
    SignOut();
  });
}

function fetchProfile() {
  $.ajax({
    url: "../../../process/profile/get_profile",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    success: function (response) {
      if (response.status === "success") {
        const profile = response.profile;
        if (!profile.profile_name) {
          const initials = profile.initials || "NA";
          $("#navProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
          $("#dropdownProfilePhoto").attr("src", `https://placehold.co/64x64/483a0f/c6983d/png?text=${initials}&font=poppins`);
        } else {
          $("#navProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
          $("#dropdownProfilePhoto").attr("src", "../../../Assets/Images/profiles/" + profile.profile_name);
        }

        $("#userName").text(profile.first_name + " " + profile.last_name);
        $("#welcomeUserName").text(profile.first_name);
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },

    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function SignOut() {
  $.ajax({
    url: "../../../process/auth/logout",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    beforeSend: function () {
      ModalVersion(swalTheme, "Signing Out", "Please wait while we sign you out...", "info", 0, "center");
    },
    success: function (response) {
      if (response.status === "success") {
        Swal.close();
        window.location.href = response.redirect_url;
      } else {
        ToastVersion(swalTheme, response.message, "error", 3000, "top-end");
      }
    },
    error: function (xhr, status, error) {
      Errors(xhr, status, error);
    },
  });
}

function fetchRequirements() {
  $.ajax({
    url: "../../../process/requirements/get_requirements",
    method: "POST",
    dataType: "json",
    data: {
      csrf_token: csrfToken,
    },
    success: function (response) {
      if (response.status === "success") {
        const requirements = response.requirements;
        const approvedCount = response.approved_count;
        const total = response.total;

        // Update Progress Bar
        const percent = total ? (approvedCount / total) * 100 : 0;
        $("#reqProgressBar").css("width", percent + "%").attr("aria-valuenow", percent);
        $("#reqApprovedCount").text(approvedCount);
        $("#reqTotalCount").text(`of ${total} approved`);

        // Update Requirements List
        const list = $("#requirementsList");
        list.empty();

        const statusStyles = {
          approved: { badge: "bg-success-subtle text-success-emphasis", dot: "text-success", label: "Approved" },
          returned: { badge: "bg-danger-subtle text-danger-emphasis", dot: "text-danger", label: "Returned" },
          submitted: { badge: "bg-primary-subtle text-primary-emphasis", dot: "text-primary", label: "Submitted" },
          not_submitted: { badge: "bg-secondary-subtle text-secondary-emphasis", dot: "text-secondary", label: "Not submitted" },
        };

        requirements.forEach((req) => {
          const style = statusStyles[req.status] || statusStyles.not_submitted;
          const html = `
            <li class="list-group-item bg-transparent px-2 px-sm-3 py-3 border-secondary-subtle">
                <div class="d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-2 w-100">
                    <div class="d-flex align-items-center gap-2">
                        <span class="${style.dot}" style="font-size: 0.70rem;">&#11044;</span>
                        <span class="fw-semibold">${req.req_label}</span>
                    </div>
                    <span class="badge rounded-pill px-3 py-2 ms-sm-auto ${style.badge}">
                        ${req.status_label || style.label}
                    </span>
                </div>
            </li>
          `;
          list.append(html);
        });
      }
    },
    error: function (xhr, status, error) {
      console.error("Failed to fetch requirements", error);
    },
  });
}

$(document).ready(function () {
  DashboardEsentialElements();
  fetchProfile();
  fetchRequirements();

  $("#dashboardRefreshBtn").on("click", function () {
    fetchProfile();
    fetchRequirements();
    ToastVersion(swalTheme, "Dashboard updated", "success", 2000, "top-end");
  });
});
