import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "default", "fast");
let letPageLoad = true;

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userUUID = $('meta[name="user-UUID"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";
const Onlypage = $("body").data("only") || "";

if (!csrfToken || !userUUID || !userRole || userRole !== "admin") {
  window.location.href = "../../../Src/Pages/Login";
  letPageLoad = false;
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
        const activeBatch = response.activeBatch;
        $("#activebatchthissemester").text(activeBatch ? `${activeBatch.label}` : "No active batch this semester");
        $("#activebatchthissemester").attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");
        $("#StudactiveBatch")
          .val(activeBatch ? `${activeBatch.label}` : "No active batch this semester")
          .attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");
        $("#editActiveBatch")
          .val(activeBatch ? `${activeBatch.label}` : "No active batch this semester")
          .attr("data-batch-uuid", activeBatch ? activeBatch.uuid : "");

        if (profile.user_uuid !== userUUID) {
          ToastVersion(swalTheme, "Profile data mismatch. Please refresh the page.", "error", 3000, "top-end");
          SignOut();
          return;
        }

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

function DashboardEsentialElements() {
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
    $("#mainContent").fadeIn(1000, function () {
      $(this).removeClass("d-none");
    });
  });

  $("#signOutBtn").on("click", function (e) {
    e.preventDefault();
    SignOut();
  });
}

function tableDropdown() {
  const MENU_Z_INDEX = 1030;
  const VIEWPORT_PADDING = 8;

  const ensureDropdownLink = (toggleBtn, menu) => {
    if (!toggleBtn.length || !menu.length) return;

    let dropdownId = toggleBtn.attr("data-dropdown-id") || menu.attr("data-dropdown-id");
    if (!dropdownId) {
      dropdownId = `dd-${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
    }

    toggleBtn.attr("data-dropdown-id", dropdownId);
    menu.attr("data-dropdown-id", dropdownId);
  };

  const resolveMenuForToggle = (toggleBtn) => {
    let menu = toggleBtn.next(".customDropdown");
    if (menu.length) {
      ensureDropdownLink(toggleBtn, menu);
      return menu;
    }

    const dropdownId = toggleBtn.attr("data-dropdown-id");
    if (!dropdownId) return $();

    menu = $(`.customDropdown[data-dropdown-id="${dropdownId}"]`).first();
    return menu;
  };

  const restoreMenuToOrigin = (menu) => {
    const placeholder = menu.data("dropdown-placeholder");
    if (placeholder && placeholder.length) {
      placeholder.before(menu);
      placeholder.remove();
    } else {
      const originParent = menu.data("dropdown-origin-parent");
      if (originParent && originParent.length) {
        originParent.append(menu);
      }
    }

    menu
      .css({
        position: "",
        top: "",
        left: "",
        right: "",
        bottom: "",
        zIndex: "",
        visibility: "",
        display: "none",
      })
      .removeData("dropdown-placeholder")
      .removeData("dropdown-origin-parent");
  };

  const positionMenuNearButton = (menu, toggleBtn) => {
    const btnRect = toggleBtn[0].getBoundingClientRect();
    const menuHeight = menu.outerHeight() || 0;
    const menuWidth = menu.outerWidth() || 170;

    const spaceBelow = window.innerHeight - btnRect.bottom;
    const spaceAbove = btnRect.top;
    const openUp = spaceBelow < menuHeight + 8 && spaceAbove >= spaceBelow;

    const top = openUp ? Math.max(VIEWPORT_PADDING, btnRect.top - menuHeight - 4) : Math.min(window.innerHeight - menuHeight - VIEWPORT_PADDING, btnRect.bottom + 4);

    const left = Math.min(window.innerWidth - menuWidth - VIEWPORT_PADDING, Math.max(VIEWPORT_PADDING, btnRect.right - menuWidth));

    menu.css({
      position: "fixed",
      top: `${top}px`,
      left: `${left}px`,
      right: "auto",
      bottom: "auto",
      zIndex: String(MENU_Z_INDEX),
      visibility: "visible",
      display: "block",
    });
  };

  const resetDropdownLayering = () => {
    $(".customDropdown").each(function () {
      restoreMenuToOrigin($(this));
    });

    $(".customDropdown").closest(".table-responsive").css({ overflowY: "" });
    $(".customDropdown").closest("td, th").css({ position: "", zIndex: "" });
  };

  $(document).on("click", function (e) {
    const target = $(e.target);
    const toggleBtn = target.closest('[data-toggle="dropdown"]');
    const dropdownItem = target.closest(".dropdown-item");

    if (toggleBtn.length) {
      const menu = resolveMenuForToggle(toggleBtn);
      if (!menu.length) {
        resetDropdownLayering();
        return;
      }

      const parentCell = toggleBtn.closest("td, th");
      const responsiveHost = toggleBtn.closest(".table-responsive");

      // reset all previous open dropdown layers first
      $(".customDropdown").not(menu).hide().closest("td, th").css({ position: "", zIndex: "" });

      const willOpen = !menu.is(":visible");
      if (!willOpen) {
        resetDropdownLayering();
        return;
      }

      if (responsiveHost.length) {
        // prevent clipping inside Bootstrap table-responsive wrappers
        responsiveHost.css({ overflowY: "visible" });
      }

      // elevate current table cell above neighboring rows
      parentCell.css({ position: "relative", zIndex: "20" });

      if (!menu.data("dropdown-origin-parent")) {
        menu.data("dropdown-origin-parent", menu.parent());
      }

      if (!menu.data("dropdown-placeholder")) {
        const placeholder = $('<span class="d-none dropdown-menu-placeholder"></span>');
        menu.after(placeholder);
        menu.data("dropdown-placeholder", placeholder);
      }

      $("body").append(menu);
      positionMenuNearButton(menu, toggleBtn);
    } else if (dropdownItem.length) {
      resetDropdownLayering();
    } else {
      resetDropdownLayering();
    }
  });
}

$(document).ready(function () {
  if (!letPageLoad) return;
  
  $("#pageLoader").fadeOut(500, function () {
    $(this).remove();
    $("#PageMainContent").fadeIn(500);
  });

  fetchProfile();
  DashboardEsentialElements();
  tableDropdown();

  if (Onlypage === "AdminDashboard") {
    $("#quickCreateBatch").on("click", function (e) {
      e.preventDefault();
      window.location.href = "../../../Src/Pages/Admin/Batches?action=create";
    });
  }
});
