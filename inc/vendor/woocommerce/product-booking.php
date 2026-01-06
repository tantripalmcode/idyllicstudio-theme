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
    $enable_course_availability = carbon_get_post_meta($product->get_id(), 'pc_enable_course_availability');
    
    if (!$enable_course_availability) {
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
 * Get custom display price for a product
 */
function pc_get_display_price($product_id) {
    $display_price = carbon_get_post_meta($product_id, 'pc_display_price');
    if (!empty($display_price) && is_numeric($display_price)) {
        return floatval($display_price);
    }
    return false;
}

/**
 * Get static payment note message
 */
function pc_get_payment_note() {
    return __('This is a deposit payment. The remaining balance will be paid on-site.', 'palmcode-child');
}

/**
 * Override product price display on single product page, cart, and checkout
 * Uses custom display price field for display, but regular WooCommerce price is still used for actual payment calculations
 */
add_filter('woocommerce_get_price_html', 'pc_override_product_price_display', 10, 2);
function pc_override_product_price_display($price_html, $product) {
    // Skip on shop, category, and tag pages
    if (is_shop() || is_product_category() || is_product_tag() || is_product_taxonomy()) {
        return $price_html;
    }
    
    // Get custom display price
    $display_price = pc_get_display_price($product->get_id());
    
    // If display price is set, use it instead of regular price
    if ($display_price !== false) {
        // Format the price using WooCommerce price formatting
        $formatted_price = wc_price($display_price);
        
        // Add price suffix if product has one
        $price_suffix = $product->get_price_suffix();
        if ($price_suffix) {
            $formatted_price .= $price_suffix;
        }
        
        return $formatted_price;
    }
    
    // If no custom display price, return original price
    return $price_html;
}

/**
 * Add payment note after price on single product page
 */
add_action('woocommerce_single_product_summary', 'pc_display_payment_note_on_product', 25);
function pc_display_payment_note_on_product() {
    global $product;
    
    if (!$product) {
        return;
    }
    
    $display_price = pc_get_display_price($product->get_id());
    $regular_price = $product->get_regular_price();
    
    // Only show if display price is different from regular price
    if ($display_price !== false && $display_price != $regular_price) {
        $payment_note = pc_get_payment_note();
        $deposit_amount = wc_price($regular_price);
        $full_price = wc_price($display_price);
        
        echo '<div class="pc-payment-info" style="margin-top: 15px; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #0073aa; border-radius: 4px;">';
        echo '<p style="margin: 0 0 8px 0; font-weight: 600; color: #333;">' . __('Payment Information', 'palmcode-child') . '</p>';
        echo '<p style="margin: 0 0 8px 0; color: #555;">';
        echo sprintf(
            __('Full course price: %s | Deposit required at booking: %s', 'palmcode-child'),
            '<strong>' . $full_price . '</strong>',
            '<strong style="color: #d63638;">' . $deposit_amount . '</strong>'
        );
        echo '</p>';
        echo '<p style="margin: 0; font-size: 0.9em; color: #666; font-style: italic;">' . esc_html($payment_note) . '</p>';
        echo '</div>';
    }
}

/**
 * Override cart item price display (unit price)
 * Shows custom display price but actual payment uses regular WooCommerce price
 */
add_filter('woocommerce_cart_item_price', 'pc_override_cart_item_price_display', 10, 3);
function pc_override_cart_item_price_display($price_html, $cart_item, $cart_item_key) {
    $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
    
    if (!$product_id) {
        return $price_html;
    }
    
    // Get custom display price
    $display_price = pc_get_display_price($product_id);
    
    // If display price is set, show it with deposit info
    if ($display_price !== false) {
        $product = wc_get_product($product_id);
        if ($product) {
            $regular_price = $product->get_regular_price();
            $formatted_price = wc_price($display_price);
            
            // Only show deposit note if prices differ
            if ($display_price != $regular_price) {
                $formatted_price .= '<br><small style="color: #d63638; font-weight: 600;">' . 
                    sprintf(__('Deposit: %s', 'palmcode-child'), wc_price($regular_price)) . 
                    '</small>';
            }
            
            return $formatted_price;
        }
        
        return wc_price($display_price);
    }
    
    return $price_html;
}

/**
 * Override cart item subtotal display
 * Shows custom display price multiplied by quantity, but actual payment uses regular WooCommerce price
 */
add_filter('woocommerce_cart_item_subtotal', 'pc_override_cart_item_subtotal_display', 10, 3);
function pc_override_cart_item_subtotal_display($subtotal_html, $cart_item, $cart_item_key) {
    $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
    $quantity = isset($cart_item['quantity']) ? $cart_item['quantity'] : 1;
    
    if (!$product_id) {
        return $subtotal_html;
    }
    
    // Get custom display price
    $display_price = pc_get_display_price($product_id);
    
    // If display price is set, calculate subtotal using display price
    if ($display_price !== false) {
        $display_subtotal = $display_price * $quantity;
        $formatted_price = wc_price($display_subtotal);
        
        // Get actual payment amount (regular price)
        $product = wc_get_product($product_id);
        if ($product) {
            $regular_price = $product->get_regular_price();
            $actual_payment = floatval($regular_price) * $quantity;
            
            // Only show note if prices differ
            if ($display_subtotal != $actual_payment) {
                $formatted_price .= '<br><small style="color: #d63638; font-weight: 600;">' . 
                    sprintf(__('Deposit: %s', 'palmcode-child'), wc_price($actual_payment)) . 
                    '</small>';
            }
        }
        
        return $formatted_price;
    }
    
    return $subtotal_html;
}

/**
 * Add deposit information notice in cart
 */
add_action('woocommerce_before_cart_table', 'pc_display_cart_deposit_notice', 10);
function pc_display_cart_deposit_notice() {
    $has_deposit_products = false;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
        if ($product_id) {
            $display_price = pc_get_display_price($product_id);
            $product = wc_get_product($product_id);
            
            if ($display_price !== false && $product) {
                $regular_price = $product->get_regular_price();
                if ($display_price != $regular_price) {
                    $has_deposit_products = true;
                    break;
                }
            }
        }
    }
    
    if ($has_deposit_products) {
        echo '<div class="woocommerce-info pc-deposit-notice" style="margin-bottom: 20px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #0073aa; border-radius: 4px;">';
        echo '<p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">' . __('Payment Information', 'palmcode-child') . '</p>';
        echo '<p style="margin: 0; color: #555;">';
        echo __('The prices shown below are the full course prices. You will pay a deposit amount at checkout (shown in red). The remaining balance will be paid on-site.', 'palmcode-child');
        echo '</p>';
        echo '</div>';
    }
}

/**
 * Add deposit information notice in checkout
 */
add_action('woocommerce_checkout_before_order_review_heading', 'pc_display_checkout_deposit_notice', 10);
function pc_display_checkout_deposit_notice() {
    $has_deposit_products = false;
    
    foreach (WC()->cart->get_cart() as $cart_item) {
        $product_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : 0;
        if ($product_id) {
            $display_price = pc_get_display_price($product_id);
            $product = wc_get_product($product_id);
            
            if ($display_price !== false && $product) {
                $regular_price = $product->get_regular_price();
                if ($display_price != $regular_price) {
                    $has_deposit_products = true;
                    break;
                }
            }
        }
    }
    
    if ($has_deposit_products) {
        echo '<div class="woocommerce-info pc-deposit-notice" style="margin-bottom: 20px; padding: 15px; background-color: #e7f3ff; border-left: 4px solid #0073aa; border-radius: 4px;">';
        echo '<p style="margin: 0 0 10px 0; font-weight: 600; color: #333;">' . __('Payment Information', 'palmcode-child') . '</p>';
        echo '<p style="margin: 0; color: #555;">';
        echo __('You are paying a deposit amount. The remaining balance will be paid on-site.', 'palmcode-child');
        echo '</p>';
        echo '</div>';
    }
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
    $enable_course_availability = carbon_get_post_meta($product_id, 'pc_enable_course_availability');
    
    if (!$enable_course_availability) {
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

/**
 * Google Calendar Integration
 * Automatically creates calendar events when orders are placed
 */

// Handle OAuth callback and token storage
add_action('admin_init', 'pc_handle_google_calendar_oauth');
function pc_handle_google_calendar_oauth() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Handle authorization
    if (isset($_GET['pc_gcal_authorize']) && $_GET['pc_gcal_authorize'] == '1') {
        pc_init_google_calendar_oauth();
    }
    
    // Handle OAuth callback
    if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'pc_gcal_oauth') {
        pc_complete_google_calendar_oauth($_GET['code']);
    }
    
    // Handle revoke
    if (isset($_GET['pc_gcal_revoke']) && $_GET['pc_gcal_revoke'] == '1') {
        delete_option('pc_gcal_access_token');
        delete_option('pc_gcal_refresh_token');
        delete_option('pc_gcal_token_expires');
        wp_redirect(add_query_arg('revoked', '1', pc_get_gcal_settings_page_url()));
        exit;
    }
}

/**
 * Initialize Google Calendar OAuth flow
 */
function pc_init_google_calendar_oauth() {
    require_once get_stylesheet_directory() . '/vendor/autoload.php';
    
    $client_id = get_option('pc_gcal_client_id', '');
    $client_secret = get_option('pc_gcal_client_secret', '');
    
    if (empty($client_id) || empty($client_secret)) {
        wp_die(__('Please enter Client ID and Client Secret first.', 'palmcode-child'));
    }
    
    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri(pc_get_gcal_settings_page_url());
    $client->addScope(Google_Service_Calendar::CALENDAR);
    $client->setAccessType('offline');
    $client->setPrompt('consent');
    $client->setState('pc_gcal_oauth');
    
    $auth_url = $client->createAuthUrl();
    wp_redirect($auth_url);
    exit;
}

/**
 * Complete OAuth flow and store tokens
 */
function pc_complete_google_calendar_oauth($code) {
    require_once get_stylesheet_directory() . '/vendor/autoload.php';
    
    $client_id = get_option('pc_gcal_client_id', '');
    $client_secret = get_option('pc_gcal_client_secret', '');
    
    if (empty($client_id) || empty($client_secret)) {
        wp_die(__('Client ID and Client Secret not configured.', 'palmcode-child'));
    }
    
    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->setRedirectUri(pc_get_gcal_settings_page_url());
    $client->addScope(Google_Service_Calendar::CALENDAR);
    
    try {
        $token = $client->fetchAccessTokenWithAuthCode($code);
        
        if (isset($token['error'])) {
            wp_die(__('Error: ' . $token['error_description'], 'palmcode-child'));
        }
        
        // Store tokens
        update_option('pc_gcal_access_token', $token['access_token']);
        if (isset($token['refresh_token'])) {
            update_option('pc_gcal_refresh_token', $token['refresh_token']);
        }
        if (isset($token['expires_in'])) {
            update_option('pc_gcal_token_expires', time() + $token['expires_in']);
        }
        
        wp_redirect(add_query_arg('authorized', '1', pc_get_gcal_settings_page_url()));
        exit;
    } catch (Exception $e) {
        wp_die(__('Error: ' . $e->getMessage(), 'palmcode-child'));
    }
}

/**
 * Get authenticated Google Calendar client
 */
function pc_get_google_calendar_client() {
    require_once get_stylesheet_directory() . '/vendor/autoload.php';
    
    $client_id = get_option('pc_gcal_client_id', '');
    $client_secret = get_option('pc_gcal_client_secret', '');
    
    if (empty($client_id) || empty($client_secret)) {
        return false;
    }
    
    $client = new Google_Client();
    $client->setClientId($client_id);
    $client->setClientSecret($client_secret);
    $client->addScope(Google_Service_Calendar::CALENDAR);
    $client->setAccessType('offline');
    
    // Get stored tokens
    $access_token = get_option('pc_gcal_access_token');
    $refresh_token = get_option('pc_gcal_refresh_token');
    $token_expires = get_option('pc_gcal_token_expires');
    
    if (empty($access_token)) {
        return false;
    }
    
    // Set access token
    $client->setAccessToken(array(
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => $token_expires ? ($token_expires - time()) : 3600,
    ));
    
    // Refresh token if expired
    if ($token_expires && time() >= $token_expires) {
        if ($refresh_token) {
            try {
                $new_token = $client->refreshToken($refresh_token);
                if (isset($new_token['access_token'])) {
                    update_option('pc_gcal_access_token', $new_token['access_token']);
                    if (isset($new_token['refresh_token'])) {
                        update_option('pc_gcal_refresh_token', $new_token['refresh_token']);
                    }
                    if (isset($new_token['expires_in'])) {
                        update_option('pc_gcal_token_expires', time() + $new_token['expires_in']);
                    }
                    $client->setAccessToken($new_token);
                }
            } catch (Exception $e) {
                error_log('Google Calendar token refresh failed: ' . $e->getMessage());
                return false;
            }
        } else {
            return false;
        }
    }
    
    return $client;
}

/**
 * Create Google Calendar event from order booking
 */
function pc_create_google_calendar_event($order_id) {
    $order = wc_get_order($order_id);
    if (!$order) {
        return false;
    }
    
    $client = pc_get_google_calendar_client();
    if (!$client) {
        error_log('Google Calendar: Client not authenticated');
        return false;
    }
    
    $service = new Google_Service_Calendar($client);
    $calendar_id = get_option('pc_gcal_calendar_id', 'primary');
    
    $events_created = 0;
    $events_failed = 0;
    $event_ids = array();
    
    // Process each order item
    foreach ($order->get_items() as $item_id => $item) {
        $product_id = $item->get_product_id();
        
        // Check if this item has booking data
        $booking_date_format = $item->get_meta('_pc_booking_date_format') ?: $item->get_meta('pc_booking_date_format');
        $booking_start_time = $item->get_meta('_pc_booking_start_time') ?: $item->get_meta('pc_booking_start_time');
        $booking_end_time = $item->get_meta('_pc_booking_end_time') ?: $item->get_meta('pc_booking_end_time');
        $booking_time = $item->get_meta('pc_booking_time');
        $booking_capacity = $item->get_meta('pc_booking_capacity');
        
        if (empty($booking_date_format)) {
            continue; // Skip items without booking data
        }
        
        // Parse date from YYYYMMDD format
        $date_obj = DateTime::createFromFormat('Ymd', $booking_date_format);
        if (!$date_obj) {
            error_log('Google Calendar: Invalid date format: ' . $booking_date_format);
            continue;
        }
        
        // Get product name
        $product_name = $item->get_name();
        
        // Get customer info
        $customer_name = $order->get_formatted_billing_full_name();
        $customer_email = $order->get_billing_email();
        $customer_phone = $order->get_billing_phone();
        $order_number = $order->get_order_number();
        
        // Parse time
        $start_datetime = null;
        $end_datetime = null;
        $is_all_day = false;
        
        if (!empty($booking_start_time) && !empty($booking_end_time)) {
            $start_datetime = DateTime::createFromFormat('Y-m-d H:i', $date_obj->format('Y-m-d') . ' ' . $booking_start_time, new DateTimeZone('Europe/Berlin'));
            $end_datetime = DateTime::createFromFormat('Y-m-d H:i', $date_obj->format('Y-m-d') . ' ' . $booking_end_time, new DateTimeZone('Europe/Berlin'));
        } elseif (!empty($booking_time)) {
            // Parse time from format "HH:MM - HH:MM"
            $time_parts = explode(' - ', $booking_time);
            if (count($time_parts) === 2) {
                $start_time = trim($time_parts[0]);
                $end_time = trim($time_parts[1]);
                $start_datetime = DateTime::createFromFormat('Y-m-d H:i', $date_obj->format('Y-m-d') . ' ' . $start_time, new DateTimeZone('Europe/Berlin'));
                $end_datetime = DateTime::createFromFormat('Y-m-d H:i', $date_obj->format('Y-m-d') . ' ' . $end_time, new DateTimeZone('Europe/Berlin'));
            }
        }
        
        // If no time, create all-day event
        if (!$start_datetime || !$end_datetime) {
            $is_all_day = true;
            $start_datetime = clone $date_obj;
            $start_datetime->setTime(0, 0, 0);
            $start_datetime->setTimezone(new DateTimeZone('Europe/Berlin'));
            $end_datetime = clone $start_datetime;
            $end_datetime->modify('+1 day');
        }
        
        // Build event details
        $event_details = sprintf(
            __('Order #%s - %s', 'palmcode-child') . "\n\n",
            $order_number,
            $product_name
        );
        
        $event_details .= __('Customer Information:', 'palmcode-child') . "\n";
        $event_details .= __('Name:', 'palmcode-child') . ' ' . $customer_name . "\n";
        $event_details .= __('Email:', 'palmcode-child') . ' ' . $customer_email . "\n";
        if ($customer_phone) {
            $event_details .= __('Phone:', 'palmcode-child') . ' ' . $customer_phone . "\n";
        }
        
        if (!empty($booking_capacity)) {
            $event_details .= "\n" . __('Capacity:', 'palmcode-child') . ' ' . $booking_capacity . "\n";
        }
        
        $event_details .= "\n" . __('Booking Date:', 'palmcode-child') . ' ' . $date_obj->format('d.m.Y');
        if (!empty($booking_time)) {
            $event_details .= ' ' . $booking_time;
        }
        
        // Create event
        $event = new Google_Service_Calendar_Event();
        $event->setSummary(sprintf(__('Booking: %s', 'palmcode-child'), $product_name));
        $event->setDescription($event_details);
        $event->setLocation(get_bloginfo('name'));
        
        // Set event time
        if ($is_all_day) {
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDate($start_datetime->format('Y-m-d'));
            $event->setStart($start);
            
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDate($end_datetime->format('Y-m-d'));
            $event->setEnd($end);
        } else {
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($start_datetime->format('Y-m-d\TH:i:s'));
            $start->setTimeZone('Europe/Berlin');
            $event->setStart($start);
            
            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($end_datetime->format('Y-m-d\TH:i:s'));
            $end->setTimeZone('Europe/Berlin');
            $event->setEnd($end);
        }
        
        // Add customer as attendee
        if (!empty($customer_email)) {
            $attendee = new Google_Service_Calendar_EventAttendee();
            $attendee->setEmail($customer_email);
            $attendee->setDisplayName($customer_name);
            $event->setAttendees(array($attendee));
        }
        
        try {
            $created_event = $service->events->insert($calendar_id, $event);
            
            // Store event ID in order meta for reference
            $order->update_meta_data('_pc_gcal_event_id_' . $item_id, $created_event->getId());
            $order->save();
            
            $event_ids[] = $created_event->getId();
            $events_created++;
            
            error_log('Google Calendar: Event created successfully for item ' . $item_id . ' - ' . $created_event->getId());
        } catch (Exception $e) {
            $events_failed++;
            error_log('Google Calendar: Failed to create event for item ' . $item_id . ' - ' . $e->getMessage());
        }
    }
    
    // Save order once after processing all items
    if ($events_created > 0) {
        $order->save();
    }
    
    // Log summary
    if ($events_created > 0) {
        error_log('Google Calendar: Created ' . $events_created . ' event(s) for order #' . $order->get_order_number());
    }
    if ($events_failed > 0) {
        error_log('Google Calendar: Failed to create ' . $events_failed . ' event(s) for order #' . $order->get_order_number());
    }
    
    // Return true if at least one event was created, false if all failed
    return $events_created > 0 ? $event_ids : false;
}

/**
 * Hook into order processing to create calendar events
 */
add_action('woocommerce_checkout_order_processed', 'pc_create_calendar_event_on_order', 20, 3);
function pc_create_calendar_event_on_order($order_id, $posted_data, $order) {
    // Only create events for orders with booking items
    $has_booking = false;
    foreach ($order->get_items() as $item) {
        $booking_date_format = $item->get_meta('_pc_booking_date_format') ?: $item->get_meta('pc_booking_date_format');
        if (!empty($booking_date_format)) {
            $has_booking = true;
            break;
        }
    }
    
    if ($has_booking) {
        pc_create_google_calendar_event($order_id);
    }
}


