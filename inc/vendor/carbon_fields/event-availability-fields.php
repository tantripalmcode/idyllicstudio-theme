<?php
defined('ABSPATH') || exit;

use Carbon_Fields\Container;
use Carbon_Fields\Field;

/**
 * Register Event Availability Fields for WooCommerce Products
 */
if (!function_exists('pc_register_event_availability_fields')) {
    function pc_register_event_availability_fields()
    {
        Container::make('post_meta', __('Event Availability', 'palmcode-child'))
            ->where('post_type', '=', 'product')
            ->add_fields(array(
                // Display Price - shown on single product page
                Field::make('text', 'pc_display_price', __('Display Price (Full Course Price)', 'palmcode-child'))
                    ->set_attribute('type', 'number')
                    ->set_attribute('step', '0.01')
                    ->set_attribute('min', '0')
                    ->set_help_text('Full course price to display to customers. The regular WooCommerce price below will be used as the minimum deposit/payment amount at booking.'),

                // Global Capacity - applies to all time slots
                Field::make('text', 'pc_event_capacity', __('Event Capacity', 'palmcode-child'))
                    ->set_attribute('type', 'number')
                    ->set_attribute('min', 1)
                    ->set_help_text('Maximum number of bookings allowed per time slot. This applies globally to all time slots. Leave empty for unlimited capacity.'),

                // Availability Mode - Radio Field
                Field::make('radio', 'pc_availability_mode', __('Availability Mode', 'palmcode-child'))
                    ->set_options(array(
                        'weekly' => __('Weekly Schedule (Tuesday - Sunday)', 'palmcode-child'),
                        'specific_dates' => __('Specific Dates Only', 'palmcode-child'),
                    ))
                    ->set_default_value('weekly')
                    ->set_help_text('Choose how availability is managed for this event. Weekly allows recurring time slots for Tuesday through Sunday, while Specific Dates allows you to set individual dates with their own time slots.'),

                // Weekly Schedule Section (Tuesday - Sunday)
                Field::make('complex', 'pc_weekly_schedule', __('Weekly Schedule', 'palmcode-child'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'pc_availability_mode',
                            'value' => 'weekly',
                            'compare' => '=',
                        ),
                    ))
                    ->set_layout('tabbed-horizontal')
                    ->add_fields(array(
                        Field::make('select', 'day', __('Day', 'palmcode-child'))
                            ->set_options(array(
                                'tuesday' => __('Tuesday', 'palmcode-child'),
                                'wednesday' => __('Wednesday', 'palmcode-child'),
                                'thursday' => __('Thursday', 'palmcode-child'),
                                'friday' => __('Friday', 'palmcode-child'),
                                'saturday' => __('Saturday', 'palmcode-child'),
                                'sunday' => __('Sunday', 'palmcode-child'),
                            ))
                            ->set_required(true)
                            ->set_help_text('Select the day of the week for this schedule entry.'),
                        
                        Field::make('time', 'start_time', __('Start Time', 'palmcode-child'))
                            ->set_required(true)
                            ->set_help_text('The start time for this day. Time slots will be generated from this time based on the interval.'),
                        
                        Field::make('time', 'end_time', __('End Time', 'palmcode-child'))
                            ->set_required(true)
                            ->set_help_text('The end time for this day. Time slots will be generated up to this time based on the interval.'),
                        
                        Field::make('select', 'interval', __('Interval', 'palmcode-child'))
                            ->set_options(array(
                                '60' => __('1 Hour', 'palmcode-child'),
                                '90' => __('1 Hour 30 Minutes', 'palmcode-child'),
                                '120' => __('2 Hours', 'palmcode-child'),
                                '150' => __('2 Hours 30 Minutes', 'palmcode-child'),
                                '180' => __('3 Hours', 'palmcode-child'),
                                '210' => __('3 Hours 30 Minutes', 'palmcode-child'),
                                '240' => __('4 Hours', 'palmcode-child'),
                                '270' => __('4 Hours 30 Minutes', 'palmcode-child'),
                                '300' => __('5 Hours', 'palmcode-child'),
                                '330' => __('5 Hours 30 Minutes', 'palmcode-child'),
                            ))
                            ->set_required(true)
                            ->set_help_text('Select the interval between each time slot for this day. Time slots will be generated automatically based on this interval.'),
                    ))
                    ->set_header_template('<% var dayLabel = day.charAt(0).toUpperCase() + day.slice(1); %><%- dayLabel %> - <%- start_time %> to <%- end_time %>')
                    ->set_help_text('Configure the schedule for each day (Tuesday through Sunday). Add one entry per day with its own start time, end time, and interval settings.'),

                // Closed Dates for Weekly Mode
                Field::make('complex', 'pc_weekly_closed_dates', __('Closed Dates', 'palmcode-child'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'pc_availability_mode',
                            'value' => 'weekly',
                            'compare' => '=',
                        ),
                    ))
                    ->set_layout('tabbed-horizontal')
                    ->add_fields(array(
                        Field::make('date', 'closed_date', __('Closed Date', 'palmcode-child'))
                            ->set_required(true)
                            ->set_storage_format('Y-m-d')
                            ->set_picker_options(array(
                                'format' => 'YYYY-MM-DD',
                                'minDate' => date('Y-m-d'),
                            ))
                            ->set_help_text('Select a date when this event will be fully closed, even if weekly schedule is configured.'),
                    ))
                    ->set_header_template('<%- closed_date %>')
                    ->set_help_text('Add specific dates when the event will be completely closed. These dates override the weekly schedule.'),

                // Specific Dates Availability Section
                Field::make('complex', 'pc_specific_date_slots', __('Specific Date Slots', 'palmcode-child'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'pc_availability_mode',
                            'value' => 'specific_dates',
                            'compare' => '=',
                        ),
                    ))
                    ->set_layout('tabbed-horizontal')
                    ->add_fields(array(
                        Field::make('date', 'slot_date', __('Date', 'palmcode-child'))
                            ->set_required(true)
                            ->set_storage_format('Y-m-d')
                            ->set_picker_options(array(
                                'format' => 'YYYY-MM-DD',
                                'minDate' => date('Y-m-d'),
                            ))
                            ->set_help_text('Select the date for this availability slot.'),
                        
                        // Nested Repeater for Time Slots
                        Field::make('complex', 'time_slots', __('Time Slots', 'palmcode-child'))
                            ->set_layout('tabbed-horizontal')
                            ->add_fields(array(
                                Field::make('time', 'start_time', __('Start Time', 'palmcode-child'))
                                    ->set_required(true)
                                    ->set_help_text('The start time for this time slot.'),
                                Field::make('time', 'end_time', __('End Time', 'palmcode-child'))
                                    ->set_required(true)
                                    ->set_help_text('The end time for this time slot.'),
                            ))
                            ->set_header_template('<%- start_time %> - <%- end_time %>')
                            ->set_help_text('Add time slots available for this specific date. Each date can have different time slots.'),
                    ))
                    ->set_header_template('<%- slot_date %>')
                    ->set_help_text('Add specific dates when the event is available. Each date can have its own set of time slots.'),
            ));
    }

    add_action('carbon_fields_register_fields', 'pc_register_event_availability_fields');
}
