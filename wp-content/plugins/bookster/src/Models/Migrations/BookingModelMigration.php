<?php
namespace Bookster\Models\Migrations;

use Bookster\Models\AppointmentModel;
use Bookster\Models\AppointmentMetaModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\BookingModel;
use Bookster\Models\BookingMetaModel;
use Bookster\Models\TransactionModel;

/**
 * Migration for BookingModel v2.0
 */
class BookingModelMigration {

    public static function do_migration_v2_0() {
        self::move_legacy_table_to_new_table();
        self::move_appt_fields_to_booking_fields();
        self::move_appt_metadata_to_booking_metadata();
        self::add_customer_id_to_transaction();
    }

    /**
     * Move columns from bookster_appointment_customers to bookster_bookings
     * Move columns from bookster_appointment_agents to bookster_assignments
     */
    private static function move_legacy_table_to_new_table() {
        global $wpdb;
        $booking_table    = BookingModel::get_tablename();
        $assignment_table = AssignmentModel::get_tablename();
        $appt_cust_table  = $wpdb->prefix . 'bookster_appointment_customers';
        $appt_agent_table = $wpdb->prefix . 'bookster_appointment_agents';

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "INSERT INTO $booking_table (`appointment_id`, `customer_id`)
            SELECT appt_cust.`appointment_id`, appt_cust.`customer_id`
            FROM $appt_cust_table as appt_cust
            ON DUPLICATE KEY UPDATE `booking_id` = `booking_id`;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Booking Model V2.0: ' . $wpdb->last_error ) );
        }

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "INSERT INTO $assignment_table (`appointment_id`, `agent_id`)
            SELECT appt_agent.`appointment_id`, appt_agent.`agent_id`
            FROM $appt_agent_table as appt_agent
            ON DUPLICATE KEY UPDATE `assignment_id` = `assignment_id`;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Assignment Model V2.0: ' . $wpdb->last_error ) );
        }
    }

    /**
     * Move payment_status, total_amount, paid_amount, customer_note from AppointmentModel to BookingModel
     * Move AppointmentMetaModel->meta_value where meta_key = bookingDetails to BookingModel->booking_details
     */
    private static function move_appt_fields_to_booking_fields() {
        global $wpdb;
        $booking_table  = BookingModel::get_tablename();
        $appt_table     = AppointmentModel::get_tablename();
        $apptmeta_table = AppointmentMetaModel::get_tablename();

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "UPDATE $booking_table AS booking
            INNER JOIN $appt_table AS appt
            ON appt.`appointment_id` = booking.`appointment_id`
            LEFT JOIN $apptmeta_table AS booking_details_meta
            ON booking_details_meta.`appointment_id` = appt.`appointment_id` AND booking_details_meta.`meta_key` = 'bookingDetails'
            SET booking.`payment_status` = appt.`payment_status`,
            booking.`total_amount` = appt.`total_amount`,
            booking.`paid_amount` = appt.`paid_amount`,
            booking.`customer_note` = appt.`customer_note`,
            booking.`booking_details`= COALESCE(booking_details_meta.`meta_value`, NULL)
            WHERE booking.`payment_status` IS NULL;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Booking Model V2.0: ' . $wpdb->last_error ) );
        }
    }

    /**
     * Move extraBookingItems, displayActivities, bcf_* from AppointmentMetaModel to BookingMetaModel
     * extraBookingItems: Support Extra Services addon.
     * bcf_*: Support Booking Custom Fields addon.
     */
    private static function move_appt_metadata_to_booking_metadata() {
        global $wpdb;
        $booking_table     = BookingModel::get_tablename();
        $bookingmeta_table = BookingMetaModel::get_tablename();
        $appt_table        = AppointmentModel::get_tablename();
        $apptmeta_table    = AppointmentMetaModel::get_tablename();

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "INSERT INTO $bookingmeta_table (`appointment_id`, `customer_id`, `meta_key`, `meta_value`)
            SELECT appt_meta.`appointment_id`, booking.`customer_id`, appt_meta.`meta_key`, appt_meta.`meta_value`
            FROM $apptmeta_table as appt_meta
            INNER JOIN $appt_table as appt ON appt.`appointment_id` = appt_meta.`appointment_id`
            INNER JOIN $booking_table as booking ON booking.`appointment_id` = appt.`appointment_id`
            WHERE appt_meta.`meta_key` = 'extraBookingItems'
            OR appt_meta.`meta_key` = 'displayActivities'
            OR appt_meta.`meta_key` LIKE 'bcf_%'
            ON DUPLICATE KEY UPDATE `booking_meta_id` = `booking_meta_id`;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Booking Model V2.0: ' . $wpdb->last_error ) );
        }
    }

    private static function add_customer_id_to_transaction() {
        global $wpdb;
        $transaction_table = TransactionModel::get_tablename();
        $booking_table     = BookingModel::get_tablename();

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "UPDATE $transaction_table AS transaction
            INNER JOIN $booking_table AS booking
            ON booking.`appointment_id` = transaction.`appointment_id`
            SET transaction.`customer_id` = booking.`customer_id`
            WHERE transaction.`customer_id` IS NULL;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Booking Model V2.0: ' . $wpdb->last_error ) );
        }
    }

    /**
     * Delete legacy appointment metadata.
     * Will apply in future migration.
     */
    public static function delete_legacy_appt_metadata() {
        global $wpdb;
        $apptmeta_table = AppointmentMetaModel::get_tablename();

        $result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            "DELETE appt_meta
            FROM $apptmeta_table as appt_meta
            WHERE appt_meta.`meta_key` = 'extraBookingItems'
            OR appt_meta.`meta_key` = 'displayActivities'
            OR appt_meta.`meta_key` LIKE 'bcf_%';"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Booking Model V2.0: ' . $wpdb->last_error ) );
        }
    }
}
