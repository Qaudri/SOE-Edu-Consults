<?php
namespace Bookster\Controllers;

use Bookster\Features\Auth\Caps;
use Bookster\Services\CustomersService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\CustomerModel;

/**
 * API Controller for Customer Models
 *
 * @method static CustomersController get_instance()
 */
class CustomersController extends BaseRestController {
    use SingletonTrait;

    /** @var CustomersService */
    private $customers_service;

    protected function __construct() {
        $this->customers_service = CustomersService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/customers',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_customer' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/customers/query',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_customers' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/customers/count',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_count_customers' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/customers/bulk-action',
            [
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_customers_bulk' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                ],
            ]
        );

        $customer_id_args = [
            'customer_id' => [
                'type'     => 'number',
                'required' => true,
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/customers/(?P<customer_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_customer' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                    'args'                => $customer_id_args,
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_customer' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                    'args'                => $customer_id_args,
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_customer' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                    'args'                => $customer_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/customers/(?P<customer_id>\d+)/link-wp-user',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_link_customer' ],
                    'permission_callback' => [ $this, 'require_any_records_caps' ],
                    'args'                => $customer_id_args,
                ],
            ]
        );
    }

    public function query_customers( \WP_REST_Request $request ) {
        $args      = $request->get_json_params();
        $customers = $this->customers_service->find_where_with_info( $args );
        $total     = $this->customers_service->count_where( $args );

        $data = array_map(
            function( $customer ) {
                return $customer->to_array();
            },
            $customers
        );
        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    public function count_customers( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        return $this->customers_service->count_where( $args );
    }

    public function get_customer( \WP_REST_Request $request ) {
        $customer = $this->customers_service->find_by_id_with_info( $request->get_param( 'customer_id' ) );
        return $customer->to_array();
    }

    public function post_customer( \WP_REST_Request $request ) {
        $args = $request->get_json_params();

        $customer = $this->customers_service->insert( $args );
        return $customer->to_array();
    }

    public function patch_customer( \WP_REST_Request $request ) {
        $args     = $request->get_json_params();
        $customer = CustomerModel::find( $request->get_param( 'customer_id' ) );

        if ( isset( $args['email'] ) && $args['email'] !== $customer->email ) {
            $new_email = $args['email'];
            $count     = $this->customers_service->count_where( [ 'email' => $new_email ] );

            if ( $count > 0 ) {
                throw new \Error( esc_html( "Customer with email $new_email' already existed!" ) );
            }

            if ( ! is_null( $customer->wp_user_id ) ) {
                \wp_update_user(
                    [
                        'ID'         => $customer->wp_user_id,
                        'user_email' => $new_email,
                    ]
                );
            }
        }

        $customer = $this->customers_service->update( $request->get_param( 'customer_id' ), $args );
        return $customer->to_array();
    }

    public function link_customer( \WP_REST_Request $request ) {
        $email       = $request->get_param( 'email' );
        $customer_id = $request->get_param( 'customer_id' );
        $customer    = CustomerModel::find( $customer_id );

        if ( $email !== $customer->email ) {
            throw new \Error( "Email does not match customer's email!" );
        }

        $wp_user_id = $this->customers_service->connect( $email, $customer->first_name, $customer->last_name );
        $customer   = $this->customers_service->update(
            $customer_id,
            [
                'wp_user_id' => $wp_user_id,
            ]
        );
        return $customer->to_array();
    }

    public function delete_customer( \WP_REST_Request $request ) {
        return $this->customers_service->delete( $request->get_param( 'customer_id' ) );
    }

    public function delete_customers_bulk( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        foreach ( $args['ids'] as $id ) {
            $this->customers_service->delete( $id );
        }
        return count( $args['ids'] );
    }

    public function exec_query_customers( $request ) {
        return $this->exec_read( [ $this, 'query_customers' ], $request );
    }

    public function exec_count_customers( $request ) {
        return $this->exec_read( [ $this, 'count_customers' ], $request );
    }

    public function exec_get_customer( $request ) {
        return $this->exec_read( [ $this, 'get_customer' ], $request );
    }

    public function exec_post_customer( $request ) {
        return $this->exec_write( [ $this, 'post_customer' ], $request );
    }

    public function exec_patch_customer( $request ) {
        return $this->exec_write( [ $this, 'patch_customer' ], $request );
    }

    public function exec_delete_customer( $request ) {
        return $this->exec_write( [ $this, 'delete_customer' ], $request );
    }

    public function exec_delete_customers_bulk( $request ) {
        return $this->exec_write( [ $this, 'delete_customers_bulk' ], $request );
    }

    public function exec_link_customer( $request ) {
        return $this->exec_write( [ $this, 'link_customer' ], $request );
    }

    public function require_any_records_caps() {
        return RestAuth::require_any_caps( [ Caps::MANAGE_SHOP_RECORDS_CAP, Caps::MANAGE_AGENT_RECORDS_CAP ] );
    }
}
