<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Manila');

if (empty($_SESSION['user_uuid']) || ($_SESSION['user_role'] ?? '') !== 'coordinator') {
    header("Location: ../Login");
    exit;
}

require_once "../../../Assets/SystemInfo.php";
$CurrentPage = "Certificates";
?>
<!doctype html>
<html lang="en">
<head>
    <?php require_once "pagehead.php"; ?>
    <script type="module" src="../../../Assets/Script/dashboardScripts/CoordinatorDashboardScript.js"></script>
    <title><?= $ShortTitle ?> - Certificate Management</title>
    <style>
        .glass-stat-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            position: relative;
            overflow: hidden;
        }
        
        .glass-stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .glass-stat-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                45deg,
                transparent,
                rgba(255, 255, 255, 0.03),
                transparent
            );
            transform: rotate(45deg);
            transition: all 0.6s ease;
        }

        .glass-stat-card:hover::after {
            left: 120%;
        }

        .stat-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
            background: rgba(255, 255, 255, 0.1);
        }

        .stat-blue .stat-icon-wrapper { color: #00d2ff; background: rgba(0, 210, 255, 0.15); box-shadow: 0 0 15px rgba(0, 210, 255, 0.2); }
        .stat-green .stat-icon-wrapper { color: #00ff87; background: rgba(0, 255, 135, 0.15); box-shadow: 0 0 15px rgba(0, 255, 135, 0.2); }
        .stat-red .stat-icon-wrapper { color: #ff007f; background: rgba(255, 0, 127, 0.15); box-shadow: 0 0 15px rgba(255, 0, 127, 0.2); }
        .stat-purple .stat-icon-wrapper { color: #b92b27; background: rgba(185, 43, 39, 0.15); box-shadow: 0 0 15px rgba(185, 43, 39, 0.2); }

        .stat-number {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 4px;
        }

        .stat-label {
            font-size: 13px;
            color: #b0b3b8;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .cert-card {
            background: linear-gradient(145deg, rgba(var(--bs-body-bg-rgb), 0.4), rgba(var(--bs-body-bg-rgb), 0.1));
            backdrop-filter: blur(16px) saturate(140%);
            -webkit-backdrop-filter: blur(16px) saturate(140%);
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.08);
            border-top: 1px solid rgba(255,255,255,0.15); /* Subtly highlight the top edge */
            border-radius: 20px;
            transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            height: 100%;
            position: relative;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
        }

        .cert-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            border-radius: inherit;
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.05);
            pointer-events: none;
            z-index: 1;
        }

        .cert-card:hover {
            transform: translateY(-6px) scale(1.01);
            border-color: rgba(0, 210, 255, 0.3);
            background: linear-gradient(145deg, rgba(var(--bs-body-bg-rgb), 0.5), rgba(var(--bs-body-bg-rgb), 0.15));
            box-shadow: 0 18px 45px rgba(0, 0, 0, 0.3), 0 0 20px rgba(0, 210, 255, 0.1);
        }

        .cert-card-header {
            padding: 16px 20px;
            border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.05);
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.04) 0%, rgba(255, 255, 255, 0) 100%);
            z-index: 2;
            position: relative;
        }

        .cert-card-body {
            padding: 20px;
            flex-grow: 1;
            z-index: 2;
            position: relative;
        }

        .cert-card-footer {
            padding: 16px 20px;
            border-top: 1px solid rgba(var(--bs-body-color-rgb), 0.05);
            background: rgba(var(--bs-body-color-rgb), 0.015);
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 2;
            position: relative;
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.05) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
            border-radius: 30px !important;
            padding: 10px 20px !important;
            transition: all 0.3s ease;
        }

        .glass-input:focus {
            background: rgba(255, 255, 255, 0.08) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1) !important;
        }

        .glass-select {
            background: rgba(20, 20, 20, 0.8) !important;
            border: 1px solid rgba(255, 255, 255, 0.1) !important;
            color: #ffffff !important;
            border-radius: 30px !important;
            padding: 10px 20px !important;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .glass-select option {
            background-color: #1a1a1a !important;
            color: #ffffff !important;
        }

        .glass-select:focus {
            border-color: rgba(255, 255, 255, 0.3) !important;
            box-shadow: 0 0 15px rgba(255, 255, 255, 0.1) !important;
        }

        .glass-modal {
            background: rgba(var(--bs-body-bg-rgb), 0.72) !important;
            backdrop-filter: blur(28px) saturate(160%) !important;
            -webkit-backdrop-filter: blur(28px) saturate(160%) !important;
            border: 1px solid rgba(var(--bs-body-color-rgb), 0.10) !important;
            border-radius: 20px !important;
            color: var(--bs-body-color);
            box-shadow: 0 24px 64px rgba(0,0,0,0.45), inset 0 1px 0 rgba(255,255,255,0.06) !important;
        }

        .glass-modal-header {
            border-bottom: 1px solid rgba(var(--bs-body-color-rgb), 0.07) !important;
            padding: 20px 24px !important;
            background: rgba(var(--bs-body-color-rgb), 0.02);
            border-radius: 20px 20px 0 0;
        }

        .glass-modal-footer {
            border-top: 1px solid rgba(var(--bs-body-color-rgb), 0.07) !important;
            padding: 16px 24px !important;
            background: rgba(var(--bs-body-color-rgb), 0.02);
            border-radius: 0 0 20px 20px;
        }

        .btn-glass-info {
            background: rgba(0, 210, 255, 0.10);
            border: 1px solid rgba(0, 210, 255, 0.35);
            color: #00d2ff;
            backdrop-filter: blur(6px);
            transition: all 0.25s ease;
        }
        .btn-glass-info:hover, .btn-glass-info:focus {
            background: rgba(0, 210, 255, 0.20);
            border-color: rgba(0, 210, 255, 0.60);
            color: #5ee7ff;
            box-shadow: 0 0 16px rgba(0, 210, 255, 0.20);
        }
        .btn-glass-info:active {
            background: rgba(0, 210, 255, 0.28);
            transform: scale(0.97);
        }

        .badge-glow-success {
            background: rgba(0, 255, 135, 0.15);
            color: #00ff87;
            border: 1px solid rgba(0, 255, 135, 0.3);
            box-shadow: 0 0 8px rgba(0, 255, 135, 0.1);
        }

        .badge-glow-danger {
            background: rgba(255, 0, 127, 0.15);
            color: #ff007f;
            border: 1px solid rgba(255, 0, 127, 0.3);
            box-shadow: 0 0 8px rgba(255, 0, 127, 0.1);
        }

        .copy-success {
            animation: bounceScale 0.3s ease;
            color: #00ff87 !important;
        }

        @keyframes bounceScale {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        .student-select-card {
            background: rgba(255,255,255,0.03);
            border: 1.5px solid rgba(255,255,255,0.08);
            border-radius: 14px;
            padding: 14px 16px;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.25,0.8,0.25,1);
            position: relative;
            user-select: none;
        }
        .student-select-card:hover {
            background: rgba(255,255,255,0.06);
            border-color: rgba(0, 210, 255, 0.3);
            transform: translateY(-2px);
        }
        .student-select-card.selected {
            background: rgba(0, 210, 255, 0.08);
            border-color: rgba(0, 210, 255, 0.55);
            box-shadow: 0 0 0 3px rgba(0, 210, 255, 0.12);
        }
        .student-select-card .card-check {
            position: absolute;
            top: 12px;
            right: 12px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            border: 2px solid rgba(255,255,255,0.25);
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            font-size: 11px;
            color: transparent;
        }
        .student-select-card.selected .card-check {
            background: #00d2ff;
            border-color: #00d2ff;
            color: #000;
        }
        .student-avatar-sm {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            background: rgba(0,210,255,0.15);
            color: #00d2ff;
            font-weight: 700;
            font-size: 13px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .eligible-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 10px;
        }
        .hours-pill {
            background: rgba(0,255,135,0.12);
            color: #00ff87;
            border: 1px solid rgba(0,255,135,0.25);
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }
        .select-all-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 10px;
            margin-bottom: 12px;
        }
        .count-badge {
            background: rgba(0,210,255,0.15);
            color: #00d2ff;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body class="login-page" data-role="coordinator" data-page-type="coordinator" data-uuid="<?= $_SESSION['user_uuid'] ?>">
    <div class="circles position-fixed w-100 h-100 overflow-hidden top-0 start-0 z-n1">
        <div class="circle circle1" data-speed="fast"></div>
        <div class="circle circle2" data-speed="normal"></div>
        <div class="circle circle3" data-speed="slow"></div>
    </div>

    <div class="w-100 min-vh-100 d-flex justify-content-center align-items-center z-1 bg-dark bg-opacity-75" id="pageLoader">
        <div class="d-flex flex-column align-items-center">
            <span class="loader"></span>
        </div>
    </div>

    <div class="d-flex flex-nowrap z-3 min-vh-100" id="PageMainContent">
        <main class="d-flex flex-column flex-grow-1 overflow-auto">
            <?php require_once "../../Components/Header_Coordinator.php"; ?>
            
            <div class="container-fluid p-4 w-100" id="dashboardContent">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                    <div>
                        <h4 class="fw-bold mb-1"><i class="bi bi-patch-check-fill text-info me-2"></i>Certificate Management</h4>
                        <p class="text-muted mb-0">View, audit, revoke, and batch generate completion certificates for qualified OJT students.</p>
                    </div>
                    <div>
                        <button class="btn btn-info rounded-pill px-4 py-2 text-white fw-bold shadow-lg" data-bs-toggle="modal" data-bs-target="#generateModal">
                            <i class="bi bi-plus-circle-fill me-2"></i>Generate Certificates
                        </button>
                    </div>
                </div>

                <div class="row g-3 mb-4" id="statisticsRow">
                    <div class="col-6 col-lg-3">
                        <div class="glass-stat-card stat-blue">
                            <div class="stat-icon-wrapper"><i class="bi bi-file-earmark-check-fill"></i></div>
                            <div class="stat-number" id="totalCerts">-</div>
                            <div class="stat-label">Total Issued</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-stat-card stat-green">
                            <div class="stat-icon-wrapper"><i class="bi bi-check-circle-fill"></i></div>
                            <div class="stat-number" id="validCerts">-</div>
                            <div class="stat-label">Active / Valid</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-stat-card stat-red">
                            <div class="stat-icon-wrapper"><i class="bi bi-x-circle-fill"></i></div>
                            <div class="stat-number" id="revokedCerts">-</div>
                            <div class="stat-label">Revoked</div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="glass-stat-card stat-purple">
                            <div class="stat-icon-wrapper"><i class="bi bi-hourglass-split"></i></div>
                            <div class="stat-number" id="avgHours">-</div>
                            <div class="stat-label">Average OJT</div>
                        </div>
                    </div>
                </div>

                <div class="glass-stat-card mb-4 py-3">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-5 position-relative">
                            <input type="text" class="form-control glass-input ps-4" id="searchInput" placeholder="Search by student name, certificate, or company...">
                        </div>
                        <div class="col-6 col-md-3">
                            <select class="form-select glass-select" id="statusFilter">
                                <option value="all">All Certificates</option>
                                <option value="valid">Valid / Active Only</option>
                                <option value="revoked">Revoked Only</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-2">
                            <button class="btn btn-outline-light rounded-pill w-100 py-2" id="searchBtn">
                                <i class="bi bi-search me-2"></i>Filter
                            </button>
                        </div>
                        <div class="col-12 col-md-2">
                            <button class="btn btn-link text-muted text-decoration-none w-100" id="resetBtn">
                                Reset Filters
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3" id="cardsContainer">
                    <div class="col-12 text-center text-muted py-5" id="listLoader">
                        <div class="spinner-border text-info mb-2" role="status"></div>
                        <p class="mb-0">Loading certificates...</p>
                    </div>
                </div>

                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center" id="paginationNav">
                    </ul>
                </nav>
            </div>
        </main>
    </div>

    <div class="modal fade" id="generateModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content glass-modal shadow-2xl">
                <div class="modal-header glass-modal-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="modal-title fw-bold mb-0"><i class="bi bi-award-fill text-info me-2"></i>Generate Completion Certificates</h5>
                        <small class="text-muted">Select eligible students to issue certificates</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="d-flex align-items-start gap-3 mb-4 p-3 rounded-3" style="background:rgba(0,210,255,0.06);border:1px solid rgba(0,210,255,0.15);">
                        <i class="bi bi-info-circle-fill text-info fs-5 mt-1 flex-shrink-0"></i>
                        <div>
                            <div class="fw-semibold text-info mb-1">Eligibility Criteria</div>
                            <p class="mb-0 small text-muted">Only students with <strong class="text-white">fully finalized OJT grades</strong> who have <strong class="text-white">no existing certificate</strong> for their batch are listed here.</p>
                        </div>
                    </div>
                    <div id="generateContent">
                        <div class="text-center text-muted py-5">
                            <div class="spinner-border text-info mb-3" role="status"></div>
                            <p class="mb-0 small">Fetching qualified candidates...</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer glass-modal-footer justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small">Selected:</span>
                        <span class="count-badge" id="selectedCountBadge">0</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-glass-info fw-semibold rounded-pill px-4" id="generateConfirmBtn">
                            <i class="bi bi-award-fill me-2"></i>Issue Certificates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="revokeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content glass-modal shadow-2xl">
                <div class="modal-header glass-modal-header d-flex justify-content-between align-items-center">
                    <h5 class="modal-title fw-bold text-danger"><i class="bi bi-exclamation-triangle-fill me-2"></i>Revoke Certificate</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-danger bg-opacity-10 text-white border-0 rounded-3 mb-4 d-flex align-items-start gap-3">
                        <i class="bi bi-shield-fill-exclamation text-danger fs-5 mt-1"></i>
                        <div>
                            <strong class="text-danger">Irreversible Action</strong>
                            <p class="mb-0 small text-muted">Revoking a certificate marks its token as void in the validation registry database. The student will no longer be able to display or download it.</p>
                        </div>
                    </div>
                    <form id="revokeForm">
                        <div class="mb-3">
                            <label for="revocationReason" class="form-label fw-bold text-white">Reason for Revocation</label>
                            <select class="form-select glass-select w-100" id="revocationReason" required>
                                <option value="">Select a structured reason...</option>
                                <option value="academic_misconduct">Academic / Conduct Misconduct</option>
                                <option value="incomplete_requirements">Incomplete Grade or Hours Discrepancy</option>
                                <option value="fraudulent_activity">Fraudulent Verification Submissions</option>
                                <option value="data_correction">Typo / Re-issue Needed</option>
                                <option value="other">Other Administrative Reason</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="revocationDetails" class="form-label fw-bold text-white">Additional Context</label>
                            <textarea class="form-control glass-input w-100" id="revocationDetails" rows="3" placeholder="Provide extra log descriptions (optional)..." style="border-radius: 12px !important;"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer glass-modal-footer">
                    <button type="button" class="btn btn-outline-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger fw-bold rounded-pill px-4" id="revokeConfirmBtn">Revoke Registry Token</button>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        import { ToastVersion, ModalVersion, ConfirmVersion } from '../../../Assets/Script/CustomSweetAlert.js';

        const CertificateManager = {
            currentPage: 1,
            filters: {
                search: '',
                status: 'all'
            },
            selectedCertificateUuid: null,

            init() {
                this.bindEvents();
                this.loadStatistics();
                this.loadCertificates();

                setTimeout(() => {
                    $('#pageLoader').fadeOut('slow', function() {
                        $(this).addClass('d-none');
                    });
                }, 500);
            },

            bindEvents() {
                const self = this;

                $('#searchBtn').on('click', () => self.handleSearch());
                $('#searchInput').on('keypress', (e) => {
                    if (e.which === 13) self.handleSearch();
                });
                $('#resetBtn').on('click', () => self.resetFilters());

                $('#generateModal').on('show.bs.modal', () => self.loadEligibleStudents());

                $('#generateConfirmBtn').on('click', () => self.bulkGenerate());
                $('#revokeConfirmBtn').on('click', () => self.revokeCertificate());

                $(document).on('click', '.btn-copy-cert', function() {
                    const certNo = $(this).attr('data-cert');
                    const icon = $(this).find('i');
                    navigator.clipboard.writeText(certNo).then(() => {
                        icon.removeClass('bi-files').addClass('bi-check-lg copy-success');
                        ToastVersion('bootstrap-5-dark', 'Certificate # copied!', 'success', 1500, 'top-end');
                        setTimeout(() => {
                            icon.removeClass('bi-check-lg copy-success').addClass('bi-files');
                        }, 2000);
                    });
                });
            },

            handleSearch() {
                this.filters.search = $('#searchInput').val();
                this.filters.status = $('#statusFilter').val();
                this.currentPage = 1;
                this.loadCertificates();
            },

            resetFilters() {
                $('#searchInput').val('');
                $('#statusFilter').val('all');
                this.filters.search = '';
                this.filters.status = 'all';
                this.currentPage = 1;
                this.loadCertificates();
                ToastVersion('bootstrap-5-dark', 'Filters cleared successfully', 'info', 1500, 'top-end');
            },

            loadStatistics() {
                $.ajax({
                    url: '../../../process/coordinator/certificate-management',
                    data: { action: 'statistics' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            const stats = response.statistics;
                            $('#totalCerts').text(stats.totalCertificates || 0);
                            $('#validCerts').text(stats.validCertificates || 0);
                            $('#revokedCerts').text(stats.revokedCertificates || 0);
                            $('#avgHours').text(Math.round(stats.averageHours || 0) + 'h');
                        }
                    }
                });
            },

            loadCertificates() {
                const self = this;
                $('#cardsContainer').html(`
                    <div class="col-12 text-center text-muted py-5" id="listLoader">
                        <div class="spinner-border text-info mb-2" role="status"></div>
                        <p class="mb-0">Loading certificates...</p>
                    </div>
                `);

                $.ajax({
                    url: '../../../process/coordinator/certificate-management',
                    data: {
                        action: 'list',
                        page: self.currentPage,
                        search: self.filters.search,
                        status: self.filters.status
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            self.displayCertificates(response.data);
                            self.displayPagination(response.pagination);
                        } else {
                            $('#cardsContainer').html('<div class="col-12 text-center text-danger py-5">Failed to fetch certificates data.</div>');
                        }
                    },
                    error: function() {
                        $('#cardsContainer').html('<div class="col-12 text-center text-danger py-5">Error connecting to the validation endpoint.</div>');
                    }
                });
            },

            displayCertificates(certificates) {
                if (!certificates || certificates.length === 0) {
                    $('#cardsContainer').html(`
                        <div class="col-12 text-center text-muted py-5">
                            <i class="bi bi-award text-secondary fs-1 mb-3"></i>
                            <h5 class="fw-bold text-white mb-1">No Certificates Found</h5>
                            <p class="text-muted small">Try modifying your filter keyword or generate a new batch.</p>
                        </div>
                    `);
                    return;
                }

                let html = '';
                certificates.forEach(cert => {
                    const isRevoked = parseInt(cert.is_revoked || 0) === 1;
                    const statusText = isRevoked ? 'Revoked' : 'Valid Registry';
                    const badgeClass = isRevoked ? 'badge-glow-danger' : 'badge-glow-success';
                    const issuedDate = new Date(cert.generated_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    });

                    html += `
                        <div class="col-12 col-md-6 col-xxl-4">
                            <div class="cert-card">
                                <div class="cert-card-header d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="bi bi-patch-check-fill text-info fs-5"></i>
                                        <code class="text-white small fw-bold">${cert.certificate_number}</code>
                                    </div>
                                    <button class="btn btn-link text-muted p-0 btn-copy-cert" data-cert="${cert.certificate_number}" title="Copy Certificate Number">
                                        <i class="bi bi-files fs-6"></i>
                                    </button>
                                </div>
                                <div class="cert-card-body">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-circle bg-info bg-opacity-15 text-info d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px; font-size: 16px;">
                                            ${cert.first_name.charAt(0)}${cert.last_name.charAt(0)}
                                        </div>
                                        <div>
                                            <h6 class="text-white fw-bold mb-0">${cert.first_name} ${cert.last_name}</h6>
                                            <span class="text-muted small">Student ID: ${cert.student_id || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-column gap-2 border-top border-bottom border-light border-opacity-5 py-3 mb-2">
                                        <div class="d-flex align-items-center text-muted small gap-2">
                                            <i class="bi bi-building"></i>
                                            <span class="text-truncate text-white">${cert.company_name || 'No Company Assigned'}</span>
                                        </div>
                                        <div class="d-flex align-items-center text-muted small gap-2">
                                            <i class="bi bi-clock-history"></i>
                                            <span>OJT Hours Completed: <strong class="text-info">${cert.hours_completed}h</strong></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="cert-card-footer">
                                    <div class="vstack">
                                        <span class="text-muted" style="font-size: 0.7em">Issued Date</span>
                                        <span class="text-white small">${issuedDate}</span>
                                    </div>
                                    <div class="d-flex gap-2 align-items-center">
                                        <span class="badge ${badgeClass} rounded-pill py-1.5 px-3 fs-7 me-1">${statusText}</span>
                                        
                                        <button class="btn btn-sm btn-info text-white rounded-pill px-3 py-1.5 flex-grow-1 d-flex align-items-center justify-content-center gap-2 shadow-sm" onclick="window.open('/Ojt-system/file_serve.php?type=certificate&cert_uuid=${cert.uuid}&action=view_raw', '_blank')" title="View Certificate Document">
                                            <i class="bi bi-eye-fill"></i> View
                                        </button>
                                        
                                        ${!isRevoked ? `
                                            <button class="btn btn-sm btn-danger rounded-pill px-3 py-1.5 flex-grow-1 d-flex align-items-center justify-content-center gap-2 shadow-sm" onclick="window.showRevokeModalGlobal('${cert.uuid}')" title="Revoke Certificate">
                                                <i class="bi bi-ban"></i> Revoke
                                            </button>
                                        ` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                $('#cardsContainer').html(html);
            },

            displayPagination(pagination) {
                let html = '';
                if (pagination.pages <= 1) {
                    $('#paginationNav').html('');
                    return;
                }

                if (pagination.page > 1) {
                    html += `<li class="page-item"><a class="page-link cursor-pointer rounded-circle me-1 border-0 bg-transparent text-white" onclick="window.goToPageGlobal(${pagination.page - 1})"><i class="bi bi-chevron-left"></i></a></li>`;
                }

                for (let i = 1; i <= pagination.pages; i++) {
                    if (i === 1 || i === pagination.pages || (i >= pagination.page - 1 && i <= pagination.page + 1)) {
                        const active = i === pagination.page ? 'active bg-info text-white font-bold' : 'bg-transparent text-muted';
                        html += `<li class="page-item"><a class="page-link cursor-pointer rounded-circle me-1 border-0 ${active}" style="width: 38px; height: 38px; display: flex; align-items: center; justify-content: center;" onclick="window.goToPageGlobal(${i})">${i}</a></li>`;
                    } else if (i === pagination.page - 2 || i === pagination.page + 2) {
                        html += `<li class="page-item disabled"><span class="page-link bg-transparent border-0 text-muted">...</span></li>`;
                    }
                }

                if (pagination.page < pagination.pages) {
                    html += `<li class="page-item"><a class="page-link cursor-pointer rounded-circle border-0 bg-transparent text-white" onclick="window.goToPageGlobal(${pagination.page + 1})"><i class="bi bi-chevron-right"></i></a></li>`;
                }

                $('#paginationNav').html(html);
            },

            loadEligibleStudents() {
                $('#generateContent').html(`
                    <div class="text-center text-muted py-5">
                        <div class="spinner-border text-info mb-3" role="status"></div>
                        <p class="mb-0 small">Fetching qualified candidates...</p>
                    </div>
                `);
                $('#selectedCountBadge').text('0');

                $.ajax({
                    url: '../../../process/coordinator/certificate-management',
                    data: { action: 'eligible-students' },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.students && response.students.length > 0) {
                            const total = response.students.length;

                            let html = `
                                <div class="select-all-bar">
                                    <div class="d-flex align-items-center gap-2">
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3" id="selectAllBtn">
                                            <i class="bi bi-check-all me-1"></i>Select All
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3" id="deselectAllBtn">
                                            <i class="bi bi-x-lg me-1"></i>Deselect All
                                        </button>
                                    </div>
                                    <span class="text-muted small">${total} eligible candidate${total !== 1 ? 's' : ''}</span>
                                </div>
                                <div class="eligible-grid" id="eligibleGrid">
                            `;

                            response.students.forEach(student => {
                                const initials = (student.first_name.charAt(0) + student.last_name.charAt(0)).toUpperCase();
                                const hours = parseInt(student.hours_completed) || 0;
                                html += `
                                    <div class="student-select-card" data-uuid="${student.student_uuid}">
                                        <div class="card-check"><i class="bi bi-check"></i></div>
                                        <input type="hidden" class="student-checkbox" value="${student.student_uuid}">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="student-avatar-sm">${initials}</div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="fw-bold text-white text-truncate" style="font-size:13px;">${student.first_name} ${student.last_name}</div>
                                                <div class="text-muted" style="font-size:11px;"><code class="text-info" style="font-size:10px;">${student.student_id || 'N/A'}</code></div>
                                            </div>
                                            <span class="hours-pill ms-auto flex-shrink-0">${hours}h</span>
                                        </div>
                                        <div class="d-flex gap-3 mt-2 pt-2" style="border-top:1px solid rgba(255,255,255,0.05);">
                                            <div class="d-flex align-items-center gap-1 text-muted" style="font-size:11px;">
                                                <i class="bi bi-building" style="font-size:10px;"></i>
                                                <span class="text-truncate">${student.company_name || 'No Company'}</span>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            html += `</div>`;
                            $('#generateContent').html(html);

                            $('#generateContent').off('click', '.student-select-card').on('click', '.student-select-card', function() {
                                $(this).toggleClass('selected');
                                const count = $('.student-select-card.selected').length;
                                $('#selectedCountBadge').text(count);
                            });

                            $('#selectAllBtn').off('click').on('click', function() {
                                $('.student-select-card').addClass('selected');
                                $('#selectedCountBadge').text(total);
                            });
                            $('#deselectAllBtn').off('click').on('click', function() {
                                $('.student-select-card').removeClass('selected');
                                $('#selectedCountBadge').text('0');
                            });

                        } else {
                            $('#generateContent').html(`
                                <div class="text-center py-5">
                                    <div class="mb-3" style="width:64px;height:64px;border-radius:16px;background:rgba(255,255,255,0.05);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                        <i class="bi bi-people-fill fs-2 text-secondary"></i>
                                    </div>
                                    <h6 class="text-white fw-bold mb-1">No Eligible Students</h6>
                                    <p class="text-muted small mb-0">Candidates must have finalized OJT grades with no existing certificate for the current batch.</p>
                                </div>
                            `);
                        }
                    },
                    error: function() {
                        $('#generateContent').html(`
                            <div class="text-center text-danger py-5">
                                <i class="bi bi-wifi-off fs-2 mb-2"></i>
                                <p class="mb-0">Error connecting to the server. Please try again.</p>
                            </div>
                        `);
                    }
                });
            },

            bulkGenerate() {
                const self = this;
                const selectedUuids = [];
                $('.student-select-card.selected').each(function() {
                    selectedUuids.push($(this).attr('data-uuid'));
                });

                if (selectedUuids.length === 0) {
                    ToastVersion('bootstrap-5-dark', 'Please select at least one student', 'warning', 2000, 'top-end');
                    return;
                }

                ConfirmVersion('bootstrap-5-dark', 'Generate Certificates?', `You are about to issue certificates for ${selectedUuids.length} selected student(s).`, 'question', 'Generate', 'info').then((result) => {
                    if (result.isConfirmed) {
                        const btn = $('#generateConfirmBtn');
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Generating...');

                        $.ajax({
                            url: '../../../process/coordinator/certificate-management?action=bulk-generate',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                studentUuids: selectedUuids
                            }),
                            dataType: 'json',
                            success: function(response) {
                                btn.prop('disabled', false).text('Generate Selected');
                                if (response.success) {
                                    ModalVersion('bootstrap-5-dark', 'Issuance Complete!', `Successfully generated ${response.generated} completion certificates.`, 'success');
                                    $('#generateModal').modal('hide');
                                    self.loadCertificates();
                                    self.loadStatistics();
                                } else {
                                    ModalVersion('bootstrap-5-dark', 'Failed to Generate', response.error || 'Unknown error occurred.', 'error');
                                }
                            },
                            error: function(xhr) {
                                btn.prop('disabled', false).text('Generate Selected');
                                let errMsg = 'Error generating certificates';
                                try {
                                    const err = JSON.parse(xhr.responseText);
                                    if (err && err.error) errMsg = err.error;
                                } catch(e) {}
                                ModalVersion('bootstrap-5-dark', 'Error', errMsg, 'error');
                            }
                        });
                    }
                });
            },

            revokeCertificate() {
                const self = this;
                const reason = $('#revocationReason').val();
                const details = $('#revocationDetails').val();

                if (!reason) {
                    ToastVersion('bootstrap-5-dark', 'Please select a reason for revocation', 'warning', 2000, 'top-end');
                    return;
                }

                ConfirmVersion('bootstrap-5-dark', 'Confirm Revocation', 'Are you absolutely sure you want to invalidate this registry token? This cannot be undone.', 'warning', 'Yes, Revoke', 'danger').then((result) => {
                    if (result.isConfirmed) {
                        const btn = $('#revokeConfirmBtn');
                        btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Revoking...');

                        $.ajax({
                            url: '../../../process/coordinator/certificate-management?action=revoke',
                            type: 'POST',
                            contentType: 'application/json',
                            data: JSON.stringify({
                                certificate_uuid: self.selectedCertificateUuid,
                                reason: reason + (details ? ' - ' + details : '')
                            }),
                            dataType: 'json',
                            success: function(response) {
                                btn.prop('disabled', false).text('Revoke Registry Token');
                                if (response.success) {
                                    ToastVersion('bootstrap-5-dark', 'Certificate revoked successfully', 'success', 2000, 'top-end');
                                    $('#revokeModal').modal('hide');
                                    $('#revokeForm')[0].reset();
                                    self.loadCertificates();
                                    self.loadStatistics();
                                } else {
                                    ModalVersion('bootstrap-5-dark', 'Revocation Failed', response.error || 'Failed to revoke.', 'error');
                                }
                            },
                            error: function() {
                                btn.prop('disabled', false).text('Revoke Registry Token');
                                ToastVersion('bootstrap-5-dark', 'Error revoking certificate', 'error', 2000, 'top-end');
                            }
                        });
                    }
                });
            }
        };

        window.goToPageGlobal = function(page) {
            CertificateManager.currentPage = page;
            CertificateManager.loadCertificates();
        };

        window.showRevokeModalGlobal = function(uuid) {
            CertificateManager.selectedCertificateUuid = uuid;
            $('#revokeModal').modal('show');
        };

        $(document).ready(() => {
            CertificateManager.init();
        });
    </script>
</body>
</html>
