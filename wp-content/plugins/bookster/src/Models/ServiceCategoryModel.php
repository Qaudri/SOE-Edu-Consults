<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Service Category Model
 *
 * @property int $service_category_id
 * @property string $name
 */
class ServiceCategoryModel extends DataModel {

    const TABLE = 'bookster_service_categories';

    protected $primary_key = 'service_category_id';
    protected $properties  = [
        'service_category_id',
        'name',
        'description',
        'position',
        'theme_color',

        'created_at',
        'updated_at',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            service_category_id bigint(20) unsigned NOT NULL auto_increment,
            name varchar(255) NOT NULL default '',
            description text,
            position smallint(6) NOT NULL default 1,
            theme_color varchar(10) NOT NULL default '#66cc8a',
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (service_category_id),
            UNIQUE KEY position_idx (position)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'service_category_id',
        'position',
    ];
}
