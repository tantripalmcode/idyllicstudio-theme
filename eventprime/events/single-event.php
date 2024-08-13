<?php
/**
 * View: Single Event
 */

defined( 'ABSPATH' ) || exit;

if( isset($args->event->em_id) && post_password_required( $args->event->em_id ) ){
    // if events are password protected
    echo get_the_password_form();
} else { ?>

    <?php if( ! empty( $args ) && ! empty( $args->post ) ) { ?>

        <?php
        $post_id             = $args->post->ID;
        $post_thumbnail_id   = get_post_thumbnail_id( $args->post );
        $event_gallery       = ( ! empty( $args->event->em_gallery_image_ids ) ? $args->event->em_gallery_image_ids: '' );
        $event_gallery       = $event_gallery ? explode( ",", $event_gallery ) : array();

        if( $post_thumbnail_id ) array_push( $event_gallery, $post_thumbnail_id );

        $event_gallery_count = count( $event_gallery ) > 5 ? 5 : count( $event_gallery );
        ?>

        <!-- Gallery Hero -->
        <?php if( $event_gallery ) get_template_part( 'template-parts/event/gallery/' . $event_gallery_count, 'photo', [ 'galleries' => $event_gallery ] ); ?>

    <?php } ?>

    <!-- Title Section -->
    <div class="row" id="event-headline">
        <div class="col-md-8 mb-4 mb-md-0">
            <div class="is-event-headline mb-5">

                <?php if( ! empty( $args->event->em_event_type ) && ! empty( $args->event->event_type_details ) ) { ?>
                    <div class="is-event-headline__subtitle mb-2">
                        <span><?php echo esc_html( $args->event->event_type_details->name ); ?></span>
                    </div>
                <?php } ?>

                <h2 class="pc-simplistic-headline mb-2" id="event-heading"><?php echo esc_html( wp_strip_all_tags( $args->post->post_title ) ); ?></h2>
                
                <?php 
                $event_headline = get_field('event_headline', $args->post->ID);
                $event_headline = !empty($event_headline) ? $event_headline : esc_html(wp_strip_all_tags($args->post->post_title));
                ?>
                <p><?php echo esc_html($event_headline); ?></p>

            </div>
            <div class="is-event-info">
                <div class="d-flex gap-md-4 gap-2 mb-2 flex-column flex-md-row">

                    <?php
                    $max_capacity = get_field( 'pc_maximum_capacity' );
                    if( $max_capacity ) { ?>
                    
                        <div class="is-event-info__capacity">
                            <svg width="25" height="26" viewBox="0 0 25 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12.5002 25.3311C19.3263 25.3311 25.0005 19.6693 25.0005 12.8311C25.0005 6.00508 19.314 0.331055 12.4879 0.331055C5.64956 0.331055 0 6.00508 0 12.8311C0 19.6693 5.66184 25.3311 12.5002 25.3311ZM12.5002 23.2477C6.71577 23.2477 2.09556 18.6154 2.09556 12.8311C2.09556 7.05899 6.70353 2.4144 12.4879 2.4144C18.2601 2.4144 22.9047 7.05899 22.9171 12.8311C22.9294 18.6154 18.2724 23.2477 12.5002 23.2477ZM12.5002 12.1448C14.2772 12.157 15.6988 10.6374 15.6988 8.6399C15.6988 6.77716 14.2772 5.22077 12.5002 5.22077C10.7232 5.22077 9.30159 6.77716 9.30159 8.6399C9.30159 10.6374 10.7232 12.1325 12.5002 12.1448ZM6.78929 19.0933H18.1988C18.689 19.0933 18.9341 18.7624 18.9341 18.3212C18.9341 16.9487 16.8753 13.4071 12.5002 13.4071C8.1251 13.4071 6.06624 16.9487 6.06624 18.3212C6.06624 18.7624 6.31137 19.0933 6.78929 19.0933Z" fill="#C19A6B"/>
                            </svg>
                            <span>Maximal <?php echo esc_html( $max_capacity ) ?> Teilnehmer</span>
                        </div>
                    <?php } ?>

                    <?php 
                    $monday_data = get_field( 'pc_dienstag' );
                    if( $monday_data && isset( $monday_data['interval'] ) && $monday_data['interval'] ) {
                    ?>
                        <div class="is-event-info__time">
                            <svg width="25" height="26" viewBox="0 0 25 26" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M24.9991 12.8313C24.9991 19.7345 19.4028 25.3307 12.4996 25.3307C5.59625 25.3307 0 19.7345 0 12.8313C0 5.92817 5.59625 0.332031 12.4996 0.332031C19.4028 0.332031 24.9991 5.92817 24.9991 12.8313ZM2.28041 12.8313C2.28041 18.4751 6.85568 23.0503 12.4996 23.0503C18.1435 23.0503 22.7188 18.4751 22.7188 12.8313C22.7188 7.18758 18.1435 2.6124 12.4996 2.6124C6.85568 2.6124 2.28041 7.18758 2.28041 12.8313Z" fill="#C19A6B"/>
                                <path d="M12.4996 4.87695C11.872 4.87695 11.3633 5.38569 11.3633 6.01326V13.3614C11.3633 13.3614 11.3633 13.6576 11.5073 13.8804C11.6036 14.0694 11.7538 14.2336 11.9512 14.3476L17.2008 17.3784C17.7443 17.6922 18.4393 17.5059 18.753 16.9624C19.0668 16.4189 18.8806 15.724 18.3371 15.4102L13.6359 12.6961V6.01326C13.6359 5.3857 13.1272 4.87695 12.4996 4.87695Z" fill="#C19A6B"/>
                            </svg>

                            <span><?php echo $monday_data['interval']; ?> Stunden</span>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
        <div class="col-md-4 d-flex flex-column align-items-md-end align-items-start align-self-center">
            
            <?php
            $price_discount = get_field('price_discount');
            if (!empty($price_discount)) {
            ?>
            <div class="is-event-discount d-flex align-items-center gap-2">
                <svg width="14" height="15" viewBox="0 0 14 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.6709 6.54587L12.6066 5.48028C12.4246 5.29801 12.2775 4.94047 12.2775 4.68809V3.17382C12.2775 2.55689 11.7734 2.05214 11.1573 2.05214H9.6519C9.39983 2.05214 9.04274 1.90492 8.8607 1.72264L7.79643 0.657044C7.36233 0.222392 6.64815 0.222392 6.21404 0.657044L5.13578 1.72264C4.96074 1.90492 4.60365 2.05214 4.34458 2.05214H2.83921C2.22305 2.05214 1.71893 2.55689 1.71893 3.17382V4.68108C1.71893 4.93346 1.57189 5.291 1.38985 5.47327L0.325581 6.53886C-0.108527 6.97352 -0.108527 7.68859 0.325581 8.12324L1.38985 9.18884C1.57189 9.37111 1.71893 9.72865 1.71893 9.98103V11.4883C1.71893 12.1052 2.22305 12.61 2.83921 12.61H4.34458C4.59664 12.61 4.95373 12.7572 5.13578 12.9395L6.20004 14.0051C6.63414 14.4397 7.34832 14.4397 7.78243 14.0051L8.8467 12.9395C9.02874 12.7572 9.38583 12.61 9.63789 12.61H11.1433C11.7594 12.61 12.2635 12.1052 12.2635 11.4883V9.98103C12.2635 9.72865 12.4106 9.37111 12.5926 9.18884L13.6569 8.12324C14.112 7.6956 14.112 6.98053 13.6709 6.54587ZM4.19755 5.23491C4.19755 4.84933 4.51262 4.53386 4.89772 4.53386C5.28282 4.53386 5.59789 4.84933 5.59789 5.23491C5.59789 5.62049 5.28982 5.93597 4.89772 5.93597C4.51262 5.93597 4.19755 5.62049 4.19755 5.23491ZM5.26881 9.81278C5.16379 9.91793 5.03075 9.96701 4.89772 9.96701C4.76469 9.96701 4.63165 9.91793 4.52663 9.81278C4.32358 9.60947 4.32358 9.27297 4.52663 9.06966L8.72767 4.86336C8.93072 4.66005 9.2668 4.66005 9.46985 4.86336C9.6729 5.06666 9.6729 5.40317 9.46985 5.60647L5.26881 9.81278ZM9.09876 10.1423C8.70666 10.1423 8.39158 9.8268 8.39158 9.44122C8.39158 9.05564 8.70666 8.74017 9.09176 8.74017C9.47685 8.74017 9.79193 9.05564 9.79193 9.44122C9.79193 9.8268 9.48385 10.1423 9.09876 10.1423Z" fill="#0F0F0B"/>
                </svg>
                <span><?php echo esc_html($price_discount); ?></span>
            </div>
            <?php } ?>

            <?php 
            $price_group = get_field('price-group', $args->post->ID);
            $price = $price_group['price'];
            $price_prefix = $price_group['prefix'];
            $price_suffix = $price_group['suffix'];
            $additional_note = $price_group['additional_note'];
            ?>
            <div class="is-event-price">
                <h4 class="pc-simplistic-headline mb-3">
                    <?php if (!empty($price_prefix)): ?>
                        <span class="is-event-price__prefix"><?php echo esc_html($price_prefix); ?></span>
                    <?php endif; ?>
                    
                    <?php if (!empty($price)): ?>
                        €<?php echo esc_html($price); ?>
                    <?php endif; ?>
            
                    <?php if (!empty($price_suffix)): ?>
                        <span class="is-event-price__suffix"><?php echo esc_html($price_suffix); ?></span>
                    <?php endif; ?>
                </h4>
                <?php if (!empty($additional_note)): ?>
                    <span class="is-event-price__note"><?php echo esc_html($additional_note); ?></span>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Horizontal Line -->
    <hr class="is-event-horizontal-line">

    <!-- Content Section -->
    <div class="row" id="event-content">
        <div class="col-md-6">
            <?php if( $args->event->description ) { ?>
                <div class="pc-event-description">
                    <h4 class="pc-simplistic-headline">Beschreibung</h4>
                    <?php echo wp_kses_post( $args->event->description ); ?>
                </div>
            <?php } ?>
        </div>
        <div class="col-md-6">
            <!-- Enthaltene Leistungen -->
            <?php $enthaltene_leistungen = get_field( 'enthaltene_leistungen', $post_id ); ?>

            <?php if( $enthaltene_leistungen ) { ?>
                <div class="pc-event-included-service">
                    <h4 class="pc-simplistic-headline">Enthaltene Leistungen</h4>
                    <?php echo wp_kses_post( $enthaltene_leistungen ); ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <!-- Booking Form -->
    <div class="row mb-5" id="event-booking-form">
        <div class="col-md-12">
            <div class="pc-booking-form mx-auto">
                <?php echo do_shortcode('[contact-form-7 id="1f01778" title="Booking Form"]') ?>
            </div>
        </div>
    </div>

    <!-- Horizontal Line -->
    <hr class="is-event-horizontal-line">

    <!-- Event Section -->
    <?php
    echo do_shortcode('[get_section_events]');
    ?>

    <!-- Course Section -->
    <div class="row position-relative" id="event-other-course">
        <h2 class="pc-simplistic-headline">Unsere anderen Kurse</h2>
        <?php
        echo do_shortcode('[pc_event_slider]');
        ?>
    </div>
<?php }