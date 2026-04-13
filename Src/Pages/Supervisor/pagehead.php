<?php
if (realpath(__FILE__) === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header('Location: ../ErrorPage.php?error=403');
    exit('Direct access not allowed');
}
?>

<meta charset="UTF-8" />
<meta name="csrf-token" content="<?= $_SESSION['csrf_token'] ?? '' ?>">
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
<title><?= $ShortTitle ?></title>