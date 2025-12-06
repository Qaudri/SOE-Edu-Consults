<?php
namespace Bookster\Features\Errors;

use Bookster\Features\Errors\Interfaces\ResponsableException;

/**
 * Invalid Argument Exception
 */
class InvalidArgumentException extends \Exception implements ResponsableException {

    public function get_response_error(): \WP_Error {
        return new \WP_Error( 'invalid_argument', $this->getMessage(), [ 'status' => 400 ] );
    }
}
