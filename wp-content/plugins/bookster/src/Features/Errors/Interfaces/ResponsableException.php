<?php
namespace Bookster\Features\Errors\Interfaces;

/**
 * Responsable Exception
 */
interface ResponsableException {

    public function get_response_error(): \WP_Error;
}
