<?php
namespace Pressidium\Limit_Login_Attempts\Login;

use Pressidium\Limit_Login_Attempts\User_Meta;
use Pressidium\Limit_Login_Attempts\Plugin;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Auth_Cookie {

    /**
     * @var array An array of data for the authentication cookie.
     */
    private $cookie_elements;

    /**
     * @var WP_User|null
     */
    private $user = null;

    /**
     * @var User_Meta|null
     */
    private $meta = null;

    /**
     * Auth_Cookie constructor.
     *
     * @param array $cookie_elements An array of data for the authentication cookie.
     */
    public function __construct( $cookie_elements, $user = null ) {
        $this->cookie_elements = $cookie_elements;
        $this->user            = $user;

        if ( is_null( $this->user ) ) {
            $this->user = $this->get_user_in_cookie();
        }

        if ( ! is_null( $this->user ) ) {
            $meta_key   = Plugin::PREFIX . '_previous_cookie';
            $this->meta = new User_Meta( $this->user->ID, $meta_key );
        }
    }

    /**
     * Retrieve the user based on this auth cookie.
     * 
     * @return WP_User|null A `WP_User` object on success,
     *                      `null` on failure.
     */
    private function get_user_in_cookie() {
        $username = $this->cookie_elements['username'];
        $user     = get_user_by( 'login', $username );

        if ( $user === false ) {
            return null;
        }

        return $user;
    }

    /**
     * Return the username.
     *
     * @return string
     */
    public function get_username() {
        return $this->cookie_elements['username'];
    }

    /**
     * Check whether we have already handled this cookie.
     *
     * @return bool
     */
    public function already_handled() {
        if ( is_null( $this->user ) ) {
            return false;
        }

        $previous_cookie = $this->meta->get();

        if ( $previous_cookie && $previous_cookie == $this->cookie_elements ) {
            // Identical cookies, already handled it
            return true;
        }

        // Store cookie so we won't handle it again
        $this->meta->set( $this->cookie_elements );

        return false;
    }

    /**
     * Clear any stored user meta for this auth cookie.
     *
     * @return bool Whether the meta was successfully removed.
     */
    public function clear_meta() {
        if ( is_null( $this->meta ) ) {
            return false;
        }

        return $this->meta->remove();
    }

}
