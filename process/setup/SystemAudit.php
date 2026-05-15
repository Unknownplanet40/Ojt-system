<?php

require_once __DIR__ . '/../../helpers/helpers.php';
require_once __DIR__ . '/../../functions/system_check_functions.php';


$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'ojt_system';

try {
    $conn = @new mysqli($host, $user, $pass);
    if ($conn && !$conn->connect_error) {
        
        $db_check = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$db'");
        if ($db_check && $db_check->num_rows > 0) {
            $dbStatus = 'valid';
        } else {
            
            
            $dbStatus = 'pending';
        }
    } else {
        $dbStatus = 'error';
    }
} catch (Exception $e) {
    $dbStatus = 'error';
}

$results = [
    'php' => checkPHPVersion()['status'] === 'ok' ? 'valid' : 'error',
    'rewrite' => checkModRewrite()['status'] === 'ok' ? 'valid' : 'error',
    'mysql' => $dbStatus,
    'apache' => checkServerSoftware()['status'] === 'ok' ? 'valid' : 'error',
    'phpmailer' => isLibraryAvailable('phpmailer') ? 'valid' : 'error',
    'permissions' => 'valid', 
    'ratchet' => isLibraryAvailable('ratchet') ? 'valid' : 'error',
    'mpdf' => isLibraryAvailable('mpdf') ? 'valid' : 'error',
    'phpoffice' => isLibraryAvailable('phpspreadsheet') ? 'valid' : 'error'
];


$storage = checkStorageDirectories();
if (!empty($storage) && $storage[0]['status'] !== 'ok') {
    $results['permissions'] = 'error';
}

response([
    'status' => 'success',
    'checks' => $results
]);
