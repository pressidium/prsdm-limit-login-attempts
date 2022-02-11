<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Number_Element extends Element implements Settings_Element_Interface {

    /**
     * Render the element.
     */
    public function render() {
        ?>

        <fieldset>
            <label>
                <input
                    type="number"
                    name="<?php echo esc_attr( $this->name ); ?>"
                    id="<?php echo esc_attr( $this->name ); ?>"
                    value="<?php echo esc_attr( $this->value ); ?>"
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
     * @return int
     */
    public function sanitize( $option_value ) {
        return intval( $option_value );
    }

}
