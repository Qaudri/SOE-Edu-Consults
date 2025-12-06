<?php
namespace Bookster\Models;

/**
 * Core Schema
 */
class Schema {

    public static function create_tables() {

        $sql  = '';
        $sql .= ServiceCategoryModel::get_schema();
        $sql .= ServiceModel::get_schema();
        $sql .= ServiceMetaModel::get_schema();

        $sql .= AgentModel::get_schema();
        $sql .= AgentMetaModel::get_schema();

        $sql .= CustomerModel::get_schema();
        $sql .= CustomerMetaModel::get_schema();

        $sql .= AppointmentModel::get_schema();
        $sql .= AppointmentMetaModel::get_schema();

        $sql .= BookingModel::get_schema();
        $sql .= BookingMetaModel::get_schema();
        $sql .= TransactionModel::get_schema();

        $sql .= AssignmentModel::get_schema();
        $sql .= AssignmentMetaModel::get_schema();

        $sql .= AvailableAgentServiceModel::get_schema();
        $sql .= LogModel::get_schema();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    public static function delete_tables() {
        global $wpdb;

        // phpcs:disable
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . ServiceCategoryModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . ServiceModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . ServiceMetaModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AgentModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AgentMetaModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . CustomerModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . CustomerMetaModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AppointmentModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AppointmentMetaModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . BookingModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . BookingMetaModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . TransactionModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AssignmentModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AssignmentMetaModel::get_tablename() . ';' ) );

        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . AvailableAgentServiceModel::get_tablename() . ';' ) );
        $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS ' . LogModel::get_tablename() . ';' ) );
        // phpcs:enable
    }
}
