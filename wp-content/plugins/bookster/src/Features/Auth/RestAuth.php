<?php
namespace Bookster\Features\Auth;

/**
 * Bookster Authorize Rest API
 */
class RestAuth {

    public static function require_login() {
        if ( is_user_logged_in() ) {
            return true;
        }

        return new \WP_Error(
            'bookster_unauthorized',
            __( 'Sorry, you need to Login to do this!!!.', 'bookster' ),
            [ 'status' => 401 ]
        );
    }

    public static function require_manage_shop_settings_cap() {
        if ( current_user_can( Caps::MANAGE_SHOP_SETTINGS_CAP ) ) {
            return true;
        }

        return new \WP_Error(
            'bookster_forbidden',
            __( 'Sorry, you need to be Manager to do this!!!.', 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public static function require_manage_shop_records_cap() {
        if ( current_user_can( Caps::MANAGE_SHOP_RECORDS_CAP ) ) {
            return true;
        }

        return new \WP_Error(
            'bookster_forbidden',
            __( 'Sorry, you need to be Manager to do this!!!.', 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public static function require_manage_agent_settings_cap() {
        if ( current_user_can( Caps::MANAGE_AGENT_SETTINGS_CAP ) ) {
            return true;
        }

        return new \WP_Error(
            'bookster_forbidden',
            __( 'Sorry, you need to be Agent to do this!!!.', 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public static function require_manage_agent_records_cap() {
        if ( current_user_can( Caps::MANAGE_AGENT_RECORDS_CAP ) ) {
            return true;
        }

        return new \WP_Error(
            'bookster_forbidden',
            __( 'Sorry, you need to be Agent to do this!!!.', 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public static function require_manage_agent_profile_cap() {
        if ( current_user_can( Caps::MANAGE_AGENT_PROFILE_CAP ) ) {
            return true;
        }

        return new \WP_Error(
            'bookster_forbidden',
            __( 'Sorry, you need to be Agent to do this!!!.', 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }

    public static function require_any_caps( array $caps ) {
        foreach ( $caps as $cap ) {
            if ( current_user_can( $cap ) ) {
                return true;
            }
        }

        return self::get_default_error();
    }

    public static function require_every_caps( array $caps ) {
        foreach ( $caps as $cap ) {
            if ( ! current_user_can( $cap ) ) {
                return self::get_default_error();
            }
        }

        return true;
    }

    public static function get_default_error() {
        return new \WP_Error(
            'bookster_forbidden',
            __( "Sorry, you don't have Permission to do this!!!.", 'bookster' ),
            [ 'status' => rest_authorization_required_code() ]
        );
    }
}
