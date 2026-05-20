<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/helpers.php';

class CertificateManager {
    private $db;
    private $logger;
    private $uploadsPath;
    private $qrCachePath;
    private $maxQRCacheAge = 2592000;
    private $tokenLength = 64;
    
    const MIN_CERT_NUMBER_LENGTH = 8;
    const MAX_CERT_NUMBER_LENGTH = 50;
    const ALLOWED_CERT_FORMATS = ['pdf', 'html'];
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->logger = new Logger();
        $this->uploadsPath = __DIR__ . '/../uploads/certificates';
        $this->qrCachePath = $this->uploadsPath . '/qr-cache';
    
        if (!is_dir($this->uploadsPath)) {
            mkdir($this->uploadsPath, 0750, true);
            chmod($this->uploadsPath, 0750);
        }

        if (!is_dir($this->qrCachePath)) {
            mkdir($this->qrCachePath, 0750, true);
            chmod($this->qrCachePath, 0750);
        }
    }
    
    private function isValidUUID($uuid) {
        $uuidPattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';
        return preg_match($uuidPattern, $uuid) === 1 || preg_match('/^[0-9a-f]{32}$/i', $uuid) === 1;
    }
    
    public function generateCertificateNumber($companyUuid, $year = null) {
        try {
            if (!$this->isValidUUID($companyUuid)) {
                throw new Exception("Invalid company UUID format: " . substr($companyUuid, 0, 10) . "...");
            }
            
            if ($year === null) {
                $year = (int)date('Y');
            } else {
                $year = (int)$year;
                if ($year < 2000 || $year > 2099) {
                    throw new Exception("Invalid year: $year");
                }
            }
            
            $query = "SELECT uuid, name FROM companies WHERE uuid = ? LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$companyUuid]);
            $company = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$company) {
                throw new Exception("Company not found: " . substr($companyUuid, 0, 10) . "...");
            }
            
            $companyCode = strtoupper(
                substr(preg_replace('/[^a-zA-Z0-9]/', '', $company['name']), 0, 3)
            );
            
            if (strlen($companyCode) < 3) {
                $companyCode = str_pad($companyCode, 3, '0');
            }
            
            $query = "SELECT COUNT(*) as count FROM certificates 
                      WHERE company_uuid = ? AND YEAR(generated_at) = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$companyUuid, $year]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $serial = str_pad((int)$result['count'] + 1, 5, '0', STR_PAD_LEFT);
            $certificateNumber = "OJT-{$year}-{$companyCode}-{$serial}";
            
            if (strlen($certificateNumber) > self::MAX_CERT_NUMBER_LENGTH) {
                throw new Exception("Generated certificate number exceeds maximum length");
            }
            
            $this->logger->info("Generated certificate number: $certificateNumber for company: {$company['name']}", 'CertificateManagement');
            return $certificateNumber;
            
        } catch (Exception $e) {
            $this->logger->error("Error generating certificate number: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function generateVerificationToken() {
        try {
            $randomBytes = random_bytes(32);
            $token = bin2hex($randomBytes);
            
            if (strlen($token) !== $this->tokenLength) {
                throw new Exception("Generated token has invalid length");
            }
            
            return $token;
        } catch (Exception $e) {
            $this->logger->error("Error generating verification token: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function validateCertificateData($data) {
        $errors = [];
        
        $requiredFields = ['student_uuid', 'ojt_grades_uuid', 'batch_uuid', 'company_uuid', 'certificate_number'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Missing required field: $field";
            }
        }
        
        $uuidFields = ['student_uuid', 'ojt_grades_uuid', 'batch_uuid', 'company_uuid'];
        foreach ($uuidFields as $field) {
            if (!empty($data[$field]) && !$this->isValidUUID($data[$field])) {
                $errors[] = "Invalid UUID format for $field";
            }
        }
        
        if (!empty($data['certificate_number'])) {
            if (!preg_match('/^OJT-\d{4}-[A-Z0-9]{3}-\d{5}$/', $data['certificate_number'])) {
                $errors[] = "Invalid certificate number format";
            }
        }
        
        if (!empty($data['verification_token'])) {
            if (!preg_match('/^[a-f0-9]{64}$/', $data['verification_token'])) {
                $errors[] = "Invalid verification token format";
            }
        }
        
        return [
            'valid' => count($errors) === 0,
            'errors' => $errors
        ];
    }
    
    public function generateQRCode($verificationToken, $format = 'png') {
        try {
            $verificationUrl = buildCertificateVerificationUrl($verificationToken);
            $qrData = $this->createQRCodeBinary($verificationUrl, $format);
            if ($qrData === false) {
                throw new Exception("Failed to generate QR code");
            }
            
            return base64_encode($qrData);
        } catch (Exception $e) {
            $this->logger->error("Error generating QR code: " . $e->getMessage());
            return false;
        }
    }

    public function getOrGenerateQRCode($certificateUuid, $verificationUrl, $format = 'png') {
        try {
            $format = in_array($format, ['png', 'svg'], true) ? $format : 'png';
            $cacheKey = hash('sha256', $certificateUuid . '|' . $verificationUrl . '|' . $format);
            $cacheFile = $this->qrCachePath . '/' . $cacheKey . '.' . $format;

            if (is_file($cacheFile) && filesize($cacheFile) > 0) {
                $cached = file_get_contents($cacheFile);
                if ($cached !== false) {
                    return $cached;
                }
            }

            $qrData = $this->createQRCodeBinary($verificationUrl, $format);
            if ($qrData === false) {
                return false;
            }

            file_put_contents($cacheFile, $qrData);
            return $qrData;
        } catch (Exception $e) {
            $this->logger->error("Error caching QR code: " . $e->getMessage());
            return false;
        }
    }

    private function createQRCodeBinary($verificationUrl, $format = 'png') {
        if (class_exists('chillerlan\QRCode\QRCode')) {
            $qr = new \chillerlan\QRCode\QRCode($verificationUrl);
            return $format === 'svg' ? $qr->render('svg') : $qr->render('png');
        }

        $encodedUrl = urlencode($verificationUrl);
        $qrServiceUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data={$encodedUrl}";

        $qrData = @file_get_contents($qrServiceUrl);
        return $qrData === false ? false : $qrData;
    }
    
    public function generateCertificatePDF($studentUuid, $gradesUuid, $certificateNumber, $verificationToken, $schoolConfig = []) {
        try {
            if (!$this->isValidUUID($studentUuid)) {
                throw new Exception("Invalid student UUID format");
            }
            if (!$this->isValidUUID($gradesUuid)) {
                throw new Exception("Invalid grades UUID format");
            }
            if (!preg_match('/^OJT-\d{4}-[A-Z0-9]{3}-\d{5}$/', $certificateNumber)) {
                throw new Exception("Invalid certificate number format");
            }
            if (!preg_match('/^[a-f0-9]{64}$/', $verificationToken)) {
                throw new Exception("Invalid verification token format");
            }
            
            $studentQuery = "SELECT sp.uuid, sp.first_name, sp.middle_name, sp.last_name, sp.student_number,
                             u.email, p.name as program_name
                             FROM student_profiles sp
                             LEFT JOIN users u ON sp.user_uuid = u.uuid
                             LEFT JOIN programs p ON sp.program_uuid = p.uuid
                             WHERE sp.uuid = ? LIMIT 1";
            $stmt = $this->db->prepare($studentQuery);
            $stmt->execute([$studentUuid]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                throw new Exception("Student profile not found for UUID: " . substr($studentUuid, 0, 10) . "...");
            }
            
            if (empty($student['first_name']) || empty($student['last_name'])) {
                throw new Exception("Student profile missing name fields - ensure first_name and last_name are populated");
            }
            if (empty($student['student_number'])) {
                throw new Exception("Student profile missing student_number");
            }
            if (empty($student['program_name'])) {
                throw new Exception("Student profile has no program assigned - ensure program_uuid is set and valid");
            }
            
            $gradesQuery = "SELECT og.uuid, og.grade_equivalent, og.weighted_score,
                            b.end_date, b.school_year, b.semester, 
                            c.name as company_name, c.address as company_address
                            FROM ojt_grades og
                            LEFT JOIN batches b ON og.batch_uuid = b.uuid
                            LEFT JOIN student_profiles sp ON og.student_uuid = sp.uuid
                            LEFT JOIN companies c ON sp.company_uuid = c.uuid
                            WHERE og.uuid = ? LIMIT 1";
            $stmt = $this->db->prepare($gradesQuery);
            $stmt->execute([$gradesUuid]);
            $grades = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$grades) {
                throw new Exception("OJT grades record not found for UUID: " . substr($gradesUuid, 0, 10) . "...");
            }
            
            if (empty($grades['end_date'])) {
                throw new Exception("Grades record missing end_date - ensure batch is assigned and data is complete");
            }
            if (empty($grades['company_name'])) {
                throw new Exception("Grades record missing company information - ensure company is properly assigned");
            }
            if (empty($grades['school_year']) || empty($grades['semester'])) {
                throw new Exception("Grades record missing school year or semester - ensure batch data is complete");
            }
            
            $qrCode = $this->generateQRCode($verificationToken, 'png');
            if (!$qrCode) {
                throw new Exception("Failed to generate QR code");
            }
            
            $mpdfPath = __DIR__ . '/../libs/composer/vendor/autoload.php';
            if (!file_exists($mpdfPath)) {
                throw new Exception("mPDF library not found. Please run composer install.");
            }
            require_once $mpdfPath;
            
            $coordinatorName = "OJT Coordinator";
            if (isset($_SESSION['user_uuid'])) {
                $coordQuery = "SELECT first_name, last_name FROM coordinator_profiles 
                               WHERE user_uuid = ? LIMIT 1";
                $stmt = $this->db->prepare($coordQuery);
                $stmt->execute([$_SESSION['user_uuid']]);
                $coord = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($coord) {
                    $coordinatorName = trim($coord['first_name'] . ' ' . $coord['last_name']);
                }
            }
            
            $html = $this->buildCertificateHTML($student, $grades, $certificateNumber, $verificationToken, $qrCode, $schoolConfig, $coordinatorName);
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'L',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
            ]);
            
            $studentDisplayName = htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ?? '') . ' ' . $student['last_name']));
            $mpdf->SetTitle("Certificate - {$studentDisplayName}");
            $mpdf->SetAuthor($schoolConfig['SchoolName'] ?? 'OJT Management System');
            $mpdf->SetSubject("OJT Certificate of Completion");
            $mpdf->SetKeywords('certificate, ojt, completion');
            $mpdf->SetCreator('OJT Management System - Production');
            
            $mpdf->WriteHTML($html);
            
            $filename = 'CERT-' . $certificateNumber . '_' . bin2hex(random_bytes(4)) . '.pdf';
            $filepath = $this->uploadsPath . '/' . $filename;
            
            if (!is_writable($this->uploadsPath)) {
                throw new Exception("Certificates directory is not writable at: {$this->uploadsPath}");
            }
            
            try {
                $mpdf->Output($filepath, \Mpdf\Output\Destination::FILE);
            } catch (Exception $mpdfError) {
                throw new Exception("mPDF Output failed: " . $mpdfError->getMessage());
            }
            
            if (!file_exists($filepath)) {
                throw new Exception("PDF file was not created - file does not exist at: {$filepath}");
            }
            
            $filesize = @filesize($filepath);
            if ($filesize === false || $filesize === 0) {
                @unlink($filepath); // Clean up empty file
                throw new Exception("PDF file is empty or inaccessible (size: {$filesize} bytes)");
            }
            
            if (!chmod($filepath, 0640)) {
                $this->logger->warn("Could not set file permissions on certificate: {$filename}");
            }
            
            $this->logger->info(
                "Certificate generated successfully: {$certificateNumber} for student: {$student['student_number']} (Size: {$filesize} bytes)", 
                'CertificateManagement'
            );
            
            return 'uploads/certificates/' . $filename;
            
        } catch (Exception $e) {
            $errorMsg = "Error generating certificate PDF: " . $e->getMessage();
            $this->logger->error($errorMsg . " [Student: {$studentUuid}, Grades: {$gradesUuid}]");
            return false;
        }
    }
    
    private function buildCertificateHTML($student, $grades, $certificateNumber, $verificationToken, $qrCode, $schoolConfig, $coordinatorName) {
        $studentName = htmlspecialchars(trim($student['first_name'] . ' ' . ($student['middle_name'] ? $student['middle_name'] . ' ' : '') . $student['last_name']), ENT_QUOTES, 'UTF-8');
        $programName = htmlspecialchars($student['program_name'] ?? 'Degree Program', ENT_QUOTES, 'UTF-8');
        $companyName = htmlspecialchars($grades['company_name'] ?? 'Partner Company', ENT_QUOTES, 'UTF-8');
        $coordinatorName = htmlspecialchars(trim($coordinatorName), ENT_QUOTES, 'UTF-8');
        
        $endDate = $grades['end_date'] ?? date('Y-m-d');
        $dateCompletion = date('F j, Y', strtotime($endDate));
        $generatedDate = date('F j, Y');
        
        $schoolName = htmlspecialchars($schoolConfig['school_name'] ?? 'OJT Management System', ENT_QUOTES, 'UTF-8');
        $schoolMotto = htmlspecialchars($schoolConfig['school_motto'] ?? '', ENT_QUOTES, 'UTF-8');
        $longTitle = htmlspecialchars($schoolConfig['long_title'] ?? 'On-the-Job Training System', ENT_QUOTES, 'UTF-8');
        $footerNote = htmlspecialchars($schoolConfig['footer_note'] ?? 'Officially issued by the OJT Coordinator Management System', ENT_QUOTES, 'UTF-8');
        
        $logo1Path = __DIR__ . '/../../' . ltrim($schoolConfig['logo_1'] ?? 'Assets/Images/school_logo.png', '/');
        $logo2Path = __DIR__ . '/../../' . ltrim($schoolConfig['logo_2'] ?? 'Assets/Images/school_logo.png', '/');
        
        $logo1Base64 = file_exists($logo1Path) && is_readable($logo1Path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo1Path)) : '';
        $logo2Base64 = file_exists($logo2Path) && is_readable($logo2Path) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logo2Path)) : '';
        
        $qrDataUri = 'data:image/png;base64,' . $qrCode;

        return "
        <!DOCTYPE html>
        <html lang=\"en\">
        <head>
            <meta charset=\"UTF-8\">
            <style>
                body { font-family: Arial, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
                .cert-container { border: 2px solid #e2e8f0; padding: 25px; text-align: center; height: 100%; box-sizing: border-box; }
                .header { text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px; }
                .header-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
                .header-table td { vertical-align: middle; }
                .header-left { width: 20%; text-align: left; }
                .header-center { width: 60%; text-align: center; }
                .header-right { width: 20%; text-align: right; }
                .header-logo { width: 65px; height: 65px; object-fit: contain; }
                
                .title { font-size: 42px; font-weight: bold; color: #0f172a; margin: 15px 0; text-transform: uppercase; letter-spacing: 0.05em; }
                .subtitle { font-size: 16px; color: #64748b; margin-bottom: 25px; text-transform: uppercase; letter-spacing: 0.1em; }
                
                .presented-to { font-size: 14px; color: #475569; margin-bottom: 15px; }
                .student-name { font-size: 32px; font-weight: bold; color: #0f172a; margin-bottom: 20px; text-decoration: underline; }
                
                .description { font-size: 15px; color: #334155; line-height: 1.5; max-width: 800px; margin: 0 auto; }
                .highlight { font-weight: bold; color: #0f172a; }
                
                .footer-area { margin-top: 35px; width: 100%; }
                .signature-table { width: 100%; border-collapse: collapse; }
                .signature-td { width: 50%; text-align: center; vertical-align: bottom; }
                .qr-td { width: 50%; text-align: right; vertical-align: bottom; padding-right: 20px; }
                
                .sig-line { border-top: 1px solid #1e293b; padding-top: 5px; font-weight: bold; font-size: 15px; width: 250px; margin: 0 auto; }
                .sig-title { font-size: 12px; color: #64748b; margin-top: 4px; }
                
                .qr-img { width: 90px; height: 90px; border: 1px solid #e2e8f0; padding: 4px; }
                .cert-no { font-size: 10px; color: #94a3b8; margin-top: 6px; font-family: monospace; }
                
                .footer-text { margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 10px; font-size: 9px; color: #64748b; text-align: center; }
            </style>
        </head>
        <body>
            <div class=\"cert-container\">
                <div class=\"header\">
                    <table class=\"header-table\" cellpadding=\"0\" cellspacing=\"0\" border=\"0\">
                        <tr>
                            <td class=\"header-left\">
                                " . ($logo1Base64 ? "<img src=\"{$logo1Base64}\" class=\"header-logo\" />" : "") . "
                            </td>
                            <td class=\"header-center\" style=\"line-height:1.35;\">
                                <div style=\"font-size: 18px; font-weight: 700; color: #0f172a; text-transform: uppercase; letter-spacing: 0.04em;\">{$schoolName}</div>
                                <div style=\"font-size: 12px; color: #64748b; margin-top: 4px;\">{$schoolMotto}</div>
                                <div style=\"font-size: 12px; color: #475569; margin-top: 4px;\">Official Digital Credential Document</div>
                                <div style=\"font-size: 12px; color: #64748b; margin-top: 4px;\">{$longTitle}</div>
                            </td>
                            <td class=\"header-right\">
                                " . ($logo2Base64 ? "<img src=\"{$logo2Base64}\" class=\"header-logo\" />" : "") . "
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class=\"title\">Certificate of Completion</div>
                <div class=\"subtitle\">On-the-Job Training Program</div>
                
                <div class=\"presented-to\">This is proudly presented to</div>
                <div class=\"student-name\">{$studentName}</div>
                
                <div class=\"description\">
                    For successfully fulfilling the required hours of the On-the-Job Training Program<br>
                    in partial fulfillment of the requirements for the degree of<br>
                    <span class=\"highlight\">{$programName}</span><br>
                    at <span class=\"highlight\">{$companyName}</span><br>
                    completed on {$dateCompletion}.
                </div>
                
                <div class=\"footer-area\">
                    <table class=\"signature-table\">
                        <tr>
                            <td class=\"signature-td\">
                                <div class=\"sig-line\">{$coordinatorName}</div>
                                <div class=\"sig-title\">OJT Coordinator</div>
                            </td>
                            <td class=\"qr-td\">
                                <img src=\"{$qrDataUri}\" class=\"qr-img\" />
                                <div class=\"cert-no\">Cert No: {$certificateNumber}</div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class=\"footer-text\">
                    {$footerNote} <br>
                    Generated on {$generatedDate} · To verify authenticity, scan the QR code.
                </div>
            </div>
        </body>
        </html>";
    }
    
    public function createCertificate($data) {
        try {
            $validation = $this->validateCertificateData($data);
            if (!$validation['valid']) {
                throw new Exception("Validation failed: " . implode(', ', $validation['errors']));
            }
            
            $hoursCompleted = (int)($data['hours_completed'] ?? 0);
            if ($hoursCompleted < 0 || $hoursCompleted > 10000) {
                throw new Exception("Invalid hours completed: must be between 0 and 10000");
            }
            
            $completionDate = $data['completion_date'] ?? date('Y-m-d');
            if (!strtotime($completionDate)) {
                throw new Exception("Invalid completion date format");
            }
            
            if (!empty($data['generated_by']) && !$this->isValidUUID($data['generated_by'])) {
                throw new Exception("Invalid generated_by UUID format");
            }
            
            $certificateUuid = bin2hex(random_bytes(16));
            
            $query = "INSERT INTO certificates (
                uuid, student_uuid, ojt_grades_uuid, batch_uuid, company_uuid,
                certificate_number, verification_token, file_path, hours_completed,
                completion_date, generated_by, generated_at, expires_at, is_revoked
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 10 YEAR), 0)";
            
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([
                $certificateUuid,
                $data['student_uuid'],
                $data['ojt_grades_uuid'],
                $data['batch_uuid'],
                $data['company_uuid'],
                $data['certificate_number'],
                $data['verification_token'],
                $data['file_path'],
                $hoursCompleted,
                $completionDate,
                $data['generated_by'] ?? null
            ]);
            
            if (!$result) {
                throw new Exception("Failed to insert certificate record into database");
            }
            
            $this->logger->info(
                "Certificate created: UUID={$certificateUuid}, Number={$data['certificate_number']}, " .
                "Student={$data['student_uuid']}, Company={$data['company_uuid']}", 
                'CertificateManagement'
            );
            
            return $certificateUuid;
            
        } catch (Exception $e) {
            $this->logger->error("Error creating certificate record: " . $e->getMessage());
            return false;
        }
    }
    
    public function getCertificateByUUID($certificateUuid) {
        try {
            if (!$this->isValidUUID($certificateUuid)) {
                throw new Exception("Invalid certificate UUID format");
            }
            
            $query = "SELECT * FROM certificates WHERE uuid = ? LIMIT 1";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$certificateUuid]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: false;
        } catch (Exception $e) {
            $this->logger->error("Error retrieving certificate: " . $e->getMessage());
            return false;
        }
    }
    
    public function revokeCertificate($certificateUuid, $reason, $revokedByUuid) {
        try {
            if (!$this->isValidUUID($certificateUuid)) {
                throw new Exception("Invalid certificate UUID format");
            }
            
            if (empty($reason) || strlen($reason) > 500) {
                throw new Exception("Revocation reason must be between 1 and 500 characters");
            }
            
            $cert = $this->getCertificateByUUID($certificateUuid);
            if (!$cert) {
                throw new Exception("Certificate not found");
            }
            
            if ($cert['is_revoked']) {
                throw new Exception("Certificate is already revoked");
            }
            
            $query = "UPDATE certificates SET is_revoked = 1, revocation_reason = ?, 
                      revoked_by = ?, revoked_at = NOW() WHERE uuid = ?";
            $stmt = $this->db->prepare($query);
            $result = $stmt->execute([$reason, $revokedByUuid, $certificateUuid]);
            
            if (!$result) {
                throw new Exception("Failed to revoke certificate");
            }

            // Insert into certificate_revocation_logs (Proposal 5 Phase 4)
            $queryLog = "INSERT INTO certificate_revocation_logs 
                         (uuid, certificate_uuid, revocation_reason, revoked_by, approval_status, approved_by, approved_at, can_appeal, appeal_deadline)
                         VALUES (UUID(), ?, ?, ?, 'approved', ?, NOW(), 1, DATE_ADD(NOW(), INTERVAL 14 DAY))";
            $stmtLog = $this->db->prepare($queryLog);
            $stmtLog->execute([$certificateUuid, $reason, $revokedByUuid, $revokedByUuid]);
            
            $this->logger->warn(
                "Certificate revoked: {$certificateUuid}, Reason: {$reason}", 
                'CertificateManagement'
            );
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error revoking certificate: " . $e->getMessage());
            return false;
        }
    }

    public function verifyCertificateByToken($token) {
        try {
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                $this->logger->warn("Invalid token format attempted: " . substr($token, 0, 16) . "...");
                return false;
            }
            
            $query = "SELECT
                            c.uuid AS certificate_uuid,
                            c.student_uuid,
                            c.ojt_grades_uuid,
                            c.batch_uuid,
                            c.company_uuid,
                            c.certificate_number,
                            c.verification_token,
                            c.file_path,
                            c.hours_completed,
                            c.completion_date,
                            c.generated_by,
                            c.generated_at,
                            c.expires_at,
                            c.is_revoked,
                            c.revocation_reason,
                            c.revoked_by,
                            c.revoked_at,
                            sp.uuid AS student_uuid_profile,
                            sp.student_number AS student_id,
                            sp.first_name,
                            sp.middle_name,
                            sp.last_name,
                            sp.program,
                            sp.program_uuid,
                            sp.year_level,
                            sp.section,
                            p.name AS program_name,
                            comp.name AS company_name,
                            comp.address AS company_address,
                            b.school_year,
                            b.semester,
                            b.start_date AS batch_start_date,
                            b.end_date AS batch_end_date,
                            og.grade_equivalent AS grade,
                            og.weighted_score AS gpa
                      FROM certificates c
                      LEFT JOIN student_profiles sp ON c.student_uuid = sp.uuid
                      LEFT JOIN programs p ON sp.program_uuid = p.uuid
                      LEFT JOIN companies comp ON c.company_uuid = comp.uuid
                      LEFT JOIN batches b ON c.batch_uuid = b.uuid
                      LEFT JOIN ojt_grades og ON c.ojt_grades_uuid = og.uuid
                      WHERE c.verification_token = ? AND c.is_revoked = 0 AND 
                            (c.expires_at IS NULL OR c.expires_at > NOW())
                      LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $this->logger->info("Certificate verified successfully: {$result['certificate_number']}", 'CertificateVerification');
            }
            
            return $result ?: false;
            
        } catch (Exception $e) {
            $this->logger->error("Verification error: " . $e->getMessage());
            return false;
        }
    }
    
    public function getVerificationStatus($token) {
        try {
            if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
                return [
                    'valid' => false,
                    'status' => 'invalid_token',
                    'message' => 'Invalid token format'
                ];
            }
            
            $query = "SELECT uuid, certificate_number, is_revoked, revocation_reason, 
                             expires_at, generated_at
                      FROM certificates WHERE verification_token = ? LIMIT 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$token]);
            $cert = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cert) {
                return [
                    'valid' => false,
                    'status' => 'not_found',
                    'message' => 'Certificate not found'
                ];
            }
            
            if ($cert['is_revoked']) {
                $this->logVerificationAttempt($cert['uuid'], 'revoked');
                return [
                    'valid' => false,
                    'status' => 'revoked',
                    'message' => 'Certificate has been revoked',
                    'reason' => $cert['revocation_reason'],
                    'certificate_number' => $cert['certificate_number']
                ];
            }
            
            if ($cert['expires_at'] && strtotime($cert['expires_at']) < time()) {
                $this->logVerificationAttempt($cert['uuid'], 'expired');
                return [
                    'valid' => false,
                    'status' => 'expired',
                    'message' => 'Certificate has expired',
                    'expired_date' => $cert['expires_at'],
                    'certificate_number' => $cert['certificate_number']
                ];
            }
            
            $this->logVerificationAttempt($cert['uuid'], 'valid');
            return [
                'valid' => true,
                'status' => 'valid',
                'message' => 'Certificate is valid and authentic',
                'certificate_number' => $cert['certificate_number'],
                'issued_date' => $cert['generated_at'],
                'expires_date' => $cert['expires_at']
            ];
            
        } catch (Exception $e) {
            $this->logger->error("Error getting verification status: " . $e->getMessage());
            return [
                'valid' => false,
                'status' => 'error',
                'message' => 'System error occurred'
            ];
        }
    }

    public function logVerificationAttempt($certificateUuid, $result, $source = 'qr_code') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            // Insert into certificate_verifications (Proposal 5 Phase 1)
            $queryCV = "INSERT INTO certificate_verifications (certificate_uuid, ip_address, user_agent, verification_result)
                        VALUES (?, ?, ?, ?)";
            $stmtCV = $this->db->prepare($queryCV);
            $stmtCV->execute([$certificateUuid, $ip, $ua, $result]);

            // Insert into verification_logs (Proposal 5 Phase 4)
            $queryVL = "INSERT INTO verification_logs (uuid, certificate_uuid, ip_address, user_agent, verification_source, verification_result)
                        VALUES (UUID(), ?, ?, ?, ?, ?)";
            $stmtVL = $this->db->prepare($queryVL);
            $stmtVL->execute([$certificateUuid, $ip, $ua, $source, $result]);
            
            return true;
        } catch (Exception $e) {
            $this->logger->error("Error logging verification attempt: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Helper function to get CertificateManager instance
 * @return CertificateManager
 */
function getCertificateManager() {
    static $instance = null;
    if ($instance === null) {
        $instance = new CertificateManager();
    }
    return $instance;
}
