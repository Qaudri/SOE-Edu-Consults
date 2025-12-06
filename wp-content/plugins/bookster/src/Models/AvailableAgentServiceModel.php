<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Available: Agent => Service, One To One Model
 *
 * @property int $available_agent_service_id
 * @property int $agent_id
 * @property int $service_id
 * @property bool $available
 */
class AvailableAgentServiceModel extends DataModel {

    const TABLE = 'bookster_available_agent_service';

    protected $primary_key = 'available_agent_service_id';
    protected $properties  = [
        'available_agent_service_id',
        'agent_id',
        'service_id',
        'available',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            available_agent_service_id bigint(20) unsigned NOT NULL auto_increment,
            agent_id bigint(20) unsigned NOT NULL,
            service_id bigint(20) unsigned NOT NULL,
            available tinyint(1) NOT NULL default true,
            meta longtext,
            PRIMARY KEY  (available_agent_service_id),
            UNIQUE KEY agent_idx (agent_id, service_id),
            KEY service_idx (service_id)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'available_agent_service_id',
        'agent_id',
        'service_id',
    ];

    protected static $boolean_attributes = [
        'available',
    ];
}
