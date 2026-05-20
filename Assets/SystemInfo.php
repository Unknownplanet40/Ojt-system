<?php


$LongTitle = "On-The-Job Training Management System";
$ShortTitle = "OJTMS";
$Description = "A web-based application designed to streamline the management of on-the-job training programs for students, educational institutions, and partner companies.";
$Author = "Ryan James V. Capadocia";
$SchoolName = "Your School Name Here";
$SchoolMotto = "School Motto Here";
$SchoolAddress = "School Address Here";
$SchoolWebsite = "http://www.yourschoolwebsite.com";
$SchoolEmail = "school@example.com";
$SchoolPhone = "+63 (46) 471-6607";
$SchoolLogoLeft = "https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans";
$SchoolLogoRight = "https://placehold.co/128x128/000000/FFF?text=LOGO&font=Open%20Sans";
$DocumentFooterNote = "Officially issued by the OJT Coordinator Management System";
$DocumentVerificationNote = "Please verify document authenticity with the coordinator's office.";
$PageLink = "http://localhost/ojt-system";
$opacitylvl = 0.55;


$db_file = __DIR__ . '/../config/db.php';
if (file_exists($db_file)) {
    
    
    if (!isset($conn)) {
        @include_once $db_file;
    }

    if (isset($conn) && $conn instanceof mysqli && !$conn->connect_error) {
        try {
            
            $target_db = $dbname ?? 'ojt_system';
            if (@$conn->select_db($target_db)) {
                $sql = "SELECT * FROM system_config WHERE id = 1 LIMIT 1";
                $result = @$conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    $sys = $result->fetch_assoc();
                    
                    if (!empty($sys['school_name'])) $SchoolName = $sys['school_name'];
                    if (!empty($sys['short_title'])) $ShortTitle = $sys['short_title'];
                    if (!empty($sys['system_description'])) $Description = $sys['system_description'];
                    if (!empty($sys['school_motto'])) $SchoolMotto = $sys['school_motto'];
                    if (!empty($sys['school_address'])) $SchoolAddress = $sys['school_address'];
                    if (!empty($sys['school_website'])) $SchoolWebsite = $sys['school_website'];
                    if (!empty($sys['school_email'])) $SchoolEmail = $sys['school_email'];
                    if (!empty($sys['school_phone'])) $SchoolPhone = $sys['school_phone'];
                    if (!empty($sys['footer_note'])) $DocumentFooterNote = $sys['footer_note'];
                    if (!empty($sys['verification_note'])) $DocumentVerificationNote = $sys['verification_note'];

                    
                    $base_folder = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
                    $base_folder = preg_replace('/(\/Src\/Pages|\/process\/.*)$/', '', $base_folder);
                    $upload_path = $base_folder . "/Assets/Images/systemImages/";

                    if (!empty($sys['logo_1'])) $SchoolLogoLeft = $upload_path . $sys['logo_1'];
                    if (!empty($sys['logo_2'])) $SchoolLogoRight = $upload_path . $sys['logo_2'];
                }
            }
        } catch (Exception $e) {
            
        }
    }
}

