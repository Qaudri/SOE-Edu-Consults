<?php
namespace Bookster\Services;

use Bookster\Models\ServiceMetaModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Service -> Meta Service
 *
 * @method static ServiceMetasService get_instance()
 */
class ServiceMetasService extends BaseService {
    use SingletonTrait;

    /**
     * @param  int $service_id
     * @return ServiceMetaModel[]
     */
    public function get_by_service_id( int $service_id ) {
        return ServiceMetaModel::where( [ 'service_id' => $service_id ] );
    }

    /**
     * @param  int    $service_id
     * @param  string $meta_key
     * @return ServiceMetaModel|null
     */
    public function get_by_meta_key( int $service_id, string $meta_key ) {
        return ServiceMetaModel::find_where(
            [
                'service_id' => $service_id,
                'meta_key'   => $meta_key,
            ]
        );
    }

    public function insert( int $service_id, string $meta_key, $meta_value = '' ) {
        $meta_model = ServiceMetaModel::insert(
            ServiceMetaModel::prepare_saved_data(
                [
                    'service_id' => $service_id,
                    'meta_key'   => $meta_key,
                    'meta_value' => $meta_value,
                ]
            )
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Service Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    public function update( int $service_id, string $meta_key, $meta_value = '' ) {
        $meta_model = ServiceMetaModel::update_where(
            ServiceMetaModel::prepare_saved_data( [ 'meta_value' => $meta_value ] ),
            [
                'service_id' => $service_id,
                'meta_key'   => $meta_key,
            ]
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Service Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    /**
     * @param  int    $service_id
     * @param  string $meta_key
     * @param  mixed  $meta_value
     * @return ServiceMetaModel
     */
    public function upsert( int $service_id, string $meta_key, $meta_value = '' ) {
        $meta_model = $this->get_by_meta_key( $service_id, $meta_key );

        if ( $meta_model ) {
            return $this->update( $service_id, $meta_key, $meta_value );
        }

        return $this->insert( $service_id, $meta_key, $meta_value );
    }

    public function delete( int $service_id, string $meta_key ) {
        $success = ServiceMetaModel::delete_where(
            [
                'service_id' => $service_id,
                'meta_key'   => $meta_key,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Service Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }
}
