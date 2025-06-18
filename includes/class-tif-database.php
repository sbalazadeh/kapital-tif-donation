<?php
/**
 * Database Operations Class - İanə Təsnifatı Field əlavə edildi
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
     * Cache group name
     */
    private $cache_group = 'tif_donation';
    
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
     * Create donation order - İanə Təsnifatı field əlavə edildi
     */
    public function create_order($amount, $data) {
        $name = isset($data['ad_soyad']) ? sanitize_text_field($data['ad_soyad']) : '';
        $phone = isset($data['telefon_nomresi']) ? sanitize_text_field($data['telefon_nomresi']) : '';
        $company = isset($data['fiziki_huquqi']) ? sanitize_text_field($data['fiziki_huquqi']) : 'Fiziki şəxs';
        $company_name = isset($data['teskilat_adi']) ? sanitize_text_field($data['teskilat_adi']) : '';
        $voen = isset($data['voen']) ? sanitize_text_field($data['voen']) : '';
        
        // YENİ: İanə Təsnifatı field əlavə edildi
        $iane_tesnifati = isset($data['iane_tesnifati']) ? sanitize_text_field($data['iane_tesnifati']) : '';
        
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
        
        // Set metadata - İanə Təsnifatı əlavə edildi
        $meta_data = array(
            'name' => $name,
            'phone' => $phone,
            'amount' => floatval($amount),
            'company' => $company,
            'company_name' => $company_name,
            'voen' => $voen,
            'iane_tesnifati' => $iane_tesnifati, // YENİ FIELD
            'payment_date' => current_time('d-m-Y H:i:s'),
            'transactionId_local' => $transaction_id,
            'payment_status' => 'Pending',
        );
        
        foreach ($meta_data as $key => $value) {
            update_post_meta($order_id, $key, $value);
        }
        
        // Set initial status
        $this->update_order_status($order_id, 'Pending');
        
        // Clear statistics cache
        $this->clear_stats_cache();
        
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
        
        // Clear statistics cache when status changes
        $this->clear_stats_cache();
        
        return true;
    }
    
    /**
     * Complete order
     */
    public function complete_order($order_id) {
        $result = $this->update_order_status($order_id, 'Completed');
        
        // Clear statistics cache
        $this->clear_stats_cache();
        
        return $result;
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
        
        try {
            $order_data = $api->get_order_status($bank_order_id);
            
            if (isset($order_data['order']['status'])) {
                $this->update_order_status($post_id, $order_data['order']['status']);
                
                // Get current status term
                $terms = wp_get_object_terms($post_id, $this->config['general']['taxonomy']);
                return !empty($terms) ? $terms[0]->name : 'Unknown';
            }
        } catch (Exception $e) {
            error_log('TIF Donation Sync Error: ' . $e->getMessage());
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
     * Get orders for export - İanə Təsnifatı field əlavə edildi
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
     * Get order statistics with caching
     */
    public function get_statistics() {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'tif_donation_stats';
        $stats = wp_cache_get($cache_key, $this->cache_group);
        
        if (false !== $stats) {
            return $stats;
        }
        
        $post_type = $this->config['general']['post_type'];
        $taxonomy = $this->config['general']['taxonomy'];
        
        $stats = array();
        
        try {
            // Total orders
            $total_posts = wp_count_posts($post_type);
            $stats['total'] = isset($total_posts->publish) ? $total_posts->publish : 0;
            
            // Orders by status
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT t.name, tt.count 
                FROM {$wpdb->terms} t 
                JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id 
                WHERE tt.taxonomy = %s
            ", $taxonomy));
            
            $stats['by_status'] = array();
            if (!empty($results)) {
                foreach ($results as $result) {
                    $stats['by_status'][$result->name] = intval($result->count);
                }
            }
            
            // Total amount - bütün ianələrin məbləği
            $total_amount = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
                FROM {$wpdb->postmeta} pm 
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                WHERE p.post_type = %s 
                AND pm.meta_key = 'amount'
                AND pm.meta_value IS NOT NULL
                AND pm.meta_value != ''
                AND pm.meta_value REGEXP '^[0-9]+(\.[0-9]+)?$'
            ", $post_type));
            
            $stats['total_amount'] = floatval($total_amount);
            
            // Completed orders amount - yalnız completed statuslu ianələrin məbləği
            $completed_amount = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
                FROM {$wpdb->postmeta} pm 
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE p.post_type = %s 
                AND pm.meta_key = 'amount'
                AND pm.meta_value IS NOT NULL
                AND pm.meta_value != ''
                AND pm.meta_value REGEXP '^[0-9]+(\.[0-9]+)?$'
                AND tt.taxonomy = %s
                AND t.slug = 'completed'
            ", $post_type, $taxonomy));
            
            $stats['completed_amount'] = floatval($completed_amount);
            
            // Failed orders count - pending, failed, cancelled birləşdiririk
            $failed_count = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p 
                JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE p.post_type = %s 
                AND tt.taxonomy = %s
                AND t.slug IN ('pending', 'failed', 'cancelled')
            ", $post_type, $taxonomy));
            
            // Failed orders amount - pending, failed, cancelled məbləğləri  
            $failed_amount = $wpdb->get_var($wpdb->prepare("
                SELECT SUM(CAST(pm.meta_value AS DECIMAL(10,2))) 
                FROM {$wpdb->postmeta} pm 
                JOIN {$wpdb->posts} p ON pm.post_id = p.ID 
                JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
                WHERE p.post_type = %s 
                AND pm.meta_key = 'amount'
                AND pm.meta_value IS NOT NULL
                AND pm.meta_value != ''
                AND pm.meta_value REGEXP '^[0-9]+(\.[0-9]+)?$'
                AND tt.taxonomy = %s
                AND t.slug IN ('pending', 'failed', 'cancelled')
            ", $post_type, $taxonomy));
            
            $stats['failed_amount'] = floatval($failed_amount);
            $stats['failed_count'] = intval($failed_count);
            
            // Override by_status for Failed to include pending + failed + cancelled
            $stats['by_status']['Failed'] = $stats['failed_count'];
            
            // Calculate success rate if we have completed payments
            if (isset($stats['by_status']['Completed']) && $stats['total'] > 0) {
                $stats['success_rate'] = round(($stats['by_status']['Completed'] / $stats['total']) * 100, 2);
            } else {
                $stats['success_rate'] = 0;
            }
            
            // Get recent activity (last 30 days)
            $recent_activity = $wpdb->get_var($wpdb->prepare("
                SELECT COUNT(*) 
                FROM {$wpdb->posts} 
                WHERE post_type = %s 
                AND post_status = 'publish'
                AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ", $post_type));
            
            $stats['recent_activity'] = intval($recent_activity);
            
        } catch (Exception $e) {
            // Fallback values in case of database error
            error_log('TIF Donation Statistics Error: ' . $e->getMessage());
            $stats = array(
                'total' => 0,
                'total_amount' => 0,
                'completed_amount' => 0,
                'failed_amount' => 0,
                'failed_count' => 0,
                'by_status' => array(),
                'success_rate' => 0,
                'recent_activity' => 0
            );
        }
        
        // Cache for 5 minutes
        wp_cache_set($cache_key, $stats, $this->cache_group, 300);
        
        return $stats;
    }
    
    /**
     * Clear statistics cache when needed
     */
    public function clear_stats_cache() {
        wp_cache_delete('tif_donation_stats', $this->cache_group);
        
        // Also clear pending donations count cache
        wp_cache_delete('pending_donations_count', 'tif_donation_admin');
    }
    
    /**
     * Get pending donations count
     */
    public function get_pending_count() {
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
     * Get orders by date range
     */
    public function get_orders_by_date_range($date_from, $date_to, $status = '') {
        global $wpdb;
        
        $sql = "SELECT p.* FROM {$wpdb->posts} p";
        
        if (!empty($status)) {
            $sql .= " JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id";
            $sql .= " JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id";
            $sql .= " JOIN {$wpdb->terms} t ON tt.term_id = t.term_id";
        }
        
        $sql .= " WHERE p.post_type = %s AND p.post_status = 'publish'";
        
        $params = array($this->config['general']['post_type']);
        
        if (!empty($date_from)) {
            $sql .= " AND p.post_date >= %s";
            $params[] = $date_from . ' 00:00:00';
        }
        
        if (!empty($date_to)) {
            $sql .= " AND p.post_date <= %s";
            $params[] = $date_to . ' 23:59:59';
        }
        
        if (!empty($status)) {
            $sql .= " AND tt.taxonomy = %s AND t.slug = %s";
            $params[] = $this->config['general']['taxonomy'];
            $params[] = $this->get_status_mapping($status);
        }
        
        $sql .= " ORDER BY p.post_date DESC";
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Bulk update order statuses
     */
    public function bulk_update_statuses($order_ids, $new_status) {
        $updated_count = 0;
        
        if (empty($order_ids) || !is_array($order_ids)) {
            return $updated_count;
        }
        
        foreach ($order_ids as $order_id) {
            if ($this->update_order_status($order_id, $new_status)) {
                $updated_count++;
            }
        }
        
        // Clear cache after bulk update
        $this->clear_stats_cache();
        
        return $updated_count;
    }
    
    /**
     * Delete old log entries (cleanup function)
     */
    public function cleanup_old_data($days_to_keep = 365) {
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_to_keep} days"));
        
        // Get old posts
        $old_posts = $wpdb->get_col($wpdb->prepare("
            SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = %s 
            AND post_date < %s
        ", $this->config['general']['post_type'], $cutoff_date));
        
        $deleted_count = 0;
        
        foreach ($old_posts as $post_id) {
            if (wp_delete_post($post_id, true)) {
                $deleted_count++;
            }
        }
        
        if ($deleted_count > 0) {
            $this->clear_stats_cache();
        }
        
        return $deleted_count;
    }
}