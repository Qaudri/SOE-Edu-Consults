<?php
namespace Bookster\Engine;

use Bookster\Features\Utils\SingletonTrait;

/**
 * Bookster AJAX
 */
class Ajax {
    use SingletonTrait;

    /** Hooks Initialization */
    protected function __construct() {
        add_action( 'wp_ajax_bookster_reload_nonce', [ $this, 'ajax_bookster_reload_nonce' ] );
        add_action( 'wp_ajax_nopriv_bookster_reload_nonce', [ $this, 'ajax_bookster_reload_nonce' ] );
    }

    public function ajax_bookster_reload_nonce() {
        wp_send_json( [ 'rest_nonce' => wp_create_nonce( 'wp_rest' ) ] );
    }
}
