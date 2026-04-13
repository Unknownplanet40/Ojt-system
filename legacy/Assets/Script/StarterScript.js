import { animate } from "../../libs/animejs/bundles/anime.esm.js";
import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "primary", "fast");

AOS.init();

$("#pageLoader").fadeIn(2000);

$(document).ready(function () {
  $("#windowWidth").text($(window).width());

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
      $("#windowWidth").text($(window).width());
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

  if ("serviceWorker" in navigator) {
    navigator.serviceWorker
      .register("../../Assets/Script/serviceworker.js")
      .then((registration) => {
        let updateNotified = false;

        const notifyUpdateAvailable = async () => {
          if (updateNotified) return;
          updateNotified = true;

          if ("setAppBadge" in navigator) {
            try {
              await navigator.setAppBadge(1);
            } catch (error) {
              console.warn("[PWA] setAppBadge failed:", error);
            }
          }

          ToastVersion(swalTheme, "New update available. Refresh to use the latest version.", "info", 6000, "top-end");
        };

        if (registration.waiting) {
          notifyUpdateAvailable();
        }

        registration.addEventListener("updatefound", () => {
          const newWorker = registration.installing;
          if (!newWorker) return;

          newWorker.addEventListener("statechange", () => {
            if (newWorker.state === "installed" && navigator.serviceWorker.controller) {
              notifyUpdateAvailable();
            }
          });
        });

        navigator.serviceWorker.addEventListener("message", (event) => {
          if (event.data?.type === "UPDATE_AVAILABLE") {
            notifyUpdateAvailable();
          }
        });

        setInterval(() => {
          registration.update().catch((error) => {
            console.warn("[PWA] Service worker update check failed:", error);
          });
        }, 60 * 1000);
      })
      .catch((error) => {
        console.error("[PWA] Service worker registration failed:", error);
      });
  }
});
