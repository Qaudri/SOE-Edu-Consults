<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Service Meta Model
 *
 * @property int $service_meta_id
 * @property int $service_id
 * @property string $meta_key
 * @property mixed $meta_value
 */
class ServiceMetaModel extends DataModel {

    const TABLE = 'bookster_service_metas';

    protected $primary_key = 'service_meta_id';
    protected $properties  = [
        'service_meta_id',
        'service_id',
        'meta_key',
        'meta_value',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            service_meta_id bigint(20) unsigned NOT NULL auto_increment,
            service_id bigint(20) unsigned NOT NULL,
            meta_key varchar(127) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (service_meta_id),
            UNIQUE KEY meta_key_idx (service_id, meta_key)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'service_meta_id',
        'service_id',
    ];

    protected static $jsonarr_attributes = [
        'meta_value',
    ];
}
