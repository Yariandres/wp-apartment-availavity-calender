<?php
/**
 * Debug functions
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Log debug information
 */
function aac_debug_log($message, $data = null) {
    if (WP_DEBUG) {
        if (is_array($data) || is_object($data)) {
            error_log('AAC Debug: ' . $message . ' - ' . print_r($data, true));
        } else {
            error_log('AAC Debug: ' . $message . ($data !== null ? ' - ' . $data : ''));
        }
    }
}

/**
 * Add debug info to the page
 */
function aac_add_debug_info() {
    if (!WP_DEBUG || !current_user_can('manage_options')) {
        return;
    }
    
    echo '<div style="background: #f8f8f8; border: 1px solid #ddd; padding: 15px; margin: 15px 0; font-family: monospace;">';
    echo '<h3>Apartment Availability Calendar Debug Info</h3>';
    
    // Check if Carbon Fields is loaded
    echo '<p>Carbon Fields loaded: ' . (function_exists('carbon_get_theme_option') ? 'Yes' : 'No') . '</p>';
    
    // Check if shortcode is registered
    global $shortcode_tags;
    echo '<p>Shortcode registered: ' . (isset($shortcode_tags['apartment_availability']) ? 'Yes' : 'No') . '</p>';
    
    // Check if scripts are registered
    echo '<p>FullCalendar script registered: ' . (wp_script_is('fullcalendar', 'registered') ? 'Yes' : 'No') . '</p>';
    echo '<p>Plugin script registered: ' . (wp_script_is('aac-calendar', 'registered') ? 'Yes' : 'No') . '</p>';
    
    echo '</div>';
}
add_action('wp_footer', 'aac_add_debug_info');