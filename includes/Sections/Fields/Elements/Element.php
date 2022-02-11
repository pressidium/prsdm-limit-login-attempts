<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields\Elements;

use Pressidium\Limit_Login_Attempts\Interfaces\UI;
use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Element implements UI {

    const NUMBER_ELEMENT = 'Number_Element';
    const RADIO_ELEMENT = 'Radio_Element';
    const CHECKBOX_ELEMENT = 'Checkbox_Element';
    const CUSTOM_ELEMENT = 'Custom_Element';

    /**
     * @var int Number of elements instantiated.
     */
    private static $number_of_elements = 0;

    /**
     * @var array Element label.
     */
    protected $label;

    /**
     * @var array Element name.
     */
    protected $name;

    /**
     * @var mixed Element value.
     */
    protected $value;

    /**
     * @var string Element option name.
     */
    private $option_name;

    /**
     * @var callable|null Validation function.
     */
    private $validate;

    /**
     * @var callable|null Pre-write function.
     */
    private $pre_write;

    /**
     * Element constructor.
     *
     * @param string  $section_id       Section ID.
     * @param Options $options_instance An instance of `Options`.
     * @param array   $properties       Element properties.
     */
    public function __construct( $section_id, $options_instance, $properties = array() ) {
        self::$number_of_elements++;

        if ( $this instanceof Settings_Element_Interface ) {
            $properties = wp_parse_args(
                $properties,
                array(
                    'label'     => sprintf(
                        /* translators: %s is the unique s/n of the element. */
                        __( 'Element #%s', 'prsdm-limit-login-attempts' ),
                        self::$number_of_elements
                    ),
                    'name'      => 'element_' . self::$number_of_elements,
                    'validate'  => null,
                    'pre_write' => null,
                    'post_read' => null
                )
            );

            $this->label       = $properties['label'];
            $this->option_name = $properties['name'];
            $this->name        = sprintf( '%s_%s[%s]', Plugin::PREFIX, $section_id, $this->option_name );
            $this->validate    = $properties['validate'];
            $this->pre_write   = $properties['pre_write'];
            $this->value       = $options_instance->get( $this->option_name );

            if ( is_callable( $properties['post_read'] ) ) {
                $this->value = $properties['post_read']( $this->value );
            }
        }
    }

    /**
     * Return the element's option name.
     *
     * @return string
     */
    public function get_option_name() {
        return $this->option_name;
    }

    /**
     * Return the validation function.
     *
     * @return callable|null
     */
    public function get_validate() {
        return $this->validate;
    }

    /**
     * Return the current value of this element.
     *
     * @return mixed
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Return the `pre_write()` function.
     *
     * @return callable|null
     */
    public function get_pre_write() {
        return $this->pre_write;
    }

}
