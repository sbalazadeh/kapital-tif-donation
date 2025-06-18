<?php
/**
 * Frontend Operations Class - SYNTAX ERROR FIXED
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Frontend {
    
    private $config;
    private $database;
    private $api;
    private $cache_group = 'tif_donation';
    private $cache_expiry = 300; // 5 minutes
    
    public function __construct($config, $database, $api) {
        $this->config = $config;
        $this->database = $database;
        $this->api = $api;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('init', array($this, 'process_payment_callback'), 5);
        add_action('init', array($this, 'setup_query_vars'));
        add_shortcode('tif_payment_form', array($this, 'payment_form_shortcode'));
        add_shortcode('tif_payment_result', array($this, 'payment_result_shortcode'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        
        // Security headers-i erkən hook ilə əlavə et
        add_action('template_redirect', array($this, 'add_security_headers'), 1);
    }
    
    /**
     * Add security headers for payment pages - Fixed version
     */
    public function add_security_headers() {
        // Headers artıq göndərildisə, heç nə etmə
        if (headers_sent()) {
            return;
        }
        
        // Yalnız payment səhifələrində header əlavə et
        if ($this->is_payment_page()) {
            header('X-Frame-Options: DENY');
            header('X-Content-Type-Options: nosniff');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            
            // Cache control for payment pages
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Check if current page is payment related
     */
    private function is_payment_page() {
        // Callback səhifələri
        if (isset($_GET['callback']) || isset($_GET['thank_you']) || 
            isset($_GET['payment_failed']) || isset($_GET['processing'])) {
            return true;
        }
        
        // Shortcode olan səhifələr
        global $post;
        return $post && (
            has_shortcode($post->post_content, 'tif_payment_form') ||
            has_shortcode($post->post_content, 'tif_payment_result')
        );
    }
    
    public function add_query_vars($vars) {
        return array_merge($vars, array('thank_you', 'processing', 'payment_failed', 'callback'));
    }
    
    public function setup_query_vars() {
        // Reserved for future use
    }
    
    /**
     * Payment form shortcode with caching
     */
    public function payment_form_shortcode($atts) {
        // Don't cache if processing results
        if (isset($_GET['callback']) || isset($_GET['thank_you']) || 
            isset($_GET['processing']) || isset($_GET['payment_failed'])) {
            return '';
        }
        
        // Form validation xətaları göstər
        $form_errors = $this->get_form_errors();
        $error_html = '';
        if (!empty($form_errors)) {
            $error_html = '<div class="alert alert-danger"><ul>';
            foreach ($form_errors as $error) {
                $error_html .= '<li>' . esc_html($error) . '</li>';
            }
            $error_html .= '</ul></div>';
            $this->clear_form_errors();
        }
        
        // Process form submission
        if (isset($_GET['gotopayment']) && $this->validate_form_data($_GET)) {
            return $this->process_payment_form($_GET);
        }
        
        // Cache the form HTML for performance
        $cache_key = 'payment_form_' . md5(serialize($atts));
        $cached_form = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $cached_form) {
            ob_start();
            $this->load_template('payment-form', array('config' => $this->config));
            $cached_form = ob_get_clean();
            
            wp_cache_set($cache_key, $cached_form, $this->cache_group, $this->cache_expiry);
        }
        
        return $error_html . $cached_form;
    }
    
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
     * Get form errors from transient
     */
    private function get_form_errors() {
        $user_ip = $this->get_user_ip();
        $errors = get_transient('tif_form_errors_' . md5($user_ip));
        return $errors ? $errors : array();
    }
    
    /**
     * Clear form errors
     */
    private function clear_form_errors() {
        $user_ip = $this->get_user_ip();
        delete_transient('tif_form_errors_' . md5($user_ip));
    }
    
    /**
     * Enhanced form validation with İanə Təsnifatı validation
     */
    private function validate_form_data($data) {
        $errors = array();
        
        $name = isset($data['ad_soyad']) ? sanitize_text_field($data['ad_soyad']) : '';
        $phone = isset($data['telefon_nomresi']) ? sanitize_text_field($data['telefon_nomresi']) : '';
        $amount = isset($data['mebleg']) ? floatval($data['mebleg']) : 0;
        $company_type = isset($data['fiziki_huquqi']) ? sanitize_text_field($data['fiziki_huquqi']) : 'Fiziki şəxs';
        $company_name = isset($data['teskilat_adi']) ? sanitize_text_field($data['teskilat_adi']) : '';
        $voen = isset($data['voen']) ? sanitize_text_field($data['voen']) : '';
        
        // İanə Təsnifatı validation
        $iane_tesnifati = isset($data['iane_tesnifati']) ? sanitize_text_field($data['iane_tesnifati']) : '';
        
        // Validate name
        if (empty($name) || strlen($name) < 2) {
            $errors[] = __('Ad və soyad ən azı 2 simvol olmalıdır.', 'kapital-tif-donation');
        }
        
        // Validate phone - Azerbaijan format
        $clean_phone = preg_replace('/[^\d]/', '', $phone);
        if (empty($clean_phone) || strlen($clean_phone) < 9 || strlen($clean_phone) > 13) {
            $errors[] = __('Telefon nömrəsi düzgün formatda deyil.', 'kapital-tif-donation');
        }
        
        // Validate amount
        if ($amount < $this->config['payment']['min_amount']) {
            $errors[] = sprintf(__('Minimum məbləğ %s %s olmalıdır.', 'kapital-tif-donation'), 
                            $this->config['payment']['min_amount'], 
                            $this->config['payment']['currency']);
        }
        
        if ($amount > $this->config['payment']['max_amount']) {
            $errors[] = sprintf(__('Maximum məbləğ %s %s olmalıdır.', 'kapital-tif-donation'), 
                            $this->config['payment']['max_amount'], 
                            $this->config['payment']['currency']);
        }
        
        // İanə Təsnifatı validation - HƏM FİZİKİ HƏM HÜQUQİ ŞƏXS ÜÇÜN MƏCBURİDİR
        if (empty($iane_tesnifati)) {
            $errors[] = __('İanə təsnifatı seçilməlidir.', 'kapital-tif-donation');
        } else {
            // Validate that the selected option is one of the allowed values
            $allowed_values = array('tifiane', 'qtdl', 'qtp');
            if (!in_array($iane_tesnifati, $allowed_values)) {
                $errors[] = __('Seçilən ianə təsnifatı düzgün deyil.', 'kapital-tif-donation');
            }
        }
        
        // Validate company name and VÖEN YALNIZ hüquqi şəxs üçün
        if ($company_type === 'Hüquqi şəxs') {
            if (empty($company_name) || strlen($company_name) < 2) {
                $errors[] = __('Hüquqi şəxs üçün qurumun adı məcburidir.', 'kapital-tif-donation');
            }
            
            // VÖEN validation YALNIZ hüquqi şəxs üçün
            $clean_voen = preg_replace('/[^\d]/', '', $voen);
            if (empty($clean_voen) || strlen($clean_voen) !== 10) {
                $errors[] = __('VÖEN 10 rəqəmdən ibarət olmalıdır.', 'kapital-tif-donation');
            }
        }
        
        // Store errors in transient instead of session for better performance
        if (!empty($errors)) {
            $user_ip = $this->get_user_ip();
            set_transient('tif_form_errors_' . md5($user_ip), $errors, 300); // 5 minutes
            return false;
        }
        
        return true;
    }
    
    /**
     * Enhanced payment processing with rate limiting
     */
    private function process_payment_form($data) {
        // Rate limiting check
        if (!$this->check_rate_limit()) {
            return '<div class="alert alert-danger">' . 
                   __('Çox tez-tez sorğu göndərirsiniz. Zəhmət olmasa biraz gözləyin.', 'kapital-tif-donation') . 
                   '</div>';
        }
        
        $amount = floatval($data['mebleg']);
        
        // Create order with error handling
        try {
            $order_id = $this->database->create_order($amount, $data);
            
            if (!$order_id) {
                throw new Exception(__('Sipariş yaradılarkən xəta baş verdi.', 'kapital-tif-donation'));
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
            
            throw new Exception(__('Ödəniş yaradılarkən xəta baş verdi.', 'kapital-tif-donation'));
            
        } catch (Exception $e) {
            // Log error
            error_log('TIF Donation Error: ' . $e->getMessage());
            
            return '<div class="alert alert-danger">' . 
                   esc_html($e->getMessage()) . ' ' .
                   __('Zəhmət olmasa biraz sonra yenidən cəhd edin.', 'kapital-tif-donation') . 
                   '</div>';
        }
    }
    
    /**
     * Rate limiting for payment attempts
     */
    private function check_rate_limit() {
        $user_ip = $this->get_user_ip();
        $cache_key = 'rate_limit_' . md5($user_ip);
        $attempts = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $attempts) {
            $attempts = 0;
        }
        
        // Allow 3 attempts per 5 minutes
        if ($attempts >= 3) {
            return false;
        }
        
        $attempts++;
        wp_cache_set($cache_key, $attempts, $this->cache_group, 300);
        
        return true;
    }
    
    /**
     * Get user IP address safely
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Enhanced callback processing with better error handling
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
            wp_redirect($this->get_failure_redirect_url(0, 'invalid_order'));
            exit;
        }
        
        // Verify order exists and belongs to our post type
        $post = get_post($wp_order_id);
        if (!$post || $post->post_type !== $this->config['general']['post_type']) {
            wp_redirect($this->get_failure_redirect_url($wp_order_id, 'order_not_found'));
            exit;
        }
        
        // Prevent duplicate processing
        $processing_key = 'processing_' . $wp_order_id;
        if (wp_cache_get($processing_key, $this->cache_group)) {
            // Already being processed, redirect to processing page
            wp_redirect(home_url('/donation/?processing=1&order_id=' . $wp_order_id));
            exit;
        }
        
        // Mark as being processed
        wp_cache_set($processing_key, true, $this->cache_group, 60); // 1 minute lock
        
        try {
            // Get callback status
            $callback_status = isset($_GET['STATUS']) ? sanitize_text_field($_GET['STATUS']) : null;
            
            // Validate payment
            $validation_result = $this->api->validate_callback($wp_order_id, $callback_status);
            
            if (!$validation_result['success']) {
                throw new Exception($validation_result['error'] ?? 'Validation failed');
            }
            
            // Process payment status
            $result = $this->process_payment_status(
                $wp_order_id, 
                $validation_result['status'], 
                $validation_result['order_data']
            );
            
            // Clear processing lock
            wp_cache_delete($processing_key, $this->cache_group);
            
            if (isset($result['redirect'])) {
                wp_redirect($result['redirect']);
                exit;
            }
            
        } catch (Exception $e) {
            // Clear processing lock
            wp_cache_delete($processing_key, $this->cache_group);
            
            // Log error
            error_log('TIF Donation Callback Error: ' . $e->getMessage());
            
            wp_redirect($this->get_failure_redirect_url($wp_order_id, 'processing_error'));
            exit;
        }
        
        // Fallback redirect
        wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $wp_order_id . '&status=processing'));
        exit;
    }
    
    private function handle_processing_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        
        if ($order_id > 0) {
            $bank_order_id = get_post_meta($order_id, 'bank_order_id', true);
            
            if ($bank_order_id) {
                try {
                    $status_response = $this->api->get_order_status($bank_order_id);
                    
                    if ($status_response && isset($status_response['order']['status']) && 
                        $status_response['order']['status'] === 'FullyPaid') {
                        
                        $this->database->complete_order($order_id);
                        wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=success'));
                        exit;
                    }
                } catch (Exception $e) {
                    error_log('TIF Donation Processing Page Error: ' . $e->getMessage());
                }
            }
            
            wp_redirect(home_url('/donation/?thank_you=1&order_id=' . $order_id . '&status=processing'));
            exit;
        }
    }
    
    private function process_payment_status($wp_order_id, $status, $order_data) {
        // Update order with additional data
        update_post_meta($wp_order_id, 'order_data', $order_data);
        update_post_meta($wp_order_id, 'last_status_check', current_time('mysql'));
        
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
    
    private function get_success_redirect_url($order_id, $status) {
        return add_query_arg(array(
            'thank_you' => '1',
            'order_id' => $order_id,
            'status' => $status,
            'token' => wp_create_nonce('tif_thank_you_' . $order_id)
        ), home_url('/donation/'));
    }
    
    private function get_failure_redirect_url($order_id, $status) {
        return add_query_arg(array(
            'payment_failed' => '1',
            'order_id' => $order_id,
            'status' => $status,
            'token' => wp_create_nonce('tif_failed_' . $order_id)
        ), home_url('/donation/'));
    }
    
    private function render_thank_you_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        // Verify token for security
        if (!wp_verify_nonce($token, 'tif_thank_you_' . $order_id)) {
            return '<div class="alert alert-danger">' . 
                   __('Təhlükəsizlik xətası.', 'kapital-tif-donation') . 
                   '</div>';
        }
        
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
    
    // FIXED: Missing closing bracket in intval() function
    private function render_payment_failed_page() {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0; // FIXED SYNTAX ERROR
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'unknown';
        $error = isset($_GET['error']) ? sanitize_text_field($_GET['error']) : '';
        $token = isset($_GET['token']) ? sanitize_text_field($_GET['token']) : '';
        
        // Verify token for security
        if (!wp_verify_nonce($token, 'tif_failed_' . $order_id)) {
            return '<div class="alert alert-danger">' . 
                   __('Təhlükəsizlik xətası.', 'kapital-tif-donation') . 
                   '</div>';
        }
        
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
    
    private function load_template($template, $args = array()) {
        $template_file = TIF_DONATION_TEMPLATES_DIR . $template . '.php';
        
        if (file_exists($template_file)) {
            extract($args);
            include $template_file;
        } else {
            echo '<p>' . sprintf(__('Template faylı tapılmadı: %s', 'kapital-tif-donation'), esc_html($template)) . '</p>';
        }
    }
}