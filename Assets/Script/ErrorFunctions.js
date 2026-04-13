import { ToastVersion, ModalVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

export function Errors(xhr, status, error) {
  // xhr: The XMLHttpRequest object that was used to make the request.
  // status: A string describing the type of error that occurred. Possible values include "timeout", "error", "abort", and "parsererror".
  // error: An optional exception object, if one occurred.

  if (xhr.status === 403) {
    ModalVersion(swalTheme, "Access Denied", "Your session may have expired or you do not have permission to access this resource. Please refresh the page and try again.", "error", 0, "center");
    return;
  }

  if (xhr.status >= 500) {
    ModalVersion(swalTheme, "Server Error", "An unexpected error occurred on the server. Please try again later.", "error", 0, "center");
    return;
  }

  if (status === "timeout") {
    ToastVersion(swalTheme, "The request timed out. Please check your internet connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "abort") {
    ToastVersion(swalTheme, "The request was aborted. Please try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "network") {
    ToastVersion(swalTheme, "A network error occurred. Please check your connection and try again.", "error", 3000, "top-end");
    return;
  }

  if (status === "parsererror") {
    ModalVersion(swalTheme, "Response Error", "The server returned an unexpected response. Please try again later.", "error", 0, "center");
    return;
  }

  if (error) {
    ModalVersion(swalTheme, "Error", "An unexpected error occurred: " + error, "error", 0, "center");
    return;
  }

  ModalVersion(swalTheme, "Unknown Error", "An unknown error occurred. Please try again later.", "error", 0, "center");
  console.error("An unknown error occurred:", { xhr, status, error });
}