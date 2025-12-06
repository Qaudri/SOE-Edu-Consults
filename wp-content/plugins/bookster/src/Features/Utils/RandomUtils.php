<?php
namespace Bookster\Features\Utils;

/**
 * Random Utils
 */
class RandomUtils {

    /**
     * Generate a random string
     *
     * @param int $length
     * @return string
     */
    public static function gen_random_string( $length = 4 ) {
        $rand_string = bin2hex( random_bytes( $length ) );
        return $rand_string;
    }

    /**
     * Generate a unique id
     *
     * @return string
     */
    public static function gen_unique_id() {
        return self::gen_random_string() . '-' . self::gen_random_string() . '-' . self::gen_random_string() . '-' . self::gen_random_string();
    }
}
