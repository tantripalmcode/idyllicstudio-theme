<?php
// Sicherheit, das A und O
if (!defined('ABSPATH')) {
    die('-1');
}

class PCImageFadeSlide
{
    private $basename;

    public function __construct()
    {
        $this->basename = str_replace(".php", "", basename(__FILE__));

        add_shortcode('pc_image_fade_slide', array($this, 'pc_register_shortcode'));

        if (function_exists('vc_map')) {
            add_action('vc_before_init', array($this, 'pc_vc_map_configuration'));
        }
    }

    /**
     * Shortcode Functions
     */
    public function pc_register_shortcode($atts, $content)
    {
        $atts = shortcode_atts([
            'all_images' => '',

            'autoplay' => '',
            'autoplay_speed' => 2500,

            'custom_widget_class' => '',
            'css' => '',
        ], $atts);

        extract($atts);

        $css_class = pc_sc_merge_css($css, $custom_widget_class);

        wp_enqueue_script( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.js', array( 'jquery' ), '1.9.0' );
        wp_enqueue_style( 'slick-theme', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick-theme.min.css', array(), '1.9.0', 'all' );
        wp_enqueue_style( 'slick', 'https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.9.0/slick.min.css', array(), '1.9.0', 'all' );

        // Enqueue CSS
        wp_enqueue_style($this->basename, pc_this_dir_url(__FILE__) . $this->basename . '.css', array(), '1.0.6');

        $uniqid = uniqid();

        ob_start();

        if ($all_images) { ?>

            <section id="pc-image-fade-slide-<?php echo esc_attr( $uniqid ); ?>" class="pc-image-fade-slide__wrapper position-relative <?php echo $css_class; ?>">

                <div class="pc-image-fade-slide">
                    <?php
                    $images = explode(",", $all_images);
                    if( $images ){
                        foreach ( $images as $index => $image ) {
                            $image_html = wp_get_attachment_image($image, 'large', false, array( 'class' => 'pc-image-fade-slide__image' )); ?>

                            <div class="pc-image-fade-slide__item">
                                <?php echo $image_html; ?>
                            </div>
                        <?php }
                    }
                    ?>
                </div>

            </section>

            <script>
                (function ($) {
                    $(document).ready(function () {
                        const $widget = "#pc-image-fade-slide-<?php echo $uniqid; ?>";
                        const $carousel = $widget + " .pc-image-fade-slide";

                        $($carousel).slick({
                            draggable: false,
                            touchMove: false,
                            swipe: false,
                            swipeToSlide: false,
                            autoplay: <?php echo $autoplay ? 'true' : 'false'; ?>,
                            autoplaySpeed: <?php echo (int) $autoplay_speed; ?>,
                            arrows: false,
                            speed: 0,
                            dots: false,
                            infinite: true,
                            slidesToShow: 1,
                            slidesToScroll: 1,
                            cssEase: "linear",
                        });
                    });
                })(jQuery);
            </script>

        <?php }

        return ob_get_clean();
    }

    /**
     * VC Map Configuration
     */
    public function pc_vc_map_configuration()
    {
        $icon = file_exists(pc_this_dir_path(__FILE__) . "/" . $this->basename . ".svg") ? pc_this_dir_url(__FILE__) . "" . $this->basename . ".svg" : PC_ICON_GLOBAL_WIDGET;

        vc_map(
            array(
                "name" => __("Palm-Code Image Fade Slide", "palmcode"),
                "base" => "pc_image_fade_slide",
                "class" => "",
                "icon" => $icon,
                "category" => "Palm-Code Widgets",
                "content_element" => true,
                "holder" => "div",
                "params" => array(
                    array(
                        "type" => "attach_images",
                        "heading" => __("Add Images", "palmcode"),
                        "param_name" => "all_images",
                        "value" => "",
                    ),

                    // Autoplay
                    array(
                        'type' => 'checkbox',
                        'heading' => __('Enable Auto Play?', 'palmcode'),
                        'param_name' => 'autoplay',
                        'value' => array(__('Yes', 'palmcode') => 'yes'),
                        'admin_label' => true,
                        'group' => 'Carousel Settings',
                    ),
                    array(
                        'type' => 'textfield',
                        'heading' => __("Autoplay Speed", "palmcode"),
                        'param_name' => 'autoplay_speed',
                        'group' => 'Carousel Settings',
                        'std' => 2500,
                        "description" => __("Please enter numbers only.", "palmcode"),
                        "dependency" => array(
                            "element" => "autoplay",
                            "value" => "yes",
                        ),
                        'edit_field_class' => 'vc_col-sm-3',
                    ),

                    array(
                        'type' => 'textfield',
                        'heading' => __('Custom Widget Class', 'palmcode'),
                        'param_name' => 'custom_widget_class',
                        'group' => 'Design Options',
                        'admin_label' => true,
                    ),
                    array(
                        'type' => 'css_editor',
                        'heading' => __('CSS', 'palmcode'),
                        'param_name' => 'css',
                        'group' => 'Design Options',
                    ),
                ),
            )
        );
    }
}

new PCImageFadeSlide();
