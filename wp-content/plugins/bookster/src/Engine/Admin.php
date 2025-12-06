<?php
namespace Bookster\Engine;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\SettingsService;

/**
 * Admin enqueue logic
 *
 * @method static Admin get_instance()
 */
class Admin {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;

    protected function __construct() {
        $this->settings_service = SettingsService::get_instance();

        add_filter( 'display_post_states', [ $this, 'add_display_post_states' ], 10, 2 );
    }

    public function add_display_post_states( $post_states, $post ) {
        if ( 'page' !== $post->post_type ) {
            return $post_states;
        }

        $customer_dashboard_page_id = $this->settings_service->get_permissions_settings()['customer_dashboard_page_id'];
        if ( $customer_dashboard_page_id === $post->ID ) {
            $post_states['bookster_customer_dashboard'] = __( 'Bookster Customer Dashboard Page', 'bookster' );
        }

        return $post_states;
    }
}
