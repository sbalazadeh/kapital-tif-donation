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