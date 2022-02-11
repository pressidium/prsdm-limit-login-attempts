<?php
namespace Pressidium\Limit_Login_Attempts\Standalone;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Notice {

    const SUCCESS = 'success';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';

    const PINNED = 'pinned';
    const DISMISSIBLE = 'dismissible';

    /**
     * @var string Message to display.
     */
    private $message;

    /**
     * @var string Notice type ('success', 'info', 'warning', or 'error').
     */
    private $notice_type;

    /**
     * @var string Pin type (either 'pinned' or 'dismissible').
     */
    private $pin_type;

    /**
     * Admin_Notice constructor.
     *
     * @param string $message     Message to display.
     * @param string $notice_type Notice type ('success', 'info', 'warning', or 'error').
     * @param string $pin_type    Pin type (either 'pinned' or 'dismissible').
     */
    public function __construct( $message, $notice_type = 'success', $pin_type = 'dismissible' ) {
        $this->message     = $message;
        $this->notice_type = $notice_type;
        $this->pin_type    = $pin_type;
    }

    /**
     * Check whether this notice is dismissible.
     *
     * @return bool
     */
    private function is_dismissible() {
        return $this->pin_type === self::DISMISSIBLE;
    }

    /**
     * Return the CSS classes of this notice.
     *
     * @return string
     */
    private function get_css_classes() {
        $css_classes = array(
            'notice',
            sprintf( 'notice-%s', $this->notice_type )
        );

        if ( $this->is_dismissible() ) {
            $css_classes[] = 'is-dismissible';
        }

        return implode( ' ', array_unique( $css_classes ) );
    }

    /**
     * Render this notice.
     */
    public function render() {
        printf(
            '<div class="%s"><p>%s</p></div>',
            esc_attr( $this->get_css_classes() ),
            $this->message
        );
    }

}
