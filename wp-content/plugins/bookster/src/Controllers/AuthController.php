<?php
namespace Bookster\Controllers;

use Bookster\Services\CustomersService;
use Bookster\Services\AuthService;
use Bookster\Features\Utils\SingletonTrait;

/**
 * Authentication Controller
 *
 * @method static AuthController get_instance()
 */
class AuthController extends BaseRestController {
    use SingletonTrait;

    /** @var CustomersService */
    private $customers_service;
    /** @var AuthService */
    private $auth_service;

    protected function __construct() {
        $this->customers_service = CustomersService::get_instance();
        $this->auth_service      = AuthService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {

        register_rest_route(
            self::REST_NAMESPACE,
            'auth/login',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_login' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            'auth/logout',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_logout' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            'auth/wp-user/email-exists',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_email_exists' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    public function login( \WP_REST_Request $request ) {
        $this->auth_service->login( $request->get_param( 'username' ), $request->get_param( 'password' ), $request->get_param( 'remember' ) );
        $customer = $this->auth_service->get_customer_record_of_current_user();
        $agent    = $this->auth_service->get_agent_record_of_current_user();

        return [
            'wpUserInfo'     => $this->auth_service->get_wp_user_info(),
            'customerRecord' => null !== $customer ? $customer->to_array() : null,
            'agentRecord'    => null !== $agent ? $agent->to_array() : null,
        ];
    }

    public function logout() {
        wp_logout();
    }

    public function check_wp_user_email_exists( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        $res  = email_exists( $args['email'] );
        return $res ? 1 : 0;
    }

    public function exec_patch_login( $request ) {
        return $this->exec_write( [ $this, 'login' ], $request );
    }

    public function exec_patch_logout( $request ) {
        return $this->exec_write( [ $this, 'logout' ], $request );
    }

    public function exec_patch_email_exists( $request ) {
        return $this->exec_read( [ $this, 'check_wp_user_email_exists' ], $request );
    }
}
