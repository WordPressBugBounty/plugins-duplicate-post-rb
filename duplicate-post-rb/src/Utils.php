<?php
/* 
*      RB Duplicate Post     
*      Version: 1.5.8
*      By RbPlugin
*
*      Contact: https://robosoft.co 
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
     * Get current screen
     */
    public static function getCurrentScreen() {
        if ( ! isset( $_SERVER[ 'REQUEST_URI' ] ) ) {
            return '';
        }

        $parts = parse_url( $_SERVER[ 'REQUEST_URI' ] );
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
}
