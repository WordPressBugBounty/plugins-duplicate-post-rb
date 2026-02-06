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

namespace rbDuplicatePost\Profile;

defined('WPINC') || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\User;
use rbDuplicatePost\cli\CliUtils ;

class Profile
{

    /**
     * get last viewed profile ID
     * @return int
     */
    public static function getLastViewedProfile()
    {
        $last_view_profile_id = (int) get_option(Constants::OPTION_NAME_PROFILE_LASTVIEW, 0);
        if ($last_view_profile_id <= 0 || !self::isProfileExists($last_view_profile_id)) {
            return self::getDefaultProfileId();
        }
        return $last_view_profile_id;
    }

    /**
     * set last viewed profile ID
     * @param int $profile_id
     */
    public static function setLastViewedProfile($profile_id)
    {
        $profile_id = absint($profile_id);
        if (! $profile_id) {
            return false;
        }
        update_option(Constants::OPTION_NAME_PROFILE_LASTVIEW, $profile_id);
    }

    /**
     * get default profile ID
     * @return int
     */
    public static function getDefaultProfileId()
    {
        return (int) get_option(Constants::OPTION_NAME_DEFAULT_PROFILE, 0);
    }


    /** 
     * set default profile by ID
     * @param int $profile_id
     * @return boolean
     */
    public static function setDefaultProfile($profile_id)
    {
        $profile_id = absint($profile_id);

        if (! self::isProfileExists($profile_id)) {
            return false;
        }
        update_option(Constants::OPTION_NAME_DEFAULT_PROFILE, $profile_id);
        return true;
    }

    /**
     * check if default profile exists
     * @return boolean
     */
    public static function isDefaultProfileExits()
    {
        $default_profile_id = self::getDefaultProfileId();

        if ($default_profile_id <= 0) {
            return false;
        }

        return self::isProfileExists($default_profile_id);
    }

    /**
     * check if exits profile
     *
     * @param mixed $profile_id
     * @return boolean
     */
    public static function isProfileExists($profile_id)
    {
        $profile_id = absint($profile_id);
        if (! $profile_id) {
            return false;
        }

        $post = get_post($profile_id);
        if (null == $post || get_class($post) !== 'WP_Post' || RB_DUPLICATE_POST_PROFILE_TYPE_POST !== $post->post_type) {
            return false;
        }

        return true;
    }

    /**
     * get profile  
     *
     * @param integer $profile_id 
     * @return \WP_Post|boolean
     */
    public static function getProfile(int $profile_id) 
    {
        if(!self::isProfileExists($profile_id)) {
            return false;
        }
        $profile = get_post($profile_id);
        if(null == $profile || get_class($profile) !== 'WP_Post' || RB_DUPLICATE_POST_PROFILE_TYPE_POST !== $profile->post_type) {
            return false;
        }

        return $profile;
    }
       

    /**
     * get profiles   
     *
     * @param integer $limit
     * @return array
     */
    public static function getProfiles($limit = -1, $fields = 'ids') 
    {
        $arg = [
            'post_type'   => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'numberposts' => $limit ? $limit : -1,
            'orderby'     => 'ID',
            'order'       => 'ASC',
         ];

         if($fields){
             $arg['fields'] = $fields;
         }

        $profiles = get_posts($arg);

        if (! is_array($profiles) || count($profiles) == 0) {
            return array();
        }
        return $profiles;
    }

    /**
     * check if profiles exists
     * @return boolean
     */
    public static function isProfilesExists()
    {
        $profiles = self::getProfiles(1);
        return count($profiles) > 0;
    }

    /**
     * create profile
     * @param string $title
     * @return integer|boolean
     */
    public static function createProfile($title = '')
    {

        if (!self::isUserCanEditProfile() ) {
            return false;
        }

        $title = trim( sanitize_text_field( $title ) );

        if ( !$title) {
            return false;
        }

        $new_post = [
            'post_title'  => $title,
            'post_status' => 'publish',
            'post_type'   => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'post_author' => CliUtils::isWpCli() ? 1 : get_current_user_id(),
         ];
        return wp_insert_post($new_post);
    }


    /**
     * update profile
     * @param integer $profile_id
     * @param string $title
     * @return integer|boolean
     */ 
    public static function updateProfile($profile_id, $title)
    {
        $profile_id = absint($profile_id);
        $title      = trim(sanitize_text_field($title));

        if (! self::isUserCanEditExitsProfile($profile_id)) {
            return false;
        }

        // allow empty title
        // if (! is_string($title) ) { //|| ! $title
        //     return false;
        // }

        $update_post = [
            'ID'         => $profile_id,
            'post_title' => $title,
         ];

        return wp_update_post($update_post);
    }

    /**
     * delete profile
     * @param integer $profile_id
     * @return boolean
     */
    public static function deleteProfile($profile_id)
    {
        $profile_id = absint($profile_id);

        if (! self::isUserCanEditExitsProfile($profile_id)) {
            return false;
        }

        if (self::getDefaultProfileId() == $profile_id) {
            return false;
        }

        return wp_delete_post($profile_id);
    }

    /**
     * check if user can edit profile
     * @return boolean 
     */
    public static function isUserCanEditProfile()
    {
        if( CliUtils::isWpCli() ) {
            return true;
        }

        if (!User::canPublishPosts()) {
            return false;
        }

        if (!User::canEditPosts()) {
            return false;
        }

        return true;
    }

    /**
     * check if user can edit exits profile
     * @param integer $profile_id
     * @return boolean
     */
    public static function isUserCanEditExitsProfile($profile_id = 0)
    {
        if (! self::isUserCanEditProfile()) {
            return false;
        }

        $profile_id = absint($profile_id);
        if (! $profile_id) {
            return false;
        }
        $post = get_post($profile_id);
        if (null == $post || get_class($post) !== 'WP_Post' || RB_DUPLICATE_POST_PROFILE_TYPE_POST !== $post->post_type) {
            return false;
        }

        return true;
    }

    /**
     * init default profile
     */
    public static function initDefaultProfile()
    {
        $default_profile = self::getDefaultProfileId();
        if (! $default_profile || ! self::isProfileExists($default_profile)) {
            $profiles = self::getProfiles(1);
            if (! is_array($profiles) || count($profiles) == 0) {
                $profile_id = self::createProfile('Default');
            } else {
                $profile_id = reset($profiles);
            }
            self::setDefaultProfile($profile_id);
        }
    }
}
