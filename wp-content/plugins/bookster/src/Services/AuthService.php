<?php
namespace Bookster\Services;

use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Auth\Caps;
use Bookster\Models\CustomerModel;
use Bookster\Models\AgentModel;
use Bookster\Features\Errors\WpErrorException;

/**
 * Auth Service
 *
 * @method static AuthService get_instance()
 */
class AuthService extends BaseService {
    use SingletonTrait;

    /** @var AgentsService */
    private $agents_service;
    /** @var CustomersService */
    private $customers_service;

    protected function __construct() {
        $this->agents_service    = AgentsService::get_instance();
        $this->customers_service = CustomersService::get_instance();
    }

    public function login( $username, $password, $remember = true ) {
        $wp_user = wp_signon(
            [
                'user_login'    => $username,
                'user_password' => $password,
                'remember'      => $remember,
            ]
        );

        if ( is_wp_error( $wp_user ) ) {
            throw new WpErrorException( $wp_user );
        }

        wp_set_current_user( $wp_user->ID );
        wp_set_auth_cookie( $wp_user->ID, true );
        return $wp_user;
    }

    public function get_wp_user_info() {
        $is_user_logged_in = is_user_logged_in();
        if ( ! $is_user_logged_in ) {
            return [
                'isLoggedIn' => false,
            ];
        }

        $current_user = wp_get_current_user();
        return [
            'isLoggedIn'  => true,
            'avatarUrl'   => get_avatar_url( $current_user->ID ),
            'firstName'   => $current_user->first_name,
            'lastName'    => $current_user->last_name,
            'displayName' => $current_user->display_name,
            'email'       => $current_user->user_email,
            'role'        => $this->get_user_role_name(),
            'caps'        => Caps::get_current_user_caps(),
        ];
    }

    private function get_user_role_name() {
        $current_user = wp_get_current_user();
        if ( empty( $current_user->roles ) ) {
            return null;
        }
        global $wp_roles;
        return translate_user_role( $wp_roles->roles[ $current_user->roles[0] ]['name'] );
    }

    /**
     * @return AgentModel|null
     */
    public function get_agent_record_of_current_user() {
        if ( ! is_user_logged_in() ) {
            return null;
        }
        $current_user = wp_get_current_user();
        return $this->agents_service->find_one_with_info( [ 'email' => $current_user->user_email ] );
    }

    /**
     * @return CustomerModel|null
     */
    public function get_customer_record_of_current_user() {
        if ( ! is_user_logged_in() ) {
            return null;
        }
        $current_user = wp_get_current_user();
        return $this->customers_service->find_one_with_info( [ 'email' => $current_user->user_email ] );
    }
}
