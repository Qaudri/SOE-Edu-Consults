<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;
use Bookster\Features\Booking\Details\AdjustmentItem;

/** Adjustment Section of Details */
class Adjustment {

    /** @var AdjustmentItem[] */
    public $items;
    /** @var Decimal */
    public $subtotal;

    /**
     * @param AdjustmentItem[] $items
     * @param Decimal          $subtotal
     */
    public function __construct( $items, $subtotal ) {
        $this->items    = $items;
        $this->subtotal = $subtotal;
    }

    public static function from_json( array $adjustment_json ): Adjustment {
        $items = array_map( [ AdjustmentItem::class, 'from_json' ], $adjustment_json['items'] );
        return new Adjustment( $items, Decimal::from_number( $adjustment_json['subtotal'] ) );
    }

    public function to_json(): array {
        return [
            'items'    => array_map(
                function ( AdjustmentItem $item ) {
                    return $item->to_json();
                },
                $this->items
            ),
            'subtotal' => $this->subtotal->to_number(),
        ];
    }

    public function calculate( Decimal $booking_subtotal ): Decimal {
        $current_amount = $booking_subtotal;
        foreach ( $this->items as $item ) {
            $amount             = $item->calculate( $booking_subtotal, $current_amount );
            $new_current_amount = $current_amount->add( $amount );

            if ( $new_current_amount->is_negative() ) {
                // Subtotal cannot be negative, Only adjust to ZERO
                $amount         = $current_amount->multiply( -1 );
                $item->amount   = $amount;
                $current_amount = Decimal::zero();
            } else {
                $item->amount   = $amount;
                $current_amount = $new_current_amount;
            }
        }
        $this->subtotal = $current_amount;
        return $this->subtotal;
    }
}
