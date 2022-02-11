<?php
namespace Pressidium\Limit_Login_Attempts;

use Pressidium\Limit_Login_Attempts\Options\WP_Options;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/includes/Options/WP_Options.php';

foreach ( WP_Options::get_option_keys() as $option_key ) {
    $db_option_name = 'prsdm_limit_login_attempts_' . $option_key;
    delete_option( $db_option_name );
}
