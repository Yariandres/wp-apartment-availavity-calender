<?php
/**
 * Shortcode functionality
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register the shortcode
 */
function aac_register_shortcodes() {
    add_shortcode('apartment_availability', 'aac_availability_shortcode');
}
add_action('init', 'aac_register_shortcodes');

/**
 * Get calendar data from Google Calendar API
 * 
 * @return array Calendar data
 */
function aac_get_calendar_data() {
    // Make sure the Google Calendar API class is available
    if (!class_exists('AAC_Google_Calendar_API')) {
        return aac_get_default_calendar_data();
    }
    
    // Get data from Google Calendar
    try {
        $api = new AAC_Google_Calendar_API();
        return $api->get_booked_dates();
    } catch (Exception $e) {
        return aac_get_default_calendar_data();
    }
}

/**
 * Get default calendar data
 */
function aac_get_default_calendar_data() {
    return array(
        'apartment1' => array(
            'name' => 'Apartment 1',
            'color' => '#4285F4',
            'events' => array(),
        ),
        'apartment2' => array(
            'name' => 'Apartment 223',
            'color' => '#EA4335',
            'events' => array(),
        ),
        'settings' => array(
            'dateFormat' => 'dd/mm/yyyy',
            'calendarView' => 'month',
            'showLegend' => true,
            'months' => 1,
        ),
    );
}

/**
 * Shortcode callback
 */
function aac_availability_shortcode($atts, $content = null, $tag = '') {
    // Parse attributes
    $atts = shortcode_atts(array(
        'months' => 1,
    ), $atts, $tag);
    
    // Get calendar data
    $calendar_data = aac_get_calendar_data();
    
    // Add months attribute to settings
    $calendar_data['settings']['months'] = intval($atts['months']);
    
    // Enqueue scripts and styles
    wp_enqueue_style('fullcalendar');
    wp_enqueue_style('aac-styles');
    wp_enqueue_script('fullcalendar');
    wp_enqueue_script('aac-calendar');
    
    // Localize script with calendar data
    wp_localize_script('aac-calendar', 'aacCalendarData', $calendar_data);
    
    // Return HTML
    $html = '<div class="aac-calendar-container">';
    $html .= '<div id="aac-calendar"></div>';
    $html .= '</div>';
    
    return $html;
}