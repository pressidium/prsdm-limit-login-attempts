<?php
namespace Pressidium\Limit_Login_Attempts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Autoloader class.
 *
 * PSR-4 compliant autoloader.
 *
 * After registering this autoloader, the following
 * line would cause the function to attempt to load
 * the `\Pressidium\Limit_Login_Attempts\Standalone\Button`
 * class from the `./includes/Standalone/Button.php` file:
 * 
 * new \Pressidium\Limit_Login_Attempts\Standalone\Button;
 */
class Autoloader {

    /**
     * @var string Project-specific namespace prefix.
     */
    const PREFIX = 'Pressidium\\Limit_Login_Attempts\\';

    /**
     * @var string Base directory for the namespace prefix.
     */
    const BASE_DIR = __DIR__ . '/includes/';

    /**
     * Register loader.
     *
     * @link https://www.php.net/manual/en/function.spl-autoload-register.php
     */
    public function register() {
        spl_autoload_register( array( $this, 'load_class' ) );
    }

    /**
     * Check whether the given class name uses the namespace prefix.
     *
     * @param string $class The class name to check.
     * @return bool
     */
    private function starts_with_namespace_prefix( $class ) {
        $len = strlen( self::PREFIX );
        return strncmp( self::PREFIX, $class, $len ) === 0;
    }

    /**
     * Return the mapped file for the namespace prefix and the given class name.
     *
     * Replace the namespace prefix with the base directory,
     * replace namespace separators with directory separators,
     * and append with `.php`.
     *
     * @param string $class The fully-qualified class name.
     * @return string
     */
    private function get_mapped_file( $class ) {
        $relative_class = substr( $class, strlen( self::PREFIX ) );
        return self::BASE_DIR . str_replace( '\\', '/', $relative_class ) . '.php';
    }

    /**
     * Require the file at the given path, if it exists.
     *
     * @param string $file
     */
    private function require_file( $file ) {
        if ( file_exists( $file ) ) {
            require $file;
        }
    }

    /**
     * Load the class file for the given class name.
     *
     * @param string $class The fully-qualified class name.
     */
    public function load_class( $class ) {
        if ( ! $this->starts_with_namespace_prefix( $class ) ) {
            /*
             * Class does not use the namespace prefix,
             * move to the next registered autoloader.
             */
            return;
        }

        $mapped_file = $this->get_mapped_file( $class );
        $this->require_file( $mapped_file );
    }

}
