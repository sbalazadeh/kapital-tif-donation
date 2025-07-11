<?php
/**
 * Admin Operations Class - DUPLICATE METHODS FIXED
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Admin {
    
    private $config;
    private $database;
    private $api;
    private $cache_group = 'tif_donation_admin';
    
    public function __construct($config, $database, $api) {
        $this->config = $config;
        $this->database = $database;
        $this->api = $api;
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->config['general']['post_type'], array($this, 'save_meta_box_data'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_menu', array($this, 'add_admin_menus'));
        add_action('admin_notices', array($this, 'show_admin_notices'));
        add_action('admin_notices', array($this, 'show_bulk_action_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_certificate_scripts'));
        add_action('wp_ajax_tif_preview_certificate', array($this, 'ajax_preview_certificate'));
        
        // Customize admin columns
        add_filter('manage_' . $this->config['general']['post_type'] . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->config['general']['post_type'] . '_posts_custom_column', array($this, 'fill_custom_columns'), 10, 2);
        add_filter('manage_edit-' . $this->config['general']['post_type'] . '_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'orderby_columns'));
        
        // Bulk actions
        add_filter('bulk_actions-edit-' . $this->config['general']['post_type'], array($this, 'add_bulk_actions'));
        add_filter('handle_bulk_actions-edit-' . $this->config['general']['post_type'], array($this, 'handle_bulk_actions'), 10, 3);
        
        // Admin bar enhancements
        add_action('admin_bar_menu', array($this, 'add_admin_bar_items'), 100);
    }
    
    /**
     * Add items to admin bar
     */
    public function add_admin_bar_items($wp_admin_bar) {
        if (!current_user_can($this->config['general']['capability'])) {
            return;
        }
        
        // Get pending donations count
        $pending_count = $this->get_pending_donations_count();
        
        if ($pending_count > 0) {
            $wp_admin_bar->add_node(array(
                'id' => 'tif-pending-donations',
                'title' => sprintf(__('İanələr (%d)', 'kapital-tif-donation'), $pending_count),
                'href' => admin_url('edit.php?post_type=' . $this->config['general']['post_type'] . '&odenis_statusu=pending'),
                'meta' => array(
                    'class' => 'tif-admin-bar-pending'
                )
            ));
        }
    }
    
    /**
     * Get pending donations count with caching
     */
    private function get_pending_donations_count() {
        $cache_key = 'pending_donations_count';
        $count = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $count) {
            $args = array(
                'post_type' => $this->config['general']['post_type'],
                'post_status' => 'publish',
                'meta_query' => array(
                    array(
                        'key' => 'payment_status',
                        'value' => 'Pending',
                        'compare' => '='
                    )
                ),
                'fields' => 'ids'
            );
            
            $query = new WP_Query($args);
            $count = $query->found_posts;
            
            wp_cache_set($cache_key, $count, $this->cache_group, 300); // 5 minutes
        }
        
        return $count;
    }
    
    /**
     * Show admin notices
     */
    public function show_admin_notices() {
        $screen = get_current_screen();
        if (!$screen || $screen->post_type !== $this->config['general']['post_type']) {
            return;
        }
        
        // Show test mode notice
        if ($this->config['test_mode']) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . __('TEST MODU AKTİVDİR:', 'kapital-tif-donation') . '</strong> ';
            echo __('Production-a keçmək üçün config.php faylında test_mode parametrini false edin.', 'kapital-tif-donation');
            echo '</p></div>';
        }
        
        // Show API status
        $api_status = $this->check_api_connectivity();
        if (!$api_status['success']) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . __('API Əlaqə Xətası:', 'kapital-tif-donation') . '</strong> ';
            echo esc_html($api_status['message']);
            echo '</p></div>';
        }
    }
    
    /**
     * Show bulk action results
     */
    public function show_bulk_action_notices() {
        // Show sync results
        if (isset($_GET['tif_synced'])) {
            $synced_count = intval($_GET['tif_synced']);
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(_n('%d ianənin statusu yeniləndi.', '%d ianənin statusu yeniləndi.', $synced_count, 'kapital-tif-donation'), $synced_count) . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Check API connectivity
     */
    private function check_api_connectivity() {
        $cache_key = 'api_connectivity_status';
        $status = wp_cache_get($cache_key, $this->cache_group);
        
        if (false === $status) {
            try {
                $env_info = $this->api->get_environment_info();
                $test_response = wp_remote_get($env_info['api_url'], array(
                    'timeout' => 10,
                    'sslverify' => $this->config['security']['ssl_verify']
                ));
                
                if (is_wp_error($test_response)) {
                    $status = array(
                        'success' => false,
                        'message' => $test_response->get_error_message()
                    );
                } else {
                    $response_code = wp_remote_retrieve_response_code($test_response);
                    $status = array(
                        'success' => ($response_code < 500),
                        'message' => $response_code >= 500 ? 'Server xətası' : 'API əlaqəsi aktiv'
                    );
                }
            } catch (Exception $e) {
                $status = array(
                    'success' => false,
                    'message' => $e->getMessage()
                );
            }
            
            wp_cache_set($cache_key, $status, $this->cache_group, 600); // 10 minutes
        }
        
        return $status;
    }
    
    /**
     * Add bulk actions
     */
    public function add_bulk_actions($bulk_actions) {
        $bulk_actions['tif_sync_status'] = __('Statusu yenilə', 'kapital-tif-donation');
        $bulk_actions['tif_export_selected'] = __('Seçilənləri ixrac et', 'kapital-tif-donation');
        return $bulk_actions;
    }
    
    /**
     * Handle bulk actions
     */
    public function handle_bulk_actions($redirect_to, $doaction, $post_ids) {
        if ($doaction === 'tif_sync_status') {
            $synced_count = 0;
            
            foreach ($post_ids as $post_id) {
                $result = $this->database->sync_payment_status($post_id, $this->api);
                if ($result) {
                    $synced_count++;
                }
            }
            
            $redirect_to = add_query_arg('tif_synced', $synced_count, $redirect_to);
        }
        
        if ($doaction === 'tif_export_selected') {
            $this->export_selected_donations($post_ids);
            exit; // File download, no redirect
        }
        
        return $redirect_to;
    }
    
    /**
     * Export selected donations - WITH İanə Təsnifatı
     */
    private function export_selected_donations($post_ids) {
        $donations = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_type === $this->config['general']['post_type']) {
                $donations[] = $post;
            }
        }
        
        if (empty($donations)) {
            wp_die(__('Seçilən ianələr tapılmadı.', 'kapital-tif-donation'));
        }
        
        // Generate CSV
        $filename = 'selected_donations_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Headers - İanə Təsnifatı əlavə edildi
        $headers = array(
            'ID', 'Transaction ID', 'Ad və soyad', 'Telefon', 'Məbləğ',
            'Qurumun növü', 'Qurumun adı', 'VÖEN', 'İanə Təsnifatı', 'Ödəniş tarixi', 'Status', 'Bank Order ID'
        );
        fputcsv($output, $headers);
        
        // Data - İanə Təsnifatı əlavə edildi
        foreach ($donations as $donation) {
            $status_terms = wp_get_object_terms($donation->ID, $this->config['general']['taxonomy']);
            $status = !empty($status_terms) ? $status_terms[0]->name : get_post_meta($donation->ID, 'payment_status', true);
            
            // Map İanə Təsnifatı for export
            $iane_tesnifati = get_post_meta($donation->ID, 'iane_tesnifati', true);
            $iane_map = array(
                'tifiane' => 'Təhsilin İnkişafı Fonduna',
                'qtdl' => 'Qızların təhsilinə dəstək layihəsinə',
                'qtp' => 'Qarabağ Təqaüd Proqramına'
            );
            $iane_display = isset($iane_map[$iane_tesnifati]) ? $iane_map[$iane_tesnifati] : $iane_tesnifati;
            
            $row = array(
                $donation->ID,
                get_post_meta($donation->ID, 'transactionId_local', true),
                get_post_meta($donation->ID, 'name', true),
                get_post_meta($donation->ID, 'phone', true),
                get_post_meta($donation->ID, 'amount', true),
                get_post_meta($donation->ID, 'company', true),
                get_post_meta($donation->ID, 'company_name', true),
                get_post_meta($donation->ID, 'voen', true),
                $iane_display, // YENİ FIELD
                get_post_meta($donation->ID, 'payment_date', true),
                $status,
                get_post_meta($donation->ID, 'bank_order_id', true)
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
    }
    
    
    /**
     * Add meta boxes - Unified version
     */
    public function add_meta_boxes() {
        // Unified main meta box - bütün məlumatlar burada
        add_meta_box(
            'tif_unified_details',
            __('İanə və Sertifikat Məlumatları', 'kapital-tif-donation'),
            array($this, 'unified_details_callback'),
            $this->config['general']['post_type'],
            'normal',
            'high'
        );
        
        // Kiçik API info - saxlanılır
        add_meta_box(
            'tif_api_info',
            __('API Status', 'kapital-tif-donation'),
            array($this, 'api_info_callback'),
            $this->config['general']['post_type'],
            'side',
            'low'
        );
    }
    
    /**
     * Debug info meta box callback
     */
    public function debug_info_callback($post) {
        $last_status_check = get_post_meta($post->ID, 'last_status_check', true);
        $api_env = $this->api->get_environment_info();
        
        echo '<div style="font-size: 12px; color: #666;">';
        echo '<p><strong>API Modu:</strong> ' . esc_html($api_env['mode']) . '</p>';
        echo '<p><strong>API URL:</strong> ' . esc_html($api_env['api_url']) . '</p>';
        echo '<p><strong>Son status yoxlaması:</strong> ' . ($last_status_check ? esc_html($last_status_check) : 'Yoxdur') . '</p>';
        echo '<p><strong>Post ID:</strong> ' . esc_html($post->ID) . '</p>';
        echo '<p><strong>Post Status:</strong> ' . esc_html($post->post_status) . '</p>';
        echo '</div>';
    }
    
    /**
     * Certificate details meta box callback - YENİ
     */
    public function certificate_details_callback($post) {
        wp_nonce_field($this->config['security']['nonce_actions']['certificate_details'], 'tif_certificate_details_nonce');
        
        // Get certificate data
        $certificate_generated = get_post_meta($post->ID, 'certificate_generated', true);
        $certificate_type = get_post_meta($post->ID, 'certificate_type', true);
        $certificate_date = get_post_meta($post->ID, 'certificate_date', true);
        $payment_status = get_post_meta($post->ID, 'payment_status', true);
        $iane_tesnifati = get_post_meta($post->ID, 'iane_tesnifati', true);
        $company = get_post_meta($post->ID, 'company', true);
        $company_name = get_post_meta($post->ID, 'company_name', true);
        $name = get_post_meta($post->ID, 'name', true);
        
        // Determine certificate availability
        $is_eligible = in_array($payment_status, array('completed', 'success', 'FullyPaid', 'Completed'));
        
        // Certificate type mapping
        $certificate_mapping = array(
            'tifiane' => 'tif',
            'qtdl' => 'youth', 
            'qtp' => 'sustainable'
        );
        $suggested_type = $certificate_mapping[$iane_tesnifati] ?? 'tif';
        
        // Display name logic
        $display_name = $name;
        if ($company === 'Hüquqi şəxs' && !empty($company_name)) {
            $display_name = $company_name;
        }
        
        $this->load_template('admin/certificate-details', array(
            'post_id' => $post->ID,
            'certificate_generated' => $certificate_generated,
            'certificate_type' => $certificate_type,
            'certificate_date' => $certificate_date,
            'payment_status' => $payment_status,
            'iane_tesnifati' => $iane_tesnifati,
            'suggested_type' => $suggested_type,
            'is_eligible' => $is_eligible,
            'display_name' => $display_name,
            'company' => $company,
            'company_name' => $company_name,
            'name' => $name
        ));
    }

    /**
     * Unified details meta box callback
     * Bütün məlumatları tək yerdə göstərir
     */
    public function unified_details_callback($post) {
        wp_nonce_field($this->config['security']['nonce_actions']['donation_details'], 'tif_unified_details_nonce');
        
        // Get all data
        $name = get_post_meta($post->ID, 'name', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $amount = get_post_meta($post->ID, 'amount', true);
        $company = get_post_meta($post->ID, 'company', true);
        $company_name = get_post_meta($post->ID, 'company_name', true);
        $voen = get_post_meta($post->ID, 'voen', true);
        $iane_tesnifati = get_post_meta($post->ID, 'iane_tesnifati', true);
        
        // Transaction data
        $bank_order_id = get_post_meta($post->ID, 'bank_order_id', true);
        $transaction_id = get_post_meta($post->ID, 'transactionId_local', true);
        $payment_status = get_post_meta($post->ID, 'payment_status', true);
        $payment_date = get_post_meta($post->ID, 'payment_date', true);
        $payment_method = get_post_meta($post->ID, 'payment_method', true);
        $approval_code = get_post_meta($post->ID, 'approval_code', true);
        $card_number = get_post_meta($post->ID, 'card_number', true);
        
        // Certificate data
        $certificate_generated = get_post_meta($post->ID, 'certificate_generated', true);
        $certificate_type = get_post_meta($post->ID, 'certificate_type', true);
        $certificate_date = get_post_meta($post->ID, 'certificate_date', true);
        
        // Process certificate logic
        $is_eligible = in_array($payment_status, array('completed', 'success', 'FullyPaid', 'Completed'));
        $certificate_mapping = array(
            'tifiane' => 'tif',
            'qtdl' => 'youth', 
            'qtp' => 'sustainable'
        );
        $suggested_type = $certificate_mapping[$iane_tesnifati] ?? 'tif';
        
        // Display name logic
        $display_name = $name;
        if ($company === 'Hüquqi şəxs' && !empty($company_name)) {
            $display_name = $company_name;
        }
        
        // İanə təsnifatı mapping
        $iane_map = array(
            'tifiane' => 'Təhsilin İnkişafı Fonduna',
            'qtdl' => 'Qızların təhsilinə dəstək layihəsinə',
            'qtp' => 'Qarabağ Təqaüd Proqramına'
        );
        $iane_display = isset($iane_map[$iane_tesnifati]) ? $iane_map[$iane_tesnifati] : 'Müəyyən edilməyib';
        
        ?>
        
        <style>
        .tif-unified-meta {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin: 15px 0;
        }
        
        .tif-meta-section {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        
        .tif-meta-section h3 {
            margin: 0 0 15px 0;
            padding: 0 0 10px 0;
            border-bottom: 1px solid #ddd;
            color: #23282d;
            font-size: 14px;
            font-weight: 600;
        }
        
        .tif-meta-field {
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .tif-meta-field label {
            font-weight: 600;
            color: #555;
            min-width: 120px;
            font-size: 13px;
        }
        
        .tif-meta-field .value {
            flex: 1;
            text-align: right;
            font-size: 13px;
        }
        
        .tif-meta-field .value.editable input,
        .tif-meta-field .value.editable select {
            width: 100%;
            max-width: 200px;
            padding: 4px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            font-size: 13px;
        }
        
        .status-completed { color: #00a32a; font-weight: 600; }
        .status-pending { color: #f56e28; font-weight: 600; }
        .status-failed { color: #d63638; font-weight: 600; }
        .status-unknown { color: #666; }
        
        .certificate-section .tif-meta-field .value {
            font-weight: 600;
        }
        
        .certificate-eligible { color: #00a32a; }
        .certificate-not-eligible { color: #d63638; }
        
        .png-generation {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            text-align: center;
        }
        
        .png-btn {
            background: #0073aa;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .png-btn:hover {
            background: #005a87;
        }
        
        .png-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .png-status {
            margin-top: 10px;
            font-size: 12px;
        }
        
        @media (max-width: 1200px) {
            .tif-unified-meta {
                grid-template-columns: 1fr;
            }
        }
        </style>
        
        <div class="tif-unified-meta">
            
            <!-- İanə Məlumatları -->
            <div class="tif-meta-section donation-section">
                <h3>📝 İanə Məlumatları</h3>
                
                <div class="tif-meta-field">
                    <label>Ad və Soyad:</label>
                    <div class="value editable">
                        <input type="text" name="name" value="<?php echo esc_attr($name); ?>" />
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Telefon:</label>
                    <div class="value editable">
                        <input type="text" name="phone" value="<?php echo esc_attr($phone); ?>" />
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Məbləğ:</label>
                    <div class="value editable">
                        <input type="number" name="amount" value="<?php echo esc_attr($amount); ?>" step="0.01" min="0" />
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Təşkilat:</label>
                    <div class="value editable">
                        <select name="company" id="company_select">
                            <option value="Fiziki şəxs" <?php selected($company, 'Fiziki şəxs'); ?>>Fiziki şəxs</option>
                            <option value="Hüquqi şəxs" <?php selected($company, 'Hüquqi şəxs'); ?>>Hüquqi şəxs</option>
                        </select>
                    </div>
                </div>
                
                <div class="tif-meta-field" id="company_name_field" <?php echo ($company != 'Hüquqi şəxs') ? 'style="display:none;"' : ''; ?>>
                    <label>Qurumun adı:</label>
                    <div class="value editable">
                        <input type="text" name="company_name" value="<?php echo esc_attr($company_name); ?>" />
                    </div>
                </div>
                
                <div class="tif-meta-field" id="voen_field" <?php echo ($company != 'Hüquqi şəxs') ? 'style="display:none;"' : ''; ?>>
                    <label>VÖEN:</label>
                    <div class="value editable">
                        <input type="text" name="voen" value="<?php echo esc_attr($voen); ?>" />
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>İanə Təsnifatı:</label>
                    <div class="value editable">
                        <select name="iane_tesnifati">
                            <option value="tifiane" <?php selected($iane_tesnifati, 'tifiane'); ?>>TIF</option>
                            <option value="qtdl" <?php selected($iane_tesnifati, 'qtdl'); ?>>Qızların təhsili</option>
                            <option value="qtp" <?php selected($iane_tesnifati, 'qtp'); ?>>Qarabağ təqaüd</option>
                        </select>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Təsnifat:</label>
                    <div class="value" style="color: #0073aa;">
                        <?php echo esc_html($iane_display); ?>
                    </div>
                </div>
            </div>
            
            <!-- Əməliyyat Məlumatları -->
            <div class="tif-meta-section transaction-section">
                <h3>💳 Əməliyyat Məlumatları</h3>
                
                <div class="tif-meta-field">
                    <label>Transaction ID:</label>
                    <div class="value">
                        <strong><?php echo esc_html($transaction_id ?: 'Yoxdur'); ?></strong>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Bank Order ID:</label>
                    <div class="value">
                        <?php echo esc_html($bank_order_id ?: 'Yoxdur'); ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Status:</label>
                    <div class="value editable">
                        <select name="payment_status">
                            <option value="pending" <?php selected($payment_status, 'pending'); ?>>Pending</option>
                            <option value="completed" <?php selected($payment_status, 'completed'); ?>>Completed</option>
                            <option value="FullyPaid" <?php selected($payment_status, 'FullyPaid'); ?>>FullyPaid</option>
                            <option value="failed" <?php selected($payment_status, 'failed'); ?>>Failed</option>
                            <option value="cancelled" <?php selected($payment_status, 'cancelled'); ?>>Cancelled</option>
                        </select>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Status Display:</label>
                    <div class="value <?php 
                        if (in_array($payment_status, ['completed', 'FullyPaid'])) echo 'status-completed';
                        elseif ($payment_status === 'pending') echo 'status-pending';
                        elseif (in_array($payment_status, ['failed', 'cancelled'])) echo 'status-failed';
                        else echo 'status-unknown';
                    ?>">
                        <?php echo esc_html($payment_status ?: 'Unknown'); ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Ödəniş Tarixi:</label>
                    <div class="value">
                        <?php echo esc_html($payment_date ? date('d.m.Y H:i', strtotime($payment_date)) : 'Yoxdur'); ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Ödəniş Metodu:</label>
                    <div class="value">
                        <?php echo esc_html($payment_method ?: 'Kapital Bank'); ?>
                    </div>
                </div>
                
                <?php if ($approval_code): ?>
                <div class="tif-meta-field">
                    <label>Approval Code:</label>
                    <div class="value">
                        <?php echo esc_html($approval_code); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($card_number): ?>
                <div class="tif-meta-field">
                    <label>Kart:</label>
                    <div class="value">
                        <?php echo esc_html($card_number); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Sertifikat Məlumatları -->
            <div class="tif-meta-section certificate-section">
                <h3>🎓 Sertifikat Məlumatları</h3>
                
                <div class="tif-meta-field">
                    <label>Sertifikat Status:</label>
                    <div class="value <?php echo $certificate_generated ? 'certificate-eligible' : 'certificate-not-eligible'; ?>">
                        <?php echo $certificate_generated ? '✓ Mövcuddur' : '✗ Yaradılmamış'; ?>
                    </div>
                </div>
                
                <?php if ($certificate_date): ?>
                <div class="tif-meta-field">
                    <label>Yaradılma Tarixi:</label>
                    <div class="value">
                        <?php echo esc_html(date('d.m.Y H:i', strtotime($certificate_date))); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="tif-meta-field">
                    <label>Sertifikat Növü:</label>
                    <div class="value">
                        <?php 
                        $type_map = array('tif' => 'TIF', 'youth' => 'Youth', 'sustainable' => 'Sustainable');
                        echo esc_html($type_map[$suggested_type] ?? 'TIF');
                        ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Uyğunluq:</label>
                    <div class="value <?php echo $is_eligible ? 'certificate-eligible' : 'certificate-not-eligible'; ?>">
                        <?php echo $is_eligible ? '✓ Uyğundur' : '✗ Uyğun deyil'; ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Sertifikatda Ad:</label>
                    <div class="value" style="font-weight: 600; color: #0073aa;">
                        <?php echo esc_html($display_name ?: 'Müəyyən edilməyib'); ?>
                    </div>
                </div>
                
                <?php if ($company === 'Hüquqi şəxs'): ?>
                <div class="tif-meta-field">
                    <label>Əlaqədar Şəxs:</label>
                    <div class="value" style="font-size: 12px; color: #666;">
                        <?php echo esc_html($name ?: 'Müəyyən edilməyib'); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- PNG Generation Section -->
                <?php if ($is_eligible): ?>
                <div class="png-generation">
                    <button type="button" id="tif-generate-png" class="png-btn" 
                            data-order-id="<?php echo esc_attr($post->ID); ?>"
                            data-certificate-type="<?php echo esc_attr($suggested_type); ?>">
                        <span>📥</span> PNG Yüklə
                    </button>
                    
                    <div id="tif-png-status" class="png-status" style="display: none;">
                        <p>Sertifikat hazırlanır...</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="png-generation">
                    <p style="color: #d63638; font-size: 12px; margin: 0;">
                        PNG generation üçün payment status "completed" olmalıdır
                    </p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- API və Debug Info -->
            <div class="tif-meta-section api-section">
                <h3>⚙️ Sistem Məlumatları</h3>
                
                <div class="tif-meta-field">
                    <label>Post ID:</label>
                    <div class="value">
                        <?php echo esc_html($post->ID); ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Post Status:</label>
                    <div class="value">
                        <?php echo esc_html($post->post_status); ?>
                    </div>
                </div>
                
                <div class="tif-meta-field">
                    <label>Son Yeniləmə:</label>
                    <div class="value">
                        <?php echo esc_html(get_the_modified_date('d.m.Y H:i', $post->ID)); ?>
                    </div>
                </div>
                
                <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; font-size: 12px; color: #666;">Debug Məlumatları</summary>
                    <div style="font-size: 10px; color: #999; margin-top: 5px; max-height: 150px; overflow-y: auto;">
                        <pre><?php 
                        $debug_data = array(
                            'name' => $name,
                            'company' => $company,
                            'company_name' => $company_name,
                            'iane_tesnifati' => $iane_tesnifati,
                            'payment_status' => $payment_status,
                            'is_eligible' => $is_eligible,
                            'display_name' => $display_name,
                            'suggested_type' => $suggested_type
                        );
                        echo esc_html(print_r($debug_data, true));
                        ?></pre>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Company type toggle
            $('#company_select').on('change', function() {
                if ($(this).val() === 'Hüquqi şəxs') {
                    $('#company_name_field, #voen_field').show();
                } else {
                    $('#company_name_field, #voen_field').hide();
                }
            });
            
            // PNG generation handler - BROWSER BASED (Thank You page metodu)
            $('#tif-generate-png').on('click', function(e) {
                e.preventDefault();
                
                const button = $(this);
                const orderId = button.data('order-id');
                const certificateType = button.data('certificate-type');
                const statusDiv = $('#tif-png-status');
                
                // Show loading state
                button.prop('disabled', true);
                button.html('<span>⏳</span> Hazırlanır...');
                statusDiv.show().find('p').text('SVG sertifikat yüklənir...');
                
                // SVG generate edək (mövcud AJAX handler ilə)
                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'tif_preview_certificate',
                        order_id: orderId,
                        type: certificateType,
                        nonce: '<?php echo wp_create_nonce("tif_preview_certificate"); ?>'
                    },
                    timeout: 30000,
                    success: function(response) {
                        if (response.success && response.data.svg) {
                            statusDiv.find('p').text('PNG formatına çevrilir...');
                            
                            // SVG-ni PNG-yə çevir və download et (browser-based)
                            convertSVGToPNGAndDownload(response.data.svg, orderId, button, statusDiv);
                            
                        } else {
                            throw new Error(response.data?.message || 'SVG əldə edilə bilmədi');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('SVG Generation Error:', error);
                        statusDiv.find('p').html('<span style="color: #d63638;">✗ Xəta: ' + (error || 'Naməlum xəta') + '</span>');
                        
                        button.prop('disabled', false);
                        button.html('<span>📥</span> PNG Yüklə');
                        
                        setTimeout(() => {
                            statusDiv.hide();
                        }, 5000);
                    }
                });
            });
            
            // Browser-based SVG to PNG conversion (Thank You page metodundan)
            function convertSVGToPNGAndDownload(svgString, orderId, button, statusDiv) {
                try {
                    // SVG-ni DOM elementinə çevir
                    const parser = new DOMParser();
                    const svgDoc = parser.parseFromString(svgString, 'image/svg+xml');
                    const svgElement = svgDoc.querySelector('svg');
                    
                    if (!svgElement) {
                        throw new Error('SVG element tapılmadı');
                    }
                    
                    // SVG viewBox və ölçüləri götür
                    const viewBox = svgElement.viewBox.baseVal;
                    const svgWidth = viewBox ? viewBox.width : (svgElement.width?.baseVal?.value || 842);
                    const svgHeight = viewBox ? viewBox.height : (svgElement.height?.baseVal?.value || 600);
                    
                    // Canvas yarat (yüksək keyfiyyət üçün 3x scale)
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');
                    const scale = 3;
                    
                    canvas.width = svgWidth * scale;
                    canvas.height = svgHeight * scale;
                    
                    // Ağ background əlavə et
                    ctx.fillStyle = 'white';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    
                    // SVG-ni Image elementinə yüklə
                    const img = new Image();
                    
                    img.onload = function() {
                        // Canvas-a çək
                        ctx.scale(scale, scale);
                        ctx.drawImage(img, 0, 0, svgWidth, svgHeight);
                        
                        // PNG-yə çevir və download et
                        canvas.toBlob(function(blob) {
                            // Filename yarat
                            const timestamp = new Date().toISOString().split('T')[0];
                            const filename = `TIF_Sertifikat_Admin_${orderId}_${timestamp}.png`;
                            
                            // Download link yarat
                            const url = URL.createObjectURL(blob);
                            const downloadLink = document.createElement('a');
                            downloadLink.href = url;
                            downloadLink.download = filename;
                            downloadLink.style.display = 'none';
                            
                            // DOM-a əlavə et və click et
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            document.body.removeChild(downloadLink);
                            
                            // URL-i təmizlə
                            URL.revokeObjectURL(url);
                            
                            // Success state
                            statusDiv.find('p').html('<span style="color: #00a32a;">✓ Uğurlu! PNG yükləndi: ' + filename + '</span>');
                            button.html('<span>✅</span> Yükləndi');
                            
                            setTimeout(() => {
                                button.prop('disabled', false);
                                button.html('<span>📥</span> PNG Yüklə');
                                statusDiv.hide();
                            }, 4000);
                            
                        }, 'image/png', 1.0);
                    };
                    
                    img.onerror = function() {
                        throw new Error('SVG-ni image-ə yükləmək olmadı');
                    };
                    
                    // SVG data URL yarat və load et
                    const svgBlob = new Blob([svgString], {type: 'image/svg+xml;charset=utf-8'});
                    const svgUrl = URL.createObjectURL(svgBlob);
                    img.src = svgUrl;
                    
                } catch (error) {
                    console.error('PNG Conversion Error:', error);
                    statusDiv.find('p').html('<span style="color: #d63638;">✗ PNG çevirmə xətası: ' + error.message + '</span>');
                    
                    button.prop('disabled', false);
                    button.html('<span>📥</span> PNG Yüklə');
                    
                    setTimeout(() => {
                        statusDiv.hide();
                    }, 5000);
                }
            }
        });
        </script>
        
        <?php
    }

    /**
     * API info callback
     */
    public function api_info_callback($post) {
        $last_status_check = get_post_meta($post->ID, 'last_status_check', true);
        $api_env = $this->api->get_environment_info();
        
        echo '<div style="font-size: 12px; color: #666;">';
        echo '<p><strong>API Modu:</strong> ' . esc_html($api_env['mode']) . '</p>';
        echo '<p><strong>API URL:</strong> ' . esc_html($api_env['api_url']) . '</p>';
        echo '<p><strong>Son status yoxlaması:</strong> ' . ($last_status_check ? esc_html($last_status_check) : 'Yoxdur') . '</p>';
        echo '<p><strong>Post ID:</strong> ' . esc_html($post->ID) . '</p>';
        echo '<p><strong>Post Status:</strong> ' . esc_html($post->post_status) . '</p>';
        echo '</div>';
    }
    
    /**
    * Save unified meta box data - Updated for all fields
    */
    public function save_meta_box_data($post_id) {
        // Check nonces - YENİ NONCE NAME
        if (!isset($_POST['tif_unified_details_nonce']) || 
            !wp_verify_nonce($_POST['tif_unified_details_nonce'], $this->config['security']['nonce_actions']['donation_details'])) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Clear cache when saving
        wp_cache_delete('pending_donations_count', $this->cache_group);
        
        // Save all fields
        $all_fields = array(
            'name', 'phone', 'amount', 'company', 'company_name', 'voen', 
            'iane_tesnifati', 'payment_status'
        );
        
        foreach ($all_fields as $field) {
            if (isset($_POST[$field])) {
                $value = $field === 'amount' ? floatval($_POST[$field]) : sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Handle payment status update
        if (isset($_POST['payment_status'])) {
            $new_status = sanitize_text_field($_POST['payment_status']);
            $old_status = get_post_meta($post_id, 'payment_status', true);
            
            if ($new_status !== $old_status) {
                update_post_meta($post_id, 'payment_status', $new_status);
                
                // Update certificate eligibility based on new status
                $is_eligible = in_array($new_status, array('completed', 'success', 'FullyPaid', 'Completed'));
                if ($is_eligible && !get_post_meta($post_id, 'certificate_generated', true)) {
                    // Auto-generate certificate if eligible
                    update_post_meta($post_id, 'certificate_generated', true);
                    update_post_meta($post_id, 'certificate_date', current_time('mysql'));
                }
            }
        }
    }

    /**
     * Direct AJAX handler for certificate preview (admin only)
     */
    public function ajax_preview_certificate() {
        // Security check
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Icazəniz yoxdur.', 'kapital-tif-donation')));
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tif_preview_certificate')) {
            wp_send_json_error(array('message' => __('Təhlükəsizlik xətası.', 'kapital-tif-donation')));
        }
        
        $order_id = intval($_POST['order_id'] ?? 0);
        $type = sanitize_text_field($_POST['type'] ?? 'tif');
        
        if ($order_id <= 0) {
            wp_send_json_error(array('message' => __('Order ID tapılmadı.', 'kapital-tif-donation')));
        }
        
        try {
            // Config yüklə
            $config_file = TIF_DONATION_CONFIG_DIR . 'config.php';
            if (!file_exists($config_file)) {
                throw new Exception('Config faylı tapılmadı');
            }
            
            $config = require $config_file;
            
            // Certificate generator yarat
            if (!class_exists('TIF_Certificate')) {
                require_once TIF_DONATION_PLUGIN_DIR . 'includes/class-tif-certificate.php';
            }
            
            $certificate_generator = new TIF_Certificate($config);
            
            // Certificate generate et
            $svg_content = $certificate_generator->generate_certificate($order_id, $type);
            
            if ($svg_content) {
                wp_send_json_success(array(
                    'svg' => $svg_content,
                    'message' => __('Sertifikat uğurla yaradıldı.', 'kapital-tif-donation'),
                    'order_id' => $order_id,
                    'type' => $type
                ));
            } else {
                throw new Exception('Sertifikat generate edilə bilmədi');
            }
            
        } catch (Exception $e) {
            error_log("TIF Admin Certificate Preview Error: " . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Sertifikat yaradıla bilmədi: ', 'kapital-tif-donation') . $e->getMessage()
            ));
        }
    }

    
    public function add_dashboard_widget() {
        if (!$this->config['admin']['dashboard_widget']) {
            return;
        }
        
        wp_add_dashboard_widget(
            'tif_recent_donations',
            __('Son ianələr', 'kapital-tif-donation'),
            array($this, 'dashboard_widget_callback')
        );
    }
    
    public function dashboard_widget_callback() {
        $orders = $this->database->get_orders(array('posts_per_page' => 5));
        
        if (!empty($orders)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Transaction ID', 'kapital-tif-donation') . '</th>';
            echo '<th>' . __('Ad və soyad', 'kapital-tif-donation') . '</th>';
            echo '<th>' . __('Məbləğ', 'kapital-tif-donation') . '</th>';
            echo '<th>' . __('Status', 'kapital-tif-donation') . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            
            foreach ($orders as $order) {
                $trans_id = get_post_meta($order->ID, 'transactionId_local', true);
                $name = get_post_meta($order->ID, 'name', true);
                $amount = get_post_meta($order->ID, 'amount', true);
                
                $status_terms = wp_get_object_terms($order->ID, $this->config['general']['taxonomy']);
                $status = !empty($status_terms) ? $status_terms[0]->name : 'Unknown';
                
                $status_class = strtolower(str_replace(' ', '-', $status));
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($order->ID) . '">' . esc_html($trans_id) . '</a></td>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html($amount) . ' AZN</td>';
                echo '<td><span class="tif-status-badge tif-status-' . esc_attr($status_class) . '">' . esc_html($status) . '</span></td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            
            $stats = $this->database->get_statistics();
            echo '<div class="tif-widget-stats">';
            echo '<p><strong>' . __('Ümumi:', 'kapital-tif-donation') . '</strong> ' . number_format($stats['total']) . ' ianə';
            echo ' | <strong>' . __('Məbləğ:', 'kapital-tif-donation') . '</strong> ' . number_format($stats['total_amount'], 2) . ' AZN</p>';
            echo '</div>';
            
            echo '<p><a href="' . admin_url('edit.php?post_type=' . $this->config['general']['post_type']) . '">' . __('Bütün ianələri görüntülə', 'kapital-tif-donation') . '</a></p>';
            
            // Add widget styles
            echo '<style>
                .tif-status-badge { 
                    padding: 2px 6px; 
                    border-radius: 3px; 
                    font-size: 11px; 
                    font-weight: bold; 
                    text-transform: uppercase; 
                }
                .tif-status-completed { background: #46b450; color: white; }
                .tif-status-pending { background: #ffb900; color: white; }
                .tif-status-failed { background: #dc3232; color: white; }
                .tif-status-processing { background: #00a0d2; color: white; }
                .tif-widget-stats { 
                    margin: 10px 0; 
                    padding: 10px; 
                    background: #f1f1f1; 
                    border-radius: 3px; 
                }
            </style>';
        } else {
            echo '<p>' . __('Hələ ki heç bir ianə qeydə alınmayıb.', 'kapital-tif-donation') . '</p>';
        }
    }
    
    public function add_admin_menus() {
        add_submenu_page(
            'edit.php?post_type=' . $this->config['general']['post_type'],
            __('İanələri ixrac et', 'kapital-tif-donation'),
            __('İanələri ixrac et', 'kapital-tif-donation'),
            $this->config['admin']['export_capability'],
            'tif-export-donations',
            array($this, 'export_donations_page')
        );
        
        add_submenu_page(
            'edit.php?post_type=' . $this->config['general']['post_type'],
            __('Statistika', 'kapital-tif-donation'),
            __('Statistika', 'kapital-tif-donation'),
            $this->config['general']['capability'],
            'tif-statistics',
            array($this, 'statistics_page')
        );
        
        // Add settings page
        add_submenu_page(
            'edit.php?post_type=' . $this->config['general']['post_type'],
            __('Parametrlər', 'kapital-tif-donation'),
            __('Parametrlər', 'kapital-tif-donation'),
            $this->config['general']['capability'],
            'tif-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (isset($_POST['tif_save_settings']) && wp_verify_nonce($_POST['tif_settings_nonce'], 'tif_save_settings')) {
            // Handle settings save
            $this->save_settings($_POST);
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Parametrlər yadda saxlanıldı.', 'kapital-tif-donation') . '</p></div>';
        }
        
        $api_env = $this->api->get_environment_info();
        
        echo '<div class="wrap">';
        echo '<h1>' . __('TIF Donation Parametrləri', 'kapital-tif-donation') . '</h1>';
        
        echo '<div class="tif-settings-grid">';
        
        // API Status Card
        echo '<div class="tif-settings-card">';
        echo '<h2>' . __('API Status', 'kapital-tif-donation') . '</h2>';
        echo '<p><strong>' . __('Modu:', 'kapital-tif-donation') . '</strong> ' . ($this->config['test_mode'] ? 'TEST' : 'PRODUCTION') . '</p>';
        echo '<p><strong>' . __('API URL:', 'kapital-tif-donation') . '</strong><br>' . esc_html($api_env['api_url']) . '</p>';
        
        $api_status = $this->check_api_connectivity();
        $status_class = $api_status['success'] ? 'success' : 'error';
        echo '<p><span class="tif-status-indicator tif-status-' . $status_class . '"></span> ' . esc_html($api_status['message']) . '</p>';
        echo '</div>';
        
        // Statistics Card
        $stats = $this->database->get_statistics();
        echo '<div class="tif-settings-card">';
        echo '<h2>' . __('Statistika', 'kapital-tif-donation') . '</h2>';
        echo '<p><strong>' . __('Ümumi ianələr:', 'kapital-tif-donation') . '</strong> ' . number_format($stats['total']) . '</p>';
        echo '<p><strong>' . __('Ümumi məbləğ:', 'kapital-tif-donation') . '</strong> ' . number_format($stats['total_amount'], 2) . ' AZN</p>';
        
        if (isset($stats['by_status']['Completed'])) {
            echo '<p><strong>' . __('Uğurlu ödənişlər:', 'kapital-tif-donation') . '</strong> ' . number_format($stats['by_status']['Completed']) . '</p>';
        }
        echo '</div>';
        
        echo '</div>';
        
        echo '<style>
            .tif-settings-grid { 
                display: grid; 
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
                gap: 20px; 
                margin-top: 20px; 
            }
            .tif-settings-card { 
                background: white; 
                padding: 20px; 
                border: 1px solid #ccd0d4; 
                border-radius: 8px; 
                box-shadow: 0 1px 3px rgba(0,0,0,0.1); 
            }
            .tif-settings-card h2 { 
                margin-top: 0; 
                font-size: 18px; 
            }
            .tif-status-indicator { 
                display: inline-block; 
                width: 10px; 
                height: 10px; 
                border-radius: 50%; 
                margin-right: 5px; 
            }
            .tif-status-success { background: #46b450; }
            .tif-status-error { background: #dc3232; }
        </style>';
        
        echo '</div>';
    }
    
    private function save_settings($data) {
        // Add settings save logic here if needed
        // For now, settings are in config file
    }
    
    public function export_donations_page() {
        $show_results = false;
        $donations = array();
        
        if (isset($_POST['tif_export_donations']) && 
            check_admin_referer($this->config['security']['nonce_actions']['export_donations'])) {
            
            $date_from = isset($_POST['date_from']) ? sanitize_text_field($_POST['date_from']) : '';
            $date_to = isset($_POST['date_to']) ? sanitize_text_field($_POST['date_to']) : '';
            
            $donations = $this->database->get_orders_for_export($date_from, $date_to);
            $show_results = true;
        }
        
        $this->load_template('admin/export-donations', array(
            'show_results' => $show_results,
            'donations' => $donations,
            'nonce_action' => $this->config['security']['nonce_actions']['export_donations']
        ));
    }
    
    public function statistics_page() {
        $stats = $this->database->get_statistics();
        
        $this->load_template('admin/statistics', array(
            'stats' => $stats
        ));
    }
    
    /**
     * Add custom columns including İanə Təsnifatı
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        if (isset($columns['title'])) {
            $new_columns['title'] = 'Transaction ID Local';
        }
        
        $new_columns['name'] = __('Ad və soyad', 'kapital-tif-donation');
        $new_columns['phone'] = __('Telefon', 'kapital-tif-donation');
        $new_columns['amount'] = __('Məbləğ', 'kapital-tif-donation');
        $new_columns['company'] = __('Qurumun növü', 'kapital-tif-donation');
        $new_columns['voen'] = __('VÖEN', 'kapital-tif-donation');
        $new_columns['iane_tesnifati'] = __('İanə Təsnifatı', 'kapital-tif-donation'); // YENİ COLUMN
        $new_columns['bank_order_id'] = __('Bank Order ID', 'kapital-tif-donation');
        $new_columns['payment_date'] = __('Ödəniş tarixi', 'kapital-tif-donation');
        
        foreach ($columns as $key => $value) {
            if (!isset($new_columns[$key]) && $key != 'title' && $key != 'date') {
                $new_columns[$key] = $value;
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Fill custom columns including İanə Təsnifatı
     */
    public function fill_custom_columns($column, $post_id) {
        switch ($column) {
            case 'name':
                echo esc_html(get_post_meta($post_id, 'name', true));
                break;
            case 'phone':
                $phone = get_post_meta($post_id, 'phone', true);
                if ($phone) {
                    echo '<a href="tel:' . esc_attr($phone) . '">' . esc_html($phone) . '</a>';
                }
                break;
            case 'amount':
                $amount = get_post_meta($post_id, 'amount', true);
                if ($amount) {
                    echo '<strong>' . esc_html($amount) . ' AZN</strong>';
                }
                break;
            case 'company':
                $company = get_post_meta($post_id, 'company', true);
                $company_name = get_post_meta($post_id, 'company_name', true);
                
                if ($company === 'Hüquqi şəxs' && !empty($company_name)) {
                    echo esc_html($company_name) . '<br><small>(' . esc_html($company) . ')</small>';
                } else {
                    echo esc_html($company);
                }
                break;
            case 'voen':
                $voen = get_post_meta($post_id, 'voen', true);
                $company = get_post_meta($post_id, 'company', true);
                
                if ($company === 'Hüquqi şəxs' && !empty($voen)) {
                    echo '<code>' . esc_html($voen) . '</code>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
            // YENİ: İanə Təsnifatı Column
            case 'iane_tesnifati':
                $iane_tesnifati = get_post_meta($post_id, 'iane_tesnifati', true);
                
                if (!empty($iane_tesnifati)) {
                    // Map values to display names
                    $iane_map = array(
                        'tifiane' => __('Təhsilin İnkişafı Fonduna', 'kapital-tif-donation'),
                        'qtdl' => __('"Qızların təhsilinə dəstək" layihəsinə', 'kapital-tif-donation'),
                        'qtp' => __('Qarabağ Təqaüd Proqramına', 'kapital-tif-donation')
                    );
                    
                    $display_name = isset($iane_map[$iane_tesnifati]) ? $iane_map[$iane_tesnifati] : $iane_tesnifati;
                    
                    // Color coding for different donation types
                    $color_map = array(
                        'tifiane' => '#2271b1', // Blue
                        'qtdl' => '#d63384',     // Pink
                        'qtp' => '#198754'       // Green
                    );
                    
                    $color = isset($color_map[$iane_tesnifati]) ? $color_map[$iane_tesnifati] : '#666';
                    
                    echo '<span style="background: ' . esc_attr($color) . '; color: white; padding: 2px 6px; border-radius: 3px; font-size: 11px; font-weight: bold;">' . 
                         esc_html($display_name) . '</span>';
                } else {
                    echo '<span style="color: #999;">—</span>';
                }
                break;
            case 'bank_order_id':
                $bank_id = get_post_meta($post_id, 'bank_order_id', true);
                if ($bank_id) {
                    echo '<code>' . esc_html($bank_id) . '</code>';
                }
                break;
            case 'payment_date':
                $date = get_post_meta($post_id, 'payment_date', true);
                if ($date) {
                    $formatted_date = date_create($date);
                    if ($formatted_date) {
                        echo date_format($formatted_date, 'd.m.Y H:i');
                    } else {
                        echo esc_html($date);
                    }
                }
                break;
        }
    }
    
    /**
     * Sortable columns including İanə Təsnifatı
     */
    public function sortable_columns($columns) {
        $columns['name'] = 'name';
        $columns['amount'] = 'amount';
        $columns['payment_date'] = 'payment_date';
        $columns['bank_order_id'] = 'bank_order_id';
        $columns['voen'] = 'voen';
        $columns['iane_tesnifati'] = 'iane_tesnifati'; // YENİ SORTABLE COLUMN
        return $columns;
    }
    
    /**
     * Order by columns including İanə Təsnifatı
     */
    public function orderby_columns($query) {
        if (!is_admin() || !$query->is_main_query() || 
            $query->get('post_type') !== $this->config['general']['post_type']) {
            return;
        }
        
        $orderby = $query->get('orderby');
        
        $meta_orderby = array(
            'name' => 'meta_value',
            'amount' => 'meta_value_num',
            'payment_date' => 'meta_value',
            'bank_order_id' => 'meta_value',
            'voen' => 'meta_value',
            'iane_tesnifati' => 'meta_value' // YENİ ORDERBY
        );
        
        if (isset($meta_orderby[$orderby])) {
            $query->set('meta_key', $orderby);
            $query->set('orderby', $meta_orderby[$orderby]);
        }
    }
    
    private function load_template($template, $args = array()) {
        $template_file = TIF_DONATION_TEMPLATES_DIR . $template . '.php';
        
        if (file_exists($template_file)) {
            extract($args);
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . 
                 sprintf(__('Template faylı tapılmadı: %s', 'kapital-tif-donation'), esc_html($template)) . 
                 '</p></div>';
        }
    }
    /**
     * Enqueue admin certificate scripts - YENİ METOD (class daxilində)
     */
    public function enqueue_admin_certificate_scripts($hook) {
        // Yalnız order edit səhifəsində
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post;
        if (!$post || $post->post_type !== $this->config['general']['post_type']) {
            return;
        }
        
        // Debug log
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("TIF Admin: Enqueuing certificate scripts for order: " . $post->ID);
        }
        
        // JavaScript üçün nonce data
        wp_localize_script('jquery', 'tif_admin_certificate', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'order_id' => $post->ID,
            'post_type' => $post->post_type,
            'nonces' => array(
                'preview' => wp_create_nonce('tif_preview_certificate'),
                'generate' => wp_create_nonce('tif_generate_certificate')
            ),
            'debug' => defined('WP_DEBUG') && WP_DEBUG,
            'messages' => array(
                'generating' => __('Sertifikat yaradılır...', 'kapital-tif-donation'),
                'converting' => __('PNG-yə çevrilir...', 'kapital-tif-donation'),
                'success' => __('Uğurla tamamlandı!', 'kapital-tif-donation'),
                'error' => __('Xəta baş verdi', 'kapital-tif-donation')
            )
        ));
    }
    
    /**
     * Debug üçün test AJAX handler (optional - test üçün)
     */
    public function test_certificate_ajax() {
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        wp_send_json_success(array(
            'message' => 'AJAX working correctly',
            'server_time' => current_time('mysql'),
            'request_data' => $_POST
        ));
    }

} // CLASS KAPANIR BURADA - ÇOX MÜHİM!

// Test AJAX hook (əgər debug lazımsa, uncomment et)
// add_action('wp_ajax_tif_test_certificate_ajax', array($this, 'test_certificate_ajax'));