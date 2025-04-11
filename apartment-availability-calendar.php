<?php
/**
 * Plugin Name: Apartment Availability Calendar
 * Description: Displays apartment availability from Google Calendar
 * Version: 1.0.0
 * Author: Yari Herrera
 * Author URI: https://www.linkedin.com/in/yari-herrera-9677a9160/
 * Text Domain: apartment-availability-calendar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AAC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AAC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AAC_PLUGIN_VERSION', '1.0.0');

// Load Composer autoloader
if (file_exists(AAC_PLUGIN_DIR . 'vendor/autoload.php')) {
    require_once AAC_PLUGIN_DIR . 'vendor/autoload.php';
}

// Initialize Carbon Fields
add_action('after_setup_theme', 'aac_load_carbon_fields');
function aac_load_carbon_fields() {
    \Carbon_Fields\Carbon_Fields::boot();
}

// Include required files
require_once AAC_PLUGIN_DIR . 'includes/carbon-fields-setup.php';
require_once AAC_PLUGIN_DIR . 'includes/google-calendar-api.php';
require_once AAC_PLUGIN_DIR . 'includes/shortcode.php';
require_once AAC_PLUGIN_DIR . 'includes/debug.php';
require_once AAC_PLUGIN_DIR . 'includes/ajax-handlers.php'; // Uncomment this line for OAuth handlers

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'aac_enqueue_scripts');
function aac_enqueue_scripts() {
    // Register FullCalendar
    wp_register_style('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css', array(), '5.10.1');
    wp_register_script('fullcalendar', 'https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js', array('jquery'), '5.10.1', true);
    
    // Register plugin styles and scripts
    wp_register_style('aac-styles', AAC_PLUGIN_URL . 'assets/css/styles.css', array('fullcalendar'), AAC_PLUGIN_VERSION);
    wp_register_script('aac-calendar', AAC_PLUGIN_URL . 'assets/js/calendar.js', array('jquery', 'fullcalendar'), AAC_PLUGIN_VERSION, true);
}

// Add a direct link to settings in the plugins page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'aac_add_plugin_action_links');
function aac_add_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=crb_carbon_fields_container_apartment_availability') . '">' . __('Settings', 'apartment-availability-calendar') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}