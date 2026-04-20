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
<meta name="csrf-token" content="<?= isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : '' ?>">
<meta name="user-UUID" content="<?= isset($_SESSION['user_uuid']) ? $_SESSION['user_uuid'] : '' ?>">
<meta name="user-Role" content="<?= isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '' ?>">
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<link rel="stylesheet" href="../../../libs/bootstrap/css/bootstrap.css" />
<link rel="stylesheet" href="../../../libs/aos/css/aos.css" />
<link rel="stylesheet" href="../../../libs/driverjs/css/driver.css" />
<link rel="stylesheet" href="../../../Assets/style/AniBG.css" />
<link rel="stylesheet" href="../../../Assets/style/MainStyle.css" />
<link rel="manifest" href="../../../Assets/manifest.json" />

<script defer src="../../../libs/bootstrap/js/bootstrap.bundle.js"></script>
<script defer src="../../../libs/sweetalert2/js/sweetalert2.all.min.js"></script>
<script src="../../../libs/aos/js/aos.js">
    AOS.init();
</script>
<script src="../../../libs/driverjs/js/driver.js.iife.js"></script>
<script src="../../../libs/jquery/js/jquery-3.7.1.min.js"></script>