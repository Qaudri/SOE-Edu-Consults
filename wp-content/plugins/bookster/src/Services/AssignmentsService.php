<?php
namespace Bookster\Services;

use Bookster\Models\AssignmentModel;
use Bookster\Models\AssignmentMetaModel;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Logic for Assignment Models
 *
 * @method static AssignmentsService get_instance()
 */
class AssignmentsService extends BaseService {
    use SingletonTrait;

    public function update( $appointment_id, $agent_ids = [] ) {
        global $wpdb;
        $assignment_table      = AssignmentModel::get_tablename();
        $assignment_meta_table = AssignmentMetaModel::get_tablename();

        // unlink all agents not in the list
        $query = call_user_func_array(
            [ $wpdb, 'prepare' ],
            array_merge(
                [
                    "DELETE assignment, assignment_meta
                    FROM $assignment_table as assignment
                    LEFT JOIN $assignment_meta_table as assignment_meta ON assignment.appointment_id = assignment_meta.appointment_id AND assignment.agent_id = assignment_meta.agent_id
                    WHERE assignment.appointment_id = %d AND assignment.agent_id NOT IN (" . implode( ', ', array_fill( 0, count( $agent_ids ), '%d' ) ) . ' )',
                    $appointment_id,
                ],
                $agent_ids
            )
        );
        $this->exec_wpdb_query( $query );

        // link all agents in the list
        foreach ( $agent_ids as $agent_id ) {
            $this->exec_wpdb_query(
                $wpdb->prepare(
                    'INSERT INTO `' . $wpdb->prefix . AssignmentModel::TABLE . '` (appointment_id,agent_id) VALUES (%d,%d) ON DUPLICATE KEY UPDATE assignment_id = assignment_id',
                    $appointment_id,
                    $agent_id
                )
            );
        }
    }

    public function delete_by_appt_id( $appointment_id ) {
        $success = AssignmentModel::delete_where( [ 'appointment_id' => $appointment_id ] );

        global $wpdb;
        if ( false === $success ) {
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }

        $success = AssignmentMetaModel::delete_where( [ 'appointment_id' => $appointment_id ] );

        if ( false === $success ) {
            throw new \Exception( esc_html( 'Error Editing Data: ' . $wpdb->last_error ) );
        }
    }
}
