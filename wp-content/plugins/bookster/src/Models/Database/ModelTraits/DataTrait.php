<?php
namespace Bookster\Models\Database\ModelTraits;

/**
 * Model functionality related to data.
 */
trait DataTrait {

    /**
     * Holds the model data.
     *
     * @var mixed[]
     */
    protected $attributes = [];
    /**
     * Holds the list of attributes or properties that should be part of the data casted to array.
     * Those not listed in this array will remain as hidden / transient.
     *
     * @var string[]
     */
    protected $properties = [];

    /**
     * Getter property.
     * Returns value as reference, reference to aliases based on functions will not work.
     *
     * @param string $property
     */
    public function &__get( $property ) {
        $value = null;
        // Protected properties
        if ( property_exists( $this, $property ) ) {
            return $this->$property;
        }
        // Normal data handled in attributes
        if ( isset( $this->attributes[ $property ] ) ) {
            return $this->attributes[ $property ];
        }
        // Aliases
        if ( method_exists( $this, 'get' . ucfirst( $property ) . 'Alias' ) ) {
            $value = call_user_func_array( [ &$this, 'get' . ucfirst( $property ) . 'Alias' ], [] );
        }
        return $value;
    }
    /**
     * Setter property values.
     *
     * @param string $property
     * @param mixed  $value
     */
    public function __set( $property, $value ) {
        if ( property_exists( $this, $property ) ) {
            // Protected properties
            $this->$property = $value;
        } elseif ( method_exists( $this, 'set' . ucfirst( $property ) . 'Alias' ) ) {
            // Aliases
            call_user_func_array( [ &$this, 'set' . ucfirst( $property ) . 'Alias' ], [ $value ] );
        } else {
            // Normal attribute
            $this->attributes[ $property ] = $value;
        }
    }
}
