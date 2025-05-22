<?php
/**
 * Database Operations Class
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class TIF_Database {
    
    /**
     * Plugin configuration
     */
    private $config;
    
    /**
     * Constructor
     */
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Register custom post type
     */
    public function register_post_type() {
        register_post_type($this->config['general']['post_type'], array(
            'labels' => array(
                'name' => __('İanələr', 'kapital-tif-donation'),
                'singular_name' => __('İanə', 'kapital-tif-donation'),
                'add_new' => __('Yeni əlavə et', 'kapital-tif-donation'),
                'add_new_item' => __('Yeni ianə əlavə et', 'kapital-tif-donation'),
                'edit_item' => __('İanəni redaktə et', 'kapital-tif-donation'),
                'new_item' => __('Yeni ianə', 'kapital-tif-donation'),
                'view_item' => __('İanəyə bax', 'kapital-tif-donation'),
                'search_items' => __('İanələri axtar', 'kapital-tif-donation'),
                'not_found' => __('İanə tapılmadı', 'kapital-tif-donation'),
                'not_found_in_trash' => __('Zibil qutusunda ianə tapılmadı', 'kapital-tif-donation'),
            ),
            'public' => true,
            'publicly_queryable' => false,
            'supports' => array('title', 'editor'),
            'taxonomies' => array($this->config['general']['taxonomy']),
            'rewrite' => false,
            'menu_icon' => 'dashicons-money-alt',
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'show_in_menu' => true,
            'show_ui' => true,
            'show_in_admin_bar' => true,
            'show_in_nav_menus' => false,
            'can_export' => true,
            'has_archive' => false,
            'exclude_from_search' => true,
            'publicly_queryable' => false,
        ));
    }
    
    /**
     * Register custom taxonomy
     */
    public function register_taxonomy() {
        register_taxonomy($this->config['general']['taxonomy'], $this->config['general']['post_type'], array(
            'label' => __('Ödəniş statusu', 'kapital-tif-donation'),
            'show_admin_column' => true,
            'hierarchical' => true,
            'rewrite' => array('slug' => 'odenis-statusu'),
            'show_in_rest' => true,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_nav_menus' => false,
            'show_tagcloud' => false,
            'show_in_quick_edit' => true,
            'meta_box_cb' => 'post_categories_meta_box',
        ));
    }
    
    /**
     * Create default terms
     */
    public function create_default_terms() {
        foreach ($this->config['default_statuses'] as $slug => $name) {
            if (!term_exists($name, $this->config['general']['taxonomy'])) {
                wp_insert_term($name, $this->config['general']['taxonomy'], array(
                    'slug' => $slug
                ));
            }
        }
    }
    
    /**
     * Create donation order
     */
    public function create_order($amount, $data) {
        $name = isset($data['ad_soyad']) ? sanitize_text_field($data['ad_soyad']) : '';
        $phone = isset($data['telefon_nomresi']) ? sanitize_text_field($data['telefon_nomresi']) : '';
        $company = isset($data['fiziki_huquqi']) ? sanitize_text_field($data['fiziki_huquqi']) : 'Fiziki şəxs';
        $company_name = isset($data['teskilat_adi']) ? sanitize_text_field($data['teskilat_adi']) : '';
        
        // Generate unique transaction ID
        $transaction_id = 'TIF-' . date('Ymd') . '-' . uniqid();
        
        $post_data = array(
            'post_title' => $transaction_id,
            'post_content' => date('Y-m-d H:i:s') . PHP_EOL . print_r($data, true),
            'post_author' => 1,
            'post_type' => $this->config['general']['post_type'],
            'post_status' => 'publish'
        );
        
        $order_id = wp_insert_post($post_data);
        
        if (!$order_id || is_wp_error($order_id)) {
            return 0;
        }
        
        // Set metadata
        $meta_data = array(
            'name' => $name,
            'phone' => $phone,
            'amount' => floatval($amount),
            'company' => $company,
            'company_name' => $company_name,
            'payment_date' => current_time('d-m-Y H:i:s'),
            'transactionId_local' => $transaction_id,
            'payment_status' => 'Pending',
        );
        
        foreach ($meta_data as $key => $value) {
            update_post_meta($order_id, $key, $value);
        }
        
        // Set initial status
        $this->update_order_status($order_id, 'Pending');
        
        return $order_id;
    }
    
    /**
     * Update order status
     */
    public function update_order_status($order_id, $status) {
        global $wpdb;
        
        // Update meta
        update_post_meta($order_id, 'payment_status', $status);
        
        // Map status to taxonomy term
        $term_slug = $this->get_status_mapping($status);
        $term = get_term_by('slug', $term_slug, $this->config['general']['taxonomy']);
        
        if (!$term) {
            // Create term if it doesn't exist
            $term_name = ucfirst($term_slug);
            $term_result = wp_insert_term($term_name, $this->config['general']['taxonomy'], array(
                'slug' => $term_slug
            ));
            
            if (!is_wp_error($term_result)) {
                $term = get_term_by('id', $term_result['term_id'], $this->config['general']['taxonomy']);
            }
        }
        
        if ($term) {
            try {
                // Direct database update for reliability
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->term_relationships} 
                    WHERE object_id = %d 
                    AND term_taxonomy_id IN (
                        SELECT tt.term_taxonomy_id 
                        FROM {$wpdb->term_taxonomy} tt 
                        WHERE tt.taxonomy = %s
                    )",
                    $order_id, $this->config['general']['taxonomy']
                ));
                
                $wpdb->query($wpdb->prepare(
                    "INSERT INTO {$wpdb->term_relationships} 
                    (object_id, term_taxonomy_id, term_order) 
                    VALUES (%d, %d, 0)",
                    $order_id, $term->term_taxonomy_id
                ));
                
                $wpdb->query($wpdb->prepare(
                    "UPDATE {$wpdb->term_taxonomy} 
                    SET count = (
                        SELECT COUNT(*) 
                        FROM {$wpdb->term_relationships} 
                        WHERE term_taxonomy_id = %d
                    )
                    WHERE term_taxonomy_id = %d",
                    $term->term_taxonomy_id, $term->term_taxonomy_id
                ));
                
                // Clean caches
                clean_post_cache($order_id);
                clean_term_cache($term->term_id, $this->config['general']['taxonomy']);
                
                // Update post
                wp_update_post(array(
                    'ID' => $order_id,
                    'post_modified' => current_time('mysql'),
                    'post_modified_gmt' => current_time('mysql', true)
                ));
                
            } catch (Exception $e) {
                // Fallback to WordPress functions
                wp_set_object_terms($order_id, array($term->term_id), $this->config['general']['taxonomy']);
            }
        }
        
        return true;
    }
    
    /**
     * Complete order
     */
    public function complete_order($order_id) {
        return $this->update_order_status($order_id, 'Completed');
    }
    
    /**
     * Get status mapping
     */
    private function get_status_mapping($status) {
        $status_lower = strtolower($status);
        
        if (isset($this->config['status_mapping'][$status])) {
            return $this->config['status_mapping'][$status];
        }
        
        // Fallback mappings
        if (in_array($status_lower, array('fullypaid', 'completed'))) {
            return 'completed';
        } elseif (in_array($status_lower, array('declined', 'failed'))) {
            return 'failed';
        } elseif ($status_lower === 'cancelled') {
            return 'cancelled';
        } elseif (in_array($status_lower, array('preparing', 'prepared', 'processing', 'pre-authorized'))) {
            return 'processing';
        }
        
        return 'pending';
    }
    
    /**
     * Sync payment status with API
     */
    public function sync_payment_status($post_id, $api) {
        $bank_order_id = get_post_meta($post_id, 'bank_order_id', true);
        
        if (!$bank_order_id) {
            return false;
        }
        
        $order_data = $api->get_order_status($bank_order_id);
        
        if (isset($order_data['order']['status'])) {
            $this->update_order_status($post_id, $order_data['order']['status']);
            
            // Get current status term
            $terms = wp_get_object_terms($post_id, $this->config['general']['taxonomy']);
            return !empty($terms) ? $terms[0]->name : 'Unknown';
        }
        
        return false;
    }
    
    /**
     * Get orders with filters
     */
    public function get_orders($args = array()) {
        $default_args = array(
            'post_type' => $this->config['general']['post_type'],
            'posts_per_page' => $this->config['admin']['posts_per_page'],
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $default_args);
        
        return get_posts($args);
    }
    
    /**
     * Get orders for export
     */
    public function get_orders_for_export($date_from = '', $date_to = '') {
        $args = array(
            'post_type' => $this->config['general']['post_type'],
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        if (!empty($date_from) || !empty($date_to)) {
            $args['date_query'] = array();
            
            if (!empty($date_from)) {
                $args['date_query']['after'] = $date_from . ' 00:00:00';
            }
            
            if (!empty($date_to)) {
                $args['date_query']['before'] = $date_to . ' 23:59:59';
            }
            
            $args['date_query']['inclusive'] = true;
        }
        
        return get_posts($args);
    }
    
    /**
     * Get order statistics
     */
    public function get_statistics() {
        global $wpdb;
        
        $post_type = $this->config['general']['post_type'];
        $taxonomy = $this->config['general']['taxonomy'];
        
        $stats = array();
        
        // Total orders
        $stats['total'] = wp_count_posts($post_type)->publish;
        
        // Orders by status
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT t.name, tt.count 
            FROM {$wpdb->terms} t 
            JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
            WHERE tt.taxonomy = %s
        ", $taxonomy));
        
        foreach ($results as $result) {
            $stats['by_status'][$result->name] = $result->count;
        }
        
        // Total amount
        $total_amount = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
            FROM {$wpdb->postmeta} pm 
            JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
            WHERE p.post_type = %s 
            AND pm.meta_key = 'amount'
        ", $post_type));
        
        $stats['total_amount'] = floatval($total_amount);
        
        return $stats;
    }
}