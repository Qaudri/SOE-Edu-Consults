<?php
namespace Bookster\Controllers;

use Bookster\Services\AgentsService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Models\AgentModel;

/**
 * API Controller for Agent Models
 *
 * @method static AgentsController get_instance()
 */
class AgentsController extends BaseRestController {
    use SingletonTrait;

    /** @var AgentsService */
    private $agents_service;

    protected function __construct() {
        $this->agents_service = AgentsService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/public',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_all_agents' ],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/query',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_agents' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/count',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_count_agents' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/agents',
            [
                [
                    'methods'             => \WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'exec_post_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/bulk-action',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_agents_bulk' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_agents_bulk' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        $agent_id_args = [
            'agent_id' => [
                'type'     => 'number',
                'required' => true,
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/(?P<agent_id>\d+)',
            [
                [
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'exec_get_agent' ],
                    'permission_callback' => '__return_true',
                    'args'                => $agent_id_args,
                ],
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_patch_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $agent_id_args,
                ],
                [
                    'methods'             => \WP_REST_Server::DELETABLE,
                    'callback'            => [ $this, 'exec_delete_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );

        register_rest_route(
            self::REST_NAMESPACE,
            '/agents/(?P<agent_id>\d+)/link-wp-user',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_link_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );
    }

    public function get_all_agents() {
        $agents = $this->agents_service->find_where_with_info( [] );

        $this->agents_service->preload_transient_attachment_posts( $agents );
        $data = array_map(
            function( $agent ) {
                return $agent->to_array();
            },
            $agents
        );
        return [
            'data'  => $data,
            'total' => count( $data ),
        ];
    }

    public function query_agents( \WP_REST_Request $request ) {
        $args   = $request->get_json_params();
        $agents = $this->agents_service->find_where_with_info( $args );
        $total  = $this->agents_service->count_where( $args );

        $this->agents_service->preload_transient_attachment_posts( $agents );
        $data = array_map(
            function( $agent ) {
                return $agent->to_array();
            },
            $agents
        );
        return [
            'data'  => $data,
            'total' => $total,
        ];
    }

    public function count_agents( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        return $this->agents_service->count_where( $args );
    }

    public function get_agent( \WP_REST_Request $request ) {
        $agent = $this->agents_service->find_by_id_with_info( $request->get_param( 'agent_id' ) );
        return $agent->to_array();
    }

    public function post_agent( \WP_REST_Request $request ) {
        $args = $request->get_json_params();

        $agent = $this->agents_service->insert( $args );
        return $agent->to_array();
    }

    public function patch_agent( \WP_REST_Request $request ) {
        $args  = $request->get_json_params();
        $agent = AgentModel::find( $request->get_param( 'agent_id' ) );

        if ( isset( $args['email'] ) && $args['email'] !== $agent->email ) {
            $new_email = $args['email'];
            $count     = $this->agents_service->count_where( [ 'email' => $new_email ] );

            if ( $count > 0 ) {
                throw new \Error( esc_html( "Agent with email $new_email' already existed!" ) );
            }

            if ( ! is_null( $agent->wp_user_id ) ) {
                \wp_update_user(
                    [
                        'ID'         => $agent->wp_user_id,
                        'user_email' => $new_email,
                    ]
                );
            }
        }

        $agent = $this->agents_service->update( $request->get_param( 'agent_id' ), $args );
        return $agent->to_array();
    }

    public function link_agent( \WP_REST_Request $request ) {
        $email    = $request->get_param( 'email' );
        $agent_id = $request->get_param( 'agent_id' );
        $agent    = AgentModel::find( $agent_id );

        if ( $email !== $agent->email ) {
            throw new \Error( "Email does not match agent's email!" );
        }

        $wp_user_id = $this->agents_service->connect( $email, $agent->first_name, $agent->last_name );
        $agent      = $this->agents_service->update(
            $agent_id,
            [
                'wp_user_id' => $wp_user_id,
            ]
        );
        return $agent->to_array();
    }

    public function delete_agent( \WP_REST_Request $request ) {
        return $this->agents_service->delete( $request->get_param( 'agent_id' ) );
    }

    public function patch_agents_bulk( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        $this->validate_arguments(
            array_keys( $args['values'] ),
            [ 'activated', 'visibility' ]
        );
        $affected_rows = $this->agents_service->update_many( $args['ids'], $args['values'] );
        return $affected_rows;
    }

    public function delete_agents_bulk( \WP_REST_Request $request ) {
        $args = $request->get_json_params();
        foreach ( $args['ids'] as $id ) {
            $this->agents_service->delete( $id );
        }
        return count( $args['ids'] );
    }

    public function exec_get_all_agents( $request ) {
        return $this->exec_read( [ $this, 'get_all_agents' ], $request );
    }

    public function exec_query_agents( $request ) {
        return $this->exec_read( [ $this, 'query_agents' ], $request );
    }

    public function exec_count_agents( $request ) {
        return $this->exec_read( [ $this, 'count_agents' ], $request );
    }

    public function exec_get_agent( $request ) {
        return $this->exec_read( [ $this, 'get_agent' ], $request );
    }

    public function exec_post_agent( $request ) {
        return $this->exec_write( [ $this, 'post_agent' ], $request );
    }

    public function exec_patch_agent( $request ) {
        return $this->exec_write( [ $this, 'patch_agent' ], $request );
    }

    public function exec_delete_agent( $request ) {
        return $this->exec_write( [ $this, 'delete_agent' ], $request );
    }

    public function exec_patch_agents_bulk( $request ) {
        return $this->exec_write( [ $this, 'patch_agents_bulk' ], $request );
    }

    public function exec_delete_agents_bulk( $request ) {
        return $this->exec_write( [ $this, 'delete_agents_bulk' ], $request );
    }

    public function exec_link_agent( $request ) {
        return $this->exec_write( [ $this, 'link_agent' ], $request );
    }
}
