<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
*      By RbPlugin
*
*      Contact: https://rbplugins.com 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

class Utils {

    /**
     * safe array insert after
     *
     * @param array  $array      Array to insert into.
     * @param string $afterKey   Key to insert after.
     * @param string $newKey     Key to insert.
     * @param mixed  $newValue   Value to insert.
     *
     * @return array             New array with inserted value.
     */
    public static function arrayInsertAfter( array $array, string $afterKey, string $newKey, $newValue ): array {

        // Check if the key exists
        if ( array_key_exists( $newKey, $array ) ) {
            return $array;
        }

        // Check if the key to insert after exists
        $inserted = false;
        $result   = array();

        foreach ( $array as $key => $value ) {
            $result[ $key ] = $value;

            if ( $key === $afterKey ) {
                $result[ $newKey ] = $newValue;
                $inserted          = true;
            }
        }

        // If the key to insert after was not found, insert it at the end
        if ( ! $inserted ) {
            $result[ $newKey ] = $newValue;
        }

        return $result;
    }

    /**
     * Runs $callback without the markup filter WordPress puts on what a user who may not
     * write any markup saves, when there is no user at all (cron, WP-CLI): a copy made
     * there is of content already on the site, saved by someone who was allowed to write
     * it - without this an automatic copy loses its embeds, forms and scripts.
     *
     * @return mixed what $callback returns
     */
    public static function withoutKsesWhenNoUser( callable $callback ) {
        if ( is_user_logged_in() || false === has_filter( 'content_save_pre', 'wp_filter_post_kses' ) ) {
            return $callback();
        }
        kses_remove_filters();
        try {
            return $callback();
        } finally {
            kses_init_filters();
        }
    }

    /**
     * Get current screen
     */
    public static function getCurrentScreen() {
        if ( ! isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
            return '';
        }

        $parts = wp_parse_url( esc_url_raw( wp_unslash( $_SERVER[ 'REQUEST_URI' ] ) ) );
        if ( ! $parts || ! isset( $parts[ 'path' ] ) ) {
            return '';
        }

        $path_elements = explode( '/', $parts[ 'path' ] );
        $path_elements = array_filter( $path_elements );
        if ( empty( $path_elements ) ) {
            return '';
        }

        $screen = end( $path_elements );
        return $screen;
    }

    /**
     * Get settings page URL.
     *
     * @return string
     */

    public static function getSettingsPageUrl() {
        // Get url for admin menu depending on pluginMenuInToolsMenu option
        $admin_url = GeneralOptions::getOptionValue( 'pluginMenuInToolsMenu' ) ? 'tools.php' : 'admin.php';
        // Return url with page parameter
        return add_query_arg( 'page', 'rb_duplicate_post_settings', admin_url( $admin_url ) );
    }


    public static function getMaxInteger() {
        return defined('PHP_INT_MAX') ? PHP_INT_MAX : 9999;
    }
}