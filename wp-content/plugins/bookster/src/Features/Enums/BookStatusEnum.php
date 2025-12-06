<?php
namespace Bookster\Features\Enums;

/**
 * Appointment Book Status Enum
 */
class BookStatusEnum {

    const PENDING  = 'pending';
    const APPROVED = 'approved';
    const CANCELED = 'canceled';

    public static function get_label( $status ) {
        switch ( $status ) {
            case self::APPROVED:
                return __( 'Approved', 'bookster' );
            case self::CANCELED:
                return __( 'Canceled', 'bookster' );
            case self::PENDING:
            default:
                return __( 'Pending Approval', 'bookster' );
        }
    }
}
