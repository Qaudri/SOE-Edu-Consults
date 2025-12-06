<?php
namespace Bookster\Features\Booking;

use Bookster\Features\Utils\Decimal;
use Bookster\Features\Booking\Details\Booking;
use Bookster\Features\Booking\Details\Adjustment;
use Bookster\Features\Booking\Details\Tax;

/** Booking Details */
class Details {

    /** @var Booking */
    public $booking;
    /** @var Adjustment */
    public $adjustment;
    /** @var Tax */
    public $tax;

    public function __construct( Booking $booking, Adjustment $adjustment, Tax $tax ) {
        $this->booking    = $booking;
        $this->adjustment = $adjustment;
        $this->tax        = $tax;
    }

    public static function from_json( array $details_json ): Details {
        $booking    = Booking::from_json( $details_json['booking'] );
        $adjustment = Adjustment::from_json( $details_json['adjustment'] );
        $tax        = Tax::from_json( $details_json['tax'] );
        return new Details( $booking, $adjustment, $tax );
    }

    public function to_json(): array {
        return [
            'booking'    => $this->booking->to_json(),
            'adjustment' => $this->adjustment->to_json(),
            'tax'        => $this->tax->to_json(),
        ];
    }

    public function calculate(): Decimal {
        $booking_subtotal    = $this->booking->calculate();
        $adjustment_subtotal = $this->adjustment->calculate( $booking_subtotal );
        return $this->tax->calculate( $adjustment_subtotal );
    }

    public function clean() {
        $adjustment_items = [];
        foreach ( $this->adjustment->items as $item ) {
            if ( ! $item->amount->is_zero() ) {
                $adjustment_items[] = $item;
            }
        }
        $this->adjustment->items = $adjustment_items;

        $tax_items = [];
        foreach ( $this->tax->items as $item ) {
            if ( ! $item->amount->is_zero() ) {
                $tax_items[] = $item;
            }
        }
        $this->tax->items = $tax_items;
    }
}
