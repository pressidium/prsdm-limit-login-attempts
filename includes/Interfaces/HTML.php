<?php
namespace Pressidium\Limit_Login_Attempts\Interfaces;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * HTML is a generic interface, meant to be used from any class of the plugin.
 */
interface HTML {
    /**
     * Return the HTML to display the UI element.
     *
     * @return string
     */
    public function get_html();
}
