<?php
namespace Bookster\Engine;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Services\AgentsService;
use Bookster\Services\CustomersService;
use Bookster\Features\Auth\Caps;

/**
 * Bookster Auth Hooks
 */
class Auth {
    use SingletonTrait;

    /** @var AgentsService */
    private $agents_service;
    /** @var CustomersService */
    private $customers_service;

    /** Hooks Initialization */
    protected function __construct() {
        $this->agents_service    = AgentsService::get_instance();
        $this->customers_service = CustomersService::get_instance();

        add_action( 'profile_update', [ $this, 'update_record_email' ], 10, 2 );

        if ( class_exists( 'woocommerce' ) ) {
            add_filter( 'woocommerce_disable_admin_bar', [ $this, 'wc_disable_admin_bar' ], 10, 1 );
            add_filter( 'woocommerce_prevent_admin_access', [ $this, 'wc_prevent_admin_access' ], 10, 1 );
        }
    }

    public function update_record_email( $user_id, $old_user_data ) {
        $old_user_email = $old_user_data->data->user_email;

        $user = get_userdata( $user_id );
        // Support WP < 5.8
        $new_user_email = $user->user_email;

        if ( $new_user_email === $old_user_email ) {
            return;
        }

        if ( function_exists( 'is_multisite' ) && is_multisite() ) {
            // Get all blog ids
            /** @var array<\WP_Site> $blogs */
            $blogs = get_sites();

            foreach ( $blogs as $blog ) {
                switch_to_blog( (int) $blog->blog_id );
                $this->single_update_user_email( $user_id, $new_user_email );
                restore_current_blog();
            }
        } else {
            $this->single_update_user_email( $user_id, $new_user_email );
        }
    }

    private function single_update_user_email( $user_id, $new_user_email ) {
        $agent_record = $this->agents_service->find_one_with_info( [ 'wp_user_id' => $user_id ] );
        if ( ! empty( $agent_record ) ) {
            $duplicated_agent_record = $this->agents_service->find_one_with_info( [ 'email' => $new_user_email ] );
            if ( empty( $duplicated_agent_record ) ) {
                // If record for new email does not exist, update the record with new email
                $this->agents_service->update( $agent_record->agent_id, [ 'email' => $new_user_email ] );
            } else {
                // If record for new email already exists
                // In this rare case, remove the wp_user_id from the record, to prevent unpredictable behavior
                $this->agents_service->update( $agent_record->agent_id, [ 'wp_user_id' => null ] );
            }
        }

        $customer_record = $this->customers_service->find_one_with_info( [ 'wp_user_id' => $user_id ] );
        if ( ! empty( $customer_record ) ) {
            $duplicated_customer_record = $this->customers_service->find_one_with_info( [ 'email' => $new_user_email ] );
            if ( empty( $duplicated_customer_record ) ) {
                // If record for new email does not exist, update the record with new email
                $this->customers_service->update( $customer_record->customer_id, [ 'email' => $new_user_email ] );
            } else {
                // If record for new email already exists
                // In this rare case, remove the wp_user_id from the record, to prevent unpredictable behavior
                $this->customers_service->update( $customer_record->customer_id, [ 'wp_user_id' => null ] );
            }
        }
    }

    public function wc_prevent_admin_access( $prevent_admin_access ) {
        if ( ! current_user_can( Caps::MANAGE_AGENT_PROFILE_CAP ) ) {
            return $prevent_admin_access;
        }
        return false;
    }

    public function wc_disable_admin_bar( $prevent_admin_access ) {
        if ( ! current_user_can( Caps::MANAGE_AGENT_PROFILE_CAP ) ) {
            return $prevent_admin_access;
        }
        return false;
    }
}
