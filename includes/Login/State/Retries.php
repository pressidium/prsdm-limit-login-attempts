<?php
namespace Pressidium\Limit_Login_Attempts\Login\State;

use Pressidium\Limit_Login_Attempts\IP_Address;
use Pressidium\Limit_Login_Attempts\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Retries {

    /**
     * @var string IP address.
     */
    private $ip_address;

    /**
     * @var Options The instance of `Options`.
     */
    private $options;

    /**
     * @var array Retries option stored in the database.
     */
    private $stored_option;

    /**
     * @var int Timestamp.
     */
    private $timestamp;

    /**
     * @var int Number of retries.
     */
    private $number_of_retries;

    /**
     * Retries constructor.
     *
     * @param Options $options
     */
    public function __construct( $options ) {
        $this->options       = $options;
        $this->stored_option = $this->options->get( 'retries' );

        $this->ip_address        = IP_Address::get_address();
        $this->timestamp         = null;
        $this->number_of_retries = 0;

        if ( $this->has_stored_option() ) {
            $ip_stored_data = $this->stored_option[ $this->ip_address ];

            $this->timestamp         = $ip_stored_data['timestamp'];
            $this->number_of_retries = $ip_stored_data['retries'];
        }
    }

    /**
     * Check whether there are stored retries for the given IP address.
     *
     * @return bool
     */
    public function has_stored_option_for_ip( $ip_address ) {
        return isset( $this->stored_option[ $ip_address ] );
    }

    /**
     * Check whether there are retries for the currently processing IP address.
     *
     * @return bool
     */
    private function has_stored_option() {
        return $this->has_stored_option_for_ip( $this->ip_address );
    }

    /**
     * Increment retries for the currently processing IP address.
     */
    public function increment() {
        $this->number_of_retries++;
        $this->timestamp = time() + $this->options->get( 'hours_until_retries_reset' );
    }

    /**
     * Reset retries.
     */
    public function reset() {
        $this->number_of_retries = 0;
        $this->timestamp         = null;
    }

    /**
     * Reset retries if no longer valid.
     */
    public function maybe_reset() {
        if ( ! is_null( $this->timestamp ) && time() >= $this->timestamp ) {
            $this->reset();
        }
    }

    /**
     * Remove no longer valid retries.
     */
    public function cleanup() {
        $now = time();

        foreach ( $this->stored_option as $ip_address => $retries ) {
            if ( $retries['timestamp'] < $now ) {
                unset( $this->stored_option[ $ip_address ] );
            }
        }
    }

    /**
     * Add, update, or remove the data for this IP address before saving.
     */
    private function prepare_stored_option() {
        $this->stored_option[ $this->ip_address ] = array(
            'timestamp' => $this->timestamp,
            'retries'   => $this->number_of_retries
        );
        
        if ( $this->number_of_retries === 0 ) {
            unset( $this->stored_option[ $this->ip_address ] );
        }
    }

    /**
     * Update the option value in the database.
     */
    public function save_option() {
        $this->prepare_stored_option();
        $this->options->set( 'retries', $this->stored_option );
    }

    /**
     * Return the number of retries.
     *
     * @return int
     */
    public function get_number_of_retries() {
        return $this->number_of_retries;
    }

}
