<?php
namespace Pressidium\Limit_Login_Attempts\Standalone;

use Pressidium\Limit_Login_Attempts\Hooks\Hooks_Manager;
use Pressidium\Limit_Login_Attempts\Interfaces\UI;
use Pressidium\Limit_Login_Attempts\Interfaces\HTML;

use Pressidium\Limit_Login_Attempts\Tables\Lockout_Logs_Table;
use Pressidium\Limit_Login_Attempts\Options\Options;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Lockout_Logs implements UI, HTML {

    const OPTION_NAME = 'lockout_logs';

    /**
     * @var Options An instance of `Options`.
     */
    private $options;

    /**
     * @var Hooks_Manager An instance of `Hooks_Manager`.
     */
    private $hooks_manager;

    /**
     * Lockout_Logs constructor.
     *
     * @param Options $options An instance of `Options`.
     */
    public function __construct( $options, $hooks_manager ) {
        $this->options       = $options;
        $this->hooks_manager = $hooks_manager;
    }

    /**
     * Display lockout logs.
     */
    public function render() {
        $lockouts_logs = $this->options->get( self::OPTION_NAME );

        if ( empty( $lockouts_logs ) ) {
            printf(
                '<p>%s</p>',
                __( 'There are no records.', 'prsdm-limit-login-attempts' )
            );
            return;
        }

        $table  = new Lockout_Logs_Table( $this->options );
        $button = new Button(
            __( 'Clear Log', 'prsdm-limit-login-attempts' ),
            'clear_log',
            array( $this, 'clear_logs' )
        );

        $this->hooks_manager->register( $button );

        $button->render();
        $table->render();
    }

    /**
     * Return the HTML to display lockout logs.
     *
     * @noinspection PhpUnnecessaryLocalVariableInspection
     *
     * @return string
     */
    public function get_html() {
        ob_start();
        $this->render();
        $html = ob_get_clean();
        return $html;
    }

    /**
     * Whether the 'Log IP' option is enabled.
     *
     * @return bool
     */
    private function option_is_enabled() {
        return $this->options->get( 'notify_on_lockout_log_ip' );
    }

    /**
     * Add a lockout log.
     *
     * @param string $ip_address
     * @param string $username
     */
    public function add_log( $ip_address, $username ) {
        if ( ! self::option_is_enabled() ) {
            return;
        }
        
        $lockouts_logs   = $this->options->get( self::OPTION_NAME );
        $logged_lockouts = 0;

        if ( isset( $lockouts_logs[ $ip_address ][ $username ] ) ) {
            $logged_lockouts = $lockouts_logs[ $ip_address ][ $username ];
        }

        $lockouts_logs[ $ip_address ][ $username ] = $logged_lockouts + 1;
        $this->options->set( self::OPTION_NAME, $lockouts_logs );
    }

    /**
     * Clear all lockout logs.
     *
     * @throws Exception If the lockout logs option couldn't be cleared.
     */
    public function clear_logs() {
        $removed = $this->options->remove( self::OPTION_NAME );

        if ( ! $removed ) {
            /** @noinspection SpellCheckingInspection */
            throw new Exception(
                __( 'Couldn\'t clear lockout logs', 'prsdm-limit-login-attempts' )
            );
        }
    }

}
