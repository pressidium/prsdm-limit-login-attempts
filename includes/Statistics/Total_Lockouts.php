<?php
namespace Pressidium\Limit_Login_Attempts\Statistics;

use Pressidium\Limit_Login_Attempts\Standalone\Button;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Total_Lockouts extends Statistic {

    /**
     * Return the option name.
     *
     * @return string
     */
    protected function get_option_name() {
        return 'total_lockouts';
    }

    /**
     * Return the button.
     *
     * @return Button
     */
    protected function get_button() {
        return new Button(
            __( 'Reset Total Lockouts', 'prsdm-limit-login-attempts' ),
            'reset_total_lockouts',
            array( $this, 'reset_total_lockouts' )
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
                '%d lockout since last reset',
                '%d lockouts since last reset',
                $value,
                'prsdm-limit-login-attempts'
            ),
            $value
        );
    }

    /**
     * Reset total lockouts.
     */
    public function reset_total_lockouts() {
        $this->options->set( $this->get_option_name(), 0 );
    }

}
