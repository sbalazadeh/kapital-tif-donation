<?php
/**
 * TIF Certificate Generator Class
 * 
 * @since 2.2.0
 * @package Kapital_TIF_Donation
 */

if (!defined('ABSPATH')) {
    exit;
}

class TIF_Certificate {
    
    private $config;
    private $certificate_templates;
    
    public function __construct($config) {
        $this->config = $config;
        $this->init_certificate_templates();
    }
    
    /**
     * Initialize certificate templates configuration
     */
    private function init_certificate_templates() {
        $this->certificate_templates = array(
            'tif' => array(
                'name' => 'Təhsilin İnkişafı Fondu',
                'template_file' => 'certificate-tif-template.svg',
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'TIF-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2'
                    ),
                    '{{AMOUNT}}' => array(
                        'x' => '184.22',
                        'y' => '499.71',
                        'suffix' => ' AZN'
                    ),
                    '{{DATE}}' => array(
                        'x' => '299.6',
                        'y' => '499.71'
                    )
                )
            ),
            'youth' => array(
                'name' => 'Gənc qızların təhsilinə dəstək',
                'template_file' => 'certificate-youth-template.svg',
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'GQT-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2'
                    ),
                    '{{AMOUNT}}' => array(
                        'x' => '184.22',
                        'y' => '499.71',
                        'suffix' => ' AZN'
                    ),
                    '{{DATE}}' => array(
                        'x' => '299.6',
                        'y' => '499.71'
                    )
                )
            ),
            'sustainable' => array(
                'name' => 'Təhsilin dayanıqlı inkişafına dəstək',
                'template_file' => 'certificate-sustainable-template.svg',
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'TDI-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2'
                    ),
                    '{{AMOUNT}}' => array(
                        'x' => '184.22',
                        'y' => '499.71',
                        'suffix' => ' AZN'
                    ),
                    '{{DATE}}' => array(
                        'x' => '299.6',
                        'y' => '499.71'
                    )
                )
            )
        );
    }
    
    /**
     * Generate certificate for specific order
     * 
     * @param int $order_id
     * @param string $certificate_type
     * @return string|false Generated SVG content or false on error
     */
    public function generate_certificate($order_id, $certificate_type = 'tif') {
    error_log("TIF Certificate Debug - Order ID: $order_id, Type: $certificate_type");
        try {
            // Validate certificate type
            if (!isset($this->certificate_templates[$certificate_type])) {
                error_log("TIF Certificate: Invalid certificate type: {$certificate_type}");
                return false;
            }
            
            // Get order data
            $order_data = $this->get_order_data($order_id);
            if (!$order_data) {
                error_log("TIF Certificate: Order data not found for ID: {$order_id}");
                return false;
            }
            
            // Load SVG template
            $svg_content = $this->load_svg_template($certificate_type);
            if (!$svg_content) {
                error_log("TIF Certificate: Template not found for type: {$certificate_type}");
                return false;
            }
            
            // Replace placeholders
            $final_svg = $this->replace_placeholders($svg_content, $order_data, $certificate_type);
            
            // Log successful generation
            $this->log_certificate_generation($order_id, $certificate_type);
            
            return $final_svg;
            
        } catch (Exception $e) {
            error_log("TIF Certificate Generation Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get order data for certificate
     * 
     * @param int $order_id
     * @return array|false
     */
    private function get_order_data($order_id) {
        $order = get_post($order_id);
        if (!$order || $order->post_type !== $this->config['general']['post_type']) {
            return false;
        }
        
        // Get meta data
        $name = get_post_meta($order_id, 'name', true);
        $amount = get_post_meta($order_id, 'amount', true);
        $payment_date = get_post_meta($order_id, 'payment_date', true);
        $trans_id = get_post_meta($order_id, 'transactionId_local', true);
        
        // Format data
        return array(
            'order_id' => $order_id,
            'name' => $name ? sanitize_text_field($name) : 'İanəçi',
            'amount' => $amount ? number_format($amount, 0, ',', '.') : '0',
            'date' => $payment_date ? date('d.m.Y', strtotime($payment_date)) : date('d.m.Y'),
            'transaction_id' => $trans_id ? $trans_id : $order_id,
            'certificate_number' => $this->generate_certificate_number($order_id)
        );
    }
    
    /**
     * Load SVG template from file
     * 
     * @param string $certificate_type
     * @return string|false
     */
    private function load_svg_template($certificate_type) {
        $template_config = $this->certificate_templates[$certificate_type];
        $template_file = TIF_DONATION_PLUGIN_DIR . 'templates/certificate/' . $template_config['template_file'];
        
        if (!file_exists($template_file)) {
            return false;
        }
        
        return file_get_contents($template_file);
    }
    
    /**
     * Replace placeholders in SVG content
     * 
     * @param string $svg_content
     * @param array $order_data
     * @param string $certificate_type
     * @return string
     */
    private function replace_placeholders($svg_content, $order_data, $certificate_type) {
        $template_config = $this->certificate_templates[$certificate_type];
        $placeholders = $template_config['placeholders'];
        
        // Prepare replacement values
        $replacements = array(
            '{{CERTIFICATE_ID}}' => $placeholders['{{CERTIFICATE_ID}}']['prefix'] . str_pad($order_data['certificate_number'], 5, '0', STR_PAD_LEFT),
            '{{NAME}}' => $order_data['name'],
            '{{AMOUNT}}' => $order_data['amount'] . $placeholders['{{AMOUNT}}']['suffix'],
            '{{DATE}}' => $order_data['date']
        );
        
        // Replace placeholders
        foreach ($replacements as $placeholder => $value) {
            $svg_content = str_replace($placeholder, esc_html($value), $svg_content);
        }
        
        return $svg_content;
    }
    
    /**
     * Generate unique certificate number
     * 
     * @param int $order_id
     * @return string
     */
    private function generate_certificate_number($order_id) {
        // Use order ID as base, you can customize this logic
        return str_pad($order_id, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if certificate is enabled for order
     * 
     * @param int $order_id
     * @return bool
     */
    public function is_certificate_enabled($order_id) {
        // Check global setting
        $enabled = isset($this->config['certificate']['enabled']) ? $this->config['certificate']['enabled'] : true;
        
        if (!$enabled) {
            return false;
        }
        
        // Check order status
        $order_status = get_post_meta($order_id, 'payment_status', true);
        
        return $order_status === 'success';
    }
    
    /**
     * Get certificate download URL
     * 
     * @param int $order_id
     * @param string $certificate_type
     * @return string
     */
        public function get_download_url($order_id, $type = 'tif') {
            return add_query_arg(array(
                'action' => 'tif_download_certificate',
                'order_id' => $order_id,
                'type' => $type,
                'nonce' => wp_create_nonce('tif_download_' . $order_id)
            ), admin_url('admin-ajax.php'));
        }
    
    /**
     * Log certificate generation
     * 
     * @param int $order_id
     * @param string $certificate_type
     */
    private function log_certificate_generation($order_id, $certificate_type) {
        if (isset($this->config['debug']['log_certificate']) && $this->config['debug']['log_certificate']) {
            $log_message = sprintf(
                "Certificate generated - Order: %d, Type: %s, Time: %s",
                $order_id,
                $certificate_type,
                current_time('Y-m-d H:i:s')
            );
            
            error_log("TIF Certificate: " . $log_message);
        }
    }
    
    /**
     * Get available certificate types
     * 
     * @return array
     */
    public function get_available_types() {
        $types = array();
        
        foreach ($this->certificate_templates as $key => $template) {
            $types[$key] = $template['name'];
        }
        
        return $types;
    }
    
    /**
     * Validate certificate data
     * 
     * @param array $order_data
     * @return bool
     */
    private function validate_certificate_data($order_data) {
        $required_fields = array('name', 'amount', 'date');
        
        foreach ($required_fields as $field) {
            if (empty($order_data[$field])) {
                return false;
            }
        }
        
        return true;
    }
}