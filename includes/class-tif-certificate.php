<?php
/**
 * TIF Certificate Generator Class - Optimized for Simplified Usage
 * 
 * @since 2.3.0
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
     * Initialize certificate templates configuration - Simplified
     */
    private function init_certificate_templates() {
        $this->certificate_templates = array(
            'tif' => array(
                'name' => 'Təhsilin İnkişafı Fondu',
                'template_file' => 'certificate-tif-template.svg',
                'enabled' => true,
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'S/N: TIF-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2',
                        'text_anchor' => 'middle' // SVG text mərkəzləmə
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
            // Digər template-ləri aktivləşdiririk
            'young_girls' => array(
                'name' => 'Gənc qızların təhsilinə dəstək',
                'template_file' => 'young-girls-certificate.svg',
                'enabled' => true,
                'fallback' => 'tif', // TIF template-ini istifadə et
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'S/N: TIF-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2',
                        'text_anchor' => 'middle' // SVG text mərkəzləmə
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
            'sustainable_development' => array(
                'name' => 'Qarabağ Təqaüd Proqramı',
                'template_file' => 'sustainable-development-certificate.svg',
                'enabled' => true,
                'fallback' => 'tif', // TIF template-ini istifadə et
                'placeholders' => array(
                    '{{CERTIFICATE_ID}}' => array(
                        'x' => '367.95',
                        'y' => '149.44',
                        'prefix' => 'S/N: TIF-'
                    ),
                    '{{NAME}}' => array(
                        'x' => '370.56',
                        'y' => '415.2',
                        'text_anchor' => 'middle' // SVG text mərkəzləmə
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
     * Generate certificate for specific order - Simplified
     * 
     * @param int $order_id
     * @param string $certificate_type
     * @return string|false Generated SVG content or false on error
     */
    public function generate_certificate($order_id, $certificate_type = 'tif') {
        error_log("TIF Certificate Debug - Order ID: $order_id, Type: $certificate_type");
        
        try {
            // Validate and handle certificate type
            $certificate_type = $this->validate_and_fix_certificate_type($certificate_type);
            
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
     * Validate certificate type and use fallback if needed
     * 
     * @param string $certificate_type
     * @return string
     */
    private function validate_and_fix_certificate_type($certificate_type) {
        // Əgər type mövcud deyilsə və ya deaktivdirsə
        if (!isset($this->certificate_templates[$certificate_type])) {
            error_log("TIF Certificate: Invalid certificate type: {$certificate_type}, using fallback");
            return 'tif'; // Default fallback
        }
        
        $template_config = $this->certificate_templates[$certificate_type];
        
        // Əgər template deaktivdirsə, fallback istifadə et
        if (!($template_config['enabled'] ?? true)) {
            $fallback = $template_config['fallback'] ?? 'tif';
            error_log("TIF Certificate: Type {$certificate_type} disabled, using fallback: {$fallback}");
            return $fallback;
        }
        
        return $certificate_type;
    }
    
    /**
     * Get order data for certificate - Updated for legal entities
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
        $company = get_post_meta($order_id, 'company', true);
        $company_name = get_post_meta($order_id, 'company_name', true);
        $amount = get_post_meta($order_id, 'amount', true);
        $payment_date = get_post_meta($order_id, 'payment_date', true);
        $trans_id = get_post_meta($order_id, 'transactionId_local', true);
        
        // Determine display name based on entity type
        $display_name = $name; // Default to individual name
        
        if ($company === 'Hüquqi şəxs' && !empty($company_name)) {
            $display_name = $company_name; // Use company name for legal entities
            error_log("TIF Certificate: Using company name for certificate: " . $company_name);
        }
        
        // Validate required fields
        if (empty($display_name) || empty($amount)) {
            error_log("TIF Certificate: Missing required fields for order {$order_id}");
            return false;
        }
        
        // Format data with fallbacks
        return array(
            'order_id' => $order_id,
            'name' => sanitize_text_field($display_name), // This will now be company name for legal entities
            'amount' => number_format(floatval($amount), 0, ',', '.'),
            'date' => $payment_date ? date('d.m.Y', strtotime($payment_date)) : date('d.m.Y'),
            'transaction_id' => $trans_id ? $trans_id : $order_id,
            'certificate_number' => $this->generate_certificate_number($order_id),
            // Store additional metadata for reference
            'entity_type' => $company,
            'original_name' => $name,
            'company_name' => $company_name
        );
    }
    
    /**
     * Load SVG template from file - Improved error handling
     * 
     * @param string $certificate_type
     * @return string|false
     */
    private function load_svg_template($certificate_type) {
        $template_config = $this->certificate_templates[$certificate_type];
        $template_file = TIF_DONATION_PLUGIN_DIR . 'templates/certificate/' . $template_config['template_file'];
        
        if (!file_exists($template_file)) {
            error_log("TIF Certificate: Template file not found: {$template_file}");
            return false;
        }
        
        $content = file_get_contents($template_file);
        if ($content === false) {
            error_log("TIF Certificate: Could not read template file: {$template_file}");
            return false;
        }
        
        return $content;
    }
    
    /**
     * Replace placeholders in SVG content - With name centering
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
            '{{CERTIFICATE_ID}}' => $this->format_certificate_id($order_data['certificate_number'], $placeholders['{{CERTIFICATE_ID}}'] ?? array()),
            '{{NAME}}' => $order_data['name'],
            '{{AMOUNT}}' => $this->format_amount($order_data['amount'], $placeholders['{{AMOUNT}}'] ?? array()),
            '{{DATE}}' => $order_data['date']
        );
        
        // Replace placeholders with security
        foreach ($replacements as $placeholder => $value) {
            $escaped_value = esc_html($value);
            
            // NAME üçün xüsusi işləmə - mərkəzləmə
            if ($placeholder === '{{NAME}}') {
                $svg_content = $this->replace_name_with_centering($svg_content, $escaped_value, $placeholders['{{NAME}}'] ?? array());
            } else {
                $svg_content = str_replace($placeholder, $escaped_value, $svg_content);
            }
        }
        
        return $svg_content;
    }
    
    /**
     * Replace name placeholder with text centering
     * 
     * @param string $svg_content
     * @param string $name
     * @param array $config
     * @return string
     */
    private function replace_name_with_centering($svg_content, $name, $config) {
        // SVG sertifikatın eni təxminən 842px, mərkəzi ~421px
        $center_x = 421;
        
        // Mövcud {{NAME}} placeholder-ini tap
        $pattern = '/(<text[^>]*?)(\s+x="[^"]*")?([^>]*>)\{\{NAME\}\}(<\/text>)/i';
        
        if (preg_match($pattern, $svg_content, $matches)) {
            $tag_start = $matches[1];
            $tag_middle = $matches[3];
            $closing_tag = $matches[4];
            
            // Yeni x koordinatı və text-anchor əlavə et
            $new_opening_tag = $tag_start . ' x="' . $center_x . '"';
            
            // text-anchor="middle" əlavə et əgər yoxdursa
            if (strpos($tag_middle, 'text-anchor') === false) {
                $new_opening_tag .= ' text-anchor="middle"';
            }
            
            $new_opening_tag .= $tag_middle;
            
            // Replace et
            $replacement = $new_opening_tag . $name . $closing_tag;
            $svg_content = preg_replace($pattern, $replacement, $svg_content);
            
            error_log("TIF Certificate: Name centered with x={$center_x}");
        } else {
            // Fallback: sadə replace
            $svg_content = str_replace('{{NAME}}', $name, $svg_content);
            error_log("TIF Certificate: Name centering failed, using simple replace");
        }
        
        return $svg_content;
    }
    
    /**
     * Format certificate ID with prefix
     * 
     * @param string $number
     * @param array $config
     * @return string
     */
    private function format_certificate_id($number, $config) {
        $prefix = $config['prefix'] ?? '';
        return $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Format amount with suffix
     * 
     * @param string $amount
     * @param array $config
     * @return string
     */
    private function format_amount($amount, $config) {
        $suffix = $config['suffix'] ?? '';
        return $amount . $suffix;
    }
    
    /**
     * Generate unique certificate number
     * 
     * @param int $order_id
     * @return string
     */
    private function generate_certificate_number($order_id) {
        return str_pad($order_id, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Check if certificate is enabled for order - Simplified
     * 
     * @param int $order_id
     * @return bool
     */
    public function is_certificate_enabled($order_id) {
        // Check global setting
        $enabled = $this->config['certificate']['enabled'] ?? true;
        
        if (!$enabled) {
            return false;
        }
        
        // Check order status - more flexible
        $order_status = get_post_meta($order_id, 'payment_status', true);
        $valid_statuses = array('completed', 'success', 'FullyPaid', 'Completed');
        
        return in_array($order_status, $valid_statuses);
    }
    
    /**
     * Get certificate download URL - Simplified
     * 
     * @param int $order_id
     * @param string $type
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
        if ($this->config['debug']['log_certificate'] ?? false) {
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
     * Get available certificate types - Only enabled ones
     * 
     * @return array
     */
    public function get_available_types() {
        $types = array();
        
        foreach ($this->certificate_templates as $key => $template) {
            if ($template['enabled'] ?? true) {
                $types[$key] = $template['name'];
            }
        }
        
        return $types;
    }
    
    /**
     * Get certificate type based on iane_tesnifati - Simplified mapping
     * 
     * @param string $iane_tesnifati
     * @return string
     */
    public function get_certificate_type_by_iane($iane_tesnifati) {
        $mapping = array(
            'qtdl' => 'young_girls',
            'qtp' => 'sustainable_development',
            'tifiane' => 'tif'
        );
        
        $type = $mapping[$iane_tesnifati] ?? 'tif';
        
        // Validate and use fallback if needed
        return $this->validate_and_fix_certificate_type($type);
    }
    
    /**
     * Quick certificate generation for thank you page
     * 
     * @param int $order_id
     * @return string|false
     */
    public function generate_certificate_for_thank_you($order_id) {
        // Get iane_tesnifati to determine certificate type
        $iane_tesnifati = get_post_meta($order_id, 'iane_tesnifati', true);
        $certificate_type = $this->get_certificate_type_by_iane($iane_tesnifati);
        
        error_log("TIF Certificate: Thank you page generation - Order: {$order_id}, Iane: {$iane_tesnifati}, Type: {$certificate_type}");
        
        return $this->generate_certificate($order_id, $certificate_type);
    }
}