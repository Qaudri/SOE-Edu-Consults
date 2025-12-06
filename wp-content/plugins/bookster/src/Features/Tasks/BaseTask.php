<?php
namespace Bookster\Features\Tasks;

use Bookster\Features\Tasks\Dispatcher\BaseDispatcher;

/**
 * Abstract class for a task.
 */
abstract class BaseTask {

    /** Unique Task Name */
    protected $task_name = 'bookster_task_name';
    /** @var BaseDispatcher */
    protected $dispatcher;

    protected function init_hooks() {
        if ( $this->dispatcher ) {
            return;
        }

        $dispatcher = apply_filters( $this->task_name . '_dispatcher', null );
        if ( null === $dispatcher ) {
            $dispatcher = $this->create_dispatcher();
        }
        $this->dispatcher = $dispatcher;
        $this->dispatcher->listen( [ $this, 'task_callback' ] );
    }

    /**
     * Callback for the task.
     *
     * @param array $args
     */
    abstract public function task_callback( $args );

    /**
     * Create the dispatcher for the task.
     *
     * @return BaseDispatcher
     */
    abstract protected function create_dispatcher();

    /**
     * Dispatch the task. Let the dispatcher decide when the task is executed.
     *
     * @param array $args
     */
    public function dispatch( $args = [] ) {
        $this->dispatcher->dispatch( $args );
    }
}
