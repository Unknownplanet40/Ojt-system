<?php
/**
 * VerifyDependencies.php
 * 
 * Verifies that all optional Composer dependencies (Ratchet, PHPMailer, etc.)
 * are installed and accessible before the application uses them.
 * 
 * Location : Assets/api/VerifyDependencies.php
 * Access   : AJAX requests only (uncomment the guard block below to enforce)
 * 
 * Error Codes:
 *   CE00 - Composer autoload not found (vendor/autoload.php missing)
 *   CE01 - Ratchet is not installed or missing
 *   CE02 - PHPMailer is not installed or missing
 */

// -----------------------------------------------------------------------
// AJAX Guard — redirect direct browser access to an error page
// -----------------------------------------------------------------------
/* if (realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) ||
        strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
        $base = dirname($_SERVER['SCRIPT_NAME'], 3);
        header("Location: $base/Src/Pages/ErrorPage.php?error=403");
        exit;
    }
} */

// -----------------------------------------------------------------------
// Helper — redirect to error page with a given error code
// -----------------------------------------------------------------------
function redirectError(string $code): void {
    $base = dirname($_SERVER['SCRIPT_NAME'], 3);
    header("Location: $base/Src/Pages/ErrorPage.php?error=$code");
    exit;
}

// -----------------------------------------------------------------------
// Helper — print a styled pass/fail status line
// -----------------------------------------------------------------------
function printStatus(bool $success, string $message): void {
    $icon  = $success ? '&#10004;' : '&#10008;';
    $color = $success ? 'green'    : 'red';
    echo "<span style='color:$color;'>$icon $message</span><br>";
}

// -----------------------------------------------------------------------
// Begin verification output
// -----------------------------------------------------------------------
echo "<pre style='font-family: monospace; font-size: 14px; padding: 16px;'>";
echo "<strong>&#128269; Verifying Composer Dependencies...</strong><br>";
echo str_repeat('-', 50) . "<br><br>";

$errors = [];

// -----------------------------------------------------------------------
// 1. Composer Autoload
// -----------------------------------------------------------------------
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

// -----------------------------------------------------------------------
// 2. Ratchet (WebSocket)
// -----------------------------------------------------------------------
$ratchetClass = 'Ratchet\Server\IoServer';

if (!class_exists($ratchetClass)) {
    printStatus(false, "Ratchet is NOT installed. (Expected class: $ratchetClass)");
    $errors[] = 'CE01';
} else {
    printStatus(true, "Ratchet is installed and ready.");
}

// -----------------------------------------------------------------------
// 3. PHPMailer
// -----------------------------------------------------------------------
$mailerClass = 'PHPMailer\PHPMailer\PHPMailer';

if (!class_exists($mailerClass)) {
    printStatus(false, "PHPMailer is NOT installed. (Expected class: $mailerClass)");
    $errors[] = 'CE02';
} else {
    printStatus(true, "PHPMailer is installed and ready.");
}

// -----------------------------------------------------------------------
// Additional dependency checks — duplicate and customize as needed
// -----------------------------------------------------------------------
/* 
    Example — add a new package check:

    $customClass = 'Vendor\Package\ClassName';   // replace with the actual class

    if (!class_exists($customClass)) {
        printStatus(false, "PackageName is NOT installed. (Expected class: $customClass)");
        $errors[] = 'CE03';                       // assign a unique error code
    } else {
        printStatus(true, "PackageName is installed and ready.");
    }
*/

// -----------------------------------------------------------------------
// Summary
// -----------------------------------------------------------------------
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

    // Redirect to the first encountered error
    echo "</pre>";
    redirectError($errors[0]);
}

echo "</pre>";