<?php
namespace Pressidium\Limit_Login_Attempts\Login\State;

use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\IP_Address;
use Pressidium\Limit_Login_Attempts\Standalone\Lockout_Logs;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lockouts {

    /**
     * @var string Representing a lockout of normal duration.
     */
    const NORMAL = 'normal';

    /**
     * @var string Representing a lockout of long duration.
     */
    const LONG = 'long';

    /**
     * @var string Username or email address.
     */
    public $username = null;

    /**
     * @var string IP address.
     */
    private $ip_address;

    /**
     * @var Options The instance of `Options`.
     */
    private $options;

    /**
     * @var Retries An instance of `Retries`.
     */
    private $retries;

    /**
     * @var Lockout_Logs Lockout logs.
     */
    private $lockout_logs;

    /**
     * @var array Lockouts option stored in the database.
     */
    private $stored_option;

    /**
     * @var int Timestamp.
     */
    private $timestamp;

    /**
     * @var int Number of lockouts.
     */
    private $number_of_lockouts;

    /**
     * @var bool Whether we just got locked out.
     */
    public static $just_locked_out = false;

    /**
     * Lockouts constructor.
     *
     * @param Options      $options
     * @param Retries      $retries
     * @param Lockout_Logs $lockout_logs
     */
    public function __construct( $options, $retries, $lockout_logs ) {
        $this->options      = $options;
        $this->retries      = $retries;
        $this->lockout_logs = $lockout_logs;

        $this->stored_option = $this->options->get( 'lockouts' );
        $this->ip_address    = IP_Address::get_address();

        $this->timestamp          = null;
        $this->number_of_lockouts = 0;

        if ( $this->has_stored_option() ) {
            $ip_stored_data = $this->stored_option[ $this->ip_address ];

            $this->timestamp          = $ip_stored_data['timestamp'];
            $this->number_of_lockouts = $ip_stored_data['lockouts'];
        }
    }

    /**
     * Check whether there are retries for this IP address.
     *
     * @return bool
     */
    private function has_stored_option() {
        return isset( $this->stored_option[ $this->ip_address ] );
    }

    /**
     * Check whether this IP address is currently locked out.
     *
     * @return bool
     */
    public function is_currently_locked_out() {
        if ( is_null( $this->timestamp ) ) {
            return false;
        }

        return time() < $this->timestamp;
    }

    /**
     * Check whether this IP address should get locked out.
     *
     * @return bool
     */
    public function should_get_locked_out() {
        $retries         = $this->retries->get_number_of_retries();
        $allowed_retries = $this->options->get( 'allowed_retries' );

        return $retries % $allowed_retries === 0;
    }

    /**
     * Check whether this lockout should be a long one.
     *
     * @return bool
     */
    private function should_get_long_lockout() {
        $retries = $this->retries->get_number_of_retries();
        
        $max_retries_per_lockout = $this->options->get( 'allowed_retries' );
        $max_lockouts            = $this->options->get( 'max_lockouts' );

        $max_retries_total = $max_retries_per_lockout * $max_lockouts;

        return $retries >= $max_retries_total;
    }

    /**
     * Return the lockout type (either `self::NORMAL` or `self::LONG`).
     *
     * @return string
     */
    private function get_lockout_type() {
        if ( $this->should_get_long_lockout() ) {
            return self::LONG;
        }

        return self::NORMAL;
    }

    /**
     * Return the lockout duration.
     *
     * @param string $lockout_type
     *
     * @return int
     */
    public function get_lockout_duration( $lockout_type ) {
        if ( $lockout_type === self::LONG ) {
            return $this->options->get( 'long_lockout_time' );
        }

        return $this->options->get( 'normal_lockout_time' );
    }

    /**
     * Reset lockouts.
     */
    public function reset() {
        $this->number_of_lockouts = 0;
        $this->timestamp          = null;
    }

    /**
     * Increment lockouts.
     */
    private function increment() {
        $this->number_of_lockouts++;

        $total_lockouts = $this->options->get( 'total_lockouts' );
        $total_lockouts++;
        $this->options->set( 'total_lockouts', $total_lockouts );
    }

    /**
     * Lockout.
     *
     * @return string Lockout type.
     */
    public function lockout() {
        $lockout_type = $this->get_lockout_type();

        if ( ! IP_Address::is_whitelisted() ) {
            self::$just_locked_out = true;

            $this->timestamp = time() + $this->get_lockout_duration( $lockout_type );
        }

        if ( $lockout_type === self::LONG ) {
            $this->retries->reset();
        }

        $this->increment();
        $this->lockout_logs->add_log( $this->ip_address, $this->username );

        return $lockout_type;
    }

    /**
     * Check whether the lockout with the given IP address and timestamp is no longer valid.
     *
     * @param string $ip_address The IP address to check.
     * @param int    $timestamp  The timestamp to check.
     *
     * @return bool
     */
    private function is_no_longer_valid_lockout( $ip_address, $timestamp ) {
        $now = time();
        return $timestamp < $now && ! $this->retries->has_stored_option_for_ip( $ip_address );
    }

    /**
     * Remove no longer valid lockouts.
     */
    private function remove_no_longer_valid_lockouts() {
        foreach ( $this->stored_option as $ip_address => $lockout ) {
            if ( $this->is_no_longer_valid_lockout( $ip_address, $lockout['timestamp'] ) ) {
                unset( $this->stored_option[ $ip_address ] );
            }
        }
    }

    /**
     * Reset the lockout for the currently processing IP, if no longer valid.
     */
    private function maybe_reset_lockout_for_this_ip() {
        if ( $this->is_no_longer_valid_lockout( $this->ip_address, $this->timestamp ) ) {
            $this->reset();
        }
    }

    /**
     * Cleanup lockouts.
     *
     * Remove no longer valid lockouts and
     * reset the lockout for this IP if no longer valid.
     */
    public function cleanup() {
        $this->remove_no_longer_valid_lockouts();
        $this->maybe_reset_lockout_for_this_ip();
    }

    /**
     * Add, update, or remove the data for this IP address before saving.
     */
    private function prepare_stored_option() {
        $this->stored_option[ $this->ip_address ] = array(
            'timestamp' => $this->timestamp,
            'lockouts'  => $this->number_of_lockouts
        );
        
        if ( $this->number_of_lockouts === 0 ) {
            unset( $this->stored_option[ $this->ip_address ] );
        }
    }

    /**
     * Update the option value in the database.
     */
    public function save_option() {
        $this->prepare_stored_option();
        $this->options->set( 'lockouts', $this->stored_option );
    }

    /**
     * Return the number of lockouts.
     *
     * @return int
     */
    public function get_number_of_lockouts() {
        return $this->number_of_lockouts;
    }

    /**
     * Return the timestamp of the lockout expiration.
     *
     * @return int
     */
    public function get_timestamp() {
        return $this->timestamp;
    }

}
