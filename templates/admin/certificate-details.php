<?php
/**
 * Admin Certificate Details Template - IMPROVED
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
    </div>

    <!-- Simple PNG Download Section -->
    <div class="tif-simple-png-download" style="margin-top: 15px; padding: 15px; background: #f6f7f7; border-radius: 4px;">
        <h4 style="margin: 0 0 10px 0;"><?php _e('PNG Sertifikat', 'kapital-tif-donation'); ?></h4>
        
        <?php if ($certificate_generated): ?>
            <p style="margin: 0 0 10px 0; font-size: 13px;"><?php _e('Sertifikatı yüksək keyfiyyətli PNG formatında yükləyin:', 'kapital-tif-donation'); ?></p>
            
            <button type="button" class="button button-primary tif-simple-png-download-btn" 
                    data-order-id="<?php echo esc_attr($post_id); ?>"
                    data-type="<?php echo esc_attr($certificate_type ?: $suggested_type); ?>">
                <span class="dashicons dashicons-download" style="line-height: 1.2;"></span>
                <?php _e('PNG Yüklə', 'kapital-tif-donation'); ?>
            </button>
            
        <?php else: ?>
            <p style="margin: 0 0 10px 0; font-size: 13px; color: #666;"><?php _e('PNG yükləmək üçün əvvəlcə sertifikat yaradın:', 'kapital-tif-donation'); ?></p>
            
            <button type="button" class="button button-primary tif-generate-certificate" 
                    data-order-id="<?php echo esc_attr($post_id); ?>"
                    data-type="<?php echo esc_attr($suggested_type); ?>">
                <span class="dashicons dashicons-plus-alt" style="line-height: 1.2;"></span>
                <?php _e('Sertifikat Yarat', 'kapital-tif-donation'); ?>
            </button>
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
    
    // PNG Download function
    $('.tif-simple-png-download-btn').on('click', function() {
        var $btn = $(this);
        var orderId = $btn.data('order-id');
        var type = $btn.data('type');
        var $status = $('.tif-simple-download-status');
        
        // Validate data
        if (!orderId || !type) {
            $status.html('<div class="notice notice-error"><p>Order ID və ya tip tapılmadı</p></div>');
            return;
        }
        
        $btn.prop('disabled', true).find('.dashicons').addClass('spin');
        $status.html('<div class="notice notice-info"><p><span class="dashicons dashicons-update spin"></span> Sertifikat hazırlanır...</p></div>');
        
        // AJAX request üçün nonce
        var previewNonce = '<?php echo wp_create_nonce("tif_preview_certificate"); ?>';
        
        // Əvvəlcə SVG content götür
        $.post(ajaxurl, {
            action: 'tif_preview_certificate',
            order_id: orderId,
            type: type,
            nonce: previewNonce
        }, function(response) {
            if (response && response.success && response.data && response.data.svg) {
                // SVG content-i PNG-yə çevir və download et
                convertAndDownloadPNG(response.data.svg, orderId, type, $status);
            } else {
                var errorMsg = response && response.data && response.data.message ? response.data.message : 'SVG yaradıla bilmədi';
                $status.html('<div class="notice notice-error"><p>Xəta: ' + errorMsg + '</p></div>');
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
    
    // PNG Conversion Function (Thank you səhifəsindən adapted + error handling improved)
    function convertAndDownloadPNG(svgContent, orderId, type, $status) {
        try {
            // Input validation
            if (!svgContent || svgContent.trim() === '') {
                $status.html('<div class="notice notice-error"><p>SVG məzmun boşdur</p></div>');
                return;
            }
            
            // Temporary div yarad
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
            
            $status.html('<div class="notice notice-info"><p><span class="dashicons dashicons-update spin"></span> PNG-yə çevrilir...</p></div>');
            
            // PNG conversion
            const svgData = new XMLSerializer().serializeToString(svgElement);
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // SVG dimensions with fallback
            const svgWidth = svgElement.viewBox && svgElement.viewBox.baseVal.width ? svgElement.viewBox.baseVal.width : 842;
            const svgHeight = svgElement.viewBox && svgElement.viewBox.baseVal.height ? svgElement.viewBox.baseVal.height : 600;
            const scale = 2; // High quality
            
            canvas.width = svgWidth * scale;
            canvas.height = svgHeight * scale;
            
            // White background
            ctx.fillStyle = 'white';
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            
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
                        
                        // Order məlumatlarını götür filename üçün
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
                        
                        // 5 saniyə sonra mesajı təmizlə
                        setTimeout(function() {
                            $status.fadeOut(500, function() {
                                $status.empty().show();
                            });
                        }, 5000);
                        
                    }, 'image/png', 1.0);
                    
                } catch (drawError) {
                    console.error('Canvas draw error:', drawError);
                    $status.html('<div class="notice notice-error"><p>Canvas çəkmə xətası: ' + drawError.message + '</p></div>');
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
            
            // Set timeout for image loading
            setTimeout(function() {
                if (!img.complete) {
                    img.src = ''; // Cancel loading
                    $status.html('<div class="notice notice-error"><p>Şəkil yükləmə timeout</p></div>');
                    if (document.body.contains(tempDiv)) {
                        document.body.removeChild(tempDiv);
                    }
                }
            }, 10000); // 10 second timeout
            
            // SVG-ni Image-ə load et
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

/* Error handling styles */
.tif-simple-download-status .notice-error {
    border-left: 4px solid #d63638;
    background: #fcf0f1;
}

.tif-simple-download-status .notice-success {
    border-left: 4px solid #00a32a;
    background: #f0f6fc;
}

.tif-simple-download-status .notice-info {
    border-left: 4px solid #72aee6;
    background: #f0f6fc;
}
</style>

<?php
/**
 * Debug helper - class-tif-admin.php-ə əlavə ediləcək
 */

// Admin enqueue scripts method
public function enqueue_admin_certificate_scripts($hook) {
    // Yalnız order edit səhifəsində
    if ($hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    global $post;
    if (!$post || $post->post_type !== $this->config['general']['post_type']) {
        return;
    }
    
    // Debug log
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("TIF Admin: Enqueuing certificate scripts for order: " . $post->ID);
    }
    
    // Nonce data için localize script
    wp_localize_script('jquery', 'tif_admin_certificate', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'order_id' => $post->ID,
        'post_type' => $post->post_type,
        'nonces' => array(
            'preview' => wp_create_nonce('tif_preview_certificate'),
            'generate' => wp_create_nonce('tif_generate_certificate')
        ),
        'debug' => defined('WP_DEBUG') && WP_DEBUG,
        'messages' => array(
            'generating' => __('Sertifikat yaradılır...', 'kapital-tif-donation'),
            'converting' => __('PNG-yə çevrilir...', 'kapital-tif-donation'),
            'success' => __('Uğurla tamamlandı!', 'kapital-tif-donation'),
            'error' => __('Xəta baş verdi', 'kapital-tif-donation')
        )
    ));
}

// Hook-u constructor və ya init-də əlavə et
add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_certificate_scripts'));

/**
 * AJAX handler test üçün
 */
public function test_certificate_ajax() {
    // Debug info
    error_log("TIF Certificate AJAX Test - Request data: " . print_r($_POST, true));
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Unauthorized'));
    }
    
    wp_send_json_success(array(
        'message' => 'AJAX working correctly',
        'server_time' => current_time('mysql'),
        'request_data' => $_POST
    ));
}

// Test AJAX hook
add_action('wp_ajax_tif_test_certificate_ajax', array($this, 'test_certificate_ajax'));
?>