<?php
namespace Bookster\Features\Utils;

use Bookster\Services\SettingsService;

/**
 * Make sure Price and Amount are calculated with the same precision as the frontend.
 */
class Decimal {

    /** @return int */
    private static function get_currency_number_of_decimals() {
        return SettingsService::get_instance()->get_public_data()['paymentSettings']['number_of_decimals'];
    }

    /** @var int save converted int value from decimal */
    private $int_value;
    /** @var int Number of digit after Decimals Point */
    private $number_of_decimals;

    /**
     * @param int|float $value
     * @param int       $number_of_decimals
     */
    private function __construct( $value, $number_of_decimals ) {
        $this->number_of_decimals = $number_of_decimals;
        if ( is_int( $value ) ) {
            $this->int_value = $value;
        } else {
            $this->int_value = (int) round( $value );
        }
    }

    /**
     * @param int|float $value
     * @param int       $number_of_decimals
     */
    public static function from_number( $value, $number_of_decimals = null ) {
        if ( is_null( $number_of_decimals ) ) {
            $number_of_decimals = self::get_currency_number_of_decimals();
        }
        return new Decimal( $value * pow( 10, $number_of_decimals ), $number_of_decimals );
    }

    /**
     * @param string $value
     * @param int    $number_of_decimals
     */
    public static function from_string( string $value, $number_of_decimals = null ) {
        return self::from_number( floatval( $value ), $number_of_decimals );
    }

    public function clone() {
        return new Decimal( $this->int_value );
    }

    public function to_number() {
        return $this->int_value / $this->get_integer_multiplier();
    }

    public function to_string() {
        return number_format( $this->to_number(), $this->number_of_decimals, '.', '' );
    }

    public function get_integer_multiplier() {
        return pow( 10, $this->number_of_decimals );
    }

    /**
     * @param Decimal|int|float|string $operand
     */
    public function add( $operand ) {
        $_operand = self::parse_operand( $operand );
        return self::from_number( $this->to_number() + $_operand );
    }

    /**
     * @param Decimal|int|float|string $operand
     */
    public function subtract( $operand ) {
        $_operand = self::parse_operand( $operand );
        return self::from_number( $this->to_number() - $_operand );
    }

    /**
     * @param Decimal|int|float|string $operand
     */
    public function multiply( $operand ) {
        $_operand = self::parse_operand( $operand );
        return self::from_number( $this->to_number() * $_operand );
    }

    /**
     * @param Decimal|int|float|string $operand
     */
    public function divide( $operand ) {
        $_operand = self::parse_operand( $operand );
        return self::from_number( $this->to_number() / $_operand );
    }

    public function equals( Decimal $operand ) {
        if ( $this->number_of_decimals === $operand->number_of_decimals ) {
            return $this->int_value === $operand->int_value;
        }
        return $this->int_value * $operand->get_integer_multiplier() === $operand->int_value * $this->get_integer_multiplier();
    }

    public function greater_than( Decimal $operand ) {
        if ( $this->number_of_decimals === $operand->number_of_decimals ) {
            return $this->int_value > $operand->int_value;
        }
        return $this->int_value * $operand->get_integer_multiplier() > $operand->int_value * $this->get_integer_multiplier();
    }

    public function greater_than_or_equals( Decimal $operand ) {
        if ( $this->number_of_decimals === $operand->number_of_decimals ) {
            return $this->int_value >= $operand->int_value;
        }
        return $this->int_value * $operand->get_integer_multiplier() >= $operand->int_value * $this->get_integer_multiplier();
    }

    public function less_than( Decimal $operand ) {
        if ( $this->number_of_decimals === $operand->number_of_decimals ) {
            return $this->int_value < $operand->int_value;
        }
        return $this->int_value * $operand->get_integer_multiplier() < $operand->int_value * $this->get_integer_multiplier();
    }

    public function less_than_or_equals( Decimal $operand ) {
        if ( $this->number_of_decimals === $operand->number_of_decimals ) {
            return $this->int_value <= $operand->int_value;
        }
        return $this->int_value * $operand->get_integer_multiplier() <= $operand->int_value * $this->get_integer_multiplier();
    }

    public function is_positive() {
        return $this->int_value > 0;
    }

    public function is_negative() {
        return $this->int_value < 0;
    }

    public function is_zero() {
        return 0 === $this->int_value;
    }

    /**
     * Do multiple operations then round to decimal places once.
     *
     * @param DecimalOperation[] $operations
     *
     * @return Decimal
     */
    public function pipe( $operations ) {
        $result = $this->to_number();
        foreach ( $operations as $operation ) {
            $result = self::do_operation_to_number( $result, $operation );
        }
        return self::from_number( $result );
    }

    /**
     * @param int|float        $input
     * @param DecimalOperation $operation
     *
     * @return int|float
     */
    private static function do_operation_to_number( $input, $operation ) {
        switch ( $operation->operator ) {
            case DecimalOperation::ADD:
                return $input + $operation->operand->to_number();
            case DecimalOperation::SUBTRACT:
                return $input - $operation->operand->to_number();
            case DecimalOperation::MULTIPLY:
                return $input * $operation->operand->to_number();
            case DecimalOperation::DIVIDE:
                return $input / $operation->operand->to_number();
            default:
                throw new \InvalidArgumentException( 'Invalid operator' );
        }
    }

    /**
     * @param Decimal|int|float|string $operand
     *
     * @return int|float
     */
    private static function parse_operand( $operand ) {
        if ( $operand instanceof Decimal ) {
            return $operand->to_number();
        } elseif ( is_int( $operand ) || is_float( $operand ) ) {
            return $operand;
        } elseif ( is_string( $operand ) ) {
            return self::from_string( $operand )->to_number();
        } else {
            throw new \InvalidArgumentException( 'Invalid operand' );
        }
    }

    private static $zero;
    public static function zero() {
        if ( ! self::$zero ) {
            self::$zero = self::from_number( 0 );
        }
        return self::$zero;
    }
}
