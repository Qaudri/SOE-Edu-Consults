<?php
namespace Bookster\Features\Tasks\Dispatcher;

use Bookster\Features\Enums\CronEnum;
use Bookster\Features\Utils\RandomUtils;
use Bookster\Features\Logger;

/**
 * Save arguments to a queue and Execute the callback in Async Request.
 * Inspired by WP_Background_Process.
 * https://github.com/A5hleyRich/wp-background-processing
 */
class AsyncDispatcher extends BaseDispatcher {

    public function __construct( string $task_name, $time_limit = 20, $lock_duration = 60 ) {
        $this->task_name     = $task_name;
        $this->time_limit    = $time_limit;
        $this->lock_duration = $lock_duration;

        add_action( 'shutdown', [ $this, 'maybe_dispatch_new_queue' ] );
        add_action( 'wp_ajax_' . $this->task_name, [ $this, 'maybe_execute_tasks' ] );
        add_action( 'wp_ajax_nopriv_' . $this->task_name, [ $this, 'maybe_execute_tasks' ] );

        add_action( $this->task_name . '_process_healthcheck', [ $this, 'run_process_healthcheck' ] );
    }

    // Execute Region

    /** @var int in second */
    protected $start_time;
    /** @var int in second */
    protected $time_limit;

    public function maybe_execute_tasks() {
        // Don't lock up other requests while processing
        session_write_close();

        if ( $this->is_process_locked() ) {
            // A process already running this task.
            wp_die();
        }

        if ( $this->is_queue_empty() ) {
            // No item to process.
            wp_die();
        }

        check_ajax_referer( $this->task_name, 'nonce' );

        $this->execute_tasks();

        wp_die();
    }

    protected function execute_tasks() {
        $this->lock_process();

        $queue_id    = null;
        $queue_items = [];

        do {
            if ( null === $queue_id ) {
                // Begin executing a new queue.
                $current_queue = $this->get_first_queue();

                if ( null === $current_queue ) {
                    // Complete all queues.
                    break;
                }

                $queue_id    = $current_queue->key;
                $queue_items = $current_queue->queue_items;
            }

            $queue_item_id = key( $queue_items );
            $args          = current( $queue_items );

            try {
                do_action( $this->task_name . '_callback', $args );
            } catch ( \Throwable $ex ) {
                // Do not block a queue by a single error. Always log the error in Async Task.
                Logger::log_throwable( $ex, true );
            }

            unset( $queue_items[ $queue_item_id ] );

            if ( empty( $queue_items ) ) {
                // Complete a queue.
                $this->delete_queue( $queue_id );
                $queue_id    = null;
                $queue_items = [];
            }
        } while ( $this->is_limit_exceeded() );

        if ( ! empty( $queue_items ) ) {
            // Update queue. Continue on next request.
            $this->update_queue( $queue_id, $queue_items );
        }

        $this->unlock_process();

        if ( ! $this->is_queue_empty() ) {
            // Exceeded limit, continue on next request.
            $this->send_async_request();
        } else {
            // Complete all queues.
            $this->clear_healthcheck_event();
        }
        wp_die();
    }

    protected function is_limit_exceeded() {
        if ( time() > $this->start_time + $this->time_limit ) {
            return true;
        }

        // 90% of the memory limit.
        $memory_limit   = $this->get_memory_limit() * 0.9;
        $current_memory = memory_get_usage( true );

        if ( $current_memory > $memory_limit ) {
            return true;
        }

        return false;
    }

    protected function get_memory_limit() {
        if ( function_exists( 'ini_get' ) ) {
            $memory_limit = ini_get( 'memory_limit' );
        } else {
            // Sensible default.
            $memory_limit = '128M';
        }

        if ( ! $memory_limit || -1 === $memory_limit ) {
            // Unlimited, set to 32GB.
            $memory_limit = '32000M';
        }

        return wp_convert_hr_to_bytes( $memory_limit );
    }

    // Queue Region

    /**
     * Check if there is no queue exist.
     *
     * @return bool True mean no more task to do.
     */
    protected function is_queue_empty() {
        global $wpdb;

        $key   = $wpdb->esc_like( $this->task_name . '_queue_' ) . '%';
        $table = $wpdb->options;
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE option_name LIKE %s", $key ) ); // @codingStandardsIgnoreLine

        return ! ( $count > 0 );
    }

    protected function get_first_queue() {
        global $wpdb;

        $key   = $wpdb->esc_like( $this->task_name . '_queue_' ) . '%';
        $table = $wpdb->options;
        $row   = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE option_name LIKE %s ORDER BY option_id ASC LIMIT 1",
                $key
            )
        );

        if ( ! $row ) {
            return null;
        }

        $queue              = new \stdClass();
        $queue->key         = substr( $row->option_name, strlen( $this->task_name . '_queue_' ) );
        $queue->queue_items = maybe_unserialize( $row->option_value );

        return $queue;
    }

    protected function update_queue( $queue_id, $queue ) {
        update_option( $this->task_name . '_queue_' . $queue_id, $queue );
    }

    protected function delete_queue( $queue_id ) {
        delete_option( $this->task_name . '_queue_' . $queue_id );
    }

    // Lock Region

    /** @var int */
    protected $lock_duration;

    protected function is_process_locked() {
        if ( get_transient( $this->task_name . '_process_locked' ) ) {
            return true;
        }
        return false;
    }

    protected function lock_process() {
        $this->start_time = time();

        set_transient( $this->task_name . '_process_locked', microtime(), $this->lock_duration );
    }

    protected function unlock_process() {
        delete_transient( $this->task_name . '_process_locked' );
    }

    // Dispatch Region

    /** Queue of args wait to dispatch on shutdown */
    protected $dispatch_queue = [];

    public function dispatch( $args = [] ) {
        $queue_item_id                          = RandomUtils::gen_unique_id();
        $this->dispatch_queue[ $queue_item_id ] = $args;
    }

    public function maybe_dispatch_new_queue() {
        if ( empty( $this->dispatch_queue ) ) {
            return;
        }

        $queue_id = RandomUtils::gen_unique_id();

        $this->update_queue( $queue_id, $this->dispatch_queue );
        $this->send_async_request();
    }

    protected function send_async_request() {
        $this->schedule_healthcheck_event();

        $url = admin_url( 'admin-ajax.php' );
        $url = add_query_arg(
            [
                'action' => $this->task_name,
                'nonce'  => wp_create_nonce( $this->task_name ),
            ],
            $url
        );

        $async_request = apply_filters(
            'bookster_async_request',
            [
                'url'  => $url,
                'args' => [
                    'timeout'   => 0.01,
                    'blocking'  => false,
                    'cookies'   => $_COOKIE,
                    'sslverify' => apply_filters(
                        'https_local_ssl_verify',
                        false
                    ),
                ],
            ]
        );

        wp_remote_post(
            $async_request['url'],
            $async_request['args'],
        );
    }

    // Health Check Region

    public function run_process_healthcheck() {
        if ( $this->is_process_locked() ) {
            // Background process already running.
            exit;
        }

        if ( $this->is_queue_empty() ) {
            // No item to process.
            $this->clear_healthcheck_event();
            exit;
        }

        $this->execute_tasks();

        exit;
    }

    protected function schedule_healthcheck_event() {
        if ( ! wp_next_scheduled( $this->task_name . '_process_healthcheck' ) ) {
            wp_schedule_event( time() + 5, CronEnum::EVERY_5_MINS, $this->task_name . '_process_healthcheck' );
        }
    }

    protected function clear_healthcheck_event() {
        wp_clear_scheduled_hook( $this->task_name . '_process_healthcheck' );
    }
}
