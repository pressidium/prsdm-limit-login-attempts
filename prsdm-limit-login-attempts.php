<?php
/**
 * Plugin Name: PRSDM Limit Login Attempts
 * Plugin URI: https://pressidium.com
 * Description: Limit rate of login attempts, including by way of cookies, for each IP.
 * Author: Pressidium
 * Author URI: https://pressidium.com
 * Text Domain: prsdm-limit-login-attempts
 * License: GPL-2.0+
 * Version: 1.0.0
 *
 * Based on Limit Login Attempts (http://devel.kostdoktorn.se/limit-login-attempts)
 * by Johan Eenfeldt (http://devel.kostdoktorn.se)
 *
 * Licenced under the GNU GPL:
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

namespace Pressidium\Limit_Login_Attempts;

use Pressidium\Limit_Login_Attempts\Options\Options;

use Pressidium\Limit_Login_Attempts\Options\WP_Options;
use Pressidium\Limit_Login_Attempts\Hooks\Hooks_Manager;
use Pressidium\Limit_Login_Attempts\Pages\Settings_Page;
use Pressidium\Limit_Login_Attempts\Login\Login_Attempts;
use Pressidium\Limit_Login_Attempts\Login\Login_Error;
use Pressidium\Limit_Login_Attempts\Login\Cookie_Login;
use Pressidium\Limit_Login_Attempts\Login\State\Retries;
use Pressidium\Limit_Login_Attempts\Login\State\Lockouts;
use Pressidium\Limit_Login_Attempts\Standalone\Lockout_Logs;
use Pressidium\Limit_Login_Attempts\Notifications\Email_Notification;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {

    const PREFIX = 'prsdm_limit_login_attempts';

    /**
     * @var Options An instance of the `Options` class.
     */
    public $options;

    /**
     * @var Hooks_Manager An instance of the `Hooks_Manager` class.
     */
    public $hooks_manager;

    /**
     * Plugin constructor.
     */
    public function __construct() {
        $this->require_files();
        $this->setup_constants();
        
        add_action( 'plugins_loaded', array( $this, 'init' ) );
    }

    /**
     * Require files.
     */
    private function require_files() {
        require_once __DIR__ . '/autoload.php';

        $autoloader = new Autoloader();
        $autoloader->register();
    }

    /**
     * Setup constants.
     */
    private function setup_constants() {
        if ( ! defined( __NAMESPACE__ . '\PLUGIN_URL' ) ) {
            define( __NAMESPACE__ . '\PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        }

        if ( ! defined( __NAMESPACE__ . '\VERSION' ) ) {
            define( __NAMESPACE__ . '\VERSION', '1.0.0' );
        }
    }

    /**
     * Initialize the plugin once activated plugins have been loaded.
     */
    public function init() {
        $this->options = new WP_Options();
        $this->hooks_manager = new Hooks_Manager();

        IP_Address::init( $this->options->get( 'site_connection' ) );

        $lockout_logs = new Lockout_Logs( $this->options, $this->hooks_manager );

        $retries  = new Retries( $this->options );
        $lockouts = new Lockouts( $this->options, $retries, $lockout_logs );

        $settings_page = new Settings_Page( $this->options, $this->hooks_manager, $lockout_logs );
        $this->hooks_manager->register( $settings_page );

        $login_error    = new Login_Error( $this->options, $retries, $lockouts );
        $login_attempts = new Login_Attempts( $retries, $lockouts, $login_error );
        $this->hooks_manager->register( $login_error );
        $this->hooks_manager->register( $login_attempts );

        $should_handle_cookie_login = $this->options->get( 'handle_cookie_login' ) === 'yes';

        if ( $should_handle_cookie_login ) {
            $cookie_login = new Cookie_Login( $login_attempts, $lockouts );
            $this->hooks_manager->register( $cookie_login );
        }

        $email_notification = new Email_Notification( $this->options );
        $this->hooks_manager->register( $email_notification );
    }

}

new Plugin();
