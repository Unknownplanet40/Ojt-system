import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

export function Errors(xhr, status, error) {
  // xhr: The XMLHttpRequest object that was used to make the request.
  // status: A string describing the type of error that occurred. Possible values include "timeout", "error", "abort", and "parsererror".
  // error: An optional exception object, if one occurred.

  const payload = xhr?.responseJSON || null;

  if (payload?.code === "PROFILE_INCOMPLETE" && payload?.redirect_url) {
    ToastVersion(swalTheme, payload.message || "Complete your profile setup first.", "warning", 2000, "top-end");
    setTimeout(() => {
      window.location.href = payload.redirect_url;
    }, 250);
    return;
  }

  if (xhr.status === 403) {
    ModalVersion(swalTheme, "Access Denied", "Your session may have expired, or you do not have permission to access this resource. Please refresh the page and try again.", "error", 0, "center");
    return;
  }

  if (xhr.status === 404) {
    ModalVersion(swalTheme, "Not Found", "The requested resource could not be found on the server. Please check the URL or contact support if the problem persists.", "error", 0, "center");
    return;
  }

  if (xhr.status >= 500) {
    ModalVersion(swalTheme, "Server Error", "The server is currently experiencing issues. Please try again later or contact support if the problem persists.", "error", 0, "center");
    return;
  }

  if (status === "timeout") {
    ToastVersion(swalTheme, "Request Timeout", "The request took too long to complete. Please check your connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "abort") {
    ToastVersion(swalTheme, "Request Cancelled", "Your request was cancelled. Please try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "network") {
    ToastVersion(swalTheme, "Network Error", "Unable to establish a connection. Please check your internet connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "parsererror") {
    ModalVersion(swalTheme, "Invalid Response", "The server response was invalid. Please try again or contact support.", "error", 0, "center");
    return;
  }

  if (error) {
    ModalVersion(swalTheme, "Error", "An error occurred: " + error, "error", 0, "center");
    return;
  }

  ModalVersion(swalTheme, "Unknown Error", "An unexpected error occurred. Please try again or contact support.", "error", 0, "center");
  console.error("An unknown error occurred:", { xhr, status, error });
}
