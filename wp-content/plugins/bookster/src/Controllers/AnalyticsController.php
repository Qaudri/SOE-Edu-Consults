<?php
namespace Bookster\Controllers;

use Bookster\Services\AnalyticsService;
use Bookster\Features\Auth\RestAuth;
use Bookster\Features\Utils\SingletonTrait;

/**
 * AnalyticsController Controller
 *
 * @method static AnalyticsController get_instance()
 */
class AnalyticsController extends BaseRestController {
    use SingletonTrait;

    /** @var AnalyticsService */
    private $analytics_service;

    protected function __construct() {
        $this->analytics_service = AnalyticsService::get_instance();
        $this->init_hooks();
    }

    protected function init_hooks() {
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/overview',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_overview' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/performance',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_performance' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/leaderboards/agents',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_leaderboard_agents' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/leaderboards/services',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_leaderboard_services' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_shop_records_cap' ],
                ],
            ]
        );

        $agent_id_args = [
            'agent_id' => [
                'type'              => 'number',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
        ];
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/as-agent/(?P<agent_id>\d+)/overview',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_overview_by_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );
        register_rest_route(
            self::REST_NAMESPACE,
            'analytics/as-agent/(?P<agent_id>\d+)/performance',
            [
                [
                    'methods'             => 'PATCH',
                    'callback'            => [ $this, 'exec_query_performance_by_agent' ],
                    'permission_callback' => [ RestAuth::class, 'require_manage_agent_records_cap' ],
                    'args'                => $agent_id_args,
                ],
            ]
        );
    }

    public function query_overview( \WP_REST_Request $request ) {
        $args           = $request->get_json_params();
        $interval_range = $args['performance']['intervalRange'];

        $performance_report = get_transient( 'bookster_performance_report_' . $interval_range );
        if ( false === $performance_report ) {
            $current_filter                = $args['performance']['filter']['currentRange'];
            $previous_filter               = $args['performance']['filter']['previousRange'];
            $interval_step                 = $args['performance']['intervalStep'];
            $current_performance_report    = $this->analytics_service->query_performance_report( $current_filter[0], $current_filter[1], null );
            $previous_performance_report   = $this->analytics_service->query_performance_report( $previous_filter[0], $previous_filter[1], null );
            $current_performance_intervals = $this->analytics_service->query_performance_intervals( $current_filter[0], $current_filter[1], $interval_step, null );

            $performance_report = [
                'intervals' => $current_performance_intervals,
                'totals'    => [
                    'current'  => $current_performance_report,
                    'previous' => $previous_performance_report,
                ],
            ];
            set_transient( 'bookster_performance_report_' . $interval_range, $performance_report, MINUTE_IN_SECONDS * 10 );
        }

        $leaderboard_services = get_transient( 'bookster_leaderboard_services' );
        if ( false === $leaderboard_services ) {
            $current_filter            = $args['serviceLeaderboard']['filter']['currentRange'];
            $previous_filter           = $args['serviceLeaderboard']['filter']['previousRange'];
            $leaderboard_services_rows = $this->analytics_service->query_leaderboard_services( $current_filter[0], $current_filter[1], $previous_filter[0], $previous_filter[1] );
            $leaderboard_services      = [
                'rows' => $leaderboard_services_rows,
            ];
            set_transient( 'bookster_leaderboard_services', $leaderboard_services, MINUTE_IN_SECONDS * 10 );
        }

        $leaderboard_agents = get_transient( 'bookster_leaderboard_agents' );
        if ( false === $leaderboard_agents ) {
            $current_filter          = $args['agentLeaderboard']['filter']['currentRange'];
            $previous_filter         = $args['agentLeaderboard']['filter']['previousRange'];
            $leaderboard_agents_rows = $this->analytics_service->query_leaderboard_agents( $current_filter[0], $current_filter[1], $previous_filter[0], $previous_filter[1] );
            $leaderboard_agents      = [
                'rows' => $leaderboard_agents_rows,
            ];
            set_transient( 'bookster_leaderboard_agents', $leaderboard_agents, MINUTE_IN_SECONDS * 10 );
        }

        return [
            'performance'        => $performance_report,
            'serviceLeaderboard' => $leaderboard_services,
            'agentLeaderboard'   => $leaderboard_agents,
        ];
    }

    public function query_performance( \WP_REST_Request $request ) {
        $args            = $request->get_json_params();
        $save_transient  = $args['saveTransient'];
        $current_filter  = $args['filter']['currentRange'];
        $previous_filter = $args['filter']['previousRange'];
        $interval_step   = $args['intervalStep'];
        $interval_range  = $args['intervalRange'];

        $current_performance_report    = $this->analytics_service->query_performance_report( $current_filter[0], $current_filter[1], null );
        $previous_performance_report   = $this->analytics_service->query_performance_report( $previous_filter[0], $previous_filter[1], null );
        $current_performance_intervals = $this->analytics_service->query_performance_intervals( $current_filter[0], $current_filter[1], $interval_step, null );

        $performance_report = [
            'intervals' => $current_performance_intervals,
            'totals'    => [
                'current'  => $current_performance_report,
                'previous' => $previous_performance_report,
            ],
        ];

        if ( true === $save_transient ) {
            set_transient( 'bookster_performance_report_' . $interval_range, $performance_report, MINUTE_IN_SECONDS * 10 );
        }

        return $performance_report;
    }

    public function query_leaderboard_agents( \WP_REST_Request $request ) {
        $args            = $request->get_json_params();
        $save_transient  = $args['saveTransient'];
        $current_filter  = $args['filter']['currentRange'];
        $previous_filter = $args['filter']['previousRange'];

        $leaderboard_agents_rows = $this->analytics_service->query_leaderboard_agents( $current_filter[0], $current_filter[1], $previous_filter[0], $previous_filter[1] );
        $leaderboard_agents      = [
            'rows' => $leaderboard_agents_rows,
        ];

        if ( true === $save_transient ) {
            set_transient( 'bookster_leaderboard_agents', $leaderboard_agents, MINUTE_IN_SECONDS * 10 );
        }
        return $leaderboard_agents;
    }

    public function query_leaderboard_services( \WP_REST_Request $request ) {
        $args            = $request->get_json_params();
        $save_transient  = $args['saveTransient'];
        $current_filter  = $args['filter']['currentRange'];
        $previous_filter = $args['filter']['previousRange'];

        $leaderboard_services_rows = $this->analytics_service->query_leaderboard_services( $current_filter[0], $current_filter[1], $previous_filter[0], $previous_filter[1] );
        $leaderboard_services      = [
            'rows' => $leaderboard_services_rows,
        ];

        if ( true === $save_transient ) {
            set_transient( 'bookster_leaderboard_services', $leaderboard_services, MINUTE_IN_SECONDS * 10 );
        }
        return $leaderboard_services;
    }

    public function query_overview_by_agent( \WP_REST_Request $request ) {
        $agent_id        = $request->get_param( 'agent_id' );
        $args            = $request->get_json_params();
        $current_filter  = $args['performance']['filter']['currentRange'];
        $previous_filter = $args['performance']['filter']['previousRange'];
        $interval_step   = $args['performance']['intervalStep'];
        $interval_range  = $args['performance']['intervalRange'];

        $performance_report = get_transient( 'bookster_performance_report_' . $interval_range . '_agent_' . $agent_id );
        $performance_report = false;
        if ( false === $performance_report ) {
            $current_performance_report    = $this->analytics_service->query_performance_report( $current_filter[0], $current_filter[1], $agent_id );
            $previous_performance_report   = $this->analytics_service->query_performance_report( $previous_filter[0], $previous_filter[1], $agent_id );
            $current_performance_intervals = $this->analytics_service->query_performance_intervals( $current_filter[0], $current_filter[1], $interval_step, $agent_id );

            $performance_report = [
                'intervals' => $current_performance_intervals,
                'totals'    => [
                    'current'  => $current_performance_report,
                    'previous' => $previous_performance_report,
                ],
            ];
            set_transient( 'bookster_performance_report_' . $interval_range . '_agent_' . $agent_id, $performance_report, MINUTE_IN_SECONDS * 10 );
        }

        return [ 'performance' => $performance_report ];
    }

    public function query_performance_by_agent( \WP_REST_Request $request ) {
        $agent_id        = $request->get_param( 'agent_id' );
        $args            = $request->get_json_params();
        $save_transient  = $args['saveTransient'];
        $current_filter  = $args['filter']['currentRange'];
        $previous_filter = $args['filter']['previousRange'];
        $interval_step   = $args['intervalStep'];
        $interval_range  = $args['intervalRange'];

        $current_performance_report    = $this->analytics_service->query_performance_report( $current_filter[0], $current_filter[1], $agent_id );
        $previous_performance_report   = $this->analytics_service->query_performance_report( $previous_filter[0], $previous_filter[1], $agent_id );
        $current_performance_intervals = $this->analytics_service->query_performance_intervals( $current_filter[0], $current_filter[1], $interval_step, $agent_id );

        $performance_report = [
            'intervals' => $current_performance_intervals,
            'totals'    => [
                'current'  => $current_performance_report,
                'previous' => $previous_performance_report,
            ],
        ];

        if ( true === $save_transient ) {
            set_transient( 'bookster_performance_report_' . $interval_range . '_agent_' . $agent_id, $performance_report, MINUTE_IN_SECONDS * 10 );
        }

        return $performance_report;
    }

    public function exec_query_overview( $request ) {
        return $this->exec_read( [ $this, 'query_overview' ], $request );
    }
    public function exec_query_performance( $request ) {
        return $this->exec_read( [ $this, 'query_performance' ], $request );
    }
    public function exec_query_leaderboard_agents( $request ) {
        return $this->exec_read( [ $this, 'query_leaderboard_agents' ], $request );
    }
    public function exec_query_leaderboard_services( $request ) {
        return $this->exec_read( [ $this, 'query_leaderboard_services' ], $request );
    }

    public function exec_query_overview_by_agent( $request ) {
        return $this->exec_read( [ $this, 'query_overview_by_agent' ], $request );
    }
    public function exec_query_performance_by_agent( $request ) {
        return $this->exec_read( [ $this, 'query_performance_by_agent' ], $request );
    }
}
