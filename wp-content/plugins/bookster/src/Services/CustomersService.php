<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\CustomerModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\AssignmentMetaModel;
use Bookster\Models\BookingModel;
use Bookster\Models\BookingMetaModel;
use Bookster\Models\AppointmentModel;
use Bookster\Models\AppointmentMetaModel;
use Bookster\Models\CustomerMetaModel;
use Bookster\Models\TransactionModel;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;
use Bookster\Features\Errors\ForbiddenException;
use Bookster\Models\Database\QueryBuilder;

/**
 * Customers Service
 *
 * @method static CustomersService get_instance()
 */
class CustomersService extends BaseService {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;
    /** @var WPUsersService */
    private $wp_users_service;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();
        $this->wp_users_service = WPUsersService::get_instance();
    }

    public function count_where( array $args ): int {
        $count = CustomerModel::count( $this->prepare_count_args( $args ) );
        $this->validate_wpdb_query();

        return $count;
    }

    /**
     * @param array $args
     * @return CustomerModel[]
     */
    public function find_where_with_info( array $args ): array {
        $builder   = $this->create_builder_find_where_with_info( $this->prepare_where_args( $args ) );
        $customers = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ CustomerModel::class, 'init_from_data' ], $customers );
    }

    /**
     * @param array $args
     * @return CustomerModel|null
     */
    public function find_one_with_info( array $args ) {
        $builder    = $this->create_builder_find_where_with_info( $this->prepare_where_args( $args ) );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        return ! empty( $attributes ) ? CustomerModel::init_from_data( $attributes ) : null;
    }

    /**
     * Select Customer by ID with Included Info.
     *
     * @param int $customer_id
     * @return CustomerModel
     */
    public function find_by_id_with_info( int $customer_id ): CustomerModel {
        $builder    = $this->create_builder_find_where_with_info( [ 'customer.customer_id' => $customer_id ] );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        if ( ! $attributes ) {
            throw new NotFoundException( 'Customer Not Found', ObjectTypeEnum::CUSTOMER, $customer_id );
        }

        return CustomerModel::init_from_data( $attributes );
    }

    /**
     * Select Customer by ID.
     *
     * @param int $customer_id
     * @return CustomerModel
     */
    public function find_by_id( int $customer_id ): CustomerModel {
        $customer = CustomerModel::find( $customer_id );
        if ( ! $customer ) {
            throw new NotFoundException( 'Customer Not Found', ObjectTypeEnum::CUSTOMER, $customer_id );
        }
        return $customer;
    }

    public function insert( array $attributes ): CustomerModel {
        if ( $this->is_auto_link_wp_users() ) {
            $attributes['wp_user_id'] = $this->connect(
                $attributes['email'],
                $attributes['first_name'],
                $attributes['last_name']
            );
        }

        $customer = CustomerModel::insert( CustomerModel::prepare_saved_data( $attributes ) );
        if ( is_null( $customer ) ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Customer: ' . $wpdb->last_error ) );
        }

        return $this->find_by_id_with_info( $customer->customer_id );
    }

    public function update( int $customer_id, array $data ): CustomerModel {
        $customer = CustomerModel::find( $customer_id );
        if ( ! $customer ) {
            throw new NotFoundException( 'Customer Not Found', ObjectTypeEnum::CUSTOMER, $customer_id );
        }

        $success = $customer->update( CustomerModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Customer: ' . $wpdb->last_error ) );
        }
        return $this->find_by_id_with_info( $customer->customer_id );
    }

    public function connect( string $email, string $first_name, string $last_name ): int {
        return $this->wp_users_service->maybe_generate_wp_user(
            $email,
            $first_name,
            $last_name
        );
    }

    public function delete( int $customer_id ): bool {
        $customer = CustomerModel::find( $customer_id );
        if ( ! $customer ) {
            throw new NotFoundException( 'Customer Not Found', ObjectTypeEnum::CUSTOMER, $customer_id );
        }

        $customer_table      = CustomerModel::get_tablename();
        $customer_meta_table = CustomerMetaModel::get_tablename();

        $appointment_table      = AppointmentModel::get_tablename();
        $appointment_meta_table = AppointmentMetaModel::get_tablename();
        $assignment_table       = AssignmentModel::get_tablename();
        $assignment_meta_table  = AssignmentMetaModel::get_tablename();
        $booking_table          = BookingModel::get_tablename();
        $booking_meta_table     = BookingMetaModel::get_tablename();
        $transaction_table      = TransactionModel::get_tablename();

        global $wpdb;
        $query = $wpdb->prepare(
            "DELETE customer, customer_meta, booking,
                appointment, appointment_meta_of_appointment, assignment_of_appointment, assignment_meta_of_appointment, booking_of_appointment, booking_meta_of_appointment, transaction_of_appointment
            FROM $customer_table as customer
            LEFT JOIN $customer_meta_table as customer_meta ON customer_meta.customer_id = customer.customer_id

            LEFT JOIN $booking_table as booking ON booking.customer_id = customer.customer_id
            LEFT JOIN $appointment_table as appointment ON appointment.appointment_id = booking.appointment_id
            LEFT JOIN $appointment_meta_table as appointment_meta_of_appointment ON appointment_meta_of_appointment.appointment_id = appointment.appointment_id

            LEFT JOIN $assignment_table as assignment_of_appointment ON assignment_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $assignment_meta_table as assignment_meta_of_appointment ON assignment_meta_of_appointment.appointment_id = assignment_of_appointment.appointment_id

            LEFT JOIN $booking_table as booking_of_appointment ON booking_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $booking_meta_table as booking_meta_of_appointment ON booking_meta_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $transaction_table as transaction_of_appointment ON transaction_of_appointment.appointment_id = appointment.appointment_id

            WHERE customer.customer_id=%d",
            $customer_id
        );
        return $this->exec_wpdb_query( $query, 'Deleting Customer' );
    }

    public function is_auto_link_wp_users(): bool {
        $permissions_settings = $this->settings_service->get_manager_data()['permissionsSettings'];
        return 'auto' === $permissions_settings['customers_link_wp_users'];
    }

    public function require_customer_is_current_user( int $customer_id ): CustomerModel {
        if ( ! is_user_logged_in() ) {
            throw new ForbiddenException( 'Please Login !!' );
        }

        $customer = $this->find_by_id_with_info( $customer_id );

        $wp_user = wp_get_current_user();
        if ( isset( $customer->wp_user_id ) && $wp_user->ID !== $customer->wp_user_id ) {
            throw new ForbiddenException( "You don't have Permisson to do this !!" );

        } elseif ( ! isset( $customer->wp_user_id ) ) {
            if ( $wp_user->user_email !== $customer->email ) {
                throw new ForbiddenException( "You don't have Permisson to do this !!" );
            } else {
                // Link registered customer with the same email
                $customer->update( [ 'wp_user_id' => $wp_user->ID ] );
            }
        }

        return $customer;
    }

    public function get_customer_meta( int $customer_id, string $meta_key ) {
        return CustomerMetaModel::find_where(
            [
                'customer_id' => $customer_id,
                'meta_key'    => $meta_key,
            ]
        );
    }

    public function update_customer_meta( int $customer_id, string $meta_key, $meta_value ): CustomerMetaModel {
        $meta_model = $this->get_customer_meta( $customer_id, $meta_key );

        if ( null === $meta_model ) {
            return CustomerMetaModel::insert(
                CustomerMetaModel::prepare_saved_data(
                    [
                        'customer_id' => $customer_id,
                        'meta_key'    => $meta_key,
                        'meta_value'  => $meta_value,
                    ]
                )
            );
        } else {
            $meta_model->update(
                CustomerMetaModel::prepare_saved_data(
                    [
                        'meta_value' => $meta_value,
                    ]
                )
            );

            return $meta_model;
        }//end if
    }

    public function delete_customer_meta( int $customer_id, string $meta_key ) {
        return CustomerMetaModel::delete_where(
            [
                'customer_id' => $customer_id,
                'meta_key'    => $meta_key,
            ]
        );
    }

    public function update_multiple_customer_meta( int $customer_id, array $meta_input ) {
        global $wpdb;
        // Delete all meta input where value = null
        foreach ( $meta_input as $key => $value ) {
            if ( null === $value ) {
                $this->delete_customer_meta( $customer_id, $key );
            }
        }
        $upsert_input = array_filter(
            $meta_input,
            function( $value ) {
                return null !== $value;
            }
        );

        if ( empty( $upsert_input ) ) {
            return;
        }
        $upsert_args = [];
        foreach ( $upsert_input as $key => $value ) {
            $upsert_args[] = [
                $customer_id,
                $key,
                wp_json_encode( $value ),
            ];
        }

        $query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    'INSERT INTO `' . $wpdb->prefix . CustomerMetaModel::TABLE . '` (customer_id, meta_key, meta_value) VALUES '
                    . implode( ', ', array_fill( 0, count( $upsert_input ), '(%d, %s, %s)' ) )
                    . ' ON DUPLICATE KEY UPDATE meta_value = VALUES( meta_value )',
                ],
                ...$upsert_args
            )
        );
        $this->exec_wpdb_query( $query );
    }

    private function create_builder_find_where_with_info( array $args ): QueryBuilder {
        global $wpdb;
        $builder = CustomerModel::create_where_builder( $this->prepare_where_args( $args ), 'customer' );

        $builder->select( 'customer.*' )
            ->select( "users.`display_name` as 'wp_user_display_name'" )
            ->join(
                "$wpdb->users as `users`",
                [ [ 'raw' => 'customer.wp_user_id = users.ID' ] ],
                'LEFT',
                false
            );

        $builder = apply_filters( 'bookster_customer_info_query_builder', $builder, $args );
        return $builder;
    }
}
