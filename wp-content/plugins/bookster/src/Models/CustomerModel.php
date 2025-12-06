<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Customer Model
 *
 * @property int $customer_id
 * @property int $wp_user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $phone
 */
class CustomerModel extends DataModel {

    const TABLE = 'bookster_customers';

    protected $primary_key          = 'customer_id';
    protected $properties           = [
        'customer_id',
        'wp_user_id',
        'first_name',
        'last_name',
        'email',
        'phone',

        'avatar_id',
        'admin_note',

        'transient_avatar_url',
        'wp_user_display_name',
        'created_at',
        'updated_at',
    ];
    protected $protected_properties = [
        'customer_id',
        'created_at',
        'updated_at',

        'transient_avatar_url',
        'wp_user_display_name',
    ];

    protected static $keywords = [ 'first_name', 'last_name', 'email', 'phone' ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            customer_id bigint(20) unsigned NOT NULL auto_increment,
            wp_user_id bigint(20) unsigned,
            first_name varchar(127) NOT NULL,
            last_name varchar(127) NOT NULL,
            email varchar(127) NOT NULL,
            phone varchar(127),
            customer_note text, 
            admin_note text,
            avatar_id bigint(20) unsigned,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (customer_id),
            UNIQUE KEY email_idx (email),
            UNIQUE KEY wp_user_idx (wp_user_id)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'customer_id',
        'wp_user_id',
        'avatar_id',
    ];

    protected function getTransient_avatar_urlAlias() {
        if ( isset( $this->attributes['transient_avatar_url'] ) ) {
            return $this->attributes['transient_avatar_url'];
        }
        if ( ! $this->avatar_id ) {
            return '';
        }
        $this->attributes['transient_avatar_url'] = wp_get_attachment_image_url( $this->avatar_id, 'thumbnail' );
        return $this->attributes['transient_avatar_url'];
    }

    public function to_array_for_customer_role() {
        $arr = $this->to_array();
        unset( $arr['admin_note'] );
        return $arr;
    }
}
