<?php
namespace Pressidium\Limit_Login_Attempts\Pages;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;

use Pressidium\Limit_Login_Attempts\Standalone\Admin_Notice;
use Pressidium\Limit_Login_Attempts\Sections\Settings_Section;
use Pressidium\Limit_Login_Attempts\Sections\Section;
use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\Plugin;

use const Pressidium\Limit_Login_Attempts\PLUGIN_URL;
use const Pressidium\Limit_Login_Attempts\VERSION;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Admin_Page implements Actions {

    /**
     * @var Section[] Page section objects.
     */
    private $sections = array();

    /**
     * @var Options An instance of `Options`.
     */
    protected $options;

    /**
     * Admin_Page constructor.
     *
     * @param Options $options An instance of `Options`.
     */
    public function __construct( $options ) {
        $this->options = $options;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            'admin_menu'            => array( 'add_page' ),
            'admin_init'            => array( 'register_sections' ),
            'admin_notices'         => array( 'display_admin_notices' ),
            'admin_enqueue_scripts' => array( 'enqueue_stylesheets' ),
        );
    }

    /**
     * Render this admin page.
     */
    public function render() {
        ?>

        <div class="wrap">
            <form action="options.php" method="post">
                <h1><?php echo esc_html( $this->get_page_title() ); ?></h1>
                <?php
                settings_fields( $this->get_slug() );
                do_settings_sections( $this->get_slug() );
                submit_button( __( 'Change Options', 'prsdm-limit-login-attempts' ) );
                ?>
            </form>
        </div>

        <?php
    }

    /**
     * Display an admin notice with the given message and type.
     *
     * @param string $message Message to display.
     * @param string $type    Notice type ('success', 'error', or 'warning').
     */
    protected function render_admin_notice( $message, $type ) {
        $notice = new Admin_Notice( $message, $type );
        $notice->render();
    }

    /**
     * Display admin notices.
     */
    public function display_admin_notices() {
        settings_errors();

        if ( isset( $_GET['action_result'] ) ) {
            if ( $_GET['action_result'] === 'success' ) {
                $this->render_admin_notice(
                    esc_html( __( 'Action was performed successfully.', 'prsdm-limit-login-attempts' ) ),
                    Admin_Notice::SUCCESS
                );
            } else {
                /** @noinspection SpellCheckingInspection */
                $this->render_admin_notice(
                    esc_html( __( 'An error occurred. Couldn\'t perform action.', 'prsdm-limit-login-attempts' ) ),
                    Admin_Notice::ERROR
                );
            }
        }
    }

    /**
     * Enqueue stylesheets for all admin pages.
     *
     * @param string $hook_suffix The current admin page.
     */
    public function enqueue_stylesheets( $hook_suffix ) {
        if ( $hook_suffix !== 'toplevel_page_' . $this->get_slug() ) {
            return;
        }

        wp_enqueue_style(
            'admin_page',
            PLUGIN_URL . 'assets/css/admin-page.css',
            array(),
            VERSION
        );
    }

    /**
     * Add this page as a top-level menu page.
     */
    public function add_page() {
        add_menu_page(
            $this->get_page_title(),    // page_title
            $this->get_menu_title(),    // menu_title
            $this->get_capability(),    // capability
            $this->get_slug(),          // menu_slug
            array( $this, 'render' ),   // callback function
            $this->get_icon_url(),      // icon_url
            $this->get_position()       // position
        );
    }

    /**
     * Return the menu title.
     *
     * @return string
     */
    abstract protected function get_menu_title();

    /**
     * Return the page title.
     *
     * @return string
     */
    abstract protected function get_page_title();

    /**
     * Return the capability required for this menu to be displayed to the user.
     *
     * @return string
     */
    protected function get_capability() {
        return 'manage_options';
    }

    /**
     * Return page slug.
     *
     * @return string
     */
    abstract protected function get_slug();

    /**
     * Return the URL to the icon to be used for this menu.
     *
     * @return string
     */
    protected function get_icon_url() {
        return '';
    }

    /**
     * Return the position in the menu order this item should appear.
     *
     * @return int|null
     */
    protected function get_position() {
        return null;
    }

    /**
     * Register sections.
     *
     * Used to add new sections to an admin page.
     *
     * @return void
     */
    abstract public function register_sections();

    /**
     * Create and register a new settings section object.
     *
     * @param string $section_id Section ID.
     * @param array  $properties Section properties.
     *
     * @return Settings_Section
     */
    protected function register_section( $section_id, $properties = array() ) {
        $section = new Settings_Section( $section_id, $this->get_slug(), $this->options, $properties );

        $this->sections[] = $section;

        register_setting(
            $this->get_slug(),
            Plugin::PREFIX . '_' . $section_id,
            array( 'sanitize_callback' => array( $section, 'sanitize' ) )
        );

        return $section;
    }

    /**
     * Create and register a new section object.
     *
     * @param string $section_id Section ID.
     * @param array  $properties Section properties.
     *
     * @return Section
     */
    protected function register_presentation_section( $section_id, $properties = array() ) {
        $section = new Section( $section_id, $this->get_slug(), $this->options, $properties );
        $this->sections[] = $section;

        return $section;
    }

}
