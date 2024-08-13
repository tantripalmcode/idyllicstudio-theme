<?php

/**
 * Add new layout options
 */
add_filter('pc_layout_options', function($options) {
    $options['1250px'] = 'Boxed (1250px)';
    return $options;
});

/**
 * Add New Global Color
 */
// add_action('pc_add_global_array_value', function () {
//     add_filter('pc_global_colors', function ($options) {
//         $options['test'] = array(
//             "css_root_code" => "--test-color",
//             "name" => "Test",
//             "text_color_class" => "text-color-test",
//             "bg_color_class" => "bg-color-test",
//         );

//         return $options;
//     });
// });

/**
 * Add New Social Media
 */
add_action('pc_add_social_media', function () {
    add_filter('pc_social_media', function ($options) {
        $options['Instagram'] = array(
            "name" => 'Instagram',
            'option_name' => 'pc_company[company_instagram]',
            'icon_embed' => '<svg width="30" height="30" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">
            <g clip-path="url(#clip0_116_113)">
            <path d="M9.71875 0.5H20.7812C23.1599 0.5 25.4411 1.44492 27.1231 3.12689C28.8051 4.80885 29.75 7.09009 29.75 9.46875V20.5312C29.75 22.9099 28.8051 25.1911 27.1231 26.8731C25.4411 28.5551 23.1599 29.5 20.7812 29.5H9.71875C7.34009 29.5 5.05885 28.5551 3.37689 26.8731C1.69492 25.1911 0.75 22.9099 0.75 20.5312V9.46875C0.75 7.09009 1.69492 4.80885 3.37689 3.12689C5.05885 1.44492 7.34009 0.5 9.71875 0.5ZM20.7812 27.2344C24.4778 27.2344 27.4844 24.2278 27.4844 20.5312V9.46875C27.4844 5.77224 24.4778 2.76562 20.7812 2.76562H9.71875C6.02224 2.76562 3.01562 5.77224 3.01562 9.46875V20.5312C3.01562 24.2278 6.02224 27.2344 9.71875 27.2344H20.7812Z" fill="#0F0F0B" stroke="#FDFDFC" stroke-width="0.5"/>
            <path d="M15.2497 19.8594H15.25C17.9288 19.8594 20.1094 17.6788 20.1094 15C20.1094 12.3193 17.9287 10.1406 15.25 10.1406C12.5713 10.1406 10.3906 12.3193 10.3906 15L10.3906 15.0003C10.3922 16.2885 10.9046 17.5236 11.8155 18.4345C12.7264 19.3454 13.9615 19.8578 15.2497 19.8594ZM10.2119 9.96186C11.5481 8.62567 13.3603 7.875 15.25 7.875C17.1397 7.875 18.9519 8.62567 20.2881 9.96186C21.6243 11.2981 22.375 13.1103 22.375 15C22.375 16.8897 21.6243 18.7019 20.2881 20.0381C18.9519 21.3743 17.1397 22.125 15.25 22.125C13.3603 22.125 11.5481 21.3743 10.2119 20.0381C8.87567 18.7019 8.125 16.8897 8.125 15C8.125 13.1103 8.87567 11.2981 10.2119 9.96186Z" fill="#0F0F0B" stroke="#FDFDFC" stroke-width="0.5"/>
            <path d="M23.9107 7.07183C23.9107 7.4765 23.5827 7.80455 23.178 7.80455C22.7734 7.80455 22.4453 7.4765 22.4453 7.07183C22.4453 6.66716 22.7734 6.33911 23.178 6.33911C23.5827 6.33911 23.9107 6.66716 23.9107 7.07183Z" fill="#0F0F0B" stroke="#FDFDFC" stroke-width="0.5"/>
            </g>
            <defs>
            <clipPath id="clip0_116_113">
            <rect width="29.5" height="29.5" fill="white" transform="translate(0.5 0.25)"/>
            </clipPath>
            </defs>
            </svg>
            ',
        );
        return $options;
    });
});