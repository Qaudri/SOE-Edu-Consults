<?php
namespace Bookster\Features\Tasks\Dispatcher;

use Bookster\Features\Enums\CronEnum;

/**
 * Execute the callback in Interval.
 */
class IntervalDispatcher extends BaseDispatcher {

    protected $interval;

    public function __construct( string $task_name, string $interval = CronEnum::DAILY ) {
        $this->task_name = $task_name;
        $this->interval  = $interval;

        $this->schedule_event();
    }

    /**
     * Override the listen method.
     * Interval Dispatcher do not support $args.
     *
     * @param callable $callback
     */
    public function listen( $callback ) {
        add_action(
            $this->task_name . '_callback',
            function () use ( $callback ) {
                // Send empty args to Task callback
                call_user_func( $callback, [] );
            },
            10,
            0
        );
    }

    public function dispatch( $args = [] ) {
        $this->schedule_event();
    }

    protected function schedule_event() {
        if ( ! wp_next_scheduled( $this->task_name . '_callback' ) ) {
            wp_schedule_event( time(), $this->interval, $this->task_name . '_callback' );
        }
    }
}
