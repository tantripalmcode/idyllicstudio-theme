<?php
if (!defined('ABSPATH')) wp_die('Direct access forbidden.');

/**
 * Enqueue scripts and styles.
 */
function child_palmcode_scripts()
{
    /**
     * CSS
     */
    wp_enqueue_style( 'jquery-timepicker', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css' );
    wp_enqueue_style( 'jquery-ui-css', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    wp_enqueue_style( 'palmcode-child-style', PC_CHILD_URL . '/style.css', array( 'palmcode-standardize', 'palmcode-header', 'palmcode-footer' ), PC_CHILD_VERSION, 'all' );

    /**
     * JS
     */
    wp_enqueue_script( 'jquery-ui-core' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_script( 'jquery-timepicker', '//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js', ['jquery'], '1.3.5', true );
    wp_enqueue_script( 'moment' );
    wp_enqueue_script( 'palmcode-child-script', PC_CHILD_ASSETS_JS_URL . '/script.js', array('palmcode-script'), PC_CHILD_VERSION, true );

    // Add ajax url
    $vars['ajaxurl'] = admin_url( 'admin-ajax.php' );
    $vars['strings'] = array(
        'error_400' => __( 'Unauthorized access', 'palmcode' ),
        'error_403' => __( 'Authorization error, please contact your webmaster', 'palmcode' ),
        'error_500' => __( 'Server error, please contact your server administrator.', 'palmcode' ),
    );

    wp_localize_script( 'palmcode-child-script', '_palmcode', $vars );

    /**
     * For Event, Booking and Checkout
     */
    if( is_singular( 'em_event' ) || is_page( 'booking' ) || is_page( 'booking-details' ) || is_page( 'events' ) ){
        wp_enqueue_script('swiper');
        wp_enqueue_script( 'palmcode-child-event', PC_CHILD_ASSETS_JS_URL . '/event.js', array('palmcode-script'), PC_CHILD_VERSION, true );
        
        wp_enqueue_style('swiper');
    }

    if( is_singular( 'em_event' ) || is_page( 'booking' ) || is_page( 'booking-details' ) || is_page( 'events' ) || is_page( 'user-profile' ) || is_page( 'register' ) || is_page( 'login' ) ){
        wp_enqueue_style( 'palmcode-child-event', PC_CHILD_ASSETS_CSS_URL . '/event.css', array( 'palmcode-standardize' ), PC_CHILD_VERSION, 'all' );
    }
}
add_action('wp_enqueue_scripts', 'child_palmcode_scripts');
