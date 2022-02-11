<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields\Elements;

use Pressidium\Limit_Login_Attempts\Options\Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Radio_Element extends Element implements Settings_Element_Interface {

    /**
     * @var array Radio values.
     */
    private $values = array();

    /**
     * Render the element.
     */
    public function render() {
        foreach ( $this->values as $current_value => $label ) {
            ?>

            <fieldset>
                <label>
                    <input
                        type="radio"
                        name="<?php echo esc_attr( $this->name ); ?>"
                        id="<?php echo esc_attr( $this->name ); ?>"
                        value="<?php echo esc_attr( $current_value ); ?>"
                        <?php checked( $this->value, $current_value ); ?>
                    />
                    <?php echo esc_html( $label ); ?>
                </label>
            </fieldset>

            <?php
        }
    }

    /**
     * Radio_Field constructor.
     *
     * @param string  $section_id       Section ID.
     * @param Options $options_instance An instance of `Options`.
     * @param array   $properties       Element properties.
     */
    public function __construct( $section_id, $options_instance, $properties = array() ) {
        parent::__construct( $section_id, $options_instance, $properties );

        if ( isset( $properties['values'] ) ) {
            $this->values = $properties['values'];
        }
    }

    /**
     * Sanitize the given option value.
     *
     * @param string $option_value
     *
     * @return string
     */
    public function sanitize( $option_value ) {
        return sanitize_text_field( $option_value );
    }

}
