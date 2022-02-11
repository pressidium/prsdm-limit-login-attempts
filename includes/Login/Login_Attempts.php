<?php
namespace Pressidium\Limit_Login_Attempts\Login;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;
use Pressidium\Limit_Login_Attempts\Hooks\Filters;

use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\Login\State\Retries;
use Pressidium\Limit_Login_Attempts\IP_Address;
use Pressidium\Limit_Login_Attempts\Plugin;

use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Login_Attempts implements Actions, Filters {

    /**
     * @var Retries
     */
    private $retries;

    /**
     * @var Lockouts
     */
    private $lockouts;

    /**
     * @var Login_Error
     */
    private $login_error;

    /**
     * Login_Attempts constructor.
     *
     * @param Retries     $retries
     * @param Lockouts    $lockouts
     * @param Login_Error $login_error
     */
    public function __construct( $retries, $lockouts, $login_error ) {
        $this->retries     = $retries;
        $this->lockouts    = $lockouts;
        $this->login_error = $login_error;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            'wp_login_failed' => array( 'handle_failed_login' ),
        );
    }

    /**
     * Return the filters to register.
     */
    public function get_filters() {
        return array(
            'wp_authenticate_user' => array( 'handle_login_attempt' ),
        );
    }

    /**
     * Remove no longer valid data if applicable, and update options in the database.
     *
     * @param Retries  $retries
     * @param Lockouts $lockouts
     */
    public function cleanup_and_update_options( $retries, $lockouts ) {
        $retries->cleanup();
        $lockouts->cleanup();

        $retries->save_option();
        $lockouts->save_option();
    }

    /**
     * Whether we should allow this login attempt.
     *
     * Meaning, if this method returns `true`,
     * the login process will continue normally.
     *
     * @return bool
     */
    private function should_allow_to_login() {
        if ( IP_Address::is_whitelisted() ) {
            return true;
        }

        return $this->lockouts->is_currently_locked_out() === false;
    }

    /**
     * Filter whether the given user can be authenticated.
     *
     * @param WP_User|WP_Error $user
     *
     * @return WP_User|WP_Error
     */
    public function handle_login_attempt( $user ) {
        if ( is_wp_error( $user ) || $this->should_allow_to_login() ) {
            return $user;
        }

        $error = new WP_Error();
        $error->add( Login_Error::ERROR_CODE, $this->login_error->get_error_message() );

        return $error;
    }

    /**
     * Handle a failed login.
     *
     * @param string $username Username or email address.
     */
    public function handle_failed_login( $username ) {
        $this->lockouts->username = $username;

        if ( $this->lockouts->is_currently_locked_out() ) {
            return;
        }

        $this->retries->maybe_reset();
        $this->retries->increment();

        if ( ! $this->lockouts->should_get_locked_out() ) {
            $this->cleanup_and_update_options( $this->retries, $this->lockouts );
            return;
        }

        /* Store the number of retries (in case they get reset
         * if this is a long lockout) */
        $number_of_retries = $this->retries->get_number_of_retries();

        $lockout_type = $this->lockouts->lockout();
        $this->cleanup_and_update_options( $this->retries, $this->lockouts );

        /**
		 * Fires when an IP address gets locked out.
		 *
		 * @param string   $username          Username or email address.
         * @param int      $number_of_retries Number of retries.
         * @param Lockouts $lockouts          An instance of `Lockouts`.
         * @param string   $lockout_type      Lockout type.
		 */
        do_action(
            Plugin::PREFIX . '_just_locked_out',
            $username,
            $number_of_retries,
            $this->lockouts,
            $lockout_type
        );
    }

}
