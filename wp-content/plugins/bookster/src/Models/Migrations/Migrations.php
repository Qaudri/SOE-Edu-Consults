<?php
namespace Bookster\Models\Migrations;

use Bookster\Features\Logger;
use Bookster\Services\ServicesCategoriesService;
use Bookster\Models\AppointmentModel;
use Bookster\Models\TransactionModel;

/**
 * Core Migrations
 */
class Migrations {

    public static function do_migrations( $previous_version ) {
        if ( version_compare( $previous_version, '1.0', '<' ) ) {
            self::exec_migration( [ self::class, 'migrate_v1_0' ] );
        }
        if ( '0.0.0' !== $previous_version && version_compare( $previous_version, '1.1', '<' ) ) {
            self::exec_migration( [ self::class, 'migrate_v1_1' ] );
        }
        if ( '0.0.0' !== $previous_version && version_compare( $previous_version, '2.0', '<' ) ) {
            self::exec_migration( [ self::class, 'migrate_v2_0' ] );
        }
    }

    public static function migrate_v1_0() {
        $service_cate_service = ServicesCategoriesService::get_instance();
        if ( 0 === $service_cate_service->count_where( [] ) ) {
            $service_cate_service->insert(
                [
                    'name'        => 'Uncategorized',
                    'description' => 'Default category for services',
                ]
            );
        }
    }

    // phpcs:disable
    public static function migrate_v1_1() {
        global $wpdb;
        $appt_table        = AppointmentModel::get_tablename();
        $transaction_table = TransactionModel::get_tablename();

        $result = $wpdb->query(
            $wpdb->prepare(
                "UPDATE $appt_table SET payment_status = %s WHERE payment_status = %s;",
                'complete',
                'paid'
            )
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Version 1.1: ' . $wpdb->last_error ) );
        }

        $result = $wpdb->query(
            "ALTER TABLE $transaction_table MODIFY appointment_id bigint(20) unsigned;"
        );

        if ( false === $result ) {
            throw new \Exception( esc_html( 'Error Migrate Version 1.1: ' . $wpdb->last_error ) );
        }
    }
    // phpcs:enable

    public static function migrate_v2_0() {
        BookingModelMigration::do_migration_v2_0();
    }

    public static function exec_migration( $callback ) {
        try {
            $callback();
        } catch ( \Throwable $ex ) {
            Logger::log_throwable( $ex, true );
            throw $ex;
        }
    }
}
