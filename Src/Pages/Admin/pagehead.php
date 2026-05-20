<?php
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Location: ../ErrorPage.php?error=403');
    exit('Direct access not allowed');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once "../../../config/db.php";
require_once "../../../functions/auth_functions.php";
require_once "../../../Assets/SystemInfo.php";

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    header('Location: ../Login');
    exit;
}

if ((int)($_SESSION['must_change_password'] ?? 0) === 1) {
    header('Location: ../ChangePassword');
    exit;
}

$currentPage = pathinfo($_SERVER['PHP_SELF'] ?? '', PATHINFO_FILENAME);
$allowWithoutCompletedProfile = ['Admin_Profile'];
$isProfileDone = isUserProfileCompleted($conn, $_SESSION['user_uuid'], 'admin');
$_SESSION['is_profile_done'] = $isProfileDone ? 1 : 0;

if (!$isProfileDone && !in_array($currentPage, $allowWithoutCompletedProfile, true)) {
    header('Location: ./Admin_Profile');
    exit;
}
?>

<meta charset="UTF-8" />
<meta name="csrf-token"
	content="<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '' ?>">
<meta name="user-UUID"
	content="<?= isset($_SESSION['user_uuid']) ? $_SESSION['user_uuid'] : '' ?>">
<meta name="user-Role"
	content="<?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '' ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="../../../libs/bootstrap/css/bootstrap.css" />
<link rel="stylesheet" href="../../../libs/aos/css/aos.css" />
<link rel="stylesheet" href="../../../libs/driverjs/css/driver.css" />
<link rel="stylesheet" href="../../../Assets/style/AniBG.css" />
<link rel="stylesheet" href="../../../Assets/style/MainStyle.css" />
<link rel="manifest" href="../../../Assets/manifest.json" />
<link rel="shortcut icon" href="../../../Assets/Images/favicon.png" type="image/x-icon">

<script defer src="../../../libs/bootstrap/js/bootstrap.bundle.js"></script>
<script defer src="../../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../../../libs/aos/js/aos.js">
	AOS.init();
</script>
<script src="../../../libs/driverjs/js/driver.js.iife.js"></script>
<script src="../../../libs/jquery/js/jquery-3.7.1.min.js"></script>
<title>
	<?= htmlspecialchars($ShortTitle ?? 'OJT Management System') ?>
</title>
<meta name="app-root" content="/Ojt-system">
<script type="module" src="../../../Assets/Script/AlertBanner.js"></script>

<div id="adminMobileBlock" role="alertdialog" aria-modal="true"
     aria-labelledby="ambTitle" aria-describedby="ambDesc">
    <div class="amb-card">
        <div class="amb-icon-wrap">
            <i class="bi bi-display"></i>
        </div>
        <p class="amb-title" id="ambTitle">Desktop Only</p>
        <p class="amb-subtitle" id="ambDesc">
            The Admin Portal is optimised for desktop use and requires a
            screen width of at least <strong style="color:#c4b5fd;">992 px</strong>.
            Please switch to a laptop or desktop computer to continue.
        </p>
        <span class="amb-badge">
            <i class="bi bi-laptop"></i>
            Use a desktop browser
        </span>
    </div>
</div>

<script>
    /* Admin mobile-block guard — runs immediately, no jQuery needed */
    (function () {
        var BREAKPOINT = 992; /* lg — must match the CSS */
        var overlay    = document.getElementById('adminMobileBlock');

        function check() {
            if (!overlay) return;
            if (window.innerWidth < BREAKPOINT) {
                overlay.classList.add('visible');
                document.body.style.overflow = 'hidden';
            } else {
                overlay.classList.remove('visible');
                document.body.style.overflow = '';
            }
        }

        /* Run immediately so the overlay is visible before any paint */
        check();

        /* Re-check on resize (debounced at 120 ms) */
        var _resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(_resizeTimer);
            _resizeTimer = setTimeout(check, 120);
        });

        /* Re-check on orientation change (mobile devices) */
        window.addEventListener('orientationchange', function () {
            setTimeout(check, 300); /* slight delay for browser to report new size */
        });
    })();
</script>