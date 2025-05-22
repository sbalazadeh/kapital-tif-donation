/**
 * Kapital TIF Donation Plugin - Frontend JavaScript
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
     * Initialize payment form functionality
     */
    function initPaymentForm() {
        var $form = $('.tif-payment-form');
        
        if ($form.length === 0) {
            return;
        }
        
        // Company type toggle
        var $fizikiRadio = $('#fiziki');
        var $huquqiRadio = $('#huquqi');
        var $teskilatField = $('.teskilat-adi-field');
        var $teskilatInput = $('#teskilatAdiLabel');
        
        function toggleCompanyField() {
            if ($huquqiRadio.is(':checked')) {
                $teskilatField.show();
                $teskilatInput.attr('required', 'required');
                $teskilatField.addClass('required');
            } else {
                $teskilatField.hide();
                $teskilatInput.removeAttr('required');
                $teskilatField.removeClass('required');
            }
        }
        
        $fizikiRadio.on('change', toggleCompanyField);
        $huquqiRadio.on('change', toggleCompanyField);
        
        // Initialize on page load
        toggleCompanyField();
        
        // Phone number formatting
        var $phoneInput = $('#telefon_nomresi');
        $phoneInput.on('input', function() {
            var value = $(this).val().replace(/\D/g, '');
            
            // Format phone number
            if (value.length > 0) {
                if (value.length <= 3) {
                    value = value;
                } else if (value.length <= 5) {
                    value = value.substring(0, 3) + value.substring(3);
                } else if (value.length <= 7) {
                    value = value.substring(0, 3) + value.substring(3, 5) + value.substring(5);
                } else {
                    value = value.substring(0, 3) + value.substring(3, 5) + value.substring(5, 7) + value.substring(7, 9);
                }
                
                // Limit to 9 digits
                if (value.length > 9) {
                    value = value.substring(0, 9);
                }
            }
            
            $(this).val(value);
        });
        
        // Amount input validation
        var $amountInput = $('#mebleg');
        $amountInput.on('input', function() {
            var value = parseFloat($(this).val());
            var min = parseFloat($(this).attr('min'));
            var max = parseFloat($(this).attr('max'));
            
            if (value < min) {
                $(this).addClass('error');
            } else if (value > max) {
                $(this).addClass('error');
            } else {
                $(this).removeClass('error');
            }
        });
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        var $form = $('.tif-payment-form');
        
        if ($form.length === 0) {
            return;
        }
        
        $form.on('submit', function(e) {
            var isValid = true;
            var $requiredFields = $form.find('[required]');
            
            // Clear previous errors
            $requiredFields.removeClass('error');
            $('.tif-error-message').remove();
            
            // Validate required fields
            $requiredFields.each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (value === '') {
                    $field.addClass('error');
                    isValid = false;
                }
            });
            
            // Validate phone number
            var phoneValue = $('#telefon_nomresi').val().replace(/\D/g, '');
            if (phoneValue.length < 9) {
                $('#telefon_nomresi').addClass('error');
                isValid = false;
            }
            
            // Validate amount
            var amount = parseFloat($('#mebleg').val());
            var min = parseFloat($('#mebleg').attr('min'));
            var max = parseFloat($('#mebleg').attr('max'));
            
            if (isNaN(amount) || amount < min || amount > max) {
                $('#mebleg').addClass('error');
                isValid = false;
            }
            
            // Show error message if validation fails
            if (!isValid) {
                e.preventDefault();
                
                var errorMessage = $('<div class="tif-error-message alert alert-danger"></div>')
                    .text('Zəhmət olmasa bütün sahələri düzgün doldurun.');
                    
                $form.prepend(errorMessage);
                
                // Scroll to first error
                var $firstError = $form.find('.error').first();
                if ($firstError.length > 0) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
            }
        });
    }
    
    /**
     * Handle payment redirect
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
     * Utility function to show notification
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
     * Add custom CSS for error states
     */
    if ($('.tif-error-css').length === 0) {
        $('head').append(`
            <style class="tif-error-css">
            .tif-payment-form .form-control.error {
                border-color: #dc3545;
                box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
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
            </style>
        `);
    }

})(jQuery);