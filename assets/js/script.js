/**
 * Kapital TIF Donation Plugin - Frontend JavaScript - Bootstrap Tab Version
 */

(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        initPaymentForm();
        initFormValidation();
        handlePaymentRedirect();
    });
    
    /**
     * Initialize payment form functionality with Bootstrap tabs
     */
    function initPaymentForm() {
        var $form = $('.tif-payment-form');
        
        if ($form.length === 0) {
            return;
        }
        
        // Tab switching functionality
        var $fizikiTab = $('#fiziki-tab-link');
        var $huquqiTab = $('#huquqi-tab-link');
        var $hiddenInput = $('#hidden_fiziki_huquqi');
        
        // Bootstrap tab event handlers
        $fizikiTab.on('shown.bs.tab', function() {
            $hiddenInput.val('Fiziki şəxs');
            updateRequiredFields('fiziki');
            clearInactiveTabFields('huquqi');
        });
        
        $huquqiTab.on('shown.bs.tab', function() {
            $hiddenInput.val('Hüquqi şəxs');
            updateRequiredFields('huquqi');
            clearInactiveTabFields('fiziki');
        });
        
        // Phone number formatting for both tabs
        formatPhoneNumber($('#fiziki_phone'));
        formatPhoneNumber($('#huquqi_phone'));
        
        // Amount validation for both tabs
        setupAmountValidation($('#fiziki_amount'));
        setupAmountValidation($('#huquqi_amount'));
        
        // Initialize with fiziki tab active
        updateRequiredFields('fiziki');
    }
    
    /**
     * Update required fields based on active tab
     */
    function updateRequiredFields(activeTab) {
        if (activeTab === 'fiziki') {
            // Fiziki şəxs fields
            setFieldRequired('#fiziki_name', true);
            setFieldRequired('#fiziki_phone', true);
            setFieldRequired('#fiziki_amount', true);
            
            // Clear hüquqi şəxs requirements
            setFieldRequired('#huquqi_company_name', false);
            setFieldRequired('#huquqi_name', false);
            setFieldRequired('#huquqi_phone', false);
            setFieldRequired('#huquqi_amount', false);
        } else {
            // Hüquqi şəxs fields
            setFieldRequired('#huquqi_company_name', true);
            setFieldRequired('#huquqi_name', true);
            setFieldRequired('#huquqi_phone', true);
            setFieldRequired('#huquqi_amount', true);
            
            // Clear fiziki şəxs requirements
            setFieldRequired('#fiziki_name', false);
            setFieldRequired('#fiziki_phone', false);
            setFieldRequired('#fiziki_amount', false);
        }
    }
    
    /**
     * Set field as required or not
     */
    function setFieldRequired(selector, required) {
        var $field = $(selector);
        if ($field.length > 0) {
            if (required) {
                $field.attr('required', 'required');
                $field.closest('.form-group').addClass('required');
            } else {
                $field.removeAttr('required');
                $field.closest('.form-group').removeClass('required');
            }
        }
    }
    
    /**
     * Clear fields in inactive tab
     */
    function clearInactiveTabFields(inactiveTab) {
        var fieldsToFlear = [];
        
        if (inactiveTab === 'fiziki') {
            fieldsToFlear = ['#fiziki_name', '#fiziki_phone', '#fiziki_amount'];
        } else {
            fieldsToFlear = ['#huquqi_company_name', '#huquqi_name', '#huquqi_phone', '#huquqi_amount'];
        }
        
        fieldsToFlear.forEach(function(selector) {
            var $field = $(selector);
            if ($field.length > 0) {
                $field.val('').removeClass('error is-invalid');
            }
        });
    }
    
    /**
     * Format phone number input
     */
    function formatPhoneNumber($input) {
        if ($input.length === 0) return;
        
        $input.on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            
            // Limit to 9 digits
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            
            $(this).val(value);
        });
    }
    
    /**
     * Setup amount validation
     */
    function setupAmountValidation($input) {
        if ($input.length === 0) return;
        
        $input.on('input', function() {
            var value = parseFloat($(this).val());
            var min = parseFloat($(this).attr('min'));
            var max = parseFloat($(this).attr('max'));
            
            if (isNaN(value) || value < min || value > max) {
                $(this).addClass('is-invalid error');
            } else {
                $(this).removeClass('is-invalid error');
            }
        });
    }
    
    /**
     * Enhanced form validation for tab structure
     */
    function initFormValidation() {
        var $form = $('.tif-payment-form');
        
        if ($form.length === 0) {
            return;
        }
        
        $form.on('submit', function(e) {
            var isValid = true;
            var activeTab = $('#hidden_fiziki_huquqi').val();
            var $activeTabPane, $requiredFields;
            
            // Clear previous errors
            $('.tif-error-message').remove();
            $('.is-invalid, .error').removeClass('is-invalid error');
            
            // Get active tab fields
            if (activeTab === 'Fiziki şəxs') {
                $activeTabPane = $('#fiziki-tab');
                $requiredFields = $activeTabPane.find('[required]');
            } else {
                $activeTabPane = $('#huquqi-tab');
                $requiredFields = $activeTabPane.find('[required]');
            }
            
            // Validate required fields in active tab only
            $requiredFields.each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value === '') {
                    $field.addClass('is-invalid error');
                    isValid = false;
                }
            });
            
            // Validate phone number in active tab
            var $phoneField = activeTab === 'Fiziki şəxs' ? $('#fiziki_phone') : $('#huquqi_phone');
            var phoneValue = $phoneField.val().replace(/\D/g, '');
            if (phoneValue.length < 9) {
                $phoneField.addClass('is-invalid error');
                isValid = false;
            }
            
            // Validate amount in active tab
            var $amountField = activeTab === 'Fiziki şəxs' ? $('#fiziki_amount') : $('#huquqi_amount');
            var amount = parseFloat($amountField.val());
            var min = parseFloat($amountField.attr('min'));
            var max = parseFloat($amountField.attr('max'));
            
            if (isNaN(amount) || amount < min || amount > max) {
                $amountField.addClass('is-invalid error');
                isValid = false;
            }
            
            // Show error message if validation fails
            if (!isValid) {
                e.preventDefault();
                
                var errorMessage = $('<div class="alert alert-danger tif-error-message"></div>')
                    .text('Zəhmət olmasa bütün sahələri düzgün doldurun.');
                    
                $form.prepend(errorMessage);
                
                // Scroll to first error
                var $firstError = $form.find('.is-invalid, .error').first();
                if ($firstError.length > 0) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
                
                // Show error tab if not active
                if (!$activeTabPane.hasClass('show active')) {
                    if (activeTab === 'Fiziki şəxs') {
                        $('#fiziki-tab-link').tab('show');
                    } else {
                        $('#huquqi-tab-link').tab('show');
                    }
                }
            }
        });
    }
    
    /**
     * Handle payment redirect (unchanged)
     */
    function handlePaymentRedirect() {
        var $redirectDiv = $('#payment-redirecting');
        
        if ($redirectDiv.length === 0) {
            return;
        }
        
        // Show loading spinner
        var $loadingSpinner = $('<div class="tif-loading-spinner"></div>');
        $redirectDiv.append($loadingSpinner);
        
        // Add CSS for spinner
        if ($('.tif-spinner-css').length === 0) {
            $('head').append(`
                <style class="tif-spinner-css">
                .tif-loading-spinner {
                    border: 4px solid #f3f3f3;
                    border-top: 4px solid #007bff;
                    border-radius: 50%;
                    width: 40px;
                    height: 40px;
                    animation: tif-spin 1s linear infinite;
                    margin: 20px auto;
                }
                
                @keyframes tif-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
                </style>
            `);
        }
        
        // Handle form submission failure
        var $paymentForm = $('#payment_form');
        var submitAttempted = false;
        
        $paymentForm.on('submit', function() {
            submitAttempted = true;
        });
        
        // If form hasn't been submitted after 5 seconds, show manual button
        setTimeout(function() {
            if (!submitAttempted) {
                $paymentForm.find('button').show();
                $loadingSpinner.hide();
            }
        }, 5000);
    }
    
    /**
     * Utility function to show notification (unchanged)
     */
    function showNotification(message, type) {
        type = type || 'info';
        
        var $notification = $('<div class="tif-notification tif-notification-' + type + '"></div>')
            .text(message)
            .css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                borderRadius: '5px',
                color: '#fff',
                zIndex: 9999,
                maxWidth: '300px'
            });
        
        // Set background color based on type
        switch (type) {
            case 'success':
                $notification.css('background-color', '#28a745');
                break;
            case 'error':
                $notification.css('background-color', '#dc3545');
                break;
            case 'warning':
                $notification.css('background-color', '#ffc107');
                break;
            default:
                $notification.css('background-color', '#007bff');
        }
        
        $('body').append($notification);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Click to dismiss
        $notification.on('click', function() {
            $(this).fadeOut(300, function() {
                $(this).remove();
            });
        });
    }
    
    /**
     * Add custom CSS for error states and Bootstrap integration
     */
    if ($('.tif-error-css').length === 0) {
        $('head').append(`
            <style class="tif-error-css">
            .tif-payment-form .form-control.is-invalid,
            .tif-payment-form .form-control.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
            }
            
            .tif-payment-form .form-group.required .form-label:after {
                content: " *";
                color: #dc3545;
                font-weight: bold;
            }
            
            .tif-error-message {
                margin-bottom: 20px;
                animation: tif-shake 0.5s ease-in-out;
            }
            
            @keyframes tif-shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
            
            /* Tab styling improvements */
            .nav-segment .nav-link {
                border-radius: 50px;
                padding: 12px 24px;
                font-weight: 600;
                transition: all 0.3s ease;
            }
            
            .nav-segment .nav-link.active {
                background-color: var(--bs-primary);
                border-color: var(--bs-primary);
            }
            
            .tab-content {
                padding-top: 2rem;
            }
            </style>
        `);
    }

})(jQuery);