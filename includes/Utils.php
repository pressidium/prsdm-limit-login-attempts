<?php
namespace Pressidium\Limit_Login_Attempts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Utils {

    /**
     * Check whether the given value is greater than zero.
     *
     * @param integer $value
     *
     * @return bool
     */
    public static function is_greater_than_zero( $value ) {
        return $value > 0;
    }

    /**
     * Convert hours to seconds.
     *
     * @param int $hours
     *
     * @return int
     */
    public static function hours_to_seconds( $hours ) {
        return $hours * HOUR_IN_SECONDS;
    }

    /**
     * Convert seconds to hours.
     *
     * @param int $seconds
     *
     * @return int
     */
    public static function seconds_to_hours( $seconds ) {
        return $seconds / HOUR_IN_SECONDS;
    }

    /**
     * Convert minutes to seconds.
     *
     * @param int $minutes
     *
     * @return int
     */
    public static function minutes_to_seconds( $minutes ) {
        return $minutes * MINUTE_IN_SECONDS;
    }

    /**
     * Convert seconds to minutes.
     *
     * @param int $seconds
     *
     * @return int
     */
    public static function seconds_to_minutes( $seconds ) {
        return $seconds / MINUTE_IN_SECONDS;
    }

    /**
     * Format the given duration (in seconds) as a human-readable duration.
     *
     * @param int $seconds Duration in seconds.
     *
     * @return string 
     */
    public static function format_duration( $seconds ) {
        $hours = floor( self::seconds_to_hours( $seconds ) );
        $seconds = $seconds - self::hours_to_seconds( $hours );

        $minutes = floor( self::seconds_to_minutes( $seconds ) );
        $seconds = $seconds - self::minutes_to_seconds( $minutes );

        $duration = array(
            _n( 'hour', 'hours', $hours, 'prsdm-limit-login-attempts' )       => $hours,
            _n( 'minute', 'minutes', $minutes, 'prsdm-limit-login-attempts' ) => $minutes,
            _n( 'second', 'seconds', $seconds, 'prsdm-limit-login-attempts' ) => $seconds
        );

        $formatted_duration = array();

        foreach ( $duration as $unit => $value ) {
            if ( $value > 0 ) {
                $formatted_duration[] = $value . ' ' . $unit;
            }
        }

        return implode( ', ', $formatted_duration );
    }

    /**
     * Remove the last item of the given array if it's empty.
     *
     * @param array $array
     *
     * @return array
     */
    public static function remove_last_item_if_empty( $array ) {        
        $last_item = end( $array );
        
        if ( empty( $last_item ) ) {
            array_pop( $array );
        }

        return $array;
    }

    /**
     * Prepend the given prefix to all items of the given array.
     *
     * @param string $prefix
     * @param array  $array
     *
     * @return array
     */
    public static function prepend_to_items( $prefix, $array ) {
        return array_map( function( $item ) use ( $prefix ) {
            return $prefix . $item;
        }, $array );
    }

    /**
     * Return the given value if it's set, otherwise return the default one.
     *
     * @param mixed $value
     * @param mixed $default
     *
     * @return mixed
     */
    public static function default_value( &$value, $default ) {
        if ( isset( $value ) ) {
            return $value;
        }

        if ( isset( $default ) ) {
            return $default;
        }

        return null;
    }

}
