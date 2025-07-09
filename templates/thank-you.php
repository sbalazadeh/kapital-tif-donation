<?php
/**
 * Thank You Page Template
 * Updated Thank You Page Template with Certificate Section
 */


// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Debug üçün
error_log('Thank You Page Debug:');
error_log('Order ID: ' . $order_id);
error_log('Status: ' . $status);
error_log('Request: ' . print_r($_REQUEST, true));

// Test mode üçün nonce yoxlamasını keç
$is_test_mode = isset($_GET['test_mode']) || (strpos(get_post_meta($order_id, 'transactionId_local', true), 'TEST-') === 0);

if ($is_test_mode) {
    // Test mode - nonce yoxlama
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

// Check if certificate is enabled and available
$certificate_enabled = false;
$certificate_type = 'tif'; // default

if (class_exists('TIF_Certificate')) {
    // Config-i yüklə
    $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
    if (file_exists($config_file)) {
        $config = require $config_file;
    }
    
    $certificate_generator = new TIF_Certificate($config);
    $certificate_enabled = $certificate_generator->is_certificate_enabled($order_id);
    
    // Determine certificate type based on iane_tesnifati
    if (!empty($iane_tesnifati)) {
        switch ($iane_tesnifati) {
            case 'qtdl':
                $certificate_type = 'young_girls';
                break;
            case 'qtp':
                $certificate_type = 'sustainable_development';
                break;
            default:
                $certificate_type = 'tif';
                break;
        }
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

    <?php //if ($certificate_enabled && $status === 'success'):
    if (($status === 'success' || $status === 'completed')): ?>
    <!-- Certificate Section -->
    <div class="tif-certificate-section">
        <div class="tif-certificate-header">
            <h2><?php _e('İanə Sertifikatınız', 'kapital-tif-donation'); ?></h2>
            <p><?php _e('İanənizə görə təşəkkür edirik. Aşağıdakı sertifikatı yükləyə və çap edə bilərsiniz.', 'kapital-tif-donation'); ?></p>
        </div>

        <div class="tif-certificate-preview" id="tif-certificate-preview">
            <div class="tif-certificate-placeholder">
                <div class="tif-loading-spinner" style="display: none;">
                    <div class="spinner"></div>
                    <p><?php _e('Sertifikat hazırlanır...', 'kapital-tif-donation'); ?></p>
                </div>
                <div class="tif-preview-content">
                    <!-- SVG content will be loaded here -->
                    <p class="tif-preview-text"><?php _e('Sertifikatı görmək üçün "Önizləmə" düyməsini basın', 'kapital-tif-donation'); ?></p>
                </div>
            </div>
        </div>

        <div class="tif-certificate-actions">
            <button type="button" id="tif-preview-certificate" class="btn btn-outline-primary" 
                    data-order-id="<?php echo esc_attr($order_id); ?>" 
                    data-certificate-type="<?php echo esc_attr($certificate_type); ?>">
                <i class="fas fa-eye"></i>
                <?php _e('Önizləmə', 'kapital-tif-donation'); ?>
            </button>
            
            <a href="<?php echo esc_url($certificate_generator->get_download_url($order_id, $certificate_type)); ?>" 
               class="btn btn-success" id="tif-download-certificate">
                <i class="fas fa-download"></i>
                <?php _e('Yüklə', 'kapital-tif-donation'); ?>
            </a>
            
            <button type="button" id="tif-print-certificate" class="btn btn-secondary" style="display: none;">
                <i class="fas fa-print"></i>
                <?php _e('Çap et', 'kapital-tif-donation'); ?>
            </button>
        </div>

        <!-- Certificate Type Selection (if multiple available) -->
        <?php if (!empty($iane_tesnifati)): ?>
        <div class="tif-certificate-type-info">
            <p class="tif-certificate-type-label">
                <strong><?php _e('Sertifikat növü:', 'kapital-tif-donation'); ?></strong>
                <?php
                $type_names = array(
                    'tif' => __('Təhsilin İnkişafı Fondu', 'kapital-tif-donation'),
                    'young_girls' => __('Gənc Qızların Təhsilinə Dəstək', 'kapital-tif-donation'),
                    'sustainable_development' => __('Qarabağ Təqaüd Proqramı', 'kapital-tif-donation')
                );
                echo esc_html($type_names[$certificate_type] ?? $type_names['tif']);
                ?>
            </p>
        </div>
        <?php endif; ?>

        <!-- Error/Success Messages -->
        <div class="tif-certificate-messages" style="display: none;">
            <div class="tif-certificate-error alert alert-danger" style="display: none;"></div>
            <div class="tif-certificate-success alert alert-success" style="display: none;"></div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="tif-actions">
        <a href="<?php echo esc_url(home_url('/donation/')); ?>" class="btn btn-outline-primary">
            <?php _e('Yeni ianə et', 'kapital-tif-donation'); ?>
        </a>
        <a href="<?php echo esc_url(home_url()); ?>" class="btn btn-primary">
            <?php _e('Ana səhifəyə qayıt', 'kapital-tif-donation'); ?>
        </a>
    </div>
</div>

<!-- Certificate CSS -->
<link rel="stylesheet" href="<?php echo TIF_DONATION_ASSETS_URL; ?>css/certificate.css?v=<?php echo TIF_DONATION_VERSION; ?>">

<!-- Certificate JavaScript -->
<script src="<?php echo TIF_DONATION_ASSETS_URL; ?>js/certificate.js?v=<?php echo TIF_DONATION_VERSION; ?>"></script>

<!-- AJAX URL for JavaScript -->
<script>
var tif_certificate_ajax = {
    ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('tif_preview_certificate'); ?>'
};
</script>

<?php //if ($certificate_enabled && $status === 'success'): 
        if (($status === 'success' || $status === 'completed')): ?>
<!-- Certificate JavaScript will be enqueued separately -->
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    if (typeof TIFCertificate !== 'undefined') {
        TIFCertificate.init();
    }
});
</script>
<?php endif; ?>