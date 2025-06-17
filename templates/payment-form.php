<?php
/**
 * Payment Form Template - Flat Tab Design
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tif-donation-container">
    <form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get" class="tif-payment-form">
        <input type="hidden" name="gotopayment" value="1">
        <input type="hidden" name="fiziki_huquqi" id="hidden_fiziki_huquqi" value="Fiziki şəxs">
        
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
            <ul class="nav nav-segment nav-pills nav-fill mx-auto mb-7" id="donationTypeTab" role="tablist" style="max-width: 50rem;">
                <li class="nav-item" role="presentation">
                    <a class="nav-link active" href="#fiziki-tab" id="fiziki-tab-link" data-bs-toggle="tab" data-bs-target="#fiziki-tab" role="tab" aria-controls="fiziki-tab" aria-selected="true">
                        <?php _e('Fiziki şəxs', 'kapital-tif-donation'); ?>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link" href="#huquqi-tab" id="huquqi-tab-link" data-bs-toggle="tab" data-bs-target="#huquqi-tab" role="tab" aria-controls="huquqi-tab" aria-selected="false">
                        <?php _e('Hüquqi şəxs', 'kapital-tif-donation'); ?>
                    </a>
                </li>
            </ul>
            <!-- End Nav -->
        </div>
        <!-- End Nav Scroller -->
    
    <!-- Tab Content -->
    <div class="tab-content" id="donationTypeTabContent">
        
        <!-- Fiziki Şəxs Tab -->
        <div class="tab-pane fade show active" id="fiziki-tab" role="tabpanel" aria-labelledby="fiziki-tab-link">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    
                    <div class="form-group required mb-4">
                        <label for="fiziki_name" class="input-label form-label">
                            <?php _e('Ad və soyad', 'kapital-tif-donation'); ?>
                        </label>
                        <input required type="text" class="form-control" name="ad_soyad" id="fiziki_name" 
                               placeholder="<?php esc_attr_e('Adınız Soyadınız', 'kapital-tif-donation'); ?>">
                    </div>
                    
                    <div class="form-group required mb-4">
                        <label for="fiziki_phone" class="input-label form-label">
                            <?php _e('Telefon nömrəniz', 'kapital-tif-donation'); ?>
                        </label>
                        <input required type="text" class="form-control" name="telefon_nomresi" id="fiziki_phone" 
                               placeholder="50200XXXX">
                    </div>
                    
                    <div class="form-group required mb-4">
                        <label for="fiziki_amount" class="input-label form-label">
                            <?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?>
                        </label>
                        <input required type="number" step="0.01" 
                               min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                               max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                               class="form-control" name="mebleg" id="fiziki_amount">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <?php _e('Ödənişə keç', 'kapital-tif-donation'); ?>
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        <!-- End Fiziki Şəxs Tab -->

        <!-- Hüquqi Şəxs Tab -->
        <div class="tab-pane fade" id="huquqi-tab" role="tabpanel" aria-labelledby="huquqi-tab-link">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    
                    <div class="form-group required mb-4">
                        <label for="huquqi_company_name" class="input-label form-label">
                            <?php _e('Təşkilatın adı', 'kapital-tif-donation'); ?>
                        </label>
                        <input type="text" class="form-control" name="teskilat_adi" id="huquqi_company_name" 
                               placeholder="<?php esc_attr_e('Təşkilatın adı', 'kapital-tif-donation'); ?>">
                    </div>
                    
                    <div class="form-group required mb-4">
                        <label for="huquqi_name" class="input-label form-label">
                            <?php _e('Əlaqədar şəxsin adı və soyadı', 'kapital-tif-donation'); ?>
                        </label>
                        <input type="text" class="form-control" name="ad_soyad" id="huquqi_name" 
                               placeholder="<?php esc_attr_e('Əlaqədar şəxsin adı və soyadı', 'kapital-tif-donation'); ?>">
                    </div>
                    
                    <div class="form-group required mb-4">
                        <label for="huquqi_phone" class="input-label form-label">
                            <?php _e('Telefon nömrəniz', 'kapital-tif-donation'); ?>
                        </label>
                        <input type="text" class="form-control" name="telefon_nomresi" id="huquqi_phone" 
                               placeholder="50200XXXX">
                    </div>
                    
                    <div class="form-group required mb-4">
                        <label for="huquqi_amount" class="input-label form-label">
                            <?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?>
                        </label>
                        <input type="number" step="0.01" 
                               min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
                               max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
                               class="form-control" name="mebleg" id="huquqi_amount">
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <?php _e('Ödənişə keç', 'kapital-tif-donation'); ?>
                        </button>
                    </div>
                    
                </div>
            </div>
        </div>
        <!-- End Hüquqi Şəxs Tab -->
        
    </div>
    <!-- End Tab Content -->
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching və form field management
    var fizikiTab = document.getElementById('fiziki-tab-link');
    var huquqiTab = document.getElementById('huquqi-tab-link');
    var hiddenInput = document.getElementById('hidden_fiziki_huquqi');
    
    // Tab-lar arasında keçid
    fizikiTab.addEventListener('click', function() {
        hiddenInput.value = 'Fiziki şəxs';
        updateRequiredFields('fiziki');
    });
    
    huquqiTab.addEventListener('click', function() {
        hiddenInput.value = 'Hüquqi şəxs';
        updateRequiredFields('huquqi');
    });
    
    // Required field-ləri yenilə
    function updateRequiredFields(type) {
        if (type === 'fiziki') {
            // Fiziki şəxs üçün required field-lər
            setFieldRequired('fiziki_name', true);
            setFieldRequired('fiziki_phone', true);
            setFieldRequired('fiziki_amount', true);
            setFieldRequired('huquqi_company_name', false);
            setFieldRequired('huquqi_name', false);
            setFieldRequired('huquqi_phone', false);
            setFieldRequired('huquqi_amount', false);
            
            // Clear hüquqi fields
            clearFields(['huquqi_company_name', 'huquqi_name', 'huquqi_phone', 'huquqi_amount']);
        } else {
            // Hüquqi şəxs üçün required field-lər
            setFieldRequired('huquqi_company_name', true);
            setFieldRequired('huquqi_name', true);
            setFieldRequired('huquqi_phone', true);
            setFieldRequired('huquqi_amount', true);
            setFieldRequired('fiziki_name', false);
            setFieldRequired('fiziki_phone', false);
            setFieldRequired('fiziki_amount', false);
            
            // Clear fiziki fields
            clearFields(['fiziki_name', 'fiziki_phone', 'fiziki_amount']);
        }
    }
    
    function setFieldRequired(fieldId, required) {
        var field = document.getElementById(fieldId);
        if (field) {
            if (required) {
                field.setAttribute('required', 'required');
            } else {
                field.removeAttribute('required');
            }
        }
    }
    
    function clearFields(fieldIds) {
        fieldIds.forEach(function(fieldId) {
            var field = document.getElementById(fieldId);
            if (field) {
                field.value = '';
                field.classList.remove('error', 'is-invalid');
            }
        });
    }
    
    // Phone number formatting (həm fiziki həm də hüquqi üçün)
    function formatPhoneNumber(input) {
        input.addEventListener('input', function() {
            var value = this.value.replace(/\D/g, '');
            
            if (value.length > 9) {
                value = value.substring(0, 9);
            }
            
            this.value = value;
        });
    }
    
    formatPhoneNumber(document.getElementById('fiziki_phone'));
    formatPhoneNumber(document.getElementById('huquqi_phone'));
    
    // Amount validation (həm fiziki həm də hüquqi üçün)
    function setupAmountValidation(input) {
        input.addEventListener('input', function() {
            var value = parseFloat(this.value);
            var min = parseFloat(this.getAttribute('min'));
            var max = parseFloat(this.getAttribute('max'));
            
            if (value < min || value > max) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    setupAmountValidation(document.getElementById('fiziki_amount'));
    setupAmountValidation(document.getElementById('huquqi_amount'));
    
    // Initialize
    updateRequiredFields('fiziki');
});
</script>