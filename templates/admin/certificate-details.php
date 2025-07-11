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
        $iane_tesnifati = get_post_meta($post_id, 'iane_tesnifati', true);
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
    </div>

    <!-- PNG Download Section -->
    <div class="tif-simple-png-download" style="margin-top: 15px; padding: 15px; background: #f6f7f7; border-radius: 4px; border: 1px solid #c3c4c7;">
        <h4 style="margin: 0 0 10px 0; color: #1d2327; font-size: 14px;"><?php _e('PNG Sertifikat', 'kapital-tif-donation'); ?></h4>
        
        <?php if ($certificate_generated && $is_eligible): ?>
            <p style="margin: 0 0 10px 0; font-size: 13px;"><?php _e('Sertifikatı yüksək keyfiyyətli PNG formatında yükləyin:', 'kapital-tif-donation'); ?></p>
            
            <button type="button" class="button button-primary tif-simple-png-download-btn" 
                    data-order-id="<?php echo esc_attr($post_id); ?>"
                    data-type="<?php echo esc_attr($certificate_type ?: $suggested_type); ?>">
                <span class="dashicons dashicons-download" style="line-height: 1.2;"></span>
                <?php _e('PNG Yüklə', 'kapital-tif-donation'); ?>
            </button>
            
        <?php elseif ($is_eligible): ?>
            <p style="margin: 0 0 10px 0; font-size: 13px; color: #666;"><?php _e('PNG yükləmək üçün əvvəlcə sertifikat yaradın:', 'kapital-tif-donation'); ?></p>
            
            <button type="button" class="button button-primary tif-generate-certificate" 
                    data-order-id="<?php echo esc_attr($post_id); ?>"
                    data-type="<?php echo esc_attr($suggested_type); ?>">
                <span class="dashicons dashicons-plus-alt" style="line-height: 1.2;"></span>
                <?php _e('Sertifikat Yarat', 'kapital-tif-donation'); ?>
            </button>
        <?php else: ?>
            <div style="padding: 10px; background: #fcf0f1; border: 1px solid #d63638; border-radius: 4px;">
                <p style="margin: 0; font-size: 13px; color: #d63638;">
                    <strong><?php _e('Qeyd:', 'kapital-tif-donation'); ?></strong> 
                    <?php _e('Sertifikat yalnız completed/success statuslu sifarişlər üçün mövcuddur.', 'kapital-tif-donation'); ?>
                </p>
                <p style="margin: 5px 0 0 0; font-size: 12px; color: #666;">
                    <?php printf(__('Cari status: %s', 'kapital-tif-donation'), '<strong>' . esc_html($payment_status ?: 'Unknown') . '</strong>'); ?>
                </p>
            </div>
        <?php endif; ?>
        
        <div class="tif-simple-download-status" style="margin-top: 10px;"></div>
    </div>

    <!-- Regenerate option (if certificate exists) -->
    <?php if ($certificate_generated): ?>
    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
        <button type="button" class="button tif-regenerate-certificate" 
                data-order-id="<?php echo esc_attr($post_id); ?>"
                data-type="<?php echo esc_attr($suggested_type); ?>" 
                style="font-size: 11px;">
            <span class="dashicons dashicons-update" style="line-height: 1.2; font-size: 14px;"></span>
            <?php _e('Sertifikatı Yenilə', 'kapital-tif-donation'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    
    // PNG Download function - FIXED
    $('.tif-simple-png-download-btn').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var type = $btn.data('type');
        var $status = $('.tif-simple-download-status');
        
        // Validation
        if (!orderId || !type) {
            $status.html('<div class="notice notice-error"><p>Order ID və ya tip tapılmadı</p></div>');
            return;
        }
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $status.html('<div class="notice notice-info"><p><span class="dashicons dashicons-update spin"></span> Sertifikat hazırlanır...</p></div>');
        
        // AJAX request with proper nonce
        var previewNonce = '<?php echo wp_create_nonce("tif_preview_certificate"); ?>';
        
        $.post(ajaxurl, {
            action: 'tif_preview_certificate',
            order_id: orderId,
            type: type,
            nonce: previewNonce
        }, function(response) {
            console.log('AJAX Response:', response); // Debug log
            
            if (response && response.success && response.data && response.data.svg) {
                // SVG content-i PNG-yə çevir və download et
                convertAndDownloadPNG(response.data.svg, orderId, type, $status);
            } else {
                var errorMsg = response && response.data && response.data.message ? response.data.message : 'SVG yaradıla bilmədi';
                $status.html('<div class="notice notice-error"><p>Xəta: ' + errorMsg + '</p></div>');
                console.error('Preview Error:', response);
            }
        }).fail(function(xhr, status, error) {
            console.error('AJAX Error:', status, error);
            $status.html('<div class="notice notice-error"><p>Server əlaqə xətası: ' + error + '</p></div>');
        }).always(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
    });
    
    // Certificate generation
    $('.tif-generate-certificate, .tif-regenerate-certificate').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var type = $btn.data('type');
        var $status = $('.tif-simple-download-status');
        
        if (!orderId || !type) {
            $status.html('<div class="notice notice-error"><p>Order ID və ya tip tapılmadı</p></div>');
            return;
        }
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $status.html('<div class="notice notice-info"><p>Sertifikat yaradılır...</p></div>');
        
        var generateNonce = '<?php echo wp_create_nonce("tif_generate_certificate"); ?>';
        
        $.post(ajaxurl, {
            action: 'tif_generate_certificate',
            order_id: orderId,
            type: type,
            nonce: generateNonce
        }, function(response) {
            if (response && response.success) {
                var successMsg = response.data && response.data.message ? response.data.message : 'Sertifikat yaradıldı';
                $status.html('<div class="notice notice-success"><p>' + successMsg + '</p></div>');
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                var errorMsg = response && response.data && response.data.message ? response.data.message : 'Sertifikat yaradıla bilmədi';
                $status.html('<div class="notice notice-error"><p>' + errorMsg + '</p></div>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Generation AJAX Error:', status, error);
            $status.html('<div class="notice notice-error"><p>Server xətası: ' + error + '</p></div>');
        }).always(function() {
            $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
    });
    
    // PNG Conversion Function - COMPLETE WORKING VERSION
    function convertAndDownloadPNG(svgContent, orderId, type, $status) {
        try {
            // Input validation
            if (!svgContent || svgContent.trim() === '') {
                $status.html('<div class="notice notice-error"><p>SVG məzmun boşdur</p></div>');
                return;
            }
            
            $status.html('<div class="notice notice-info"><p><span class="dashicons dashicons-update spin"></span> PNG-yə çevrilir...</p></div>');
            
            // Create temporary div for SVG
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = svgContent;
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = '-9999px';
            tempDiv.style.top = '-9999px';
            tempDiv.style.visibility = 'hidden';
            document.body.appendChild(tempDiv);
            
            const svgElement = tempDiv.querySelector('svg');
            if (!svgElement) {
                $status.html('<div class="notice notice-error"><p>SVG element tapılmadı</p></div>');
                document.body.removeChild(tempDiv);
                return;
            }
            
            // Get SVG dimensions
            const svgWidth = svgElement.viewBox && svgElement.viewBox.baseVal.width ? svgElement.viewBox.baseVal.width : 842;
            const svgHeight = svgElement.viewBox && svgElement.viewBox.baseVal.height ? svgElement.viewBox.baseVal.height : 600;
            const scale = 2; // High quality
            
            // Create canvas
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            canvas.width = svgWidth * scale;
            canvas.height = svgHeight * scale;
            
            // White background
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
            // Convert SVG to image
            const img = new Image();
            const today = new Date().toISOString().split('T')[0];
            
            img.onload = function() {
                try {
                    ctx.scale(scale, scale);
                    ctx.drawImage(img, 0, 0, svgWidth, svgHeight);
                    
                    // Download as PNG
                    canvas.toBlob(function(blob) {
                        if (!blob) {
                            $status.html('<div class="notice notice-error"><p>PNG blob yaradıla bilmədi</p></div>');
                            return;
                        }
                        
                        const url = URL.createObjectURL(blob);
                        const downloadLink = document.createElement('a');
                        downloadLink.href = url;
                        
                        // Generate filename
                        const orderTitle = $('#title').val() || 'Order';
                        const cleanTitle = orderTitle.replace(/[^a-zA-Z0-9]/g, '_').substring(0, 20);
                        
                        downloadLink.download = `TIF_Sertifikat_${cleanTitle}_Order_${orderId}_${type}_${today}.png`;
                        downloadLink.style.display = 'none';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                        URL.revokeObjectURL(url);
                        
                        // Success message
                        $status.html('<div class="notice notice-success"><p><span class="dashicons dashicons-yes"></span> PNG sertifikat uğurla yükləndi!</p></div>');
                        
                        // Clear message after 5 seconds
                        setTimeout(function() {
                            $status.fadeOut(500, function() {
                                $status.empty().show();
                            });
                        }, 5000);
                        
                    }, 'image/png', 1.0);
                    
                } catch (drawError) {
                    console.error('Canvas draw error:', drawError);
                    $status.html('<div class="notice notice-error"><p>Canvas çəkmə xətası</p></div>');
                } finally {
                    // Cleanup
                    if (document.body.contains(tempDiv)) {
                        document.body.removeChild(tempDiv);
                    }
                }
            };
            
            img.onerror = function(imgError) {
                console.error('Image load error:', imgError);
                $status.html('<div class="notice notice-error"><p>Şəkil yükləmə xətası</p></div>');
                if (document.body.contains(tempDiv)) {
                    document.body.removeChild(tempDiv);
                }
            };
            
            // Set timeout
            setTimeout(function() {
                if (!img.complete) {
                    img.src = '';
                    $status.html('<div class="notice notice-error"><p>Şəkil yükləmə timeout</p></div>');
                    if (document.body.contains(tempDiv)) {
                        document.body.removeChild(tempDiv);
                    }
                }
            }, 10000);
            
            // Convert SVG to blob URL for image
            const svgData = new XMLSerializer().serializeToString(svgElement);
            const svgBlob = new Blob([svgData], {type: 'image/svg+xml;charset=utf-8'});
            const url = URL.createObjectURL(svgBlob);
            img.src = url;
            
        } catch (error) {
            console.error('PNG conversion error:', error);
            $status.html('<div class="notice notice-error"><p>JavaScript xətası: ' + error.message + '</p></div>');
        }
    }
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

.tif-simple-png-download {
    border: 1px solid #c3c4c7;
}

.tif-simple-download-status .notice {
    margin: 10px 0 5px 0;
    padding: 8px 12px;
    border-left: 4px solid;
    border-radius: 0 4px 4px 0;
}

.tif-simple-download-status .notice p {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 5px;
}

.tif-certificate-meta-section .button {
    margin-right: 5px;
}

.tif-simple-png-download h4 {
    color: #1d2327;
    font-size: 14px;
}

/* Enhanced styling for notices */
.tif-simple-download-status .notice-error {
    border-left-color: #d63638;
    background: #fcf0f1;
}

.tif-simple-download-status .notice-success {
    border-left-color: #00a32a;
    background: #f0f6fc;
}

.tif-simple-download-status .notice-info {
    border-left-color: #72aee6;
    background: #f0f6fc;
}
</style>