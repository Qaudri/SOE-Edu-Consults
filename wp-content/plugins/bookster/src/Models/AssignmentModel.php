<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Appointment => Agent, One To Many Model
 *
 * @property int $assignment_id
 * @property int $appointment_id
 * @property int $agent_id
 */
class AssignmentModel extends DataModel {

    const TABLE = 'bookster_assignments';

    protected $primary_key = 'assignment_id';
    protected $properties  = [
        'assignment_id',
        'appointment_id',
        'agent_id',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            assignment_id bigint(20) unsigned NOT NULL auto_increment,
            appointment_id bigint(20) unsigned NOT NULL,
            agent_id bigint(20) unsigned NOT NULL,
            PRIMARY KEY  (assignment_id),
            UNIQUE KEY appt_agent_idx (appointment_id, agent_id)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'assignment_id',
        'appointment_id',
        'agent_id',
    ];
}
