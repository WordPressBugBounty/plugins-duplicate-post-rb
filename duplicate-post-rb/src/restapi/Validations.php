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

namespace rbDuplicatePost\restapi;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\ProfileOptionsConfig;
use rbDuplicatePost\IDsParser;
use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\Helpers\Multisite;

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

        // the posts are looked up on the site they are copied from; a site the user
        // is not a member of is not looked into at all (it would tell which IDs exist there)
        $source_blog_id = $request instanceof \WP_REST_Request ? absint( $request->get_param( 'source_blog_id' ) ) : 0;
        if ( $source_blog_id && ! Multisite::can_user_copy_from_blog( $source_blog_id ) ) {
            return false;
        }

        $posts = Multisite::with_blog( $source_blog_id, function () use ( $ids ) {
            return get_posts( array(
                'fields'      => 'ids',
                'include'     => $ids,
                'post_status' => Constants::ALLOWED_POST_STATUSES,
                'post_type' =>  array_keys( PostTypes::get_all_types(  ) ),
                'ignore_sticky_posts'=>false
            ) );
        } );

        if (  count( $posts ) != count( $ids ) ) {
            // say why: an unsaved post (auto-draft), a status or a post type the plugin does not copy
            // (looked up on the same source site as the posts above)
            $missing = array_diff( $ids, array_map( 'absint', $posts ) );
            $reason  = Multisite::with_blog( $source_blog_id, function () use ( $missing ) {
                $types = array_keys( PostTypes::get_all_types() );
                foreach ( $missing as $id ) {
                    $status = get_post_status( $id );
                    if ( $status === 'auto-draft' ) {
                        return __( 'Save the post first: a new post can be copied once it is saved.', 'duplicate-post-rb' );
                    }
                    if ( $status === 'trash' ) {
                        return __( 'A post in the trash cannot be copied. Restore it first.', 'duplicate-post-rb' );
                    }
                    if ( $status && ! in_array( get_post_type( $id ), $types, true ) ) {
                        return __( 'This post type is not copied. Turn it on in Settings → Content types.', 'duplicate-post-rb' );
                    }
                }
                return __( 'The post to copy was not found.', 'duplicate-post-rb' );
            } );
            return new \WP_Error( 'rest_invalid_param', $reason, array( 'status' => 400 ) );
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

        // letters or digits: a name of signs only would be empty once cleaned
        if ( ! is_string( $param ) || \rbDuplicatePost\Profile\Profile::clean_title( $param ) === '' ) {
            return new \WP_Error( 'rest_invalid_param', __( 'A profile name needs letters or digits (up to 40).', 'duplicate-post-rb' ), array( 'status' => 400 ) );
        }

        return true;
    }


    
    /** the Assistant's scenario of a profile */
    public static function validate_scenario_field($value, $option)
    {
        $scenario = \rbDuplicatePost\Scenario\Scenario::sanitize($value);
        $error    = \rbDuplicatePost\Scenario\Scenario::check_permissions($scenario, $option['value'] ?? null);
        return $error ? $error : $scenario;
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

            case 'multiline_string':
                $value = self::sanitize_multiline_text($value);
                break;

            case 'color':
                $value = self::sanitize_color($value, $option['default'] ?? '');
                break;

            // stored as a string of digits, as the option always was ('0': none)
            case 'post_id':
                $value = is_scalar($value) && preg_match('/^\d+$/', trim((string) $value)) ? (string) absint($value) : '0';
                break;
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

    /**
     * A CSS colour as the colour picker sends it (#hex, rgb(), rgba(), hsl(), hsla())
     * or a colour name; anything else - more CSS after the colour - gives the default.
     */
    private static function sanitize_color($input, $default) {
        $input = is_string($input) ? trim($input) : '';

        if (preg_match('/^#[0-9a-f]{3,8}$/i', $input)
            || preg_match('/^(rgb|hsl)a?\(\s*[0-9.%,\s\/]+\)$/i', $input)
            || preg_match('/^[a-z]{3,30}$/i', $input)) {
            return $input;
        }

        return $default;
    }

    private static function sanitize_multiline_text($input) {
        if (!is_string($input)) {
            return '';
        }

        // Normalize line breaks (\r\n, \r → \n)
        $input = str_replace(["\r\n", "\r"], "\n", $input);

        // Split into lines
        $lines = explode("\n", $input);

        //  Sanitize each line
        $sanitized_lines = array_map(function($line) {
            return sanitize_text_field($line);
        }, $lines);

        //  Join back
        return implode("\n", $sanitized_lines);
    }

}
