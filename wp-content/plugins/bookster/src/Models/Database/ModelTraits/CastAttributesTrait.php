<?php
namespace Bookster\Models\Database\ModelTraits;

/**
 * Model functionality related to casting attributes from db datatype to php type.
 */
trait CastAttributesTrait {

    /** @var string[] */
    protected static $integer_attributes = [];

    /** @var string[] */
    protected static $boolean_attributes = [];

    /**
     * Save as json string, parse as php stdClass
     *
     * @var string[]
     */
    protected static $json_attributes = [];

    /**
     * Save as json string, parse as php array
     *
     * @var string[]
     */
    protected static $jsonarr_attributes = [];

    /**
     * Cast attributes from db datatype to php type.
     */
    public function cast_attributes() {
        foreach ( static::$integer_attributes as $attribute ) {
            if ( isset( $this->attributes[ $attribute ] ) ) {
                $this->attributes[ $attribute ] = (int) $this->attributes[ $attribute ];
            }
        }
        foreach ( static::$boolean_attributes as $attribute ) {
            if ( isset( $this->attributes[ $attribute ] ) ) {
                $this->attributes[ $attribute ] = (bool) $this->attributes[ $attribute ];
            }
        }
        foreach ( static::$json_attributes as $attribute ) {
            if ( isset( $this->attributes[ $attribute ] ) && is_string( $this->attributes[ $attribute ] ) ) {
                $this->attributes[ $attribute ] = json_decode( $this->attributes[ $attribute ], false );
            }
        }
        foreach ( static::$jsonarr_attributes as $attribute ) {
            if ( isset( $this->attributes[ $attribute ] ) && is_string( $this->attributes[ $attribute ] ) ) {
                $this->attributes[ $attribute ] = json_decode( $this->attributes[ $attribute ], true );
            }
        }
    }

    /**
     * Cast some object/array column to text before saving.
     *
     * @param  mixed[] $data
     * @return mixed[]
     */
    public static function prepare_saved_data( array $data ) {
        foreach ( static::$json_attributes as $attribute ) {
            if ( isset( $data[ $attribute ] ) ) {
                $data[ $attribute ] = wp_json_encode( $data[ $attribute ] );
            }
        }
        foreach ( static::$jsonarr_attributes as $attribute ) {
            if ( isset( $data[ $attribute ] ) ) {
                $data[ $attribute ] = wp_json_encode( $data[ $attribute ] );
            }
        }

        return $data;
    }

    /**
     * Cast query params from string to correct type.
     *
     * @param  string[] $args
     * @return mixed[]
     */
    public static function parse_query_params( array $args ) {
        foreach ( static::$integer_attributes as $attribute ) {
            if ( isset( $args[ $attribute ] ) ) {
                $args[ $attribute ] = (int) $args[ $attribute ];
            }
        }
        foreach ( static::$boolean_attributes as $attribute ) {
            if ( isset( $args[ $attribute ] ) ) {
                $args[ $attribute ] = 'false' === $args[ $attribute ] ? false : true;
            }
        }
        return $args;
    }
}
