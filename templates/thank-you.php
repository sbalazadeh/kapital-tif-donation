<?php
/**
 * Thank You Page Template - SIMPLIFIED VERSION
 * Sertifikat avtomatik generate olunur və çap button ilə print edilir
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug üçün
error_log('Thank You Page Debug:');
error_log('Order ID: ' . $order_id);
error_log('Status: ' . $status);

// Test mode üçün nonce yoxlamasını keç
$is_test_mode = isset($_GET['test_mode']) || (strpos(get_post_meta($order_id, 'transactionId_local', true), 'TEST-') === 0);

if ($is_test_mode) {
    error_log('Test mode aktiv');
}

// Get order details
$name = get_post_meta($order_id, 'name', true);
$amount = get_post_meta($order_id, 'amount', true);
$transaction_id = get_post_meta($order_id, 'transactionId_local', true);
$date = get_post_meta($order_id, 'payment_date', true);
$iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);

// Format date
if (empty($date)) {
    $date = date('d F Y');
} else {
    $date_obj = date_create($date);
    if ($date_obj) {
        $date = date_format($date_obj, 'd F Y');
    }
}

// Certificate generation - SIMPLIFIED
$certificate_enabled = false;
$certificate_svg = '';
$certificate_error = '';

if (class_exists('TIF_Certificate')) {
    try {
        // Config-i yüklə
        $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
        if (file_exists($config_file)) {
            $config = require $config_file;
            
            $certificate_generator = new TIF_Certificate($config);
            $certificate_enabled = $certificate_generator->is_certificate_enabled($order_id);
            
            if ($certificate_enabled) {
                // Avtomatik sertifikat generation
                $certificate_svg = $certificate_generator->generate_certificate_for_thank_you($order_id);
                
                if ($certificate_svg) {
                    error_log("TIF Certificate: Auto-generated for thank you page - Order: {$order_id}");
                } else {
                    $certificate_error = 'Sertifikat yaradıla bilmədi.';
                    error_log("TIF Certificate: Generation failed for order: {$order_id}");
                }
            }
        }
    } catch (Exception $e) {
        $certificate_error = 'Sertifikat xətası: ' . $e->getMessage();
        error_log("TIF Certificate Error: " . $e->getMessage());
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
    
    <!-- Order Details -->
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

    <?php if (($status === 'success' || $status === 'completed') && $certificate_enabled): ?>
    <!-- SIMPLIFIED Certificate Section -->
    <div class="tif-certificate-section">
        <div class="tif-certificate-header">
            <h2><?php _e('İanə Sertifikatınız', 'kapital-tif-donation'); ?></h2>
            <p><?php _e('İanənizə görə təşəkkür edirik. Sertifikatınızı aşağıda görə və çap edə bilərsiniz.', 'kapital-tif-donation'); ?></p>
        </div>

        <?php if (!empty($certificate_error)): ?>
        <!-- Certificate Error -->
        <div class="tif-certificate-error alert alert-danger">
            <p><strong><?php _e('Xəta:', 'kapital-tif-donation'); ?></strong> <?php echo esc_html($certificate_error); ?></p>
            <p><?php _e('Zəhmət olmasa daha sonra yenidən cəhd edin və ya bizimlə əlaqə saxlayın.', 'kapital-tif-donation'); ?></p>
        </div>
        
        <?php elseif (!empty($certificate_svg)): ?>
        <!-- Certificate Display - DIRECT SVG OUTPUT -->
        <div class="tif-certificate-display" id="tif-certificate-display">
            <div class="tif-certificate-content">
                <?php echo $certificate_svg; ?>
            </div>
        </div>

        <!-- SIMPLIFIED Actions - Only Print Button -->
        <div class="tif-certificate-actions">
            <button type="button" onclick="printCertificate()" class="btn btn-primary">
                <i class="fas fa-print"></i>
                <?php _e('Çap et / PDF olaraq saxla', 'kapital-tif-donation'); ?>
            </button>
        </div>

        <!-- Certificate Type Info -->
        <?php if (!empty($iane_tesnifati)): ?>
        <div class="tif-certificate-type-info">
            <p class="tif-certificate-type-label">
                <strong><?php _e('Sertifikat növü:', 'kapital-tif-donation'); ?></strong>
                <?php
                $type_names = array(
                    'tifiane' => __('Təhsilin İnkişafı Fondu', 'kapital-tif-donation'),
                    'qtdl' => __('Gənc Qızların Təhsilinə Dəstək', 'kapital-tif-donation'),
                    'qtp' => __('Qarabağ Təqaüd Proqramı', 'kapital-tif-donation')
                );
                echo esc_html($type_names[$iane_tesnifati] ?? $type_names['tifiane']);
                ?>
            </p>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <!-- No Certificate Available -->
        <div class="tif-certificate-not-available">
            <p><?php _e('Sertifikat hazırlanır, zəhmət olmasa bir az gözləyin.', 'kapital-tif-donation'); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Navigation Actions -->
    <div class="tif-actions">
        <a href="<?php echo esc_url(home_url('/donation/')); ?>" class="btn btn-outline-primary">
            <?php _e('Yeni ianə et', 'kapital-tif-donation'); ?>
        </a>
        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-primary">
            <?php _e('Ana səhifəyə qayıt', 'kapital-tif-donation'); ?>
        </a>
    </div>
</div>

<!-- SIMPLIFIED Certificate CSS - Only essential styles -->
<style>
/* Certificate Section */
.tif-certificate-section {
    background-color: #fff;
    border-radius: 12px;
    padding: 30px;
    margin: 30px 0;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: 2px solid #e9ecef;
}

.tif-certificate-header {
    text-align: center;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
}

.tif-certificate-header h2 {
    color: #28a745;
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.tif-certificate-header h2::before {
    content: "🏆";
    font-size: 1.5rem;
    margin-right: 10px;
}

/* Certificate Display */
.tif-certificate-display {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
    text-align: center;
}

.tif-certificate-content svg {
    max-width: 100%;
    height: auto;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border-radius: 8px;
    background: white;
}

/* Actions */
.tif-certificate-actions {
    text-align: center;
    margin: 20px 0;
}

.tif-certificate-actions .btn {
    padding: 12px 24px;
    font-size: 1.1rem;
    border-radius: 6px;
    margin: 10px;
}

.tif-certificate-type-info {
    text-align: center;
    margin-top: 15px;
    padding: 10px;
    background: #e8f5e8;
    border-radius: 6px;
    font-size: 0.9rem;
}

/* Error Styles */
.tif-certificate-error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
    border-radius: 6px;
    padding: 15px;
    margin: 15px 0;
}

/* Print Styles */
@media print {
    .tif-certificate-section {
        box-shadow: none;
        border: none;
        margin: 0;
        padding: 0;
    }
    
    .tif-certificate-actions,
    .tif-certificate-header p,
    .tif-certificate-type-info,
    .tif-actions {
        display: none !important;
    }
    
    .tif-certificate-display {
        background: none;
        border: none;
        padding: 0;
        margin: 0;
    }
    
    .tif-certificate-content svg {
        box-shadow: none;
        border-radius: 0;
        max-width: 100%;
        height: auto;
    }
}
</style>

<!-- SIMPLIFIED JavaScript - Only Print Function -->
<script>
function printCertificate() {
    // Browser-in öz print dialog-unu aç
    window.print();
}

// Page load olduqda scroll certificate-a
document.addEventListener('DOMContentLoaded', function() {
    const certificateSection = document.querySelector('.tif-certificate-section');
    if (certificateSection) {
        // 1 saniyə sonra smooth scroll
        setTimeout(function() {
            certificateSection.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 1000);
    }
});
</script>