<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;

/** One Item in Booking Section of Details */
class BookingItem {

    /** @var string */
    public $id;
    /** @var string */
    public $title;
    /** @var int */
    public $quantity;
    /** @var Decimal */
    public $unit_price;
    /** @var Decimal */
    public $amount;

    /**
     * @param string  $id
     * @param string  $title
     * @param int     $quantity
     * @param Decimal $unit_price
     * @param Decimal $amount
     */
    public function __construct( $id, $title, $quantity, $unit_price, $amount ) {
        $this->id         = $id;
        $this->title      = $title;
        $this->quantity   = $quantity;
        $this->unit_price = $unit_price;
        $this->amount     = $amount;
    }

    public static function from_json( array $item_json ): BookingItem {
        return new BookingItem( $item_json['id'], $item_json['title'], $item_json['quantity'], Decimal::from_number( $item_json['unitPrice'] ), Decimal::from_number( $item_json['amount'] ) );
    }

    public function to_json(): array {
        return [
            'id'        => $this->id,
            'title'     => $this->title,
            'quantity'  => $this->quantity,
            'unitPrice' => $this->unit_price->to_number(),
            'amount'    => $this->amount->to_number(),
        ];
    }

    public function calculate(): Decimal {
        $this->amount = $this->unit_price->multiply( Decimal::from_number( $this->quantity ) );
        return $this->amount;
    }
}
