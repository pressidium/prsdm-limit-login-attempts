<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields;

use Pressidium\Limit_Login_Attempts\Options\Options;
use Pressidium\Limit_Login_Attempts\Sections\Fields\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Field {

    /**
     * @var int Number of fields instantiated.
     */
    private static $number_of_fields = 0;

    /**
     * @var Element[] Field elements.
     */
    private $elements = array();

    /**
     * @var Options An instance of `Options`.
     */
    private $options;

    /**
     * @var string ID of the section this field belongs to.
     */
    private $section_id;

    /**
     * @var array Field description.
     */
    private $description;

    /**
     * Render the field.
     */
    public function render() {
        if ( ! empty( $this->description ) ) {
            printf(
                '<p class="description">%s</p>',
                esc_html( $this->description )
            );
        }

        foreach ( $this->elements as $key => $element ) {
            $element->render();
        }
    }

    /**
     * Field constructor.
     *
     * @param string  $section_id       Section ID.
     * @param string  $page             Slug-name of settings page.
     * @param Options $options_instance An instance of `Options`.
     * @param array   $properties       Field properties.
     */
    public function __construct( $section_id, $page, $options_instance, $properties = array() ) {
        self::$number_of_fields++;

        $properties = wp_parse_args(
            $properties,
            array(
                'label'       => sprintf(
                    /* translators: %s is the unique s/n of the field. */
                    __( 'Field #%s', 'prsdm-limit-login-attempts' ),
                    self::$number_of_fields
                ),
                'id'          => 'field_' . self::$number_of_fields,
                'description' => '',
            )
        );

        $this->options = $options_instance;

        $this->section_id  = $section_id;
        $this->description = $properties['description'];

        add_settings_field(
            $properties['id'],
            $properties['label'],
            array( $this, 'render' ),
            $page,
            $section_id
        );
    }

    /**
     * Create and add a new element object to this field.
     *
     * @param string $element_type
     * @param array  $properties
     */
    public function add_element( $element_type, $properties ) {
        $element_type = __NAMESPACE__ . '\\Elements\\' . $element_type;

        if ( ! class_exists( $element_type ) ) {
            return;
        }

        $element = new $element_type( $this->section_id, $this->options, $properties );

        if ( ! ( $element instanceof Element ) ) {
            return;
        }

        $this->elements[ $element->get_option_name() ] = $element;
    }

    /**
     * Return the field elements.
     *
     * @return Element[]
     */
    public function get_elements() {
        return $this->elements;
    }

}
