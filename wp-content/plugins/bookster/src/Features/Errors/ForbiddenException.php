<?php
namespace Bookster\Features\Errors;

use Bookster\Features\Errors\Interfaces\ResponsableException;

/**
 * Forbidden Exception
 */
class ForbiddenException extends \Exception implements ResponsableException {

    public function get_response_error(): \WP_Error {
        return new \WP_Error( 'forbidden', $this->getMessage(), [ 'status' => is_user_logged_in() ? 403 : 401 ] );
    }
}
