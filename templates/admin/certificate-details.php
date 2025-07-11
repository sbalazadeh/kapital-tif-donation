<?php
/**
 * Admin Certificate Details Template - FIXED VERSION
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="tif-certificate-meta-section">
    <div class="tif-certificate-info">
        <p style="margin: 5px 0;"><strong><?php _e('Sertifikat statusu:', 'kapital-tif-donation'); ?></strong></p>
        <?php if ($certificate_generated): ?>
            <p style="margin: 5px 0; color: #00a32a;"><?php _e('✓ Sertifikat mövcuddur', 'kapital-tif-donation'); ?></p>
            <p style="margin: 5px 0; font-size: 12px; color: #666;">
                <?php echo esc_html($certificate_date ? date('d.m.Y H:i', strtotime($certificate_date)) : 'Tarix yoxdur'); ?>
            </p>
        <?php else: ?>
            <p style="margin: 5px 0; color: #d63638;"><?php _e('✗ Sertifikat yaradılmamış', 'kapital-tif-donation'); ?></p>
        <?php endif; ?>
        
        <p style="margin: 5px 0;"><strong><?php _e('İanə təsnifatı:', 'kapital-tif-donation'); ?></strong></p>
        <?php
        // FIX: Use callback-dən ötürülən variable
        $iane_map = array(
            'tifiane' => 'Təhsilin İnkişafı Fonduna',
            'qtdl' => 'Qızların təhsilinə dəstək layihəsinə',
            'qtp' => 'Qarabağ Təqaüd Proqramına'
        );
        $iane_display = isset($iane_map[$iane_tesnifati]) ? $iane_map[$iane_tesnifati] : 'Müəyyən edilməyib';
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
        
        <!-- Payment Status Debug -->
        <p style="margin: 5px 0;"><strong><?php _e('Payment Status:', 'kapital-tif-donation'); ?></strong></p>
        <p style="margin: 5px 0; color: <?php echo $is_eligible ? '#00a32a' : '#d63638'; ?>;">
            <?php echo esc_html($payment_status ?: 'Unknown'); ?> 
            <?php echo $is_eligible ? '(Eligible)' : '(Not Eligible)'; ?>
        </p>
        
        <!-- İanə edən məlumatları -->
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <p style="margin: 5px 0;"><strong><?php _e('İanə edən:', 'kapital-tif-donation'); ?></strong></p>
            
            <?php if ($company === 'Hüquqi şəxs'): ?>
                <p style="margin: 5px 0; color: #0073aa;">
                    <strong>Şirkət:</strong> <?php echo esc_html($company_name ?: 'Müəyyən edilməyib'); ?>
                </p>
                <p style="margin: 5px 0; font-size: 12px; color: #666;">
                    <strong>Əlaqədar şəxs:</strong> <?php echo esc_html($name ?: 'Müəyyən edilməyib'); ?>
                </p>
                <p style="margin: 5px 0; font-size: 11px; color: #999;">
                    Sertifikatda şirkət adı görünəcək
                </p>
            <?php else: ?>
                <p style="margin: 5px 0; color: #0073aa;">
                    <strong>Ad:</strong> <?php echo esc_html($name ?: 'Müəyyən edilməyib'); ?>
                </p>
                <p style="margin: 5px 0; font-size: 11px; color: #999;">
                    Fiziki şəxs - sertifikatda şəxsi ad görünəcək
                </p>
            <?php endif; ?>
        </div>
        
        <!-- PNG Generation Status -->
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <p style="margin: 5px 0;"><strong><?php _e('PNG Sertifikat', 'kapital-tif-donation'); ?></strong></p>
            <p style="margin: 5px 0; font-size: 12px; color: #666;">
                Sertifikatı yüksək keyfiyyətli PNG formatında yükləyin:
            </p>
            
            <?php if ($is_eligible): ?>
                <button type="button" id="tif-generate-png" class="button button-primary" 
                        data-order-id="<?php echo esc_attr($post_id); ?>"
                        data-certificate-type="<?php echo esc_attr($suggested_type); ?>"
                        style="margin-top: 5px;">
                    <span class="dashicons dashicons-download"></span> PNG Yüklə
                </button>
                
                <div id="tif-png-status" style="margin-top: 10px; display: none;">
                    <p style="color: #666; font-size: 12px;">Sertifikat hazırlanır...</p>
                </div>
            <?php else: ?>
                <p style="margin: 5px 0; color: #d63638; font-size: 12px;">
                    Server alaçıq xətası: Bad Request
                </p>
                <p style="margin: 5px 0; color: #666; font-size: 11px;">
                    PNG generation üçün payment status "completed" olmalıdır
                </p>
            <?php endif; ?>
        </div>
        
        <!-- Debug məlumatları (development) -->
        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #ddd;">
            <details>
                <summary style="font-size: 11px; color: #666;">Debug məlumatları</summary>
                <div style="font-size: 10px; color: #999; margin-top: 5px;">
                    <p>Post ID: <?php echo esc_html($post_id); ?></p>
                    <p>Payment Status: <?php echo esc_html($payment_status ?: 'null'); ?></p>
                    <p>İanə Təsnifatı: <?php echo esc_html($iane_tesnifati ?: 'null'); ?></p>
                    <p>Company: <?php echo esc_html($company ?: 'null'); ?></p>
                    <p>Company Name: <?php echo esc_html($company_name ?: 'null'); ?></p>
                    <p>Name: <?php echo esc_html($name ?: 'null'); ?></p>
                    <p>Display Name: <?php echo esc_html($display_name ?: 'null'); ?></p>
                    <p>Suggested Type: <?php echo esc_html($suggested_type ?: 'null'); ?></p>
                </div>
            </details>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // PNG generation handler
    $('#tif-generate-png').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const orderId = button.data('order-id');
        const certificateType = button.data('certificate-type');
        const statusDiv = $('#tif-png-status');
        
        // Show loading state
        button.prop('disabled', true);
        button.html('<span class="dashicons dashicons-update spin"></span> Hazırlanır...');
        statusDiv.show().find('p').text('Sertifikat PNG formatına çevrilir...');
        
        // AJAX request for PNG generation
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'tif_generate_certificate_png',
                order_id: orderId,
                certificate_type: certificateType,
                nonce: '<?php echo wp_create_nonce("tif_certificate_png"); ?>'
            },
            timeout: 30000, // 30 saniyə timeout
            success: function(response) {
                if (response.success && response.data.download_url) {
                    // PNG hazır - download et
                    statusDiv.find('p').html('<span style="color: #00a32a;">✓ Uğurlu! PNG yüklənir...</span>');
                    
                    // Auto download
                    const link = document.createElement('a');
                    link.href = response.data.download_url;
                    link.download = 'sertifikat-' + orderId + '.png';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    // Success state
                    button.html('<span class="dashicons dashicons-yes"></span> Yükləndi');
                    setTimeout(() => {
                        button.prop('disabled', false);
                        button.html('<span class="dashicons dashicons-download"></span> PNG Yüklə');
                        statusDiv.hide();
                    }, 3000);
                    
                } else {
                    throw new Error(response.data?.message || 'PNG yaradıla bilmədi');
                }
            },
            error: function(xhr, status, error) {
                console.error('PNG Generation Error:', error);
                statusDiv.find('p').html('<span style="color: #d63638;">✗ Xəta: ' + (error || 'Naməlum xəta') + '</span>');
                
                button.prop('disabled', false);
                button.html('<span class="dashicons dashicons-download"></span> PNG Yüklə');
                
                setTimeout(() => {
                    statusDiv.hide();
                }, 5000);
            }
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

.tif-certificate-meta-section {
    font-size: 13px;
}

.tif-certificate-meta-section details summary {
    cursor: pointer;
    padding: 5px;
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 3px;
}

.tif-certificate-meta-section details[open] summary {
    margin-bottom: 10px;
}
</style>