<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Companies";

?>

<!doctype html>
<html lang="en">

<head>
    <?php require_once "pagehead.php" ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/AdminDashboard.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/CompaniesScripts.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>
    <div class="w-100 min-vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75"
        id="pageLoader">
        <div class="d-flex flex-column align-items-center">
            <span class="loader"></span>
        </div>
    </div>

    <div class="container">
        <div class="modal fade" id="NewCompanyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4 p-md-5">
                        <!-- Header Section -->
                        <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                            <h4 class="mb-2 fw-bold text-white">Add Company</h4>
                            <p class="text-muted small mb-0">Register a new OJT partner company</p>
                        </div>

                        <!-- Basic Information Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Basic Information</h6>
                                    <small class="text-muted d-block">Register the general details of the
                                        company</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="companyName" class="form-label fw-medium small">Company
                                                Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="companyName"
                                                placeholder="e.g. Acme Corporation" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companyindustry"
                                                class="form-label fw-medium small">Industry</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="companyindustry" placeholder="e.g. IT Services & Consulting">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companyemail" class="form-label fw-medium small">Company
                                                Email</label>
                                            <input type="email" class="form-control border rounded-3" id="companyemail"
                                                placeholder="e.g. info@acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companycontact" class="form-label fw-medium small">Contact
                                                Number</label>
                                            <input type="tel" class="form-control border rounded-3" id="companycontact"
                                                placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companyaddress" class="form-label fw-medium small">Company
                                                Address</label>
                                            <input type="text" class="form-control border rounded-3" id="companyaddress"
                                                placeholder="e.g. 123 Main St">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companycity" class="form-label fw-medium small">City</label>
                                            <input type="text" class="form-control border rounded-3" id="companycity"
                                                placeholder="e.g. Taguig City">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companywebsite" class="form-label fw-medium small">Company
                                                Website</label>
                                            <input type="url" class="form-control border rounded-3" id="companywebsite"
                                                placeholder="e.g. https://www.acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companyworksetup" class="form-label fw-medium small">Preferred
                                                Work Setup<span class="text-danger ms-1">*</span></label>
                                            <select class="form-select border rounded-3" id="companyworksetup" required>
                                                <option class="CustomOption" value="" selected disabled>Select work
                                                    setup</option>
                                                <option class="CustomOption" value="on-site">On-site</option>
                                                <option class="CustomOption" value="remote">Remote</option>
                                                <option class="CustomOption" value="hybrid">Hybrid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accreditation & Slots Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Accreditation & Slots
                                    </h6>
                                    <small class="text-muted d-block">Status and available slots for the current
                                        batch</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="companyaccreditationstatus"
                                                class="form-label fw-medium small">Accreditation Status<span
                                                    class="text-danger ms-1">*</span></label>
                                            <select class="form-select border rounded-3" id="companyaccreditationstatus"
                                                required>
                                                <option value="" selected disabled>Select accreditation status</option>
                                                <option class="CustomOption" value="active">Active</option>
                                                <option class="CustomOption" value="pending">Pending</option>
                                                <option class="CustomOption" value="expired">Expired</option>
                                                <option class="CustomOption" value="blacklisted">Blacklisted</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companytotalslots" class="form-label fw-medium small">Total OJT
                                                Slots <span class="text-muted fw-normal">(current batch)</span><span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="number" class="form-control border rounded-3"
                                                id="companytotalslots" placeholder="e.g. 10" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-md-12 d-none" id="newBlocklistedReasonContainer">
                                        <script>
                                            $('#companyaccreditationstatus').on('change', function() {
                                                if ($(this).val() === 'blacklisted') {
                                                    $('#newBlocklistedReasonContainer').removeClass('d-none');
                                                    $('#companyblocklistedreason').attr('required', true);
                                                } else {
                                                    $('#newBlocklistedReasonContainer').addClass('d-none');
                                                    $('#companyblocklistedreason').removeAttr('required');
                                                }
                                            });
                                        </script>
                                        <div>
                                            <label for="companyblocklistedreason"
                                                class="form-label fw-medium small">Blocklisted Reason</label>
                                            <textarea class="form-control border rounded-3"
                                                id="companyblocklistedreason" rows="3"
                                                placeholder="Provide reason for blocklisting (e.g. violation of agreement terms)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accepted Programs Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Accepted Programs</h6>
                                    <small class="text-muted d-block">Which courses this company accepts for OJT</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="programcontainer" id="acceptedProgramsContainer">
                                </div>
                            </div>
                        </div>

                        <!-- Primary Contact Person Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Primary Contact Person
                                    </h6>
                                    <small class="text-muted d-block">HR or OJT coordinator at the company</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="companycontactname" class="form-label fw-medium small">Contact
                                                Person Name</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="companycontactname" placeholder="e.g. Juan Dela Cruz">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companyposition"
                                                class="form-label fw-medium small">Position/Role</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="companyposition" placeholder="e.g. HR Manager">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companycontactemail" class="form-label fw-medium small">Contact
                                                Person Email</label>
                                            <input type="email" class="form-control border rounded-3"
                                                id="companycontactemail" placeholder="e.g. hr@example.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="companycontactnumber" class="form-label fw-medium small">Contact
                                                Person Number</label>
                                            <input type="tel" class="form-control border rounded-3"
                                                id="companycontactnumber" placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supervisor Account Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Supervisor Account</h6>
                                    <small class="text-muted d-block">This account will be linked to the company and used for coordinator assignments.</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="supervisorfirstname" class="form-label fw-medium small">Supervisor First Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="supervisorfirstname" placeholder="e.g. Juan" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="supervisorlastname" class="form-label fw-medium small">Supervisor Last Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="supervisorlastname" placeholder="e.g. Dela Cruz" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="supervisoremail" class="form-label fw-medium small">Supervisor Email<span class="text-danger ms-1">*</span></label>
                                            <input type="email" class="form-control border rounded-3" id="supervisoremail" placeholder="e.g. supervisor@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="supervisormobile" class="form-label fw-medium small">Supervisor Mobile<span class="text-danger ms-1">*</span></label>
                                            <input type="tel" class="form-control border rounded-3" id="supervisormobile" placeholder="e.g. 09XX XXX XXXX" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="supervisorposition" class="form-label fw-medium small">Supervisor Position<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="supervisorposition" placeholder="e.g. OJT Supervisor" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="supervisordepartment" class="form-label fw-medium small">Supervisor Department<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="supervisordepartment" placeholder="e.g. Human Resources" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="d-flex flex-column-reverse flex-sm-row gap-2 gap-sm-3 pt-3 border-top border-secondary border-opacity-25">
                            <button
                                class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3 flex-grow-1 flex-sm-grow-0" id="cancelNewCompanyBtn"
                                data-bs-dismiss="modal">Cancel</button>
                            <button
                                class="btn btn-sm btn-dark text-light px-4 py-2 rounded-3 border border-secondary flex-grow-1 flex-sm-grow-0"
                                id="saveCompanyBtn">Save Company</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="EditCompanyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" data-company-uuid="">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4 p-md-5">
                        <!-- Header Section -->
                        <div class="mb-4 pb-3 border-bottom border-secondary border-opacity-25">
                            <h4 class="mb-2 fw-bold text-white">Edit Company</h4>
                            <p class="text-muted small mb-0">Update the details of the selected company.</p>
                        </div>

                        <!-- Basic Information Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Basic Information</h6>
                                    <small class="text-muted d-block">Edit the general details of the company</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="EditcompanyName" class="form-label fw-medium small">Company
                                                Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="EditcompanyName" placeholder="e.g. Acme Corporation" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyindustry"
                                                class="form-label fw-medium small">Industry</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="Editcompanyindustry" placeholder="e.g. IT Services & Consulting">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyemail" class="form-label fw-medium small">Company
                                                Email</label>
                                            <input type="email" class="form-control border rounded-3"
                                                id="Editcompanyemail" placeholder="e.g. info@acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanycontact" class="form-label fw-medium small">Contact
                                                Number</label>
                                            <input type="tel" class="form-control border rounded-3"
                                                id="Editcompanycontact" placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyaddress" class="form-label fw-medium small">Company
                                                Address</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="Editcompanyaddress" placeholder="e.g. 123 Main St">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanycity" class="form-label fw-medium small">City</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="Editcompanycity" placeholder="e.g. Taguig City">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanywebsite" class="form-label fw-medium small">Company
                                                Website</label>
                                            <input type="url" class="form-control border rounded-3"
                                                id="Editcompanywebsite" placeholder="e.g. https://www.acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyworksetup"
                                                class="form-label fw-medium small">Preferred Work Setup<span
                                                    class="text-danger ms-1">*</span></label>
                                            <select class="form-select border rounded-3" id="Editcompanyworksetup"
                                                required>
                                                <option value="" selected hidden disabled>Select work setup</option>
                                                <option value="on-site">On-site</option>
                                                <option value="remote">Remote</option>
                                                <option value="hybrid">Hybrid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accreditation & Slots Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Accreditation & Slots
                                    </h6>
                                    <small class="text-muted d-block">Status and available slots for the current
                                        batch</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyaccreditationstatus"
                                                class="form-label fw-medium small">Accreditation Status<span
                                                    class="text-danger ms-1">*</span></label>
                                            <select class="form-select border rounded-3"
                                                id="Editcompanyaccreditationstatus" required>
                                                <option class="CustomOption" value="active">Active</option>
                                                <option class="CustomOption" value="pending">Pending</option>
                                                <option class="CustomOption" value="expired">Expired</option>
                                                <option class="CustomOption" value="blacklisted">Blacklisted</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanytotalslots" class="form-label fw-medium small">Total
                                                OJT Slots <span class="text-muted fw-normal">(current batch)</span><span
                                                    class="text-danger ms-1">*</span></label>
                                            <input type="number" class="form-control border rounded-3"
                                                id="Editcompanytotalslots" placeholder="e.g. 10" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12 d-none" id="BlocklistedReasonContainer">
                                        <script>
                                            $('#Editcompanyaccreditationstatus').on('change', function() {
                                                if ($(this).val() === 'blacklisted') {
                                                    $('#BlocklistedReasonContainer').removeClass('d-none');
                                                    $('#Editcompanyblocklistedreason').attr('required', true);
                                                } else {
                                                    $('#BlocklistedReasonContainer').addClass('d-none');
                                                    $('#Editcompanyblocklistedreason').removeAttr('required');
                                                }
                                            });
                                        </script>
                                        <div>
                                            <label for="Editcompanyblocklistedreason"
                                                class="form-label fw-medium small">Blocklisted Reason</label>
                                            <textarea class="form-control border rounded-3"
                                                id="Editcompanyblocklistedreason" rows="3"
                                                placeholder="Provide reason for blocklisting (e.g. violation of agreement terms)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accepted Programs Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Accepted Programs</h6>
                                    <small class="text-muted d-block">Which courses this company accepts for OJT</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="programcontainer" id="EditacceptedProgramsContainer">
                                </div>
                            </div>
                        </div>

                        <!-- Primary Contact Person Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden d-none"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Primary Contact Person
                                    </h6>
                                    <small class="text-muted d-block">HR or OJT coordinator at the company</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanycontactname"
                                                class="form-label fw-medium small">Contact Person Name</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="Editcompanycontactname" placeholder="e.g. Juan Dela Cruz">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanyposition"
                                                class="form-label fw-medium small">Position/Role</label>
                                            <input type="text" class="form-control border rounded-3"
                                                id="Editcompanyposition" placeholder="e.g. HR Manager">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanycontactemail"
                                                class="form-label fw-medium small">Contact Person Email</label>
                                            <input type="email" class="form-control border rounded-3"
                                                id="Editcompanycontactemail" placeholder="e.g. hr@example.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editcompanycontactnumber"
                                                class="form-label fw-medium small">Contact Person Number</label>
                                            <input type="tel" class="form-control border rounded-3"
                                                id="Editcompanycontactnumber" placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Supervisor Account Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-4 rounded-4 overflow-hidden"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-4">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Supervisor Account</h6>
                                    <small class="text-muted d-block">This supervisor will be linked to the company for coordinator assignment.</small>
                                </div>
                                <input type="hidden" id="EditsupervisorProfileUuid" value="">
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2 g-3">
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisorfirstname" class="form-label fw-medium small">Supervisor First Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="Editsupervisorfirstname" placeholder="e.g. Juan" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisorlastname" class="form-label fw-medium small">Supervisor Last Name<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="Editsupervisorlastname" placeholder="e.g. Dela Cruz" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisoremail" class="form-label fw-medium small">Supervisor Email<span class="text-danger ms-1">*</span></label>
                                            <input type="email" class="form-control border rounded-3" id="Editsupervisoremail" placeholder="e.g. supervisor@example.com" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisormobile" class="form-label fw-medium small">Supervisor Mobile<span class="text-danger ms-1">*</span></label>
                                            <input type="tel" class="form-control border rounded-3" id="Editsupervisormobile" placeholder="e.g. 09XX XXX XXXX" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisorposition" class="form-label fw-medium small">Supervisor Position<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="Editsupervisorposition" placeholder="e.g. OJT Supervisor" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div>
                                            <label for="Editsupervisordepartment" class="form-label fw-medium small">Supervisor Department<span class="text-danger ms-1">*</span></label>
                                            <input type="text" class="form-control border rounded-3" id="Editsupervisordepartment" placeholder="e.g. Human Resources" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div
                            class="d-flex flex-column-reverse flex-sm-row gap-2 gap-sm-3 pt-3 border-top border-secondary border-opacity-25">
                            <button
                                class="btn btn-sm btn-outline-secondary px-4 py-2 rounded-3 flex-grow-1 flex-sm-grow-0"
                                data-bs-dismiss="modal" id="cancelEditCompanyBtn">Cancel</button>
                            <button
                                class="btn btn-sm btn-dark text-light px-4 py-2 rounded-3 border border-secondary flex-grow-1 flex-sm-grow-0"
                                id="EditsaveCompanyBtn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ViewCompanyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down modal-dialog-scrollable">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4">
                        <!-- Header Section -->
                        <div class="sticky-top d-flex align-items-start justify-content-between gap-3 mb-4 p-3 border rounded-3 bg-blur-10 bg-semi-transparent"
                            style="top: 0; z-index: 10;">
                            <div class="flex-grow-1">
                                <h4 class="mb-1 fw-bold" id="viewCompanyName">Company Name</h4>
                                <p class="mb-0 text-muted small" id="viewCompanyIndustry">Industry</p>
                            </div>
                            <div class="d-flex gap-2 flex-shrink-0 my-3">
                                <button class="btn btn-sm btn-outline-primary text-nowrap" id="editCompanyBtn">
                                    <i class="bi bi-pencil-square me-1"></i>
                                    <span class="d-none d-sm-inline">Edit</span>
                                </button>
                                <button class="btn btn-sm btn-outline-primary text-nowrap" id="uploadMoABtn">
                                    <i class="bi bi-upload me-1"></i>
                                    <span class="d-none d-sm-inline">Upload MOA</span>
                                </button>
                            </div>
                        </div>

                        <!-- Main Content -->
                        <div class="row row-cols-1 row-cols-lg-2 g-3 mb-4">
                            <!-- Company Details Card -->
                            <div class="col">
                                <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 fw-600 text-uppercase small letter-spacing">Company Details</h6>
                                        <hr class="my-2 opacity-25">
                                        <div class="vstack gap-2">
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="bi bi-envelope flex-shrink-0 text-muted mt-1"
                                                    style="min-width: 20px;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <small class="text-muted d-block">Email</small>
                                                    <span class="d-block text-truncate"
                                                        id="viewCompanyEmail">Unknown</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="bi bi-telephone flex-shrink-0 text-muted mt-1"
                                                    style="min-width: 20px;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <small class="text-muted d-block">Contact Number</small>
                                                    <span class="d-block" id="viewCompanyContactNumber">Unknown</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="bi bi-geo-alt flex-shrink-0 text-muted mt-1"
                                                    style="min-width: 20px;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <small class="text-muted d-block">Address</small>
                                                    <span class="d-block" id="viewCompanyAddress">Unknown</span>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="bi bi-globe flex-shrink-0 text-muted mt-1"
                                                    style="min-width: 20px;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <small class="text-muted d-block">Website</small>
                                                    <a href="#" target="_blank" class="d-block text-truncate"
                                                        id="viewCompanyWebsite">Unknown</a>
                                                </div>
                                            </div>
                                            <div class="d-flex align-items-start gap-3">
                                                <i class="bi bi-check-circle flex-shrink-0 text-muted mt-1"
                                                    style="min-width: 20px;"></i>
                                                <div class="flex-grow-1 min-w-0">
                                                    <small class="text-muted d-block">Status</small>
                                                    <span class="badge rounded-pill mt-1" id="viewCompanyStatus"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Slot Summary Card -->
                            <div class="col">
                                <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <h6 class="mb-3 fw-600 text-uppercase small letter-spacing">Slot Summary</h6>
                                        <hr class="my-2 opacity-25">
                                        <div class="vstack gap-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="text-muted">Batch</small>
                                                <span class="fw-500" id="viewCompanyBatch">-</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="text-muted">Total Slots</small>
                                                <span class="fw-500" id="viewCompanyTotalSlots">-</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="text-muted">Filled</small>
                                                <span class="fw-500" id="viewCompanyFilledSlots">-</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="text-muted">Remaining</small>
                                                <span class="fw-500" id="viewCompanyRemainingSlots">-</span>
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between">
                                                <small class="text-muted">MOA Expiry</small>
                                                <span class="fw-500" id="viewCompanyMOAExpiry">-</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Blacklist Reason Card (conditionally shown) -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 mb-3 d-none"
                            id="viewBlacklistReasonCard" style="--blur-lvl: 0.50">
                            <div class="card-body p-3">
                                <h6 class="mb-3 fw-600 text-uppercase small letter-spacing text-danger">Blacklist Reason
                                </h6>
                                <hr class="my-2 opacity-25">
                                <p class="mb-0" id="viewBlacklistReasonText">-</p>
                            </div>
                        </div>

                        <!-- Primary Contact Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 mb-3"
                            style="--blur-lvl: 0.50">
                            <div class="card-body p-3">
                                <div class="hstack align-items-center justify-content-between mb-3">
                                    <h6 class="mb-0 fw-600 text-uppercase small letter-spacing">Primary Contact Person
                                    </h6>
                                    <small class="text-warning fw-medium" style="cursor: pointer;"
                                        data-bs-toggle="tooltip" data-bs-placement="top"
                                        title="For now this is just informational and cannot be edited. Will be editable in the future when we implement multiple contacts per company.">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Permanent contact details
                                    </small>
                                </div>
                                <hr class="my-2 opacity-25">
                                <div class="row row-cols-1 row-cols-sm-2 g-3">
                                    <div class="col">
                                        <small class="text-muted d-block mb-1">Name</small>
                                        <span class="d-block fw-500" id="viewContactName">-</span>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block mb-1">Position</small>
                                        <span class="d-block fw-500" id="viewContactPosition">-</span>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block mb-1">Email</small>
                                        <span class="d-block fw-500 text-truncate" id="viewContactEmail">-</span>
                                    </div>
                                    <div class="col">
                                        <small class="text-muted d-block mb-1">Contact Number</small>
                                        <span class="d-block fw-500" id="viewContactNumber">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 mb-3"
                            style="--blur-lvl: 0.50">
                            <div class="card-body p-3">
                                <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Documents</h6>
                                <small class="text-muted d-block mb-3">Uploaded agreements and certifications</small>
                                <hr class="my-2 opacity-25">
                                <div class="vstack gap-2" id="viewCompanyDocuments">
                                </div>
                            </div>
                        </div>

                        <!-- Assigned Students Card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 mb-4"
                            style="--blur-lvl: 0.50">
                            <div class="card-body p-3">
                                <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Assigned Students</h6>
                                <small class="text-muted d-block mb-3">Students placed at this company — <span
                                        id="currentBatchLabel"></span></small>
                                <hr class="my-2 opacity-25">
                                <div class="vstack gap-2" id="viewCompanyStudents">
                                    <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0 py-2 px-3"
                                        role="alert">
                                        <i class="bi bi-person flex-shrink-0"></i>
                                        <div class="d-flex flex-column flex-grow-1 min-w-0">
                                            <span class="fw-500 small">Maria Reyes</span>
                                            <small class="text-muted">BSIT - 3rd Year</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- accepted programs card -->
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm rounded-3 mb-4"
                            style="--blur-lvl: 0.50">
                            <div class="card-body p-3">
                                <div class="mb-4">
                                    <h6 class="mb-1 fw-600 text-uppercase small letter-spacing">Accepted Programs</h6>
                                    <small class="text-muted d-block">Which courses this company accepts for OJT</small>
                                </div>
                                <hr class="my-3 opacity-25">
                                <div class="row row-cols-1 row-cols-md-2" id="viewCompanyAcceptedProgramsContainer">
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <button class="btn btn-sm btn-outline-secondary px-4" data-bs-dismiss="modal"
                                id="closeViewCompanyBtn">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="Docupload" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <h4 class="mb-2 fw-bold">Upload Company Document</h4>
                            <p class="text-muted small mb-0">Upload MOA or other relevant documents for this company.
                            </p>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4 shadow-sm mb-4"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-3">
                                <div class="mb-3">
                                    <label for="documentType" class="form-label fw-medium">Document Type</label>
                                    <select class="form-select border" id="documentType">
                                        <option value="moa" selected>Memorandum of Agreement (MOA)</option>
                                        <option value="nda">Non-Disclosure Agreement (NDA)</option>
                                        <option value="insurance">Insurance Certificate</option>
                                        <option value="bir_cert">BIR Certificate</option>
                                        <option value="sec_dti">SEC/DTI Registration</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="documentFile" class="form-label fw-medium">Select File</label>
                                    <input class="form-control border" type="file" id="documentFile" accept=".pdf">
                                    <small class="form-text text-muted d-block mt-2">
                                        <i class="bi bi-info-circle me-1"></i>
                                        PDF files only. Maximum size: 10MB.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="card bg-blur-5 bg-semi-transparent border-0 rounded-4 shadow-sm"
                            style="--blur-lvl: <?= $opacitylvl ?>">
                            <div class="card-body p-3">
                                <div class="row row-cols-1 row-cols-sm-2 g-3">
                                    <div class="col">
                                        <label for="ValidFrom" class="form-label fw-medium small">Valid From</label>
                                        <input type="date" class="form-control border" id="ValidFrom">
                                    </div>
                                    <div class="col">
                                        <label for="ValidUntil" class="form-label fw-medium small">Valid Until</label>
                                        <input type="date" class="form-control border" id="ValidUntil">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="hstack gap-2 mt-4 justify-content-end">
                            <button class="btn btn-outline-secondary px-4" data-bs-dismiss="modal" id="cancelUploadBtn">
                                Cancel
                            </button>
                            <button class="btn btn-dark text-light px-4" id="uploadDocumentBtn">
                                <i class="bi bi-upload me-2"></i>
                                Upload Document
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header.php" ?>
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="hstack">
                    <div>
                        <h4 class="">Companies</h4>
                        <p class="blockquote-footer pt-2 fs-6"><span id="totalcompanies">0</span> accredited partners ·
                            <span id="activeBatchLabel"></span>
                        </p>
                    </div>
                    <button class="btn btn-sm btn-outline-secondary ms-auto text-nowrap" id="addCompanyBtn"
                        data-bs-toggle="modal" data-bs-target="#NewCompanyModal">
                        <i class="bi bi-plus-lg me-1"></i>
                        Add Company
                    </button>
                </div>
                <div class="vstack">
                    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-3 d-flex">
                        <div class="col-12 col-sm-6 col-lg-6">
                            <div class="input-group">
                                <span class="input-group-text bg-blur-5 bg-semi-transparent border"
                                    style="--blur-lvl: 0.40">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" class="form-control bg-blur-5 bg-semi-transparent border"
                                    placeholder="Search companies..." id="searchInput" style="--blur-lvl: 0.40">
                            </div>
                        </div>
                        <div class="col-12 col-sm-2 col-lg-2">
                            <select class="form-select bg-blur-5 bg-semi-transparent border" id="filterStatus"
                                style="--blur-lvl: 0.40">
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-lg-2">
                            <select class="form-select bg-blur-5 bg-semi-transparent border" id="filterSetup"
                                style="--blur-lvl: 0.40">
                            </select>
                        </div>
                        <div class="col-12 col-sm-2 col-lg-2">
                            <select class="form-select bg-blur-5 bg-semi-transparent border" id="filterProgram"
                                style="--blur-lvl: 0.40">
                            </select>
                        </div>
                    </div>
                </div>
                <div class="container my-4 p-0">
                    <div class="row row-cols-1 row-cols-md-1 g-4 d-flex" id="companiesContainer">
                        <div class="col">
                            <div class="card h-100 bg-blur-5 bg-semi-transparent rounded-4 shadow-sm border-0 overflow-hidden"
                                style="--blur-lvl: 0.60; transition: box-shadow 0.3s ease, transform 0.3s ease;">
                                <div class="card-body p-4">
                                    <!-- Header Section -->
                                    <div class="d-flex align-items-start justify-content-between gap-3 mb-3">
                                        <div class="flex-grow-1 min-w-0">
                                            <h5 class="card-title mb-1 fw-bold text-white">${company.name}</h5>
                                            <p class="text-muted mb-0 small" style="font-size: 0.85rem;">
                                                ${company.industry} · ${company.city}</p>
                                        </div>
                                        <span
                                            class="badge bg-success text-white rounded-pill fw-medium flex-shrink-0">Active</span>
                                    </div>

                                    <!-- Stats Grid -->
                                    <div class="row row-cols-2 row-cols-lg-4 g-2 mb-4">
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100"
                                                style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Slots</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white">-</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100"
                                                style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Remaining</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white"><span>-</span></p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100"
                                                style="--blur-lvl: 0.40">
                                                <div class="card-body p-3 text-center">
                                                    <p class="card-title mb-2 text-muted small fw-medium">Work Setup</p>
                                                    <p class="card-text mb-0 fw-bold fs-6 text-white">-</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="card bg-blur-5 bg-semi-transparent rounded-3 border-0 shadow-sm h-100"
                                                style="--blur-lvl: 0.40">
                                                <div class="card-body p-3">
                                                    <p class="card-title mb-2 text-muted small fw-medium">MOA Expiry</p>
                                                    <p class="card-text mb-2 fw-bold fs-6 text-white small">-</p>
                                                    <div class="progress" style="height: 3px;">
                                                        <div class="progress-bar progress-bar-striped progress-bar-animated rounded-pill"
                                                            role="progressbar" style="width: 50%;" aria-valuenow="50"
                                                            aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Divider -->
                                    <hr class="my-3 opacity-50">

                                    <!-- Footer Section -->
                                    <div class="d-flex flex-column gap-2">
                                        <div class="d-flex align-items-center gap-2 mb-1 justify-content-between">
                                            <span
                                                class="badge bg-dark text-white rounded-pill fw-medium border border-secondary small">Programs</span>
                                            <div class="d-flex align-items-center gap-2">
                                                <button class="btn btn-sm btn-outline-light border text-nowrap"
                                                    data-bs-toggle="modal" data-bs-target="#ViewCompanyModal"
                                                    id="viewCompanyBtn-${company.uuid}">
                                                    <i class="bi bi-eye me-1"></i>
                                                    <span class="d-none d-sm-inline">View</span>
                                                </button>
                                                <button class="btn btn-sm btn-outline-light border text-nowrap"
                                                    data-bs-toggle="modal" data-bs-target="#EditCompanyModal"
                                                    id="editCompanyBtn-${company.uuid}">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                    <span class="d-none d-sm-inline">Edit</span>
                                                </button>
                                                <button class="btn btn-sm btn-outline-light border text-nowrap"
                                                    data-bs-toggle="modal" data-bs-target="#Docupload"
                                                    id="uploadMoABtn-${company.uuid}">
                                                    <i class="bi bi-upload me-1"></i>
                                                    <span class="d-none d-sm-inline">MOA</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    </main>
    </div>
</body>

</html>