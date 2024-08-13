<?php
if (!defined('ABSPATH')) wp_die('Direct access forbidden.');

if (function_exists('vc_map')) {
    /**
     * WP Bakery Shortcode
     */
    $indir = scandir(PC_CHILD_WPBAKERY_SHORTCODES_PATH);

    foreach ($indir as $file) {
        $fileinfo = pathinfo(PC_CHILD_WPBAKERY_SHORTCODES_PATH . '/' . $file);

        if (is_dir(PC_CHILD_WPBAKERY_SHORTCODES_PATH . '/' . $file)) {
            if ($file == "." || $file == "..") {
                continue;
            }
            if (file_exists(PC_CHILD_WPBAKERY_SHORTCODES_PATH . "/$file/$file.php")) {
                require PC_CHILD_WPBAKERY_SHORTCODES_PATH . "/$file/$file.php";
            }
        } else {
            if (isset($fileinfo["extension"]) && $fileinfo["extension"] == 'php') {
                require PC_CHILD_WPBAKERY_SHORTCODES_PATH . '/' . $file;
            }
        }
    }
}
