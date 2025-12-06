<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Transaction Model
 *
 * @property int $transaction_id
 * @property string $transaction_secret_id
 * @property int $appointment_id
 * @property int $customer_id
 * @property string $payment_gateway
 * @property string $amount Decimal
 * @property string $transaction_status
 * @property string $transaction_message
 * @property string $transaction_link
 * @property object $transaction_meta
 */
class TransactionModel extends DataModel {

    const TABLE = 'bookster_transactions';

    protected $primary_key = 'transaction_id';
    protected $properties  = [
        'transaction_id',
        'transaction_secret_id',
        'appointment_id',
        'customer_id',

        'payment_gateway',
        'amount',
        'transaction_status',
        'transaction_message',
        'transaction_link',
        'transaction_meta',

        'created_at',
        'updated_at',
    ];

    protected static $keywords = [];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            transaction_id bigint(20) unsigned NOT NULL auto_increment,
            transaction_secret_id varchar(255),
            appointment_id bigint(20) unsigned,
            customer_id bigint(20) unsigned,
            payment_gateway varchar(255) NOT NULL,
            amount decimal(13,2) NOT NULL,
            transaction_status varchar(10) NOT NULL default 'created',
            transaction_message text,
            transaction_link varchar(255),
            transaction_meta longtext,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (transaction_id),
            KEY transaction_secret_idx (transaction_secret_id),
            KEY appt_customer_idx (appointment_id, customer_id)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'transaction_id',
        'appointment_id',
        'customer_id',
    ];

    protected static $json_attributes = [
        'transaction_meta',
    ];

    public function to_client_array() {
        $transaction_array = $this->to_array();

        unset( $transaction_array['transaction_secret_id'] );
        unset( $transaction_array['transaction_message'] );
        unset( $transaction_array['transaction_link'] );
        unset( $transaction_array['transaction_meta'] );

        return $transaction_array;
    }
}
