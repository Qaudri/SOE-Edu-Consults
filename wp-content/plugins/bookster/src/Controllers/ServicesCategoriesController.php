<?php
namespace Bookster\Controllers;

use Bookster\Services\ServicesCategoriesService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;

/**
 * API Controller for Service Category Models
 *
 * @method static ServicesCategoriesController get_instance()
 */
class ServicesCategoriesController extends BaseRestController {
    use SingletonTrait;

    /** @var ServicesCategoriesService */
    private $categories_service;

    protected function __construct() {
        $this->categories_service = ServicesCategoriesService::get_instance();

        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/services_categories',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_categories' ],
                    'permission_callback' => '__return_true',
                ],
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_category' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        $category_id_args = [
            'service_category_id' => [
                'type'     => 'number',
                'required' => true,
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/services_categories/(?P<service_category_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_category' ],
                    'permission_callback' => '__return_true',
                    'args'                => $category_id_args,
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_category' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $category_id_args,
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_category' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $category_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/services_categories/(?P<service_category_id>\d+)/update_position',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_category_update_position' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $category_id_args,
                ],
            ]
        );
    }

    public function get_categories() {
        $categories = $this->categories_service->find_where( [] );
        $data       = array_map(
            function( $categories ) {
                return $categories->to_array();
            },
            $categories
        );

        return [
            'data' => $data,
        ];
    }

    public function get_category( \WP_REST_Request $request ) {
        $category = $this->categories_service->find_by_id( $request->get_param( 'service_category_id' ) );
        return $category->to_array();
    }

    public function post_category( \WP_REST_Request $request ) {
        $category = $this->categories_service->insert( $request->get_json_params() );
        return $category->to_array();
    }

    public function patch_category( \WP_REST_Request $request ) {
        $category = $this->categories_service->update( $request->get_param( 'service_category_id' ), $request->get_json_params() );
        return $category->to_array();
    }

    public function delete_category( \WP_REST_Request $request ) {
        return $this->categories_service->delete( $request->get_param( 'service_category_id' ) );
    }

    public function patch_category_update_position( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        return $this->categories_service->find_and_update_position(
            $request->get_param( 'service_category_id' ),
            $args['position']
        );
    }

    public function exec_get_categories( $request ) {
        return $this->exec_read( [ $this, 'get_categories' ], $request );
    }

    public function exec_get_category( $request ) {
        return $this->exec_read( [ $this, 'get_category' ], $request );
    }

    public function exec_post_category( $request ) {
        return $this->exec_write( [ $this, 'post_category' ], $request );
    }

    public function exec_patch_category( $request ) {
        return $this->exec_write( [ $this, 'patch_category' ], $request );
    }

    public function exec_delete_category( $request ) {
        return $this->exec_write( [ $this, 'delete_category' ], $request );
    }

    public function exec_patch_category_update_position( $request ) {
        return $this->exec_write( [ $this, 'patch_category_update_position' ], $request );
    }
}
