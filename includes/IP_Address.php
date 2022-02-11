<?php
namespace Pressidium\Limit_Login_Attempts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class IP_Address {

    const DIRECT = 'direct';
    const REVERSE_PROXY = 'reverse_proxy';

    /**
     * @var string IP address.
     */
    private static $address = null;

    /**
     * IP_Address constructor.
     *
     * @param string $connection_type 
     */
    public static function init( $connection_type ) {
        self::$address = self::get_ip( $connection_type );
    }

    /**
     * Check whether the IP address is whitelisted.
     *
     * @return bool
     */
    public static function is_whitelisted() {
        $filter_name = Plugin::PREFIX . '_whitelist_ip';
        return apply_filters( $filter_name, false, self::$address ) === true;
    }

    /**
     * Return either 'REMOTE_ADDR' or 'HTTP_X_FORWARDED_FOR' based on the given connection type.
     *
     * @param string $connection_type Either 'direct' or 'reverse_proxy'.
     *
     * @return string
     */
    private static function map_connection_type( $connection_type ) {
        $type_map = array(
            self::DIRECT        => 'REMOTE_ADDR',
            self::REVERSE_PROXY => 'HTTP_X_FORWARDED_FOR'
        );

        if ( ! isset( $type_map[ $connection_type ] ) ) {
            return null;
        }

        return $type_map[ $connection_type ];
    }

    /**
     * Return the IP address based on the given site connection type.
     *
     * @param string $connection_type
     * @return string
     */
    private static function get_ip( $connection_type ) {
        $key = self::map_connection_type( $connection_type );

        if ( isset( $_SERVER[ $key ] ) ) {
            return $_SERVER[ $key ];
        }

        return '';
    }

    /**
     * Return the direct IP address.
     *
     * @return string
     */
    public static function get_direct() {
        return self::get_ip( self::DIRECT );
    }

    /**
     * Return the reverse proxy IP address.
     *
     * @return string
     */
    public static function get_reverse_proxy() {
        return self::get_ip( self::REVERSE_PROXY );
    }

    /**
     * Make a guess if we are behind a proxy or not.
     *
     * @return bool
     */
    public static function is_behind_proxy() {
        $key = self::map_connection_type( self::REVERSE_PROXY );
        return isset( $_SERVER[ $key ] );
    }

    /**
     * Make a guess about the connection type.
     *
     * @return string
     */
    public static function guess_connection_type() {
        if ( self::is_behind_proxy() ) {
            return self::REVERSE_PROXY;
        }

        return self::DIRECT;
    }

    /**
     * Return the IP address based on the stored 'site_connection' option.
     *
     * @return string
     */
    public static function get_address() {
        // Default to 'direct' connection type, if it hasn't been initialized
        if ( is_null( self::$address ) ) {
            self::$address = self::get_direct();
        }

        return self::$address;
    }

}
