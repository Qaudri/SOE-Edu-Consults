<?php
namespace Bookster\Services;

use Bookster\Features\Auth\Roles;
use Bookster\Features\Enums\ObjectTypeEnum;
use Bookster\Models\AgentModel;
use Bookster\Models\AgentMetaModel;
use Bookster\Models\AvailableAgentServiceModel;
use Bookster\Models\AssignmentModel;
use Bookster\Models\AssignmentMetaModel;
use Bookster\Models\BookingModel;
use Bookster\Models\BookingMetaModel;
use Bookster\Models\AppointmentModel;
use Bookster\Models\AppointmentMetaModel;
use Bookster\Models\TransactionModel;
use Bookster\Features\Utils\SingletonTrait;
use Bookster\Features\Errors\NotFoundException;
use Bookster\Models\Database\QueryBuilder;

/**
 * Service Service
 *
 * @method static AgentsService get_instance()
 */
class AgentsService extends BaseService {
    use SingletonTrait;

    /** @var SettingsService */
    private $settings_service;
    /** @var WPUsersService */
    private $wp_users_service;
    /** @var AvailableAgentServiceService */
    private $available_agent_service_service;

    protected function __construct() {
        $this->settings_service                = SettingsService::get_instance();
        $this->wp_users_service                = WPUsersService::get_instance();
        $this->available_agent_service_service = AvailableAgentServiceService::get_instance();
    }

    public function find_where( array $args ): array {
        $agents = AgentModel::where( $this->prepare_where_args( $args ) );
        $this->validate_wpdb_query();

        return $agents;
    }

    public function count_where( array $args ): int {
        $count = AgentModel::count( $this->prepare_count_args( $args ) );
        $this->validate_wpdb_query();

        return $count;
    }

    public function find_where_with_info( array $args ): array {
        $builder = $this->create_builder_find_where_with_info( $this->prepare_where_args( $args ) );
        $agents  = $builder->get();
        $this->validate_wpdb_query();

        return array_map( [ AgentModel::class, 'init_from_data' ], $agents );
    }

    /**
     * @param array $args
     * @return AgentModel|null
     */
    public function find_one_with_info( array $args ) {
        $agents_models = $this->find_where_with_info( $args );
        return ! empty( $agents_models ) ? $agents_models[0] : null;
    }

    public function find_by_id( int $agent_id ): AgentModel {
        $agent = AgentModel::find( $agent_id );
        if ( ! $agent ) {
            throw new NotFoundException( 'Agent Not Found', ObjectTypeEnum::AGENT, $agent_id );
        }
        return $agent;
    }

    public function find_by_id_with_info( int $agent_id ): AgentModel {
        $builder    = $this->create_builder_find_where_with_info( [ 'agent.agent_id' => $agent_id ] );
        $attributes = $builder->first();
        $this->validate_wpdb_query();

        if ( ! $attributes ) {
            throw new NotFoundException( 'Agent Not Found', ObjectTypeEnum::AGENT, $agent_id );
        }
        return AgentModel::init_from_data( $attributes );
    }

    public function insert( array $attributes ): AgentModel {
        if ( $this->is_auto_link_wp_users() ) {
            $attributes['wp_user_id'] = $this->connect(
                $attributes['email'],
                $attributes['first_name'],
                $attributes['last_name']
            );
        }

        $agent = AgentModel::insert( AgentModel::prepare_saved_data( $attributes ) );
        if ( is_null( $agent ) ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Agent: ' . $wpdb->last_error ) );
        }

        if ( ! empty( $attributes['available_agent_services'] ) ) {
            $this->available_agent_service_service->update_by_agent( $agent->agent_id, $attributes['available_agent_services'] );
        }

        return $this->find_by_id_with_info( $agent->agent_id );
    }

    public function update( int $agent_id, array $data ): AgentModel {
        $agent = AgentModel::find( $agent_id );
        if ( ! $agent ) {
            throw new NotFoundException( 'Agent Not Found', ObjectTypeEnum::AGENT, $agent_id );
        }

        $success = $agent->update( AgentModel::prepare_saved_data( $data ) );
        if ( false === $success ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Agent: ' . $wpdb->last_error ) );
        }

        if ( ! empty( $data['available_agent_services'] ) ) {
            $this->available_agent_service_service->update_by_agent( $agent_id, $data['available_agent_services'] );
        }

        return $this->find_by_id_with_info( $agent->agent_id );
    }

    public function connect( string $email, string $first_name, string $last_name ): int {
        return $this->wp_users_service->maybe_generate_wp_user(
            $email,
            $first_name,
            $last_name,
            Roles::AGENT_ROLE
        );
    }

    public function delete( int $agent_id ): bool {
        $agent = AgentModel::find( $agent_id );
        if ( ! $agent ) {
            throw new NotFoundException( 'Agent Not Found', ObjectTypeEnum::AGENT, $agent_id );
        }

        $agent_table                   = AgentModel::get_tablename();
        $agent_meta_table              = AgentMetaModel::get_tablename();
        $available_agent_service_table = AvailableAgentServiceModel::get_tablename();

        $appointment_table      = AppointmentModel::get_tablename();
        $appointment_meta_table = AppointmentMetaModel::get_tablename();
        $assignment_table       = AssignmentModel::get_tablename();
        $assignment_meta_table  = AssignmentMetaModel::get_tablename();
        $booking_table          = BookingModel::get_tablename();
        $booking_meta_table     = BookingMetaModel::get_tablename();
        $transaction_table      = TransactionModel::get_tablename();

        global $wpdb;
        $query = $wpdb->prepare(
            "DELETE agent, agent_meta, available_agent_service, assignment,
                appointment, appointment_meta_of_appointment, assignment_of_appointment, assignment_meta_of_appointment, booking_of_appointment, booking_meta_of_appointment, transaction_of_appointment
            FROM $agent_table as agent
            LEFT JOIN $agent_meta_table as agent_meta ON agent_meta.agent_id = agent.agent_id
            LEFT JOIN $available_agent_service_table as available_agent_service ON available_agent_service.agent_id = agent.agent_id

            LEFT JOIN $assignment_table as assignment ON assignment.agent_id = agent.agent_id
            LEFT JOIN $appointment_table as appointment ON appointment.appointment_id = assignment.appointment_id
            LEFT JOIN $appointment_meta_table as appointment_meta_of_appointment ON appointment_meta_of_appointment.appointment_id = appointment.appointment_id

            LEFT JOIN $assignment_table as assignment_of_appointment ON assignment_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $assignment_meta_table as assignment_meta_of_appointment ON assignment_meta_of_appointment.appointment_id = assignment_of_appointment.appointment_id

            LEFT JOIN $booking_table as booking_of_appointment ON booking_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $booking_meta_table as booking_meta_of_appointment ON booking_meta_of_appointment.appointment_id = appointment.appointment_id
            LEFT JOIN $transaction_table as transaction_of_appointment ON transaction_of_appointment.appointment_id = appointment.appointment_id

            WHERE agent.agent_id=%d",
            $agent_id
        );
        return $this->exec_wpdb_query( $query, 'Deleting Agent' );
    }

    public function update_many( array $ids, array $data ) {
        $affected_rows = AgentModel::update_where(
            AgentModel::prepare_saved_data( $data ),
            [
                'in_args' => [
                    'agent_id' => [
                        'values'      => $ids,
                        'placeholder' => '%d',
                    ],
                ],
            ]
        );

        if ( false === $affected_rows ) {
            global $wpdb;
            throw new \Exception( esc_html( 'Error Saving Agent: ' . $wpdb->last_error ) );
        }
        return $affected_rows;
    }

    public function is_auto_link_wp_users(): bool {
        $permissions_settings = $this->settings_service->get_manager_data()['permissionsSettings'];
        return 'auto' === $permissions_settings['agents_link_wp_users'];
    }

    private function create_builder_find_where_with_info( array $args ): QueryBuilder {
        global $wpdb;
        $builder = AgentModel::create_where_builder( $this->prepare_where_args( $args ), 'agent' );

        $builder->select( 'agent.*' )
            ->select( "users.`display_name` as 'wp_user_display_name'" )
            ->select(
                "JSON_ARRAYAGG(
                    JSON_OBJECT(
                        'available_agent_service_id', available.`available_agent_service_id`,
                        'agent_id', available.`agent_id`,
                        'service_id', available.`service_id`,
                        'available', available.`available`
                    )
                ) AS '_available_agent_services'"
            )
            ->join(
                AvailableAgentServiceModel::TABLE . ' as `available`',
                [ [ 'raw' => 'agent.agent_id = available.agent_id' ] ],
                'LEFT'
            )
            ->join(
                "$wpdb->users as `users`",
                [ [ 'raw' => 'agent.wp_user_id = users.ID' ] ],
                'LEFT',
                false
            )
            ->group_by( 'agent.agent_id' );

        if ( ! isset( $args['order_by'] ) ) {
            $builder->order_by( 'activated', 'DESC' );
            $builder->order_by( 'priority', 'ASC' );
            $builder->order_by( 'visibility', 'DESC' );
            $builder->order_by( 'agent_id', 'DESC' );
        }
        return $builder;
    }

    public function preload_transient_attachment_posts( $agents ) {
        if ( empty( $agents ) ) {
            return;
        }

        $avatar_id_list = [];
        foreach ( $agents as $agent ) {
            $avatar_id = $agent->avatar_id;
            if ( ! empty( $avatar_id ) ) {
                $avatar_id_list[] = $avatar_id;
            }
        }

        if ( $avatar_id_list ) {
            get_posts(
                [
                    'include'   => array_unique( $avatar_id_list ),
                    'post_type' => 'attachment',
                ]
            );
        }
    }
}
