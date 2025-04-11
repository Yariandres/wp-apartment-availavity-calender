<?php
/**
 * Template Name: Apartment Calendar Test
 */

get_header();
?>

<div id="primary" class="content-area">
    <main id="main" class="site-main">
        <div class="entry-content">
            <h1>Apartment Availability Calendar Test</h1>
            
            <?php echo do_shortcode('[apartment_availability]'); ?>
            
            <div class="debug-info">
                <h3>Debug Information</h3>
                <p>Shortcode: [apartment_availability]</p>
                <p>Plugin Version: <?php echo AAC_PLUGIN_VERSION; ?></p>
                <p>Carbon Fields Loaded: <?php echo function_exists('carbon_get_theme_option') ? 'Yes' : 'No'; ?></p>
            </div>
        </div>
    </main>
</div>

<?php
get_footer();