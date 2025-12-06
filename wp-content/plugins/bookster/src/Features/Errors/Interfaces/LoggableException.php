<?php
namespace Bookster\Features\Errors\Interfaces;

use Bookster\Models\LogModel;

/**
 * Loggable Exception
 */
interface LoggableException {

    public function get_log_model(): LogModel;
}
