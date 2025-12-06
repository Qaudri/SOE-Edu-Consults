<?php
namespace Bookster\Features\Constants;

use Bookster\Features\Enums\BookStatusEnum;

/**
 * Settings Data
 */
class SettingsData {

    const DEFAULT_GENERAL_SETTINGS = [
        'time_slot_step'             => 60,
        'default_appointment_status' => BookStatusEnum::PENDING,
        'items_per_page'             => 20,
        'phone_country_code'         => 'us',
        'time_system'                => '12h',
        'date_format'                => 'YYYY-MM-DD',
        'book_restriction_earliest'  => [ 'no_restriction' ],
        'book_restriction_latest'    => [ 'no_restriction' ],
    ];

    const DEFAULT_WEEKLY_SETTINGS = [
        0 => [
            'working' => false,
            'periods' => [],
        ],
        1 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],
        2 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],
        3 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],
        4 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],
        5 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],
        6 => [
            'working' => true,
            'periods' => [
                [
                    'start' => [
                        'hour'      => 8,
                        'minute'    => 0,
                        'absMinute' => 480,
                    ],
                    'end'   => [
                        'hour'      => 17,
                        'minute'    => 0,
                        'absMinute' => 1020,
                    ],
                ],
            ],
        ],

    ];

    const DEFAULT_PERMISSION_SETTINGS = [
        'agents_link_wp_users'               => 'auto',
        'agents_allow_edit_appointment'      => false,
        'agents_allow_edit_settings'         => false,
        'customers_link_wp_users'            => 'manual',
        'customers_allow_cancel_appointment' => false,
        'customer_dashboard_page_id'         => null,
    ];

    const DEFAULT_PAYMENT_SETTINGS = [
        'currency'           => 'USD',
        'currency_symbol'    => '$',
        'currency_position'  => 'before',
        'thousand_separator' => ',',
        'decimal_separator'  => '.',
        'number_of_decimals' => 2,
        'payment-later'      => [
            'enabled' => true,
        ],
    ];

    public static function get_default_holidays_settings() {
        return [
            'every_year'    => null,
            'specific_year' => null,
        ];
    }

    public static function get_default_customize_settings() {
        return [
            'content' => [
                'steps' => [
                    'service'      => [
                        'title'       => __( 'Service Selection', 'bookster' ),
                        'description' => __( 'Please select a service for your appointment', 'bookster' ),
                    ],
                    'agent'        => [
                        'title'       => __( 'Agent', 'bookster' ),
                        'description' => __( 'Please select an agent for your appointment', 'bookster' ),
                    ],
                    'datetime'     => [
                        'title'       => __( 'Date & Time', 'bookster' ),
                        'description' => __( 'Please select a date and time for your appointment', 'bookster' ),
                    ],
                    'contact'      => [
                        'title'       => __( 'Contact', 'bookster' ),
                        'description' => __( 'Please enter your contact information', 'bookster' ),
                    ],
                    'checkout'     => [
                        'title'       => __( 'Checkout', 'bookster' ),
                        'description' => __( 'Please enter your payment information', 'bookster' ),
                    ],
                    'confirmation' => [
                        'title'       => __( 'Confimation', 'bookster' ),
                        'description' => __( 'Your appointment has been booked', 'bookster' ),
                    ],
                ],
                'help'  => __( 'Need help? Call Us Now', 'bookster' ),
            ],
        ];
    }
}
