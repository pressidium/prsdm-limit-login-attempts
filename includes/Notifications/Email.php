<?php
namespace Pressidium\Limit_Login_Attempts\Notifications;

use Pressidium\Limit_Login_Attempts\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Email {

    /**
     * @var string Email subject.
     */
    private $subject;

    /**
     * @var string Email body.
     */
    private $body;

    /**
     * @var string Sender email address.
     */
    private $sender_email = null;

    /**
     * @var array Email headers.
     */
    private $headers = array();

    /**
     * Email constructor.
     *
     * @param string $subject
     * @param string $body
     */
    public function __construct( $subject, $body ) {
        $this->subject = $subject;
        $this->body    = $body;
    }

    /**
     * Set the sender email address.
     *
     * @param string $email_address
     * @return Email
     */
    public function from( $email_address ) {
        $this->sender_email = $email_address;
        return $this;
    }

    /**
     * Add the given value(s) to the `headers` property.
     *
     * @param string       $key
     * @param array|string $values
     */
    private function add_headers( $key, $values ) {
        $prefix = $key . ': ';

        if ( is_array( $values ) ) {
            $this->headers = array_merge(
                $this->headers,
                Utils::prepend_to_items( $prefix, $values )
            );
        } else {
            $this->headers[] = $prefix . $values;
        }
    }

    /**
     * Set one or multiple email address(es) to 'Cc' this email.
     *
     * @param array|string $email_address
     * @return Email
     */
    public function cc( $email_address ) {
        $this->add_headers( 'Cc', $email_address );
        return $this;
    }

    /**
     * Set one or multiple email address(es) to 'Bcc' this email.
     *
     * @param array|string $email_address
     * @return Email
     */
    public function bcc( $email_address ) {
        $this->add_headers( 'Bcc', $email_address );
        return $this;
    }

    /**
     * Default the sender email to the admin email (if not already set).
     */
    private function set_default_sender_address() {
        if ( is_null( $this->sender_email ) ) {
            // Default to the admin email address
            $this->from( get_bloginfo( 'admin_email' ) );
        }
    }

    /**
     * Return the headers array (including the 'From' header).
     *
     * @return array
     */
    private function get_headers() {
        $from_header = 'From: ' . $this->sender_email;
        $headers     = array_merge( $this->headers, array( $from_header ) );

        return array_unique( $headers );
    }

    /**
     * Send this email to the given recipient.
     *
     * @param array|string $to Recipient email address(es).
     * @return bool Whether the email was sent successfully.
     */
    public function send( $to ) {
        $this->set_default_sender_address();
        
        return wp_mail( $to, $this->subject, $this->body, $this->get_headers() );
    }

    /**
     * Send this email to the site administrator.
     *
     * @return bool Whether the email was sent successfully.
     */
    public function send_to_admin() {
        $admin_email = get_bloginfo( 'admin_email' );
        return $this->send( $admin_email );
    }

}
