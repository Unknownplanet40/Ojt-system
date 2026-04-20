import { ToastVersion, ModalVersion } from "../CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "../SystemTheme.js";
import { Errors } from "../ErrorFunctions.js";


const driver = window.driver.js.driver;
MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true);

const csrfToken = $('meta[name="csrf-token"]').attr("content") || "";
const userRole = $('meta[name="user-Role"]').attr("content") || "";

if (!csrfToken || !userRole || userRole !== "coordinator") {
  window.location.href = "../../../Src/Pages/Login";
}

function viewCoordinatorProfile() {
}

$(document).ready(function () {
  $("#editprofileBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/Coordinator/Coordinator_Profile?action=edit";
  });

  $("#changepasswordBtn").on("click", function () {
    window.location.href = "../../../Src/Pages/ChangePassword";
  });
});
