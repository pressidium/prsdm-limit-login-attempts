<?php
namespace Pressidium\Limit_Login_Attempts\Sections;

use Pressidium\Limit_Login_Attempts\Sections\Fields\Elements\Element;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Settings_Section extends Section {

    /**
     * Return all element objects in this section.
     *
     * @return Element[]
     */
    private function get_all_elements_in_section() {
        $elements = array();
        
        foreach ( $this->fields as $field ) {
            $elements = array_merge( $elements, $field->get_elements() );
        }

        return $elements;
    }

    /**
     * Sanitize the options' values.
     *
     * @param array $options
     *
     * @return array
     */
    public function sanitize( $options ) {
        $elements = $this->get_all_elements_in_section();

        foreach ( $options as $key => $value ) {
            $element         = $elements[ $key ];
            $sanitized_value = $element->sanitize( $value );
            $validate        = $element->get_validate();
            $pre_write       = $element->get_pre_write();

            if ( is_callable( $validate ) && ! $validate( $sanitized_value ) ) {
                $sanitized_value = $element->get_value();
            }

            if ( is_callable( $pre_write ) ) {
                $sanitized_value = $pre_write( $sanitized_value );
            }

            $options[ $key ] = $sanitized_value;
        }

        return $options;
    }

}
