<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../Assets/SystemInfo.php";
require_once "../../Assets/database/dbconfig.php";

$active_sy = date("Y") . "-" . (date("Y") + 1);
$active_sem = (date("m") >= 6 && date("m") <= 11) ? "1st Semester" : "2nd Semester";

try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        // Fallback to defaults
    } else {
        $result = $conn->query("SELECT school_year, semester FROM batches WHERE status = 'active' LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $active_sy = $row['school_year'];
            $active_sem = $row['semester'] === 'Summer' ? 'Summer' : $row['semester'] . ' Semester';
        }
    }
} catch (Exception $e) {
    // Fallback to defaults
}

if (isset($_SESSION['user'])) {
    switch ($_SESSION['user']['role']) {
        case 'admin':
            header("Location: ../Pages/Admin/AdminDashboard");
            exit();
        case 'coordinator':
            header("Location: ../Pages/Coordinator/CoordinatorDashboard");
            exit();
        case 'student':
            header("Location: ../Pages/Students/StudentsDashboard");
            exit();
        case 'supervisor':
            header("Location: ../Pages/Supervisor/SupervisorDashboard");
            exit();
        default:
            session_destroy();
            header("Location: Login");
            exit();
    }
}
?>


<!doctype html>
<html lang="en">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<link rel="stylesheet" href="../../libs/bootstrap/css/bootstrap.css" />
	<link rel="stylesheet" href="../../libs/aos/css/aos.css" />
	<link rel="stylesheet" href="../../libs/driverjs/css/driver.css" />
	<link rel="stylesheet" href="../../Assets/style/AniBG.css" />
	<link rel="stylesheet" href="../../Assets/style/MainStyle.css" />
	<link rel="manifest" href="../../Assets/manifest.json" />

	<script defer src="../../libs/bootstrap/js/bootstrap.bundle.js"></script>
	<script defer src="../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
	<script defer src="../../libs/aos/js/aos.js"></script>
	<script src="../../libs/driverjs/js/driver.js.iife.js"></script>
	<script src="../../libs/jquery/js/jquery-3.7.1.min.js"></script>
	<script type="module" src="../../Assets/Script/loginScript.js"></script>
	<title><?= $ShortTitle ?></title>
</head>

<body class="login-page">
	<div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
		<div class="circle circle1" data-speed="fast"></div>
		<div class="circle circle2" data-speed="normal"></div>
		<div class="circle circle3" data-speed="slow"></div>
	</div>
	<div class="w-100 vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75"
		id="pageLoader">
		<div class="d-flex flex-column align-items-center">
			<span class="loader"></span>
		</div>
	</div>
	<div id="PageMainContent" class="login-page-main z-3 d-flex justify-content-center align-items-center w-100">
		<div class="login-panel-card card rounded-3 bg-blur-3 bg-semi-transparent w-100" style="--blur-lvl: 0.50;">
			<div class="row g-0 h-100">
				<div class="col-md-5 order-md-0 order-2">
					<div style="background-color: #0F6E56;" class="p-4 rounded-start-3 h-100 d-none d-md-block"
						name="login-left">
						<div class="vstack h-100">
							<div>
								<small
									class="text-start d-block mb-5 fw-bold text-muted"><?= $SchoolName ?></small>
								<h3 class="text-start text-white mb-3 text-wrap">
									<?= $LongTitle ?>
								</h3><small
									class="text-start d-block mb-4 text-white-50"><?= $Description ?></small>
							</div>
							<div class="mt-auto">
								<div class="alert alert-dark mb-0 px-3 py-2 opacity-50" role="alert">
									<small class="fw-bold" id="currentBatch">S.Y. </small><small
										id="currentSchoolYear"><?= htmlspecialchars($active_sy) ?></small>
									· <small
										id="currentSemester"><?= htmlspecialchars($active_sem) ?></small>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-md-7 order-md-1 order-1 border-5">
					<div class="card-body h-100 p-4">
						<h5 class="card-title mt-3">Sign In</h5>
						<small class="text-muted mb-3 d-block">Use the credentials provided by your coordinator or
							admin.</small>
						<div class="mb-3">
							<label for="email" class="form-label">Email address</label>
							<input type="email" class="form-control rounded-2 bg-semi-transparent fw-bold border border-secondary" id="email"
								placeholder="yourname@school.edu.ph">
							<div class="invalid-feedback" id="emailFeedback">Please enter a valid email address.</div>
						</div>
						<div class="mb-3">
							<label for="password" class="form-label">Password</label>
							<input type="password" class="form-control rounded-2 bg-semi-transparent fw-bold border border-secondary"
								id="password" placeholder="Enter your password">
							<div class="hstack">
								<div class="form-text me-auto"><a href="ForgotPassword.php"
										class="text-decoration-none text-success">Forgot
										password?</a></div>
								<div class="form-text"><input type="checkbox" class="form-check-input"
										id="showPassword"> <label for="showPassword"
										class="form-check-label text-muted">Show Password</label></div>
								<script>
									$("#showPassword").change(function() {
										const passwordField = $("#password");
										if ($(this).is(":checked")) {
											passwordField.attr("type", "text");
										} else {
											passwordField.attr("type", "password");
										}
									});
								</script>
							</div>
							<div class="invalid-feedback" id="passwordFeedback">Password must be at least 8 characters
								long and
								include uppercase, lowercase, and a number.</div>
						</div>
						<div class="d-grid mt-4">
							<button id="loginBtn"
								class="btn btn-outline-dark rounded-2 fw-bold text-light border-secondary" disabled>
								<span class="spinner-border spinner-border-sm me-2 d-none" id="loginSpinner"
									role="status" aria-hidden="true"></span>
								<span class="text-body" id="loginBtnText">Sign In</span>
							</button>
						</div>
						<div class="divider-line"><span>No account yet?</span></div>
						<div class="d-grid">
							<div class="alert rounded-3 p-2 px-3 bg-dark bg-opacity-50 border-secondary">
								<div class="vstack">
									<small class="mb-0 fw-bold">Don't have an account?</small>
									<small class="mb-3">Accounts are created by your OJT coordinator or system
										administrator. If
										you're
										a student or company supervisor and haven't received your login credentials yet,
										please contact
										your
										coordinator directly.</small>
									<small class="fw-bold text-success">Administrator@school.edu.ph · +63 912 345
										6789</small>
								</div>
							</div>
							<div class="alert rounded-3 p-2 px-3 bg-primary bg-opacity-25 border-secondary">
								<div class="vstack">
									<small>First time logging in? You will be asked to set a new password after signing
										in with your
										temporary credentials.</small>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>

</html>