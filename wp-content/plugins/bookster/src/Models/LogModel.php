<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Log Model
 *
 * @property int            $log_id
 * @property int|null       $log_object_id
 * @property string|null    $log_object_type
 * @property string         $log_level
 * @property string         $log_message
 * @property mixed          $log_detail
 */
class LogModel extends DataModel {

    const TABLE = 'bookster_logs';

    protected $primary_key = 'log_id';
    protected $properties  = [
        'log_id',
        'log_object_id',
        'log_object_type',
        'log_level',
        'log_message',
        'log_detail',

        'created_at',
        'updated_at',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            log_id bigint(20) unsigned NOT NULL auto_increment,
            log_object_id bigint(20) unsigned,
            log_object_type varchar(20) default 'general',
            log_level varchar(10) NOT NULL default 'info',
            log_message text,
            log_detail longtext,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (log_id),
            KEY log_object_idx (log_object_type, log_object_id),
            KEY log_level_idx (log_level)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'log_id',
        'log_object_id',
    ];

    protected static $jsonarr_attributes = [
        'log_detail',
    ];
}
