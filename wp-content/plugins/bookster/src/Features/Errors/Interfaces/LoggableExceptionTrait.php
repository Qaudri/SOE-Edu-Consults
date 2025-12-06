<?php
namespace Bookster\Features\Errors\Interfaces;

use Bookster\Models\LogModel;

/**
 * Loggable Exception Trait
 */
trait LoggableExceptionTrait {

    /** @var string ObjectTypeEnum */
    protected $log_object_type;
    /** @var int */
    protected $log_object_id;
    /** @var string LogLevelEnum */
    protected $log_level;
    /** @var string LogLevelEnum */
    protected $log_message;
    /** @var array */
    protected $log_detail;

    public function get_log_model(): LogModel {
        $log                  = new LogModel();
        $log->log_object_id   = $this->log_object_id;
        $log->log_object_type = $this->log_object_type;
        $log->log_level       = $this->log_level;
        $log->log_message     = $this->log_message;
        $log->log_detail      = $this->log_detail;

        return $log;
    }
}
