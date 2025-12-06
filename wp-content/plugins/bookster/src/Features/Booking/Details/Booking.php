<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;
use Bookster\Features\Booking\Details\BookingItem;

/** Booking Section of Details */
class Booking {

    /** @var BookingItem[] */
    public $items;
    /** @var Decimal */
    public $subtotal;

    /**
     * @param BookingItem[] $items
     * @param Decimal       $subtotal
     */
    public function __construct( $items, $subtotal ) {
        $this->items    = $items;
        $this->subtotal = $subtotal;
    }

    public static function from_json( array $booking_json ): Booking {
        $items = array_map( [ BookingItem::class, 'from_json' ], $booking_json['items'] );
        return new Booking( $items, Decimal::from_number( $booking_json['subtotal'] ) );
    }

    public function to_json(): array {
        return [
            'items'    => array_map(
                function ( BookingItem $item ) {
                    return $item->to_json();
                },
                $this->items
            ),
            'subtotal' => $this->subtotal->to_number(),
        ];
    }

    public function calculate(): Decimal {
        $subtotal = Decimal::zero();
        foreach ( $this->items as $item ) {
            $amount       = $item->calculate();
            $item->amount = $amount;
            $subtotal     = $subtotal->add( $amount );
        }
        $this->subtotal = $subtotal;
        return $this->subtotal;
    }
}
