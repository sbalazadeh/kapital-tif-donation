<?php
/**
 * Admin Donation Details Template - VÖEN field əlavə edildi
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<table class="form-table">
    <tr>
        <th><label for="name"><?php _e('Ad və soyad', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="name" name="name" value="<?php echo esc_attr($name); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="phone"><?php _e('Telefon', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="phone" name="phone" value="<?php echo esc_attr($phone); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="amount"><?php _e('Məbləğ (AZN)', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="number" id="amount" name="amount" value="<?php echo esc_attr($amount); ?>" 
                   class="regular-text" step="0.01" min="0">
        </td>
    </tr>
    <tr>
        <th><label for="company"><?php _e('Təşkilat', 'kapital-tif-donation'); ?></label></th>
        <td>
            <select id="company" name="company" class="regular-text">
                <option value="Fiziki şəxs" <?php selected($company, 'Fiziki şəxs'); ?>>
                    <?php _e('Fiziki şəxs', 'kapital-tif-donation'); ?>
                </option>
                <option value="Hüquqi şəxs" <?php selected($company, 'Hüquqi şəxs'); ?>>
                    <?php _e('Hüquqi şəxs', 'kapital-tif-donation'); ?>
                </option>
            </select>
        </td>
    </tr>
    <tr id="company_name_row" <?php echo ($company != 'Hüquqi şəxs') ? 'style="display:none;"' : ''; ?>>
        <th><label for="company_name"><?php _e('Qurumun adı', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="company_name" name="company_name" 
                   value="<?php echo esc_attr($company_name); ?>" class="regular-text">
        </td>
    </tr>
    <!-- YENİ: VÖEN Field -->
    <tr id="voen_row" <?php echo ($company != 'Hüquqi şəxs') ? 'style="display:none;"' : ''; ?>>
        <th><label for="voen"><?php _e('Qurumun VÖENİ', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="voen" name="voen" 
                   value="<?php echo esc_attr($voen); ?>" class="regular-text" 
                   maxlength="10" pattern="[0-9]{10}">
            <p class="description"><?php _e('10 rəqəmdən ibarət VÖEN daxil edin', 'kapital-tif-donation'); ?></p>
        </td>
    </tr>
    <!-- End VÖEN Field -->
</table>

<script>
jQuery(document).ready(function($) {
    $('#company').on('change', function() {
        if ($(this).val() === 'Hüquqi şəxs') {
            $('#company_name_row, #voen_row').show();
        } else {
            $('#company_name_row, #voen_row').hide();
        }
    });
    
    // VÖEN formatting (10 digits only)
    $('#voen').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        
        // Limit to 10 digits
        if (value.length > 10) {
            value = value.substring(0, 10);
        }
        
        $(this).val(value);
    });
});
</script>