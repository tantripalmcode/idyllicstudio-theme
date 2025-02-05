<?php
/**
 * Remove auto tag p from contact 7 plugin
 */
add_filter( 'wpcf7_autop_or_not', '__return_false' );

/**
 * Overwrite value of dropdown position contact form 7
 */
add_filter( 'wpcf7_form_tag', 'modify_cf7_field', 10, 2 );

/**
 * Remove action select num rules contact form 7
 */
remove_action( 'wpcf7_swv_create_schema', 'wpcf7_swv_add_select_enum_rules', 20 );

function modify_cf7_field( $tag, $unused ) {

    // Datum
    // if ( $tag['basetype'] === 'text' && $tag['name'] === 'datum' ) {
    //     $tag['values'] = array( 'default' => date( 'F d, Y' ) );
    // }

    // Kurse
    if ( $tag['basetype'] === 'select' && $tag['name'] === 'kurse' ) {
        $args = array(
            'post_type'      => 'em_event',
            'post_status'    => array('publish'),
            'posts_per_page' => -1,
            'number_posts'   => -1,
            'order'          => 'ASC',
            'orderby'        => 'post_title'
        );
    
        $posts = new \WP_Query( $args );

        $options = array();
        
        if( $posts->have_posts() ){
            while( $posts->have_posts() ){
                $posts->the_post();

                $post_title = get_the_title();

                $options[] = $post_title;

            } wp_reset_postdata();
        }

        $tag['raw_values'] = $options;
        $tag['values'] = $options;
    }

    // Zeit
    if ( $tag['basetype'] === 'select' && $tag['name'] === 'zeit' ) {
        $time_available = [
            '10:00 - 12:00',
            '12:00 - 14:00',
            '14:00 - 16:00',
            '16:00 - 18:00',
            '19:00 - 22:00',
        ];

        $options = array();
        foreach( $time_available as $time ) {
            $options[] = $time;
        }

        $tag['raw_values'] = $options;
        $tag['values'] = $options;
    }

    // Kapazitat
    if ( $tag['basetype'] === 'select' && $tag['name'] === 'kapazitat' ) {
        $options = array();

        for( $i=1; $i<=10; $i++ ) {
            $options[] = $i;
        }

        $tag['raw_values'] = $options;
        $tag['values'] = $options;
    }

    return $tag;
}

/**
 * Save booking form submission
 */
add_action( 'wpcf7_mail_sent', 'save_booking_form_submission' );
function save_booking_form_submission( $contact_form ) {

    if ( $contact_form->id() === 320 ) {
        // Get the posted form data
        $submission = WPCF7_Submission::get_instance();
        
        if ( $submission ) {
            $posted_data = $submission->get_posted_data();

            $vorname       = $posted_data['vorname'];
            $nachname      = $posted_data['nachname'];
            $telefonnummer = $posted_data['telefonnummer'];
            $email_address = $posted_data['email-address'];
            $kurse         = is_array( $posted_data['kurse'] ) ? $posted_data['kurse'][0] : '';
            $datum         = $posted_data['datum'];
            $datum_format  = $posted_data['datum_format'];
            $zeit          = is_array( $posted_data['zeit'] ) ? str_replace( ' ', '', $posted_data['zeit'][0] ) : '';
            $time          = $zeit ? explode( "-", $zeit ) : '';
            $start_time    = $time ? $time[0] : '';
            $end_time      = $time ? $time[1] : '';
            $kapazitat     = is_array( $posted_data['kapazitat'] ) ? (int) $posted_data['kapazitat'][0] : '';

            $unique_id = uniqid();
            $unique_number = substr( $unique_id, -6 );
            
            // Prepare post data
            $post_data = array(
                'post_title'    => "#{$unique_number} - Event booking from {$vorname} {$nachname}",
                'post_type'     => 'event-booking',
                'post_status'   => 'publish',
            );
            
            // Insert the post into the database
            $booking_id = wp_insert_post( $post_data );
            
            if ( $booking_id ) {
                $event_id  = pc_get_post_id_by_post_title( 'em_event', $kurse );
                $datum = date( "Ymd", strtotime( $datum ) );

                update_post_meta( $booking_id, 'pc_vorname', $vorname );
                update_post_meta( $booking_id, 'pc_nachname', $nachname );
                update_post_meta( $booking_id, 'pc_telefonnummer', $telefonnummer );
                update_post_meta( $booking_id, 'pc_email_address', $email_address );
                update_post_meta( $booking_id, 'pc_event', $event_id );
                update_post_meta( $booking_id, 'pc_date', $datum_format );
                update_post_meta( $booking_id, 'pc_time', $zeit );
                update_post_meta( $booking_id, 'pc_start_time', $start_time );
                update_post_meta( $booking_id, 'pc_end_time', $end_time );
                update_post_meta( $booking_id, 'pc_time', $zeit );
                update_post_meta( $booking_id, 'pc_kapazitat', $kapazitat );
            }
        }
    }
}


/**
 * Validation kapazitat
 */
function custom_validate_kapazitat( $result, $tag ) {
    if ( $tag->name !== 'kapazitat' ) {
        return $result;
    }

    $value = isset($_POST['kapazitat']) ? sanitize_text_field($_POST['kapazitat']) : '';

    // Get the selected course, date, and time from the form
    $course        = isset($_POST['kurse']) ? sanitize_text_field($_POST['kurse']): '';
    $datum         = isset($_POST['datum_format']) ? sanitize_text_field($_POST['datum_format'])  : '';
    $time_selected = isset($_POST['zeit']) ? sanitize_text_field($_POST['zeit'])    : '';

    if (empty($course) || empty($datum) || empty($time_selected)) {
        $result->invalidate( $tag, 'Bitte wählen Sie einen Kurs, ein Datum und eine Zeit aus asdasd.' );
        return $result;
    }

    // Get the event ID based on the course title
    $event_id = pc_get_post_id_by_post_title('em_event', $course);

    if (!$event_id) {
        $result->invalidate( $tag, 'Der ausgewählte Kurs ist ungültig.' );
        return $result;
    }

    $event_id = pc_get_post_id_by_post_title('em_event', $course);
    if (!$event_id) {
        $result->invalidate( $tag, 'Der ausgewählte Kurs ist ungültig.' );
        return $result;
    }

    $total_capacity_used = get_data_event_booking_capacity($event_id, $datum, $time_selected);
    $max_capacity = get_field('pc_maximum_capacity', $event_id);
    $available_capacity  = (int)$max_capacity - $total_capacity_used;


    // Ensure the selected capacity does not exceed the available capacity
    if ($value > $available_capacity) {
        $result->invalidate( $tag, 'Die ausgewählte Kapazität übersteigt die verfügbare Kapazität für diesen Zeitraum.' );
    }

    return $result;
}
add_filter( 'wpcf7_validate_select*', 'custom_validate_kapazitat', 10, 2 );