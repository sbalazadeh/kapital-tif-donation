<?php
/**
 * Certificate Configuration Update & AJAX Handlers
 */

 class TIF_Certificate_Ajax {
    
    private $certificate_generator;
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
        $this->certificate_generator = new TIF_Certificate($config);
        
        // AJAX hooks
        add_action('wp_ajax_tif_download_certificate', array($this, 'handle_download'));
        add_action('wp_ajax_nopriv_tif_download_certificate', array($this, 'handle_download'));
        
        add_action('wp_ajax_tif_preview_certificate', array($this, 'handle_preview'));
        add_action('wp_ajax_tif_generate_certificate', array($this, 'handle_generate'));
    }
    
    /**
     * Handle certificate download request
     */
    public function handle_download() {
        try {
            // Verify nonce
            $order_id = intval($_GET['order_id'] ?? 0);
            $nonce = sanitize_text_field($_GET['nonce'] ?? '');
            $type = sanitize_text_field($_GET['type'] ?? 'tif');
            
            if (!wp_verify_nonce($nonce, 'tif_download_' . $order_id)) {
                wp_die(__('Təhlükəsizlik xətası.', 'kapital-tif-donation'));
            }
            
            if ($order_id <= 0) {
                wp_die(__('Sifariş tapılmadı.', 'kapital-tif-donation'));
            }
            
            // Check if certificate is enabled for this order
            //if (!$this->certificate_generator->is_certificate_enabled($order_id)) {
            //    wp_die(__('Bu sifariş üçün sertifikat mövcud deyil.', 'kapital-tif-donation'));
            //}
            
            // Generate certificate
            $svg_content = $this->certificate_generator->generate_certificate($order_id, $type);
            
            if (!$svg_content) {
                wp_die(__('Sertifikat yaradıla bilmədi.', 'kapital-tif-donation'));
            }
            
            // Get order data for filename
            $order_data = $this->get_order_data($order_id);
            $filename = $this->generate_filename($order_data, $type);
            
            // Set headers for download
            header('Content-Type: image/svg+xml');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . strlen($svg_content));
            header('Cache-Control: no-cache, must-revalidate');
            
            // Output SVG
            echo $svg_content;
            
            // Log download
            $this->log_download($order_id, $type);
            
            exit;
            
        } catch (Exception $e) {
            error_log("TIF Certificate Download Error: " . $e->getMessage());
            wp_die(__('Sertifikat yüklənə bilmədi.', 'kapital-tif-donation'));
        }
    }
    
    /**
     * Handle certificate preview request
     */
    public function handle_preview() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'tif_preview_certificate')) {
            wp_send_json_error(array('message' => __('Təhlükəsizlik xətası.', 'kapital-tif-donation')));
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'tif');
        
        if ($order_id <= 0) {
            wp_send_json_error(array('message' => __('Order ID tapılmadı.', 'kapital-tif-donation')));
        }
        
        // Generate certificate
        $svg_content = $this->certificate_generator->generate_certificate($order_id, $type);
        
        if ($svg_content) {
            wp_send_json_success(array(
                'svg' => $svg_content,
                'message' => __('Sertifikat uğurla yaradıldı.', 'kapital-tif-donation')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sertifikat yaradıla bilmədi.', 'kapital-tif-donation')
            ));
        }
    }
    
    /**
     * Handle manual certificate generation (for admin)
     */
    public function handle_generate() {
        // Verify admin access
        if (!current_user_can('edit_posts')) {
            wp_die(__('Icazəniz yoxdur.', 'kapital-tif-donation'));
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'tif');
        
        // Generate and store certificate reference
        $svg_content = $this->certificate_generator->generate_certificate($order_id, $type);
        
        if ($svg_content) {
            // Mark certificate as generated
            update_post_meta($order_id, 'certificate_generated', true);
            update_post_meta($order_id, 'certificate_type', $type);
            update_post_meta($order_id, 'certificate_date', current_time('Y-m-d H:i:s'));
            
            wp_send_json_success(array(
                'message' => __('Sertifikat uğurla yaradıldı.', 'kapital-tif-donation'),
                'download_url' => $this->certificate_generator->get_download_url($order_id, $type)
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Sertifikat yaradıla bilmədi.', 'kapital-tif-donation')
            ));
        }
    }
    
    /**
     * Get order data
     */
    private function get_order_data($order_id) {
        return array(
            'name' => get_post_meta($order_id, 'name', true),
            'amount' => get_post_meta($order_id, 'amount', true),
            'date' => get_post_meta($order_id, 'payment_date', true)
        );
    }
    
    /**
     * Generate filename for certificate
     */
    private function generate_filename($order_data, $type) {
        $type_names = array(
            'tif' => 'TIF',
            'youth' => 'GencQizlar', 
            'sustainable' => 'DayaniqliInkisaf'
        );
        
        $type_name = $type_names[$type] ?? 'TIF';
        $clean_name = $this->clean_filename($order_data['name'] ?? 'Ianeci');
        
        return sprintf(
            '%s_Sertifikat_%s_%s.svg',
            $type_name,
            $clean_name,
            date('Y-m-d')
        );
    }
    
    /**
     * Clean filename for download
     */
    private function clean_filename($name) {
        // Remove special characters, keep only letters and numbers
        $clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $name);
        $clean = preg_replace('/\s+/', '_', trim($clean));
        return substr($clean, 0, 20); // Limit length
    }
    
    /**
     * Log certificate download
     */
    private function log_download($order_id, $type) {
        if ($this->config['debug']['log_certificate'] ?? false) {
            $log_message = sprintf(
                "Certificate downloaded - Order: %d, Type: %s, IP: %s, Time: %s",
                $order_id,
                $type,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                current_time('Y-m-d H:i:s')
            );
            
            error_log("TIF Certificate Download: " . $log_message);
        }
    }
}