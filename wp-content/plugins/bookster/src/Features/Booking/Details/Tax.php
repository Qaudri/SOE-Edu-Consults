<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;
use Bookster\Features\Booking\Details\TaxItem;

/** Tax Section of Details */
class Tax {

    /** @var TaxItem[] */
    public $items;
    /** @var Decimal */
    public $total;

    /**
     * @param TaxItem[] $items
     * @param Decimal   $total
     */
    public function __construct( $items, $total ) {
        $this->items = $items;
        $this->total = $total;
    }

    public static function from_json( array $tax_json ): Tax {
        $items = array_map( [ TaxItem::class, 'from_json' ], $tax_json['items'] );
        return new Tax( $items, Decimal::from_number( $tax_json['total'] ) );
    }

    public function to_json(): array {
        return [
            'items' => array_map(
                function ( TaxItem $item ) {
                    return $item->to_json();
                },
                $this->items
            ),
            'total' => $this->total->to_number(),
        ];
    }

    public function calculate( Decimal $adjustment_subtotal ): Decimal {
        $current_amount = $adjustment_subtotal;
        foreach ( $this->items as $item ) {
            $amount             = $item->calculate( $adjustment_subtotal, $current_amount );
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
        $this->total = $current_amount;
        return $this->total;
    }
}
