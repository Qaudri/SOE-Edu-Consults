<?php
namespace Bookster\Engine\BEPages;

use Bookster\Features\Auth\Caps;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Backend Agent Page
 */
class AgentPage {
    use SingletonTrait;

    /** @var EnqueueLogic */
    private $enqueue_logic;

    public const MENU_SLUG = 'bookster-agent';
    private $page_suffix   = null;

    /** Hooks Initialization */
    protected function __construct() {
        $this->enqueue_logic = EnqueueLogic::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        if ( current_user_can( Caps::MANAGE_AGENT_RECORDS_CAP ) ) {
            add_action( 'admin_bar_menu', [ $this, 'add_my_bookster_link_to_admin_bar' ], 999 );
        }

        add_action( 'admin_menu', [ $this, 'add_my_bookster_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
    }

    /**
     * Add Link to Bookster to admin bar
     *
     * @param \WP_Admin_Bar $wp_admin_bar
     * @return void
     */
    public function add_my_bookster_link_to_admin_bar( \WP_Admin_Bar $wp_admin_bar ) {
        $icon  = '<span class="ab-icon dashicons-before dashicons-calendar" aria-hidden="true"></span>';
        $title = '<span class="ab-label" aria-hidden="true">' . __( 'My Bookster', 'bookster' ) . '</span>';

        $wp_admin_bar->add_node(
            [
                'id'    => 'bookster_agent_top_link',
                'title' => '<span style="display: flex; align-items: center;">' . $icon . $title . '</span>',
                'href'  => admin_url( 'admin.php?page=' . self::MENU_SLUG ),
            ]
        );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_agent_top_link',
                'id'     => 'bookster_agent_dashboard_link',
                'title'  => __( 'Dashboard', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/dashboard' ),
            ]
        );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_agent_top_link',
                'id'     => 'bookster_agent_calendar_link',
                'title'  => __( 'Calendar', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/calendar' ),
            ]
        );
        $today     = wp_date( 'Y-m-d' );
        $next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
        $wp_admin_bar->add_node(
            [
                'parent' => 'bookster_agent_top_link',
                'id'     => 'bookster_agent_appointments_link',
                'title'  => __( 'My Appointments', 'bookster' ),
                'href'   => admin_url( 'admin.php?page=' . self::MENU_SLUG . '#/appointments?date-from=' . $today . '&date-to=' . $next_week ),
            ]
        );
    }

    /**
     * Register the administration menu for this plugin into the WordPress Dashboard menu.
     *
     * @return void
     */
    public function add_my_bookster_menu() {
        $default_cap = current_user_can( Caps::MANAGE_AGENT_RECORDS_CAP )
            ? Caps::MANAGE_AGENT_RECORDS_CAP
            : ( current_user_can( Caps::MANAGE_AGENT_PROFILE_CAP )
                ? Caps::MANAGE_AGENT_PROFILE_CAP
                : Caps::MANAGE_AGENT_SETTINGS_CAP );

        $this->page_suffix = add_menu_page(
            __( 'My Bookster', 'bookster' ),
            __( 'My Bookster', 'bookster' ),
            $default_cap,
            self::MENU_SLUG,
            [ $this, 'render_agent_page' ],
            'dashicons-calendar',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'My Bookster', 'bookster' ),
            __( 'Dashboard', 'bookster' ),
            Caps::MANAGE_AGENT_RECORDS_CAP,
            Caps::MANAGE_AGENT_RECORDS_CAP === $default_cap ? self::MENU_SLUG : self::MENU_SLUG . '#/dashboard',
            [ $this, 'render_agent_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'My Bookster', 'bookster' ),
            __( 'Calendar', 'bookster' ),
            Caps::MANAGE_AGENT_RECORDS_CAP,
            self::MENU_SLUG . '#/calendar',
            [ $this, 'render_agent_page' ],
        );

        $today     = wp_date( 'Y-m-d' );
        $next_week = wp_date( 'Y-m-d', strtotime( '+7 days' ) );
        add_submenu_page(
            self::MENU_SLUG,
            __( 'My Bookster', 'bookster' ),
            __( 'My Appointments', 'bookster' ),
            Caps::MANAGE_AGENT_RECORDS_CAP,
            self::MENU_SLUG . '#/appointments?date-from=' . $today . '&date-to=' . $next_week,
            [ $this, 'render_agent_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'My Bookster', 'bookster' ),
            __( 'Profile', 'bookster' ),
            Caps::MANAGE_AGENT_PROFILE_CAP,
            Caps::MANAGE_AGENT_PROFILE_CAP === $default_cap ? self::MENU_SLUG : self::MENU_SLUG . '#/profile',
            [ $this, 'render_agent_page' ],
        );

        add_submenu_page(
            self::MENU_SLUG,
            __( 'My Bookster', 'bookster' ),
            __( 'Settings', 'bookster' ),
            Caps::MANAGE_AGENT_RECORDS_CAP,
            self::MENU_SLUG . '#/settings/schedule',
            [ $this, 'render_agent_page' ],
        );
    }

    /**
     * Render the agent page.
     *
     * @return void
     */
    public function render_agent_page() {
        include_once BOOKSTER_PLUGIN_PATH . 'templates/pages/agent.php';
    }

    public function admin_enqueue_scripts( $hook_suffix ) {
        if ( in_array( $hook_suffix, [ $this->page_suffix ], true ) ) {
            $this->enqueue_logic->enqueue_page_agent();
        }
    }
}
