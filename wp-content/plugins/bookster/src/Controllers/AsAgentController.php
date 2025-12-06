<?php
namespace Bookster\Controllers;

use Bookster\Services\AgentsService;
use Bookster\Services\AppointmentsService;
use Bookster\Services\BookingsService;
use Bookster\Services\AssignmentsService;
use Bookster\Services\AppointmentMetasService;
use Bookster\Services\TransactionsService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\AppointmentModel;
use Bookster\Features\Errors\ForbiddenException;
use Bookster\Models\AgentModel;
use Bookster\Features\Enums\BookStatusEnum;
use Bookster\Features\Enums\PaymentStatusEnum;

/**
 * Agent Role Controller
 * Provide APIs for agents Role
 *
 * @method static AsAgentController get_instance()
 */
class AsAgentController extends BaseRestController {
    use SingletonTrait;

    /** @var AgentsService */
    private $agents_service;
    /** @var AppointmentsService */
    private $appointments_service;
    /** @var BookingsService */
    private $bookings_service;
    /** @var AssignmentsService */
    private $assignments_service;
    /** @var AppointmentMetasService */
    private $appointment_metas_service;
    /** @var TransactionsService */
    private $transactions_service;

    protected function __construct() {
        $this->agents_service            = AgentsService::get_instance();
        $this->appointments_service      = AppointmentsService::get_instance();
        $this->bookings_service          = BookingsService::get_instance();
        $this->assignments_service       = AssignmentsService::get_instance();
        $this->appointment_metas_service = AppointmentMetasService::get_instance();
        $this->transactions_service      = TransactionsService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        $agent_id_args = [
            'agent_id' => [
                'type'              => 'number',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/agent',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_agent' ],
                    'permission_callback' => '__return_true',
                    'args'                => $agent_id_args,
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
            '/as-agent/(?P<agent_id>\d+)/profile',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_profile' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_profile_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/schedule',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_schedule' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_settings_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/available',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_available' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_settings_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/counter',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_counter_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/query',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/bulk-action',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_bulk_update_appointments' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/(?P<appointment_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => array_merge( $agent_id_args, $appointment_id_args ),
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => array_merge( $agent_id_args, $appointment_id_args ),
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_appointment' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => array_merge( $agent_id_args, $appointment_id_args ),
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/(?P<appointment_id>\d+)/details',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_appointment_details' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => array_merge( $agent_id_args, $appointment_id_args ),
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/as-agent/(?P<agent_id>\d+)/appointments/(?P<appointment_id>\d+)/bookings',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_appointment_bookings' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => array_merge( $agent_id_args, $appointment_id_args ),
                ],
            ]
        );
    }

    public function get_agent( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $agent    = $this->require_agent_is_current_user( $agent_id );

        return $agent->to_array();
    }

    public function patch_profile( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args ),
            [ 'first_name', 'last_name', 'avatar_id', 'phone', 'bio' ]
        );

        $agent = $this->agents_service->update( $request->get_param( 'agent_id' ), $args );
        return $agent->to_array();
    }

    public function patch_schedule( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args ),
            [ 'weekly_schedule_enabled', 'weekly_schedule', 'dayoff_schedule_enabled', 'dayoff_schedule' ]
        );

        $agent = $this->agents_service->update( $request->get_param( 'agent_id' ), $args );
        return $agent->to_array();
    }

    public function patch_available( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args ),
            [ 'available_agent_services' ]
        );

        $agent = $this->agents_service->update( $request->get_param( 'agent_id' ), $args );
        return $agent->to_array();
    }

    public function counter_appointments( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $pending_args = [
            'in_args'             => [
                'book_status' => [
                    'values'      => [
                        BookStatusEnum::PENDING,
                    ],
                    'placeholder' => '%s',
                ],
            ],
            'assignment.agent_id' => $agent_id,
        ];

        $incomplete_args  = [
            'in_args'             => [
                'payment_status' => [
                    'values'      => [
                        PaymentStatusEnum::INCOMPLETE,
                    ],
                    'placeholder' => '%s',
                    'alias'       => 'booking',
                ],
            ],
            'assignment.agent_id' => $agent_id,
        ];
        $pending_count    = $this->appointments_service->count_where_with_info( $pending_args );
        $incomplete_count = $this->appointments_service->count_where_with_info( $incomplete_args );

        return [
            'book_status_pending'       => $pending_count,
            'payment_status_incomplete' => $incomplete_count,
        ];
    }

    public function query_appointments( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $args = $request->get_json_params();
        $args = array_merge( $args, [ 'assignment.agent_id' => $agent_id ] );

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

    public function get_appointment( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $appointment_id = $request->get_param( 'appointment_id' );
        $appointment    = $this->appointments_service->find_by_id_with_info( $appointment_id );
        $this->require_appointment_assign_to_agent( $appointment, $agent_id );

        return $appointment->to_array();
    }

    public function get_appointment_details( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

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
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $payload                 = $request->get_json_params();
        $appt_input              = $payload['apptInput'];
        $appt_input['agent_ids'] = [ $agent_id ];
        $bookings_input          = $payload['bookingsInput'];

        $appointment = $this->appointments_service->insert( $appt_input, $bookings_input );

        do_action( 'bookster_agent_create_appointment', $appointment );
        return $appointment->to_array();
    }

    // Update Appt and Bookings in a single transaction
    public function patch_appointment_bookings( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );
        $appointment_id = $request->get_param( 'appointment_id' );

        $old_appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        $this->require_appointment_assign_to_agent( $old_appointment, $agent_id );

        $payload        = $request->get_json_params();
        $appt_input     = $payload['apptInput'];
        $bookings_input = $payload['bookingsInput'];

        $this->validate_arguments(
            array_keys( $appt_input ),
            [
                'book_status',
                'datetime_start',
                'datetime_end',
                'abs_min_start',
                'abs_min_end',
                'buffer_before',
                'buffer_after',
                'busy_abs_min_start',
                'busy_abs_min_end',
                'busy_datetime_start',
                'busy_datetime_end',
            ]
        );

        $this->appointments_service->update( $appointment_id, $appt_input );
        $this->bookings_service->update_by_bookings_input( $appointment_id, $bookings_input );

        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        do_action( 'bookster_agent_update_appointment', $appointment, $old_appointment );

        return $appointment->to_array();
    }

    // Only Update Appt Model
    public function patch_appointment( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $appointment_id = $request->get_param( 'appointment_id' );
        $appt_input     = $request->get_json_params();

        $old_appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        $this->require_appointment_assign_to_agent( $old_appointment, $agent_id );

        $this->validate_arguments(
            array_keys( $appt_input ),
            [
                'book_status',
                'staff_note',

                'datetime_start',
                'datetime_end',
                'abs_min_start',
                'abs_min_end',
                'buffer_before',
                'buffer_after',
                'busy_abs_min_start',
                'busy_abs_min_end',
                'busy_datetime_start',
                'busy_datetime_end',
            ]
        );

        $this->appointments_service->update(
            $appointment_id,
            $appt_input
        );

        $appointment = $this->appointments_service->find_by_id_with_info( $appointment_id );
        do_action( 'bookster_agent_update_appointment', $appointment, $old_appointment );

        return $appointment->to_array();
    }

    public function delete_appointment( \WP_REST_Request $request ) {
        return $this->appointments_service->delete( $request->get_param( 'appointment_id' ) );
    }

    public function bulk_update_appointments( \WP_REST_Request $request ) {
        $agent_id = $request->get_param( 'agent_id' );
        $this->require_agent_is_current_user( $agent_id );

        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args['values'] ),
            [ 'book_status' ]
        );

        $affected_rows = $this->appointments_service->update_many( $args['ids'], $args['values'] );
        return $affected_rows;
    }

    public function exec_get_agent( $request ) {
        return $this->exec_read( [ $this, 'get_agent' ], $request );
    }

    public function exec_patch_profile( $request ) {
        return $this->exec_write( [ $this, 'patch_profile' ], $request );
    }

    public function exec_patch_schedule( $request ) {
        return $this->exec_write( [ $this, 'patch_schedule' ], $request );
    }

    public function exec_patch_available( $request ) {
        return $this->exec_write( [ $this, 'patch_available' ], $request );
    }

    public function exec_counter_appointments( $request ) {
        return $this->exec_read( [ $this, 'counter_appointments' ], $request );
    }

    public function exec_query_appointments( $request ) {
        return $this->exec_read( [ $this, 'query_appointments' ], $request );
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

    public function exec_bulk_update_appointments( $request ) {
        return $this->exec_write( [ $this, 'bulk_update_appointments' ], $request );
    }

    private function require_agent_is_current_user( $agent_id ): AgentModel {
        $agent           = $this->agents_service->find_by_id_with_info( $agent_id );
        $current_user_id = get_current_user_id();
        if ( (int) $current_user_id === $agent->wp_user_id ) {
            return $agent;
        } else {
            throw new ForbiddenException( "You don't have Permisson to do this !!" );
        }
    }

    private function require_appointment_assign_to_agent( AppointmentModel $appointment, int $agent_id ) {
        if ( in_array( $agent_id, $appointment->agent_ids, true ) ) {
            return true;
        } else {
            throw new ForbiddenException( "You don't have Permisson to do this !!" );
        }
    }
}
