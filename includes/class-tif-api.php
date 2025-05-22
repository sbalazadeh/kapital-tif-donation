<?php
/**
 * Kapital Bank API Integration Class
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
     * Make API request
     */
    public function make_request($endpoint, $body = null, $method = 'POST') {
        $url = $this->api_config['api_url'] . $endpoint;
        
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
        }
        
        // Log request
        $this->log_request($endpoint, $args);
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            $this->log_error("API Request Failed: {$error_message}");
            
            return array(
                'errorCode' => 'ConnectionError',
                'errorDescription' => $error_message
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // Log response
        $this->log_response($endpoint, $response_code, $body);
        
        $json_response = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_error("JSON Decode Error: " . json_last_error_msg());
            return array(
                'errorCode' => 'JSONError',
                'errorDescription' => 'Invalid JSON response from API'
            );
        }
        
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
     * Get detailed order status
     */
    public function get_detailed_order_status($bank_order_id) {
        $endpoint = '/order/' . $bank_order_id . '/?tranDetailLevel=2&tokenDetailLevel=2&orderDetailLevel=2';
        return $this->make_request($endpoint, null, 'GET');
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
     * Validate payment callback
     */
    public function validate_callback($wp_order_id, $callback_status = null) {
        $bank_order_id = get_post_meta($wp_order_id, 'bank_order_id', true);
        
        if (empty($bank_order_id)) {
            return array(
                'success' => false,
                'error' => 'Bank order ID not found'
            );
        }
        
        // Get order status from API
        $order_data = $this->get_detailed_order_status($bank_order_id);
        
        if (empty($order_data) || !isset($order_data['order']['status'])) {
            return array(
                'success' => false,
                'error' => 'Unable to get order status from API'
            );
        }
        
        $api_status = $order_data['order']['status'];
        
        // Use API status as it's more reliable
        $final_status = $api_status;
        
        // Save order data
        update_post_meta($wp_order_id, 'order_data', $order_data);
        
        // Extract transaction details
        $this->extract_transaction_details($wp_order_id, $order_data);
        
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
     * Log message
     */
    private function log($message, $level = 'info') {
        if (!$this->config['debug']['log_api_requests']) {
            return;
        }
        
        $log_file = $this->config['debug']['log_file'];
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [API] [{$level}] {$message}" . PHP_EOL;
        
        // Check log file size
        if (file_exists($log_file) && filesize($log_file) > $this->config['debug']['max_log_size']) {
            rename($log_file, $log_file . '.old');
        }
        
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
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