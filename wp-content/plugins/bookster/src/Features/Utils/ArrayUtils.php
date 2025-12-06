<?php
namespace Bookster\Features\Utils;

/**
 * Array Utils
 */
class ArrayUtils {

    /**
     * Pick keys from array
     *
     * @param array $arr
     * @param array $keys
     * @return array
     */
    public static function pick( $arr, $keys ) {
        $result = [];
        foreach ( $keys as $key ) {
            if ( array_key_exists( $key, $arr ) ) {
                $result[ $key ] = $arr[ $key ];
            }
        }
        return $result;
    }

    public static function has_any_key( $arr, $keys ) {
        foreach ( $keys as $key ) {
            if ( array_key_exists( $key, $arr ) ) {
                return true;
            }
        }
        return false;
    }

    public static function has_all_keys( $arr, $keys ) {
        foreach ( $keys as $key ) {
            if ( ! array_key_exists( $key, $arr ) ) {
                return false;
            }
        }
        return true;
    }
}
