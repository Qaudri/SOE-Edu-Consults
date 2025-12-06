<?php
namespace Bookster\Features\Utils;

/**
 * Operation on decimal values
 */
class DecimalOperation {
    const ADD      = 'add';
    const SUBTRACT = 'subtract';
    const MULTIPLY = 'multiply';
    const DIVIDE   = 'divide';

    /** @var string */
    public $operator;
    /** @var Decimal */
    public $operand;

    /**
     * @param string  $operator
     * @param Decimal $operand
     */
    public function __construct( $operator, $operand ) {
        if ( ! in_array( $operator, [ self::ADD, self::SUBTRACT, self::MULTIPLY, self::DIVIDE ], true ) ) {
            throw new \InvalidArgumentException( 'Invalid operator' );
        }
        $this->operator = $operator;
        $this->operand  = $operand;
    }
}
