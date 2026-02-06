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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\ProfileOptionsConfig;
use rbDuplicatePost\IDsParser;

/**
 * REST API Validation Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class Validations {

    /**
     * @param mixed $param profile id 
     * @param mixed $request 
     * @param string $key
     *
     * @return boolean
     */
    public static function validate_profile_id( $param, $request, $key ) {

        if ( ! is_numeric( $param ) || $param <= 0 ) {
            return false;
        }

        $param = absint( $param );

        if ( 0 === $param ) {
            return false;
        }

        $post = get_post( $param );

        if ( null == $post || get_class( $post ) !== 'WP_Post' || RB_DUPLICATE_POST_PROFILE_TYPE_POST !== $post->post_type ) {
            return false;
        }

        return true;
    }

    /**
     * @param mixed $param
     * @param mixed $request
     * @param string $key
     *
     * @return boolean
     */
    public static function validate_post_id( $param, $request, $key ) {
        return self::validate_post_ids( $param , $request, $key );
    }

    /**
     * @param mixed $param
     * @param mixed $request
     * @param string $key
     *
     * @return boolean
     */
    public static function validate_post_ids( $param, $request, $key ) {

        $ids = IDsParser::parse( $param );

        $args = array(
            'fields'      => 'ids',
            'include'     => $ids,
            'post_status' => Constants::ALLOWED_POST_STATUSES,
            'post_type' => ['post', 'page'],
            'ignore_sticky_posts'=>false
        );
        $posts = get_posts( $args );

        if (  count( $posts ) != count( $ids ) ) {
            return false;
        }

        foreach ( $posts as $post ) {
            if ( null == $post || absint( $post ) <= 0 ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $param
     * @param mixed $request
     * @param string $key
     *
     * @return boolean
     */
    public static function validate_option_id( $param, $request, $key ) {
        return $param && array_key_exists( $param, ProfileOptionsConfig::getOptions() );
    }

    public static function validate_profile_title( $param, $request, $key ) {

        if ( ! is_string( $param ) || trim( $param ) === '' ) {
            return false;
        }

        return true;
    }


    
    public static function validate_multiselect_field($values, $option)
    {

        if (empty($values)) {
            return array();
        }

        if (!is_array($values) || count($values) == 0) {
            return array();
        }

        if (!is_array($option) || !isset($option['options']) || !is_array($option['options'])) {
            return array();
        }

        $final_values = array();
        $allow_items = $option['options'];
        foreach ($values as $value) {
            if (in_array($value, $allow_items, true)) {
                $final_values[] = $value;
            }
        }

        return $final_values;
    }

    /**
     * @param mixed $value
     * @param mixed $option
     *
     * @return mixed
     */
    public static function validate_text_field($value, $option)
    {
        if (isset($option[0])) {
            $option = $option[0];
        }
        if (!isset($option['sanitize'])) {
            $option['sanitize'] = 'string';
        }

        switch ($option['sanitize']) {
            case 'boolean':$value = $value ? true : false;
                break;

            case 'integer':
                $value = (int) $value;
                if(isset($option['params'])){
                    if(isset($option['params']['min'])){
                        $min = $option['params']['min'];
                        if( $value < $min )  $value = $min;
                    }

                    if(isset($option['params']['max'])){
                        $max = $option['params']['max'];
                        if( $value > $max )  $value = $max;
                    }
                }
                break;

            // case 'array':
            //     $value = sanitize_text_field($value);
            //     if ($value) {
            //         $value_array = explode(',', $value);
            //     }

            //     if (!is(array($value_array)) || count($value_array) == 0) {
            //         return $value = array();
            //     }

            //     foreach ($value_array as $key => $val) {
            //         $value       = array();
            //         $value[$key] = sanitize_text_field($val);
            //     }
            //     break;

            case 'string':
            default:
                $value = sanitize_text_field($value);

        }

        if (isset($option['options'])) {
            if (!in_array($value, $option['options'], true)) {
                $value = $option['default'];
            }
        }

        return $value;
    }

}
