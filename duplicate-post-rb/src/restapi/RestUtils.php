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

namespace rbDuplicatePost\restapi;
defined('WPINC') || exit;

/**
 * REST API Setting Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class RestUtils
{

    /**
     * Boolean for if a option type is a valid supported setting type.
     *
     * @param  string $type Type.
     * @return bool
     */
    public static function isValidOptionType( $type): bool
    {
        $type = strtolower($type);
        return in_array(
            $type,
            array(

                'text', // Validates with validate_text_field.
                'email', // Validates with validate_text_field.
                'number', // Validates with validate_text_field.
                'color', // Validates with validate_text_field.
                'password', // Validates with validate_text_field.
                'textarea', // Validates with validate_textarea_field.
                'select', // Validates with validate_select_field.
                'multiselect', // Validates with validate_multiselect_field.
                'radio', // Validates with validate_radio_field (-> validate_select_field).
                'checkbox', // Validates with validate_checkbox_field.
                'image_width', // Validates with validate_image_width_field.
                'thumbnail_cropping', // Validates with validate_text_field.
            ),
            true
        );
    }

    /**
     * Callback for allowed keys for each setting response.
     *
     * @param  string $key Key to check.
     * @return boolean
     */
    public static function isAllowedOptionIds($key)
    {
        return in_array(
            $key,
            array(
                //'id',
                'default',
                'type',
                'value',

                'options',

                'group',

                'label',
                'description',
                'tip',
                'placeholder',
                'option_id',
            ),
            true
        );
    }

}