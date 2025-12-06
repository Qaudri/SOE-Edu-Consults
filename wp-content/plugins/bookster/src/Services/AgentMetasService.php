<?php
namespace Bookster\Services;

use Bookster\Models\AgentMetaModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Agent -> Meta Service
 *
 * @method static AgentMetasService get_instance()
 */
class AgentMetasService extends BaseService {
    use SingletonTrait;

    /**
     * @param  int $agent_id
     * @return AgentMetaModel[]
     */
    public function get_by_agent_id( int $agent_id ) {
        return AgentMetaModel::where( [ 'agent_id' => $agent_id ] );
    }

    /**
     * @param  int    $agent_id
     * @param  string $meta_key
     * @return AgentMetaModel|null
     */
    public function get_by_meta_key( int $agent_id, string $meta_key ) {
        return AgentMetaModel::find_where(
            [
                'agent_id' => $agent_id,
                'meta_key' => $meta_key,
            ]
        );
    }

    public function insert( int $agent_id, string $meta_key, $meta_value = '' ) {
        $meta_model = AgentMetaModel::insert(
            AgentMetaModel::prepare_saved_data(
                [
                    'agent_id'   => $agent_id,
                    'meta_key'   => $meta_key,
                    'meta_value' => $meta_value,
                ]
            )
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Agent Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    public function update( int $agent_id, string $meta_key, $meta_value = '' ) {
        $meta_model = AgentMetaModel::update_where(
            AgentMetaModel::prepare_saved_data( [ 'meta_value' => $meta_value ] ),
            [
                'agent_id' => $agent_id,
                'meta_key' => $meta_key,
            ]
        );

        if ( false === $meta_model ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Agent Meta: ' . $wpdb->last_error ) );
        }

        return $meta_model;
    }

    /**
     * @param  int    $agent_id
     * @param  string $meta_key
     * @param  mixed  $meta_value
     * @return AgentMetaModel
     */
    public function upsert( int $agent_id, string $meta_key, $meta_value = '' ) {
        $meta_model = $this->get_by_meta_key( $agent_id, $meta_key );

        if ( $meta_model ) {
            return $this->update( $agent_id, $meta_key, $meta_value );
        }

        return $this->insert( $agent_id, $meta_key, $meta_value );
    }

    public function delete( int $agent_id, string $meta_key ) {
        $success = AgentMetaModel::delete_where(
            [
                'agent_id' => $agent_id,
                'meta_key' => $meta_key,
            ]
        );

        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Deleting Agent Meta: ' . $wpdb->last_error ) );
        }

        return $success;
    }
}
