<?php
namespace Pressidium\Limit_Login_Attempts\Hooks;

// Prevent direct access to files
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

interface Actions {
    /**
     * Return the actions to register.
     *
     * @return array
     */
    public function get_actions();
}
