<?php
namespace Pressidium\Limit_Login_Attempts;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class User_Meta {

    /**
     * @var int User ID.
     */
    public $user_id;

    /**
     * @var string Metadata name.
     */
    public $meta_key;

    /**
     * User_Meta constructor.
     *
     * @param int    $user_id  (Optional) User ID.
     * @param string $meta_key (Optional) Metadata name.
     */
    public function __construct( $user_id = null, $meta_key = null ) {
        $this->user_id  = $user_id;
        $this->meta_key = $meta_key;
    }

    /**
     * Check whether the meta key exists.
     * 
     * If the meta value exists but is empty, it will
     * return `false` as if the meta value didn't exist.
     *
     * @return bool
     */
    public function exists() {
        return ! empty( $this->get() );
    }

    /**
     * Retrieve a user meta field.
     *
     * @link https://developer.wordpress.org/reference/functions/get_user_meta/
     *
     * @param bool $single Whether to return a single value.
     *
     * @return mixed An array if $single is `false`. The value of the metadata
     *               field if $single is `true`. `false` for an invalid $user_id.
     */
    public function get( $single = true ) {
        return get_user_meta( $this->user_id, $this->meta_key, $single );
    }

    /**
     * Set a user meta field.
     *
     * Update (or add, if it doesn't exist) a user meta field.
     *
     * @link https://developer.wordpress.org/reference/functions/update_user_meta/
     *
     * @param mixed $value      Metadata value. Must be serializable if non-scalar.
     * @param mixed $prev_value (Optional) Previous value to check before updating.
     *                          If specified, only update existing metadata entries
     *                          with this value. Otherwise, update all entries.
     *
     * @return int|bool Meta ID if the key didn't exist, `true` on successful update,
     *                  `false` on failure or if the value passed to the function is
     *                  the same as the one that is already in the database.
     */
    public function set( $value, $prev_value = '' ) {
        return update_user_meta( $this->user_id, $this->meta_key, $value, $prev_value );
    }

    /**
     * Remove a user meta field.
     *
     * @link https://developer.wordpress.org/reference/functions/delete_user_meta/
     * 
     * @param mixed $meta_value (Optional) Metadata value. If provided, rows will only
     *                          be removed that match the value. Must be serializable
     *                          if non-scalar.
     *
     * @return bool Whether the user meta field was successfully removed.
     */
    public function remove( $meta_value = '' ) {
        if ( $this->exists() ) {
            return false;
        }

        return delete_user_meta( $this->user_id, $this->meta_key, $meta_value );
    }

}
