<?php
namespace Pressidium\Limit_Login_Attempts\Standalone;

use Pressidium\Limit_Login_Attempts\Hooks\Actions;
use Pressidium\Limit_Login_Attempts\Interfaces\UI;

use Exception;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Button implements Actions, UI {

    /**
     * @var string Button label.
     */
    private $label;

    /**
     * @var string Action name.
     */
    private $action;

    /**
     * @var callable Handler function.
     */
    private $handler;

    /**
     * @var array Button CSS classes.
     */
    private $css_classes;

    /**
     * Button constructor.
     *
     * @param string   $label       Button label.
     * @param string   $action      Action name.
     * @param callable $handler     Action handler function.
     * @param array    $css_classes (Optional) Button CSS classes.
     */
    public function __construct( $label, $action, $handler, $css_classes = array() ) {
        $this->label       = $label;
        $this->action      = $action;
        $this->handler     = $handler;
        $this->css_classes = $css_classes;
    }

    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions() {
        return array(
            "admin_post_{$this->action}" => array( 'action_handler' ),
        );
    }

    /**
     * Render the HTML of the button.
     */
    public function render() {
        $css_classes = array_unique( array_merge(
            array( 'button' ),
            $this->css_classes
        ) );

        $url = add_query_arg(
            array(
                'nonce'  => wp_create_nonce( $this->action ),
                'action' => $this->action
            ),
            admin_url( 'admin-post.php' )
        );
        ?>

        <a href="<?php echo esc_url( $url ); ?>"
           class="<?php echo esc_attr( implode( ' ', $css_classes ) ); ?>">
            <?php echo esc_html( $this->label ); ?>
        </a>

        <?php
    }

    /**
     * Handle the request for this button's action.
     */
    public function action_handler() {
        if ( ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], $this->action ) ) {
            /** @noinspection SpellCheckingInspection */
            die( 'Couldn\'t pass security check.' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            die( 'You don\'t have permission to perform this action.' );
        }

        $handler = $this->handler;

        if ( ! is_callable( $handler ) ) {
            die( 'There is no handler set for this action.' );
        }

        $successfully_ran = true;

        try {
            $handler();
        } catch ( Exception $e ) {
            $successfully_ran = false;
        }

        $url = add_query_arg(
            array( 'action_result' => $successfully_ran ? 'success' : 'failure' ),
            wp_get_referer()
        );

        wp_redirect( $url );
    }

}
