<?php
namespace Bookster\Features\Tasks\Dispatcher;

/**
 * BaseDispatcher
 */
abstract class BaseDispatcher {

    protected $task_name = 'bookster_task_name';

    /**
     * Listen to the task when plugin loaded.
     *
     * @param callable $callback
     */
    public function listen( $callback ) {
        add_action( $this->task_name . '_callback', $callback, 10, 1 );
    }

    /**
     * Dispatch the task. Let the concrete dispatcher decide when the task is executed.
     *
     * @param array $args
     */
    abstract public function dispatch( $args = [] );
}
