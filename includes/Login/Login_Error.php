<?php
namespace Pressidium\Limit_Login_Attempts\Login;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;
use Pressidium\Limit_Login_Attempts\Hooks\Filters;

use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\Login\State\Retries;
use Pressidium\Limit_Login_Attempts\IP_Address;
use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\Utils;

use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Login_Error implements Actions, Filters {

    const ERROR_CODE = 'too_many_retries';

    /**
     * @var bool Whether the login error was displayed.
     */
    private $error_added = false;

    /**
     * @var bool Whether the username/password are non-empty.
     */
    private $non_empty_credentials = false;

    /**
     * @var array Error messages.
     */
    private $errors;

    /**
     * @var Options An instance of `Options`.
     */
    private $options;

    /**
     * @var Retries
     */
    private $retries;

    /**
     * @var Lockouts
     */
    private $lockouts;

    /**
     * Login_Error constructor.
     *
     * @param Options  $options
     * @param Retries  $retries
     * @param Lockouts $lockouts
     */
    public function __construct( $options, $retries, $lockouts ) {
        $this->options  = $options;
        $this->retries  = $retries;
        $this->lockouts = $lockouts;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            'login_head' => array( 'add_errors' ),
        );
    }

    /**
     * Return the filters to register.
     *
     * @return array
     */
    public function get_filters() {
        return array(
            'authenticate'      => array( 'track_credentials', 10, 3 ),
            'shake_error_codes' => array( 'add_error_code' ),
            'login_errors'      => array( 'format_error_messages' ),
        );
    }

    /**
     * Add an error code to the login error codes.
     *
     * @param array $error_codes
     *
     * @return array
     */
    public function add_error_code( $error_codes ) {
        $error_codes[] = self::ERROR_CODE;
        return $error_codes;
    }

    /**
     * Check whether we should display errors on this page.
     *
     * @return bool
     */
    private function should_display_errors_on_this_page() {
        if ( isset( $_GET['key'] ) ) {
            // Reset password
            return false;
        }

        if ( ! isset( $_REQUEST['action'] ) ) {
            return true;
        }

        /** @noinspection SpellCheckingInspection */
        $ignore_actions = array(
            'lostpassword',
            'retrievepassword',
            'resetpass',
            'rp',
            'register'
        );

        return ! in_array( $_REQUEST['action'], $ignore_actions );
    }

    /**
     * Check whether we should display errors.
     *
     * @return bool
     */
    private function should_display_errors() {
        if ( IP_Address::is_whitelisted() ) {
            return false;
        }

        return $this->should_display_errors_on_this_page();
    }

    /**
     * Build an informative error message about the current lockout.
     */
    private function build_lockout_error_message() {
        $message = sprintf(
            '<strong>%s</strong>: %s',
            __( 'ERROR', 'prsdm-limit-login-attempts' ),
            __( 'Too many failed login attempts.', 'prsdm-limit-login-attempts' )
        ) . ' ';

        $timestamp = $this->lockouts->get_timestamp();

        if ( is_null( $timestamp ) ) {
            $message .= __( 'Please try again later.', 'prsdm-limit-login-attempts' );
            return $message;
        }

        $duration           = $timestamp - time();
        $duration_formatted = Utils::format_duration( $duration );

        $message .= sprintf(
            /* translators: %s is the duration until the lockout expires. */
            __( 'Please try again in %s', 'prsdm-limit-login-attempts' ),
            $duration_formatted
        );

        return $message;
    }

    /**
     * Calculate the remaining retries.
     *
     * @return int
     */
    private function calculate_remaining_retries() {
        $retries           = $this->retries->get_number_of_retries();
        $allowed_retries   = $this->options->get( 'allowed_retries' );
        $retries_remaining = $allowed_retries - ( $retries % $allowed_retries );

        return max( $retries_remaining, 0 );
    }

    /**
     * Build an informative error message about the current login attempt.
     *
     * @return string
     */
    private function build_retry_error_message() {
        if ( $this->retries->get_number_of_retries() === 0 ) {
            // No retries at all
            return '';
        }

        $retries_remaining = $this->calculate_remaining_retries();

        return sprintf(
            /* translators: %s is the number of attempts remaining. */
            _n(
                '%s attempt remaining',
                '%s attempts remaining',
                $retries_remaining,
                'prsdm-limit-login-attempts'
            ),
            sprintf( '<strong>%d</strong>', $retries_remaining )
        );
    }

    /**
     * Return an informative error message about the current lockout.
     *
     * @return string
     */
    public function get_error_message() {
        if ( ! $this->should_display_errors() ) {
            return '';
        }

        if ( $this->lockouts->is_currently_locked_out() ) {
            return $this->build_lockout_error_message();
        }
        
        return $this->build_retry_error_message();
    }

    /**
     * Keep track of empty username/password to filter errors correctly.
     *
     * @param null|WP_User|WP_Error $user
     * @param string $username
     * @param string $password
     *
     * @return null|WP_User|WP_Error
     */
    public function track_credentials( $user, $username, $password ) {
        $this->non_empty_credentials = ! empty( $username ) && ! empty( $password );
        return $user;
    }

    /**
     * Return the given error string as an array of errors.
     *
     * @param string $error_messages
     *
     * @return array
     */
    private function get_errors_as_array( $error_messages ) {
        $errors = explode( "<br />\n", $error_messages );
        $errors = array_map( 'trim', $errors );
        return Utils::remove_last_item_if_empty( $errors );
    }

    /**
     * Return the given errors as an error string.
     *
     * @return string
     */
    private function get_errors_with_linebreaks() {
        $linebreak        = "<br />\n";
        $double_linebreak = $linebreak . $linebreak;
        return implode( $double_linebreak, $this->errors ) . $linebreak;
    }

    /**
     * Check whether WordPress is about to display an error on the login page.
     *
     * @return bool
     */
    private function there_are_wp_errors() {
        $number_of_our_errors = $this->error_added ? 1 : 0;
        return count( $this->errors ) > $number_of_our_errors;
    }

    /**
     * Replace WordPress login errors (if any).
     *
     * WordPress displays error messages like 'Unknown username'
     * or 'Unknown email address' leaking information regarding
     * user account names. Replace those error messages (if any)
     * with a more generic 'Incorrect username or password'.
     */
    private function maybe_replace_wp_errors() {
        if ( ! $this->there_are_wp_errors() ) {
            return;
        }

        $this->errors = array();
        
        $this->errors[] = sprintf(
            '<strong>%s</strong>: %s',
            __( 'ERROR', 'prsdm-limit-login-attempts' ),
            __( 'Incorrect username or password.', 'prsdm-limit-login-attempts' )
        );

        if ( $this->error_added ) {
            $this->errors[] = $this->get_error_message();
        }
    }

    /**
     * Check whether we have been already locked out.
     *
     * @return bool
     */
    private function already_locked_out() {
        return $this->lockouts->is_currently_locked_out() && ! Lockouts::$just_locked_out;
    }

    /**
     * Format error messages before displaying them.
     *
     * @param string $error_messages
     * @return string
     */
    public function format_error_messages( $error_messages ) {
        if ( ! $this->should_display_errors() ) {
            return $error_messages;
        }
        
        if ( $this->already_locked_out() ) {
            return $this->get_error_message();
        }

        if ( ! $this->non_empty_credentials ) {
            return $error_messages;
        }

        $this->errors = $this->get_errors_as_array( $error_messages );
        $this->maybe_replace_wp_errors();

        return $this->get_errors_with_linebreaks();
    }

    /**
     * Add errors to be displayed on the login page.
     */
    public function add_errors() {
        global $error;

        if ( ! $this->should_display_errors() || $this->error_added ) {
            return;
        }

        $error .= $this->get_error_message();

        $this->error_added = true;
    }

}
