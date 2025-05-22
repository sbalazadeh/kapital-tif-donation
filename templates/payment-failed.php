<?php
/**
 * Payment Failed Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get error description based on status
$error_descriptions = array(
    'declined' => __('Ödəniş bank tərəfindən rədd edildi', 'kapital-tif-donation'),
    'cancelled' => __('Ödəniş ləğv edildi', 'kapital-tif-donation'),
    'no_status_available' => __('Bank statusu alınmadı', 'kapital-tif-donation'),
    'validation_failed' => __('Ödəniş təsdiqi uğursuz oldu', 'kapital-tif-donation'),
    'timeout' => __('Ödəniş vaxtı keçdi', 'kapital-tif-donation'),
);

$error_description = isset($error_descriptions[$status]) ? 
    $error_descriptions[$status] : 
    __('Bilinməyən xəta', 'kapital-tif-donation');
?>

<div class="tif-payment-failed-container">
    <h1 class="tif-payment-failed-title">
        <?php _e('Ödəniş uğursuz oldu', 'kapital-tif-donation'); ?>
    </h1>
    
    <div class="tif-status-message tif-status-failed">
        <p><strong><?php _e('Təəssüf ki, ödənişiniz tamamlanmadı.', 'kapital-tif-donation'); ?></strong></p>
    </div>
    
    <div class="tif-error-details">
        <p><strong><?php _e('Xəta kodu:', 'kapital-tif-donation'); ?></strong> <?php echo strtoupper(esc_html($status)); ?></p>
        <p><strong><?php _e('Açıqlama:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($error_description); ?></p>
        
        <?php if (!empty($error)): ?>
        <p><strong><?php _e('Ətraflı məlumat:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($error); ?></p>
        <?php endif; ?>
    </div>
    
    <p><?php _e('Zəhmət olmasa başqa bir ödəniş üsulu ilə yenidən cəhd edin və ya dəstək xidməti ilə əlaqə saxlayın.', 'kapital-tif-donation'); ?></p>
    
    <div class="tif-actions">
        <a href="<?php echo esc_url(home_url('/donation/')); ?>" class="btn btn-primary">
            <?php _e('Yenidən cəhd et', 'kapital-tif-donation'); ?>
        </a>
        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-secondary">
            <?php _e('Ana səhifəyə qayıt', 'kapital-tif-donation'); ?>
        </a>
    </div>
</div>