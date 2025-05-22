<?php
/**
 * Configuration file for Kapital TIF Donation Plugin
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Plugin settings
return array(
    
    // General settings
    'general' => array(
        'text_domain' => 'kapital-tif-donation',
        'post_type' => 'odenis',
        'taxonomy' => 'odenis_statusu',
        'capability' => 'manage_options',
    ),
    
    // Test mode - Keep TRUE until all testing is complete
    // Set to FALSE only when ready for production deployment
    'test_mode' => true,
    
    // API Configuration - Test Environment
    'test' => array(
        'api_url' => 'https://txpgtst.kapitalbank.az/api',
        'hpp_url' => 'https://txpgtst.kapitalbank.az/flex',
        'username' => 'TerminalSys/kapital',
        'password' => 'kapital123',
    ),
    
    // API Configuration - Production Environment
    'production' => array(
        'api_url' => 'https://e-commerce.kapitalbank.az/api',
        'hpp_url' => 'https://e-commerce.kapitalbank.az/flex',
        'username' => 'TerminalSys/kapital', // Replace with production credentials
        'password' => 'kapital123', // Replace with production credentials
    ),
    
    // Payment settings
    'payment' => array(
        'currency' => 'AZN',
        'language' => 'az',
        'min_amount' => 1,
        'max_amount' => 10000,
        'timeout' => 30,
    ),
    
    // Order statuses mapping
    'status_mapping' => array(
        'FullyPaid' => 'completed',
        'Completed' => 'completed',
        'PreAuthorized' => 'processing',
        'Prepared' => 'processing',
        'Preparing' => 'processing',
        'Processing' => 'processing',
        'Declined' => 'failed',
        'Failed' => 'failed',
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
    
    // Security settings - Testing configuration
    'security' => array(
        'ssl_verify' => false, // Keep FALSE for testing, set TRUE for production
        'nonce_actions' => array(
            'donation_details' => 'tif_donation_details',
            'transaction_details' => 'tif_transaction_details',
            'sync_status' => 'tif_sync_status_nonce',
            'export_donations' => 'tif_export_donations_nonce',
        ),
    ),
    
    // Debug settings - Keep enabled during testing phase
    'debug' => array(
        'log_api_requests' => true, // Keep TRUE for testing
        'log_file' => WP_CONTENT_DIR . '/uploads/tif-donation-logs.txt',
        'max_log_size' => 5 * 1024 * 1024, // 5MB
    ),
    
    // Admin settings
    'admin' => array(
        'posts_per_page' => 20,
        'dashboard_widget' => true,
        'export_capability' => 'manage_options',
    ),
    
    // Frontend settings
    'frontend' => array(
        'load_styles' => true,
        'load_scripts' => true,
        'form_validation' => true,
    ),
);