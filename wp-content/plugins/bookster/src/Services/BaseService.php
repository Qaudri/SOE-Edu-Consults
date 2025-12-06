<?php
namespace Bookster\Services;

/**
 * Base Service
 */
abstract class BaseService {
    protected function prepare_where_args( array $args, array $excluded = [] ): array {
        unset( $args['rest_route'] );
        unset( $args['include'] );

        foreach ( $excluded as $excluded_key ) {
            unset( $args[ $excluded_key ] );
        }
        return $args;
    }

    protected function prepare_count_args( array $args, array $excluded = [] ): array {
        unset( $args['rest_route'] );
        unset( $args['include'] );
        unset( $args['limit'] );
        unset( $args['offset'] );
        unset( $args['keywords_separator'] );
        unset( $args['order_by'] );
        unset( $args['order'] );

        foreach ( $excluded as $excluded_key ) {
            unset( $args[ $excluded_key ] );
        }
        return $args;
    }

    public function exec_wpdb_query( string $prepared_query, string $action = 'Editing Data' ) {
        global $wpdb;
        $result = $wpdb->query( $prepared_query );

        if ( false === $result ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error ' . $action . ': ' . $wpdb->last_error ) );
        }
        return $result;
    }

    public function validate_wpdb_query( string $action = 'Loading Data' ) {
        global $wpdb;
        if ( $wpdb->last_error ) {
            throw new \Exception( esc_html( 'Error ' . $action . ': ' . $wpdb->last_error ) );
        }
    }
}
