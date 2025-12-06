<?php
namespace Bookster\Controllers;

use Bookster\Services\ServicesCategoriesService;
use Bookster\Services\ServicesService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;

/**
 * API Controller for Service Models
 *
 * @method static ServicesController get_instance()
 */
class ServicesController extends BaseRestController {
    use SingletonTrait;

    /** @var ServicesCategoriesService */
    private $categories_service;
    /** @var ServicesService */
    private $services_service;

    protected function __construct() {
        $this->categories_service = ServicesCategoriesService::get_instance();
        $this->services_service   = ServicesService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/services/group_by_category/public',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_services_group_by_category' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/services/group_by_category',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_services_group_by_category' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/services/query',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_services' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/services/count',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_count_services' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/services',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_service' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        $service_id_args = [
            'service_id' => [
                'type'     => 'number',
                'required' => true,
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/services/(?P<service_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_service' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $service_id_args,
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_service' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $service_id_args,
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_service' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $service_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            '/services/(?P<service_id>\d+)/update_position',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_service_update_position' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $service_id_args,
                ],
            ]
        );
    }

    public function get_services_group_by_category() {
        $services = $this->services_service->find_where_with_info( [] );
        $services = array_map(
            function( $service ) {
                return $service->to_array();
            },
            $services
        );

        $categories = $this->categories_service->find_where(
            [
                'order_by' => 'position',
                'order'    => 'ASC',
            ]
        );
        $categories = array_map(
            function( $category ) {
                return $category->to_array();
            },
            $categories
        );

        $categories = array_map(
            function( $category ) use ( $services ) {
                $services_of_cate = [];
                foreach ( $services as $service ) {
                    if ( $category['service_category_id'] === $service['service_category_id'] ) {
                        $services_of_cate[] = $service;
                    }
                }
                $category['services'] = $services_of_cate;

                return $category;
            },
            $categories
        );

        return [
            'data' => $categories,
        ];
    }

    public function query_services( \WP_REST_Request $request ) {
        $args     = $request->get_json_params();
        $services = $this->services_service->find_where_with_info( $args );
        $total    = $this->services_service->count_where( $args );

        $data = array_map(
            function( $service ) {
                return $service->to_array();
            },
            $services
        );
        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    public function count_services( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        return $this->services_service->count_where( $args );
    }

    public function get_service( \WP_REST_Request $request ) {
        $data = $this->services_service->find_by_id_with_info( $request->get_param( 'service_id' ) );
        return $data->to_array();
    }

    public function post_service( \WP_REST_Request $request ) {
        $service = $this->services_service->insert( $request->get_json_params() );
        return $service->to_array();
    }

    public function patch_service( \WP_REST_Request $request ) {
        $service = $this->services_service->update(
            $request->get_param( 'service_id' ),
            $request->get_json_params()
        );
        return $service->to_array();
    }

    public function patch_service_update_position( \WP_REST_Request $request ) {
        $args         = $request->get_json_params();
        $service_data = $this->services_service->find_and_update_position(
            $request->get_param( 'service_id' ),
            $args['service_category_id'],
            $args['position']
        );

        return $service_data->to_array();
    }

    public function delete_service( \WP_REST_Request $request ) {
        return $this->services_service->delete( $request->get_param( 'service_id' ) );
    }

    public function exec_get_services_group_by_category( $request ) {
        return $this->exec_read( [ $this, 'get_services_group_by_category' ], $request );
    }

    public function exec_query_services( $request ) {
        return $this->exec_read( [ $this, 'query_services' ], $request );
    }

    public function exec_count_services( $request ) {
        return $this->exec_read( [ $this, 'count_services' ], $request );
    }

    public function exec_post_service( $request ) {
        return $this->exec_write( [ $this, 'post_service' ], $request );
    }

    public function exec_get_service( $request ) {
        return $this->exec_read( [ $this, 'get_service' ], $request );
    }

    public function exec_patch_service( $request ) {
        return $this->exec_write( [ $this, 'patch_service' ], $request );
    }

    public function exec_delete_service( $request ) {
        return $this->exec_write( [ $this, 'delete_service' ], $request );
    }

    public function exec_patch_service_update_position( $request ) {
        return $this->exec_write( [ $this, 'patch_service_update_position' ], $request );
    }
}
