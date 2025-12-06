<?php
namespace Bookster\Features\Auth;

/**
 * Bookster Authorization
 */
class AuthFns {

    public static function install_roles() {
        self::update_role_caps( 'administrator', Caps::get_manager_additional_caps() );

        $role = add_role( Roles::MANAGER_ROLE, __( 'Bookster Manager', 'bookster' ), Caps::get_manager_caps() );
        if ( null === $role ) {
            self::update_role_caps( Roles::MANAGER_ROLE, Caps::get_manager_caps() );
        }

        $role = add_role( Roles::AGENT_ROLE, __( 'Bookster Agent', 'bookster' ), Caps::get_agent_caps() );
        if ( null === $role ) {
            self::update_role_caps( Roles::AGENT_ROLE, Caps::get_agent_caps() );
        }
    }

    public static function update_role_caps( string $role, array $caps ) {
        $wp_role = get_role( $role );

        foreach ( $caps as $key => $value ) {
            if ( $wp_role->has_cap( $key ) && false === $value ) {
                $wp_role->remove_cap( $key );
            } elseif ( ! $wp_role->has_cap( $key ) && true === $value ) {
                $wp_role->add_cap( $key, $value );
            }
        }
    }

    public static function remove_caps() {
        $wp_role = get_role( 'administrator' );

        foreach ( Caps::get_manager_additional_caps() as $key => $value ) {
            if ( $wp_role->has_cap( $key ) ) {
                $wp_role->remove_cap( $key );
            }
        }
    }

    public static function uninstall_roles() {
        remove_role( Roles::MANAGER_ROLE );
        remove_role( Roles::AGENT_ROLE );
    }
}
