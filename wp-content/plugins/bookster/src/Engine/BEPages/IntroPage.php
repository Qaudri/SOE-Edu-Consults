<?php
namespace Bookster\Engine\BEPages;

use Bookster\Features\Auth\Caps;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Auth\RestAuth;
use Bookster\Services\SettingsService;
use Bookster\Features\Scripts\EnqueueLogic;

/**
 * Intro Page - Wizard to begin using the plugin
 */
class IntroPage {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;
    /** @var EnqueueLogic */
    private $enqueue_logic;

    public const MENU_SLUG = 'bookster-intro';
    private $page_suffix   = null;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();
        $this->enqueue_logic    = EnqueueLogic::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_bookster_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
        add_action( 'current_screen', [ $this, 'redirect_to_intro' ] );
        add_filter( 'default_content', [ $this, 'preset_page_content' ] );
        add_filter( 'default_title', [ $this, 'preset_page_title' ] );
    }

    public function add_bookster_menu() {
        if ( $this->require_caps_for_intro() ) {
            $this->page_suffix = add_submenu_page(
                'options-general.php',
                __( 'Bookster Setup Walkthrough', 'bookster' ),
                __( 'Bookster Tours', 'bookster' ),
                'manage_options',
                self::MENU_SLUG,
                [ $this, 'render_intro_page' ]
            );
        }
    }

    public function render_intro_page() {
        include_once BOOKSTER_PLUGIN_PATH . 'templates/pages/intro.php';
    }

    public function admin_enqueue_scripts( $hook_suffix ) {
        if ( in_array( $hook_suffix, [ $this->page_suffix ], true ) ) {
            $this->enqueue_logic->enqueue_page_intro();
        }
    }

    public function redirect_to_intro( \WP_Screen $current_screen ) {
        if ( ManagerPage::is_manager_page( $current_screen ) && $this->settings_service->need_to_run_intro() ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . self::MENU_SLUG ) );
            exit;
        }
    }

    public function preset_page_content( $content ) {
        $preset_page = $this->get_preset_page();

        if ( 'booking-form' === $preset_page ) {
            return $this->is_block_editor_page()
            ? '<!-- wp:shortcode -->
                [bookster_booking_form]
            <!-- /wp:shortcode -->'
            : '[bookster_booking_form]';
        }
        if ( 'customer-dashboard' === $preset_page ) {
            return $this->is_block_editor_page()
            ? '<!-- wp:shortcode -->
                [bookster_customer_dashboard]
            <!-- /wp:shortcode -->'
            : '[bookster_customer_dashboard]';
        }
        return $content;
    }

    public function preset_page_title( $title ) {
        $preset_page = $this->get_preset_page();

        if ( 'booking-form' === $preset_page ) {
            return __( 'Booking', 'bookster' );
        }
        if ( 'customer-dashboard' === $preset_page ) {
            return __( 'Customer Dashboard', 'bookster' );
        }
        return $title;
    }

    private function get_preset_page(): string {
        if (
            isset( $_GET['bookster_preset_page'], $_GET['nonce'] )
            && wp_verify_nonce( sanitize_key( $_GET['nonce'] ), 'wp_rest' )
        ) {
            return sanitize_key( wp_unslash( $_GET['bookster_preset_page'] ) );
        } else {
            return '';
        }
    }

    private function is_block_editor_page() {
        $screen = get_current_screen();
        return $screen && $screen->is_block_editor();
    }

    public static function require_caps_for_intro() {
        return RestAuth::require_every_caps( [ Caps::MANAGE_SHOP_SETTINGS_CAP, Caps::MANAGE_SHOP_RECORDS_CAP ] );
    }
}
