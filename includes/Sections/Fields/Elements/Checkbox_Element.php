<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Checkbox_Element extends Element implements Settings_Element_Interface {

    /**
     * Render the element.
     */
    public function render() {
        ?>

        <fieldset>
            <label>
                <input
                    type="checkbox"
                    name="<?php echo esc_attr( $this->name ); ?>"
                    id="<?php echo esc_attr( $this->name ); ?>"
                    value="1"
                    <?php checked( '1', $this->value ); ?>
                />
                <?php echo esc_html( $this->label ); ?>
            </label>
        </fieldset>

        <?php
    }

    /**
     * Sanitize the given option value.
     *
     * @param string $option_value
     *
     * @return bool
     */
    public function sanitize( $option_value ) {
        return boolval( $option_value );
    }

}
