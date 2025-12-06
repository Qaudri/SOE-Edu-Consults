<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\ServiceModel;
use Bookster\Models\ServiceMetaModel;
use Bookster\Models\AvailableAgentServiceModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\AssignmentMetaModel;
use Bookster\Models\BookingModel;
use Bookster\Models\BookingMetaModel;
use Bookster\Models\AppointmentModel;
use Bookster\Models\AppointmentMetaModel;
use Bookster\Models\TransactionModel;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;
use Bookster\Models\Database\QueryBuilder;

/**
 * Service Service
 *
 * @method static ServicesService get_instance()
 */
class ServicesService extends BaseService {
    use SingletonTrait;

    /** @var AvailableAgentServiceService */
    private $available_agent_service_service;

    protected function __construct() {
        $this->available_agent_service_service = AvailableAgentServiceService::get_instance();
    }

    public function find_where( array $args ): array {
        $services = ServiceModel::where( $this->prepare_where_args( $args ) );
        $this->validate_wpdb_query();

        return $services;
    }

    /**
     * @param mixed[] $args
     * @return ServiceModel[]
     */
    public function find_where_with_info( array $args ): array {
        $builder  = $this->create_builder_find_where_with_info( $args );
        $services = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ ServiceModel::class, 'init_from_data' ], $services );
    }

    public function count_where( array $args ): int {
        $count = ServiceModel::count( $this->prepare_count_args( $args ) );
        $this->validate_wpdb_query();

        return $count;
    }

    public function find_by_id( int $service_id ): ServiceModel {
        $service = ServiceModel::find( $service_id );
        if ( ! $service ) {
            throw new NotFoundException( 'Service Not Found', ObjectTypeEnum::SERVICE, $service_id );
        }
        return $service;
    }

    public function find_by_id_with_info( int $service_id ): ServiceModel {
        $builder    = $this->create_builder_find_where_with_info( [ 'service.service_id' => $service_id ] );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        if ( ! $attributes ) {
            throw new NotFoundException( 'Service Not Found', ObjectTypeEnum::SERVICE, $service_id );
        }
        return ServiceModel::init_from_data( $attributes );
    }

    public function insert( array $attributes ): ServiceModel {
        global $wpdb;
        $tablename  = ServiceModel::get_tablename();
        $attributes = ServiceModel::prepare_saved_data( $attributes );

        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $tablename 
                SET position = position + 1
                WHERE service_category_id = %d
                ORDER BY position DESC",
                $attributes['service_category_id']
            )
        );

        $query = $wpdb->prepare(
            "INSERT INTO $tablename (
                name,
                description,
                service_category_id,
                theme_color,
                price,
                duration,
                buffer_before,
                buffer_after,
                cover_id,
                gallery_ids,
                activated,
                visibility,
                position 
                ) VALUES (
                    %s,%s,%d,%s,%f,%d,%d,%d,%d,%s,%s,%s,1
                )",
            $attributes['name'],
            $attributes['description'],
            $attributes['service_category_id'],
            $attributes['theme_color'],
            $attributes['price'],
            $attributes['duration'],
            $attributes['buffer_before'],
            $attributes['buffer_after'],
            $attributes['cover_id'],
            $attributes['gallery_ids'],
            $attributes['activated'],
            $attributes['visibility']
        );

        $this->exec_wpdb_query(
            $query,
            'Saving Service'
        );

        $service_id = $wpdb->insert_id;

        if ( ! empty( $attributes['available_agent_services'] ) ) {
            $this->available_agent_service_service->update_by_service( $service_id, $attributes['available_agent_services'] );
        }

        return $this->find_by_id_with_info( $service_id );
    }

    public function find_and_update_position( int $service_id, int $service_category_id, int $position ) {
        $service = $this->find_by_id( $service_id );

        $this->update_position(
            $service_id,
            $service->service_category_id,
            $service->position,
            $service_category_id,
            $position
        );
        return $this->find_by_id_with_info( $service->service_id );
    }

    private function update_position(
        int $service_id,
        int $old_service_category_id,
        int $old_position,
        int $new_service_category_id,
        int $new_position
    ) {

        global $wpdb;
        $service_table = ServiceModel::get_tablename();
        if ( $old_service_category_id === $new_service_category_id
            && $old_position < $new_position ) {
            // Adjust new position to account for Closing the gap

            ++$new_position;
        }

        // Make space for the new position
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $service_table
                SET position = position + 1
                WHERE service_category_id = %d AND position >= %d
                ORDER BY position DESC",
                $new_service_category_id,
                $new_position
            )
        );

        // Update the service
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $service_table,
                (SELECT IFNULL(max(position),0) + 1 as max_position FROM $service_table WHERE service_category_id = %d) as max_position
                SET service_category_id = %d, position = LEAST(%d, max_position.max_position)
                WHERE service_id = %d",
                $new_service_category_id,
                $new_service_category_id,
                $new_position,
                $service_id
            )
        );

        // Close the gap
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $service_table
                SET position = position - 1
                WHERE service_category_id = %d AND position > %d
                ORDER BY position ASC",
                $old_service_category_id,
                $old_position
            )
        );
    }

    public function update( int $service_id, array $data ): ServiceModel {
        $service = $this->find_by_id( $service_id );

        if ( isset( $data['service_category_id'] ) && $service->service_category_id !== $data['service_category_id'] ) {
            $this->update_position(
                $service_id,
                $service->service_category_id,
                $service->position,
                $data['service_category_id'],
                PHP_INT_MAX
            );
        }
        unset( $data['service_category_id'] );
        unset( $data['position'] );

        $success = $service->update( ServiceModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Service: ' . $wpdb->last_error ) );
        }

        if ( ! empty( $data['available_agent_services'] ) ) {
            $this->available_agent_service_service->update_by_service( $service_id, $data['available_agent_services'] );
        }

        return $this->find_by_id_with_info( $service->service_id );
    }

    public function delete( int $service_id ): bool {
        $service = $this->find_by_id( $service_id );

        $service_table                 = ServiceModel::get_tablename();
        $service_meta_table            = ServiceMetaModel::get_tablename();
        $available_agent_service_table = AvailableAgentServiceModel::get_tablename();

        $appointment_table      = AppointmentModel::get_tablename();
        $appointment_meta_table = AppointmentMetaModel::get_tablename();
        $assignment_table       = AssignmentModel::get_tablename();
        $assignment_meta_table  = AssignmentMetaModel::get_tablename();
        $booking_table          = BookingModel::get_tablename();
        $booking_meta_table     = BookingMetaModel::get_tablename();
        $transaction_table      = TransactionModel::get_tablename();

        global $wpdb;
        $query  = $wpdb->prepare(
            "DELETE service, service_meta, available_agent_service,
                appointment, appointment_meta_of_appointment, assignment_of_appointment, assignment_meta_of_appointment, booking_of_appointment, booking_meta_of_appointment, transaction_of_appointment
            FROM $service_table as service
            LEFT JOIN $service_meta_table as service_meta ON service_meta.service_id = service.service_id
            LEFT JOIN $available_agent_service_table as available_agent_service ON available_agent_service.service_id = service.service_id

            LEFT JOIN $appointment_table as appointment ON appointment.service_id = service.service_id
            LEFT JOIN $appointment_meta_table as appointment_meta_of_appointment ON appointment_meta_of_appointment.appointment_id = appointment.appointment_id

            LEFT JOIN $assignment_table as assignment_of_appointment ON assignment_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $assignment_meta_table as assignment_meta_of_appointment ON assignment_meta_of_appointment.appointment_id = assignment_of_appointment.appointment_id

            LEFT JOIN $booking_table as booking_of_appointment ON booking_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $booking_meta_table as booking_meta_of_appointment ON booking_meta_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $transaction_table as transaction_of_appointment ON transaction_of_appointment.appointment_id = appointment.appointment_id

            WHERE service.service_id=%d",
            $service_id
        );
        $result = $this->exec_wpdb_query( $query, 'Deleting Service' );

        // Close the gap
        $this->exec_wpdb_query(
            $wpdb->prepare(
                "UPDATE $service_table
                SET position = position - 1
                WHERE service_category_id = %d AND position >= %d
                ORDER BY position ASC",
                $service->service_category_id,
                $service->position
            )
        );

        return $result;
    }

    private function create_builder_find_where_with_info( array $args ): QueryBuilder {
        $builder = ServiceModel::create_where_builder( $this->prepare_where_args( $args ), 'service' );

        $builder->select( 'service.*' )
            ->select(
                "JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'available_agent_service_id', available.`available_agent_service_id`,
                        'agent_id', available.`agent_id`,
                        'service_id', available.`service_id`,
                        'available', available.`available`
                    )
                ) AS '_available_agent_services'"
            )
            ->join(
                AvailableAgentServiceModel::TABLE . ' as `available`',
                [ [ 'raw' => 'service.service_id = available.service_id' ] ],
                'LEFT'
            )
            ->group_by( 'service.service_id' );

        if ( ! isset( $args['order_by'] ) ) {
            $builder->order_by( 'service_category_id', 'ASC' );
            $builder->order_by( 'position', 'ASC' );
        }

        $builder = apply_filters( 'bookster_service_info_query_builder', $builder );
        return $builder;
    }
}
