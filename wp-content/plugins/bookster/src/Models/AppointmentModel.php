<?php
namespace Bookster\Models;

use Bookster\Models\Database\DataModel;

/**
 * Appointment Model
 *
 * @property int $appointment_id
 * @property int $service_id
 * @property ServiceModel $service
 * @property AgentModel[] $agents
 * @property BookingModel[] $bookings
 * @property string $book_status
 * @property string $datetime_start
 * @property string $datetime_end
 * @property string $busy_datetime_start
 * @property string $busy_datetime_end
 */
class AppointmentModel extends DataModel {

    const TABLE = 'bookster_appointments';

    protected $primary_key          = 'appointment_id';
    protected $properties           = [
        'appointment_id',
        'service_id',
        'location_id',

        'book_status',
        'datetime_start',
        'abs_min_start',
        'utc_datetime_start',

        'datetime_end',
        'abs_min_end',

        'busy_datetime_start',
        'busy_abs_min_start',

        'busy_datetime_end',
        'busy_abs_min_end',

        'buffer_before',
        'buffer_after',

        'staff_note',

        'service',
        'agent_ids',
        'agents',
        'bookings',
        'created_at',
        'updated_at',
    ];
    protected $protected_properties = [
        'appointment_id',
        'created_at',
        'updated_at',

        '_service',
        'service',
        'agent_ids',
        '_agents',
        'agents',
        '_bookings',
        'bookings',
        'booking',
    ];

    public static function get_schema() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $tablename       = static::get_tablename();

        return "CREATE TABLE $tablename (
            appointment_id bigint(20) unsigned NOT NULL auto_increment,
            service_id bigint(20) unsigned NOT NULL,
            location_id bigint(20) unsigned default NULL,
            book_status varchar(10) NOT NULL default 'pending',
            staff_note text,
            datetime_start datetime NOT NULL,
            datetime_end datetime NOT NULL,
            utc_datetime_start datetime NOT NULL,
            abs_min_start smallint(6) unsigned NOT NULL,
            abs_min_end smallint(6) unsigned NOT NULL,
            buffer_before smallint(6) NOT NULL default 0,
            buffer_after smallint(6) NOT NULL default 0,
            busy_abs_min_start smallint(6) unsigned NOT NULL,
            busy_abs_min_end smallint(6) unsigned NOT NULL,
            busy_datetime_start datetime NOT NULL,
            busy_datetime_end datetime NOT NULL,
            created_at timestamp NOT NULL default CURRENT_TIMESTAMP,
            updated_at timestamp NOT NULL default CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (appointment_id),
            KEY service_idx (service_id, datetime_start),
            KEY location_idx (location_id, datetime_start),
            KEY datetime_start_idx (datetime_start),
            KEY book_status_idx (book_status)
        ) $charset_collate;";
    }

    protected static $integer_attributes = [
        'appointment_id',
        'service_id',
        'location_id',

        'abs_min_start',
        'abs_min_end',
        'buffer_before',
        'buffer_after',
        'busy_abs_min_start',
        'busy_abs_min_end',
    ];

    protected static $jsonarr_attributes = [
        'agent_ids',
        '_service',
        '_agents',
        '_bookings',
    ];

    protected function getServiceAlias() {
        if ( isset( $this->attributes['service'] ) ) {
            return $this->attributes['service'];
        }
        if ( ! $this->_service ) {
            return null;
        }

        $this->attributes['service'] = ServiceModel::init_from_data( $this->_service );
        return $this->attributes['service'];
    }

    protected function getAgentsAlias() {
        if ( isset( $this->attributes['agents'] ) ) {
            return $this->attributes['agents'];
        }
        if ( ! $this->_agents ) {
            return [];
        }

        $agent_infos = $this->_agents;
        $agent_infos = array_filter(
            $agent_infos,
            function( $agent_info ) {
                return null !== $agent_info['agent_id'];
            }
        );

        $agent_infos = array_map(
            function( $agent_info ) {
                return AgentModel::init_from_data( $agent_info );
            },
            $agent_infos
        );

        $this->attributes['agents'] = $agent_infos;
        return $this->attributes['agents'];
    }

    protected function getBookingsAlias() {
        if ( isset( $this->attributes['bookings'] ) ) {
            return $this->attributes['bookings'];
        }
        if ( ! $this->_bookings ) {
            return [];
        }

        $booking_infos = $this->_bookings;
        $booking_infos = array_filter(
            $booking_infos,
            function( $booking_info ) {
                return null !== $booking_info['booking_id'];
            }
        );

        $booking_infos = array_map(
            function( $booking_info ) {
                return BookingModel::init_from_data( $booking_info );
            },
            $booking_infos
        );

        $this->attributes['bookings'] = $booking_infos;
        return $this->attributes['bookings'];
    }

    /**
     * Hidden staff_note and bookings.
     * Only get the one booking with current customer_id.
     *
     * @param int $customer_id
     */
    public function to_array_for_customer_role( $customer_id ) {
        $arr = $this->to_array();
        if ( isset( $arr['bookings'] ) && ! empty( $arr['bookings'] ) ) {
            foreach ( $arr['bookings'] as $booking ) {
                if ( $booking['customer_id'] === $customer_id ) {
                    $arr['booking'] = $booking;
                    break;
                }
            }
        }

        $arr['booking_count'] = count( $arr['bookings'] );
        unset( $arr['bookings'] );
        unset( $arr['staff_note'] );
        return $arr;
    }
}
