<?php
/**
 * Payment Form Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<form action="<?php echo esc_url(home_url('/donation/')); ?>" method="get" class="tif-payment-form">
    <input type="hidden" name="gotopayment" value="1">
    
    <div class="form-check-group">
        <div class="form-check">
            <input class="form-check-input" type="radio" name="fiziki_huquqi" id="fiziki" value="Fiziki şəxs" checked>
            <label class="form-check-label" for="fiziki">
                <?php _e('Fiziki şəxs', 'kapital-tif-donation'); ?>
            </label>
        </div>
        <div class="form-check">
            <input class="form-check-input" type="radio" name="fiziki_huquqi" id="huquqi" value="Hüquqi şəxs">
            <label class="form-check-label" for="huquqi">
                <?php _e('Hüquqi şəxs', 'kapital-tif-donation'); ?>
            </label>
        </div>
    </div>
    
    <div class="form-group required">
        <label for="cardNameLabel" class="input-label">
            <?php _e('Ad və soyad', 'kapital-tif-donation'); ?>
        </label>
        <input required type="text" class="form-control" name="ad_soyad" id="cardNameLabel" 
               placeholder="<?php esc_attr_e('Adınız Soyadınız', 'kapital-tif-donation'); ?>">
    </div>
    
    <div class="form-group teskilat-adi-field" style="display:none;">
        <label for="teskilatAdiLabel" class="input-label">
            <?php _e('Təşkilatın adı', 'kapital-tif-donation'); ?>
        </label>
        <input type="text" class="form-control" name="teskilat_adi" id="teskilatAdiLabel" 
               placeholder="<?php esc_attr_e('Təşkilatın adı', 'kapital-tif-donation'); ?>">
    </div>
    
    <div class="form-group required">
        <label for="telefon_nomresi" class="input-label">
            <?php _e('Telefon nömrəniz', 'kapital-tif-donation'); ?>
        </label>
        <input required type="text" class="form-control" name="telefon_nomresi" id="telefon_nomresi" 
               placeholder="50200XXXX">
    </div>
    
    <div class="form-group required">
        <label for="mebleg" class="input-label">
            <?php printf(__('Məbləğ (%s)', 'kapital-tif-donation'), $config['payment']['currency']); ?>
        </label>
        <input required type="number" step="0.01" 
               min="<?php echo esc_attr($config['payment']['min_amount']); ?>" 
               max="<?php echo esc_attr($config['payment']['max_amount']); ?>" 
               class="form-control" name="mebleg" id="mebleg">
    </div>
    
    <button type="submit" class="btn btn-primary">
        <?php _e('Ödənişə keç', 'kapital-tif-donation'); ?>
    </button>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fizikiRadio = document.getElementById('fiziki');
    var huquqiRadio = document.getElementById('huquqi');
    var teskilatField = document.querySelector('.teskilat-adi-field');
    var teskilatInput = document.getElementById('teskilatAdiLabel');
    
    function toggleTeskilatField() {
        if (huquqiRadio.checked) {
            teskilatField.style.display = 'block';
            teskilatInput.setAttribute('required', 'required');
            teskilatField.classList.add('required');
        } else {
            teskilatField.style.display = 'none';
            teskilatInput.removeAttribute('required');
            teskilatField.classList.remove('required');
        }
    }
    
    fizikiRadio.addEventListener('change', toggleTeskilatField);
    huquqiRadio.addEventListener('change', toggleTeskilatField);
    
    // Initialize on page load
    toggleTeskilatField();
});
</script>