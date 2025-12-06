<?php
namespace Bookster\Features\Tasks\Dispatcher;

/**
 * Execute the callback Immediately.
 */
class ImmediateDispatcher extends BaseDispatcher {

    public function __construct( string $task_name ) {
        $this->task_name = $task_name;
    }

    public function dispatch( $args = [] ) {
        do_action( $this->task_name . '_callback', $args );
    }
}
