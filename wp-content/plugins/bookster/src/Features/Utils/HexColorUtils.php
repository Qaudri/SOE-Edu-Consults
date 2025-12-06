<?php
namespace Bookster\Features\Utils;

/**
 * HexColor Utils
 */
class HexColorUtils {

    /**
     * Adjust brightness of hex color
     *
     * @param string $hex
     * @param int    $percent -100 ~ 100, -100 is black, 0 is no change, 100 is white.
     *
     * @return string
     */
    public static function adjust_brightness( $hex, $percent = 40 ) {
        list( $r, $g, $b ) = self::parse( $hex );

        $r = max( 0, min( 255, $r + ( $percent * 255 / 100 ) ) );
        $g = max( 0, min( 255, $g + ( $percent * 255 / 100 ) ) );
        $b = max( 0, min( 255, $b + ( $percent * 255 / 100 ) ) );

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Check if hex color is light. To guess the text color.
     *
     * @param string $hex
     *
     * @return bool
     */
    public static function is_light( $hex ) {
        list( $r, $g, $b ) = self::parse( $hex );
        $brightness        = ( $r * 299 + $g * 587 + $b * 114 ) / 1000;
        return $brightness > 155;
    }

    public static function get_foreground_color( $hex ) {
        return self::is_light( $hex ) ? '#1F2937' : '#FFFFFF';
    }

    /**
     * Parse hex color to RGB
     *
     * @param string $hex
     *
     * @return array
     */
    private static function parse( $hex ) {
        $hex = str_replace( '#', '', $hex );
        if ( strlen( $hex ) === 3 ) {
            $r = hexdec( substr( $hex, 0, 1 ) . substr( $hex, 0, 1 ) );
            $g = hexdec( substr( $hex, 1, 1 ) . substr( $hex, 1, 1 ) );
            $b = hexdec( substr( $hex, 2, 1 ) . substr( $hex, 2, 1 ) );
        } else {
            $r = hexdec( substr( $hex, 0, 2 ) );
            $g = hexdec( substr( $hex, 2, 2 ) );
            $b = hexdec( substr( $hex, 4, 2 ) );
        }
        return [ $r, $g, $b ];
    }

    /**
     * Convert RGB to HSB
     *
     * @param array $rgb
     *
     * @return array
     */
    private static function to_hsb( $rgb ) {
        list($r, $g, $b) = $rgb;

        $r = $r / 255;
        $g = $g / 255;
        $b = $b / 255;

        $max    = max( $r, $g, $b );
        $min    = min( $r, $g, $b );
        $delta  = $max - $min;
        $bright = $max;
        $sat    = 0 !== $max ? $delta / $max : 0;

        if ( 0 === $sat ) {
            $hue = 0;
        } else {
            if ( $r === $max ) {
                $hue = ( $g - $b ) / $delta;
            } elseif ( $g === $max ) {
                $hue = 2 + ( $b - $r ) / $delta;
            } else {
                $hue = 4 + ( $r - $g ) / $delta;
            }
            $hue = $hue * 60;
            if ( $hue < 0 ) {
                $hue = $hue + 360;
            }
        }

        return [ $hue, $sat, $bright ];
    }
}
