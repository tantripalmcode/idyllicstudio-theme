<?php
/**
 * Palm Code Child functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Palm_Code_Child
 */

define("PC_CHILD_VERSION", time());
// define("PC_CHILD_VERSION", "1.1.5");
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
define('PC_MAX_CAPACITY_PER_TIME_SLOT', 20);

// Custom Define
define('PALMCODE_HEADER_CTA_LENGTH', 3);

// Carbon Fields
require_once get_stylesheet_directory() . '/vendor/autoload.php';
add_action('after_setup_theme', function () {
    \Carbon_Fields\Carbon_Fields::boot();
});

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

/**
 * Load Admin Column
 */
if( is_admin() ){
    require_once PC_CHILD_INC_PATH . '/admin-column.php';
}

/**
 * Load Event Availability Fields
 */
require_once PC_CHILD_VENDOR_PATH . '/carbon_fields/event-availability-fields.php';

/**
 * Load Google Calendar Settings
 */
require_once PC_CHILD_VENDOR_PATH . '/woocommerce/google-calendar-settings.php';

/**
 * Load WooCommerce Product Booking
 */
require_once PC_CHILD_VENDOR_PATH . '/woocommerce/product-booking.php';


/**
 * Modify Event Day Language
 */
if ( !function_exists( 'modify_event_day_language' ) ) {
    function modify_event_day_language() {
        // Only run on frontend, not in admin area or during AJAX
        if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }
        
        // Start output buffering only on frontend
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
    
    // Use template_redirect hook which only fires on frontend
    add_action('template_redirect', 'modify_event_day_language', 1);
}

/**
 * Fix save button being disabled in admin
 * This ensures the save/publish button works properly by preventing output buffering issues
 */
if ( !function_exists( 'pc_fix_admin_save_button' ) ) {
    function pc_fix_admin_save_button() {
        // Only run in admin area on post edit screens
        if ( !is_admin() ) {
            return;
        }
        
        // Get current screen
        $screen = get_current_screen();
        if ( !$screen || $screen->base !== 'post' ) {
            return;
        }
        
        // Add JavaScript to ensure save button works
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Ensure save button is enabled after page load
            setTimeout(function() {
                var $saveButton = $('#publish, #save-post');
                if ($saveButton.length && $saveButton.prop('disabled')) {
                    $saveButton.prop('disabled', false);
                }
            }, 100);
            
            // Re-enable button if something tries to disable it
            var observer = new MutationObserver(function(mutations) {
                var $saveButton = $('#publish, #save-post');
                if ($saveButton.length && $saveButton.prop('disabled')) {
                    $saveButton.prop('disabled', false);
                }
            });
            
            var targetNode = document.getElementById('submitdiv') || document.body;
            if (targetNode) {
                observer.observe(targetNode, {
                    attributes: true,
                    attributeFilter: ['disabled'],
                    childList: false,
                    subtree: true
                });
            }
        });
        </script>
        <?php
    }
    add_action('admin_footer', 'pc_fix_admin_save_button');
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

// Disable support for comments and trackbacks in post types
function disable_comments_post_types_support() {
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        if (post_type_supports($post_type, 'comments')) {
            remove_post_type_support($post_type, 'comments');
            remove_post_type_support($post_type, 'trackbacks');
        }
    }
}
add_action('admin_init', 'disable_comments_post_types_support');

// Close comments on the front-end
function disable_comments_status() {
    return false;
}
add_filter('comments_open', 'disable_comments_status', 20, 2);
add_filter('pings_open', 'disable_comments_status', 20, 2);

// Hide existing comments
function disable_comments_hide_existing_comments($comments) {
    return array();
}
add_filter('comments_array', 'disable_comments_hide_existing_comments', 10, 2);

// Remove comments page from admin menu
function disable_comments_admin_menu() {
    remove_menu_page('edit-comments.php');
}
add_action('admin_menu', 'disable_comments_admin_menu');

// Redirect any user trying to access comments page
function disable_comments_admin_menu_redirect() {
    global $pagenow;
    if ($pagenow === 'edit-comments.php') {
        wp_redirect(admin_url());
        exit;
    }
}
add_action('admin_init', 'disable_comments_admin_menu_redirect');

// Remove comments metabox from dashboard
function disable_comments_dashboard() {
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
}
add_action('admin_init', 'disable_comments_dashboard');

/**
 * Add Body Class
 */
if (!function_exists('pc_custom_body_classes')) {
    function pc_custom_body_classes($classes)
    {
        global $post;

        $show_sticky_banner = get_post_meta( $post->ID, 'show_sticky_banner', true );

        if( $show_sticky_banner ){
            $classes[] = 'pc-show-sticky-banner';
        }

        return $classes;
    }
    add_filter('body_class', 'pc_custom_body_classes');
}