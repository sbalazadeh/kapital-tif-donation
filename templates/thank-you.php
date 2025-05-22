<?php
/**
 * Thank You Page Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get order details
$name = get_post_meta($order_id, 'name', true);
$amount = get_post_meta($order_id, 'amount', true);
$transaction_id = get_post_meta($order_id, 'transactionId_local', true);
$date = get_post_meta($order_id, 'payment_date', true);

// Format date
if (empty($date)) {
    $date = date('d F Y');
} else {
    $date_obj = date_create($date);
    if ($date_obj) {
        $date = date_format($date_obj, 'd F Y');
    }
}
?>

<div class="tif-thank-you-container">
    <h1 class="tif-thank-you-title">
        <?php _e('Təşəkkür edirik!', 'kapital-tif-donation'); ?>
    </h1>
    
    <?php if ($status === 'processing'): ?>
    <div class="tif-status-message tif-status-processing">
        <p><strong><?php _e('Ödənişiniz hazırda emal edilir.', 'kapital-tif-donation'); ?></strong> 
           <?php _e('Proses tamamlandıqda təsdiq olunacaq.', 'kapital-tif-donation'); ?></p>
    </div>
    <?php else: ?>
    <div class="tif-status-message tif-status-success">
        <p><strong><?php _e('Ödənişiniz uğurla tamamlandı.', 'kapital-tif-donation'); ?></strong></p>
    </div>
    <?php endif; ?>
    
    <div class="tif-order-details">
        <?php if (!empty($name)): ?>
        <p><strong><?php _e('Ad və soyad:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($name); ?></p>
        <?php endif; ?>
        
        <?php if (!empty($amount)): ?>
        <p><strong><?php _e('İanə məbləği:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($amount); ?> AZN</p>
        <?php endif; ?>
        
        <?php if (!empty($transaction_id)): ?>
        <p><strong><?php _e('Əməliyyat ID:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($transaction_id); ?></p>
        <?php endif; ?>
        
        <p><strong><?php _e('Tarix:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($date); ?></p>
    </div>
    
    <div class="tif-actions">
        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-primary">
            <?php _e('Ana səhifəyə qayıt', 'kapital-tif-donation'); ?>
        </a>
    </div>
</div>