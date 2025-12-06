<?php
namespace Bookster\Models\Database\ModelTraits;

/**
 * Model functionality related to casting array.
 * The array then can be used to return as REST API response.
 */
trait ToArrayTrait {

    /**
     * Returns model as array.
     *
     * @return mixed[]
     */
    public function to_array() {
        $output = [];
        foreach ( $this->properties as $property ) {
            if ( null !== $this->$property ) {
                $output[ $property ] = $this->parse_array( $this->$property );
            }
        }
        return $output;
    }

    /**
     * Recursively parse array.
     *
     * @param mixed $value Value to parse.
     * @return mixed
     */
    private function parse_array( $value ) {
        switch ( gettype( $value ) ) {
            case 'object':
                return method_exists( $value, 'to_array' )
                    ? $value->to_array()
                    : (array) $value;
            case 'array':
                $output = [];
                foreach ( $value as $key => $data ) {
                    if ( null !== $data ) {
                        $output[ $key ] = $this->parse_array( $data );
                    }
                }
                return $output;
        }
        return $value;
    }
}
