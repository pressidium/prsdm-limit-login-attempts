<?php
namespace Pressidium\Limit_Login_Attempts\Statistics;

use Pressidium\Limit_Login_Attempts\Interfaces\UI;
use Pressidium\Limit_Login_Attempts\Interfaces\HTML;

use Pressidium\Limit_Login_Attempts\Hooks\Hooks_Manager;
use Pressidium\Limit_Login_Attempts\Standalone\Button;
use Pressidium\Limit_Login_Attempts\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Statistic implements UI, HTML {

    /**
     * @var Options An instance of `Options`.
     */
    protected $options;

    /**
     * @var Hooks_Manager An instance of `Hooks_Manager`.
     */
    protected $hooks_manager;

    /**
     * @var mixed Stored value.
     */
    private $value;

    /**
     * Return the option name.
     *
     * @return string
     */
    abstract protected function get_option_name();

    /**
     * Return the button.
     *
     * @return Button|null
     */
    abstract protected function get_button();

    /**
     * Return the statistic message.
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract protected function get_message( $value );

    /**
     * Parse the stored value.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    protected function parse_value( $value ) {
        return $value;
    }

    /**
     * Statistic constructor.
     *
     * @param Options $options
     */
    public function __construct( $options, $hooks_manager ) {
        $this->options       = $options;
        $this->hooks_manager = $hooks_manager;

        $stored_option = $this->options->get( $this->get_option_name() );
        $this->value   = $this->parse_value( $stored_option );
    }

    /**
     * Render the statistic.
     */
    public function render() {
        if ( $this->value === 0 ) {
            _e( 'There are no records.', 'prsdm-limit-login-attempts' );
            return;
        }

        $button = $this->get_button();

        if ( $button instanceof Button ) {
            $this->hooks_manager->register( $button );
            $button->render();
        }

        printf(
            '<p>%s</p>',
            esc_html( $this->get_message( $this->value ) )
        );
    }

    /**
     * Return the HTML to display the statistic.
     *
     * @return string
     */
    public function get_html() {
        ob_start();
        $this->render();
        $html = ob_get_clean();
        return $html;
    }

}
