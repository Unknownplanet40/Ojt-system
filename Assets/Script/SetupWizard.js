import { ToastVersion, ModalVersion, ConfirmVersion, LoadingVersion } from "./CustomSweetAlert.js";
import { MatchsystemThemes, SwalTheme, BGcircleTheme } from "./SystemTheme.js";
import { Errors } from "./ErrorFunctions.js";

MatchsystemThemes(true);
let swalTheme = SwalTheme();
BGcircleTheme(true, "default", "fast");

$(document).ready(function() {
    
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true
        });
    }

    
    const DEBUG_MODE = false; 
    const RANDOM_DEBUG = false; 

    let currentStep = 1;
    const totalSteps = 5;

    
    const $prevBtn = $('#prevBtn');
    const $nextBtn = $('#nextBtn');
    const $finishBtn = $('#finishBtn');
    const $stepProgress = $('#stepProgress');
    const $form = $('#setupForm');

        function updateWizard() {
        
        $('.setup-step').removeClass('active');
        $(`#step${currentStep}`).addClass('active');

        
        $('.step-item').each(function() {
            const stepNum = parseInt($(this).data('step'));
            $(this).removeClass('active completed');
            
            if (stepNum < currentStep) {
                $(this).addClass('completed');
            } else if (stepNum === currentStep) {
                $(this).addClass('active');
            }
        });

        
        const progress = ((currentStep - 1) / (totalSteps - 1)) * 100;
        $stepProgress.css('width', `${progress}%`);

        
        if (currentStep === 1) {
            $prevBtn.addClass('d-none');
        } else {
            $prevBtn.removeClass('d-none');
        }

        if (currentStep === totalSteps) {
            $nextBtn.addClass('d-none');
            $finishBtn.removeClass('d-none');
            prepareReview();
        } else {
            $nextBtn.removeClass('d-none');
            $finishBtn.addClass('d-none');
        }

        
        if (currentStep === 3) {
            validateDependencies();
        } else {
            $nextBtn.prop('disabled', false); 
        }
    }

        function validateDependencies() {
        const items = $('.dependency-item');
        
        if (DEBUG_MODE && RANDOM_DEBUG) {
            
            items.each(function() {
                const $item = $(this);
                if ($item.attr('data-status') !== 'checking') {
                    const isError = Math.random() < 0.4;
                    updateItemUI($item, isError ? 'error' : 'valid');
                }
            });
            updateNextButtonState();
        } else if (DEBUG_MODE) {
            items.each(function() {
                const $item = $(this);
                updateItemUI($item, 'error');
            });
            updateNextButtonState();
        } else {
            
            performRealAudit();
        }
    }

    function updateItemUI($item, status) {
        $item.attr('data-status', status);
        const $icon = $item.find('i.bi');
        $icon.removeClass('d-none bi-check-circle-fill bi-x-circle-fill bi-info-circle-fill text-success text-danger text-info');
        
        if (status === 'error') {
            $icon.addClass('bi-x-circle-fill text-danger');
        } else if (status === 'valid') {
            $icon.addClass('bi-check-circle-fill text-success');
        } else if (status === 'pending') {
            $icon.addClass('bi-info-circle-fill text-info');
        }
    }

    function performRealAudit() {
        $.ajax({
            url: '../../process/setup/SystemAudit',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    Object.keys(response.checks).forEach(key => {
                        const $item = $(`.dependency-item[data-fix="${key}"]`);
                        if ($item.length) {
                            updateItemUI($item, response.checks[key]);
                        }
                    });
                }
                updateNextButtonState();
            },
            error: function() {
                console.error('Failed to perform system audit.');
                updateNextButtonState();
            }
        });
    }

    function updateNextButtonState() {
        const items = $('.dependency-item');
        let hasError = false;
        let stillChecking = false;

        items.each(function() {
            const status = $(this).attr('data-status');
            if (status === 'error') hasError = true;
            if (status === 'checking') stillChecking = true;
        });

        if (hasError) {
            $('#btnFixDependencies').removeClass('d-none');
        } else {
            $('#btnFixDependencies').addClass('d-none');
        }

        if (hasError || stillChecking) {
            $nextBtn.prop('disabled', true);
            if (hasError) {
                $nextBtn.attr('title', 'Please resolve all dependencies to continue');
            }
        } else {
            $nextBtn.prop('disabled', false);
            $nextBtn.removeAttr('title');
        }
    }

        $('#btnRecheck').on('click', function() {
        const $btn = $(this);
        const $icon = $btn.find('i');
        $icon.addClass('spinner-border spinner-border-sm border-0').removeClass('bi-arrow-clockwise');
        
        $('.dependency-item').each(function() {
            const $item = $(this);
            $item.attr('data-status', 'checking');
            const $icon = $item.find('i.bi');
            $icon.addClass('d-none');
            $item.find('.tmp-spinner').remove();
            $item.append('<div class="spinner-border spinner-border-sm text-primary tmp-spinner"></div>');
        });

        setTimeout(() => {
            $icon.removeClass('spinner-border spinner-border-sm border-0').addClass('bi-arrow-clockwise');
            $('.tmp-spinner').remove();
            validateDependencies();
            if (!DEBUG_MODE) {
                ToastVersion(swalTheme, 'Real-time environment audit complete', 'info');
            }
        }, 1200);
    });

        $('#btnFixDependencies').on('click', function() {
        const errors = $('.dependency-item[data-status="error"]');
        let fixContent = '<div class="text-start fix-list-container">';
        
        errors.each(function() {
            const fixType = $(this).data('fix');
            const title = $(this).find('h6').text();
            
            fixContent += `<div class="mb-3 p-3 glass-ui rounded-3">
                <h6 class="fw-bold text-primary small mb-2"><i class="bi bi-tools me-2"></i>Fix ${title}</h6>`;
            
            if (fixType === 'php') {
                fixContent += `<p class="small mb-0 text-muted">Update your PHP version to at least <b>8.0</b>. If using XAMPP, download a newer version or update via Homebrew/Apt.</p>`;
            } else if (fixType === 'phpmailer') {
                fixContent += `<p class="small mb-0 text-muted">Run <code>composer require phpmailer/phpmailer</code> in the root directory.</p>`;
            } else if (fixType === 'mysql') {
                fixContent += `<p class="small mb-0 text-muted">Ensure MySQL service is running in XAMPP/Docker and check if <code>extension=pdo_mysql</code> is enabled in <code>php.ini</code>.</p>`;
            } else if (fixType === 'apache') {
                fixContent += `<p class="small mb-0 text-muted">Restart your Apache server. Check error logs if it fails to start.</p>`;
            } else if (fixType === 'rewrite') {
                fixContent += `<p class="small mb-0 text-muted">Enable <code>mod_rewrite</code> in Apache configuration and set <code>AllowOverride All</code> for the project folder.</p>`;
            } else if (fixType === 'permissions') {
                fixContent += `<p class="small mb-0 text-muted">Set Write Permissions (CHMOD 755/777) for <code>Assets/</code>, <code>functions/</code>, and <code>Storage/</code> directories.</p>`;
            } else if (fixType === 'ratchet') {
                fixContent += `<p class="small mb-0 text-muted">Install Ratchet via Composer: <code>composer require cboden/ratchet</code> and ensure your server supports WebSockets.</p>`;
            } else if (fixType === 'mpdf') {
                fixContent += `<p class="small mb-0 text-muted">Install mPDF via Composer: <code>composer require mpdf/mpdf</code>. Requires <code>mbstring</code> and <code>gd</code> extensions.</p>`;
            } else if (fixType === 'phpoffice') {
                fixContent += `<p class="small mb-0 text-muted">Install PhpSpreadsheet: <code>composer require phpoffice/phpspreadsheet</code> for Excel support.</p>`;
            } else {
                fixContent += `<p class="small mb-0 text-muted">Please contact your server administrator to resolve this requirement.</p>`;
            }
            fixContent += `</div>`;
        });
        fixContent += '</div>';

        ModalVersion(swalTheme, 'Dependency Solutions', '', 'info', 0);
        Swal.update({ html: fixContent });
    });

    function runPHPMailerCheck() {
        const $item = $('#depPHPMailer');
        const $spinner = $('#phpmailerSpinner');
        const $check = $('#phpmailerCheck');
        const $error = $('#phpmailerError');

        setTimeout(() => {
            if (DEBUG_MODE) {
                $item.attr('data-status', 'error');
                $spinner.addClass('d-none');
                $check.addClass('d-none');
                $error.removeClass('d-none');
            } else {
                $item.attr('data-status', 'valid');
                $spinner.addClass('d-none');
                $check.removeClass('d-none');
                $error.addClass('d-none');
            }
            validateDependencies();
        }, 1200);
    }

    function runPermissionCheck() {
        const $item = $('#depPermissions');
        const $spinner = $('#permSpinner');
        const $check = $('#permCheck');
        const $error = $('#permError');

        setTimeout(() => {
            if (DEBUG_MODE) {
                $item.attr('data-status', 'error');
                $spinner.addClass('d-none');
                $check.addClass('d-none');
                $error.removeClass('d-none');
            } else {
                $item.attr('data-status', 'valid');
                $spinner.addClass('d-none');
                $check.removeClass('d-none');
                $error.addClass('d-none');
            }
            validateDependencies();
        }, 1800);
    }

        function prepareReview() {
        const formData = new FormData($form[0]);
        
        $('#rev_school').text(formData.get('school_name') || '--');
        $('#rev_short').text(formData.get('short_title') || '--');
        $('#rev_desc').text(formData.get('description') || '--');
        $('#rev_motto').text(formData.get('school_motto') || '--');
        $('#rev_address').text(formData.get('school_address') || '--');
        
        $('#rev_admin_name').text(formData.get('admin_name') || '--');
        $('#rev_admin_email').text(formData.get('admin_email') || '--');
        
        $('#rev_smtp_host').text(formData.get('smtp_host') || '--');
        $('#rev_smtp_user').text(formData.get('smtp_user') || '--');
    }

        function handleImagePreview(inputId, previewId) {
        $(`#${inputId}`).on('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    $(`#${previewId}`).attr('src', e.target.result);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    handleImagePreview('inputLogoLeft', 'previewLogoLeft');
    handleImagePreview('inputLogoRight', 'previewLogoRight');

        function validateStep() {
        const $currentFields = $(`#step${currentStep} [required]`);
        let isValid = true;

        $currentFields.each(function() {
            if (!this.checkValidity()) {
                $(this).addClass('is-invalid');
                isValid = false;
            } else {
                $(this).removeClass('is-invalid');
            }
        });

        
        if (currentStep === 2 || currentStep === 4) {
            const $email = $(`#step${currentStep} input[type="email"]`);
            if ($email.length && $email.val()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test($email.val())) {
                    $email.addClass('is-invalid');
                    isValid = false;
                }
            }
        }

        
        if (currentStep === 2) {
            const pass = $('input[name="admin_password"]').val();
            const confirm = $('input[name="confirm_password"]').val();
            if (pass && pass.length < 6) {
                $('input[name="admin_password"]').addClass('is-invalid');
                isValid = false;
            }
            if (pass !== confirm) {
                $('input[name="confirm_password"]').addClass('is-invalid');
                isValid = false;
            }
        }

        return isValid;
    }

    
    $nextBtn.on('click', function() {
        if (validateStep()) {
            if (currentStep < totalSteps) {
                currentStep++;
                updateWizard();
            }
        } else {
            ToastVersion(
                swalTheme,
                'Please correct the highlighted errors before proceeding.',
                'warning'
            );
        }
    });

    $prevBtn.on('click', function() {
        if (currentStep > 1) {
            currentStep--;
            updateWizard();
        }
    });

    
    $finishBtn.on('click', function() {
        ConfirmVersion(
            swalTheme,
            'Initialize System?',
            'This will set up your database and administrator account.',
            'question',
            'Yes, Initialize!'
        ).then((result) => {
            if (result.isConfirmed) {
                LoadingVersion(swalTheme, 'Setting up...', 'Please wait while we configure your system.');
                
                const formData = new FormData($form[0]);
                
                $.ajax({
                    url: '../../process/setup/SetupHandler',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            ToastVersion(swalTheme, 'System Initialized Successfully!', 'success');
                            setTimeout(() => window.location.href = 'Login', 2000);
                        } else {
                            ModalVersion(swalTheme, 'Setup Failed', response.message || 'An error occurred during initialization.', 'error');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Could not communicate with the server.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        }
                        ModalVersion(swalTheme, 'System Error', msg, 'error');
                    }
                });
            }
        });
    });

    $form.on('input', 'input, textarea', function() {
        $(this).removeClass('is-invalid');
    });

    
    updateWizard();
});

window.togglePassword = function(id) {
    const input = document.getElementById(id);
    const icon = $(input).next('button').find('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.removeClass('bi-eye').addClass('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.removeClass('bi-eye-slash').addClass('bi-eye');
    }
};
