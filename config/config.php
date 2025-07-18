<?php
/**
 * TIF Donation Plugin Configuration - EDITOR PERMISSIONS UPDATED
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

return array(
    // Test mode (production-da false olmalıdır)
    'test_mode' => false,
    
    // Test environment configuration
    'test' => array(
        'api_url' => 'https://txpgtst.kapitalbank.az/api',
        'hpp_url' => 'https://txpgtst.kapitalbank.az/flex',
        'username' => 'TerminalSys/kapital',
        'password' => 'kapital123',
    ),
    
    // Production environment configuration
    'production' => array(
        'api_url' => 'https://e-commerce.kapitalbank.az/api',
        'hpp_url' => 'https://e-commerce.kapitalbank.az/flex',
        'username' => 'TerminalSys/E1020337',
        'password' => 'U9q0:83S*&QyKl1eo7y)',
    ),
    
    // General plugin settings - EDITOR PERMISSIONS UPDATED
    'general' => array(
        'post_type' => 'odenis',
        'taxonomy' => 'odenis_statusu',
        'capability' => 'edit_posts', // ← CHANGED: manage_options → edit_posts
        'menu_position' => 25,
    ),
    
    // Payment settings
    'payment' => array(
        'currency' => 'AZN',
        'language' => 'az',
        'min_amount' => 1,
        'max_amount' => 10000,
        'timeout' => 30,
    ),
    
    // Certificate settings
    'certificate' => array(
        'enabled' => true,
        'auto_generate' => true,
        'default_type' => 'tif',
        'path' => WP_CONTENT_DIR . '/uploads/tif-certificates/',
        'url' => WP_CONTENT_URL . '/uploads/tif-certificates/',
    ),
    
    // Status mapping
    'status_mapping' => array(
        'FullyPaid' => 'completed',
        'Completed' => 'completed',
        'Success' => 'completed',
        'Declined' => 'failed',
        'Failed' => 'failed',
        'Processing' => 'processing',
        'Preparing' => 'processing',
        'Prepared' => 'processing',
        'Pre-authorized' => 'processing',
        'Cancelled' => 'cancelled',
        'Pending' => 'pending',
    ),
    
    // Default statuses to create
    'default_statuses' => array(
        'pending' => 'Pending',
        'completed' => 'Completed',
        'failed' => 'Failed',
        'processing' => 'Processing',
        'cancelled' => 'Cancelled',
    ),
    
    // Security settings - PRODUCTION SECURITY
    'security' => array(
        'ssl_verify' => true, // SSL aktivləşdirildi
        'nonce_actions' => array(
            'form_submission' => 'tif_donation_submit_action',
            'donation_details' => 'tif_donation_details_action',
            'transaction_details' => 'tif_transaction_details_action',
            'certificate_details' => 'tif_certificate_details_action', 
            'export_donations' => 'tif_export_donations_action',
            'bulk_actions' => 'tif_bulk_actions',
            'sync_status' => 'tif_sync_status',      
        ),
    ),
    
    // Debug settings - PRODUCTION DEBUG DISABLED
    'debug' => array(
        'log_api_requests' => true, // Log-lar aktiv
        'log_certificate' => true, // Certificate log-ları
        'log_file' => WP_CONTENT_DIR . '/uploads/tif-donation-logs.txt',
        'max_log_size' => 5 * 1024 * 1024, // 5MB
    ),
    
    // Admin settings - EDITOR PERMISSIONS UPDATED
    'admin' => array(
        'posts_per_page' => 20,
        'dashboard_widget' => true,
        'export_capability' => 'edit_posts', // ← CHANGED: manage_options → edit_posts
    ),
    
    // Frontend settings
    'frontend' => array(
        'load_styles' => true,
        'load_scripts' => true,
        'form_validation' => true,
    ),
);