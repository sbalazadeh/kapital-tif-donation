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

// Activation hook - EDITOR PERMISSIONS ADDED
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

// Add admin notice for test mode - EDITOR PERMISSIONS UPDATED
function tif_donation_global_admin_notice() {
    if (!current_user_can('edit_posts')) { // CHANGED: manage_options → edit_posts
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

// Certificate system initialization
add_action('init', 'tif_init_certificate_system');

function tif_init_certificate_system() {
    global $config;
    
    // Check if certificate system is enabled
    if (!isset($config['certificate']['enabled']) || !$config['certificate']['enabled']) {
        return;
    }
    
    // Include certificate classes
    require_once TIF_DONATION_PLUGIN_DIR . 'includes/class-tif-certificate.php';
    require_once TIF_DONATION_PLUGIN_DIR . 'includes/class-tif-certificate-templates.php';
    
    // Auto-generate certificates for completed payments
    add_action('tif_payment_completed', 'tif_auto_generate_certificate', 10, 1);
}

// Auto-generate certificate when payment is completed
function tif_auto_generate_certificate($order_id) {
    if (!$order_id) {
        return;
    }
    
    // Check if already generated
    $certificate_generated = get_post_meta($order_id, 'certificate_generated', true);
    if ($certificate_generated) {
        return;
    }
    
    global $config;
    $certificate_generator = new TIF_Certificate($config);
    
    // Get İanə Təsnifatı to determine certificate type
    $iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);
    
    // Certificate type mapping
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

/**
 * NEW FUNCTION: Add Editor Capabilities for İanə Management
 * Called during plugin activation
 */
function tif_add_editor_capabilities() {
    $editor_role = get_role('editor');
    
    if ($editor_role) {
        // İanə post type permissions
        $editor_role->add_cap('edit_odenis');
        $editor_role->add_cap('edit_others_odenis'); 
        $editor_role->add_cap('read_odenis');
        $editor_role->add_cap('read_private_odenis');
        $editor_role->add_cap('edit_published_odenis');
        $editor_role->add_cap('edit_private_odenis');
        $editor_role->add_cap('delete_odenis');
        $editor_role->add_cap('delete_others_odenis');
        $editor_role->add_cap('delete_published_odenis');
        $editor_role->add_cap('delete_private_odenis');
        $editor_role->add_cap('publish_odenis');
        
        // İanə taxonomy permissions  
        $editor_role->add_cap('manage_odenis_statusu');
        $editor_role->add_cap('edit_odenis_statusu');
        $editor_role->add_cap('delete_odenis_statusu');
        $editor_role->add_cap('assign_odenis_statusu');
        
        error_log('TIF Donation: Editor roluna İanə icazələri əlavə edildi');
    } else {
        error_log('TIF Donation: Editor rolu tapılmadı');
    }
}