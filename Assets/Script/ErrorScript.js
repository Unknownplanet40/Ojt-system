import { MatchsystemThemes, BGcircleTheme } from "./SystemTheme.js";
MatchsystemThemes(true);
BGcircleTheme(true, "danger", "slow");

$("#error-page").addClass("d-none");

function getErrorMessage(code) {
  const errorMessages = {
    400: "Bad Request",
    401: "Authorization Required",
    402: "Payment Required (not used yet)",
    403: "Forbidden",
    404: "Not Found",
    405: "Method Not Allowed",
    406: "Not Acceptable (encoding)",
    407: "Proxy Authentication Required",
    408: "Request Timed Out",
    409: "Conflicting Request",
    410: "Gone",
    411: "Content Length Required",
    412: "Precondition Failed",
    413: "Request Entity Too Long",
    414: "Request URI Too Long",
    415: "Unsupported Media Type.",
    500: "Internal Server Error",
    501: "Not Implemented",
    502: "Bad Gateway",
    503: "Service Unavailable",
    504: "Gateway Timeout",
    505: "HTTP Version Not Supported",
    CE00: "Server Configuration Required",
    CE01: "Dependency Error",
    CE02: "Ratchet Dependency Error",
    CE03: "PHPMailer Dependency Error",
  };
  return errorMessages[code] || "Unknown Error";
}

function getErrorDescription(code) {
  const errorDescriptions = {
    400: "Your request was malformed or invalid. Please check your input and try again.",
    401: "You need to log in to access this resource.",
    402: "Payment is required to access this resource.",
    403: "You do not have permission to access this resource.",
    404: "The page or resource you are looking for does not exist.",
    405: "The action you are trying to perform is not allowed on this resource.",
    406: "The server cannot provide the content format you requested.",
    407: "Proxy authentication is required to access this resource.",
    408: "Your request took too long to complete. Please try again.",
    409: "Your request conflicts with the current state of the resource.",
    410: "This resource has been permanently removed and is no longer available.",
    411: "The server requires content length information for this request.",
    412: "Your request preconditions were not met by the server.",
    413: "Your request is too large. Please reduce the file size and try again.",
    414: "The URL you provided is too long for the server to process.",
    415: "The file format you uploaded is not supported.",
    500: "Something went wrong on the server. Please try again later.",
    501: "This functionality is not yet implemented.",
    502: "The server received an invalid response from an upstream server.",
    503: "The server is temporarily unavailable. Please try again later.",
    504: "The server took too long to respond. Please try again.",
    505: "This HTTP version is not supported by the server.",
    CE00: "mod_rewrite is disabled. Required for clean URLs. Enable it in httpd.conf and restart Apache.",
    CE01: "A required dependency is missing. Please read the documentation and ensure all dependencies are installed correctly.",
    CE02: "Ratchet dependency missing. Reinstall dependencies.",
    CE03: "PHPMailer dependency missing. Reinstall dependencies.",

  };
  return errorDescriptions[code] || "An unexpected error has occurred.";
}

$(document).ready(function () {
  $("#error-page").removeClass("d-none");
  $("#loading-spinner").addClass("d-none");

  let errorCode = new URLSearchParams(window.location.search).get("error") || 500;
  BGcircleTheme(true, "danger", "fast");
  if (typeof ServerStatus !== "undefined") {
    errorCode = ServerStatus;
    BGcircleTheme(true, "cv", "fast");
  }

  $("#status").text(`${errorCode} ${getErrorMessage(errorCode)}`);
  $("#description").text(getErrorDescription(errorCode));
  document.title = `${errorCode} ${getErrorMessage(errorCode)}`;

  if (document.referrer && document.referrer !== window.location.href) {
    $("#back-button").html('<i class="bi bi-arrow-left"></i> Go Back');
  } else {
    $("#back-button").html('<i class="bi bi-house-door"></i> Home');
  }

  $("#back-button").on("click", function () {
    if (document.referrer && document.referrer !== window.location.href) {
      window.history.back();
    } else {
      window.location.href = "../../";
    }
  })
});
