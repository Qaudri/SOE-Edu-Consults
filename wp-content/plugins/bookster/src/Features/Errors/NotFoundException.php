<?php
namespace Bookster\Features\Errors;

use Bookster\Features\Errors\Interfaces\ResponsableException;
use Bookster\Features\Errors\Interfaces\LoggableException;
use Bookster\Features\Errors\Interfaces\LoggableExceptionTrait;
use Bookster\Features\Enums\LogLevelEnum;

/**
 * Not Found Exception
 */
class NotFoundException extends \Exception implements ResponsableException, LoggableException {
    use LoggableExceptionTrait;

    public function __construct(
        string $message,
        string $log_object_type,
        int $log_object_id = null,
        $log_level = LogLevelEnum::INFO,
        $log_detail = [],
        $code = 0,
        \Throwable $previous = null
    ) {

        parent::__construct( $message, $code, $previous );

        $this->log_object_type     = $log_object_type;
        $this->log_object_id       = $log_object_id;
        $this->log_level           = $log_level;
        $this->log_message         = $this->getMessage();
        $this->log_detail          = $log_detail;
        $this->log_detail['trace'] = $this->getTrace();
    }

    public function get_response_error(): \WP_Error {
        return new \WP_Error( 'not_found', $this->getMessage(), [ 'status' => 404 ] );
    }
}
