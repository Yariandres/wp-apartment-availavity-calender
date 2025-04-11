<?php
/**
 * Google Calendar API Integration
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AAC_Google_Calendar_API {
    private $api_key;
    private $client_id;
    private $client_secret;
    private $apartment1_calendar_id;
    private $apartment2_calendar_id;
    private $access_token;
    private $settings_loaded = false;
    private $debug_mode = true; // Set to true to enable debug logging
    
    public function __construct() {
        // We'll load settings when needed
        $this->debug_log('Initializing Google Calendar API class');
    }
    
    /**
     * Load settings from Carbon Fields
     */
    private function load_settings() {
        // If settings are already loaded, return
        if ($this->settings_loaded) {
            return true;
        }
        
        // Make sure Carbon Fields is loaded
        if (!function_exists('carbon_get_theme_option')) {
            $this->debug_log('Carbon Fields not loaded yet');
            return false;
        }
        
        $this->api_key = carbon_get_theme_option('aac_google_api_key');
        $this->client_id = carbon_get_theme_option('aac_google_client_id');
        $this->client_secret = carbon_get_theme_option('aac_google_client_secret');
        $this->apartment1_calendar_id = carbon_get_theme_option('aac_apartment1_calendar_id');
        $this->apartment2_calendar_id = carbon_get_theme_option('aac_apartment2_calendar_id');
        $this->access_token = get_option('aac_google_access_token');
        
        $this->debug_log('Settings loaded', [
            'api_key_set' => !empty($this->api_key),
            'client_id_set' => !empty($this->client_id),
            'client_secret_set' => !empty($this->client_secret),
            'apartment1_calendar_id_set' => !empty($this->apartment1_calendar_id),
            'apartment2_calendar_id_set' => !empty($this->apartment2_calendar_id),
            'access_token_set' => !empty($this->access_token),
        ]);
        
        $this->settings_loaded = true;
        return true;
    }
    
    /**
     * Debug log function
     */
    private function debug_log($message, $data = null) {
        if (!$this->debug_mode) {
            return;
        }
        
        if (is_array($data) || is_object($data)) {
            error_log('Google Calendar API: ' . $message . ' - ' . print_r($data, true));
        } else {
            error_log('Google Calendar API: ' . $message . ($data !== null ? ' - ' . $data : ''));
        }
    }
    
    /**
     * Get booked dates from Google Calendar
     */
    public function get_booked_dates() {
        $this->debug_log('Getting booked dates');
        
        // Load settings if not already loaded
        if (!$this->load_settings()) {
            $this->debug_log('Failed to load settings, returning default data');
            return $this->get_default_data();
        }
        
        // Check if we have the necessary credentials
        if (empty($this->api_key) || empty($this->client_id) || empty($this->client_secret)) {
            $this->debug_log('Missing API credentials');
            return $this->get_default_data();
        }
        
        // Check if we have an access token
        if (empty($this->access_token)) {
            $this->debug_log('No access token available');
            return $this->get_default_data();
        }
        
        // Initialize Google Client
        try {
            $client = new Google_Client();
            $client->setApplicationName('Apartment Availability Calendar');
            $client->setDeveloperKey($this->api_key);
            $client->setClientId($this->client_id);
            $client->setClientSecret($this->client_secret);
            $client->setAccessToken($this->access_token);
            
            // Check if token is expired
            if ($client->isAccessTokenExpired()) {
                $this->debug_log('Access token expired, trying to refresh');
                $refresh_token = get_option('aac_google_refresh_token');
                
                if (!empty($refresh_token)) {
                    try {
                        $client->refreshToken($refresh_token);
                        $new_access_token = $client->getAccessToken();
                        update_option('aac_google_access_token', $new_access_token);
                        $this->access_token = $new_access_token;
                        $this->debug_log('Successfully refreshed access token');
                    } catch (Exception $e) {
                        $this->debug_log('Failed to refresh token: ' . $e->getMessage());
                        return $this->get_default_data();
                    }
                } else {
                    $this->debug_log('No refresh token available');
                    return $this->get_default_data();
                }
            }
            
            // Get calendar events
            $apartment1_events = $this->get_calendar_events($this->apartment1_calendar_id, $client);
            $apartment2_events = $this->get_calendar_events($this->apartment2_calendar_id, $client);
            
            $this->debug_log('Retrieved events', [
                'apartment1_events_count' => count($apartment1_events),
                'apartment2_events_count' => count($apartment2_events),
            ]);
            
            return array(
                'apartment1' => array(
                    'name' => carbon_get_theme_option('aac_apartment1_name'),
                    'color' => carbon_get_theme_option('aac_apartment1_color'),
                    'events' => $apartment1_events,
                ),
                'apartment2' => array(
                    'name' => carbon_get_theme_option('aac_apartment2_name'),
                    'color' => carbon_get_theme_option('aac_apartment2_color'),
                    'events' => $apartment2_events,
                ),
                'settings' => array(
                    'dateFormat' => carbon_get_theme_option('aac_date_format'),
                    'calendarView' => carbon_get_theme_option('aac_calendar_view'),
                    'showLegend' => carbon_get_theme_option('aac_show_legend'),
                ),
            );
        } catch (Exception $e) {
            $this->debug_log('Error: ' . $e->getMessage());
            return $this->get_default_data();
        }
    }
    
    /**
     * Get default data when settings can't be loaded
     */
    private function get_default_data() {
        return array(
            'apartment1' => array(
                'name' => 'Apartment 1',
                'color' => '#4285F4',
                'events' => array(),
            ),
            'apartment2' => array(
                'name' => 'Apartment 2',
                'color' => '#EA4335',
                'events' => array(),
            ),
            'settings' => array(
                'dateFormat' => 'dd/mm/yyyy',
                'calendarView' => 'month',
                'showLegend' => true,
            ),
        );
    }
    
    /**
     * Get events from a specific Google Calendar
     */
    private function get_calendar_events($calendar_id, $client) {
        if (empty($calendar_id)) {
            $this->debug_log('Calendar ID is empty');
            return array();
        }
        
        try {
            $service = new Google_Service_Calendar($client);
            $events = array();
            
            // Get events from now to 1 year in the future
            $optParams = array(
                'maxResults' => 500,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => date('c'),
                'timeMax' => date('c', strtotime('+1 year')),
            );
            
            $results = $service->events->listEvents($calendar_id, $optParams);
            $items = $results->getItems();
            
            if (empty($items)) {
                $this->debug_log('No events found for calendar: ' . $calendar_id);
                return array();
            }
            
            foreach ($items as $event) {
                $start = $event->getStart()->getDateTime();
                if (empty($start)) {
                    $start = $event->getStart()->getDate();
                }
                
                $end = $event->getEnd()->getDateTime();
                if (empty($end)) {
                    $end = $event->getEnd()->getDate();
                }
                
                // Instead of using the event title, just use "Occupied"
                $events[] = array(
                    'title' => 'Occupied',
                    'start' => $start,
                    'end' => $end,
                    'allDay' => empty($event->getStart()->getDateTime()),
                );
            }
            
            return $events;
        } catch (Exception $e) {
            $this->debug_log('Error getting calendar events: ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Public method to test Google Calendar connection
     */
    public function test_connection() {
        $this->debug_log('Testing Google Calendar connection');
        
        // Load settings
        if (!$this->load_settings()) {
            return [
                'success' => false,
                'message' => 'Failed to load settings. Carbon Fields may not be initialized yet.'
            ];
        }
        
        // Check if we have the necessary credentials
        if (empty($this->api_key) || empty($this->client_id) || empty($this->client_secret)) {
            return [
                'success' => false,
                'message' => 'Missing API credentials. Please configure API Key, Client ID, and Client Secret.'
            ];
        }
        
        try {
            // Initialize Google Client
            $client = new Google_Client();
            $client->setApplicationName('Apartment Availability Calendar');
            $client->setDeveloperKey($this->api_key);
            $client->setClientId($this->client_id);
            $client->setClientSecret($this->client_secret);
            
            // Test if we can initialize the client
            if (!$client) {
                return [
                    'success' => false,
                    'message' => 'Failed to initialize Google Client.'
                ];
            }
            
            // Check if we have an access token
            if (empty($this->access_token)) {
                // Generate auth URL
                $client->setRedirectUri(admin_url('admin-ajax.php?action=aac_google_oauth_callback'));
                $client->setScopes(['https://www.googleapis.com/auth/calendar.readonly']);
                $client->setAccessType('offline');
                $auth_url = $client->createAuthUrl();
                
                return [
                    'success' => false,
                    'message' => 'No access token available. Authentication required.',
                    'auth_url' => $auth_url
                ];
            }
            
            // Set the access token
            $client->setAccessToken($this->access_token);
            
            // Check if token is expired
            if ($client->isAccessTokenExpired()) {
                $refresh_token = get_option('aac_google_refresh_token');
                
                if (empty($refresh_token)) {
                    return [
                        'success' => false,
                        'message' => 'Access token expired and no refresh token available. Re-authentication required.'
                    ];
                }
                
                try {
                    // Try to refresh the token
                    $client->refreshToken($refresh_token);
                    $new_access_token = $client->getAccessToken();
                    update_option('aac_google_access_token', $new_access_token);
                    
                    return [
                        'success' => true,
                        'message' => 'Successfully refreshed access token.'
                    ];
                } catch (Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'Failed to refresh token: ' . $e->getMessage()
                    ];
                }
            }
            
            // Token is valid, try to make a simple API call
            $service = new Google_Service_Calendar($client);
            $calendarList = $service->calendarList->listCalendarList();
            
            return [
                'success' => true,
                'message' => 'Connection successful! Found ' . count($calendarList->getItems()) . ' calendars.'
            ];
            
        } catch (Exception $e) {
            $this->debug_log('Connection test error', $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
}