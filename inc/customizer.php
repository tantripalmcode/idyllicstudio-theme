<?php
if (!defined('ABSPATH')) wp_die('Direct access forbidden.');

// use pc_theme_customize;

/**
 * Add CTA Button to Header
 */
add_action('add_customizer_options_in_section', 'pc_add_custom_header_option', 10, 2);
function pc_add_custom_header_option($titleSection, $section)
{
    if ($titleSection === "Header") {
        for ($i = 1; $i <= PALMCODE_HEADER_CTA_LENGTH; $i++) {
            pc_theme_customize::add_customizer_seperator(__("Header CTA " . $i, "palmcode"), $section);
            pc_theme_customize::add_customizer_text_option(__("CTA $i Button Text", "palmcode"), "pc_header[cta-$i-button-text]", $section, "");
            pc_theme_customize::add_customizer_text_option(__("CTA $i Button Link", "palmcode"), "pc_header[cta-$i-button-link]", $section, "");
            pc_theme_customize::add_customizer_radio_option(__("CTA $i Button Target", "palmcode"), "pc_header[cta-$i-button-target]", ['_self' => 'Self', '_blank' => 'Blank'], $section);
            pc_theme_customize::add_customizer_text_option(__("CTA $i Button Class", "palmcode"), "pc_header[cta-$i-button-class]", $section, "");
        }
    }
}
