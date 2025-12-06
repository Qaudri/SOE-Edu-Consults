<?php
namespace Bookster\Services;

use Bookster\Models\AppointmentMetaModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Appointment -> Meta Service
 *
 * @method static AppointmentMetasService get_instance()
 */
class AppointmentMetasService extends BaseService {
    use SingletonTrait;

    /**
     * @param  int $appointment_id
     * @return AppointmentMetaModel[]
     */
    public function get_by_appt_id( int $appointment_id ) {
        return AppointmentMetaModel::where( [ 'appointment_id' => $appointment_id ] );
    }

    /**
     * @param  int    $appointment_id
     * @param  string $meta_key
     * @return AppointmentMetaModel|null
     */
    public function get_by_meta_key( int $appointment_id, string $meta_key ) {
        return AppointmentMetaModel::find_where(
            [
                'appointment_id' => $appointment_id,
                'meta_key'       => $meta_key,
            ]
        );
    }

    public function insert( int $appointment_id, string $meta_key, $meta_value = '' ) {
        $meta_model = AppointmentMetaModel::insert(
            AppointmentMetaModel::prepare_saved_data(
                [
                    'appointment_id' => $appointment_id,
                    'meta_key'       => $meta_key,
                    'meta_value'     => $meta_value,
                ]
            )
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Appointment Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    public function update( int $appointment_id, string $meta_key, $meta_value = '' ) {
        $meta_model = AppointmentMetaModel::update_where(
            AppointmentMetaModel::prepare_saved_data( [ 'meta_value' => $meta_value ] ),
            [
                'appointment_id' => $appointment_id,
                'meta_key'       => $meta_key,
            ]
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Appointment Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    /**
     * @param  int    $appointment_id
     * @param  string $meta_key
     * @param  mixed  $meta_value
     */
    public function upsert( int $appointment_id, string $meta_key, $meta_value = '' ) {
        $meta_model = $this->get_by_meta_key( $appointment_id, $meta_key );

        if ( $meta_model ) {
            return $this->update( $appointment_id, $meta_key, $meta_value );
        }

        return $this->insert( $appointment_id, $meta_key, $meta_value );
    }

    public function delete( int $appointment_id, string $meta_key ) {
        $success = AppointmentMetaModel::delete_where(
            [
                'appointment_id' => $appointment_id,
                'meta_key'       => $meta_key,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Appointment Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }

    public function delete_by_appt_id( $appointment_id ) {
        $success = AppointmentMetaModel::delete_where( [ 'appointment_id' => $appointment_id ] );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }

        return $success;
    }

    /**
     * Upsert multiple meta values for an appointment
     *
     * @param  int   $appt_id
     * @param  array $meta_input [meta_key => meta_value] meta_value can be null to delete meta.
     * @return void
     */
    public function upsert_multiple( int $appt_id, $meta_input ) {
        global $wpdb;

        foreach ( $meta_input as $key => $value ) {
            if ( null === $value ) {
                $this->delete( $appt_id, $key );
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
                $appt_id,
                $key,
                wp_json_encode( $value ),
            ];
        }

        $query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    'INSERT INTO `' . $wpdb->prefix . AppointmentMetaModel::TABLE . '` (appointment_id, meta_key, meta_value) VALUES '
                    . implode( ', ', array_fill( 0, count( $upsert_intput ), '(%d, %s, %s)' ) )
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
