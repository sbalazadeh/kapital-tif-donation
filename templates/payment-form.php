<?php
/**
 * Payment Form Template - Bootstrap 5 Tab Design
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<!-- Nav Scroller -->
<div class="js-nav-scroller hs-nav-scroller-horizontal">
    <span class="hs-nav-scroller-arrow-prev" style="display: none;">
        <a class="hs-nav-scroller-arrow-link" href="javascript:;">
            <i class="bi-chevron-left"></i>
        </a>
    </span>

    <span class="hs-nav-scroller-arrow-next" style="display: none;">
        <a class="hs-nav-scroller-arrow-link" href="javascript:;">
            <i class="bi-chevron-right"></i>
        </a>
    </span>
    
    <!-- Nav -->
    <ul class="nav nav-segment nav-pills nav-fill mx-auto mb-7" id="paymentTabNav" role="tablist" style="max-width: 50rem;">
        <li class="nav-item" role="presentation">
            <a class="nav-link active" href="#fizikiTab" id="fiziki-tab" data-bs-toggle="tab" data-bs-target="#fizikiTab" role="tab" aria-controls="fizikiTab" aria-selected="true">
                <?php _e('Fiziki şəxs', 'kapital-tif-donation'); ?>
            </a>
        </li>
        <li class="nav-item" role="presentation">
            <a class="nav-link" href="#huquqiTab" id="huquqi-tab" data-bs-toggle="tab" data-bs-target="#huquqiTab" role="tab" aria-controls="huquqiTab" aria-selected="false">
                <?php _e('Hüquqi şəxs', 'kapital-tif-donation'); ?>
            </a>
        </li>
    </ul>
    <!-- End Nav -->
</div>
<!-- End Nav Scroller -->

<!-- Tab Content -->
<div class="tab-content" id="paymentTabContent">
    
    <!-- Fiziki Şəxs Tab -->
    <div class="tab-pane fade show active" id="fizikiTab" role="tabpanel" aria-labelledby="fiziki-tab">
        <form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get" class="tif-payment-form" id="fizikiForm">
            <input type="hidden" name="gotopayment" value="1">
            <input type="hidden" name="fiziki_huquqi" value="Fiziki şəxs">
            
            <div class="mb-5">
                <h4 class="my-7"><?php _e('Fiziki şəxs haqqında informasiya', 'kapital-tif-donation'); ?></h4>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="fiziki_ad_soyad">
                                <?php _e('Fiziki şəxsin Soyadı, Adı, Ata adı', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-person-fill"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="ad_soyad" id="fiziki_ad_soyad"
                                       placeholder="<?php esc_attr_e('Soyad, Ad, Ata adı', 'kapital-tif-donation'); ?>">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="fiziki_telefon">
                                <?php _e('Mobil nömrə', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-telephone-inbound-fill"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="telefon_nomresi" id="fiziki_telefon"
                                       placeholder="+994(xx)xxx-xx-xx">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="fiziki_mebleg">
                                <?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi bi-cash"></i>
                                </span>
                                <input required type="number" step="0.01" 
                                       min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                                       max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                                       class="form-control form-control-lg" 
                                       name="mebleg" id="fiziki_mebleg">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->
                </div>
                <!-- End Row -->
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg">
                    <?php _e('Ödənişə keç', 'kapital-tif-donation'); ?>
                </button>
            </div>
        </form>
    </div>
    <!-- End Fiziki Şəxs Tab -->

    <!-- Hüquqi Şəxs Tab -->
    <div class="tab-pane fade" id="huquqiTab" role="tabpanel" aria-labelledby="huquqi-tab">
        <form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get" class="tif-payment-form" id="huquqiForm">
            <input type="hidden" name="gotopayment" value="1">
            <input type="hidden" name="fiziki_huquqi" value="Hüquqi şəxs">
            
            <div class="mb-5">
                <h4 class="my-7"><?php _e('Hüquqi şəxs haqqında informasiya', 'kapital-tif-donation'); ?></h4>

                <div class="row">
                    <div class="col-lg-12">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="huquqi_ad_soyad">
                                <?php _e('Şəxsin Soyadı, Adı, Ata adı', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-person-fill"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="ad_soyad" id="huquqi_ad_soyad"
                                       placeholder="<?php esc_attr_e('Soyad, Ad, Ata adı', 'kapital-tif-donation'); ?>">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="huquqi_qurum_adi">
                                <?php _e('Qurumun adı', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-building"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="teskilat_adi" id="huquqi_qurum_adi"
                                       placeholder="<?php esc_attr_e('Qurumun adı', 'kapital-tif-donation'); ?>">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="huquqi_voen">
                                <?php _e('Qurumun VÖENİ', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-hash"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="voen" id="huquqi_voen"
                                       placeholder="<?php esc_attr_e('Qurumun VÖENİ', 'kapital-tif-donation'); ?>">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="huquqi_telefon">
                                <?php _e('Əlaqə vasitəsi', 'kapital-tif-donation'); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi-telephone-inbound-fill"></i>
                                </span>
                                <input required type="text" class="form-control form-control-lg" 
                                       name="telefon_nomresi" id="huquqi_telefon"
                                       placeholder="+994(xx)xxx-xx-xx">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->

                    <div class="col-md-6">
                        <!-- Form -->
                        <div class="mb-4">
                            <label class="form-label" for="huquqi_mebleg">
                                <?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?>
                                <span style="color: #dc3545;">*</span>
                            </label>
                            <div class="input-group input-group-merge">
                                <span class="input-group-prepend input-group-text">
                                    <i class="bi bi-cash"></i>
                                </span>
                                <input required type="number" step="0.01" 
                                       min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                                       max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                                       class="form-control form-control-lg" 
                                       name="mebleg" id="huquqi_mebleg">
                            </div>
                        </div>
                        <!-- End Form -->
                    </div>
                    <!-- End Col -->
                </div>
                <!-- End Row -->
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-success btn-lg">
                    <?php _e('Ödənişə keç', 'kapital-tif-donation'); ?>
                </button>
            </div>
        </form>
    </div>
    <!-- End Hüquqi Şəxs Tab -->
    
</div>
<!-- End Tab Content -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number formatting for both forms
    function formatPhoneNumber(input) {
        input.addEventListener('input', function() {
            var value = this.value.replace(/\D/g, '');
            
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
            
            this.value = value;
        });
    }
    
    // Apply phone formatting to both forms
    var fizikiPhone = document.getElementById('fiziki_telefon');
    var huquqiPhone = document.getElementById('huquqi_telefon');
    
    if (fizikiPhone) formatPhoneNumber(fizikiPhone);
    if (huquqiPhone) formatPhoneNumber(huquqiPhone);
    
    // VÖEN formatting (10 digits)
    var voenInput = document.getElementById('huquqi_voen');
    if (voenInput) {
        voenInput.addEventListener('input', function() {
            var value = this.value.replace(/\D/g, '');
            
            // Limit to 10 digits for VÖEN
            if (value.length > 10) {
                value = value.substring(0, 10);
            }
            
            this.value = value;
        });
    }
    
    // Form validation for both forms
    function validateForm(form) {
        var isValid = true;
        var requiredFields = form.querySelectorAll('[required]');
        
        // Clear previous errors
        requiredFields.forEach(function(field) {
            field.classList.remove('is-invalid');
        });
        
        // Remove existing error messages
        var existingErrors = form.querySelectorAll('.invalid-feedback');
        existingErrors.forEach(function(error) {
            error.remove();
        });
        
        // Validate required fields
        requiredFields.forEach(function(field) {
            var value = field.value.trim();
            
            if (value === '') {
                field.classList.add('is-invalid');
                showFieldError(field, '<?php _e("Bu sahə məcburidir", "kapital-tif-donation"); ?>');
                isValid = false;
            }
        });
        
        // Validate phone number
        var phoneField = form.querySelector('input[name="telefon_nomresi"]');
        if (phoneField) {
            var phoneValue = phoneField.value.replace(/\D/g, '');
            if (phoneValue.length < 9) {
                phoneField.classList.add('is-invalid');
                showFieldError(phoneField, '<?php _e("Telefon nömrəsi düzgün formatda deyil", "kapital-tif-donation"); ?>');
                isValid = false;
            }
        }
        
        // Validate VÖEN for hüquqi şəxs
        if (voenInput && form.id === 'huquqiForm') {
            var voenValue = voenInput.value.replace(/\D/g, '');
            if (voenValue.length !== 10) {
                voenInput.classList.add('is-invalid');
                showFieldError(voenInput, '<?php _e("VÖEN 10 rəqəmdən ibarət olmalıdır", "kapital-tif-donation"); ?>');
                isValid = false;
            }
        }
        
        // Validate amount
        var amountField = form.querySelector('input[name="mebleg"]');
        if (amountField) {
            var amount = parseFloat(amountField.value);
            var min = parseFloat(amountField.getAttribute('min'));
            var max = parseFloat(amountField.getAttribute('max'));
            
            if (isNaN(amount) || amount < min || amount > max) {
                amountField.classList.add('is-invalid');
                showFieldError(amountField, '<?php printf(__("Məbləğ %s və %s arasında olmalıdır", "kapital-tif-donation"), "' + min + '", "' + max + '"); ?>');
                isValid = false;
            }
        }
        
        return isValid;
    }
    
    function showFieldError(field, message) {
        var errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    // Add form submit handlers
    var fizikiForm = document.getElementById('fizikiForm');
    var huquqiForm = document.getElementById('huquqiForm');
    
    if (fizikiForm) {
        fizikiForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                
                // Scroll to first error
                var firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    }
    
    if (huquqiForm) {
        huquqiForm.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
                
                // Scroll to first error
                var firstError = this.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        });
    }
});
</script>