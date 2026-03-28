<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user'])) {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";

$CurrentPage = "Companies";

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../../../libs/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="../../../libs/aos/css/aos.css" />
    <link rel="stylesheet" href="../../../libs/driverjs/css/driver.css" />
    <link rel="stylesheet" href="../../../Assets/style/AniBG.css" />
    <link rel="stylesheet" href="../../../Assets/style/MainStyle.css" />
    <link rel="manifest" href="../../../Assets/manifest.json" />

    <script defer src="../../../libs/bootstrap/js/bootstrap.bundle.js"></script>
    <script defer src="../../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script defer src="../../../libs/aos/js/aos.js"></script>
    <script src="../../../libs/driverjs/js/driver.js.iife.js"></script>
    <script src="../../../libs/jquery/js/jquery-3.7.1.min.js"></script>
    <script type="module" src="../../../Assets/Script/AdminScripts/CompaniesScripts.js"></script>
    <title><?= $ShortTitle ?></title>
</head>

<body class="login-page"
    data-role="<?= $_SESSION['user']['role'] ?>"
    data-uuid="<?= $_SESSION['user']['uuid'] ?>">
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
                    <div class="modal-body p-4">
                        <div>
                            <h4 class="mb-0">Add Company</h4>
                            <p>Register a new OJT partner company</p>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Basic information</p>
                                    <small>Register a new OJT partner company</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyName" class="form-label">Company Name<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="companyName"
                                                placeholder="e.g. Acme Corporation" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyindustry" class="form-label">Industry</label>
                                            <input type="text" class="form-control" id="companyindustry"
                                                placeholder="e.g. IT Services & Consulting">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyemail" class="form-label">Company Email</label>
                                            <input type="email" class="form-control" id="companyemail"
                                                placeholder="e.g. info@acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companycontact" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="companycontact"
                                                placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyaddress" class="form-label">Company Address</label>
                                            <input type="text" class="form-control" id="companyaddress"
                                                placeholder="e.g. 123 Main St">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companycity" class="form-label">City</label>
                                            <input type="text" class="form-control" id="companycity"
                                                placeholder="e.g. Taguig City">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companywebsite" class="form-label">Company Website</label>
                                            <input type="url" class="form-control" id="companywebsite"
                                                placeholder="e.g. https://www.acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyworksetup" class="form-label">Preferred Work Setup<span
                                                    class="text-danger">*</span></label>
                                            <select class="form-select" id="companyworksetup" required>
                                                <option value="" selected disabled>Select work setup</option>
                                                <option value="on-site">On-site</option>
                                                <option value="remote">Remote</option>
                                                <option value="hybrid">Hybrid</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Accreditation & slots</p>
                                    <small>Status and available slots for the current batch.</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyaccreditationstatus" class="form-label">Accreditation
                                                Status<span class="text-danger">*</span></label>
                                            <select class="form-select" id="companyaccreditationstatus" required>
                                                <option value="" selected disabled>Select accreditation status</option>
                                                <option class="CustomOption" value="active">Active</option>
                                                <option class="CustomOption" value="pending">Pending</option>
                                                <option class="CustomOption" value="expired">Expired</option>
                                                <option class="CustomOption" value="blacklisted">Blacklisted</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companytotalslots" class="form-label">Total Slots OJT slots
                                                <small>(current batch)</small><span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="companytotalslots"
                                                placeholder="e.g. 10" min="1" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Accepted programs</p>
                                    <small>Which courses this company accepts for OJT.</small>
                                </div>
                                <div class="programcontainer mt-2" id="acceptedProgramsContainer">
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Primary contact person</p>
                                    <small>HR or OJT coordinator at the company.</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companycontactname" class="form-label">Contact Person
                                                Name</label>
                                            <input type="text" class="form-control" id="companycontactname"
                                                placeholder="e.g. Juan Dela Cruz">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companyposition" class="form-label">Position/Role</label>
                                            <input type="text" class="form-control" id="companyposition"
                                                placeholder="e.g. HR Manager">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companycontactemail" class="form-label">Contact Person
                                                Email</label>
                                            <input type="email" class="form-control" id="companycontactemail"
                                                placeholder="e.g. hr@example.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="companycontactnumber" class="form-label">Contact Person
                                                Number</label>
                                            <input type="tel" class="form-control" id="companycontactnumber"
                                                placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hstack d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-dark text-light border" data-bs-dismiss="modal">Cancel</button>
                            <button class="btn btn-dark text-light border" id="saveCompanyBtn">Save Company</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="EditCompanyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel" data-company-uuid="">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4">
                        <div>
                            <h4 class="mb-0">Edit Company</h4>
                            <p>Edit the details of the selected company.</p>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Basic information</p>
                                    <small>Edit the general details of the company</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="EditcompanyName" class="form-label">Company Name<span
                                                    class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="EditcompanyName"
                                                placeholder="e.g. Acme Corporation" required>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyindustry" class="form-label">Industry</label>
                                            <input type="text" class="form-control" id="Editcompanyindustry"
                                                placeholder="e.g. IT Services & Consulting">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyemail" class="form-label">Company Email</label>
                                            <input type="email" class="form-control" id="Editcompanyemail"
                                                placeholder="e.g. info@acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanycontact" class="form-label">Contact Number</label>
                                            <input type="tel" class="form-control" id="Editcompanycontact"
                                                placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyaddress" class="form-label">Company Address</label>
                                            <input type="text" class="form-control" id="Editcompanyaddress"
                                                placeholder="e.g. 123 Main St">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanycity" class="form-label">City</label>
                                            <input type="text" class="form-control" id="Editcompanycity"
                                                placeholder="e.g. Taguig City">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanywebsite" class="form-label">Company Website</label>
                                            <input type="url" class="form-control" id="Editcompanywebsite"
                                                placeholder="e.g. https://www.acme.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyworksetup" class="form-label">Preferred Work
                                                Setup<span class="text-danger">*</span></label>
                                            <select class="form-select" id="Editcompanyworksetup" required>
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
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Accreditation & slots</p>
                                    <small>Status and available slots for the current batch.</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyaccreditationstatus" class="form-label">Accreditation
                                                Status<span class="text-danger">*</span></label>
                                            <select class="form-select" id="Editcompanyaccreditationstatus" required>
                                                <option class="CustomOption" value="active">Active</option>
                                                <option class="CustomOption" value="pending">Pending</option>
                                                <option class="CustomOption" value="expired">Expired</option>
                                                <option class="CustomOption" value="blacklisted">Blacklisted</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanytotalslots" class="form-label">Total Slots OJT slots
                                                <small>(current batch)</small><span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="Editcompanytotalslots"
                                                placeholder="e.g. 10" min="1" required>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-12 d-none" id="BlocklistedReasonContainer">
                                        <div class="mb-3">
                                            <label for="Editcompanyblocklistedreason" class="form-label">Blocklisted Reason</label>
                                            <textarea class="form-control" id="Editcompanyblocklistedreason" rows="3" placeholder="Provide reason for blocklisting (e.g. violation of agreement terms)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Accepted programs</p>
                                    <small>Which courses this company accepts for OJT.</small>
                                </div>
                                <div class="programcontainer mt-2" id="EditacceptedProgramsContainer">
                                </div>
                            </div>
                        </div>
                        <div class="card bg-blur-5 bg-semi-transparent border-0 shadow-sm mb-2"
                            style="--blur-lvl: 0.70">
                            <div class="card-body p-3">
                                <div>
                                    <p class="mb-0 fw-bold">Primary contact person</p>
                                    <small>HR or OJT coordinator at the company.</small>
                                </div>
                                <div class="row row-cols-1 row-cols-md-2 g-2 d-flex mt-2">
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanycontactname" class="form-label">Contact Person
                                                Name</label>
                                            <input type="text" class="form-control" id="Editcompanycontactname"
                                                placeholder="e.g. Juan Dela Cruz">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanyposition" class="form-label">Position/Role</label>
                                            <input type="text" class="form-control" id="Editcompanyposition"
                                                placeholder="e.g. HR Manager">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanycontactemail" class="form-label">Contact Person
                                                Email</label>
                                            <input type="email" class="form-control" id="Editcompanycontactemail"
                                                placeholder="e.g. hr@example.com">
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="mb-3">
                                            <label for="Editcompanycontactnumber" class="form-label">Contact Person
                                                Number</label>
                                            <input type="tel" class="form-control" id="Editcompanycontactnumber"
                                                placeholder="e.g. 09XX XXX XXXX">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hstack d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-dark text-light border" data-bs-dismiss="modal"
                                id="cancelEditCompanyBtn">Cancel</button>
                            <button class="btn btn-dark text-light border" id="EditsaveCompanyBtn">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="ViewCompanyModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="staticBackdropLabel">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-fullscreen-sm-down">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4">
                        <div class="hstack">
                            <div>
                                <h4 class="mb-0" id="viewCompanyName">Company Name</h4>
                                <p id="viewCompanyIndustry">Industry</p>
                            </div>
                            <button class="btn btn-sm btn-outline-dark text-light border ms-auto text-nowrap"
                                id="editCompanyBtn">
                                <i class="bi bi-pencil-square me-1"></i>
                                Edit
                            </button>
                            <button class="btn btn-sm btn-outline-dark border text-light ms-2 text-nowrap" data-bs-toggle="modal" data-bs-target="#Docupload"
                                id="uploadMoABtn">
                                <i class="bi bi-upload me-1"></i>
                                Upload MOA
                            </button>
                        </div>
                        <div class="row row-cols-1 row-cols-md-2 g-4 d-flex">
                            <div class="col">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm mb-2 rounded-4 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <div>
                                            <p class="mb-0 fw-bold">Company details</p>
                                        </div>
                                        <hr class="my-2">
                                        <div class="vstack gap-1">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-envelope me-5 w-25 text-nowrap"> Email:</i>
                                                <span id="viewCompanyEmail">Unknown</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-telephone me-5 w-25 text-nowrap"> Contact Number:</i>
                                                <span id="viewCompanyContactNumber">Unknown</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-geo-alt me-5 w-25 text-nowrap"> Address:</i>
                                                <span id="viewCompanyAddress">Unknown</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-globe me-5 w-25 text-nowrap"> Website:</i>
                                                <span id="viewCompanyWebsite">Unknown</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-check-circle me-5 w-25 text-nowrap"> Status:</i>
                                                <span id="viewCompanyStatus">Active</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm mb-2 rounded-4 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <div>
                                            <p class="mb-0 fw-bold">Slot summary</p>
                                        </div>
                                        <hr class="my-2">
                                        <div class="vstack gap-1">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-calendar me-5 w-25 text-nowrap"> Batch:</i>
                                                <span id="viewCompanyBatch"></span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-people me-5 w-25 text-nowrap"> Total Slots:</i>
                                                <span id="viewCompanyTotalSlots"></span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-person-check me-5 w-25 text-nowrap"> Filled:</i>
                                                <span id="viewCompanyFilledSlots"></span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-person-x me-5 w-25 text-nowrap"> Remaining:</i>
                                                <span id="viewCompanyRemainingSlots"></span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-calendar me-5 w-25 text-nowrap"> MOA Expiry:</i>
                                                <span id="viewCompanyMOAExpiry"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm mb-2 rounded-4 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <div>
                                            <p class="mb-0 fw-bold">Primary contact</p>
                                        </div>
                                        <hr class="my-2">
                                        <div class="vstack gap-1">
                                            <div class="hstack gap-2">
                                                <i class="bi bi-person me-5 w-25 text-nowrap"> Name:</i>
                                                <span id="viewContactName"></span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-briefcase me-5 w-25 text-nowrap"> Position:</i>
                                                <span id="viewContactPosition">HR Manager</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-envelope me-5 w-25 text-nowrap"> Email:</i>
                                                <span id="viewContactEmail">maria.reyes@accenture.com.ph</span>
                                            </div>
                                            <div class="hstack gap-2">
                                                <i class="bi bi-telephone me-5 w-25 text-nowrap"> Contact Number:</i>
                                                <span id="viewContactNumber">+63 912 345 6789</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm mb-2 rounded-4 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <div>
                                            <p class="mb-0 fw-bold">Documents</p>
                                            <small>Uploaded agreements and certifications.</small>
                                        </div>
                                        <hr class="my-2">
                                        <div class="vstack gap-2" id="viewCompanyDocuments">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-12">
                                <div class="card bg-blur-5 bg-semi-transparent border shadow-sm mb-2 rounded-4 h-100"
                                    style="--blur-lvl: 0.50">
                                    <div class="card-body p-3">
                                        <div>
                                            <p class="mb-0 fw-bold">Assigned students</p>
                                            <small>Students placed at this company — <span
                                                    id="currentBatchLabel"></span>.</small>
                                        </div>
                                        <hr class="my-2">
                                        <div class="vstack gap-2" id="viewCompanyStudents">
                                            <div class="alert alert-secondary d-flex align-items-center gap-2 mb-0"
                                                role="alert">
                                                <i class="bi bi-person me-2"></i>
                                                <div class="d-flex flex-column">
                                                    <span class="fw-bold">Maria Reyes</span>
                                                    <small>BSIT - 3rd Year</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="hstack d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-dark text-light border" data-bs-dismiss="modal" id="closeViewCompanyBtn">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="Docupload" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content bg-blur-5 bg-semi-transparent border-0 shadow" style="--blur-lvl: 0.40">
                    <div class="modal-body p-4">
                        <div>
                            <h4 class="mb-0">Upload Company Document</h4>
                            <p>Upload MOA or other relevant documents for this company.</p>
                        </div>
                        <div class="mb-3">
                            <label for="documentType" class="form-label">Document Type</label>
                            <select class="form-select" id="documentType">
                                <option value="moa" selected>Memorandum of Agreement (MOA)</option>
                                <option value="nda">Non-Disclosure Agreement (NDA)</option>
                                <option value="insurance">Insurance Certificate</option>
                                <option value="bir_cert">BIR Certificate</option>
                                <option value="sec_dti">SEC/DTI Registration</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="documentFile" class="form-label">Select file</label>
                            <input class="form-control" type="file" id="documentFile" accept=".pdf">
                            <small class="form-text text-muted">Only PDF files are allowed. Max size: 10MB.</small>
                        </div>
                        <div id="moaValidityFields">
                            <div class="mb-3">
                                <label for="moaValidFrom" class="form-label">MOA Valid From</label>
                                <input type="date" class="form-control" id="moaValidFrom">
                            </div>
                            <div class="mb-3">
                                <label for="moaValidUntil" class="form-label">MOA Valid Until</label>
                                <input type="date" class="form-control" id="moaValidUntil">
                            </div>
                        </div>
                        <div class="hstack d-flex justify-content-end gap-2 mt-3">
                            <button class="btn btn-dark text-light border" data-bs-dismiss="modal" id="cancelUploadBtn">Cancel</button>
                            <button class="btn btn-dark text-light border" id="uploadDocumentBtn">Upload</button>
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
                            <select class="form-select bg-blur-5 bg-semi-transparent border"
                                id="filterStatus" style="--blur-lvl: 0.40">
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

                    </div>
                </div>
            </div>
    </div>
    </main>
    </div>
</body>

</html>