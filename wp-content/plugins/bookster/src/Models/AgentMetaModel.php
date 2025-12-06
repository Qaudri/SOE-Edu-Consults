<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Agent Meta Model
 *
 * @property int $agent_meta_id
 * @property int $agent_id
 * @property string $meta_key
 * @property mixed $meta_value
 */
class AgentMetaModel extends DataModel {

    const TABLE = 'bookster_agent_metas';

    protected $primary_key = 'agent_meta_id';
    protected $properties  = [
        'agent_meta_id',
        'agent_id',
        'meta_key',
        'meta_value',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            agent_meta_id bigint(20) unsigned NOT NULL auto_increment,
            agent_id bigint(20) unsigned NOT NULL,
            meta_key varchar(127) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (agent_meta_id),
            UNIQUE KEY meta_key_idx (agent_id, meta_key)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'agent_meta_id',
        'agent_id',
    ];

    protected static $jsonarr_attributes = [
        'meta_value',
    ];
}
