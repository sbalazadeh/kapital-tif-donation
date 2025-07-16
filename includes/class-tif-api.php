<?php
/**
 * Kapital Bank API Integration Class - CORRECTED VERSION
 * Replace the entire class-tif-api.php file with this content
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_API {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * API configuration
     */
    private $api_config;
    
    /**
     * Constructor
     */
    public function __construct($config) {
        $this->config = $config;
        $this->api_config = $config['test_mode'] ? $config['test'] : $config['production'];
    }
    
    /**
     * Enhanced make_request with detailed logging
     */
    public function make_request($endpoint, $body = null, $method = 'POST') {
        $url = $this->api_config['api_url'] . $endpoint;
        
        error_log("TIF API Debug - make_request started");
        error_log("TIF API Debug - Method: {$method}");
        error_log("TIF API Debug - URL: {$url}");
        error_log("TIF API Debug - Username: " . $this->api_config['username']);
        error_log("TIF API Debug - Password: " . (empty($this->api_config['password']) ? 'EMPTY' : 'SET'));
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8',
                'Authorization' => 'Basic ' . base64_encode($this->api_config['username'] . ':' . $this->api_config['password'])
            ),
            'sslverify' => $this->config['security']['ssl_verify'],
            'timeout' => $this->config['payment']['timeout'],
        );
        
        if ($body !== null) {
            if (is_array($body)) {
                $args['body'] = json_encode($body, JSON_UNESCAPED_UNICODE);
            } else {
                $args['body'] = $body;
            }
            error_log("TIF API Debug - Request Body: " . $args['body']);
        }
        
        error_log("TIF API Debug - SSL Verify: " . ($this->config['security']['ssl_verify'] ? 'true' : 'false'));
        error_log("TIF API Debug - Timeout: " . $this->config['payment']['timeout']);
        
        // Log request
        $this->log_request($endpoint, $args);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            error_log("TIF API Debug - WP Error: {$error_message}");
            $this->log_error("API Request Failed: {$error_message}");
            
            return array(
                'errorCode' => 'ConnectionError',
                'errorDescription' => $error_message
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);
        
        error_log("TIF API Debug - Response Code: {$response_code}");
        error_log("TIF API Debug - Response Headers: " . print_r($response_headers, true));
        error_log("TIF API Debug - Response Body: {$response_body}");
        
        // Log response
        $this->log_response($endpoint, $response_code, $response_body);
        
        $json_response = json_decode($response_body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("TIF API Debug - JSON Decode Error: " . json_last_error_msg());
            $this->log_error("JSON Decode Error: " . json_last_error_msg());
            return array(
                'errorCode' => 'JSONError',
                'errorDescription' => 'Invalid JSON response from API',
                'raw_response' => $response_body
            );
        }
        
        error_log("TIF API Debug - Parsed JSON Response: " . print_r($json_response, true));
        
        return $json_response;
    }
    
    /**
     * Create payment order
     */
    public function create_order($amount, $wp_order_id, $callback_url) {
        // Format amount
        $amount = number_format((float)$amount, 2, '.', '');
        
        // Get transaction ID
        $transaction_id = get_post_meta($wp_order_id, 'transactionId_local', true);
        
        $order_data = array(
            'order' => array(
                'typeRid' => 'Order_SMS',
                'amount' => $amount,
                'currency' => $this->config['payment']['currency'],
                'language' => $this->config['payment']['language'],
                'description' => sprintf(__('TIF İanə %s', 'kapital-tif-donation'), $transaction_id),
                'hppRedirectUrl' => $callback_url
            )
        );
        
        $response = $this->make_request('/order', $order_data);
        
        if (isset($response['order']['id']) && isset($response['order']['password'])) {
            // Save order details
            update_post_meta($wp_order_id, 'bank_order_id', $response['order']['id']);
            update_post_meta($wp_order_id, 'bank_order_password', $response['order']['password']);
            update_post_meta($wp_order_id, 'order_data', $response);
            update_post_meta($wp_order_id, 'payment_method', 'Kapital Bank');
            
            return $response;
        }
        
        return false;
    }
    
    /**
     * Get order status
     */
    public function get_order_status($bank_order_id) {
        $endpoint = '/order/' . $bank_order_id;
        return $this->make_request($endpoint, null, 'GET');
    }
    
    /**
     * Enhanced get_detailed_order_status with detailed logging
     */
    public function get_detailed_order_status($bank_order_id) {
        $endpoint = '/order/' . $bank_order_id . '/?tranDetailLevel=2&tokenDetailLevel=2&orderDetailLevel=2';
        
        error_log("TIF API Debug - get_detailed_order_status called");
        error_log("TIF API Debug - Endpoint: {$endpoint}");
        error_log("TIF API Debug - Full URL: " . $this->api_config['api_url'] . $endpoint);
        
        $response = $this->make_request($endpoint, null, 'GET');
        
        error_log("TIF API Debug - Raw API Response: " . print_r($response, true));
        
        // Check for error codes
        if (isset($response['errorCode'])) {
            error_log("TIF API Debug - API Error Code: " . $response['errorCode']);
            error_log("TIF API Debug - API Error Description: " . ($response['errorDescription'] ?? 'No description'));
        }
        
        return $response;
    }
    
    /**
     * Process refund
     */
    public function process_refund($bank_order_id, $amount) {
        $endpoint = '/order/' . $bank_order_id . '/exec-tran';
        
        $refund_data = array(
            'tran' => array(
                'phase' => 'Single',
                'amount' => number_format((float)$amount, 2, '.', ''),
                'type' => 'Refund'
            )
        );
        
        return $this->make_request($endpoint, $refund_data);
    }
    
    /**
     * Process reversal
     */
    public function process_reversal($bank_order_id, $void_kind = 'Full', $amount = null) {
        $endpoint = '/order/' . $bank_order_id . '/exec-tran';
        
        $reversal_data = array(
            'tran' => array(
                'phase' => 'Single',
                'voidKind' => $void_kind
            )
        );
        
        if ($amount !== null && $void_kind === 'Partial') {
            $reversal_data['tran']['amount'] = number_format((float)$amount, 2, '.', '');
        }
        
        return $this->make_request($endpoint, $reversal_data);
    }
    
    /**
     * Generate payment redirect HTML
     */
    public function generate_payment_redirect($bank_order_id, $bank_order_password) {
        ob_start();
        ?>
        <div id="payment-redirecting" style="text-align:center; padding:30px;">
            <h3><?php _e('Ödəniş səhifəsinə yönləndirilirsiniz...', 'kapital-tif-donation'); ?></h3>
            <p><?php _e('Avtomatik yönləndirmə işləməsə, aşağıdakı düyməyə klikləyin:', 'kapital-tif-donation'); ?></p>
            
            <form id="payment_form" method="post" action="<?php echo esc_url($this->api_config['hpp_url']); ?>">
                <input type="hidden" name="id" value="<?php echo esc_attr($bank_order_id); ?>">
                <input type="hidden" name="password" value="<?php echo esc_attr($bank_order_password); ?>">
                <button type="submit" class="btn btn-primary">
                    <?php _e('Ödəniş səhifəsinə keç', 'kapital-tif-donation'); ?>
                </button>
            </form>
            
            <script>
                setTimeout(function() {
                    document.getElementById('payment_form').submit();
                }, 1000);
            </script>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Enhanced validate callback with detailed logging
     */
    public function validate_callback($wp_order_id, $callback_status = null) {
        $bank_order_id = get_post_meta($wp_order_id, 'bank_order_id', true);
        
        // Enhanced logging
        error_log("TIF API Debug - validate_callback started");
        error_log("TIF API Debug - WP Order ID: {$wp_order_id}");
        error_log("TIF API Debug - Bank Order ID: {$bank_order_id}");
        error_log("TIF API Debug - Callback Status: " . ($callback_status ?: 'null'));
        
        if (empty($bank_order_id)) {
            error_log("TIF API Debug - ERROR: Bank order ID is empty");
            return array(
                'success' => false,
                'error' => 'Bank order ID not found'
            );
        }
        
        // Get order status from API with enhanced logging
        error_log("TIF API Debug - Calling get_detailed_order_status with ID: {$bank_order_id}");
        $order_data = $this->get_detailed_order_status($bank_order_id);
        
        // Log the full API response
        error_log("TIF API Debug - Full API Response: " . print_r($order_data, true));
        
        if (empty($order_data)) {
            error_log("TIF API Debug - ERROR: Order data is completely empty");
            return array(
                'success' => false,
                'error' => 'API returned empty response'
            );
        }
        
        if (!isset($order_data['order'])) {
            error_log("TIF API Debug - ERROR: order key missing in response");
            error_log("TIF API Debug - Available keys: " . implode(', ', array_keys($order_data)));
            return array(
                'success' => false,
                'error' => 'API response missing order data'
            );
        }
        
        if (!isset($order_data['order']['status'])) {
            error_log("TIF API Debug - ERROR: order.status missing");
            error_log("TIF API Debug - Available order keys: " . implode(', ', array_keys($order_data['order'])));
            return array(
                'success' => false,
                'error' => 'API response missing order status'
            );
        }
        
        $api_status = $order_data['order']['status'];
        error_log("TIF API Debug - SUCCESS: API Status found: {$api_status}");
        
        // Use API status as it's more reliable
        $final_status = $api_status;
        
        // Save order data
        update_post_meta($wp_order_id, 'order_data', $order_data);
        update_post_meta($wp_order_id, 'last_api_check', current_time('mysql'));
        
        // Extract transaction details
        $this->extract_transaction_details($wp_order_id, $order_data);
        
        error_log("TIF API Debug - validate_callback completed successfully with status: {$final_status}");
        
        return array(
            'success' => true,
            'status' => $final_status,
            'order_data' => $order_data
        );
    }
    
    /**
     * Extract transaction details from order data
     */
    private function extract_transaction_details($wp_order_id, $order_data) {
        // Extract transaction details
        if (isset($order_data['order']['trans']) && is_array($order_data['order']['trans']) && !empty($order_data['order']['trans'])) {
            $trans_data = $order_data['order']['trans'][0];
            
            if (isset($trans_data['approvalCode'])) {
                update_post_meta($wp_order_id, 'approval_code', $trans_data['approvalCode']);
            }
            
            if (isset($trans_data['actionId'])) {
                update_post_meta($wp_order_id, 'action_id', $trans_data['actionId']);
            }
            
            if (isset($trans_data['ridByPmo'])) {
                update_post_meta($wp_order_id, 'pmo_transaction_id', $trans_data['ridByPmo']);
            }
            
            if (isset($trans_data['regTime'])) {
                update_post_meta($wp_order_id, 'payment_date', $trans_data['regTime']);
            }
        }
        
        // Extract card details
        if (isset($order_data['order']['srcToken']['displayName'])) {
            update_post_meta($wp_order_id, 'card_number', $order_data['order']['srcToken']['displayName']);
        }
    }
    
    /**
     * Log API request
     */
    private function log_request($endpoint, $args) {
        if (!$this->config['debug']['log_api_requests']) {
            return;
        }
        
        $log_data = array(
            'endpoint' => $endpoint,
            'method' => $args['method'],
            'body' => isset($args['body']) ? $args['body'] : null,
        );
        
        $this->log("API Request: " . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Log API response
     */
    private function log_response($endpoint, $response_code, $body) {
        if (!$this->config['debug']['log_api_requests']) {
            return;
        }
        
        $log_data = array(
            'endpoint' => $endpoint,
            'response_code' => $response_code,
            'body' => $body,
        );
        
        $this->log("API Response: " . json_encode($log_data, JSON_UNESCAPED_UNICODE));
    }
    
    /**
     * Log error
     */
    private function log_error($message) {
        $this->log($message, 'error');
    }
    
    /**
     * Log message with better error handling
     */
    private function log($message, $level = 'info') {
        if (!$this->config['debug']['log_api_requests']) {
            return;
        }
        
        $log_file = $this->config['debug']['log_file'];
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [API] [{$level}] {$message}" . PHP_EOL;
        
        // Ensure log directory exists
        $log_dir = dirname($log_file);
        if (!file_exists($log_dir)) {
            wp_mkdir_p($log_dir);
        }
        
        // Check log file size
        if (file_exists($log_file) && filesize($log_file) > $this->config['debug']['max_log_size']) {
            rename($log_file, $log_file . '.old');
        }
        
        // Write log with error handling
        $result = file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Fallback to error_log if file writing fails
        if ($result === false) {
            error_log("TIF Donation Log: {$message}");
        }
    }
    
    /**
     * Get API environment info
     */
    public function get_environment_info() {
        return array(
            'mode' => $this->config['test_mode'] ? 'test' : 'production',
            'api_url' => $this->api_config['api_url'],
            'hpp_url' => $this->api_config['hpp_url'],
        );
    }
}