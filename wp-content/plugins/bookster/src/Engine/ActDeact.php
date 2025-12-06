<?php
namespace Bookster\Engine;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\PageSetup;
use Bookster\Models\Schema;
use Bookster\Models\Migrations\Migrations;
use Bookster\Features\Auth\AuthFns;
use Bookster\Services\SettingsService;

/**
 * Activate and deactive method of the plugin and relates.
 */
class ActDeact {

    use SingletonTrait;

    public const CORE_DB_VERSION = 'bookster_core_db_version';

    /** @var SettingsService */
    private $settings_service;

    /** Hooks Initialization */
    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();

        // Activate plugin when new blog is added
        add_action( 'wpmu_new_blog', [ $this, 'activate_new_site' ] );
        add_action( 'admin_init', [ $this, 'upgrade_procedure' ] );
        add_filter( 'bookster_agent_capabilities', [ $this, 'add_agent_setting_caps' ] );
    }

    /**
     * Fired when a new site is activated with a WPMU environment.
     *
     * @param int $blog_id ID of the new blog.
     */
    public function activate_new_site( int $blog_id ) {
        if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
            return;
        }

        switch_to_blog( $blog_id );
        self::single_activate();
        restore_current_blog();
    }

    /**
     * Fired when the plugin is activated.
     *
     * @param bool $network_wide True if active in a multiste, false if classic site.
     */
    public static function activate( $network_wide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( $network_wide ) {
                // Get all blog ids
                /** @var array<\WP_Site> $blogs */
                $blogs = get_sites();

                foreach ( $blogs as $blog ) {
                    switch_to_blog( (int) $blog->blog_id );
                    self::single_activate();
                    restore_current_blog();
                }

                return;
            }
        }

        self::single_activate();
    }

    /**
     * Fired when the plugin is deactivated.
     *
     * @param bool $network_wide True if WPMU superadmin uses
     * "Network Deactivate" action, false if
     * WPMU is disabled or plugin is
     * deactivated on an individual blog.
     */
    public static function deactivate( $network_wide ) {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            if ( $network_wide ) {
                // Get all blog ids
                /** @var array<\WP_Site> $blogs */
                $blogs = get_sites();

                foreach ( $blogs as $blog ) {
                    switch_to_blog( (int) $blog->blog_id );
                    self::single_deactivate();
                    restore_current_blog();
                }

                return;
            }
        }

        self::single_deactivate();
    }

    /** Fired when the plugin is uninstalled. */
    public static function uninstall() {
        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
                // Get all blog ids
                /** @var array<\WP_Site> $blogs */
                $blogs = get_sites();

            foreach ( $blogs as $blog ) {
                switch_to_blog( (int) $blog->blog_id );
                self::single_uninstall();
                restore_current_blog();
            }

            return;
        }

        self::single_uninstall();
    }

    /** Procedure run when version update */
    public static function upgrade_procedure() {
        if ( ! is_admin() && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
            return;
        }

        $version = get_option( self::CORE_DB_VERSION, '0.0.0' );
        if ( ! version_compare( BOOKSTER_VERSION, $version, '>' ) ) {
            return;
        }

        Schema::create_tables();
        Migrations::do_migrations( $version );
        AuthFns::install_roles();

        update_option( self::CORE_DB_VERSION, BOOKSTER_VERSION );
    }

    /** Fired for each blog when the plugin is activated. */
    private static function single_activate() {
        self::upgrade_procedure();
        PageSetup::get_instance()->maybe_create_pages();
        // Clear the permalinks
        flush_rewrite_rules();
    }

    /** Fired for each blog when the plugin is deactivated. */
    private static function single_deactivate() {
        delete_option( self::CORE_DB_VERSION );
        AuthFns::remove_caps();
        // Clear the permalinks
        flush_rewrite_rules();
    }

    /** The plugin is uninstall single site. */
    private static function single_uninstall() {
        AuthFns::uninstall_roles();
        wp_cache_flush();
    }

    public function add_agent_setting_caps( $caps ) {
        $permissions_settings       = $this->settings_service->get_manager_data()['permissionsSettings'];
        $agents_allow_edit_settings = isset( $permissions_settings['agents_allow_edit_settings'] ) ? $permissions_settings['agents_allow_edit_settings'] : false;

        $caps['bookster_manage_agent_settings'] = $agents_allow_edit_settings;

        return $caps;
    }
}
