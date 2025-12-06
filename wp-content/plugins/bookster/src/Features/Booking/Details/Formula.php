<?php
namespace Bookster\Features\Booking\Details;

use Bookster\Features\Utils\Decimal;
use Bookster\Features\Utils\DecimalOperation;

/** Booking Calculating Formula */
class Formula {

    const FIXED_FORMULA         = 'fixed';
    const RATE_FORMULA          = 'rate';
    const COMPOUND_RATE_FORMULA = 'compoundRate';

    /** @var string */
    public $key;
    /** @var int|float */
    public $payload;

    /**
     * @param string    $key
     * @param int|float $payload
     */
    public function __construct( $key, $payload ) {
        $this->key     = $key;
        $this->payload = $payload;
    }

    public static function from_json( array $formula_json ): Formula {
        return new Formula( $formula_json[0], $formula_json[1] );
    }

    public function to_json(): array {
        return [ $this->key, $this->payload ];
    }

    /**
     * @param Decimal $subtotal
     * @param Decimal $current_amount
     * @return Decimal
     */
    public function run_formula( Decimal $subtotal, Decimal $current_amount ): Decimal {
        switch ( $this->key ) {
            case self::FIXED_FORMULA:
                return Decimal::from_number( $this->payload );
            case self::RATE_FORMULA:
                return $subtotal->pipe(
                    [
                        new DecimalOperation( DecimalOperation::MULTIPLY, Decimal::from_number( $this->payload, 2 ) ),
                        new DecimalOperation( DecimalOperation::DIVIDE, Decimal::from_number( 100 ) ),
                    ]
                );
            case self::COMPOUND_RATE_FORMULA:
                return $current_amount->pipe(
                    [
                        new DecimalOperation( DecimalOperation::MULTIPLY, Decimal::from_number( $this->payload, 2 ) ),
                        new DecimalOperation( DecimalOperation::DIVIDE, Decimal::from_number( 100 ) ),
                    ]
                );
            default:
                throw new \InvalidArgumentException( 'Invalid formula key' );
        }
    }
}
