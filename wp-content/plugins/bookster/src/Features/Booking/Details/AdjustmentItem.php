<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;

/** One Item in Adjustment Section of Details */
class AdjustmentItem {

    /** @var string */
    public $id;
    /** @var string */
    public $title;
    /** @var Formula */
    public $formula;
    /** @var Decimal */
    public $amount;

    /**
     * @param string  $id
     * @param string  $title
     * @param Formula $formula
     * @param Decimal $amount
     */
    public function __construct( $id, $title, $formula, $amount ) {
        $this->id      = $id;
        $this->title   = $title;
        $this->formula = $formula;
        $this->amount  = $amount;
    }

    public static function from_json( array $item_json ): AdjustmentItem {
        return new AdjustmentItem( $item_json['id'], $item_json['title'], Formula::from_json( $item_json['formula'] ), Decimal::from_number( $item_json['amount'] ) );
    }

    public function to_json(): array {
        return [
            'id'      => $this->id,
            'title'   => $this->title,
            'formula' => $this->formula->to_json(),
            'amount'  => $this->amount->to_number(),
        ];
    }

    public function calculate( Decimal $subtotal, Decimal $current_amount ): Decimal {
        $this->amount = $this->formula->run_formula( $subtotal, $current_amount );
        return $this->amount;
    }
}
