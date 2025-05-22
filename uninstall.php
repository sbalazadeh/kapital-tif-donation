<?php
/**
 * Uninstall script for Kapital TIF Donation Plugin
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user has permission to delete plugins
if (!current_user_can('delete_plugins')) {
    exit;
}

// Load plugin configuration
$config_file = plugin_dir_path(__FILE__) . 'config/config.php';
if (file_exists($config_file)) {
    $config = require $config_file;
} else {
    // Fallback configuration
    $config = array(
        'general' => array(
            'post_type' => 'odenis',
            'taxonomy' => 'odenis_statusu',
        )
    );
}

/**
 * Remove all plugin data
 */
function tif_donation_remove_all_data($config) {
    global $wpdb;
    
    $post_type = $config['general']['post_type'];
    $taxonomy = $config['general']['taxonomy'];
    
    // 1. Delete all posts of our custom post type
    $posts = get_posts(array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'any'
    ));
    
    foreach ($posts as $post) {
        // Delete post meta
        $wpdb->delete($wpdb->postmeta, array('post_id' => $post->ID));
        
        // Delete post
        wp_delete_post($post->ID, true);
    }
    
    // 2. Remove taxonomy terms and relationships
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
        'fields' => 'ids'
    ));
    
    if (!empty($terms) && !is_wp_error($terms)) {
        foreach ($terms as $term_id) {
            wp_delete_term($term_id, $taxonomy);
        }
    }
    
    // 3. Clean up taxonomy from database
    $wpdb->delete($wpdb->term_taxonomy, array('taxonomy' => $taxonomy));
    
    // 4. Remove any orphaned term relationships
    $wpdb->query("DELETE tr FROM {$wpdb->term_relationships} tr 
                  LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID 
                  WHERE p.ID IS NULL");
    
    // 5. Remove plugin options
    delete_option('tif_donation_options');
    delete_option('tif_donation_version');
    
    // 6. Clear scheduled events
    wp_clear_scheduled_hook('tif_hourly_status_sync');
    
    // 7. Remove user capabilities if any were added
    $roles = wp_roles();
    foreach ($roles->roles as $role_name => $role_info) {
        $role = get_role($role_name);
        if ($role) {
            $role->remove_cap('manage_' . $post_type);
            $role->remove_cap('edit_' . $post_type);
            $role->remove_cap('read_' . $post_type);
            $role->remove_cap('delete_' . $post_type);
        }
    }
    
    // 8. Remove log files
    $upload_dir = wp_upload_dir();
    $log_files = array(
        $upload_dir['basedir'] . '/tif-donation-logs.txt',
        $upload_dir['basedir'] . '/tif-donation-logs.txt.old',
        WP_CONTENT_DIR . '/uploads/tif-donation-logs.txt',
        WP_CONTENT_DIR . '/uploads/tif-donation-logs.txt.old'
    );
    
    foreach ($log_files as $log_file) {
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
    
    // 9. Clean up any transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_tif_donation_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_tif_donation_%'");
    
    // 10. Remove any custom database tables if they were created (none in our case)
    
    // 11. Clean up WordPress cache
    wp_cache_flush();
    
    // 12. Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Ask user what to do with data
 */
function tif_donation_uninstall_with_confirmation() {
    // Check if user explicitly wants to remove all data
    $remove_data = get_option('tif_donation_remove_data_on_uninstall', false);
    
    if ($remove_data) {
        // User has confirmed data removal
        global $config;
        tif_donation_remove_all_data($config);
        
        // Remove the confirmation option
        delete_option('tif_donation_remove_data_on_uninstall');
        
        // Log the uninstall
        error_log('Kapital TIF Donation Plugin: All data removed during uninstall');
    } else {
        // Just clean up plugin options and leave data intact
        delete_option('tif_donation_options');
        wp_clear_scheduled_hook('tif_hourly_status_sync');
        
        // Log the uninstall
        error_log('Kapital TIF Donation Plugin: Plugin uninstalled, data preserved');
    }
}

// Execute uninstall
try {
    tif_donation_uninstall_with_confirmation();
} catch (Exception $e) {
    // Log any errors
    error_log('Kapital TIF Donation Plugin Uninstall Error: ' . $e->getMessage());
}

// Final cleanup
wp_cache_flush();