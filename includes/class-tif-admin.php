<?php
/**
 * Frontend Operations Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Frontend {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * Database instance
     */
    private $database;
    
    /**
     * API instance
     */
    private $api;
    
    /**
     * Constructor
     */
    public function __construct($config, $database, $api) {
        $this->config = $config;
        $this->database = $database;
        $this->api = $api;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'process_payment_callback'), 5);
        add_action('init', array($this, 'setup_query_vars'));
        add_shortcode('tif_payment_form', array($this, 'payment_form_shortcode'));
        add_shortcode('tif_payment_result', array($this, 'payment_result_shortcode'));
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'thank_you';
        $vars[] = 'processing';
        $vars[] = 'payment_failed';
        $vars[] = 'callback';
        return $vars;
    }
    
    /**
     * Setup query vars
     */
    public function setup_query_vars() {
        // This ensures query vars are available
    }
    
    /**
     * Payment form shortcode
     */
    public function payment_form_shortcode($atts) {
        // Don't show form if processing results
        if (isset($_GET['callback']) || isset($_GET['thank_you']) || isset($_GET['processing']) || isset($_GET['payment_failed'])) {
            return '';
        }
        
        ob_start();
        
        // Process form submission
        if (isset($_GET['gotopayment']) && $this->validate_form_data($_GET)) {
            echo $this->process_payment_form($_GET);
            return ob_get_clean();
        }
        
        // Load form template
        $this->load_template('payment-form', array('config' => $this->config));
        
        return ob_get_clean();
    }
    
    /**
     * Payment result shortcode
     */
    public function payment_result_shortcode($atts) {
        if (isset($_GET['thank_you'])) {
            return $this->render_thank_you_page();
        }
        
        if (isset($_GET['payment_failed'])) {
            return $this->render_payment_failed_page();
        }
        
        return '';
    }
    
    /**
     * Validate form data
     */
    private function validate_form_data($data) {
        $name = isset($data['ad_soyad']) ? sanitize_text_field($data['ad_soyad']) : '';
        $phone = isset($data['telefon_nomresi']) ? sanitize_text_field($data['telefon_nomresi']) : '';
        $amount = isset($data['mebleg']) ? floatval($data['mebleg']) : 0;
        $company_type = isset($data['fiziki_huquqi']) ? sanitize_text_field($data['fiziki_huquqi']) : 'Fiziki şəxs';
        $company_name = isset($data['teskilat_adi']) ? sanitize_text_field($data['teskilat_adi']) : '';
        
        // Validate required fields
        if (empty($name) || empty($phone)) {
            return false;
        }
        
        // Validate amount
        if ($amount < $this->config['payment']['min_amount'] || $amount > $this->config['payment']['max_amount']) {
            return false;
        }
        
        // Validate company name for legal entities
        if ($company_type === 'Hüquqi şəxs' && empty($company_name)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Process payment form
     */
    private function process_payment_form($data) {
        $amount = floatval($data['mebleg']);
        
        // Create order
        $order_id = $this->database->create_order($amount, $data);
        
        if (!$order_id) {
            return '<div class="alert alert-danger">' . __('Sipariş yaradılarkən xəta baş verdi.', 'kapital-tif-donation') . '</div>';
        }
        
        // Create payment order
        $callback_url = home_url('/donation/?callback=1&wpid=' . $order_id);
        $payment_response = $this->api->create_order($amount, $order_id, $callback_url);
        
        if ($payment_response && isset($payment_response['order']['id'])) {
            return $this->api->generate_payment_redirect(
                $payment_response['order']['id'],
                $payment_response['order']['password']
            );
        }
        
        return '<div class="alert alert-danger">' . __('Ödəniş yaradılarkən xəta baş verdi. Zəhmət olmasa biraz sonra yenidən cəhd edin.', 'kapital-tif-donation') . '</div>';
    }
    
    /**
     * Process payment callback
     */
    public function process_payment_callback() {
        // Handle processing page
        if (isset($_GET['processing'])) {
            $this->handle_processing_page();
            return;
        }
        
        // Check if this is a payment callback
        if (!isset($_GET['callback']) || !isset($_GET['wpid'])) {
            return;
        }
        
        $wp_order_id = intval($_GET['wpid']);
        if (!$wp_order_id) {
            return;
        }
        
        // Verify order exists
        $post = get_post($wp_order_id);
        if (!$post || $post->post_type !== $this->config['general']['post_type']) {
            return;
        }
        
        // Get callback status
        $callback_status = isset($_GET['STATUS']) ? $_GET['STATUS'] : null;
        
        // Validate payment
        $validation_result = $this->api->validate_callback($wp_order_id, $callback_status);
        
        if (!$validation_result['success']) {
            wp_redirect($this->get_failure_redirect_url($wp_order_id, 'validation_failed'));
            exit;
        }
        
        // Process payment status
        $result = $this->process_payment_status($wp_order_id, $validation_result['status'], $validation_result['order_data']);
        
        if (isset($result['redirect'])) {
            wp_redirect($result['redirect']);
            exit;
        }
        
        // Fallback redirect
        wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $wp_order_id . '&status=processing'));
        exit;
    }
    
    /**
     * Handle processing page
     */
    private function handle_processing_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if ($order_id > 0) {
            $bank_order_id = get_post_meta($order_id, 'bank_order_id', true);
            
            if ($bank_order_id) {
                $status_response = $this->api->get_order_status($bank_order_id);
                
                if ($status_response && isset($status_response['order']['status']) && $status_response['order']['status'] === 'FullyPaid') {
                    $this->database->complete_order($order_id);
                    wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=success'));
                    exit;
                }
            }
            
            wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=processing'));
            exit;
        }
    }
    
    /**
     * Process payment status
     */
    private function process_payment_status($wp_order_id, $status, $order_data) {
        // Update order with additional data
        update_post_meta($wp_order_id, 'order_data', $order_data);
        
        switch ($status) {
            case 'FullyPaid':
                $this->database->complete_order($wp_order_id);
                return array(
                    'success' => true,
                    'redirect' => $this->get_success_redirect_url($wp_order_id, 'success')
                );
                
            case 'PreAuthorized':
            case 'Prepared':
            case 'Preparing':
            case 'Processing':
                $this->database->update_order_status($wp_order_id, $status);
                return array(
                    'success' => true,
                    'redirect' => $this->get_success_redirect_url($wp_order_id, 'processing')
                );
                
            case 'Cancelled':
                $this->database->update_order_status($wp_order_id, 'Cancelled');
                return array(
                    'success' => false,
                    'redirect' => $this->get_failure_redirect_url($wp_order_id, 'cancelled')
                );
                
            case 'Declined':
                $this->database->update_order_status($wp_order_id, 'Failed');
                return array(
                    'success' => false,
                    'redirect' => $this->get_failure_redirect_url($wp_order_id, 'declined')
                );
                
            default:
                $this->database->update_order_status($wp_order_id, 'Unknown: ' . $status);
                return array(
                    'success' => false,
                    'redirect' => $this->get_failure_redirect_url($wp_order_id, urlencode($status))
                );
        }
    }
    
    /**
     * Get success redirect URL
     */
    private function get_success_redirect_url($order_id, $status) {
        return home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=' . $status);
    }
    
    /**
     * Get failure redirect URL
     */
    private function get_failure_redirect_url($order_id, $status) {
        return home_url('/donation/?payment_failed=1&order_id=' . $order_id . '&status=' . $status);
    }
    
    /**
     * Render thank you page
     */
    private function render_thank_you_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        if ($order_id > 0) {
            $order = get_post($order_id);
            if ($order && $order->post_type === $this->config['general']['post_type']) {
                ob_start();
                $this->load_template('thank-you', array(
                    'order_id' => $order_id,
                    'status' => $status,
                    'order' => $order
                ));
                return ob_get_clean();
            }
        }
        
        return '';
    }
    
    /**
     * Render payment failed page
     */
    private function render_payment_failed_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'unknown';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        
        if ($order_id > 0) {
            $order = get_post($order_id);
            if ($order && $order->post_type === $this->config['general']['post_type']) {
                ob_start();
                $this->load_template('payment-failed', array(
                    'order_id' => $order_id,
                    'status' => $status,
                    'error' => $error,
                    'order' => $order
                ));
                return ob_get_clean();
            }
        }
        
        return '';
    }
    
    /**
     * Load template
     */
    private function load_template($template, $args = array()) {
        $template_file = TIF_DONATION_TEMPLATES_DIR . $template . '.php';
        
        if (file_exists($template_file)) {
            extract($args);
            include $template_file;
        } else {
            echo '<p>' . sprintf(__('Template faylı tapılmadı: %s', 'kapital-tif-donation'), $template) . '</p>';
        }
    }
}