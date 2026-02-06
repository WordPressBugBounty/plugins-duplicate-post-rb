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

use rbDuplicatePost\IDsParser;

/**
 * REST API Setting Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class Sanitize
{

    /**
     * @param mixed $param
     * @param mixed $request
     * @param mixed $key
     *
     * @return integer 
     */
    public static function sanitize_profile_id($param, $request, $key)
    {
        return intval($param);
    }

    
    public static function sanitize_profile_title($param, $request, $key)
    {
        $title = sanitize_text_field($param);
        $title = substr($title, 0, 200);
        return $title;
    }
    
    public static function sanitize_option_id($param, $request, $key)
    {
        $option_id = sanitize_text_field($param);
        $option_id = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $option_id );
        return $option_id;
    }
    
    public static function sanitize_post_id($param, $request, $key)
    {
        return intval($param);
    }
    
    public static function sanitize_post_ids($param, $request, $key)
    {
        return IDsParser::parse($param);
    }
}
