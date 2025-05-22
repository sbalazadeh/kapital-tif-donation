<?php
/**
 * Admin Transaction Details Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Prepare order data HTML
$order_data_html = '';
if (!empty($order_data) && is_array($order_data)) {
    $order_data_html = '<details>
        <summary>' . __('Ətraflı məlumatları göstər', 'kapital-tif-donation') . '</summary>
        <div style="margin-top: 10px;">
            <pre style="max-height: 400px; overflow-y: auto; background: #f8f9fa; padding: 10px; border: 1px solid #ddd;">' . 
            esc_html(print_r($order_data, true)) . '</pre>
        </div>
    </details>';
} else {
    $order_data_html = __('Məlumat yoxdur', 'kapital-tif-donation');
}
?>

<table class="form-table">
    <tr>
        <th><label for="trans_id_local"><?php _e('Transaction ID Local', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="trans_id_local" name="transactionId_local" 
                   value="<?php echo esc_attr($trans_id_local); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="bank_order_id"><?php _e('Bank Order ID', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="bank_order_id" name="bank_order_id" 
                   value="<?php echo esc_attr($bank_order_id); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="approval_code"><?php _e('Approval Code', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="approval_code" name="approval_code" 
                   value="<?php echo esc_attr($approval_code); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="payment_method"><?php _e('Ödəniş üsulu', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="payment_method" name="payment_method" 
                   value="<?php echo esc_attr($payment_method); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="card_number"><?php _e('Kart nömrəsi', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="card_number" name="card_number" 
                   value="<?php echo esc_attr($card_number); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="payment_date"><?php _e('Ödəniş tarixi', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="payment_date" name="payment_date" 
                   value="<?php echo esc_attr($payment_date); ?>" class="regular-text">
        </td>
    </tr>
    <tr>
        <th><label for="payment_status"><?php _e('Ödəniş statusu', 'kapital-tif-donation'); ?></label></th>
        <td>
            <input type="text" id="payment_status" name="payment_status" 
                   value="<?php echo esc_attr($payment_status); ?>" class="regular-text">
            <p class="description">
                <?php printf(__('Mövcud status: %s', 'kapital-tif-donation'), '<strong>' . esc_html($current_term) . '</strong>'); ?>
            </p>
            <button type="button" id="sync_status_button" class="button button-secondary" 
                    data-post-id="<?php echo esc_attr($post_id); ?>">
                <?php _e('Statusu yenilə', 'kapital-tif-donation'); ?>
            </button>
            <span id="sync_status_result"></span>
        </td>
    </tr>
    <tr>
        <th><label><?php _e('Ətraflı məlumat', 'kapital-tif-donation'); ?></label></th>
        <td><?php echo $order_data_html; ?></td>
    </tr>
</table>

<script>
jQuery(document).ready(function($) {
    $('#sync_status_button').on('click', function() {
        var button = $(this);
        var postId = button.data('post-id');
        var result = $('#sync_status_result');
        
        button.prop('disabled', true);
        result.html('<span style="color:blue"><?php _e("Yenilənir...", "kapital-tif-donation"); ?></span>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'tif_sync_payment_status',
                post_id: postId,
                nonce: '<?php echo esc_js($nonce); ?>'
            },
            success: function(response) {
                if (response.success) {
                    result.html('<span style="color:green"><?php _e("Status yeniləndi:", "kapital-tif-donation"); ?> ' + response.data.status + '</span>');
                    setTimeout(function() { 
                        location.reload(); 
                    }, 1500);
                } else {
                    result.html('<span style="color:red"><?php _e("Xəta:", "kapital-tif-donation"); ?> ' + response.data.message + '</span>');
                }
            },
            error: function() {
                result.html('<span style="color:red"><?php _e("Serverlə əlaqə zamanı xəta baş verdi.", "kapital-tif-donation"); ?></span>');
            },
            complete: function() {
                button.prop('disabled', false);
            }
        });
    });
});
</script>