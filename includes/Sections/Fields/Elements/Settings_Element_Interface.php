<?php
namespace Pressidium\Limit_Login_Attempts\Sections\Fields\Elements;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Settings_Element_Interface {
    /**
     * Sanitize the given option value.
     *
     * @param string $option_value
     *
     * @return mixed
     */
    public function sanitize( $option_value );
}
