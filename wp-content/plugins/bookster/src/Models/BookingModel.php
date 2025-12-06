<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Appointment => Customer, One To Many Model
 *
 * @property int $booking_id
 * @property int $appointment_id
 * @property int $customer_id
 * @property string $total_amount
 * @property string $paid_amount
 * @property string $payment_status
 * @property mixed[] $booking_details
 * @property CustomerModel $customer
 */
class BookingModel extends DataModel {

    const TABLE = 'bookster_bookings';

    protected $primary_key          = 'booking_id';
    protected $properties           = [
        'booking_id',
        'appointment_id',
        'customer_id',

        'total_amount',
        'paid_amount',
        'payment_status',
        'booking_details',

        'customer_note',
        'customer',
    ];
    protected $protected_properties = [
        'booking_id',
        'created_at',
        'updated_at',

        '_customer',
        'customer',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            booking_id bigint(20) unsigned NOT NULL auto_increment,
            appointment_id bigint(20) unsigned NOT NULL,
            customer_id bigint(20) unsigned NOT NULL,
            total_amount decimal(13,2) NOT NULL default 0,
            paid_amount decimal(13,2) NOT NULL default 0,
            payment_status varchar(10),
            booking_details text,
            customer_note text,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (booking_id),
            UNIQUE KEY appt_customer_idx (appointment_id, customer_id),
            KEY payment_status_idx (payment_status)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'booking_id',
        'appointment_id',
        'customer_id',
    ];

    protected static $jsonarr_attributes = [
        'booking_details',
        '_customer',
    ];

    protected function getCustomerAlias() {
        if ( isset( $this->attributes['customer'] ) ) {
            return $this->attributes['customer'];
        }
        if ( ! $this->_customer ) {
            return null;
        }

        $this->attributes['customer'] = CustomerModel::init_from_data( $this->_customer );
        return $this->attributes['customer'];
    }
}
