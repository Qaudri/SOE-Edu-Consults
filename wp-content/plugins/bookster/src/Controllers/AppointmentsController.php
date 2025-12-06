<?php
namespace Bookster\Controllers;

use Bookster\Services\AppointmentsService;
use Bookster\Services\BookingsService;
use Bookster\Services\AppointmentMetasService;
use Bookster\Services\TransactionsService;
use Bookster\Services\CustomersService;
use Bookster\Services\EmailService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Enums\BookStatusEnum;
use Bookster\Features\Enums\PaymentStatusEnum;

/**
 * API Controller for Appointment Models
 *
 * @method static AppointmentsController get_instance()
 */
class AppointmentsController extends BaseRestController {
    use SingletonTrait;

    /** @var AppointmentsService */
    private $appointments_service;
    /** @var BookingsService */
    private $bookings_service;
    /** @var AppointmentMetasService */
    private $appointment_metas_service;
    /** @var CustomersService */
    private $customers_service;
    /** @var TransactionsService */
    private $transactions_service;
    /** @var EmailService */
    private $email_service;

    protected function __construct() {
        $this->appointments_service      = AppointmentsService::get_instance();
        $this->bookings_service          = BookingsService::get_instance();
        $this->appointment_metas_service = AppointmentMetasService::get_instance();
        $this->customers_service         = CustomersService::get_instance();
        $this->transactions_service      = TransactionsService::get_instance();
        $this->email_service             = EmailService::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/booked-by-agent',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_booked_appointments_by_agent' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/booked-by-user',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_booked_appointments_by_user' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/query',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/counter',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_counter_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/count',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_count_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/bulk-action',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointments_bulk' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_appointments_bulk' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        $appointment_id_args = [
            'appointment_id' => [
                'type'              => 'number',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/(?P<appointment_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/(?P<appointment_id>\d+)/details',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_appointment_details' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/(?P<appointment_id>\d+)/bookings',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment_bookings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/(?P<appointment_id>\d+)/resend-email-to-customer',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_send_appointment_details_to_customer' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/appointments/(?P<appointment_id>\d+)/resend-email-to-agent',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_send_appointment_details_to_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $appointment_id_args,
                ],
            ]
        );
    }

    public function query_booked_appointments_by_agent( \WP_REST_Request $request ) {
        $args = $request->get_json_params();

        $appts_by_agent = $this->appointments_service->query_booked_appts_with_info(
            [
                'datetime_start' => [
                    'operator' => 'BETWEEN',
                    'min'      => $args['datetime_min'],
                    'max'      => $args['datetime_max'],
                ],

                'in_args'        => [
                    'agent_id'    => [
                        'values'      => $args['agent_ids'],
                        'placeholder' => '%d',
                        'alias'       => 'assignment',
                    ],

                    'book_status' => [
                        'values'      => [
                            BookStatusEnum::PENDING,
                            BookStatusEnum::APPROVED,
                        ],
                        'placeholder' => '%s',
                    ],
                ],
            ]
        );

        $appts_by_agent = array_map(
            function( $appointment ) {
                return $appointment->to_array();
            },
            $appts_by_agent
        );
        return $appts_by_agent;
    }

    public function query_booked_appointments_by_user( \WP_REST_Request $request ) {
        $args    = $request->get_json_params();
        $user_id = get_current_user_id();

        if ( 0 === $user_id ) {
            return [];
        }
        $customer_record = $this->customers_service->find_one_with_info( [ 'wp_user_id' => $user_id ] );
        if ( null === $customer_record ) {
            return [];
        }

        $appts_by_customer = $this->appointments_service->query_booked_appts_with_info(
            [
                'datetime_start' => [
                    'operator' => 'BETWEEN',
                    'min'      => $args['datetime_min'],
                    'max'      => $args['datetime_max'],
                ],

                'in_args'        => [
                    'customer_id' => [
                        'values'      => [ $customer_record->customer_id ],
                        'placeholder' => '%d',
                        'alias'       => 'booking',
                    ],

                    'book_status' => [
                        'values'      => [
                            BookStatusEnum::PENDING,
                            BookStatusEnum::APPROVED,
                        ],
                        'placeholder' => '%s',
                    ],
                ],
            ]
        );

        $appts_by_customer = array_map(
            function( $appointment ) {
                return $appointment->to_array();
            },
            $appts_by_customer
        );
        return $appts_by_customer;
    }

    public function query_appointments( \WP_REST_Request $request ) {
        $args         = $request->get_json_params();
        $appointments = $this->appointments_service->query_where_with_info( $args );
        $total        = $this->appointments_service->count_where_with_info( $args );

        $data = array_map(
            function( $appointment ) {
                return $appointment->to_array();
            },
            $appointments
        );

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    public function count_appointments( \WP_REST_Request $request ) {
        $args  = $request->get_json_params();
        $count = $this->appointments_service->count_where_with_info( $args );

        return $count;
    }

    public function counter_appointments( \WP_REST_Request $request ) {
        $pending_args     = [
            'in_args' => [
                'book_status' => [
                    'values'      => [
                        BookStatusEnum::PENDING,
                    ],
                    'placeholder' => '%s',
                ],
            ],
        ];
        $incomplete_args  = [
            'in_args' => [
                'payment_status' => [
                    'values'      => [
                        PaymentStatusEnum::INCOMPLETE,
                    ],
                    'placeholder' => '%s',
                    'alias'       => 'booking',
                ],
            ],
        ];
        $pending_count    = $this->appointments_service->count_where_with_info( $pending_args );
        $incomplete_count = $this->appointments_service->count_where_with_info( $incomplete_args );

        return [
            'book_status_pending'       => $pending_count,
            'payment_status_incomplete' => $incomplete_count,
        ];
    }

    public function get_appointment( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $appointment    = $this->appointments_service->find_by_id_with_info( $appointment_id );
        return $appointment->to_array();
    }

    public function get_appointment_details( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $appointment    = $this->appointments_service->find_by_id_with_info( $appointment_id );

        $transactions = $this->transactions_service->get_by_appt_id( $appointment_id );

        return [
            'appointment'  => $appointment->to_array(),
            'transactions' => array_map(
                function( $transaction ) {
                    return $transaction->to_array();
                },
                $transactions
            ),
        ];
    }

    public function post_appointment( \WP_REST_Request $request ) {
        $payload        = $request->get_json_params();
        $appt_input     = $payload['apptInput'];
        $bookings_input = $payload['bookingsInput'];

        $appointment = $this->appointments_service->insert( $appt_input, $bookings_input );

        do_action( 'bookster_manager_create_appointment', $appointment );
        return $appointment->to_array();
    }

    // Update Appt and Bookings in a single transaction
    public function patch_appointment_bookings( \WP_REST_Request $request ) {
        $payload        = $request->get_json_params();
        $appt_input     = $payload['apptInput'];
        $bookings_input = $payload['bookingsInput'];
        $appointment_id = $request->get_param( 'appointment_id' );

        $old_appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );

        $this->appointments_service->update( $appointment_id, $appt_input );
        $this->bookings_service->update_by_bookings_input( $appointment_id, $bookings_input );

        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        do_action( 'bookster_manager_update_appointment', $appointment, $old_appointment );

        return $appointment->to_array();
    }

    // Only Update Appt Model
    public function patch_appointment( \WP_REST_Request $request ) {
        $appt_input     = $request->get_json_params();
        $appointment_id = $request->get_param( 'appointment_id' );

        $old_appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );

        $this->appointments_service->update(
            $appointment_id,
            $appt_input
        );

        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        do_action( 'bookster_manager_update_appointment', $appointment, $old_appointment );

        return $appointment->to_array();
    }

    public function delete_appointment( \WP_REST_Request $request ) {
        return $this->appointments_service->delete( $request->get_param( 'appointment_id' ) );
    }

    public function patch_appointments_bulk( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args['values'] ),
            [ 'book_status' ]
        );
        $affected_rows = $this->appointments_service->update_many( $args['ids'], $args['values'] );
        return $affected_rows;
    }

    public function delete_appointments_bulk( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        foreach ( $args['ids'] as $id ) {
            $this->appointments_service->delete( $id );
        }
        return count( $args['ids'] );
    }

    public function send_appointment_details_to_customer( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $appt           = $this->appointments_service->find_by_id_with_info( $appointment_id );
        foreach ( $appt->bookings as $booking ) {
            $this->email_service->send_appt_notice_customer( $appt, $booking, 'manual_resend_appt_customer' );
        }
        return true;
    }

    public function send_appointment_details_to_agent( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $appt           = $this->appointments_service->find_by_id_with_info( $appointment_id );
        return $this->email_service->send_appt_notice_agent( $appt, $appt->bookings[0], 'manual_resend_appt_agent' );
    }

    public function exec_query_booked_appointments_by_agent( $request ) {
        return $this->exec_read( [ $this, 'query_booked_appointments_by_agent' ], $request );
    }

    public function exec_query_booked_appointments_by_user( $request ) {
        return $this->exec_read( [ $this, 'query_booked_appointments_by_user' ], $request );
    }

    public function exec_query_appointments( $request ) {
        return $this->exec_read( [ $this, 'query_appointments' ], $request );
    }

    public function exec_count_appointments( $request ) {
        return $this->exec_read( [ $this, 'count_appointments' ], $request );
    }

    public function exec_counter_appointments( $request ) {
        return $this->exec_read( [ $this, 'counter_appointments' ], $request );
    }

    public function exec_get_appointment( $request ) {
        return $this->exec_read( [ $this, 'get_appointment' ], $request );
    }

    public function exec_get_appointment_details( $request ) {
        return $this->exec_read( [ $this, 'get_appointment_details' ], $request );
    }

    public function exec_post_appointment( $request ) {
        return $this->exec_write( [ $this, 'post_appointment' ], $request );
    }

    public function exec_patch_appointment( $request ) {
        return $this->exec_write( [ $this, 'patch_appointment' ], $request );
    }

    public function exec_patch_appointment_bookings( $request ) {
        return $this->exec_write( [ $this, 'patch_appointment_bookings' ], $request );
    }

    public function exec_delete_appointment( $request ) {
        return $this->exec_write( [ $this, 'delete_appointment' ], $request );
    }

    public function exec_patch_appointments_bulk( $request ) {
        return $this->exec_write( [ $this, 'patch_appointments_bulk' ], $request );
    }

    public function exec_delete_appointments_bulk( $request ) {
        return $this->exec_write( [ $this, 'delete_appointments_bulk' ], $request );
    }

    public function exec_send_appointment_details_to_customer( $request ) {
        return $this->exec_read( [ $this, 'send_appointment_details_to_customer' ], $request );
    }

    public function exec_send_appointment_details_to_agent( $request ) {
        return $this->exec_read( [ $this, 'send_appointment_details_to_agent' ], $request );
    }
}
