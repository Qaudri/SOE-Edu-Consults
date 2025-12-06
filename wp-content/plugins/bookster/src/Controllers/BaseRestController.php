<?php
namespace Bookster\Controllers;

use Bookster\Features\Errors\Interfaces\ResponsableException;
use Bookster\Features\Errors\InvalidArgumentException;
use Bookster\Features\Logger;

/**
 * Base Rest API Controller
 */
abstract class BaseRestController {

    public const REST_NAMESPACE = 'bookster/v1';

    /**
     * Execute Routes which DO NOT Write to DB
     *
     * @param  callable         $callback
     * @param  \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function exec_read( $callback, \WP_REST_Request $request ) {
        try {
            if ( is_callable( $callback ) ) {
                $response = $callback( $request );
            }
        } catch ( \Throwable $ex ) {
            Logger::log_throwable( $ex );

            if ( $ex instanceof ResponsableException ) {
                $response = $ex->get_response_error();
            } else {
                $response = new \WP_Error( 'internal_error', $ex->getMessage(), [ 'status' => 500 ] );
            }
        }

        return rest_ensure_response( $response );
    }

    /**
     * Execute Routes which DO Write to DB
     *
     * @param  callable         $callback
     * @param  \WP_REST_Request $request
     * @return \WP_REST_Response|\WP_Error
     */
    public function exec_write( $callback, $request ) {
        global $wpdb;

        try {
            $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB
            if ( is_callable( $callback ) ) {
                $response = $callback( $request );
            }
            $wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB

        } catch ( \Throwable $ex ) {
            $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB
            Logger::log_throwable( $ex );

            if ( $ex instanceof ResponsableException ) {
                $response = $ex->get_response_error();
            } else {
                $response = new \WP_Error( 'internal_error', $ex->getMessage(), [ 'status' => 500 ] );
            }
        }

        return rest_ensure_response( $response );
    }

    public function validate_arguments( $args = [], $allow_args = [] ) {
        foreach ( $args as $arg ) {
            if ( ! in_array( $arg, $allow_args, true ) ) {
                throw new InvalidArgumentException( 'You do NOT have permission to edit this data!' );
            }
        }
        return true;
    }
}
