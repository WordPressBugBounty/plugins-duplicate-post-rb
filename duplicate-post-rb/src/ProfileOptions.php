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

use rbDuplicatePost\Constants;
use rbDuplicatePost\ProfileOptionsConfig;

class ProfileOptions {

    /**
     * Read profile options from DB by profile ID.
     *
     * @param integer $profile_id  profile ID
     * @return array
     */
    public static function getOptionsFromDb( int $profile_id ) : array 
    {
        if(!$profile_id || absint($profile_id) <= 0) {
            return array();
        }

        $options = get_post_meta(
            $profile_id,
            Constants::OPTION_NAME,
            true
        );

        if ( ! is_array( $options ) ) {
            $options = array();
        }
        $options_data = ProfileOptionsConfig::getOptions();
        $return_data  = array();

        foreach ( $options_data as $key => $option ) {
            $option[ 'value' ]   = array_key_exists( $key, $options ) ? $options[ $key ] : $option[ 'default' ];
            $return_data[ $key ] = $option;
        }
        return $return_data;
    }


    public static function prepareOptionsForDB($options)
    {
        $formatedOptions = array();
        foreach ($options as $key => $option) {
            $formatedOptions[$key] = $option['value'];
        }
        return $formatedOptions;
    }



    /**
     * Get all profiles options as unassociated array.
     *
     * @return array
     */
    public static function getOptions( int $profile_id, string $option_id = "" ): array {
        $options = self::getOptionsFromDb( $profile_id );

        $filtered_options = array();

        foreach ( $options as $key => $option ) {
            
            if ( $option_id && $option[ 'option_id' ] !== $option_id ) {
                continue;
            }
            $filtered_options[] = $option;
        }

        if ( empty( $filtered_options ) ) {
            return array();
        }
        return $filtered_options;
    }

    /**
     * Get all profiles options as associative array.
     * 
     * @return array
     */
    public static function getOptionsArray(int $profile_id, string $option_id = "" ) : array {
        $optionsArray = array();
        $options          = self::getOptions( $profile_id, $option_id );
        foreach ( $options as  $option ) {
            $optionsArray[ $option['option_id'] ] = $option;
        }
        return $optionsArray;
    }


    /**
     * Update options in DB.
     *
     * @param integer $profile_id profile ID to update
     * @param array $options_in options to update
     * @return void
     */
    public static function updateOptionsInDb( int $profile_id, array $options_in): void
    {
        $profile_id = absint( $profile_id );
        $options = self::getOptionsFromDb($profile_id);
        $options = self::prepareOptionsForDB($options);

        foreach ($options_in as $option_id => $value) {
            $options[$option_id] = $value;
        }

        if (!add_post_meta( $profile_id, Constants::OPTION_NAME, $options, true)) {
            update_post_meta(  $profile_id, Constants::OPTION_NAME, $options);
        }
    }
}
