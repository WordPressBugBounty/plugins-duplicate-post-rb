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
use rbDuplicatePost\GeneralOptionsConfig;

class GeneralOptions {

    /**
     * Read general options from DB by 
     *
     * @return array
     */
    public static function getOptionsFromDb() : array 
    {

        $defaultOptions = array();

        $options = get_option( Constants::GENERAL_OPTIONS_NAME, $defaultOptions );

        if ( ! is_array( $options ) ) {
            $options = array();
        }
        $options_data = GeneralOptionsConfig::getOptions();
        $return_data  = array();

        foreach ( $options_data as $key => $option ) {
            $option[ 'value' ]   = array_key_exists( $key, $options ) ? $options[ $key ] : $option[ 'default' ];
            $return_data[ $key ] = $option;
        }
        return $return_data;
    }


    /**
     * Prepare options for DB.
     * 
     * @param array $options
     * @return array
     */
    public static function prepareOptionsForDB($options): array
    {
        $formatedOptions = array();
        foreach ($options as $key => $option) {
            $formatedOptions[$key] = $option['value'];
        }
        return $formatedOptions;
    }


    /**
     * Get all general options as unassociated array.
     *
     * @return array
     */
    public static function getOptions( string $option_id = "" ): array {
        $options = self::getOptionsFromDb();

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

    public static function getOptionValue( string $option_id  )  {
        $option = self::getOption( $option_id );

        if(empty($option) || !isset($option['value'])) {
            return  isset($option['default']) ? $option['default'] : null;
        }
        return $option['value'];
    }

    public static function getOption( string $option_id  ) : array {
        if(!$option_id) {
            return array(
                'option_id' => '',
                'value' => null,
            );
        }
        $options = self::getOptions( $option_id );
        if(empty($options)) {
            return array(
                'option_id' => $option_id,
                'value' => null,
            );
        }
        return $options[0];
    }

    /**
     * Get all general options as associative array.
     * 
     * @return array
     */
    public static function getOptionsArray( string $option_id = "" ) : array {
        $optionsArray = array();
        $options          = self::getOptions( $option_id );
        foreach ( $options as  $option ) {
            $optionsArray[ $option['option_id'] ] = $option;
        }
        return $optionsArray;
    }


    /**
     * Update general options in DB.
     *
     * @param array $options_in options to update
     * @return bool 
     */
    public static function updateOptionsInDb(  array $options_in): bool
    {
        $options = self::getOptionsFromDb();
        $options = self::prepareOptionsForDB($options);

        foreach ($options_in as $option_id => $value) {
            $options[$option_id] = $value;
        }

        return update_option(   Constants::GENERAL_OPTIONS_NAME, $options);
    }
}
