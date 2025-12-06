<?php
namespace Bookster\Controllers;

use Bookster\Services\CustomersService;
use Bookster\Services\AppointmentsService;
use Bookster\Services\BookingsService;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\AppointmentMetasService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Errors\ForbiddenException;
use Bookster\Features\Enums\BookStatusEnum;
use Bookster\Features\Utils\ArrayUtils;

/**
 * As Customer Controller
 *
 * @method static AsCustomersController get_instance()
 */
class AsCustomersController extends BaseRestController {
    use SingletonTrait;

    /** @var CustomersService */
    private $customers_service;
    /** @var AppointmentsService */
    private $appointments_service;
    /** @var BookingsService */
    private $bookings_service;
    /** @var AppointmentMetasService */
    private $appointment_metas_service;

    protected function __construct() {
        $this->customers_service         = CustomersService::get_instance();
        $this->appointments_service      = AppointmentsService::get_instance();
        $this->bookings_service          = BookingsService::get_instance();
        $this->appointment_metas_service = AppointmentMetasService::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        $customer_id_args = [
            'customer_id' => [
                'type'              => 'number',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
        ];

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-customer/(?P<customer_id>\d+)/profile',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_profile' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                    'args'                => $customer_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-customer/profile',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_profile' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-customer/(?P<customer_id>\d+)/appointments',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_get_appointments_by_customer' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                    'args'                => $customer_id_args,
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
            '/as-customer/(?P<customer_id>\d+)/appointments/(?P<appointment_id>\d+)/note',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment_note' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                    'args'                => array_merge( $customer_id_args, $appointment_id_args ),
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-customer/(?P<customer_id>\d+)/appointments/(?P<appointment_id>\d+)/cancel',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment_cancel' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                    'args'                => array_merge( $customer_id_args, $appointment_id_args ),
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-customer/(?P<customer_id>\d+)/appointments/(?P<appointment_id>\d+)/details',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_appointment_details' ],
                    'permission_callback' => [ RestAuth::class, 'require_login' ],
                    'args'                => array_merge( $customer_id_args, $appointment_id_args ),
                ],
            ]
        );
    }

    public function get_profile( \WP_REST_Request $request ) {
        $customer = $this->customers_service->require_customer_is_current_user( $request->get_param( 'customer_id' ) );

        return $customer->to_array_for_customer_role();
    }

    public function patch_profile( \WP_REST_Request $request ) {
        $customer_id  = $request->get_param( 'customer_id' );
        $args         = $request->get_json_params();
        $current_user = wp_get_current_user();

        $update_args               = ArrayUtils::pick(
            $args,
            apply_filters( 'bookster_allowed_customer_profile_args', [ 'first_name', 'last_name', 'phone' ] )
        );
        $update_args['wp_user_id'] = $current_user->ID;

        if ( null !== $customer_id ) {
            // update record with customer_id
            $customer = $this->customers_service->require_customer_is_current_user( $customer_id );
            $customer->update( $update_args );

            return $customer->to_array_for_customer_role();
        }

        $customer = $this->customers_service->find_one_with_info( [ 'email' => $current_user->user_email ] );

        if ( null === $customer ) {
            // create new customer record
            $update_args['email'] = $current_user->user_email;
            $customer             = $this->customers_service->insert( $update_args );
        } else {
            // customer record existed
            $customer->update( $update_args );
        }

        return $customer->to_array_for_customer_role();
    }

    public function get_appointments_by_customer( \WP_REST_Request $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $this->customers_service->require_customer_is_current_user( $customer_id );

        $args         = $request->get_json_params();
        $args         = array_merge( $args, [ 'booking.customer_id' => $customer_id ] );
        $appointments = $this->appointments_service->query_where_with_info( $args );
        $total        = $this->appointments_service->count_where_with_info( $args );

        $data = array_map(
            function( $appointment ) use ( $customer_id ) {
                return $appointment->to_array_for_customer_role( $customer_id );
            },
            $appointments
        );

        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    public function get_appointment_details( \WP_REST_Request $request ) {
        $customer_id = $request->get_param( 'customer_id' );
        $this->customers_service->require_customer_is_current_user( $customer_id );

        $appointment_id = $request->get_param( 'appointment_id' );
        $appointment    = $this->appointments_service->find_by_id_with_info( $appointment_id );

        return [
            'appointment' => $appointment->to_array_for_customer_role( $customer_id ),
        ];
    }

    public function patch_appointment_note( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $customer_id    = $request->get_param( 'customer_id' );
        $args           = $request->get_json_params();

        $this->customers_service->require_customer_is_current_user( $customer_id );
        $this->validate_arguments( array_keys( $args ), [ 'customer_note' ] );

        $this->bookings_service->update_by_customer_id( $appointment_id, $customer_id, $args );

        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        return $appointment->to_array_for_customer_role( $customer_id );
    }

    public function patch_appointment_cancel( \WP_REST_Request $request ) {
        $appointment_id = $request->get_param( 'appointment_id' );
        $customer_id    = $request->get_param( 'customer_id' );

        $this->customers_service->require_customer_is_current_user( $customer_id );
        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        // if appointment passed in wp timezone then throw error
        if ( $appointment->datetime_start < wp_date( 'Y-m-d H:i:s' ) ) {
            throw new ForbiddenException( 'The appointment is in the passed.' );
        }

        $customer_has_booking = false;
        foreach ( $appointment->bookings as $booking ) {
            if ( $booking->customer_id === $customer_id ) {
                $customer_has_booking = true;
                break;
            }
        }
        if ( count( $appointment->bookings ) > 1 || ! $customer_has_booking ) {
            throw new ForbiddenException( 'You are not allowed to cancel this appointment.' );
        }

        $appointment->update( [ 'book_status' => BookStatusEnum::CANCELED ] );
        return $appointment->to_array_for_customer_role( $customer_id );
    }

    public function exec_get_profile( $request ) {
        return $this->exec_read( [ $this, 'get_profile' ], $request );
    }

    public function exec_patch_profile( $request ) {
        return $this->exec_write( [ $this, 'patch_profile' ], $request );
    }

    public function exec_get_appointments_by_customer( $request ) {
        return $this->exec_read( [ $this, 'get_appointments_by_customer' ], $request );
    }

    public function exec_get_appointment_details( $request ) {
        return $this->exec_read( [ $this, 'get_appointment_details' ], $request );
    }

    public function exec_patch_appointment_note( $request ) {
        return $this->exec_write( [ $this, 'patch_appointment_note' ], $request );
    }

    public function exec_patch_appointment_cancel( $request ) {
        return $this->exec_write( [ $this, 'patch_appointment_cancel' ], $request );
    }
}
