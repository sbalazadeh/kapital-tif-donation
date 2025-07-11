<?php
/**
 * Plugin Name: Kapital TIF Donation Integration
 * Plugin URI: https://iam.az
 * Description: Professional donation integration with Kapital Bank E-commerce API
 * Version: 2.0.0
 * Author: Sahil Balazade
 * Text Domain: kapital-tif-donation
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TIF_DONATION_VERSION', '2.0.0');
define('TIF_DONATION_PLUGIN_FILE', __FILE__);
define('TIF_DONATION_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('TIF_DONATION_PLUGIN_URL', plugin_dir_url(__FILE__));
define('TIF_DONATION_INCLUDES_DIR', TIF_DONATION_PLUGIN_DIR . 'includes/');
define('TIF_DONATION_CONFIG_DIR', TIF_DONATION_PLUGIN_DIR . 'config/');
define('TIF_DONATION_TEMPLATES_DIR', TIF_DONATION_PLUGIN_DIR . 'templates/');
define('TIF_DONATION_ASSETS_URL', TIF_DONATION_PLUGIN_URL . 'assets/');

// Autoloader function
function tif_donation_autoload($class) {
    if (strpos($class, 'TIF_') !== 0) {
        return;
    }
    
    $class = strtolower(str_replace('_', '-', $class));
    $file = TIF_DONATION_INCLUDES_DIR . 'class-' . $class . '.php';
    
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('tif_donation_autoload');

// Load configuration
require_once TIF_DONATION_CONFIG_DIR . 'config.php';

// Load main class
require_once TIF_DONATION_INCLUDES_DIR . 'class-tif-donation.php';

// Initialize plugin
function tif_donation_init() {
    return TIF_Donation::instance();
}

// Start the plugin after WordPress is loaded
add_action('plugins_loaded', 'tif_donation_init');

// Activation hook
register_activation_hook(__FILE__, array('TIF_Donation', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('TIF_Donation', 'deactivate'));

// Check if plugin can run
function tif_donation_requirements_check() {
    if (version_compare(phpversion(), '7.4', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Bu plugin PHP 7.4 və daha yüksək versiya tələb edir.', 'kapital-tif-donation'));
    }
    
    if (version_compare(get_bloginfo('version'), '5.0', '<')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Bu plugin WordPress 5.0 və daha yüksək versiya tələb edir.', 'kapital-tif-donation'));
    }
}
add_action('admin_init', 'tif_donation_requirements_check');

// Add settings link in plugins page
function tif_donation_settings_link($links) {
    $settings_link = '<a href="' . admin_url('edit.php?post_type=odenis') . '">' . __('Parametrlər', 'kapital-tif-donation') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'tif_donation_settings_link');

// kapital-tif-donation.php əlavəsi:

// Plugin activated/deactivated hooks
add_action('plugins_loaded', 'tif_donation_init', 10);

// Add plugin row meta links
function tif_donation_plugin_row_meta($links, $file) {
    if ($file === plugin_basename(TIF_DONATION_PLUGIN_FILE)) {
        $links[] = '<a href="' . admin_url('edit.php?post_type=odenis&page=tif-statistics') . '">' . __('Statistika', 'kapital-tif-donation') . '</a>';
        $links[] = '<a href="https://documenter.getpostman.com/view/14817621/2sA3dxCB1b" target="_blank">' . __('API Sənədləri', 'kapital-tif-donation') . '</a>';
    }
    return $links;
}
add_filter('plugin_row_meta', 'tif_donation_plugin_row_meta', 10, 2);

// Add admin notice for test mode
function tif_donation_global_admin_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $screen = get_current_screen();
    if ($screen && $screen->base === 'plugins') {
        // Load config to check test mode
        $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
        if (file_exists($config_file)) {
            $config = require $config_file;
            if ($config['test_mode']) {
                echo '<div class="notice notice-warning">';
                echo '<p><strong>TIF Donation Plugin:</strong> ';
                echo __('Test modunda işləyir. Production üçün config.php faylında test_mode parametrini false edin.', 'kapital-tif-donation');
                echo '</p></div>';
            }
        }
    }
}
add_action('admin_notices', 'tif_donation_global_admin_notice');

add_action('init', 'tif_init_certificate_system');

function tif_init_certificate_system() {
    global $config;
    
    // Check if certificate system is enabled
    if (!isset($config['certificate']['enabled']) || !$config['certificate']['enabled']) {
        return;
    }
    
    // Include certificate classes
    require_once TIF_DONATION_PLUGIN_DIR . 'includes/class-tif-certificate.php';
    require_once TIF_DONATION_PLUGIN_DIR . 'includes/class-tif-certificate-ajax.php';
    
    // Initialize certificate AJAX handler
    new TIF_Certificate_Ajax($config);
    
    // Auto-generate certificate on successful payment
    if (isset($config['certificate']['auto_generate']) && $config['certificate']['auto_generate']) {
        add_action('tif_payment_completed', 'tif_auto_generate_certificate', 10, 1);
    }
}

/**
 * Auto-generate certificate on successful payment
 */
function tif_auto_generate_certificate($order_id) {
    global $config;
    
    $certificate_generator = new TIF_Certificate($config);
    
    // İanə Təsnifatı əsasında certificate type müəyyən et
    $iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);
    $certificate_mapping = array(
        'tifiane' => 'tif',
        'qtdl' => 'youth', 
        'qtp' => 'sustainable'
    );
    $certificate_type = $certificate_mapping[$iane_tesnifati] ?? $config['certificate']['default_type'] ?? 'tif';
    
    // Generate certificate
    $svg_content = $certificate_generator->generate_certificate($order_id, $certificate_type);
    
    if ($svg_content) {
        // Mark as generated
        update_post_meta($order_id, 'certificate_generated', true);
        update_post_meta($order_id, 'certificate_type', $certificate_type);
        update_post_meta($order_id, 'certificate_date', current_time('Y-m-d H:i:s'));
        
        // Log success
        error_log("TIF Certificate: Auto-generated for order {$order_id}, type: {$certificate_type}");
    }
}

// Frontend certificate display shortcode
add_shortcode('tif_certificate', 'tif_certificate_shortcode');

function tif_certificate_shortcode($atts) {
    $atts = shortcode_atts(array(
        'order_id' => 0,
        'type' => 'tif',
        'display' => 'button' // button, inline, preview
    ), $atts);
    
    $order_id = intval($atts['order_id']);
    if ($order_id <= 0) {
        return '';
    }
    
    global $config;
    $certificate_generator = new TIF_Certificate($config);
    
    if (!$certificate_generator->is_certificate_enabled($order_id)) {
        return '';
    }
    
    $download_url = $certificate_generator->get_download_url($order_id, $atts['type']);
    
    if ($atts['display'] === 'button') {
        return sprintf(
            '<a href="%s" class="tif-certificate-download btn btn-success" target="_blank">
                <i class="fas fa-download"></i> %s
            </a>',
            esc_url($download_url),
            __('Sertifikatı Yüklə', 'kapital-tif-donation')
        );
    }
    
    return '';
}

// Test Order functionality
add_action('admin_menu', 'tif_add_test_order_menu');

function tif_add_test_order_menu() {
    add_submenu_page(
        'edit.php?post_type=odenis',
        'Test Order Yarat',
        'Test Order',
        'manage_options',
        'tif-create-test-order',
        'tif_create_test_order_page'
    );
}

function tif_create_test_order_page() {
    global $config;
    
    if (isset($_POST['create_test_order']) && wp_verify_nonce($_POST['_wpnonce'], 'create_test_order')) {
        
        // Test orderın növünü müəyyən et
        $order_type = sanitize_text_field($_POST['order_type'] ?? 'individual');
        
        if ($order_type === 'legal_entity') {
            // Hüquqi şəxs test order
            $order_id = wp_insert_post(array(
                'post_type' => 'odenis',
                'post_status' => 'publish',
                'post_title' => 'Test Order - Hüquqi Şəxs - ' . date('Y-m-d H:i:s')
            ));
            
            // Hüquqi şəxs məlumatları
            update_post_meta($order_id, 'name', 'Əli Həsənov'); // Fiziki şəxsin adı
            update_post_meta($order_id, 'company', 'Hüquqi şəxs');
            update_post_meta($order_id, 'company_name', 'TechSoft MMC'); // Şirkətin adı - bu sertifikatda görünəcək
            update_post_meta($order_id, 'voen', '1234567890');
            update_post_meta($order_id, 'phone', '+994501234567');
            update_post_meta($order_id, 'amount', '500');
            update_post_meta($order_id, 'iane_tesnifati', 'qtdl'); // Qızların təhsilinə dəstək
            
        } else {
            // Fiziki şəxs test order (əvvəlki)
            $order_id = wp_insert_post(array(
                'post_type' => 'odenis',
                'post_status' => 'publish',
                'post_title' => 'Test Order - Fiziki Şəxs - ' . date('Y-m-d H:i:s')
            ));
            
            // Fiziki şəxs məlumatları
            update_post_meta($order_id, 'name', 'Test İstifadəçi');
            update_post_meta($order_id, 'company', 'Fiziki şəxs');
            update_post_meta($order_id, 'phone', '+994501234567');
            update_post_meta($order_id, 'amount', '100');
            update_post_meta($order_id, 'iane_tesnifati', 'tifiane');
        }
        
        // Ümumi məlumatlar (hər iki növ üçün)
        update_post_meta($order_id, 'payment_status', 'completed');
        update_post_meta($order_id, 'payment_date', current_time('mysql'));
        update_post_meta($order_id, 'transactionId_local', 'TEST-' . $order_id);
        update_post_meta($order_id, 'bank_order_id', 'BANK-TEST-' . $order_id);
        update_post_meta($order_id, 'certificate_generated', true);
        
        // Certificate type İanə Təsnifatına görə təyin et
        $iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);
        $certificate_mapping = array(
            'tifiane' => 'tif',
            'qtdl' => 'youth', 
            'qtp' => 'sustainable'
        );
        $certificate_type = $certificate_mapping[$iane_tesnifati] ?? 'tif';
        update_post_meta($order_id, 'certificate_type', $certificate_type);
        
        // Status taxonomy əlavə et
        wp_set_object_terms($order_id, 'completed', 'odenis_statusu');
        
        // Success message with token
        $token = wp_create_nonce('tif_thank_you_' . $order_id);
        $order_type_text = ($order_type === 'legal_entity') ? 'Hüquqi Şəxs' : 'Fiziki Şəxs';
        $company_name = get_post_meta($order_id, 'company_name', true);
        $name = get_post_meta($order_id, 'name', true);
        
        echo '<div class="notice notice-success"><p>';
        echo '<strong>Test order yaradıldı!</strong><br>';
        echo '<strong>Order ID:</strong> ' . $order_id . '<br>';
        echo '<strong>Növ:</strong> ' . $order_type_text . '<br>';
        if ($order_type === 'legal_entity') {
            echo '<strong>Şirkət:</strong> ' . $company_name . ' (sertifikatda bu ad görünəcək)<br>';
            echo '<strong>Fiziki şəxs:</strong> ' . $name . '<br>';
        } else {
            echo '<strong>Ad:</strong> ' . $name . '<br>';
        }
        echo '<strong>İanə Təsnifatı:</strong> ' . get_post_meta($order_id, 'iane_tesnifati', true) . '<br>';
        echo '<strong>Sertifikat növü:</strong> ' . $certificate_type . '<br><br>';
        
        // Links
        echo '<a href="' . admin_url('post.php?post=' . $order_id . '&action=edit') . '" class="button button-primary">Order-i Redaktə Et</a> ';
        echo '<a href="' . home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=success&token=' . $token) . '" target="_blank" class="button button-secondary">Thank You səhifəsinə keç</a>';
        echo '</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>Test Order Yarat</h1>
        <form method="post">
            <?php wp_nonce_field('create_test_order'); ?>
            
            <div class="card" style="max-width: 700px; padding: 20px;">
                <h3>Test Order Növü</h3>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Test Order Növü</th>
                        <td>
                            <label>
                                <input type="radio" name="order_type" value="individual" checked>
                                <strong>Fiziki Şəxs</strong> - Test İstifadəçi (100 AZN, TIF)
                            </label><br><br>
                            <label>
                                <input type="radio" name="order_type" value="legal_entity">
                                <strong>Hüquqi Şəxs</strong> - TechSoft MMC (500 AZN, QTDL)
                            </label>
                        </td>
                    </tr>
                </table>
                
                <div id="individual-details" class="test-details">
                    <h4>Fiziki Şəxs Test Məlumatları:</h4>
                    <ul>
                        <li><strong>Ad:</strong> Test İstifadəçi (sertifikatda bu ad görünəcək)</li>
                        <li><strong>Məbləğ:</strong> 100 AZN</li>
                        <li><strong>İanə Təsnifatı:</strong> TIF (Təhsilin İnkişafı Fonduna)</li>
                        <li><strong>Sertifikat:</strong> TIF certificate</li>
                        <li><strong>Status:</strong> Completed (Uğurlu)</li>
                    </ul>
                </div>
                
                <div id="legal-details" class="test-details" style="display: none;">
                    <h4>Hüquqi Şəxs Test Məlumatları:</h4>
                    <ul>
                        <li><strong>Fiziki şəxs:</strong> Əli Həsənov</li>
                        <li><strong>Şirkət:</strong> TechSoft MMC (sertifikatda bu ad görünəcək)</li>
                        <li><strong>VÖEN:</strong> 1234567890</li>
                        <li><strong>Məbləğ:</strong> 500 AZN</li>
                        <li><strong>İanə Təsnifatı:</strong> QTDL (Qızların təhsilinə dəstək)</li>
                        <li><strong>Sertifikat:</strong> Youth certificate</li>
                        <li><strong>Status:</strong> Completed (Uğurlu)</li>
                    </ul>
                </div>
                
                <p class="submit">
                    <button type="submit" name="create_test_order" class="button button-primary">Test Order Yarat</button>
                </p>
            </div>
        </form>
        
        <script>
        jQuery(document).ready(function($) {
            $('input[name="order_type"]').on('change', function() {
                if ($(this).val() === 'legal_entity') {
                    $('#individual-details').hide();
                    $('#legal-details').show();
                } else {
                    $('#individual-details').show();
                    $('#legal-details').hide();
                }
            });
        });
        </script>
    </div>
    <?php
}