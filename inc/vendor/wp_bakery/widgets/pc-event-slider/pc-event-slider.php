<?php
// Sicherheit, das A und O
if (!defined('ABSPATH')) {
    die('-1');
}

class PCEventSlider
{
    private $basename;

    public function __construct()
    {
        $this->basename = str_replace(".php", "", basename(__FILE__));

        add_shortcode('pc_event_slider', array($this, 'pc_register_shortcode'));

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
            'custom_widget_class' => '',
            'css' => '',
        ], $atts);

        extract($atts);

        $css_class = pc_sc_merge_css($css, $custom_widget_class);

        wp_enqueue_script('swiper');
        wp_enqueue_style('swiper');

        // Enqueue CSS
        wp_enqueue_style($this->basename, pc_this_dir_url(__FILE__) . $this->basename . '.css', array(), time());

        $uniqid = uniqid();

        ob_start();

        $args = array(
            'post_type' => 'em_event',
            'post_status' => 'publish',
            'posts_per_page' => 6,
            'numberposts' => -1,
        );

        if( is_singular( 'em_event' ) ){
            $args['post__not_in'] = array( get_the_ID() );
        }

        $posts = new \WP_Query( $args );

        if ( $posts->have_posts() ) { ?>

            <section id="pc-event-slider-<?php echo esc_attr( $uniqid ); ?>" class="pc-event-slider__wrapper <?php echo $css_class; ?>">
                <div class="swiper pc-event-slider">
                    <div class="swiper-wrapper">

                        <?php while ( $posts->have_posts() ) { $posts->the_post(); ?>

                            <?php
                            $post_id           = get_the_ID();
                            $post_title        = get_the_title();
                            $post_link         = esc_url( get_permalink() );  
                            $post_content      = get_the_content();
                            // $post_content      = substr( wp_strip_all_tags( $post_content ), 0, '115' ) . ' ...';
                            $post_content      = wp_trim_words( get_the_content(), 21 );
                            // $price          = pc_get_event_price( $post_id, true );
                            $price             = get_field('price-group')['price'];
                            $post_thumbnail_id = get_post_thumbnail_id( $post_id );
                            ?>

                            <a href="<?php echo $post_link; ?>" class="pc-event-slider__item swiper-slide">

                                <?php if( $post_thumbnail_id ) echo wp_get_attachment_image( $post_thumbnail_id, 'large', false, [ 'class' => 'pc-event-slider__item-image' ] ) ?>

                                <h2 class="pc-event-slider__item-title fw-bold"><?php echo $post_title; ?></h2>

                                <?php if( $post_content ) {
                                    echo sprintf( '<div class="pc-event-slider__item-desc">%s</div>', wp_kses_post( $post_content ) );
                                } ?>

                                <div class="pc-event-slider__item-footer d-flex justify-content-between align-items-center">
                                    <span class="pc-event-slider__item-button text-center">Buche jetzt</span>

                                    <?php if( $price ) {
                                        echo sprintf( '<span class="pc-event-slider__item-price">€%s pro Person</span>', esc_html( $price ) );
                                    } ?>
                                </div>

                            </a>

                        <?php } wp_reset_postdata(); ?>
                    </div>
                </div>

                <!-- Arrow -->
                <div class="pc-swiper-arrow">
                    <div class="swiper-button-next swiper-arrow"></div>
                    <div class="swiper-button-prev swiper-arrow"></div>
                </div>
            </section>

            <script>
                (function($) {
                    $(document).ready(function() {
                        const $widget = '#pc-event-slider-<?php echo esc_attr( $uniqid ); ?>';
                        const $carousel = $widget + ' .pc-event-slider';

                        let settings = {
                            slidesPerView: 1,
                            spaceBetween: 24,
                            slidesPerGroup: 1,
                            loop: false,
                            navigation: {
                                nextEl: $widget + " .swiper-button-next",
                                prevEl: $widget + " .swiper-button-prev",
                            },
                            breakpoints: {
                                650: {
                                    slidesPerView: 2,
                                    spaceBetween: 15,
                                },
                                1080: {
                                    slidesPerView: 3,
                                },
                                1200: {
                                    slidesPerView: 4,
                                },
                            },
                        };

                        const swiper = new Swiper($carousel, settings);
                    })
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
                "name" => __("Palm-Code Event Slider", "palmcode"),
                "base" => "pc_event_slider",
                "class" => "",
                "icon" => $icon,
                "category" => "Palm-Code Widgets",
                "content_element" => true,
                "holder" => "div",
                "params" => array(
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

new PCEventSlider();