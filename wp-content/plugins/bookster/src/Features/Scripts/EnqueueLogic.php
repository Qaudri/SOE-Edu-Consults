<?php
namespace Bookster\Features\Scripts;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\LocalizeService;
use Bookster\Features\Scripts\ScriptName;

/**
 * Enqueue Logic
 */
class EnqueueLogic {
    use SingletonTrait;

    /** @var LocalizeService */
    private $localize_service;

    private $is_prod = true;

    protected function __construct() {
        $this->localize_service = LocalizeService::get_instance();
        $this->is_prod          = ! defined( 'BOOKSTER_IS_DEVELOPMENT' ) || BOOKSTER_IS_DEVELOPMENT !== true;
    }

    public function enqueue_page_manager() {
        wp_enqueue_script( ScriptName::PAGE_MANAGER );
        wp_enqueue_media();
        wp_enqueue_editor();

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_ADMIN_HIDDEN );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::PAGE_MANAGER );
        $this->localize_service->localize_manager_data( ScriptName::PAGE_MANAGER );
        $this->localize_service->localize_meta( ScriptName::PAGE_MANAGER );
        $this->localize_service->localize_addons( ScriptName::PAGE_MANAGER );

        remove_all_actions( 'admin_notices' );

        do_action( 'bookster_after_enqueue_script', ScriptName::PAGE_MANAGER, $this->is_prod );
    }

    public function enqueue_page_agent() {
        wp_enqueue_script( ScriptName::PAGE_AGENT );
        wp_enqueue_media();

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_ADMIN_HIDDEN );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::PAGE_AGENT );
        $this->localize_service->localize_meta( ScriptName::PAGE_AGENT );
        $this->localize_service->localize_addons( ScriptName::PAGE_AGENT );

        remove_all_actions( 'admin_notices' );

        do_action( 'bookster_after_enqueue_script', ScriptName::PAGE_AGENT, $this->is_prod );
    }

    public function enqueue_page_intro() {
        wp_enqueue_script( ScriptName::PAGE_INTRO );
        wp_enqueue_media();

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_ADMIN_HIDDEN );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::PAGE_INTRO );
        $this->localize_service->localize_manager_data( ScriptName::PAGE_INTRO );
        $this->localize_service->localize_meta( ScriptName::PAGE_INTRO );
        $this->localize_service->localize_addons( ScriptName::PAGE_INTRO );

        remove_all_actions( 'admin_notices' );

        do_action( 'bookster_after_enqueue_script', ScriptName::PAGE_INTRO, $this->is_prod );
    }

    public function enqueue_block_booking_button() {
        wp_enqueue_script( ScriptName::BLOCK_BOOKING_BUTTON );

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_RESET_THEME );
        wp_enqueue_style( ScriptName::STYLE_ANIMXYZ );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::BLOCK_BOOKING_BUTTON );
        $this->localize_service->localize_meta( ScriptName::BLOCK_BOOKING_BUTTON );
        $this->localize_service->localize_addons( ScriptName::BLOCK_BOOKING_BUTTON );

        do_action( 'bookster_after_enqueue_script', ScriptName::BLOCK_BOOKING_BUTTON, $this->is_prod );
    }

    public function enqueue_block_booking_form() {
        wp_enqueue_script( ScriptName::BLOCK_BOOKING_FORM );

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_RESET_THEME );
        wp_enqueue_style( ScriptName::STYLE_ANIMXYZ );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::BLOCK_BOOKING_FORM );
        $this->localize_service->localize_meta( ScriptName::BLOCK_BOOKING_FORM );
        $this->localize_service->localize_addons( ScriptName::BLOCK_BOOKING_FORM );

        do_action( 'bookster_after_enqueue_script', ScriptName::BLOCK_BOOKING_FORM, $this->is_prod );
    }

    public function enqueue_block_customer_dashboard() {
        wp_enqueue_script( ScriptName::BLOCK_CUSTOMER_DASHBOARD );

        wp_enqueue_style( ScriptName::STYLE_BOOKSTER_FONT );
        wp_enqueue_style( ScriptName::STYLE_RESET_THEME );
        wp_enqueue_style( ScriptName::STYLE_ANIMXYZ );
        if ( $this->is_prod ) {
            wp_enqueue_style( ScriptName::STYLE_BOOKSTER );
        }

        $this->localize_service->localize_public_data( ScriptName::BLOCK_CUSTOMER_DASHBOARD );
        $this->localize_service->localize_meta( ScriptName::BLOCK_CUSTOMER_DASHBOARD );
        $this->localize_service->localize_addons( ScriptName::BLOCK_CUSTOMER_DASHBOARD );

        do_action( 'bookster_after_enqueue_script', ScriptName::BLOCK_CUSTOMER_DASHBOARD, $this->is_prod );
    }

    public function is_prod() {
        return $this->is_prod;
    }
}
