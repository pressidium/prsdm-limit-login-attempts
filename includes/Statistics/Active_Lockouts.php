<?php
namespace Pressidium\Limit_Login_Attempts\Statistics;

use Pressidium\Limit_Login_Attempts\Standalone\Button;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Active_Lockouts extends Statistic {

    /**
     * Return the option name.
     *
     * @return string
     */
    protected function get_option_name() {
        return 'lockouts';
    }

    /**
     * Return the button.
     *
     * @return Button
     */
    protected function get_button() {
        return new Button(
            __( 'Remove Active Lockouts', 'prsdm-limit-login-attempts' ),
            'remove_active_lockouts',
            array( $this, 'remove_active_lockouts' )
        );
    }
    
    /**
     * Return the statistic message.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function get_message( $value ) {
        return sprintf(
            /* translators: %d is the number of lockouts. */
            _n(
                '%d IP is currently blocked from trying to log in',
                '%d IPs are currently blocked from trying to log in',
                $value,
                'prsdm-limit-login-attempts'
            ),
            $value
        );
    }

    /**
     * Override the `parse_value()` method.
     *
     * @param array $lockouts
     *
     * @return int
     */
    protected function parse_value( $lockouts ) {
        $now = time();

        $number_of_active_lockouts = 0;

        foreach ( $lockouts as $lockout ) {
            if ( $now < $lockout['timestamp'] ) {
                $number_of_active_lockouts++;
            }
        }

        return $number_of_active_lockouts;
    }

    /**
     * Remove active lockouts.
     */
    public function remove_active_lockouts() {
        $this->options->set( $this->get_option_name(), array() );
    }

}
