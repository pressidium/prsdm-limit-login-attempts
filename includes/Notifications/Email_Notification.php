<?php
namespace Pressidium\Limit_Login_Attempts\Notifications;

use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\IP_Address;
use Pressidium\Limit_Login_Attempts\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Email_Notification extends Notification {
    
    /**
     * @var string Username or email address.
     */
    private $username;

    /**
     * @var int Number of retries.
     */
    private $number_of_retries;

    /**
     * @var int Number of lockouts.
     */
    private $number_of_lockouts;

    /**
     * @var bool Whether this is a long lockout.
     */
    private $is_long_lockout;

    /**
     * @var int Lockout duration in seconds.
     */
    private $lockout_duration;

    /**
     * Initialize the email notification.
     *
     * @param string   $username          Username or email address.
     * @param int      $number_of_retries Number of retries.
     * @param Lockouts $lockouts          An instance of `Lockouts`.
     * @param string   $lockout_type      Lockout type.
     */
    public function init( $username, $number_of_retries, $lockouts, $lockout_type ) {
        $this->username = $username;

        $this->number_of_retries = $number_of_retries;
        
        $this->number_of_lockouts = $lockouts->get_number_of_lockouts();
        $this->is_long_lockout    = $lockout_type === Lockouts::LONG;
        $this->lockout_duration   = $lockouts->get_lockout_duration( $lockout_type );

        $this->maybe_send();
    }

    /**
     * Build the notification.
     *
     * @return email An instance of `Email`.
     */
    private function build() {
        $duration_formatted = Utils::format_duration( $this->lockout_duration );
        
        $blog_name = get_bloginfo();

        $message = sprintf(
            __( '%d failed login attempts (%d lockout(s)) from IP: %s', 'prsdm-limit-login-attempts' ),
            $this->number_of_retries,
            $this->number_of_lockouts,
            IP_Address::get_address()
        ) . "\r\n\r\n";

        if ( ! empty( $this->username ) ) {
            $message .= sprintf(
                __( 'Last user attempted: %s', 'prsdm-limit-login-attempts' ),
                $this->username
            ) . "\r\n\r\n";
        }

        if ( IP_Address::is_whitelisted() ) {
            $subject = sprintf(
                __( '[%s] Failed login attempts from whitelisted IP', 'prsdm-limit-login-attempts' ),
                $blog_name
            );

            $message .= __( 'IP was NOT blocked because of external whitelist', 'prsdm-limit-login-attempts' );
        } else {
            $subject = sprintf(
                __( '[%s] Too many failed login attempts', 'prsdm-limit-login-attempts' ),
                $blog_name
            );

            $message .= sprintf(
                __( 'IP was blocked for %s', 'prsdm-limit-login-attempts' ),
                $duration_formatted
            );
        }

        return new Email( $subject, $message );
    }

    /**
     * Return the 'Email to admin' option name.
     *
     * @return string
     */
    protected function get_option_name() {
        return 'notify_on_lockout_email_to_admin';
    }

    /**
     * Override the `should_send()` method to determine whether we should send this email notification.
     *
     * @return bool
     */
    protected function should_send() {
        if ( ! parent::should_send() ) {
            return false;
        }

        if ( $this->is_long_lockout ) {
            // Always notify on long lockouts
            return true;
        }

        $allowed_retries    = $this->options->get( 'allowed_retries' );
        $number_of_lockouts = $this->number_of_retries / $allowed_retries;

        $notify_after_that_many_lockouts = $this->options->get( 'notify_after_lockouts' );

        return $number_of_lockouts % $notify_after_that_many_lockouts === 0;
    }

    /**
     * Send an email if we should notify the site administrator.
     *
     * @return bool Whether an email was sent.
     */
    public function maybe_send() {
        if ( $this->should_send() ) {
            $email = $this->build();
            return $email->send_to_admin();
        }

        return false;
    }

}
