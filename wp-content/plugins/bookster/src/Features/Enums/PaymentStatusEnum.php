<?php
namespace Bookster\Features\Enums;

/**
 * Appointment Payment Status Enum
 */
class PaymentStatusEnum {

    const UNPAID     = 'unpaid';
    const INCOMPLETE = 'incomplete';
    const COMPLETE   = 'complete';
    const REFUNDED   = 'refunded';

    public static function get_label( $status ) {
        switch ( $status ) {
            case self::UNPAID:
                return __( 'Unpaid', 'bookster' );
            case self::REFUNDED:
                return __( 'Refunded', 'bookster' );
            case self::COMPLETE:
                return __( 'Completed', 'bookster' );
            case self::INCOMPLETE:
            default:
                return __( 'Incomplete', 'bookster' );
        }
    }
}
