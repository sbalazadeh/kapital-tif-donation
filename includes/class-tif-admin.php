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
    
    public function add_meta_boxes() {
        add_meta_box(
            'tif_donation_details',
            __('İanə məlumatları', 'kapital-tif-donation'),
            array($this, 'donation_details_callback'),
            $this->config['general']['post_type'],
            'normal',
            'high'
        );
        
        add_meta_box(
            'tif_transaction_details',
            __('Əməliyyat məlumatları', 'kapital-tif-donation'),
            array($this, 'transaction_details_callback'),
            $this->config['general']['post_type'],
            'normal',
            'high'
        );
        
        // API debug info meta box for test mode
        if ($this->config['test_mode'] && $this->config['debug']['log_api_requests']) {
            add_meta_box(
                'tif_debug_info',
                __('Debug Məlumatları', 'kapital-tif-donation'),
                array($this, 'debug_info_callback'),
                $this->config['general']['post_type'],
                'side',
                'low'
            );
        }
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
     * Donation details callback with İanə Təsnifatı
     */
    public function donation_details_callback($post) {
        wp_nonce_field($this->config['security']['nonce_actions']['donation_details'], 'tif_donation_details_nonce');
        
        $name = get_post_meta($post->ID, 'name', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $amount = get_post_meta($post->ID, 'amount', true);
        $company = get_post_meta($post->ID, 'company', true);
        $company_name = get_post_meta($post->ID, 'company_name', true);
        $voen = get_post_meta($post->ID, 'voen', true);
        $iane_tesnifati = get_post_meta($post->ID, 'iane_tesnifati', true); // YENİ FIELD
        
        $this->load_template('admin/donation-details', array(
            'name' => $name,
            'phone' => $phone,
            'amount' => $amount,
            'company' => $company,
            'company_name' => $company_name,
            'voen' => $voen,
            'iane_tesnifati' => $iane_tesnifati // YENİ VARIABLE
        ));
    }
    
    public function transaction_details_callback($post) {
        wp_nonce_field($this->config['security']['nonce_actions']['transaction_details'], 'tif_transaction_details_nonce');
        
        $bank_order_id = get_post_meta($post->ID, 'bank_order_id', true);
        $trans_id_local = get_post_meta($post->ID, 'transactionId_local', true);
        $payment_method = get_post_meta($post->ID, 'payment_method', true);
        $payment_date = get_post_meta($post->ID, 'payment_date', true);
        $approval_code = get_post_meta($post->ID, 'approval_code', true);
        $payment_status = get_post_meta($post->ID, 'payment_status', true);
        $card_number = get_post_meta($post->ID, 'card_number', true);
        $order_data = get_post_meta($post->ID, 'order_data', true);
        
        // Get current taxonomy status
        $terms = wp_get_object_terms($post->ID, $this->config['general']['taxonomy']);
        $current_term = !empty($terms) ? $terms[0]->name : 'Unknown';
        
        $this->load_template('admin/transaction-details', array(
            'post_id' => $post->ID,
            'bank_order_id' => $bank_order_id,
            'trans_id_local' => $trans_id_local,
            'payment_method' => $payment_method,
            'payment_date' => $payment_date,
            'approval_code' => $approval_code,
            'payment_status' => $payment_status,
            'card_number' => $card_number,
            'order_data' => $order_data,
            'current_term' => $current_term,
            'nonce' => wp_create_nonce($this->config['security']['nonce_actions']['sync_status'])
        ));
    }
    
    /**
     * Save meta box data including İanə Təsnifatı
     */
    public function save_meta_box_data($post_id) {
        // Check nonces
        if (!isset($_POST['tif_donation_details_nonce']) || 
            !wp_verify_nonce($_POST['tif_donation_details_nonce'], $this->config['security']['nonce_actions']['donation_details'])) {
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
        
        // Save donation details - İanə Təsnifatı əlavə edildi
        $donation_fields = array('name', 'phone', 'amount', 'company', 'company_name', 'voen', 'iane_tesnifati');
        foreach ($donation_fields as $field) {
            if (isset($_POST[$field])) {
                $value = $field === 'amount' ? floatval($_POST[$field]) : sanitize_text_field($_POST[$field]);
                update_post_meta($post_id, $field, $value);
            }
        }
        
        // Save transaction details
        $transaction_fields = array(
            'bank_order_id', 'transactionId_local', 'payment_method', 
            'payment_date', 'approval_code', 'card_number'
        );
        
        foreach ($transaction_fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Handle transaction ID as post title
        if (isset($_POST['transactionId_local'])) {
            $trans_id = sanitize_text_field($_POST['transactionId_local']);
            if (!empty($trans_id)) {
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_title' => $trans_id
                ));
            }
        }
        
        // Handle payment status update
        if (isset($_POST['payment_status'])) {
            $new_status = sanitize_text_field($_POST['payment_status']);
            $old_status = get_post_meta($post_id, 'payment_status', true);
            
            if ($new_status !== $old_status) {
                update_post_meta($post_id, 'payment_status', $new_status);
                $this->database->update_order_status($post_id, $new_status);
            }
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
            if (!isset($new_columns[$key]) && $key != 'title') {
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
}