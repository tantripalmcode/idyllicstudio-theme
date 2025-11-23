<?php
if (!defined('ABSPATH')) wp_die('Direct access forbidden.');

/**
 * Ajax select course
 */
add_action('wp_ajax_pc_check_availability', 'ajax_pc_check_availability');
add_action('wp_ajax_nopriv_pc_check_availability', 'ajax_pc_check_availability');
function ajax_pc_check_availability()
{
    $post          = isset($_POST) ? wp_unslash($_POST) : [];
    $course        = $post['course'];
    $dayName       = $post['dayName'];
    $time_selected = $post['time'];

    if ($dayName) {
        $today         = strtolower($dayName);
        $selected_date = $post['formattedDate'];
    } else {
        $today = false;
        $selected_date = date("Ymd");
    }

    $response['success'] = false;
    $response['selected_date'] = $selected_date;

    // Close event date lists global
    $close_event_date_lists = get_field('pc_close_date_lists', 'option');
    $close_dates = [];
    if ($close_event_date_lists) {
        foreach ($close_event_date_lists as $close_event) {
            $close_dates[] = $close_event['date'];
        }
    }


    // Get event posts
    // $course here contains the slug, so get the event by slug
    $event = get_page_by_path($course, OBJECT, 'em_event');
    $event_id = $event ? $event->ID : false;

    if ($event_id) {

        $is_monthly_event = get_field('pc_monthly_event', $event_id);

        //close event date lists per event
        $close_event_start_date = get_field('close_start_date', $event_id);
        $close_event_end_date = get_field('close_end_date', $event_id);
        if ($close_event_start_date && $close_event_end_date) {
            // Convert DD/MM/YYYY format to DateTime objects
            $start_date = DateTime::createFromFormat('d/m/Y', $close_event_start_date);
            $end_date = DateTime::createFromFormat('d/m/Y', $close_event_end_date);
            
            if ($start_date && $end_date) {
                // Generate all dates from start to end
                $current_date = clone $start_date;
                while ($current_date <= $end_date) {
                    $close_dates[] = $current_date->format('Y-m-d');
                    $current_date->add(new DateInterval('P1D')); // Add 1 day
                }
            }
        }

        $day_data            = get_field('pc_' . $today, $event_id);
        $max_capacity        = get_field('pc_maximum_capacity', $event_id);
        
        // For monthly events, check capacity without time (only by date)
        $time_for_capacity_check = $is_monthly_event ? '' : $time_selected;
        $total_capacity_used = get_data_event_booking_capacity($event_id, $selected_date, $time_for_capacity_check);
        $max_capacity        = (int)$max_capacity - $total_capacity_used;
        $max_capacity        = $max_capacity <= 0 ? 0 : $max_capacity;

        $total_booked_on_that_day = get_total_book_on_the_day($selected_date, $time_for_capacity_check);
        $available_capcaity_left_on_that_day = PC_MAX_CAPACITY_PER_TIME_SLOT - $total_booked_on_that_day;

        if($max_capacity > $available_capcaity_left_on_that_day){
            $max_capacity = $available_capcaity_left_on_that_day;
        }
        $max_capacity  = $max_capacity <= 0 ? 0 : $max_capacity;

        // Get availability dates
        $availability_dates = get_field('pc_availability_date', $event_id);
        $dates = [];
        if ($availability_dates) {
            foreach ($availability_dates as $available_date) {
                $dates[] = $available_date['datum'];
            }
        }

        $response['interval'] = $day_data['interval'] ?? 0;

        // For monthly events, skip time options and show capacity directly
        if ($is_monthly_event) {
            $response['success'] = true;
            
            // Set max capacity options for monthly event
            $max_capacity_options = '<option value="">Kapazitat wählen</option>';
            for ($capacity = 1; $capacity <= (int) $max_capacity; $capacity++) {
                $max_capacity_options .= '<option value="' . $capacity . '">' . $capacity . '</option>';
            }
            
            $response['max_capacity']         = '(' . $max_capacity . ' verfügbar' . ')';
            $response['max_capacity_number']  = (int) $max_capacity;
            $response['max_capacity_options'] = $max_capacity_options;
            $response['time_field']           = "not_required"; // Indicate time is not required for monthly events
        } elseif ($day_data && $today) {
            $now = new DateTime('now', new DateTimeZone('Europe/Berlin')); // Current date and time

            // Create a DateTime object from the string
            $selectedDateFormat = DateTime::createFromFormat('Ymd', $selected_date);

            $response['success'] = true;

            $start_time = strtotime($day_data['start_time']);
            $end_time   = strtotime($day_data['end_time']);
            $interval   = $day_data['interval'] * 3600; // Convert interval to seconds

            // Set time options
            $time_options = '<option value="">Zeit wählen</option>';
            for ($time = $start_time; $time < $end_time; $time += $interval) {
                $start = date('H:i', $time);
                $end   = date('H:i', $time + $interval);
                $start = trim($start);
                
                // Format the date to Y-m-d and combine with the start time
                $eventDateTime  = $selectedDateFormat->format('Y-m-d') . " " . $start;
                $eventDate      = new DateTime($eventDateTime); // Create DateTime object for event
                $eventTimestamp = $eventDate->getTimestamp();
                $nowTimestamp   = $now->getTimestamp();
                $secondsDiff    = $eventTimestamp - $nowTimestamp;
                $hoursDiff      = $secondsDiff / 3600;
            
                if ($hoursDiff > 25.5) {
                    $time_options .= "<option value=\"$start - $end\">$start - $end</option>\n";
                }
            }
            

            // Set max capcity options
            $max_capacity_options = '<option value="">Kapazitat wählen</option>';
            for ($capacity = 1; $capacity <= (int) $max_capacity; $capacity++) {
                $max_capacity_options .= '<option value="' . $capacity . '">' . $capacity . '</option>';
            }

            $response['time_options'] = $time_options;
            $response['time_selected'] = $time_selected;

            if ($time_selected) {
                $response['max_capacity']         = '(' . $max_capacity . ' verfügbar' . ')';
                $response['max_capacity_number']  = (int) $max_capacity;
                $response['max_capacity_options'] = $max_capacity_options;
            } else {
                $response['time_field']           = "empty";
            }
        } else {
            $response['success'] = true;
            $response['date_field'] = "empty";
        }

        $response['is_monthly_event'] = $is_monthly_event;
        $response['dates']            = $dates ? $dates: 'everyday';
        $response['close_dates']      = $close_dates;
    }

    wp_send_json($response);
    die;
}

/**
 * Get data event booking capacity
 */
function get_data_event_booking_capacity($event_id, $date, $time)
{
    $date         = $date ?: date('Ymd');
    $time         = str_replace(' ', '', $time);
    $time         = $time ? explode('-', $time) : '';
    $start_time   = $time ? $time[0] : '';
    $end_time     = $time ? $time[1] : '';

    // Build meta query - for monthly events (empty time), don't filter by time
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key' => 'pc_event',
            'compare' => '=',
            'value' => $event_id,
        ),
        array(
            'key' => 'pc_date',
            'value' => $date,
            'compare' => '=',
            'type' => 'DATE',
        ),
    );

    // Only add time filters if time is provided (not for monthly events)
    if ($start_time && $end_time) {
        $meta_query[] = array(
            'key' => 'pc_start_time',
            'value' => $start_time,
            'compare' => '=',
        );
        $meta_query[] = array(
            'key' => 'pc_end_time',
            'value' => $end_time,
            'compare' => '=',
        );
    }

    // Get event booking posts
    $event_bookings = get_posts(
        array(
            'post_type' => 'event-booking',  // Change 'post' to your custom post type if needed
            'posts_per_page' => -1,  // Get all posts
            'post_status' => ['publish'],
            'meta_query' => $meta_query,
        )
    );

    $total_capacity = 0;

    if ($event_bookings) {
        foreach ($event_bookings as $event_booking) {
            $event_booking_id = $event_booking->ID;
            $event_booking_kapasitat = get_field('pc_kapazitat', $event_booking_id);

            $total_capacity = $total_capacity + (int) $event_booking_kapasitat;
        }
    }

    return (int) $total_capacity;
}


/**
 * Get data event booking capacity
 */
function get_total_book_on_the_day($date, $time)
{
    $date         = $date ?: date('Ymd');
    $time         = str_replace(' ', '', $time);
    $time         = $time ? explode('-', $time) : '';
    $start_time   = $time ? $time[0] : '';
    $end_time     = $time ? $time[1] : '';

    // Build meta query - for monthly events (empty time), don't filter by time
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key' => 'pc_date',
            'value' => $date,
            'compare' => '=',
            'type' => 'DATE',
        ),
    );

    // Only add time filters if time is provided (not for monthly events)
    if ($start_time && $end_time) {
        $meta_query[] = array(
            'key' => 'pc_start_time',
            'value' => $start_time,
            'compare' => '=',
        );
        $meta_query[] = array(
            'key' => 'pc_end_time',
            'value' => $end_time,
            'compare' => '=',
        );
    }

    // Get event booking posts
    $event_bookings = get_posts(
        array(
            'post_type' => 'event-booking',  // Change 'post' to your custom post type if needed
            'posts_per_page' => -1,  // Get all posts
            'post_status' => ['publish'],
            'meta_query' => $meta_query,
        )
    );

    $total_booked = 0;

    if ($event_bookings) {
        foreach ($event_bookings as $event_booking) {
            $event_booking_id = $event_booking->ID;
            $event_booking_kapasitat = get_field('pc_kapazitat', $event_booking_id);

            $total_booked = $total_booked + (int) $event_booking_kapasitat;
        }
    }

    return (int) $total_booked;
}