<?php
/**
 * Bookster
 *
 * @package           Bookster
 * @author            WPBookster
 * @copyright         Copyright 2023-2024, Bookster
 * @license           http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License, version 3 or later
 *
 * @wordpress-plugin
 * Plugin Name:       Bookster
 * Plugin URI:        https://wpbookster.com/
 * Description:       An awesome Booking system for WordPress.
 * Version:           2.1.1
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            WPBookster
 * Author URI:        https://wpbookster.com/about
 * Text Domain:       bookster
 * License:           GPL v3 or later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
    die( 'We\'re sorry, but you can not directly access this file.' );
}

define( 'BOOKSTER_VERSION', '2.1.1' );

define( 'BOOKSTER_PLUGIN_FILE', __FILE__ );
define( 'BOOKSTER_PLUGIN_PATH', plugin_dir_path( BOOKSTER_PLUGIN_FILE ) );
define( 'BOOKSTER_PLUGIN_URL', plugin_dir_url( BOOKSTER_PLUGIN_FILE ) );
define( 'BOOKSTER_PLUGIN_BASENAME', plugin_basename( BOOKSTER_PLUGIN_FILE ) );

add_action(
    'init',
    function() {
        load_plugin_textdomain( 'bookster', false, dirname( BOOKSTER_PLUGIN_BASENAME ) . '/languages' );
    }
);

function bookster_activate( $network_wide ) {
    if ( class_exists( '\Bookster\Engine\ActDeact' ) ) {
        \Bookster\Engine\ActDeact::activate( $network_wide );
    }
}
function bookster_deactivate( $network_wide ) {
    if ( class_exists( '\Bookster\Engine\ActDeact' ) ) {
        \Bookster\Engine\ActDeact::deactivate( $network_wide );
    }
}
function bookster_uninstall() {
    if ( class_exists( '\Bookster\Engine\ActDeact' ) ) {
        \Bookster\Engine\ActDeact::uninstall();
    }
}
register_activation_hook( BOOKSTER_PLUGIN_FILE, 'bookster_activate' );
register_deactivation_hook( BOOKSTER_PLUGIN_FILE, 'bookster_deactivate' );
register_uninstall_hook( BOOKSTER_PLUGIN_FILE, 'bookster_uninstall' );

/** Require Dependencies */
$bookster_has_required_deps = true;
define( 'BOOKSTER_MIN_PHP_VERSION', '7.4' );
define( 'BOOKSTER_MIN_WP_VERSION', '6.2' );
if ( version_compare( PHP_VERSION, BOOKSTER_MIN_PHP_VERSION, '<' ) ) {
    $bookster_has_required_deps = false;

    add_action(
        'admin_notices',
        function() {
            /* translators: %s: Bookster Minimum php version */
            $notice = sprintf( __( '"Bookster Plugin" requires PHP %s or newer.', 'bookster' ), BOOKSTER_MIN_PHP_VERSION );
            echo wp_kses_post(
                sprintf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    $notice
                )
            );
        }
    );
}

if ( version_compare( $GLOBALS['wp_version'], BOOKSTER_MIN_WP_VERSION, '<' ) ) {
    $bookster_has_required_deps = false;

    add_action(
        'admin_notices',
        function() {
            /* translators: %s: Bookster Minimum WordPress version */
            $notice = sprintf( __( '"Bookster Plugin" requires WordPress %s or newer.', 'bookster' ), BOOKSTER_MIN_WP_VERSION );
            echo wp_kses_post(
                sprintf(
                    '<div class="notice notice-error"><p>%s</p></div>',
                    $notice
                )
            );
        }
    );
}

if ( ! $bookster_has_required_deps ) {
    add_action(
        'admin_init',
        function() {
            deactivate_plugins( BOOKSTER_PLUGIN_BASENAME );
        }
    );

} else {
    require_once BOOKSTER_PLUGIN_PATH . 'vendor/autoload.php';
    if ( ! wp_installing() ) {
        add_action(
            'plugins_loaded',
            function () {
                \Bookster\Initialize::get_instance();
            }
        );
    }
}
