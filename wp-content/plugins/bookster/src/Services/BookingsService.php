<?php
namespace Bookster\Services;

use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\BookingModel;
use Bookster\Models\CustomerModel;
use Bookster\Models\Database\QueryBuilder;
use Bookster\Features\Booking\Details;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;

/**
 * Logic for Booking Models
 *
 * @method static BookingsService get_instance()
 */
class BookingsService extends BaseService {
    use SingletonTrait;

    /**
     * Get Model with Customer Info
     *
     * @param int $booking_id
     * @return BookingModel
     */
    public function get_booking( $booking_id ) {
        $builder    = $this->create_builder_find_where_with_info( [ 'booking_id' => $booking_id ] );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        if ( ! $attributes ) {
            throw new NotFoundException( 'Booking Not Found', ObjectTypeEnum::BOOKING, $booking_id );
        }
        return BookingModel::init_from_data( $attributes );
    }

    /**
     * @param int $appointment_id
     * @return BookingModel[]
     */
    public function get_bookings( $appointment_id ) {
        $builder  = $this->create_builder_find_where_with_info( [ 'appointment_id' => $appointment_id ] );
        $bookings = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ BookingModel::class, 'init_from_data' ], $bookings );
    }

    /**
     * @param array $attributes
     * @return BookingModel
     */
    public function insert( $attributes ) {
        $booking = BookingModel::insert( BookingModel::prepare_saved_data( $attributes ) );
        if ( is_null( $booking ) ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Booking: ' . $wpdb->last_error ) );
        }

        $booking = $this->get_booking( $booking->booking_id );
        return $booking;
    }

    /**
     * @param int   $booking_id
     * @param array $attributes
     * @return BookingModel
     */
    public function update( $booking_id, array $attributes ) {
        $booking = BookingModel::find( $booking_id );
        if ( ! $booking ) {
            throw new NotFoundException( 'Booking Not Found', ObjectTypeEnum::BOOKING, $booking_id );
        }

        $success = $booking->update( BookingModel::prepare_saved_data( $attributes ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }

        return $booking;
    }

    /**
     * @param int   $appt_id
     * @param int   $customer_id
     * @param array $attributes
     */
    public function update_by_customer_id( $appt_id, $customer_id, $attributes ) {
        $booking = BookingModel::find_where(
            [
                'customer_id'    => $customer_id,
                'appointment_id' => $appt_id,
            ]
        );
        if ( ! $booking ) {
            throw new NotFoundException(
                "Booking Not Found: appointment_id $appt_id, customer_id $customer_id",
                ObjectTypeEnum::BOOKING
            );
        }

        $success = $booking->update( BookingModel::prepare_saved_data( $attributes ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }
    }

    /**
     * @param int   $appt_id
     * @param array $bookings_input
     */
    public function update_by_bookings_input( $appt_id, $bookings_input ) {
        $bookings = $this->get_bookings( $appt_id );

        foreach ( $bookings as $booking ) {
            $booking_input = null;

            foreach ( $bookings_input as $booking_input ) {
                if ( $booking_input['customer_id'] === $booking->customer_id ) {
                    $booking_input = $booking_input;
                    break;
                }
            }

            if ( null === $booking_input ) {
                $booking->delete();

            } else {
                $success = $booking->update( BookingModel::prepare_saved_data( $booking_input ) );
                if ( false === $success ) {
                    global $wpdb;
                    throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
                }
            }
        }//end foreach
    }

    public function delete_by_appt_id( $appointment_id ) {
        $success = BookingModel::delete_where( [ 'appointment_id' => $appointment_id ] );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }
    }

    private function create_builder_find_where_with_info( array $args ): QueryBuilder {
        $builder = BookingModel::create_where_builder( $args, 'booking' );
        $builder->join(
            CustomerModel::TABLE . ' as `cust`',
            [ [ 'raw' => 'booking.customer_id = cust.customer_id' ] ],
            'LEFT'
        );

        $builder->select( 'booking.*' )
        ->select(
            "JSON_OBJECT(
                'customer_id', cust.`customer_id`,
                'first_name', cust.`first_name`,
                'last_name', cust.`last_name`,
                'email', cust.`email`,
                'phone', cust.`phone`
            ) AS '_customer'"
        );

        $builder = apply_filters( 'bookster_booking_info_query_builder', $builder );
        return $builder;
    }



    /**
     * Generate default booking details
     *
     * @param  string $total_amount
     * @param  string $service_name
     */
    private function gen_default_details( $total_amount, $service_name ) {
        $total_amount = (float) $total_amount;

        return Details::from_json(
            [
                'booking'    => [
                    'items'    => [
                        [
                            'id'        => 'aaa-bbb-ccc-ddd',
                            'title'     => $service_name,
                            'quantity'  => 1,
                            'unitPrice' => $total_amount,
                            'amount'    => $total_amount,
                        ],
                    ],
                    'subtotal' => $total_amount,
                ],
                'adjustment' => [
                    'items'    => [],
                    'subtotal' => $total_amount,
                ],
                'tax'        => [
                    'items' => [],
                    'total' => $total_amount,
                ],
            ]
        );
    }
}
