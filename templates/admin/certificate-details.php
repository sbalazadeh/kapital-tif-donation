<?php
/**
 * Admin Certificate Details Template
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tif-certificate-admin-section">
    <!-- Certificate Status -->
    <div class="tif-certificate-status-section" style="margin-bottom: 15px;">
        <?php if ($is_eligible): ?>
            <?php if ($certificate_generated): ?>
                <p style="color: #28a745; font-weight: bold;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Sertifikat yaradılıb', 'kapital-tif-donation'); ?>
                </p>
                <?php if ($certificate_date): ?>
                    <p style="font-size: 12px; color: #666; margin: 5px 0;">
                        <?php printf(__('Tarix: %s', 'kapital-tif-donation'), date('d.m.Y H:i', strtotime($certificate_date))); ?>
                    </p>
                <?php endif; ?>
            <?php else: ?>
                <p style="color: #856404; font-weight: bold;">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Sertifikat yaradılmayıb', 'kapital-tif-donation'); ?>
                </p>
            <?php endif; ?>
        <?php else: ?>
            <p style="color: #dc3545; font-weight: bold;">
                <span class="dashicons dashicons-dismiss"></span>
                <?php _e('Sertifikat yaradıla bilməz', 'kapital-tif-donation'); ?>
            </p>
            <p style="font-size: 12px; color: #666;">
                <?php printf(__('Status: %s', 'kapital-tif-donation'), $payment_status ?: 'Müəyyən edilməyib'); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Certificate Info -->
    <?php if ($is_eligible): ?>
        <div class="tif-certificate-info-section" style="background: #f8f9fa; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
            <p style="margin: 5px 0;"><strong><?php _e('Sertifikatda görünəcək ad:', 'kapital-tif-donation'); ?></strong></p>
            <p style="margin: 5px 0; color: #0073aa;"><?php echo esc_html($display_name); ?></p>
            
            <?php if ($company === 'Hüquqi şəxs'): ?>
                <p style="margin: 5px 0; font-size: 11px; color: #666;">
                    <?php printf(__('(Fiziki şəxs: %s)', 'kapital-tif-donation'), esc_html($name)); ?>
                </p>
            <?php endif; ?>
            
            <hr style="margin: 8px 0;">
            
            <p style="margin: 5px 0;"><strong><?php _e('İanə təsnifatı:', 'kapital-tif-donation'); ?></strong></p>
            <?php
            $iane_map = array(
                'tifiane' => 'Təhsilin İnkişafı Fonduna',
                'qtdl' => 'Qızların təhsilinə dəstək layihəsinə',
                'qtp' => 'Qarabağ Təqaüd Proqramına'
            );
            $iane_display = $iane_map[$iane_tesnifati] ?? 'Müəyyən edilməyib';
            ?>
            <p style="margin: 5px 0; color: #0073aa;"><?php echo esc_html($iane_display); ?></p>
            
            <p style="margin: 5px 0;"><strong><?php _e('Sertifikat növü:', 'kapital-tif-donation'); ?></strong></p>
            <?php
            $type_map = array(
                'tif' => 'TIF Certificate',
                'youth' => 'Youth Certificate',
                'sustainable' => 'Sustainable Certificate'
            );
            $type_display = $type_map[$suggested_type] ?? 'TIF Certificate';
            ?>
            <p style="margin: 5px 0; color: #0073aa;"><?php echo esc_html($type_display); ?></p>
        </div>

        <!-- Certificate Actions -->
        <div class="tif-certificate-actions-section">
            <?php if ($certificate_generated): ?>
                <!-- Download Certificate -->
                <p style="margin-bottom: 10px;">
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=tif_download_certificate&order_id=' . $post_id . '&type=' . ($certificate_type ?: $suggested_type) . '&nonce=' . wp_create_nonce('tif_download_' . $post_id))); ?>" 
                       class="button button-primary" target="_blank">
                        <span class="dashicons dashicons-download" style="line-height: 1.2;"></span>
                        <?php _e('PNG Sertifikatı Yüklə', 'kapital-tif-donation'); ?>
                    </a>
                </p>
                
                <!-- Regenerate Certificate -->
                <p style="margin-bottom: 10px;">
                    <button type="button" class="button tif-regenerate-certificate" 
                            data-order-id="<?php echo esc_attr($post_id); ?>"
                            data-type="<?php echo esc_attr($suggested_type); ?>">
                        <span class="dashicons dashicons-update" style="line-height: 1.2;"></span>
                        <?php _e('Sertifikatı Yenilə', 'kapital-tif-donation'); ?>
                    </button>
                </p>
            <?php else: ?>
                <!-- Generate Certificate -->
                <p style="margin-bottom: 10px;">
                    <button type="button" class="button button-primary tif-generate-certificate" 
                            data-order-id="<?php echo esc_attr($post_id); ?>"
                            data-type="<?php echo esc_attr($suggested_type); ?>">
                        <span class="dashicons dashicons-plus-alt" style="line-height: 1.2;"></span>
                        <?php _e('Sertifikat Yarat', 'kapital-tif-donation'); ?>
                    </button>
                </p>
            <?php endif; ?>
            
            <!-- Preview Certificate -->
            <p style="margin-bottom: 10px;">
                <button type="button" class="button tif-preview-certificate" 
                        data-order-id="<?php echo esc_attr($post_id); ?>"
                        data-type="<?php echo esc_attr($suggested_type); ?>">
                    <span class="dashicons dashicons-visibility" style="line-height: 1.2;"></span>
                    <?php _e('Önizləmə', 'kapital-tif-donation'); ?>
                </button>
            </p>
        </div>

        <!-- Loading/Messages -->
        <div class="tif-certificate-messages" style="margin-top: 10px;"></div>

        <!-- Preview Container -->
        <div class="tif-certificate-preview-container" style="display: none; margin-top: 15px; max-width: 100%; overflow: hidden;">
            <div class="tif-certificate-preview-content" style="border: 1px solid #ddd; padding: 10px; background: white; border-radius: 4px; text-align: center;">
                <!-- Preview will be loaded here -->
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Certificate generation
    $('.tif-generate-certificate, .tif-regenerate-certificate').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var type = $btn.data('type');
        var $messages = $('.tif-certificate-messages');
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $messages.html('<div class="notice notice-info"><p>Sertifikat yaradılır...</p></div>');
        
        $.post(ajaxurl, {
            action: 'tif_generate_certificate',
            order_id: orderId,
            type: type,
            nonce: '<?php echo wp_create_nonce("tif_generate_certificate"); ?>'
        }, function(response) {
            if (response.success) {
                $messages.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                $messages.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
    });
    
    // Certificate preview
    $('.tif-preview-certificate').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var type = $btn.data('type');
        var $container = $('.tif-certificate-preview-container');
        var $content = $('.tif-certificate-preview-content');
        var $messages = $('.tif-certificate-messages');
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $messages.html('<div class="notice notice-info"><p>Önizləmə yüklənir...</p></div>');
        
        $.post(ajaxurl, {
            action: 'tif_preview_certificate',
            order_id: orderId,
            type: type,
            nonce: '<?php echo wp_create_nonce("tif_preview_certificate"); ?>'
        }, function(response) {
            if (response.success) {
                $content.html(response.data.svg);
                $container.show();
                $messages.html('<div class="notice notice-success"><p>Önizləmə hazırdır</p></div>');
            } else {
                $messages.html('<div class="notice notice-error"><p>' + response.data.message + '</p></div>');
            }
        }).always(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
    });
});
</script>

<style>
.dashicons.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.tif-certificate-preview-content svg {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
</style>