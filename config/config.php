<?php
/**
 * Configuration file for Kapital TIF Donation Plugin - PRODUCTION READY
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
    
    // PRODUCTION MODE ACTIVE
    'test_mode' => false,
    
    // API Configuration - Test Environment (saxlanılır test üçün)
    'test' => array(
        'api_url' => 'https://txpgtst.kapitalbank.az/api',
        'hpp_url' => 'https://txpgtst.kapitalbank.az/flex',
        'username' => 'TerminalSys/kapital',
        'password' => 'kapital123',
    ),
    
    // API Configuration - Production Environment - REAL CREDENTIALS
    'production' => array(
        'api_url' => 'https://e-commerce.kapitalbank.az/api',
        'hpp_url' => 'https://e-commerce.kapitalbank.az/flex',
        'username' => 'TerminalSys/E1020337',
        'password' => 'U9q0:83S*&QyKl1eo7y)',
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
        'templates_dir' => 'templates/certificate/',
        'download_enabled' => true,
        'print_enabled' => true,
        'share_enabled' => false, // Future feature
        'types' => array(
            'tif' => 'Təhsilin İnkişafı Fondu',
            'youth' => 'Gənc qızların təhsilinə dəstək',
            'sustainable' => 'Qarabağ Təqaüd Proqramı'
        )
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
    
    // Security settings - PRODUCTION SECURITY
    'security' => array(
        'ssl_verify' => true, // SSL aktivləşdirildi
        'nonce_actions' => array(
        'form_submission' => 'tif_donation_submit_action',
        'donation_details' => 'tif_donation_details_action',
        'transaction_details' => 'tif_transaction_details_action',
        'certificate_details' => 'tif_certificate_details_action', // YENİ
        'export_donations' => 'tif_export_donations_action',
        'bulk_actions' => 'tif_bulk_actions'
        ),
    ),
    
    // Debug settings - PRODUCTION DEBUG DISABLED
    'debug' => array(
        'log_api_requests' => true, // Log-lar aktiv
        'log_certificate' => true, // Certificate log-ları
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