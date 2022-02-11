<?php
namespace Pressidium\Limit_Login_Attempts\Notifications;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;

use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Notification implements Actions {

    /**
     * @var Options An instance of `Options`.
     */
    protected $options;

    /**
     * Notification constructor.
     *
     * @param Options $options
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            Plugin::PREFIX . '_just_locked_out' => array( 'init', 10, 4 ),
        );
    }

    /**
     * Return the option name.
     *
     * @return string
     */
    abstract protected function get_option_name();

    /**
     * Send a notification if we should notify the site administrator.
     *
     * @param string   $username          Username or email address.
     * @param int      $number_of_retries Number of retries.
     * @param Lockouts $lockouts          An instance of `Lockouts`.
     * @param string   $lockout_type      Lockout type.
     * 
     * @return bool Whether a notification was sent.
     */
    abstract public function init( $username, $number_of_retries, $lockouts, $lockout_type );

    /**
     * Whether the option is enabled.
     *
     * @return bool
     */
    private function option_is_enabled() {
        return $this->options->get( $this->get_option_name() );
    }

    /**
     * Whether we should send this notification.
     *
     * @return bool
     */
    protected function should_send() {
        if ( ! $this->option_is_enabled() ) {
            return false;
        }

        return true;
    }

}
