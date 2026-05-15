<?php








function redirectError(string $code): void {
    $base = dirname($_SERVER['SCRIPT_NAME'], 3);
    header("Location: $base/Src/Pages/ErrorPage.php?error=$code");
    exit;
}




function printStatus(bool $success, string $message): void {
    $icon  = $success ? '&#10004;' : '&#10008;';
    $color = $success ? 'green'    : 'red';
    echo "<span style='color:$color;'>$icon $message</span><br>";
}




echo "<pre style='font-family: monospace; font-size: 14px; padding: 16px;'>";
echo "<strong>&#128269; Verifying Composer Dependencies...</strong><br>";
echo str_repeat('-', 50) . "<br><br>";

$errors = [];




$autoloadPath = dirname(__DIR__, 2) . '/libs/composer/vendor/autoload.php';

try {
    if (!file_exists($autoloadPath)) {
        throw new \RuntimeException("autoload.php not found at: $autoloadPath");
    }
    require $autoloadPath;
    printStatus(true, "Composer autoload loaded successfully.");
} catch (\Throwable $th) {
    printStatus(false, "Composer autoload failed: " . $th->getMessage());
    echo "<br><strong>&#10008; Verification aborted — autoload is required for all checks.</strong>";
    echo "</pre>";
    redirectError('CE00');
}

echo "<br>";




$ratchetClass = 'Ratchet\Server\IoServer';

if (!class_exists($ratchetClass)) {
    printStatus(false, "Ratchet is NOT installed. (Expected class: $ratchetClass)");
    $errors[] = 'CE01';
} else {
    printStatus(true, "Ratchet is installed and ready.");
}




$mailerClass = 'PHPMailer\PHPMailer\PHPMailer';

if (!class_exists($mailerClass)) {
    printStatus(false, "PHPMailer is NOT installed. (Expected class: $mailerClass)");
    $errors[] = 'CE02';
} else {
    printStatus(true, "PHPMailer is installed and ready.");
}








echo "<br>" . str_repeat('-', 50) . "<br>";

if (empty($errors)) {
    echo "<strong style='color:green;'>&#10004; All dependencies verified successfully.</strong>";
} else {
    $count = count($errors);
    $label = $count === 1 ? 'issue' : 'issues';
    echo "<strong style='color:red;'>&#10008; Verification completed with $count $label.</strong><br>";
    echo "Run <code>composer require cboden/ratchet phpmailer/phpmailer</code> ";
    echo "inside <code>libs/composer/</code> to fix missing packages.<br>";
    echo "See <code>InstallDependencies.md</code> for full setup instructions.<br>";

    
    echo "</pre>";
    redirectError($errors[0]);
}

echo "</pre>";