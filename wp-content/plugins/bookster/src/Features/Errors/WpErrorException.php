<?php
namespace Bookster\Features\Errors;

use Bookster\Features\Errors\Interfaces\ResponsableException;

/**
 * WP_Error Carrier Exception
 */
class WpErrorException extends \Exception implements ResponsableException {

    /** @var \WP_Error */
    private $inner_error;

    public function __construct( \WP_Error $inner_error ) {

        $this->inner_error = $inner_error;
    }

    public function get_response_error(): \WP_Error {
        return $this->inner_error;
    }
}
