<?php
namespace Bookster\Services;

use Bookster\Models\AvailableAgentServiceModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * AvailableAgentService Service
 *
 * @method static AvailableAgentServiceService get_instance()
 */
class AvailableAgentServiceService extends BaseService {
    use SingletonTrait;

    /**
     * Update/Insert avalability of multiple services and only one agent
     * Keep the avalability of (archived) services that are not in the list
     *
     * @param  int   $agent_id
     * @param  array $available_agent_services
     * @return void
     */
    public function update_by_agent( int $agent_id, array $available_agent_services = [] ) {
        global $wpdb;

        if ( empty( $available_agent_services ) ) {
            return;
        }

        $args = array_map(
            function ( $available_model ) use ( $agent_id ) {
                return [
                    $agent_id,
                    $available_model['service_id'],
                    $available_model['available'],
                ];
            },
            $available_agent_services
        );

        $query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    'INSERT INTO `' . $wpdb->prefix . AvailableAgentServiceModel::TABLE . '` (agent_id, service_id, available) VALUES '
                    . implode( ', ', array_fill( 0, count( $available_agent_services ), '(%d, %d, %d)' ) )
                    . ' ON DUPLICATE KEY UPDATE available = VALUES( available )',
                ],
                ...$args
            )
        );
        $this->exec_wpdb_query( $query );
    }

    /**
     * Update/Insert avalability of multiple agents and only one services
     * Keep the avalability of (archived) agents that are not in the list
     *
     * @param  int   $service_id
     * @param  array $available_agent_services
     * @return void
     */
    public function update_by_service( int $service_id, array $available_agent_services = [] ) {
        global $wpdb;

        if ( empty( $available_agent_services ) ) {
            return;
        }

        $args = array_map(
            function ( $available_model ) use ( $service_id ) {
                return [
                    $service_id,
                    $available_model['agent_id'],
                    $available_model['available'],
                ];
            },
            $available_agent_services
        );

        $query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    'INSERT INTO `' . $wpdb->prefix . AvailableAgentServiceModel::TABLE . '` (service_id, agent_id, available) VALUES '
                    . implode( ', ', array_fill( 0, count( $available_agent_services ), '(%d, %d, %d)' ) )
                    . ' ON DUPLICATE KEY UPDATE available = VALUES( available )',
                ],
                ...$args
            )
        );
        $this->exec_wpdb_query( $query );
    }
}
