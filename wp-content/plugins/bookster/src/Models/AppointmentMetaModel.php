<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Appointment Meta Model
 *
 * @property int $appointment_meta_id
 * @property int $appointment_id
 * @property string $meta_key
 * @property mixed $meta_value
 */
class AppointmentMetaModel extends DataModel {

    const TABLE = 'bookster_appointment_metas';

    protected $primary_key = 'appointment_meta_id';
    protected $properties  = [
        'appointment_meta_id',
        'appointment_id',
        'meta_key',
        'meta_value',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            appointment_meta_id bigint(20) unsigned NOT NULL auto_increment,
            appointment_id bigint(20) unsigned NOT NULL,
            meta_key varchar(127) NOT NULL,
            meta_value longtext NOT NULL,
            PRIMARY KEY  (appointment_meta_id),
            UNIQUE KEY meta_key_idx (appointment_id, meta_key)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'appointment_meta_id',
        'appointment_id',
    ];

    protected static $jsonarr_attributes = [
        'meta_value',
    ];
}
