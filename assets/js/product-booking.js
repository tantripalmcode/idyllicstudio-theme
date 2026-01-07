(function ($) {
    'use strict';

    /**
     * Initialize Product Booking System
     */
    function initProductBooking() {
        const $bookingDateField = $('#pc-booking-date');
        const $bookingTimeField = $('#pc-booking-time');
        const $bookingTimeValue = $('#pc-booking-time-value');
        const $timeDropdown = $('#pc-time-dropdown');
        const $timeGroup = $('.pc-booking-time-group');
        const $availabilityInfo = $('.pc-availability-info');
        const $loadingIcon = $('.pc-booking-loading');
        const $productId = $('#pc-product-id').val();
        const $addToCartButton = $('button.single_add_to_cart_button, .single_add_to_cart_button');
        const $quantityField = $('input[name="quantity"]');

        if (!$bookingDateField.length || !$productId) {
            return;
        }

        let availableDates = [];
        let closeDates = [];
        let availabilityMode = 'weekly';
        let lastTimeOptionsHtml = ''; // Store last time options to rebuild dropdown if needed

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
                dateFormat: 'MM dd, yy', // Use 'yy' instead of 'yyyy' to avoid double year issue
                beforeShowDay: function (date) {
                    const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                    const dayOfWeek = date.getDay();
                    let classes = '';
                    let isSelectable = true;

                    // Check if date is closed
                    if (closeDates && closeDates.indexOf(dateString) !== -1) {
                        return [false, 'date-closed', 'Closed'];
                    }

                    // Check if this is the selected date
                    const selectedDateFormat = $('#pc-booking-date-format').val();
                    console.log(selectedDateFormat);
                    if (selectedDateFormat && selectedDateFormat.length === 8) {
                        // Convert YYYYMMDD to yy-mm-dd format for comparison
                        const year = selectedDateFormat.substring(0, 4);
                        const month = selectedDateFormat.substring(4, 6);
                        const day = selectedDateFormat.substring(6, 8);
                        const selectedDateString = year + '-' + month + '-' + day;
                        
                        if (dateString === selectedDateString) {
                            classes = 'pc-date-selected';
                        }
                    }

                    // For weekly mode, only allow Tuesday (2) through Sunday (0)
                    if (availabilityMode === 'weekly') {
                        // Sunday is 0, Tuesday is 2, etc.
                        // Allow: Tuesday (2), Wednesday (3), Thursday (4), Friday (5), Saturday (6), Sunday (0)
                        isSelectable = dayOfWeek === 0 || (dayOfWeek >= 2 && dayOfWeek <= 6);
                        return [isSelectable, classes, ''];
                    } else {
                        // For specific dates mode, check available dates
                        if (availableDates && availableDates.length > 0 && availableDates !== 'everyday') {
                            isSelectable = availableDates.indexOf(dateString) !== -1;
                        }
                        return [isSelectable, classes, ''];
                    }
                },
                onSelect: function (dateText, inst) {
                    // Parse the datepicker format (MM dd, yy) and format with moment for display
                    // dateText will be like "December 23, 25" from datepicker
                    // We need to convert it to full format
                    const datepickerDate = $.datepicker.parseDate('MM dd, yy', dateText);
                    const date = moment(datepickerDate);
                    let formattedDate = '';
                    let displayDate = '';
                    
                    if (date.isValid()) {
                        formattedDate = date.format('YYYYMMDD');
                        // Format display date as dd.mm.YYYY
                        displayDate = date.format('DD.MM.YYYY');
                    }

                    // Set visible field with properly formatted date (dd.mm.YYYY)
                    $bookingDateField.val(displayDate);
                    $('#pc-booking-date-format').val(formattedDate);
                    
                    // Ensure the name attribute is set for form submission
                    $bookingDateField.attr('name', 'pc_booking_date');
                    $('#pc-booking-date-format').attr('name', 'pc_booking_date_format');

                    // Refresh datepicker to show selected date with active class
                    // Close and reopen to trigger beforeShowDay for all dates
                    $bookingDateField.datepicker('hide');
                    
                    // Reset time
                    $bookingTimeField.val('').removeClass('pc-selected');
                    $bookingTimeValue.val('');
                    $timeDropdown.html('').removeClass('active');
                    $timeGroup.hide();
                    $availabilityInfo.html('');

                    // Show loading icon
                    $loadingIcon.show();

                    // Check availability
                    checkAvailability(formattedDate, displayDate, '');
                },
                beforeShow: function(input, inst) {
                    // Refresh datepicker when it opens to ensure selected date is highlighted
                    // The beforeShowDay callback will be called for each date and add the active class
                    return true;
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
                    selected_date: dateText ? (() => {
                        // Try parsing with dd.mm.YYYY format first, then fallback to other formats
                        let date = moment(dateText, 'DD.MM.YYYY', true);
                        if (!date.isValid()) {
                            date = moment(dateText, 'MMMM D, YY', 'de');
                        }
                        if (!date.isValid()) {
                            date = moment(dateText, 'MMMM DD, YYYY', 'de');
                        }
                        return date.isValid() ? date.format('YYYY-MM-DD') : '';
                    })() : '',
                    formatted_date: formattedDate,
                    time: timeSelected
                },
                beforeSend: function () {
                    $addToCartButton.prop('disabled', true);
                    // Only hide time group if no time is selected yet
                    if (!timeSelected) {
                        $timeGroup.hide();
                        // Show loading icon when checking availability for time slots
                        $loadingIcon.show();
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

                        // Hide loading icon
                        $loadingIcon.hide();

                        // Show time field if needed (when no time is selected yet)
                        if (data.show_time && data.time_options && !timeSelected) {
                            buildCustomTimeDropdown(data.time_options);
                            $bookingTimeField.prop('disabled', false);
                            $timeGroup.show();
                        } else if (timeSelected) {
                            // If time is already selected, keep the time group visible
                            $timeGroup.show();
                            $bookingTimeField.prop('disabled', false);
                            
                            // Only rebuild dropdown if it's empty or if we have new time_options
                            const dropdownHasOptions = $timeDropdown.children('.pc-time-option').length > 0;
                            
                            if (data.time_options) {
                                // Store new options
                                lastTimeOptionsHtml = data.time_options;
                                
                                // Only rebuild if dropdown is empty or options have changed
                                if (!dropdownHasOptions) {
                                    buildCustomTimeDropdown(data.time_options, timeSelected);
                                } else {
                                    // Just update the selected state without rebuilding
                                    $timeDropdown.find('.pc-time-option').removeClass('selected');
                                    $timeDropdown.find('.pc-time-option[data-value="' + timeSelected + '"]').addClass('selected');
                                }
                                
                                // Update display field with selected time text
                                const $temp = $('<div>').html(data.time_options);
                                const $selectedOption = $temp.find('option[value="' + timeSelected + '"]');
                                if ($selectedOption.length) {
                                    $bookingTimeField.val($selectedOption.text()).addClass('pc-selected');
                                }
                            } else if (!dropdownHasOptions && lastTimeOptionsHtml) {
                                // Only rebuild if dropdown is empty and we have stored options
                                buildCustomTimeDropdown(lastTimeOptionsHtml, timeSelected);
                            } else if (dropdownHasOptions) {
                                // Just update the selected state if dropdown already has options
                                $timeDropdown.find('.pc-time-option').removeClass('selected');
                                $timeDropdown.find('.pc-time-option[data-value="' + timeSelected + '"]').addClass('selected');
                            }
                        }

                        // Show availability info only when both date and time are selected
                        if (data.available_capacity_text && timeSelected) {
                            $availabilityInfo.html('<span class="pc-availability-text">' + data.available_capacity_text + '</span>');
                        } else {
                            $availabilityInfo.html('');
                        }

                        // Enable add to cart if date and time are filled (quantity is handled by WooCommerce)
                        if (formattedDate && timeSelected) {
                            $addToCartButton.prop('disabled', false);
                        }
                    } else {
                        // Hide loading icon on error
                        $loadingIcon.hide();
                        if (response.data && response.data.message) {
                            $availabilityInfo.html('<span class="pc-error-text">' + response.data.message + '</span>');
                        }
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    // Hide loading icon on error
                    $loadingIcon.hide();
                    $availabilityInfo.html('<span class="pc-error-text">Fehler beim Laden der Verfügbarkeit.</span>');
                }
            });
        }

        /**
         * Build custom time dropdown from HTML options
         */
        function buildCustomTimeDropdown(timeOptionsHtml, selectedValue = '') {
            // Store the time options HTML for later use
            lastTimeOptionsHtml = timeOptionsHtml;
            
            // Parse the HTML to extract option values and text
            const $temp = $('<div>').html(timeOptionsHtml);
            const $options = $temp.find('option');
            
            $timeDropdown.html('');
            
            $options.each(function() {
                const $option = $(this);
                const value = $option.attr('value');
                const text = $option.text();
                
                if (value === '') {
                    // Skip placeholder option
                    return;
                }
                
                const $timeOption = $('<div>')
                    .addClass('pc-time-option')
                    .attr('data-value', value)
                    .text(text);
                
                if (value === selectedValue) {
                    $timeOption.addClass('selected');
                }
                
                $timeOption.on('click', function(e) {
                    e.stopPropagation();
                    const selectedTime = $(this).attr('data-value');
                    $bookingTimeField.val(text).addClass('pc-selected');
                    $bookingTimeValue.val(selectedTime);
                    $timeDropdown.removeClass('active');
                    
                    // Remove selected class from all options
                    $timeDropdown.find('.pc-time-option').removeClass('selected');
                    // Add selected class to clicked option
                    $(this).addClass('selected');
                    
                    // Trigger availability check
                    const formattedDate = $('#pc-booking-date-format').val();
                    const dateText = $bookingDateField.val();
                    if (formattedDate) {
                        checkAvailability(formattedDate, dateText, selectedTime);
                    }
                });
                
                $timeDropdown.append($timeOption);
            });
        }

        /**
         * Handle time field click to toggle dropdown
         */
        $bookingTimeField.on('click', function(e) {
            if ($(this).prop('disabled')) {
                return;
            }
            e.stopPropagation();
            
            // If dropdown is empty but we have stored options, rebuild it
            if ($timeDropdown.children().length === 0 && lastTimeOptionsHtml) {
                const currentSelected = $bookingTimeValue.val();
                buildCustomTimeDropdown(lastTimeOptionsHtml, currentSelected);
            }
            
            // Toggle dropdown visibility
            $timeDropdown.toggleClass('active');
        });

        /**
         * Close dropdown when clicking outside
         */
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.pc-booking-time-wrapper').length) {
                $timeDropdown.removeClass('active');
            }
        });

        /**
         * Handle time field change (for form validation)
         */
        $bookingTimeValue.on('change', function () {
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
            const time = $bookingTimeValue.val();
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
                    // Try parsing with dd.mm.YYYY format first, then fallback to other formats
                    let date = moment(dateText, 'DD.MM.YYYY', true);
                    if (!date.isValid()) {
                        date = moment(dateText, 'MMMM D, YY', 'de');
                    }
                    if (!date.isValid()) {
                        date = moment(dateText, 'MMMM DD, YYYY', 'de');
                    }
                    if (date.isValid()) {
                        $('#pc-booking-date-format').val(date.format('YYYYMMDD'));
                    }
                }
            }

            // Ensure all fields have name attributes for form submission
            $('#pc-booking-date').attr('name', 'pc_booking_date');
            $('#pc-booking-date-format').attr('name', 'pc_booking_date_format');
            $bookingTimeValue.attr('name', 'pc_booking_time');

            // Double-check all values are present
            if (!$('#pc-booking-date').val() || !$('#pc-booking-date-format').val() || !$bookingTimeValue.val() || !$quantityField.val()) {
                e.preventDefault();
                alert('Bitte füllen Sie alle Felder aus.');
                return false;
            }

            // Log values for debugging
            console.log('Submitting booking data:', {
                date: $('#pc-booking-date').val(),
                dateFormat: $('#pc-booking-date-format').val(),
                time: $bookingTimeValue.val(),
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

