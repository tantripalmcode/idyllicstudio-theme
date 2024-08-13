<?php 
/**
 * Add custom column header for event booking post type
 */
function custom_event_booking_columns($columns) {

    // Remove the 'date' column from the array
    $date_column = $columns['date'];
    unset($columns['date']);

    $columns['event']      = 'Event';
    $columns['name']       = 'Name';
    $columns['phone']      = 'Telefonnummer';
    $columns['event_date'] = 'Datum';
    $columns['event_time'] = 'Zeit';
    $columns['person']     = 'Person';
    $columns['date']       = $date_column;

    return $columns;
}
add_filter('manage_event-booking_posts_columns', 'custom_event_booking_columns');

// Populate custom column with data
function custom_event_booking_column_content($column_name, $post_id) {
    if ( $column_name === 'event' ) {
        $event_id = get_post_meta( $post_id, 'pc_event', true );
        echo get_the_title( $event_id );
    }

    if ( $column_name === 'name' ) {
        $vorname = get_post_meta( $post_id, 'pc_vorname', true );
        $nachname = get_post_meta( $post_id, 'pc_nachname', true );
        echo $vorname . ' ' . $nachname;
    }

    if ( $column_name === 'phone' ) {
        $phone = get_post_meta( $post_id, 'pc_telefonnummer', true );
        echo sprintf( '<a href="tel:%1$s">%1$s</a>', $phone );;
    }

    if ( $column_name === 'event_date' ) {
        $date = get_post_meta( $post_id, 'pc_date', true );
        $date = $date ? date( "d M, Y", strtotime( $date ) ) : '';
        echo $date;
    }

    if ( $column_name === 'event_time' ) {
        $start_time = get_post_meta( $post_id, 'pc_start_time', true );
        $end_time = get_post_meta( $post_id, 'pc_end_time', true );
        echo $start_time . ' - ' . $end_time;
    }

    if ( $column_name === 'person' ) {
        $person = get_post_meta( $post_id, 'pc_kapazitat', true );
        echo $person;
    }
}
add_action('manage_event-booking_posts_custom_column', 'custom_event_booking_column_content', 10, 2);