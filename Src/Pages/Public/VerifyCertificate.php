<?php
require_once __DIR__ . '/../../../helpers/helpers.php';

$certificateVerifyApiUrl = buildAppUrl('/process/certificate/verify');
$certificateQrCodeUrl = buildAppUrl('/process/certificate/qr-code.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OJT Certificate - OJT Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00d2ff;
            --success-color: #00ff87;
            --danger-color: #ff007f;
            --warning-color: #ffc107;
            --light-bg: rgba(255, 255, 255, 0.03);
            --border-color: rgba(255, 255, 255, 0.1);
        }
        
        body {
            background-color: #0f1115;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(0, 210, 255, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(0, 255, 135, 0.1), transparent 25%);
            color: #ffffff;
            min-height: 100vh;
            padding: 2vh 15px;
            font-family: 'Inter', -apple-system, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
        }
        
        .verification-container {
            width: 100%;
            max-width: 750px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }
        
        .glass-card {
            background: linear-gradient(145deg, rgba(20, 22, 28, 0.6), rgba(20, 22, 28, 0.3));
            backdrop-filter: blur(20px) saturate(150%);
            -webkit-backdrop-filter: blur(20px) saturate(150%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            position: relative;
        }
        
        .glass-header {
            background: linear-gradient(180deg, rgba(0, 210, 255, 0.1) 0%, transparent 100%);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            padding: 25px 30px 20px;
        }
        
        .card-body {
            padding: 25px 30px;
        }
        
        .verification-badge {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px;
            border-radius: 50%;
            position: relative;
        }
        
        .badge-valid {
            background: rgba(0, 255, 135, 0.1);
            color: var(--success-color);
            box-shadow: 0 0 30px rgba(0, 255, 135, 0.2);
            border: 1px solid rgba(0, 255, 135, 0.3);
        }
        
        .badge-invalid {
            background: rgba(255, 0, 127, 0.1);
            color: var(--danger-color);
            box-shadow: 0 0 30px rgba(255, 0, 127, 0.2);
            border: 1px solid rgba(255, 0, 127, 0.3);
        }
        
        .badge-revoked {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.2);
            border: 1px solid rgba(255, 193, 7, 0.3);
        }
        
        .certificate-details {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .detail-row:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .detail-row.full {
            grid-template-columns: 1fr;
        }
        
        .detail-label {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.4);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .detail-value {
            font-size: 16px;
            color: #ffffff;
            font-weight: 500;
            margin-top: 6px;
        }
        
        .qr-code-section {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            margin: 15px 0;
        }
        
        .qr-code-section img {
            max-width: 140px;
            height: auto;
            border: none;
            border-radius: 8px;
            padding: 8px;
            background: #ffffff;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-glass-primary {
            background: rgba(0, 210, 255, 0.15);
            color: #00d2ff;
            border: 1px solid rgba(0, 210, 255, 0.3);
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        .btn-glass-primary:hover {
            background: rgba(0, 210, 255, 0.25);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(0, 210, 255, 0.2);
        }
        
        .btn-glass-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        .btn-glass-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.03) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
            padding: 10px 15px !important;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.06) !important;
            border-color: rgba(0, 210, 255, 0.5) !important;
            box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1) !important;
        }
        .glass-input::placeholder {
            color: rgba(255, 255, 255, 0.3);
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 40px;
        }
        
        .error-message {
            background: rgba(255, 0, 127, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(255, 0, 127, 0.2);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 0;
            backdrop-filter: blur(5px);
        }
        
        .revoked-banner {
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
            border: 1px solid rgba(255, 193, 7, 0.2);
            padding: 15px 20px;
            border-radius: 12px;
            margin: 20px 0;
            backdrop-filter: blur(5px);
        }

        .verification-timestamp {
            font-size: 11px;
            color: rgba(255, 255, 255, 0.3);
            text-align: center;
            margin-top: 15px;
        }
        
        .header-icon {
            font-size: 36px;
            margin-bottom: 10px;
            color: #00d2ff;
            filter: drop-shadow(0 0 15px rgba(0, 210, 255, 0.3));
        }
        
        @media (max-width: 768px) {
            .card-body, .glass-header { padding: 25px; }
            .detail-row { grid-template-columns: 1fr; gap: 15px; }
            .action-buttons { flex-direction: column; }
        }

        @media print {
            @page {
                margin: 1cm;
                size: portrait;
            }
            body {
                background: white !important;
                color: black !important;
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                min-height: auto !important;
            }
            .verification-container {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
                display: block !important;
            }
            .glass-card {
                background: white !important;
                border: 1px solid #ccc !important;
                border-radius: 8px !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                -webkit-backdrop-filter: none !important;
                page-break-inside: avoid !important;
            }
            .glass-header {
                background: #f8f9fa !important;
                border-bottom: 2px solid #ddd !important;
                padding: 10px !important;
            }
            .glass-header h3 {
                color: #333 !important;
                font-size: 1.5rem !important;
            }
            .header-icon {
                color: #333 !important;
                filter: none !important;
                font-size: 24px !important;
                margin-bottom: 5px !important;
            }
            .card-body {
                padding: 15px !important;
            }
            .verification-badge {
                width: 60px !important;
                height: 60px !important;
                font-size: 30px !important;
                margin-bottom: 10px !important;
            }
            .certificate-details {
                background: white !important;
                border: 1px solid #ddd !important;
                padding: 15px !important;
                margin: 15px 0 !important;
            }
            .detail-label {
                color: #555 !important;
                font-size: 10px !important;
            }
            .detail-value {
                color: #000 !important;
                font-size: 14px !important;
                margin-top: 2px !important;
            }
            .detail-row {
                border-bottom-color: #eee !important;
                padding-bottom: 8px !important;
                margin-bottom: 8px !important;
                gap: 10px !important;
            }
            .qr-code-section {
                background: white !important;
                border: 1px solid #ddd !important;
                padding: 10px !important;
                margin: 10px 0 !important;
            }
            .qr-code-section img {
                border: 1px solid #ccc !important;
                max-width: 100px !important;
                padding: 5px !important;
            }
            .action-buttons, 
            .search-section,
            .verification-container > .text-center:first-child,
            .verification-container > .text-center:last-child {
                display: none !important;
            }
            #resultTitle, #resultMessage, h5, h6 {
                color: #000 !important;
            }
            .verification-timestamp {
                color: #666 !important;
                margin-top: 10px !important;
            }
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="verification-container d-flex flex-column">
        <div class="text-center text-white mb-3">
            <h2 class="fw-bold" style="letter-spacing: -1px;"><i class="bi bi-patch-check-fill text-info me-2"></i> Certificate Verification</h2>
            <p class="text-white-50 small mb-0">Verify the authenticity and integrity of OJT certificates</p>
        </div>
        
        <div class="glass-card">
            <div class="glass-header text-center">
                <div class="header-icon">
                    <i class="fas fa-search-location"></i>
                </div>
                <h3 class="mb-0 fw-bold">Query Registry</h3>
            </div>
            
            <div class="card-body">
                <div class="search-section mb-0" id="searchSection">
                    <label for="tokenInput" class="form-label text-white-50 mb-2 small">Enter Verification Token or Scan QR Code</label>
                    <div class="d-flex gap-2 flex-column flex-md-row">
                        <input type="text" class="form-control glass-input flex-grow-1" id="tokenInput" placeholder="Paste verification token..." autocomplete="off">
                        <button class="btn btn-glass-primary px-4 py-2 rounded-3 fw-bold shadow-sm" id="verifyBtn" style="white-space: nowrap;">
                            <i class="fas fa-check me-1"></i> Verify
                        </button>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-info-circle"></i> You can also scan the QR code from the certificate using your device's camera
                    </small>
                </div>
                
                <div class="loading-spinner" id="loadingSpinner">
                    <div class="spinner-border" role="status" style="color: var(--primary-color);">
                        <span class="visually-hidden">Verifying...</span>
                    </div>
                    <p class="mt-3 text-muted">Verifying certificate...</p>
                </div>
                
                <div id="errorMessage" style="display: none;"></div>
                
                <div id="resultSection" style="display: none;">
                    <div class="text-center">
                        <div id="verificationBadge" class="verification-badge"></div>
                        <h4 id="resultTitle" class="fw-bold mb-1"></h4>
                        <p id="resultMessage" class="text-muted small mb-0"></p>
                    </div>
                    
                    <div id="revokedBanner" style="display: none;"></div>
                    
                    <div class="certificate-details" id="certificateDetails" style="display: none;">
                        <h5 class="mb-3">
                            <i class="fas fa-award"></i> Certificate Information
                        </h5>
                        
                        <div class="detail-row">
                            <div>
                                <div class="detail-label">Certificate Number</div>
                                <div class="detail-value" id="certNumber"></div>
                            </div>
                            <div>
                                <div class="detail-label">Issued Date</div>
                                <div class="detail-value" id="issuedDate"></div>
                            </div>
                        </div>
                        
                        <div class="detail-row full">
                            <div>
                                <div class="detail-label">Student Name</div>
                                <div class="detail-value" id="studentName"></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div>
                                <div class="detail-label">Student ID</div>
                                <div class="detail-value" id="studentId"></div>
                            </div>
                            <div>
                                <div class="detail-label">Company</div>
                                <div class="detail-value" id="company"></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div>
                                <div class="detail-label">Hours Completed</div>
                                <div class="detail-value" id="hours"></div>
                            </div>
                            <div>
                                <div class="detail-label">Completion Date</div>
                                <div class="detail-value" id="completionDate"></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div>
                                <div class="detail-label">School Year</div>
                                <div class="detail-value" id="schoolYear"></div>
                            </div>
                            <div>
                                <div class="detail-label">Semester</div>
                                <div class="detail-value" id="semester"></div>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div>
                                <div class="detail-label">Final Grade</div>
                                <div class="detail-value" id="grade"></div>
                            </div>
                            <div>
                                <div class="detail-label">GPA</div>
                                <div class="detail-value" id="gpa"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="qr-code-section" id="qrSection" style="display: none;">
                        <h6 class="mb-3">
                            <i class="fas fa-qrcode"></i> Scan to Verify
                        </h6>
                        <img id="qrImage" src="" alt="Certificate QR Code">
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-glass-secondary py-2 rounded-3 fw-bold flex-grow-1" id="printBtn">
                            <i class="fas fa-print me-1"></i> Print Report
                        </button>
                        <button class="btn btn-glass-primary py-2 rounded-3 fw-bold flex-grow-1" id="verifyAnotherBtn">
                            <i class="fas fa-redo me-1"></i> Verify Another
                        </button>
                    </div>
                </div>
                
                <div class="verification-timestamp" id="verificationTime"></div>
            </div>
        </div>
        
        <div class="text-center text-white mt-4">
            <p class="small">
                <i class="fas fa-shield-alt"></i> This is an official OJT Management System verification page
            </p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const CERTIFICATE_VERIFY_URL = <?= json_encode($certificateVerifyApiUrl) ?>;
            const CERTIFICATE_QR_URL = <?= json_encode($certificateQrCodeUrl) ?>;
            const CERTIFICATE_PDF_URL = <?= json_encode(buildAppUrl('/process/certificate/export_verification_pdf.php')) ?>;

            const urlParams = new URLSearchParams(window.location.search);
            const tokenFromUrl = urlParams.get('token');
            
            if (tokenFromUrl) {
                $('#tokenInput').val(tokenFromUrl);
                verifyToken(tokenFromUrl);
            }
            
            $('#verifyBtn').on('click', function() {
                const token = $('#tokenInput').val().trim();
                if (!token) {
                    showError('Please enter a verification token');
                    return;
                }
                verifyToken(token);
            });
            
            $('#tokenInput').on('keypress', function(e) {
                if (e.which === 13) {
                    $('#verifyBtn').click();
                }
            });
            
            $('#verifyAnotherBtn').on('click', function() {
                $('#tokenInput').val('').focus();
                $('#resultSection').fadeOut(function() {
                    $('#searchSection').fadeIn();
                });
            });
            
            $('#printBtn').on('click', function() {
                const token = $('#tokenInput').val().trim();
                if (!token) return;
                
                window.open(CERTIFICATE_PDF_URL + '?token=' + encodeURIComponent(token), '_blank');
            });
            
            $('#downloadBtn').on('click', function() {
                alert('Download functionality would be implemented here');
            });
            
            function verifyToken(token) {
                $('#searchSection').fadeOut();
                $('#resultSection').fadeOut();
                $('#errorMessage').fadeOut();
                $('#loadingSpinner').fadeIn();
                
                $.ajax({
                    url: CERTIFICATE_VERIFY_URL,
                    type: 'GET',
                    data: { token: token },
                    dataType: 'json',
                    timeout: 10000,
                    success: function(response) {
                        if(response.success && response.data) {
                            displayCertificate(response.data);
                            $('#loadingSpinner').fadeOut();
                            $('#resultSection').fadeIn();
                        } else {
                            $('#loadingSpinner').fadeOut();
                            showError('Certificate not found or invalid.');
                            $('#searchSection').fadeIn();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#loadingSpinner').fadeOut();
                        showError('Certificate not found or invalid. Please check the token and try again.');
                        $('#searchSection').fadeIn();
                    }
                });
            }
            
            function displayCertificate(data) {
                const badge = $('#verificationBadge');
                badge.html('<i class="fas fa-check-circle"></i>')
                    .removeClass('badge-invalid badge-revoked')
                    .addClass('badge-valid');
                
                $('#resultTitle').text('Certificate Verified ✓');
                $('#resultMessage').text('This certificate is authentic and valid');
                
                if (data.certificate.isRevoked) {
                    badge.html('<i class="fas fa-exclamation-triangle"></i>')
                        .removeClass('badge-valid')
                        .addClass('badge-revoked');
                    
                    $('#resultTitle').text('Certificate Revoked');
                    $('#resultMessage').text('This certificate has been revoked');
                    
                    const revokedBanner = $('<div class="revoked-banner">')
                        .html('<strong><i class="fas fa-ban"></i> Revocation Notice</strong><br>' +
                              'Reason: ' + (data.certificate.revocationReason || 'Not specified'));
                    $('#revokedBanner').html(revokedBanner).show();
                } else {
                    $('#revokedBanner').hide();
                }
                
                $('#certNumber').text(data.certificate.number);
                $('#issuedDate').text(formatDate(data.certificate.issuedDate));
                $('#studentName').text(data.student.name);
                $('#studentId').text(data.student.id);
                $('#company').text(data.program.company);
                $('#hours').text(data.program.hoursCompleted + ' hours');
                $('#completionDate').text(formatDate(data.certificate.completionDate));
                $('#schoolYear').text(data.program.schoolYear);
                $('#semester').text(data.program.semester);
                $('#grade').text(data.academic.grade);
                
                if(data.academic.gpa) {
                    $('#gpa').text(parseFloat(data.academic.gpa).toFixed(2));
                } else {
                    $('#gpa').text('N/A');
                }
                
                $('#certificateDetails').show();
                
                $('#qrImage').attr('src', CERTIFICATE_QR_URL + '?token=' + encodeURIComponent($('#tokenInput').val()) + '&format=png');
                $('#qrSection').show();
                
                $('#verificationTime').text('Verified at: ' + new Date().toLocaleString());
            }
            
            function showError(message) {
                $('#errorMessage')
                    .html('<div class="error-message"><i class="fas fa-exclamation-circle"></i> ' + message + '</div>')
                    .fadeIn();
            }
            
            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
            }
        });
    </script>
</body>
</html>
