<?php
/**
 * Carbon Fields Setup
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

use Carbon_Fields\Container;
use Carbon_Fields\Field;

// Register fields
add_action('carbon_fields_register_fields', 'aac_register_carbon_fields');
function aac_register_carbon_fields() {
    Container::make('theme_options', __('Apartment Availability', 'apartment-availability-calendar'))
        ->set_page_parent('options-general.php') // This puts it under Settings
        ->add_tab(__('General Settings', 'apartment-availability-calendar'), array(
            Field::make('text', 'aac_google_api_key', __('Google API Key', 'apartment-availability-calendar'))
                ->set_help_text(__('Enter your Google API Key', 'apartment-availability-calendar')),
            Field::make('text', 'aac_google_client_id', __('Google Client ID', 'apartment-availability-calendar'))
                ->set_help_text(__('Enter your Google Client ID', 'apartment-availability-calendar')),
            Field::make('text', 'aac_google_client_secret', __('Google Client Secret', 'apartment-availability-calendar'))
                ->set_help_text(__('Enter your Google Client Secret', 'apartment-availability-calendar')),
            // In your fields setup, make sure the Google auth button looks like this:
            Field::make('html', 'aac_google_auth')
                ->set_html('<div class="aac-google-auth">
                    <h3>' . __('Google Authentication', 'apartment-availability-calendar') . '</h3>
                    <p>' . __('You need to authenticate with Google to access your calendars.', 'apartment-availability-calendar') . '</p>
                    <a href="' . admin_url('admin-ajax.php?action=aac_google_auth') . '" class="button button-primary">' . __('Authenticate with Google', 'apartment-availability-calendar') . '</a>
                </div>'),
        ))
        ->add_tab(__('Apartment 1', 'apartment-availability-calendar'), array(
            Field::make('text', 'aac_apartment1_name', __('Apartment Name', 'apartment-availability-calendar'))
                ->set_default_value(__('Apartment 1', 'apartment-availability-calendar')),
            Field::make('color', 'aac_apartment1_color', __('Calendar Color', 'apartment-availability-calendar'))
                ->set_default_value('#4285F4'),
            Field::make('text', 'aac_apartment1_calendar_id', __('Google Calendar ID', 'apartment-availability-calendar'))
                ->set_help_text(__('Enter the Google Calendar ID for this apartment', 'apartment-availability-calendar')),
        ))
        ->add_tab(__('Apartment 2', 'apartment-availability-calendar'), array(
            Field::make('text', 'aac_apartment2_name', __('Apartment Name', 'apartment-availability-calendar'))
                ->set_default_value(__('Apartment 2', 'apartment-availability-calendar')),
            Field::make('color', 'aac_apartment2_color', __('Calendar Color', 'apartment-availability-calendar'))
                ->set_default_value('#EA4335'),
            Field::make('text', 'aac_apartment2_calendar_id', __('Google Calendar ID', 'apartment-availability-calendar'))
                ->set_help_text(__('Enter the Google Calendar ID for this apartment', 'apartment-availability-calendar')),
        ))
        ->add_tab(__('Display Settings', 'apartment-availability-calendar'), array(
            Field::make('select', 'aac_date_format', __('Date Format', 'apartment-availability-calendar'))
                ->set_options(array(
                    'dd/mm/yyyy' => __('DD/MM/YYYY', 'apartment-availability-calendar'),
                    'mm/dd/yyyy' => __('MM/DD/YYYY', 'apartment-availability-calendar'),
                    'yyyy-mm-dd' => __('YYYY-MM-DD', 'apartment-availability-calendar'),
                ))
                ->set_default_value('dd/mm/yyyy'),
            Field::make('select', 'aac_calendar_view', __('Calendar View', 'apartment-availability-calendar'))
                ->set_options(array(
                    'month' => __('Month', 'apartment-availability-calendar'),
                    'year' => __('Year', 'apartment-availability-calendar'),
                ))
                ->set_default_value('month'),
            Field::make('checkbox', 'aac_show_legend', __('Show Legend', 'apartment-availability-calendar'))
                ->set_default_value(true),
        ));
}

// Add AJAX handler for testing Google connection
add_action('wp_ajax_aac_test_google_connection', 'aac_test_google_connection');
function aac_test_google_connection() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aac_test_google_connection')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }
    
    // Check if Carbon Fields is loaded
    if (!function_exists('carbon_get_theme_option')) {
        wp_send_json_error(array('message' => 'Carbon Fields not loaded.'));
    }
    
    // Get API credentials
    $api_key = carbon_get_theme_option('aac_google_api_key');
    $client_id = carbon_get_theme_option('aac_google_client_id');
    $client_secret = carbon_get_theme_option('aac_google_client_secret');
    
    // Check if credentials are set
    if (empty($api_key) || empty($client_id) || empty($client_secret)) {
        wp_send_json_error(array('message' => 'API credentials not fully configured. Please enter API Key, Client ID, and Client Secret.'));
    }
    
    // Test connection to Google Calendar API
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
        
        // Test a simple API call
        $service = new Google_Service_Calendar($client);
        
        // If we have an access token, try to use it
        $access_token = get_option('aac_google_access_token');
        if (!empty($access_token)) {
            $client->setAccessToken($access_token);
            
            // If token is expired, try to refresh it
            if ($client->isAccessTokenExpired()) {
                $refresh_token = get_option('aac_google_refresh_token');
                if (!empty($refresh_token)) {
                    try {
                        $client->refreshToken($refresh_token);
                        $new_access_token = $client->getAccessToken();
                        update_option('aac_google_access_token', $new_access_token);
                        wp_send_json_success(array('message' => 'Successfully refreshed access token.'));
                    } catch (Exception $e) {
                        wp_send_json_error(array('message' => 'Failed to refresh token: ' . $e->getMessage()));
                    }
                } else {
                    wp_send_json_error(array('message' => 'Access token expired and no refresh token available. Please re-authenticate.'));
                }
            } else {
                wp_send_json_success(array('message' => 'Connection successful! Access token is valid.'));
            }
        } else {
            // Generate auth URL for user to authenticate
            $auth_url = $client->createAuthUrl();
            wp_send_json_error(array(
                'message' => 'No access token found. Please authenticate with Google.',
                'auth_url' => $auth_url
            ));
        }
    } catch (Exception $e) {
        wp_send_json_error(array('message' => 'Error connecting to Google Calendar API: ' . $e->getMessage()));
    }
}

// Add AJAX handler for Google authentication
// Remove the duplicate aac_google_auth function
// Remove the duplicate aac_google_oauth_callback function if it exists

// Keep the HTML button that points to the AJAX action
add_action('wp_ajax_aac_google_auth', 'aac_google_auth');
function aac_google_auth() {
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
        
        // Generate auth URL and redirect
        $auth_url = $client->createAuthUrl();
        wp_redirect($auth_url);
        exit;
    } catch (Exception $e) {
        wp_die('Error initializing Google Client: ' . $e->getMessage());
    }
}

// Add OAuth callback handler
add_action('wp_ajax_aac_google_oauth_callback', 'aac_google_oauth_callback');
function aac_google_oauth_callback() {
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

// Add success message after authentication
add_action('admin_notices', 'aac_auth_success_notice');
function aac_auth_success_notice() {
    if (isset($_GET['page']) && $_GET['page'] === 'crb_carbon_fields_container_apartment_availability' && isset($_GET['auth_success'])) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Successfully authenticated with Google Calendar!', 'apartment-availability-calendar') . '</p></div>';
    }
}

// Add a notice to help users find the settings page
add_action('admin_notices', 'aac_admin_notices');
function aac_admin_notices() {
    // Only show this notice on the plugins page
    $screen = get_current_screen();
    if (!$screen || $screen->id !== 'plugins') {
        return;
    }
    
    // Check if the function exists before using it
    if (!function_exists('carbon_get_theme_option')) {
        // Carbon Fields not loaded yet, just show a basic notice
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p>' . sprintf(
            __('Thank you for installing Apartment Availability Calendar! Please <a href="%s">configure the plugin settings</a> to get started.', 'apartment-availability-calendar'),
            admin_url('options-general.php?page=crb_carbon_fields_container_apartment_availability')
        ) . '</p>';
        echo '</div>';
        return;
    }
    
    // Check if the user has already configured the plugin
    $api_key = carbon_get_theme_option('aac_google_api_key');
    if (!empty($api_key)) {
        return;
    }
    
    echo '<div class="notice notice-info is-dismissible">';
    echo '<p>' . sprintf(
        __('Thank you for installing Apartment Availability Calendar! Please <a href="%s">configure the plugin settings</a> to get started.', 'apartment-availability-calendar'),
        admin_url('options-general.php?page=crb_carbon_fields_container_apartment_availability')
    ) . '</p>';
    echo '</div>';
}