<?php 
/**
 * Get Event Price
 */
function pc_get_event_price( $event_id, $show_currency = false, $suffix = 'pro person' ) {
    global $wpdb;

    $price_options_table = $wpdb->prefix . 'em_price_options';
    $get_ticket_data = $wpdb->get_row( $wpdb->prepare( "SELECT price FROM $price_options_table WHERE `event_id` = %d", $event_id ) );
    $price = $get_ticket_data->price ?? 0;

    if( empty( $price ) ) return;

    if( $show_currency && function_exists( 'ep_price_with_position' ) ){
        return ep_price_with_position( $price ) . ' ' . $suffix;
    } else {
        return $price. ' ' . $suffix;
    }
}

/**
 * Get event diff
 */
function pc_get_event_date_time_diff( $event ) {
    $date_diff = '';
    if( ! empty( $event->em_start_date ) && ! empty( $event->em_end_date ) ) {
        $start_date = ep_timestamp_to_date( $event->em_start_date, 'Y-m-d', 1 );
        $start_time = $end_time = '';
        if( ! empty( $event->em_start_time ) ) {
            $start_time = $event->em_start_time;
        }
        $end_date = ep_timestamp_to_date( $event->em_end_date, 'Y-m-d', 1 );
        if( ! empty( $event->em_end_time ) ) {
            $end_time = $event->em_end_time;
        }
        if( ! empty( $start_time ) ) {
            $start_date .= ' ' . $start_time;
        }
        if( ! empty( $end_time ) ) {
            $end_date .= ' ' . $end_time;
        }

        // create date
        //$start_date = DateTime::createFromFormat( 'Y-m-d H:i', $start_date );
        $start_datetime = new DateTime( $start_date );
        // get difference
        //$end_date = DateTime::createFromFormat( 'Y-m-d H:i', $end_date );
        $diff = $start_datetime->diff( new DateTime( $end_date ) );
        $date_diff = '';
        if( ! empty( $diff->y ) ) { // year
            $date_diff .= $diff->y . ' ' . _n( 'Jahr', 'Jahre', $diff->y ) . ' ';
        }
        if( ! empty( $diff->m ) ) { // month
            $date_diff .= $diff->m . ' ' . _n( 'Monat', 'Monate', $diff->m ) . ' ';
        }
        if( ! empty( $diff->d ) ) { // days
            $date_diff .= $diff->d . ' ' . _n( 'Tag', 'Tage', $diff->d ) . ' ';
        }
        if( ! empty( $diff->h ) ) { // hour
            $date_diff .= $diff->h . ' ' . _n( 'Stunde', 'Stunden', $diff->h ) . ' ';
        }
        if( ! empty( $diff->i ) ) { // minute
            $date_diff .= $diff->i . ' ' . _n( 'Minute', 'Minuten', $diff->i ) . ' ';
        }
    }
    return $date_diff;
}