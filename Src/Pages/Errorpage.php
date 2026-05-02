<?php
$status = $_SERVER['REDIRECT_STATUS'] ?? 0;
require_once __DIR__ . '/../../Assets/SystemInfo.php';

$path = '/Ojt-system/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?php echo $path; ?>libs/bootstrap/css/bootstrap.css" />
    <link rel="stylesheet" href="<?php echo $path; ?>libs/aos/css/aos.css" />
    <link rel="stylesheet" href="<?php echo $path; ?>libs/driverjs/css/driver.css" />
    <link rel="stylesheet" href="<?php echo $path; ?>Assets/style/AniBG.css" />
    <link rel="stylesheet" href="<?php echo $path; ?>Assets/style/MainStyle.css" />
    <title><?php echo htmlspecialchars(($ShortTitle ?? 'OJT Management System') . ' - Error'); ?></title>

    <script defer src="<?php echo $path; ?>libs/bootstrap/js/bootstrap.js"></script>
    <script defer src="<?php echo $path; ?>libs/sweetalert2/js/sweetalert2.all.min.js"></script>
    <script src="<?php echo $path; ?>libs/driverjs/js/driver.js.iife.js"></script>
    <script src="<?php echo $path; ?>libs/jquery/js/jquery-3.7.1.min.js"></script>
    <script type="module" src="<?php echo $path; ?>Assets/Script/ErrorScript.js"></script>
    <?php 
        if ($status != 200) {
            echo '<script>let ServerStatus = ' . $status . ';</script>';
        }
    ?>
</head>

<body>
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1"></div>
        <div class="circle circle2"></div>
        <div class="circle circle3"></div>
    </div>

    <div class="container w-100 vh-100 d-flex justify-content-center align-items-center z-1" id="loading-spinner">
        <div class="spinner-border text-secondary mx-auto" style="width: 3rem; height: 3rem; border-width: 0.5rem"
            role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    <div class="container w-100 vh-100 d-flex justify-content-center align-items-center z-1 d-none" id="error-page">
        <div class="vstack gap-3 h-100 justify-content-center">
            <img src="<?php echo $path; ?>Assets/Images/icons/ErrorIcon.gif"
                alt="" class="mx-auto" style="width: 150px; height: 150px;" />
            <span class="text-center fs-5 fw-bold" id="status"></span>
            <p class="text-center fs-6" id="description"></p>
        </div>
    </div>
    <div>
        <button class="btn btn-sm btn-outline-secondary bg-blur-5 bg-semi-transparent rounded-5 border-0 position-absolute top-0 start-0 m-3 px-3 fw-bold" id="back-button">
            <i class="bi bi-arrow-left"></i> Back
        </button>
    </div>
</body>

</html>