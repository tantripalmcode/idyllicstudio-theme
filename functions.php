<?php
/**
 * Palm Code Child functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Palm_Code_Child
 */

define("PC_CHILD_VERSION", time());
define('PC_CHILD_PATH', get_stylesheet_directory());
define('PC_CHILD_URL', get_stylesheet_directory_uri());
define('PC_CHILD_INC_PATH', PC_CHILD_PATH . '/inc');
define('PC_CHILD_VENDOR_PATH', PC_CHILD_PATH . '/inc/vendor');
define('PC_CHILD_VENDOR_URL', PC_CHILD_URL . '/inc/vendor');
define('PC_CHILD_WPBAKERY_PATH', PC_CHILD_VENDOR_PATH . '/wp_bakery');
define('PC_CHILD_WPBAKERY_URL', PC_CHILD_VENDOR_URL . '/wp_bakery');
define('PC_CHILD_WPBAKERY_SHORTCODES_PATH', PC_CHILD_WPBAKERY_PATH . '/widgets');
define('PC_CHILD_WPBAKERY_SHORTCODES_URL', PC_CHILD_WPBAKERY_URL . '/widgets');
define('PC_CHILD_ASSETS_URL', PC_CHILD_URL . '/assets');
define('PC_CHILD_ASSETS_PATH', PC_CHILD_PATH . '/assets');
define('PC_CHILD_ASSETS_CSS_URL', PC_CHILD_ASSETS_URL . '/css');
define('PC_CHILD_ASSETS_JS_URL', PC_CHILD_ASSETS_URL . '/js');
define('PC_CHILD_ASSETS_IMAGE_URL', PC_CHILD_ASSETS_URL . '/images');
define('PC_CHILD_ASSETS_FONT_URL', PC_CHILD_ASSETS_URL . '/fonts');
define('PC_CHILD_ASSETS_FONT_PATH', PC_CHILD_ASSETS_PATH . '/fonts');

// Custom Define
define('PALMCODE_HEADER_CTA_LENGTH', 3);


/**
 * Custom functions for this theme.
 */
require_once PC_CHILD_INC_PATH . '/enqueue.php';

/**
 * Load WP Bakery Shortcode
 */
require_once PC_CHILD_WPBAKERY_PATH . '/shortcodes.php';

/**
 * Custom Customizer
 */
require_once PC_CHILD_INC_PATH . '/customizer.php';

/**
 * Custom Shortcode
 */
require_once PC_CHILD_INC_PATH . '/shortcode.php';

/**
 * Custom Parent Data
 */
require_once PC_CHILD_INC_PATH . '/custom-parent-data.php';

/**
 * Load Ajax
 */
require_once PC_CHILD_INC_PATH . '/ajax.php';

/**
 * Load Event Prime Helpers
 */
require_once PC_CHILD_VENDOR_PATH . '/event_prime/event_prime.php';

/**
 * Load Contact Form 7 Helpers
 */
require_once PC_CHILD_VENDOR_PATH . '/contact_form_7/contact_form_7.php';

if( is_admin() ){
    require_once PC_CHILD_INC_PATH . '/admin-column.php';
}


/**
 * Modify Event Day Language
 */
if ( !function_exists( 'modify_event_day_language' ) ) {
    function modify_event_day_language() {
        // Start output buffering
        ob_start(function($buffer) {
            // Replace English weekday names with German weekday names
            $german_weekdays = array(
                'Monday'    => 'Montag',
                'Tuesday'   => 'Dienstag',
                'Wednesday' => 'Mittwoch',
                'Thursday'  => 'Donnerstag',
                'Friday'    => 'Freitag',
                'Saturday'  => 'Samstag',
                'Sunday'    => 'Sonntag'
            );
            return preg_replace_callback('/(\b(?:' . implode('|', array_keys($german_weekdays)) . ')\b)/', function($matches) use ($german_weekdays) {
                return $german_weekdays[$matches[0]];
            }, $buffer);
        });
    }
    
    add_filter('wp_loaded', 'modify_event_day_language');
}


/**
 * Get German day name
 */
function get_german_weekdays( $day ) {
    $german_weekdays = array(
        'monday'    => 'montag',
        'tuesday'   => 'dienstag',
        'wednesday' => 'mittwoch',
        'thursday'  => 'donnerstag',
        'friday'    => 'freitag',
        'saturday'  => 'samstag',
        'sunday'    => 'sonntag'
    );

    if( isset( $german_weekdays[$day] ) ) {
        return $german_weekdays[$day];
    } else {
        return '';
    }
}


/**
 * Get post by post title
 */
function pc_get_post_id_by_post_title( $post_type, $post_title ) {
    $post = get_posts(
        array(
            'post_type'   => $post_type,
            'title'       => $post_title,
            'post_status' => ['publish'],
            'numberposts' => 1,
        )
    );

    if( !$post ) return false;
    
    $post_id = $post[0]->ID;

    return $post_id;
}

// Function to hide plugin update notifications for specific plugins
function disable_plugin_updates( $value ) {
    // List of plugin directories to hide updates for
    $plugins_to_hide = array(
        'eventprime-event-calendar-management/event-prime.php',
    );

    if ( isset( $value ) && is_object( $value ) ) {
        foreach ( $plugins_to_hide as $plugin ) {
            if ( isset( $value->response[$plugin] ) ) {
                unset( $value->response[$plugin] );
            }
        }
    }

    return $value;
}

// Hook into the 'site_transient_update_plugins' filter
add_filter( 'site_transient_update_plugins', 'disable_plugin_updates' );