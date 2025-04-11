<?php
/**
 * AJAX Handlers for Apartment Availability Calendar
 * 
 * Handles Google OAuth authentication and token management
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for Google authentication
 */
add_action('wp_ajax_aac_google_auth', 'aac_ajax_google_auth');
function aac_ajax_google_auth() {
    // Check if Carbon Fields is loaded
    if (!function_exists('carbon_get_theme_option')) {
        wp_die('Carbon Fields not loaded yet. Please try again later.');
    }
    
    // Get API credentials
    $api_key = carbon_get_theme_option('aac_google_api_key');
    $client_id = carbon_get_theme_option('aac_google_client_id');
    $client_secret = carbon_get_theme_option('aac_google_client_secret');
    
    // Check if credentials are set
    if (empty($api_key) || empty($client_id) || empty($client_secret)) {
        wp_die('API credentials not fully configured. Please enter API Key, Client ID, and Client Secret.');
    }
    
    try {
        // Initialize Google Client
        $client = new Google_Client();
        $client->setApplicationName('Apartment Availability Calendar');
        $client->setDeveloperKey($api_key);
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri(admin_url('admin-ajax.php?action=aac_google_oauth_callback'));
        $client->setScopes(array('https://www.googleapis.com/auth/calendar.readonly'));
        $client->setAccessType('offline');
        $client->setPrompt('consent'); // Force to get refresh token
        $client->setIncludeGrantedScopes(true);
        
        // Generate auth URL and redirect
        $auth_url = $client->createAuthUrl();
        wp_redirect($auth_url);
        exit;
    } catch (Exception $e) {
        error_log('Google Calendar API: Error initializing Google Client - ' . $e->getMessage());
        wp_die('Error initializing Google Client: ' . $e->getMessage());
    }
}

/**
 * AJAX handler for Google OAuth callback
 */
add_action('wp_ajax_aac_google_oauth_callback', 'aac_ajax_google_oauth_callback');
function aac_ajax_google_oauth_callback() {
    // Check if we have an authorization code
    if (!isset($_GET['code'])) {
        wp_die('Authorization code not found.');
    }
    
    // Check if Carbon Fields is loaded
    if (!function_exists('carbon_get_theme_option')) {
        wp_die('Carbon Fields not loaded yet. Please try again later.');
    }
    
    // Get API credentials
    $api_key = carbon_get_theme_option('aac_google_api_key');
    $client_id = carbon_get_theme_option('aac_google_client_id');
    $client_secret = carbon_get_theme_option('aac_google_client_secret');
    
    try {
        // Initialize Google Client
        $client = new Google_Client();
        $client->setApplicationName('Apartment Availability Calendar');
        $client->setDeveloperKey($api_key);
        $client->setClientId($client_id);
        $client->setClientSecret($client_secret);
        $client->setRedirectUri(admin_url('admin-ajax.php?action=aac_google_oauth_callback'));
        $client->setScopes(array('https://www.googleapis.com/auth/calendar.readonly'));
        
        // Exchange authorization code for access token
        $access_token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        // Check for errors
        if (isset($access_token['error'])) {
            error_log('Google Calendar API: Error getting access token - ' . $access_token['error_description']);
            wp_die('Error getting access token: ' . $access_token['error_description']);
        }
        
        // Save access token and refresh token
        update_option('aac_google_access_token', $access_token);
        
        if (isset($access_token['refresh_token'])) {
            update_option('aac_google_refresh_token', $access_token['refresh_token']);
        }
        
        // Log success
        error_log('Google Calendar API: Successfully obtained access token');
        
        // Redirect back to settings page
        wp_redirect(admin_url('options-general.php?page=crb_carbon_fields_container_apartment_availability&auth_success=1'));
        exit;
    } catch (Exception $e) {
        error_log('Google Calendar API: Error during OAuth callback - ' . $e->getMessage());
        wp_die('Error during OAuth callback: ' . $e->getMessage());
    }
}

// Removed the duplicate aac_auth_success_notice function since it's already in carbon-fields-setup.php

/**
 * AJAX handler for manual token entry (useful for local development)
 */
add_action('wp_ajax_aac_save_manual_tokens', 'aac_ajax_save_manual_tokens');
function aac_ajax_save_manual_tokens() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aac_save_manual_tokens')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }
    
    // Get tokens
    $access_token = isset($_POST['access_token']) ? stripslashes($_POST['access_token']) : '';
    $refresh_token = isset($_POST['refresh_token']) ? sanitize_text_field($_POST['refresh_token']) : '';
    
    // Validate access token
    if (empty($access_token)) {
        wp_send_json_error(array('message' => 'Access token is required.'));
    }
    
    // Validate JSON format
    $access_token_decoded = json_decode($access_token, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        wp_send_json_error(array('message' => 'Invalid JSON format for access token: ' . json_last_error_msg()));
    }
    
    // Save tokens
    update_option('aac_google_access_token', $access_token_decoded);
    
    if (!empty($refresh_token)) {
        update_option('aac_google_refresh_token', $refresh_token);
    }
    
    // Log success
    error_log('Google Calendar API: Manually saved access token and refresh token');
    
    wp_send_json_success(array('message' => 'Tokens saved successfully.'));
}