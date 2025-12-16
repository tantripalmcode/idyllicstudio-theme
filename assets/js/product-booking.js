(function ($) {
    'use strict';

    /**
     * Initialize Product Booking System
     */
    function initProductBooking() {
        const $bookingDateField = $('#pc-booking-date');
        const $bookingTimeField = $('#pc-booking-time');
        const $timeGroup = $('.pc-booking-time-group');
        const $availabilityInfo = $('.pc-availability-info');
        const $productId = $('#pc-product-id').val();
        const $addToCartButton = $('button.single_add_to_cart_button, .single_add_to_cart_button');
        const $quantityField = $('input[name="quantity"]');

        if (!$bookingDateField.length || !$productId) {
            return;
        }

        let availableDates = [];
        let closeDates = [];
        let availabilityMode = 'weekly';

        /**
         * Get initial availability data
         */
        function getInitialAvailability() {
            $.ajax({
                type: 'POST',
                url: pcProductBooking.ajaxurl,
                data: {
                    action: 'pc_check_product_availability',
                    nonce: pcProductBooking.nonce,
                    product_id: $productId,
                    selected_date: '',
                    formatted_date: '',
                    time: ''
                },
                success: function (response) {
                    if (response.success && response.data) {
                        if (response.data.available_dates) {
                            availableDates = response.data.available_dates;
                        }
                        if (response.data.close_dates) {
                            closeDates = response.data.close_dates;
                        }
                        // Re-initialize datepicker with updated data
                        initializeDatepicker();
                    }
                }
            });
        }

        /**
         * Initialize Datepicker
         */
        function initializeDatepicker() {
            // Destroy existing datepicker if any
            if ($bookingDateField.hasClass('hasDatepicker')) {
                $bookingDateField.datepicker('destroy');
            }

            // Initialize datepicker
            $bookingDateField.datepicker({
                minDate: 0,
                dateFormat: 'MM dd, yyyy',
                beforeShowDay: function (date) {
                    const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                    const day = date.getDay();

                    // Check if date is closed
                    if (closeDates && closeDates.indexOf(dateString) !== -1) {
                        return [false, 'date-closed', 'Closed'];
                    }

                    // For weekly mode, only allow Tuesday (2) through Sunday (0)
                    if (availabilityMode === 'weekly') {
                        // Sunday is 0, Tuesday is 2, etc.
                        // Allow: Tuesday (2), Wednesday (3), Thursday (4), Friday (5), Saturday (6), Sunday (0)
                        return [day === 0 || (day >= 2 && day <= 6), ''];
                    } else {
                        // For specific dates mode, check available dates
                        if (availableDates && availableDates.length > 0 && availableDates !== 'everyday') {
                            return [availableDates.indexOf(dateString) !== -1, ''];
                        }
                        return [true, ''];
                    }
                },
                onSelect: function (dateText, inst) {
                    const date = moment(dateText, 'MMMM DD, YYYY', 'de');
                    let formattedDate = '';
                    
                    if (date.isValid()) {
                        formattedDate = date.format('YYYYMMDD');
                    }

                    // Set both visible and hidden fields
                    $bookingDateField.val(dateText);
                    $('#pc-booking-date-format').val(formattedDate);
                    
                    // Ensure the name attribute is set for form submission
                    $bookingDateField.attr('name', 'pc_booking_date');
                    $('#pc-booking-date-format').attr('name', 'pc_booking_date_format');

                    // Reset time
                    $bookingTimeField.val('').prop('disabled', true);
                    $timeGroup.hide();
                    $availabilityInfo.html('');

                    // Check availability
                    checkAvailability(formattedDate, dateText, '');
                }
            });
        }

        /**
         * Check availability via AJAX
         */
        function checkAvailability(formattedDate, dateText, timeSelected) {
            $.ajax({
                type: 'POST',
                url: pcProductBooking.ajaxurl,
                data: {
                    action: 'pc_check_product_availability',
                    nonce: pcProductBooking.nonce,
                    product_id: $productId,
                    selected_date: dateText ? moment(dateText, 'MMMM DD, YYYY', 'de').format('YYYY-MM-DD') : '',
                    formatted_date: formattedDate,
                    time: timeSelected
                },
                beforeSend: function () {
                    $addToCartButton.prop('disabled', true);
                    // Only hide time group if no time is selected yet
                    if (!timeSelected) {
                        $timeGroup.hide();
                    }
                    $availabilityInfo.html('');
                },
                success: function (response) {
                    if (response.success && response.data) {
                        const data = response.data;

                        // Update available dates, close dates, and availability mode
                        if (data.available_dates) {
                            availableDates = data.available_dates;
                        }
                        if (data.close_dates) {
                            closeDates = data.close_dates;
                        }
                        if (data.availability_mode) {
                            availabilityMode = data.availability_mode;
                        }

                        // Show time field if needed (when no time is selected yet)
                        if (data.show_time && data.time_options && !timeSelected) {
                            $bookingTimeField.html(data.time_options);
                            $bookingTimeField.prop('disabled', false);
                            $timeGroup.show();
                        } else if (timeSelected) {
                            // If time is already selected, keep the time group visible
                            $timeGroup.show();
                            $bookingTimeField.prop('disabled', false);
                        }

                        // Show availability info
                        if (data.available_capacity_text) {
                            $availabilityInfo.html('<span class="pc-availability-text">' + data.available_capacity_text + '</span>');
                        }

                        // Enable add to cart if date and time are filled (quantity is handled by WooCommerce)
                        if (formattedDate && timeSelected) {
                            $addToCartButton.prop('disabled', false);
                        }
                    } else {
                        if (response.data && response.data.message) {
                            $availabilityInfo.html('<span class="pc-error-text">' + response.data.message + '</span>');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    $availabilityInfo.html('<span class="pc-error-text">Fehler beim Laden der Verfügbarkeit.</span>');
                }
            });
        }

        /**
         * Handle time field change
         */
        $bookingTimeField.on('change', function () {
            const timeSelected = $(this).val();
            const formattedDate = $('#pc-booking-date-format').val();
            const dateText = $bookingDateField.val();

            if (timeSelected && formattedDate) {
                // Keep time group visible when time is selected
                $timeGroup.show();
                checkAvailability(formattedDate, dateText, timeSelected);
            } else {
                $availabilityInfo.html('');
                $addToCartButton.prop('disabled', true);
            }
        });

        /**
         * Validate and ensure data is captured before add to cart
         */
        $('form.cart').on('submit', function (e) {
            const date = $('#pc-booking-date').val();
            const dateFormat = $('#pc-booking-date-format').val();
            const time = $bookingTimeField.val();
            const quantity = $quantityField.val();

            // Validate all fields are filled
            if (!date || !dateFormat) {
                e.preventDefault();
                alert('Bitte wählen Sie ein Datum aus.');
                return false;
            }

            if (!time) {
                e.preventDefault();
                alert('Bitte wählen Sie eine Zeit aus.');
                return false;
            }

            if (!quantity || parseInt(quantity) <= 0) {
                e.preventDefault();
                alert('Bitte wählen Sie eine Menge aus.');
                return false;
            }

            // Ensure all hidden fields are set before submission
            if (!$('#pc-booking-date-format').val()) {
                // Try to get date format from the visible date field
                const dateText = $('#pc-booking-date').val();
                if (dateText) {
                    const date = moment(dateText, 'MMMM DD, YYYY', 'de');
                    if (date.isValid()) {
                        $('#pc-booking-date-format').val(date.format('YYYYMMDD'));
                    }
                }
            }

            // Ensure all fields have name attributes for form submission
            $('#pc-booking-date').attr('name', 'pc_booking_date');
            $('#pc-booking-date-format').attr('name', 'pc_booking_date_format');
            $bookingTimeField.attr('name', 'pc_booking_time');

            // Double-check all values are present
            if (!$('#pc-booking-date').val() || !$('#pc-booking-date-format').val() || !$bookingTimeField.val() || !$quantityField.val()) {
                e.preventDefault();
                alert('Bitte füllen Sie alle Felder aus.');
                return false;
            }

            // Log values for debugging
            console.log('Submitting booking data:', {
                date: $('#pc-booking-date').val(),
                dateFormat: $('#pc-booking-date-format').val(),
                time: $bookingTimeField.val(),
                quantity: $quantityField.val()
            });
        });

        // Get initial availability and initialize datepicker
        getInitialAvailability();
    }


    /**
     * Initialize when document is ready
     */
    $(document).ready(function () {
        initProductBooking();
    });

    /**
     * Re-initialize on AJAX complete (for dynamic content)
     */
    $(document).on('wc_fragment_refresh', function () {
        initProductBooking();
    });

})(jQuery);

