<?php
namespace Bookster\Engine\BEPages;

use Bookster\Features\Auth\Caps;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Backend Manager Page
 */
class ManagerPage {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    public const MENU_SLUG = 'bookster-manager';
    private $page_suffix   = null;

    /** Hooks Initialization */
    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        if ( current_user_can( Caps::MANAGE_SHOP_RECORDS_CAP ) ) {
            add_filter( 'plugin_action_links_' . plugin_basename( BOOKSTER_PLUGIN_FILE ), [ $this, 'add_action_links' ] );
            add_action( 'admin_bar_menu', [ $this, 'add_bookster_link_to_admin_bar' ], 999 );
        }

        add_action( 'admin_menu', [ $this, 'add_bookster_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
    }

    /**
     * Add Link to Bookster to admin bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     * @return void
     */
    public function add_bookster_link_to_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
        $icon  = '<span class="ab-icon dashicons-before dashicons-calendar" aria-hidden="true"></span>';
        $title = '<span class="ab-label" aria-hidden="true">' . __( 'Bookster', 'bookster' ) . '</span>';

        $wp_admin_bar->add_node(
            [
                'id'    => 'bookster_manager_top_link',
                'title' => '<span style="display: flex; align-items: center;">' . $icon . $title . '</span>',
                'href'  => admin_url( 'admin.php?page=' . self::MENU_SLUG ),
            ]
        );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_manager_top_link',
                'id'     => 'bookster_manager_dashboard_link',
                'title'  => __( 'Dashboard', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/dashboard' ),
            ]
        );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_manager_top_link',
                'id'     => 'bookster_manager_calendar_link',
                'title'  => __( 'Calendar', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/calendar' ),
            ]
        );
        $today     = wp_date( 'Y-m-d' );
        $next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_manager_top_link',
                'id'     => 'bookster_manager_appointments_link',
                'title'  => __( 'Appointments', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/appointments?date-from=' . $today . '&date-to=' . $next_week ),
            ]
        );
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @return void
     */
    public function add_bookster_menu() {
        $default_cap = current_user_can( Caps::MANAGE_SHOP_RECORDS_CAP ) ? Caps::MANAGE_SHOP_RECORDS_CAP : Caps::MANAGE_SHOP_SETTINGS_CAP;

        $this->page_suffix = add_menu_page(
            __( 'Bookster', 'bookster' ),
            __( 'Bookster', 'bookster' ),
            $default_cap,
            self::MENU_SLUG,
            [ $this, 'render_manager_page' ],
            'dashicons-calendar',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Dashboard', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            Caps::MANAGE_SHOP_RECORDS_CAP === $default_cap ? self::MENU_SLUG : self::MENU_SLUG . '#/dashboard',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Calendar', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            self::MENU_SLUG . '#/calendar',
            [ $this, 'render_manager_page' ],
        );

        $today     = wp_date( 'Y-m-d' );
        $next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Appointments', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            self::MENU_SLUG . '#/appointments?date-from=' . $today . '&date-to=' . $next_week,
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Agents', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            self::MENU_SLUG . '#/agents',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Services', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            self::MENU_SLUG . '#/services/list',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Customers', 'bookster' ),
            Caps::MANAGE_SHOP_RECORDS_CAP,
            self::MENU_SLUG . '#/customers',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Settings', 'bookster' ),
            Caps::MANAGE_SHOP_SETTINGS_CAP,
            Caps::MANAGE_SHOP_SETTINGS_CAP === $default_cap ? self::MENU_SLUG : self::MENU_SLUG . '#/settings/general',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Customize', 'bookster' ),
            Caps::MANAGE_SHOP_SETTINGS_CAP,
            self::MENU_SLUG . '#/customize',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Integrations', 'bookster' ),
            Caps::MANAGE_SHOP_SETTINGS_CAP,
            self::MENU_SLUG . '#/integrations/overview',
            [ $this, 'render_manager_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'Bookster', 'bookster' ),
            __( 'Add-ons', 'bookster' ),
            Caps::MANAGE_SHOP_SETTINGS_CAP,
            self::MENU_SLUG . '#/addons',
            [ $this, 'render_manager_page' ],
        );
    }

    /**
     * Render the manager page.
     *
     * @return void
     */
    public function render_manager_page() {
        include_once BOOKSTER_PLUGIN_PATH . 'templates/pages/manager.php';
    }

    /**
     * Add manage action link to the plugins page.
     *
     * @param array $links Array of links.
     * @return array
     */
    public function add_action_links( array $links ) {
        return array_merge(
            [
                'manage' => '<a href="' . admin_url( 'admin.php?page=' . self::MENU_SLUG ) . '">' . __( 'Manage', 'bookster' ) . '</a>',
            ],
            $links
        );
    }

    public function admin_enqueue_scripts( $hook_suffix ) {
        if ( in_array( $hook_suffix, [ $this->page_suffix ], true ) ) {
            $this->enqueue_logic->enqueue_page_manager();
        }
    }

    public static function is_manager_page( \WP_Screen $current_screen = null ) {
        if ( ! $current_screen ) {
            $current_screen = get_current_screen();
        }
        if ( ! $current_screen ) {
            return false;
        }
        return 'toplevel_page_' . self::MENU_SLUG === $current_screen->base;
    }
}
