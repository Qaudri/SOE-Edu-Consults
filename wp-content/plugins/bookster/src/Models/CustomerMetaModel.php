<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Customer Meta Model
 *
 * @property int $customer_meta_id
 * @property int $customer_id
 * @property string $meta_key
 * @property mixed $meta_value
 */
class CustomerMetaModel extends DataModel {

    const TABLE = 'bookster_customer_metas';

    protected $primary_key = 'customer_meta_id';
    protected $properties  = [
        'customer_meta_id',
        'customer_id',
        'meta_key',
        'meta_value',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            customer_meta_id bigint(20) unsigned NOT NULL auto_increment,
            customer_id bigint(20) unsigned NOT NULL,
            meta_key varchar(127) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (customer_meta_id),
            UNIQUE KEY meta_key_idx (customer_id, meta_key)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'customer_meta_id',
        'customer_id',
    ];

    protected static $json_attributes = [
        'meta_value',
    ];
}
