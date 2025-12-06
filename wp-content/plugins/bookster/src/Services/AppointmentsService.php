<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\AppointmentModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\ServiceModel;
use Bookster\Models\CustomerModel;
use Bookster\Models\AgentModel;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;
use Bookster\Models\BookingModel;
use Bookster\Models\Database\QueryBuilder;
use Bookster\Models\DTOs\BookedAppointmentDTO;

/**
 * Appointment Service
 *
 * @method static AppointmentsService get_instance()
 */
class AppointmentsService extends BaseService {
    use SingletonTrait;

    /** @var AssignmentsService */
    private $assignments_service;
    /** @var BookingsService */
    private $bookings_service;
    /** @var AppointmentMetasService */
    private $appointment_metas_service;
    /** @var BookingMetasService */
    private $booking_metas_service;
    /** @var TransactionsService */
    private $transactions_service;

    protected function __construct() {
        $this->assignments_service       = AssignmentsService::get_instance();
        $this->bookings_service          = BookingsService::get_instance();
        $this->appointment_metas_service = AppointmentMetasService::get_instance();
        $this->booking_metas_service     = BookingMetasService::get_instance();
        $this->transactions_service      = TransactionsService::get_instance();
    }

    /**
     * Query appointments with included info from joined tables.
     *
     * @param array    $args       Query arguments.
     * @param int|null $customer_id Optional customer ID to filter bookings.
     * @return AppointmentModel[]
     */
    public function query_where_with_info( array $args, $customer_id = null ): array {
        $builder = $this->create_builder_find_where_with_info( $this->prepare_where_args( $args ) );
        $builder = $this->add_query_clauses( $builder );
        $builder->select( '(' . $this->get_appt_bookings_subquery( $customer_id ) . ") AS '_bookings'" );
        $appointments = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ AppointmentModel::class, 'init_from_data' ], $appointments );
    }

    /**
     * Query booked appointments, select only columns needed to check Available.
     *
     * @param array $args
     */
    public function query_booked_appts_with_info( array $args ): array {
        $builder      = $this->create_builder_find_where_with_info( $this->prepare_where_args( $args ) );
        $builder      = $this->add_booked_clauses( $builder );
        $appointments = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ BookedAppointmentDTO::class, 'init_from_data' ], $appointments );
    }

    public function count_where_with_info( array $args ): int {
        $builder = $this->create_builder_find_where_with_info( $this->prepare_count_args( $args ) );
        $builder = $this->add_query_select_count_clause( $builder );
        $count   = (int) $builder->value();
        $this->validate_wpdb_query();

        return $count;
    }

    public function find_by_id( int $appointment_id ): AppointmentModel {
        $appointment = AppointmentModel::find( $appointment_id );
        if ( ! $appointment ) {
            throw new NotFoundException( 'Appointment Not Found', ObjectTypeEnum::APPOINTMENT, $appointment_id );
        }
        return $appointment;
    }

    /**
     * Find an appointment by ID with included info from joined tables.
     *
     * @param int      $appointment_id
     * @param int|null $customer_id Optional customer ID to filter bookings.
     * @return AppointmentModel The appointment model
     */
    public function find_by_id_with_info( int $appointment_id, $customer_id = null ): AppointmentModel {
        $builder = $this->create_builder_find_where_with_info( [ 'appt.appointment_id' => $appointment_id ] );
        $builder = $this->add_query_clauses( $builder );
        $builder->select( '(' . $this->get_appt_bookings_subquery( $customer_id ) . ") AS '_bookings'" );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        if ( ! $attributes ) {
            throw new NotFoundException( 'Appointment Not Found', ObjectTypeEnum::APPOINTMENT, $appointment_id );
        }
        return AppointmentModel::init_from_data( $attributes );
    }

    /**
     * @param array $appt_attrs
     * @param array $bookings_attrs
     * @return AppointmentModel
     */
    public function insert( $appt_attrs, $bookings_attrs = null ): AppointmentModel {
        $appointment = AppointmentModel::insert( AppointmentModel::prepare_saved_data( $appt_attrs ) );
        if ( is_null( $appointment ) ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Appointment: ' . $wpdb->last_error ) );
        }

        $this->assignments_service->update( $appointment->appointment_id, $appt_attrs['agent_ids'] );

        if ( $bookings_attrs ) {
            foreach ( $bookings_attrs as $booking_attrs ) {
                $booking_attrs['appointment_id'] = $appointment->appointment_id;
                $this->bookings_service->insert( $booking_attrs );
            }
        }
        return $this->find_by_id_with_info( $appointment->appointment_id );
    }

    public function update( int $appointment_id, array $data ) {
        $appointment = AppointmentModel::find( $appointment_id );
        if ( ! $appointment ) {
            throw new NotFoundException( 'Appointment Not Found', ObjectTypeEnum::APPOINTMENT, $appointment_id );
        }

        $success = $appointment->update( AppointmentModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Appointment: ' . $wpdb->last_error ) );
        }
        if ( isset( $data['agent_ids'] ) ) {
            $this->assignments_service->update( $appointment->appointment_id, $data['agent_ids'] );
        }
    }

    public function delete( int $appointment_id ): bool {
        $appointment = AppointmentModel::find( $appointment_id );
        if ( ! $appointment ) {
            throw new NotFoundException( 'Appointment Not Found', ObjectTypeEnum::APPOINTMENT, $appointment_id );
        }

        $this->assignments_service->delete_by_appt_id( $appointment_id );
        $this->appointment_metas_service->delete_by_appt_id( $appointment_id );
        $this->bookings_service->delete_by_appt_id( $appointment_id );
        $this->booking_metas_service->delete_by_appt_id( $appointment_id );
        $this->transactions_service->delete_by_appt_id( $appointment_id );
        $success = $appointment->delete();

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Appointment: ' . $wpdb->last_error ) );
        }
        return $success;
    }

    public function update_many( array $ids, array $data ) {
        $affected_rows = AppointmentModel::update_where(
            AppointmentModel::prepare_saved_data( $data ),
            [
                'raw' => AppointmentModel::build_in_query( $ids, '%d', 'appointment_id' ),
            ]
        );

        if ( false === $affected_rows ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Appointment: ' . $wpdb->last_error ) );
        }
        return $affected_rows;
    }

    private function create_builder_find_where_with_info( array $args ): QueryBuilder {
        $builder = AppointmentModel::create_where_builder( $args, 'appt' );

        $builder->join(
            AssignmentModel::TABLE . ' as `assignment`',
            [ [ 'raw' => 'appt.appointment_id = assignment.appointment_id' ] ],
            'LEFT'
        )
        ->join(
            BookingModel::TABLE . ' as `booking`',
            [ [ 'raw' => 'appt.appointment_id = booking.appointment_id' ] ],
            'LEFT'
        );

        if ( ! isset( $args['order_by'] ) ) {
            $builder->order_by( 'datetime_start', 'DESC' );
        }

        $builder = apply_filters( 'bookster_appointments_info_query_builder', $builder );

        return $builder;
    }

    private function add_query_select_count_clause( $builder ) {
        $builder->select( 'COUNT(appt.appointment_id)' );
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     * @return QueryBuilder
     */
    private function add_query_clauses( $builder ) {
        $builder->select( 'appt.appointment_id' )
        ->select( 'appt.service_id' )
        ->select( 'appt.location_id' )
        ->select( 'appt.book_status' )
        ->select( 'appt.datetime_start' )
        ->select( 'appt.datetime_end' )
        ->select( 'appt.utc_datetime_start' )
        ->select( 'appt.abs_min_start' )
        ->select( 'appt.abs_min_end' )
        ->select( 'appt.buffer_before' )
        ->select( 'appt.buffer_after' )
        ->select( 'appt.busy_abs_min_start' )
        ->select( 'appt.busy_abs_min_end' )
        ->select( 'appt.busy_datetime_start' )
        ->select( 'appt.busy_datetime_end' )
        ->select( 'appt.staff_note' )
        ->select( 'appt.updated_at' )
        ->select(
            '(SELECT JSON_ARRAYAGG( _assg.`agent_id`)
            FROM ' . AssignmentModel::get_tablename() . " AS _assg
            WHERE _assg.appointment_id = appt.appointment_id
            ) AS 'agent_ids'"
        )

        ->select(
            "(SELECT 
                JSON_ARRAYAGG( JSON_OBJECT(
                    'agent_id', _a.`agent_id`,
                    'avatar_id', _a.`avatar_id`,
                    'first_name', _a.`first_name`,
                    'last_name', _a.`last_name`,
                    'email', _a.`email`,
                    'phone', _a.`phone`
                ))
            FROM " . AssignmentModel::get_tablename() . ' AS _assg
            LEFT JOIN ' . AgentModel::get_tablename() . " AS _a ON _assg.agent_id = _a.agent_id
            WHERE _assg.appointment_id = appt.appointment_id
            ) AS '_agents'"
        )

        ->select(
            "(SELECT JSON_OBJECT(
                'service_id', _serv.`service_id`,
                'service_category_id', _serv.`service_category_id`,
                'name', _serv.`name`,
                'price', _serv.`price`,
                'theme_color', _serv.`theme_color`
            )
            FROM " . ServiceModel::get_tablename() . " AS _serv
            WHERE _serv.service_id = appt.service_id
            ) AS '_service'"
        )

        ->group_by( 'appt.appointment_id' );

        $builder = apply_filters( 'bookster_appointment_info_query_builder', $builder );
        return $builder;
    }

    /**
     * @param QueryBuilder $builder
     * @return QueryBuilder
     */
    private function add_booked_clauses( $builder ) {

        $builder->select( 'appt.appointment_id' )
        ->select( 'appt.service_id' )
        ->select( 'appt.location_id' )
        ->select( 'appt.book_status' )
        ->select( 'appt.datetime_start' )
        ->select( 'appt.datetime_end' )
        ->select( 'appt.utc_datetime_start' )
        ->select( 'appt.abs_min_start' )
        ->select( 'appt.abs_min_end' )
        ->select( 'appt.buffer_before' )
        ->select( 'appt.buffer_after' )
        ->select( 'appt.busy_abs_min_start' )
        ->select( 'appt.busy_abs_min_end' )
        ->select( 'appt.busy_datetime_start' )
        ->select( 'appt.busy_datetime_end' )
        ->select(
            '(SELECT JSON_ARRAYAGG( _assg.`agent_id`)
            FROM ' . AssignmentModel::get_tablename() . " AS _assg
            WHERE _assg.appointment_id = appt.appointment_id
            ) AS 'agent_ids'"
        )

        ->group_by( 'appt.appointment_id' );

        $builder = apply_filters( 'bookster_appointment_booked_query_builder', $builder );
        return $builder;
    }

    /**
     * @param int|null $customer_id
     * @return string The subquery to get bookings for an appointment
     */
    private function get_appt_bookings_subquery( $customer_id = null ) {
        $builder = BookingModel::create_where_builder( [], '_booking' );
        $builder = new QueryBuilder( BookingModel::TABLE . '_subquery' );

        $builder->from( BookingModel::TABLE . ' AS _booking' )
        ->join(
            CustomerModel::TABLE . ' as `_cust`',
            [ [ 'raw' => '_booking.customer_id = _cust.customer_id' ] ],
            'INNER'
        )
        ->where( [ 'raw' => '_booking.appointment_id = appt.appointment_id' ] );
        if ( ! is_null( $customer_id ) ) {
            $builder->where( [ '_booking.customer_id' => $customer_id ] );
        }

        // total_amount, paid_amount: string decimal
        $bookings_json_args = [
            "'booking_id'",
            '_booking.`booking_id`',
            "'customer_id'",
            '_booking.`customer_id`',
            "'total_amount'",
            'CAST(_booking.`total_amount` AS CHAR)',
            "'paid_amount'",
            'CAST(_booking.`paid_amount` AS CHAR)',
            "'payment_status'",
            '_booking.`payment_status`',
            "'booking_details'",
            '_booking.`booking_details`',
            "'customer_note'",
            '_booking.`customer_note`',
            "'_customer'",
            "JSON_OBJECT(
                'customer_id', _cust.`customer_id`,
                'first_name', _cust.`first_name`,
                'last_name', _cust.`last_name`,
                'email', _cust.`email`,
                'phone', _cust.`phone`
            )",
        ];

        $bookings_json_args = apply_filters( 'bookster_booking_subquery_json_args', $bookings_json_args );
        $builder->select( 'JSON_ARRAYAGG( JSON_OBJECT(' . implode( ', ', $bookings_json_args ) . '))' );

        $builder = apply_filters( 'bookster_booking_subquery_query_builder', $builder );
        return $builder->build_subquery();
    }
}
