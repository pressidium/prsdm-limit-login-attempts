<?php
namespace Pressidium\Limit_Login_Attempts\Login;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;

use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\IP_Address;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Cookie_Login implements Actions {

    /**
     * @var array
     */
    const AUTH_COOKIES = array(
        AUTH_COOKIE,
        SECURE_AUTH_COOKIE,
        LOGGED_IN_COOKIE
    );

    /**
     * @var Login_Attempts
     */
    private $login_attempts;

    /**
     * Cookies constructor.
     *
     * @param Login_Attempts $login_attempts
     * @param Lockouts       $lockouts
     */
    public function __construct( $login_attempts, $lockouts ) {
        $this->login_attempts = $login_attempts;

        if ( ! IP_Address::is_whitelisted() && $lockouts->is_currently_locked_out() ) {
            $this->clear();
        }
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            'auth_cookie_bad_username' => array( 'handle_bad_username' ),
            'auth_cookie_bad_hash'     => array( 'handle_bad_hash' ),
            'auth_cookie_valid'        => array( 'handle_valid', 10, 2 ),
        );
    }

    /**
     * Handle failed cookie login due to bad username.
     */
    public function handle_bad_username( $cookie_elements ) {
        $this->clear();

        $username = $cookie_elements['username'];
        $this->login_attempts->handle_failed_login( $username );
    }

    /**
     * Handle failed cookie login due to bad hash.
     */
    public function handle_bad_hash( $cookie_elements ) {
        $this->clear();

        $auth_cookie = new Auth_Cookie( $cookie_elements );

        if ( ! $auth_cookie->already_handled() ) {
            $username = $auth_cookie->get_username();
            $this->login_attempts->handle_failed_login( $username );
        }
    }

    /**
     * Handle successful cookie login.
     */
    public function handle_valid( $cookie_elements, $user ) {
        $auth_cookie = new Auth_Cookie( $cookie_elements, $user );
        $auth_cookie->clear_meta();
    }

    /**
     * Check whether we have already cleared auth cookies.
     *
     * @return bool
     */
    private function already_cleared() {
        foreach ( self::AUTH_COOKIES as $cookie ) {
            if ( ! empty( $_COOKIE[ $cookie ] ) ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear auth cookie (for this session too).
     */
    public function clear() {
        if ( $this->already_cleared() ) {
            return;
        }

        foreach ( self::AUTH_COOKIES as $cookie ) {
            if ( ! empty( $_COOKIE[ $cookie ] ) ) {
                $_COOKIE[ $cookie ] = '';
            }
        }

        wp_clear_auth_cookie();
    }

}
