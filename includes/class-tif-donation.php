<?php
/**
 * Main Plugin Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Donation {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Plugin configuration
     */
    public $config;
    
    /**
     * Plugin components
     */
    public $admin;
    public $frontend;
    public $api;
    public $database;
    
    /**
     * Get plugin instance
     */
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_config();
        $this->init_hooks();
        $this->init_components();
    }
    
    /**
     * Load configuration
     */
    private function load_config() {
        $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
        if (file_exists($config_file)) {
            $this->config = require $config_file;
        } else {
            wp_die(__('Konfiqurasiya faylı tapılmadı.', 'kapital-tif-donation'));
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'load_textdomain'));
        add_action('init', array($this, 'init_post_types'), 0);
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_tif_sync_payment_status', array($this, 'ajax_sync_payment_status'));
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Database operations
        $this->database = new TIF_Database($this->config);
        
        // API integration
        $this->api = new TIF_API($this->config);
        
        // Admin interface
        if (is_admin()) {
            $this->admin = new TIF_Admin($this->config, $this->database, $this->api);
        }
        
        // Frontend interface
        if (!is_admin()) {
            $this->frontend = new TIF_Frontend($this->config, $this->database, $this->api);
        }
    }
    
    /**
     * Load text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'kapital-tif-donation',
            false,
            dirname(plugin_basename(TIF_DONATION_PLUGIN_FILE)) . '/languages'
        );
    }
    
    /**
     * Initialize post types and taxonomies
     */
    public function init_post_types() {
        $this->database->register_post_type();
        $this->database->register_taxonomy();
        $this->database->create_default_terms();
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        if (!$this->config['frontend']['load_styles'] && !$this->config['frontend']['load_scripts']) {
            return;
        }
        
        // Only load on pages with our shortcodes
        global $post;
        if (!$post || (!has_shortcode($post->post_content, 'tif_payment_form') && 
                      !has_shortcode($post->post_content, 'tif_payment_result'))) {
            return;
        }
        
        if ($this->config['frontend']['load_styles']) {
            wp_enqueue_style(
                'tif-donation-style',
                TIF_DONATION_ASSETS_URL . 'css/style.css',
                array(),
                TIF_DONATION_VERSION
            );
        }
        
        if ($this->config['frontend']['load_scripts']) {
            wp_enqueue_script(
                'tif-donation-script',
                TIF_DONATION_ASSETS_URL . 'js/script.js',
                array('jquery'),
                TIF_DONATION_VERSION,
                true
            );
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== $this->config['general']['post_type']) {
            return;
        }
        
        wp_enqueue_script(
            'tif-donation-admin',
            TIF_DONATION_ASSETS_URL . 'js/admin.js',
            array('jquery'),
            TIF_DONATION_VERSION,
            true
        );
        
        wp_localize_script('tif-donation-admin', 'tif_admin_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce($this->config['security']['nonce_actions']['sync_status']),
        ));
    }
    
    /**
     * AJAX handler for status sync
     */
    public function ajax_sync_payment_status() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], $this->config['security']['nonce_actions']['sync_status'])) {
            wp_send_json_error(array('message' => __('Təhlükəsizlik yoxlaması uğursuz oldu.', 'kapital-tif-donation')));
        }
        
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Sifarişin ID-si tapılmadı.', 'kapital-tif-donation')));
        }
        
        $result = $this->database->sync_payment_status($post_id, $this->api);
        
        if ($result) {
            wp_send_json_success(array('status' => $result));
        } else {
            wp_send_json_error(array('message' => __('Status yeniləmək mümkün olmadı.', 'kapital-tif-donation')));
        }
    }
    
    /**
     * Get configuration value
     */
    public function get_config($key = null, $default = null) {
        if (null === $key) {
            return $this->config;
        }
        
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }
    
    /**
     * Get API credentials
     */
    public function get_api_config() {
        $env = $this->config['test_mode'] ? 'test' : 'production';
        return $this->config[$env];
    }
    
    /**
     * Log message
     */
    public function log($message, $level = 'info') {
        if (!$this->config['debug']['log_api_requests']) {
            return;
        }
        
        $log_file = $this->config['debug']['log_file'];
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        // Check log file size
        if (file_exists($log_file) && filesize($log_file) > $this->config['debug']['max_log_size']) {
            // Rotate log file
            rename($log_file, $log_file . '.old');
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Plugin activation
     */
    public static function activate() {
        // Create upload directory if it doesn't exist
        $upload_dir = wp_upload_dir();
        if (!file_exists($upload_dir['basedir'])) {
            wp_mkdir_p($upload_dir['basedir']);
        }
        
        // Set default options
        $default_options = array(
            'version' => TIF_DONATION_VERSION,
            'installed_at' => time(),
        );
        
        add_option('tif_donation_options', $default_options);
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        // Clear scheduled events
        wp_clear_scheduled_hook('tif_hourly_status_sync');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

// Certificate functionality initialization
if (isset($config['certificate']['enabled']) && $config['certificate']['enabled']) {
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