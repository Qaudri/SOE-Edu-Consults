<?php
namespace Bookster\Services;

use Bookster\Models\BookingMetaModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Booking -> Meta Service
 *
 * @method static BookingMetasService get_instance()
 */
class BookingMetasService extends BaseService {
    use SingletonTrait;

    /**
     * @param  int $appointment_id
     * @param  int $customer_id
     * @return BookingMetaModel[]
     */
    public function get_by_booking( $appointment_id, $customer_id ) {
        return BookingMetaModel::where(
            [
                'appointment_id' => $appointment_id,
                'customer_id'    => $customer_id,
            ]
        );
    }

    /**
     * @param  int    $appointment_id
     * @param  int    $customer_id
     * @param  string $meta_key
     * @return BookingMetaModel|null
     */
    public function get_by_meta_key( $appointment_id, $customer_id, $meta_key ) {
        return BookingMetaModel::find_where(
            [
                'appointment_id' => $appointment_id,
                'customer_id'    => $customer_id,
                'meta_key'       => $meta_key,
            ]
        );
    }

    /**
     * @param  int    $appointment_id
     * @param  int    $customer_id
     * @param  string $meta_key
     * @param mixed  $meta_value
     * @return BookingMetaModel
     */
    public function insert( $appointment_id, $customer_id, $meta_key, $meta_value = '' ) {
        $meta_model = BookingMetaModel::insert(
            BookingMetaModel::prepare_saved_data(
                [
                    'appointment_id' => $appointment_id,
                    'customer_id'    => $customer_id,
                    'meta_key'       => $meta_key,
                    'meta_value'     => $meta_value,
                ]
            )
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Booking Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    /**
     * @param  int    $appointment_id
     * @param  int    $customer_id
     * @param  string $meta_key
     * @param mixed  $meta_value
     * @return BookingMetaModel
     */
    public function update( $appointment_id, $customer_id, $meta_key, $meta_value = '' ) {
        $meta_model = BookingMetaModel::update_where(
            BookingMetaModel::prepare_saved_data( [ 'meta_value' => $meta_value ] ),
            [
                'appointment_id' => $appointment_id,
                'customer_id'    => $customer_id,
                'meta_key'       => $meta_key,
            ]
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Booking Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    /**
     * @param  int    $appointment_id
     * @param  int    $customer_id
     * @param  string $meta_key
     * @return bool
     */
    public function delete_by_metakey( $appointment_id, $customer_id, $meta_key ) {
        $success = BookingMetaModel::delete_where(
            [
                'appointment_id' => $appointment_id,
                'customer_id'    => $customer_id,
                'meta_key'       => $meta_key,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Booking Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }

    /**
     * @param  int $appointment_id
     * @param  int $customer_id
     * @return bool
     */
    public function delete_by_booking( $appointment_id, $customer_id ) {
        $success = BookingMetaModel::delete_where(
            [
                'appointment_id' => $appointment_id,
                'customer_id'    => $customer_id,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Booking Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }

    /**
     * @param  int $appointment_id
     * @return bool
     */
    public function delete_by_appt_id( $appointment_id ) {
        $success = BookingMetaModel::delete_where(
            [
                'appointment_id' => $appointment_id,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Booking Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }

    /**
     * Upsert meta value for a meta
     *
     * @param  int    $appointment_id
     * @param  int    $customer_id
     * @param  string $meta_key
     * @param  mixed  $meta_value
     * @return void
     */
    public function upsert( $appointment_id, $customer_id, $meta_key, $meta_value ) {
        global $wpdb;
        $booking_metas_tablename = BookingMetaModel::get_tablename();

        $query = $wpdb->prepare(
            "INSERT INTO $booking_metas_tablename (appointment_id, customer_id, meta_key, meta_value) VALUES (%d, %d, %s, %s)
            ON DUPLICATE KEY UPDATE meta_value = VALUES( meta_value )",
            $appointment_id,
            $customer_id,
            $meta_key,
            wp_json_encode( $meta_value )
        );
        $this->exec_wpdb_query( $query );
    }

    /**
     * Upsert multiple meta values for an booking
     *
     * @param  int   $appointment_id
     * @param  int   $customer_id
     * @param  array $meta_input [meta_key => meta_value] meta_value can be null to delete meta.
     * @return void
     */
    public function upsert_multiple( $appointment_id, $customer_id, $meta_input ) {
        global $wpdb;

        foreach ( $meta_input as $key => $value ) {
            if ( null === $value ) {
                $this->delete_by_metakey( $appointment_id, $customer_id, $key );
            }
        }

        $upsert_intput = array_filter(
            $meta_input,
            function ( $value ) {
                return null !== $value;
            }
        );

        if ( empty( $upsert_intput ) ) {
            return;
        }

        $upsert_args = [];
        foreach ( $upsert_intput as $key => $value ) {
            $upsert_args[] = [
                $appointment_id,
                $customer_id,
                $key,
                wp_json_encode( $value ),
            ];
        }

        $booking_metas_tablename = BookingMetaModel::get_tablename();
        $query                   = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    "INSERT INTO $booking_metas_tablename (appointment_id, customer_id, meta_key, meta_value) VALUES "
                    . implode( ', ', array_fill( 0, count( $upsert_intput ), '(%d, %d, %s, %s)' ) )
                    . ' ON DUPLICATE KEY UPDATE meta_value = VALUES( meta_value )',
                ],
                ...$upsert_args
            )
        );
        $this->exec_wpdb_query( $query );
    }

    /**
     * Prepare payload. Make sure Frontend receive object instead of array.
     *
     * @param array $meta_models
     * @return array
     */
    public function to_json_object( $meta_models ) {
        if ( empty( $meta_models ) ) {
            return [ 'emptyObj' => true ];
        }

        $metas = [];
        foreach ( $meta_models as $meta_model ) {
            $metas[ $meta_model->meta_key ] = $meta_model->meta_value;
        }

        return $metas;
    }
}
