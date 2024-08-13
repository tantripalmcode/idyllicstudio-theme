<?php
if (!defined('ABSPATH')) wp_die('Direct access forbidden.');

// Shortcode section id=events
function get_section_events($atts) {
    $homepage_id = 9; 

    $homepage_content = get_post_field('post_content', $homepage_id);

    $shortcode_start_position = strpos($homepage_content, '[vc_section el_id="events"');

    if ($shortcode_start_position !== false) {
        $content_start_position = strpos($homepage_content, ']', $shortcode_start_position);
        
        if ($content_start_position !== false) {
            $content_start_position++; 
            $content_end_position = strpos($homepage_content, '[/vc_section]', $content_start_position);
            
            if ($content_end_position !== false) {
                $section_content = substr($homepage_content, $shortcode_start_position, $content_end_position + 13 - $shortcode_start_position);
                return do_shortcode($section_content);
            }
        }
    }

    return '';
}
add_shortcode('get_section_events', 'get_section_events');
