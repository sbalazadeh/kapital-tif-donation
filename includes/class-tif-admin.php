<?php
/**
 * Admin Operations Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Admin {
    
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
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . $this->config['general']['post_type'], array($this, 'save_meta_box_data'));
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        add_action('admin_menu', array($this, 'add_admin_menus'));
        
        // Customize admin columns
        add_filter('manage_' . $this->config['general']['post_type'] . '_posts_columns', array($this, 'add_custom_columns'));
        add_action('manage_' . $this->config['general']['post_type'] . '_posts_custom_column', array($this, 'fill_custom_columns'), 10, 2);
        add_filter('manage_edit-' . $this->config['general']['post_type'] . '_sortable_columns', array($this, 'sortable_columns'));
        add_action('pre_get_posts', array($this, 'orderby_columns'));
    }
    
    /**
     * Add meta boxes
     */
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
    }
    
    /**
     * Donation details meta box callback
     */
    public function donation_details_callback($post) {
        wp_nonce_field($this->config['security']['nonce_actions']['donation_details'], 'tif_donation_details_nonce');
        
        $name = get_post_meta($post->ID, 'name', true);
        $phone = get_post_meta($post->ID, 'phone', true);
        $amount = get_post_meta($post->ID, 'amount', true);
        $company = get_post_meta($post->ID, 'company', true);
        $company_name = get_post_meta($post->ID, 'company_name', true);
        
        $this->load_template('admin/donation-details', array(
            'name' => $name,
            'phone' => $phone,
            'amount' => $amount,
            'company' => $company,
            'company_name' => $company_name
        ));
    }
    
    /**
     * Transaction details meta box callback
     */
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
     * Save meta box data
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
        
        // Save donation details
        $donation_fields = array('name', 'phone', 'amount', 'company', 'company_name');
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
    
    /**
     * Add dashboard widget
     */
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
    
    /**
     * Dashboard widget callback
     */
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
                
                echo '<tr>';
                echo '<td><a href="' . get_edit_post_link($order->ID) . '">' . esc_html($trans_id) . '</a></td>';
                echo '<td>' . esc_html($name) . '</td>';
                echo '<td>' . esc_html($amount) . ' AZN</td>';
                echo '<td>' . esc_html($status) . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody></table>';
            echo '<p><a href="' . admin_url('edit.php?post_type=' . $this->config['general']['post_type']) . '">' . __('Bütün ianələri görüntülə', 'kapital-tif-donation') . '</a></p>';
        } else {
            echo '<p>' . __('Hələ ki heç bir ianə qeydə alınmayıb.', 'kapital-tif-donation') . '</p>';
        }
    }
    
    /**
     * Add admin menus
     */
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
    }
    
    /**
     * Export donations page
     */
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
    
    /**
     * Statistics page
     */
    public function statistics_page() {
        $stats = $this->database->get_statistics();
        
        $this->load_template('admin/statistics', array(
            'stats' => $stats
        ));
    }
    
    /**
     * Add custom columns
     */
    public function add_custom_columns($columns) {
        $new_columns = array();
        
        if (isset($columns['title'])) {
            $new_columns['title'] = 'Transaction ID Local';
        }
        
        $new_columns['name'] = __('Ad və soyad', 'kapital-tif-donation');
        $new_columns['phone'] = __('Telefon', 'kapital-tif-donation');
        $new_columns['amount'] = __('Məbləğ', 'kapital-tif-donation');
        $new_columns['company'] = __('Təşkilat', 'kapital-tif-donation');
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
     * Fill custom columns
     */
    public function fill_custom_columns($column, $post_id) {
        switch ($column) {
            case 'name':
                echo esc_html(get_post_meta($post_id, 'name', true));
                break;
            case 'phone':
                echo esc_html(get_post_meta($post_id, 'phone', true));
                break;
            case 'amount':
                $amount = get_post_meta($post_id, 'amount', true);
                echo !empty($amount) ? esc_html($amount) . ' AZN' : '';
                break;
            case 'company':
                $company = get_post_meta($post_id, 'company', true);
                $company_name = get_post_meta($post_id, 'company_name', true);
                
                if ($company === 'Hüquqi şəxs' && !empty($company_name)) {
                    echo esc_html($company_name);
                } else {
                    echo esc_html($company);
                }
                break;
            case 'bank_order_id':
                echo esc_html(get_post_meta($post_id, 'bank_order_id', true));
                break;
            case 'payment_date':
                echo esc_html(get_post_meta($post_id, 'payment_date', true));
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function sortable_columns($columns) {
        $columns['name'] = 'name';
        $columns['amount'] = 'amount';
        $columns['payment_date'] = 'payment_date';
        $columns['bank_order_id'] = 'bank_order_id';
        return $columns;
    }
    
    /**
     * Handle column sorting
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
            'bank_order_id' => 'meta_value'
        );
        
        if (isset($meta_orderby[$orderby])) {
            $query->set('meta_key', $orderby);
            $query->set('orderby', $meta_orderby[$orderby]);
        }
    }
    
    /**
     * Load admin template
     */
    private function load_template($template, $args = array()) {
        $template_file = TIF_DONATION_TEMPLATES_DIR . $template . '.php';
        
        if (file_exists($template_file)) {
            extract($args);
            include $template_file;
        } else {
            echo '<div class="notice notice-error"><p>' . 
                 sprintf(__('Template faylı tapılmadı: %s', 'kapital-tif-donation'), $template) . 
                 '</p></div>';
        }
    }
}