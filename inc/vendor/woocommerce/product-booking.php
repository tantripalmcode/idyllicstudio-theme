<?php
defined('ABSPATH') || exit;

/**
 * WooCommerce Product Booking System
 * Handles datepicker and booking fields for event products
 */

/**
 * Add booking fields before add to cart button on single product page
 */
add_action('woocommerce_before_add_to_cart_button', 'pc_add_product_booking_fields');
function pc_add_product_booking_fields() {
    global $product;
    
    // Only show for products that have event availability configured
    $availability_mode = carbon_get_post_meta($product->get_id(), 'pc_availability_mode');
    
    if (!$availability_mode) {
        return;
    }
    
    ?>
    <div class="pc-product-booking-fields">
        <div class="pc-booking-field-group">
            <label for="pc-booking-date"><?php _e('Datum', 'palmcode-child'); ?> <span class="required">*</span></label>
            <input type="text" id="pc-booking-date" name="pc_booking_date" class="pc-booking-date" readonly />
            <input type="hidden" id="pc-booking-date-format" name="pc_booking_date_format" />
        </div>
        
        <div class="pc-booking-field-group pc-booking-time-group" style="display: none;">
            <label for="pc-booking-time"><?php _e('Zeit', 'palmcode-child'); ?> <span class="required">*</span></label>
            <select id="pc-booking-time" name="pc_booking_time" class="pc-booking-time">
                <option value=""><?php _e('Zeit wählen', 'palmcode-child'); ?></option>
            </select>
        </div>
        
        <div class="pc-availability-info"></div>
        
        <input type="hidden" id="pc-product-id" value="<?php echo esc_attr($product->get_id()); ?>" />
    </div>
    <?php
}

/**
 * Enqueue scripts and styles for product booking
 */
add_action('wp_enqueue_scripts', 'pc_enqueue_product_booking_scripts');
function pc_enqueue_product_booking_scripts() {
    // Only enqueue on product page (not cart page since we're just displaying info)
    if (!is_product()) {
        return;
    }
    
    // Enqueue datepicker (jQuery UI)
    wp_enqueue_script('jquery-ui-datepicker');
    wp_enqueue_style('jquery-ui-datepicker', 'https://code.jquery.com/ui/1.13.2/themes/ui-lightness/jquery-ui.css');
    
    // Enqueue moment.js if not already loaded
    if (!wp_script_is('moment', 'enqueued')) {
        wp_enqueue_script('moment', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js', array(), '2.29.4', true);
        wp_enqueue_script('moment-locale-de', 'https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/locale/de.min.js', array('moment'), '2.29.4', true);
    }
    
    // Enqueue custom booking script
    wp_enqueue_script(
        'pc-product-booking',
        PC_CHILD_ASSETS_JS_URL . '/product-booking.js',
        array('jquery', 'jquery-ui-datepicker', 'moment'),
        PC_CHILD_VERSION,
        true
    );
    
    // Localize script
    wp_localize_script('pc-product-booking', 'pcProductBooking', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pc_product_booking_nonce'),
    ));
}

/**
 * AJAX handler to check product availability
 */
add_action('wp_ajax_pc_check_product_availability', 'pc_ajax_check_product_availability');
add_action('wp_ajax_nopriv_pc_check_product_availability', 'pc_ajax_check_product_availability');
function pc_ajax_check_product_availability() {
    check_ajax_referer('pc_product_booking_nonce', 'nonce');
    
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $selected_date = isset($_POST['selected_date']) ? sanitize_text_field($_POST['selected_date']) : '';
    $formatted_date = isset($_POST['formatted_date']) ? sanitize_text_field($_POST['formatted_date']) : '';
    $time_selected = isset($_POST['time']) ? sanitize_text_field($_POST['time']) : '';
    
    $response = array(
        'success' => false,
        'message' => '',
    );
    
    if (!$product_id) {
        wp_send_json_error(array('message' => __('Product ID is required.', 'palmcode-child')));
        return;
    }
    
    // Get product availability mode
    $availability_mode = carbon_get_post_meta($product_id, 'pc_availability_mode');
    $event_capacity = carbon_get_post_meta($product_id, 'pc_event_capacity');
    
    if (!$availability_mode) {
        wp_send_json_error(array('message' => __('This product does not have availability configured.', 'palmcode-child')));
        return;
    }
    
    // Get closed dates (global and per product)
    $close_dates = array();
    
    // Global closed dates
    $close_event_date_lists = carbon_get_theme_option('pc_close_date_lists');
    if ($close_event_date_lists) {
        foreach ($close_event_date_lists as $close_event) {
            if (isset($close_event['date'])) {
                $close_dates[] = $close_event['date'];
            }
        }
    }
    
    // Product-specific closed dates
    if ($availability_mode === 'weekly') {
        $weekly_closed_dates = carbon_get_post_meta($product_id, 'pc_weekly_closed_dates');
        if ($weekly_closed_dates) {
            foreach ($weekly_closed_dates as $closed_date) {
                if (isset($closed_date['closed_date'])) {
                    $close_dates[] = $closed_date['closed_date'];
                }
            }
        }
    }
    
    // If no date selected, just return availability mode and dates info
    if (empty($selected_date) && empty($formatted_date)) {
        // Get available dates for specific_dates mode
        $available_dates = array();
        if ($availability_mode === 'specific_dates') {
            $specific_date_slots = carbon_get_post_meta($product_id, 'pc_specific_date_slots');
            if ($specific_date_slots && is_array($specific_date_slots)) {
                foreach ($specific_date_slots as $slot) {
                    if (isset($slot['slot_date'])) {
                        $available_dates[] = $slot['slot_date'];
                    }
                }
            }
        }
        
        $response['success'] = true;
        $response['available_dates'] = $availability_mode === 'weekly' ? 'everyday' : $available_dates;
        $response['close_dates'] = $close_dates;
        $response['availability_mode'] = $availability_mode;
        wp_send_json_success($response);
        return;
    }
    
    // Check if selected date is closed
    if (in_array($selected_date, $close_dates)) {
        wp_send_json_error(array('message' => __('Selected date is closed.', 'palmcode-child')));
        return;
    }
    
    // Get day name from date (convert to Carbon Fields format: tuesday, wednesday, etc.)
    $day_name = '';
    if ($formatted_date) {
        $date_obj = DateTime::createFromFormat('Ymd', $formatted_date);
        if ($date_obj) {
            $day_name_english = strtolower($date_obj->format('l')); // monday, tuesday, etc.
            // Map to Carbon Fields day values
            $day_mapping = array(
                'monday' => 'monday',
                'tuesday' => 'tuesday',
                'wednesday' => 'wednesday',
                'thursday' => 'thursday',
                'friday' => 'friday',
                'saturday' => 'saturday',
                'sunday' => 'sunday'
            );
            $day_name = isset($day_mapping[$day_name_english]) ? $day_mapping[$day_name_english] : '';
        }
    }
    
        // Get available dates for specific_dates mode
        $available_dates = array();
        if ($availability_mode === 'specific_dates') {
            $specific_date_slots = carbon_get_post_meta($product_id, 'pc_specific_date_slots');
            if ($specific_date_slots && is_array($specific_date_slots)) {
                foreach ($specific_date_slots as $slot) {
                    if (isset($slot['slot_date'])) {
                        $available_dates[] = $slot['slot_date'];
                    }
                }
            }
        }
        
        // Get availability data based on mode
        if ($availability_mode === 'weekly') {
        // Get weekly schedule
        $weekly_schedule = carbon_get_post_meta($product_id, 'pc_weekly_schedule');
        
        if (!$weekly_schedule || !is_array($weekly_schedule)) {
            wp_send_json_error(array('message' => __('No weekly schedule configured.', 'palmcode-child')));
            return;
        }
        
        // Find schedule for selected day
        $day_schedule = null;
        foreach ($weekly_schedule as $schedule) {
            if (isset($schedule['day']) && $schedule['day'] === $day_name) {
                $day_schedule = $schedule;
                break;
            }
        }
        
        if (!$day_schedule) {
            wp_send_json_error(array('message' => __('No schedule available for selected day.', 'palmcode-child')));
            return;
        }
        
        // Get schedule details
        $start_time = isset($day_schedule['start_time']) ? $day_schedule['start_time'] : '';
        $end_time = isset($day_schedule['end_time']) ? $day_schedule['end_time'] : '';
        $interval = isset($day_schedule['interval']) ? intval($day_schedule['interval']) : 60;
        
        if (!$start_time || !$end_time) {
            wp_send_json_error(array('message' => __('Schedule times not configured.', 'palmcode-child')));
            return;
        }
        
        // Calculate available capacity
        $total_capacity_used = pc_get_product_booking_capacity($product_id, $formatted_date, $time_selected);
        $max_capacity = $event_capacity ? intval($event_capacity) : PC_MAX_CAPACITY_PER_TIME_SLOT;
        $available_capacity = $max_capacity - $total_capacity_used;
        $available_capacity = $available_capacity < 0 ? 0 : $available_capacity;
        
        // Generate time slots
        $time_options = '<option value="">' . __('Zeit wählen', 'palmcode-child') . '</option>';
        
        if (!$time_selected) {
            $start_timestamp = strtotime($start_time);
            $end_timestamp = strtotime($end_time);
            $interval_seconds = $interval * 60;
            
            $now = new DateTime('now', new DateTimeZone('Europe/Berlin'));
            $selected_date_obj = DateTime::createFromFormat('Ymd', $formatted_date);
            
            for ($time = $start_timestamp; $time < $end_timestamp; $time += $interval_seconds) {
                $start = date('H:i', $time);
                $end = date('H:i', $time + $interval_seconds);
                
                // Check if time slot is at least 25.5 hours in the future
                if ($selected_date_obj) {
                    $event_datetime = $selected_date_obj->format('Y-m-d') . ' ' . $start;
                    $event_date = new DateTime($event_datetime);
                    $event_timestamp = $event_date->getTimestamp();
                    $now_timestamp = $now->getTimestamp();
                    $hours_diff = ($event_timestamp - $now_timestamp) / 3600;
                    
                    if ($hours_diff > 25.5) {
                        $time_options .= '<option value="' . esc_attr($start . ' - ' . $end) . '">' . esc_html($start . ' - ' . $end) . '</option>';
                    }
                }
            }
        }
        
        // Generate capacity options
        $capacity_options = '<option value="">' . __('Kapazität wählen', 'palmcode-child') . '</option>';
        for ($capacity = 1; $capacity <= $available_capacity; $capacity++) {
            $capacity_options .= '<option value="' . $capacity . '">' . $capacity . '</option>';
        }
        
        $response['success'] = true;
        $response['time_options'] = $time_options;
        $response['capacity_options'] = $capacity_options;
        $response['available_capacity'] = $available_capacity;
        $response['available_dates'] = 'everyday'; // For weekly mode
        $response['close_dates'] = $close_dates;
        $response['availability_mode'] = $availability_mode;
        
        // Only show availability text when time is selected
        if ($time_selected) {
            $response['show_capacity'] = true;
            $response['available_capacity_text'] = '(' . $available_capacity . ' verfügbar)';
        } else {
            $response['show_time'] = true;
            // Don't show availability text when only date is selected
            $response['available_capacity_text'] = '';
        }
        
    } elseif ($availability_mode === 'specific_dates') {
        // Handle specific dates mode
        $specific_date_slots = carbon_get_post_meta($product_id, 'pc_specific_date_slots');
        
        if (!$specific_date_slots || !is_array($specific_date_slots)) {
            wp_send_json_error(array('message' => __('No specific dates configured.', 'palmcode-child')));
            return;
        }
        
        // Find matching date slot
        $date_slot = null;
        foreach ($specific_date_slots as $slot) {
            if (isset($slot['slot_date']) && $slot['slot_date'] === $selected_date) {
                $date_slot = $slot;
                break;
            }
        }
        
        if (!$date_slot) {
            wp_send_json_error(array('message' => __('Selected date is not available.', 'palmcode-child')));
            return;
        }
        
        // Get time slots for this date
        $time_slots = isset($date_slot['time_slots']) ? $date_slot['time_slots'] : array();
        
        if (empty($time_slots)) {
            wp_send_json_error(array('message' => __('No time slots available for selected date.', 'palmcode-child')));
            return;
        }
        
        // Calculate available capacity
        $total_capacity_used = pc_get_product_booking_capacity($product_id, $formatted_date, $time_selected);
        $max_capacity = $event_capacity ? intval($event_capacity) : PC_MAX_CAPACITY_PER_TIME_SLOT;
        $available_capacity = $max_capacity - $total_capacity_used;
        $available_capacity = $available_capacity < 0 ? 0 : $available_capacity;
        
        // Generate time options from specific time slots
        $time_options = '<option value="">' . __('Zeit wählen', 'palmcode-child') . '</option>';
        if (!$time_selected) {
            foreach ($time_slots as $slot) {
                $start_time = isset($slot['start_time']) ? $slot['start_time'] : '';
                $end_time = isset($slot['end_time']) ? $slot['end_time'] : '';
                
                if ($start_time && $end_time) {
                    $time_options .= '<option value="' . esc_attr($start_time . ' - ' . $end_time) . '">' . esc_html($start_time . ' - ' . $end_time) . '</option>';
                }
            }
        }
        
        // Generate capacity options
        $capacity_options = '<option value="">' . __('Kapazität wählen', 'palmcode-child') . '</option>';
        for ($capacity = 1; $capacity <= $available_capacity; $capacity++) {
            $capacity_options .= '<option value="' . $capacity . '">' . $capacity . '</option>';
        }
        
        $response['success'] = true;
        $response['time_options'] = $time_options;
        $response['capacity_options'] = $capacity_options;
        $response['available_capacity'] = $available_capacity;
        $response['available_dates'] = $available_dates;
        $response['close_dates'] = $close_dates;
        $response['availability_mode'] = $availability_mode;
        
        // Only show availability text when time is selected
        if ($time_selected) {
            $response['show_capacity'] = true;
            $response['available_capacity_text'] = '(' . $available_capacity . ' verfügbar)';
        } else {
            $response['show_time'] = true;
            // Don't show availability text when only date is selected
            $response['available_capacity_text'] = '';
        }
    }
    
    wp_send_json_success($response);
}

/**
 * Get product booking capacity for a specific date and time
 * Includes both completed orders and current cart items
 */
function pc_get_product_booking_capacity($product_id, $date, $time = '', $exclude_cart_item_key = '') {
    $date = $date ?: date('Ymd');
    $time = str_replace(' ', '', $time);
    $time = $time ? explode('-', $time) : '';
    $start_time = $time ? $time[0] : '';
    $end_time = $time ? $time[1] : '';
    
    $total_capacity = 0;
    
    // 1. Check completed/processing orders
    $orders = wc_get_orders(array(
        'limit' => -1,
        'status' => array('wc-completed', 'wc-processing', 'wc-on-hold'),
    ));
    
    if ($orders) {
        foreach ($orders as $order) {
            foreach ($order->get_items() as $item) {
                // Check if this item matches the product, date, and time
                if ($item->get_product_id() == $product_id) {
                    // Check for both underscore and non-underscore versions for backward compatibility
                    $item_date_format = $item->get_meta('_pc_booking_date_format') ?: $item->get_meta('pc_booking_date_format');
                    $item_start_time = $item->get_meta('_pc_booking_start_time') ?: $item->get_meta('pc_booking_start_time');
                    $item_end_time = $item->get_meta('_pc_booking_end_time') ?: $item->get_meta('pc_booking_end_time');
                    
                    // Check if date matches
                    if ($item_date_format == $date) {
                        // If time is provided, check time match
                        if ($start_time && $end_time) {
                            if ($item_start_time == $start_time && $item_end_time == $end_time) {
                                $booking_capacity = $item->get_meta('pc_booking_capacity');
                                if ($booking_capacity) {
                                    $total_capacity += intval($booking_capacity);
                                }
                            }
                        } else {
                            // No time filter, count all bookings for this date
                            $booking_capacity = $item->get_meta('pc_booking_capacity');
                            if ($booking_capacity) {
                                $total_capacity += intval($booking_capacity);
                            }
                        }
                    }
                }
            }
        }
    }
    
    // 2. Check current cart items (to include items already in cart)
    if (WC()->cart && !WC()->cart->is_empty()) {
        foreach (WC()->cart->get_cart() as $cart_item_key => $cart_item) {
            // Skip the current item being added if specified
            if ($cart_item_key === $exclude_cart_item_key) {
                continue;
            }
            
            // Check if this cart item matches the product, date, and time
            if (isset($cart_item['product_id']) && $cart_item['product_id'] == $product_id) {
                $cart_date_format = isset($cart_item['pc_booking_date_format']) ? $cart_item['pc_booking_date_format'] : '';
                $cart_start_time = isset($cart_item['pc_booking_start_time']) ? $cart_item['pc_booking_start_time'] : '';
                $cart_end_time = isset($cart_item['pc_booking_end_time']) ? $cart_item['pc_booking_end_time'] : '';
                
                // Check if date matches
                if ($cart_date_format == $date) {
                    // If time is provided, check time match
                    if ($start_time && $end_time) {
                        if ($cart_start_time == $start_time && $cart_end_time == $end_time) {
                            $cart_capacity = isset($cart_item['pc_booking_capacity']) ? $cart_item['pc_booking_capacity'] : (isset($cart_item['quantity']) ? $cart_item['quantity'] : 0);
                            if ($cart_capacity) {
                                $total_capacity += intval($cart_capacity);
                            }
                        }
                    } else {
                        // No time filter, count all bookings for this date
                        $cart_capacity = isset($cart_item['pc_booking_capacity']) ? $cart_item['pc_booking_capacity'] : (isset($cart_item['quantity']) ? $cart_item['quantity'] : 0);
                        if ($cart_capacity) {
                            $total_capacity += intval($cart_capacity);
                        }
                    }
                }
            }
        }
    }
    
    return intval($total_capacity);
}

/**
 * Validate booking fields before adding to cart
 */
add_filter('woocommerce_add_to_cart_validation', 'pc_validate_product_booking_fields', 10, 3);
function pc_validate_product_booking_fields($passed, $product_id, $quantity) {
    // Check if product has availability configured
    $availability_mode = carbon_get_post_meta($product_id, 'pc_availability_mode');
    
    if (!$availability_mode) {
        return $passed;
    }
    
    // Validate booking date
    if (empty($_POST['pc_booking_date_format'])) {
        wc_add_notice(__('Bitte wählen Sie ein Datum aus.', 'palmcode-child'), 'error');
        $passed = false;
    }
    
    // Validate booking time (if not monthly event)
    if (empty($_POST['pc_booking_time'])) {
        wc_add_notice(__('Bitte wählen Sie eine Zeit aus.', 'palmcode-child'), 'error');
        $passed = false;
    }
    
    // Validate quantity (used as capacity)
    if (empty($_POST['quantity']) || intval($_POST['quantity']) <= 0) {
        wc_add_notice(__('Bitte wählen Sie eine Menge aus.', 'palmcode-child'), 'error');
        $passed = false;
    }
    
    // Check capacity availability using quantity
    if (!empty($_POST['pc_booking_date_format']) && !empty($_POST['pc_booking_time']) && !empty($_POST['quantity'])) {
        $date = sanitize_text_field($_POST['pc_booking_date_format']);
        $time = sanitize_text_field($_POST['pc_booking_time']);
        $requested_capacity = intval($_POST['quantity']);
        
        $total_capacity_used = pc_get_product_booking_capacity($product_id, $date, $time);
        $event_capacity = carbon_get_post_meta($product_id, 'pc_event_capacity');
        $max_capacity = $event_capacity ? intval($event_capacity) : PC_MAX_CAPACITY_PER_TIME_SLOT;
        $available_capacity = $max_capacity - $total_capacity_used;
        
        if ($requested_capacity > $available_capacity) {
            wc_add_notice(__('Die ausgewählte Kapazität übersteigt die verfügbare Kapazität für diesen Zeitraum.', 'palmcode-child'), 'error');
            $passed = false;
        }
    }
    
    return $passed;
}

/**
 * Add booking data to cart item
 */
add_filter('woocommerce_add_cart_item_data', 'pc_add_booking_data_to_cart_item', 10, 3);
function pc_add_booking_data_to_cart_item($cart_item_data, $product_id, $variation_id) {
    error_log('PC Booking - add_booking_data_to_cart_item called');
    // Initialize array if not set
    if (!is_array($cart_item_data)) {
        $cart_item_data = array();
    }
    
    // Debug: Log what we're receiving
    error_log('PC Booking - POST data: ' . print_r($_POST, true));
    
    if (isset($_POST['pc_booking_date']) && !empty($_POST['pc_booking_date'])) {
        $cart_item_data['pc_booking_date'] = sanitize_text_field($_POST['pc_booking_date']);
        error_log('PC Booking - Saved date: ' . $cart_item_data['pc_booking_date']);
    }
    
    if (isset($_POST['pc_booking_date_format']) && !empty($_POST['pc_booking_date_format'])) {
        $cart_item_data['pc_booking_date_format'] = sanitize_text_field($_POST['pc_booking_date_format']);
        error_log('PC Booking - Saved date format: ' . $cart_item_data['pc_booking_date_format']);
    }
    
    if (isset($_POST['pc_booking_time']) && !empty($_POST['pc_booking_time'])) {
        $cart_item_data['pc_booking_time'] = sanitize_text_field($_POST['pc_booking_time']);
        error_log('PC Booking - Saved time: ' . $cart_item_data['pc_booking_time']);
        
        // Extract start and end time
        $time = str_replace(' ', '', $_POST['pc_booking_time']);
        $time_parts = explode('-', $time);
        if (count($time_parts) === 2) {
            $cart_item_data['pc_booking_start_time'] = sanitize_text_field($time_parts[0]);
            $cart_item_data['pc_booking_end_time'] = sanitize_text_field($time_parts[1]);
        }
    }
    
    // Use quantity as capacity (WooCommerce quantity field)
    if (isset($_POST['quantity']) && !empty($_POST['quantity'])) {
        $cart_item_data['pc_booking_capacity'] = intval($_POST['quantity']);
        error_log('PC Booking - Saved capacity (from quantity): ' . $cart_item_data['pc_booking_capacity']);
    }
    
    error_log('PC Booking - Final cart item data: ' . print_r($cart_item_data, true));
    
    return $cart_item_data;
}

/**
 * Convert date format from YYYYMMDD to dd.mm.YYYY
 * Falls back to parsing pc_booking_date if format is not available
 */
function pc_format_booking_date_display($date_format, $booking_date = '') {
    // First try to use YYYYMMDD format
    if (!empty($date_format) && strlen($date_format) === 8) {
        $year = substr($date_format, 0, 4);
        $month = substr($date_format, 4, 2);
        $day = substr($date_format, 6, 2);
        
        // Return in dd.mm.YYYY format
        return $day . '.' . $month . '.' . $year;
    }
    
    // Fallback: try to parse pc_booking_date string (e.g., "Januar 15, 2024")
    if (!empty($booking_date)) {
        // Try to parse common date formats
        $parsed_date = date_create_from_format('F j, Y', $booking_date);
        if (!$parsed_date) {
            $parsed_date = date_create_from_format('d.m.Y', $booking_date);
        }
        if (!$parsed_date) {
            $parsed_date = strtotime($booking_date);
            if ($parsed_date !== false) {
                return date('d.m.Y', $parsed_date);
            }
        } else {
            return $parsed_date->format('d.m.Y');
        }
    }
    
    return '';
}

/**
 * Display booking data in cart (read-only overview)
 * Using woocommerce_cart_item_name filter to append booking info
 * Only show on cart page, not on checkout (checkout uses woocommerce_get_item_data instead)
 */
add_filter('woocommerce_cart_item_name', 'pc_display_booking_data_in_cart', 10, 3);
function pc_display_booking_data_in_cart($name, $cart_item, $cart_item_key) {
    // Only show on cart page, not on checkout
    if (is_checkout()) {
        return $name;
    }
    
    // Only show for products with booking data
    if (!isset($cart_item['pc_booking_date']) && !isset($cart_item['pc_booking_time']) && !isset($cart_item['pc_booking_capacity'])) {
        return $name;
    }
    
    $booking_time = isset($cart_item['pc_booking_time']) ? $cart_item['pc_booking_time'] : '';
    // Use quantity from cart item as capacity
    $booking_capacity = isset($cart_item['quantity']) ? $cart_item['quantity'] : (isset($cart_item['pc_booking_capacity']) ? $cart_item['pc_booking_capacity'] : '');
    
    // Format date from YYYYMMDD to dd.mm.YYYY
    $booking_date_format = isset($cart_item['pc_booking_date_format']) ? $cart_item['pc_booking_date_format'] : '';
    $booking_date_raw = isset($cart_item['pc_booking_date']) ? $cart_item['pc_booking_date'] : '';
    $booking_date = pc_format_booking_date_display($booking_date_format, $booking_date_raw);
    
    if (empty($booking_date) && empty($booking_time) && empty($booking_capacity)) {
        return $name;
    }
    
    $booking_info = '<div class="pc-cart-booking-info" style="margin-top: 8px; font-size: 0.9em;">';
    
    if (!empty($booking_date)) {
        $booking_info .= '<div class="pc-booking-detail" style="margin-bottom: 4px;">';
        $booking_info .= '<strong>' . __('Datum:', 'palmcode-child') . '</strong> ';
        $booking_info .= '<span>' . esc_html($booking_date) . '</span>';
        $booking_info .= '</div>';
    }
    
    if (!empty($booking_time)) {
        $booking_info .= '<div class="pc-booking-detail" style="margin-bottom: 4px;">';
        $booking_info .= '<strong>' . __('Zeit:', 'palmcode-child') . '</strong> ';
        $booking_info .= '<span>' . esc_html($booking_time) . '</span>';
        $booking_info .= '</div>';
    }
    
    if (!empty($booking_capacity)) {
        $booking_info .= '<div class="pc-booking-detail" style="margin-bottom: 4px;">';
        $booking_info .= '<strong>' . __('Kapazität:', 'palmcode-child') . '</strong> ';
        $booking_info .= '<span>' . esc_html($booking_capacity) . '</span>';
        $booking_info .= '</div>';
    }
    
    $booking_info .= '</div>';
    
    return $name . $booking_info;
}

/**
 * Format booking data for checkout cart item data display
 * This ensures booking info shows properly formatted on checkout page only
 * (Cart page uses woocommerce_cart_item_name filter instead)
 */
add_filter('woocommerce_get_item_data', 'pc_format_booking_data_for_checkout', 10, 2);
function pc_format_booking_data_for_checkout($item_data, $cart_item) {
    // Only show on checkout page, not on cart page
    if (!is_checkout()) {
        return $item_data;
    }
    
    // Only show for products with booking data
    if (!isset($cart_item['pc_booking_date']) && !isset($cart_item['pc_booking_time'])) {
        return $item_data;
    }
    
    $booking_time = isset($cart_item['pc_booking_time']) ? $cart_item['pc_booking_time'] : '';
    // Use quantity from cart item as capacity
    $booking_capacity = isset($cart_item['quantity']) ? $cart_item['quantity'] : (isset($cart_item['pc_booking_capacity']) ? $cart_item['pc_booking_capacity'] : '');
    
    // Format date from YYYYMMDD to dd.mm.YYYY
    $booking_date_format = isset($cart_item['pc_booking_date_format']) ? $cart_item['pc_booking_date_format'] : '';
    $booking_date_raw = isset($cart_item['pc_booking_date']) ? $cart_item['pc_booking_date'] : '';
    $booking_date = pc_format_booking_date_display($booking_date_format, $booking_date_raw);
    
    if (empty($booking_date) && empty($booking_time) && empty($booking_capacity)) {
        return $item_data;
    }
    
    // Add booking date
    if (!empty($booking_date)) {
        $item_data[] = array(
            'key' => __('Datum', 'palmcode-child'),
            'value' => esc_html($booking_date),
        );
    }
    
    // Add booking time
    if (!empty($booking_time)) {
        $item_data[] = array(
            'key' => __('Zeit', 'palmcode-child'),
            'value' => esc_html($booking_time),
        );
    }
    
    // Add booking capacity
    if (!empty($booking_capacity)) {
        $item_data[] = array(
            'key' => __('Kapazität', 'palmcode-child'),
            'value' => esc_html($booking_capacity),
        );
    }
    
    return $item_data;
}

/**
 * Ensure cart item data persists
 */
add_filter('woocommerce_get_cart_item_from_session', 'pc_get_cart_item_from_session', 10, 3);
function pc_get_cart_item_from_session($cart_item, $values, $cart_item_key) {
    if (isset($values['pc_booking_date'])) {
        $cart_item['pc_booking_date'] = $values['pc_booking_date'];
    }
    if (isset($values['pc_booking_date_format'])) {
        $cart_item['pc_booking_date_format'] = $values['pc_booking_date_format'];
    }
    if (isset($values['pc_booking_time'])) {
        $cart_item['pc_booking_time'] = $values['pc_booking_time'];
    }
    if (isset($values['pc_booking_start_time'])) {
        $cart_item['pc_booking_start_time'] = $values['pc_booking_start_time'];
    }
    if (isset($values['pc_booking_end_time'])) {
        $cart_item['pc_booking_end_time'] = $values['pc_booking_end_time'];
    }
    // Use quantity as capacity
    if (isset($values['quantity'])) {
        $cart_item['pc_booking_capacity'] = $values['quantity'];
    } else    // Use quantity as capacity
    if (isset($values['quantity'])) {
        $cart_item['pc_booking_capacity'] = $values['quantity'];
    } elseif (isset($values['pc_booking_capacity'])) {
        $cart_item['pc_booking_capacity'] = $values['pc_booking_capacity'];
    }
    return $cart_item;
}

/**
 * Save booking data to order item meta
 */
add_action('woocommerce_checkout_create_order_line_item', 'pc_save_booking_data_to_order', 10, 4);
function pc_save_booking_data_to_order($item, $cart_item_key, $values, $order) {
    if (isset($values['pc_booking_date'])) {
        $item->add_meta_data('pc_booking_date', $values['pc_booking_date']);
    }
    
    // Save date format as hidden meta (starts with underscore to hide from display)
    if (isset($values['pc_booking_date_format'])) {
        $item->add_meta_data('_pc_booking_date_format', $values['pc_booking_date_format']);
    }
    
    if (isset($values['pc_booking_time'])) {
        $item->add_meta_data('pc_booking_time', $values['pc_booking_time']);
    }
    
    // Save start/end time as hidden meta (starts with underscore to hide from display)
    if (isset($values['pc_booking_start_time'])) {
        $item->add_meta_data('_pc_booking_start_time', $values['pc_booking_start_time']);
    }
    
    if (isset($values['pc_booking_end_time'])) {
        $item->add_meta_data('_pc_booking_end_time', $values['pc_booking_end_time']);
    }
    
    // Use quantity as capacity
    if (isset($values['quantity'])) {
        $item->add_meta_data('pc_booking_capacity', $values['quantity']);
        $item->add_meta_data('_product_id', $values['product_id']);
    } elseif (isset($values['pc_booking_capacity'])) {
        $item->add_meta_data('pc_booking_capacity', $values['pc_booking_capacity']);
        $item->add_meta_data('_product_id', $values['product_id']);
    }
}

/**
 * Format booking meta keys for display on order pages
 */
add_filter('woocommerce_order_item_display_meta_key', 'pc_format_booking_meta_key', 10, 3);
function pc_format_booking_meta_key($display_key, $meta, $item) {
    // Map technical keys to user-friendly labels
    $key_mapping = array(
        'pc_booking_date' => __('Datum', 'palmcode-child'),
        'pc_booking_time' => __('Zeit', 'palmcode-child'),
        'pc_booking_capacity' => __('Kapazität', 'palmcode-child'),
    );
    
    if (isset($key_mapping[$meta->key])) {
        return $key_mapping[$meta->key];
    }
    
    return $display_key;
}

/**
 * Format booking meta values for display on order pages
 * Converts date format to dd.mm.YYYY
 */
add_filter('woocommerce_order_item_display_meta_value', 'pc_format_booking_meta_value', 10, 3);
function pc_format_booking_meta_value($display_value, $meta, $item) {
    // Only format booking date
    if ($meta->key === 'pc_booking_date') {
        // Try to get the date format from hidden meta
        $date_format = $item->get_meta('_pc_booking_date_format') ?: $item->get_meta('pc_booking_date_format');
        
        // Format date using our helper function
        $formatted_date = pc_format_booking_date_display($date_format, $display_value);
        
        if (!empty($formatted_date)) {
            return $formatted_date;
        }
    }
    
    return $display_value;
}

/**
 * Hide technical booking meta fields from order display
 */
add_filter('woocommerce_order_item_get_formatted_meta_data', 'pc_hide_technical_booking_meta', 10, 2);
function pc_hide_technical_booking_meta($formatted_meta, $item) {
    // Fields to hide (technical/internal fields)
    $hidden_fields = array(
        'pc_booking_date_format',
        '_pc_booking_date_format',
        'pc_booking_start_time',
        '_pc_booking_start_time',
        'pc_booking_end_time',
        '_pc_booking_end_time',
        '_product_id',
    );
    
    foreach ($formatted_meta as $meta_id => $meta) {
        if (in_array($meta->key, $hidden_fields)) {
            unset($formatted_meta[$meta_id]);
        }
    }
    
    return $formatted_meta;
}

