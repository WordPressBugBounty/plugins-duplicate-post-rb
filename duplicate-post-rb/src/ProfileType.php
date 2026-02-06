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

defined('WPINC') || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\Profile\Profile;

class ProfileType
{

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->addHooks();
    }

    /** Add hooks */
    private function addHooks()
    {
        add_action('init', array( $this, 'addType'), 1);

        if (self::isNeedRefreshAfterInstall()) {
            add_action('init', array(self::class, 'RefreshAfterInstall' ));
        }
    }

    /** Check if need refresh rewrite rules after install */
    private static function isNeedRefreshAfterInstall()
    {
        return get_option( Constants::INSTALL_FLAG_OPTION_NAME, 0) ? true : false;
    }

    /** Add custom post Profile type  */
    public function addType()
    {
        $label = array(

            'name'          => 'Rb Duplicate Post Profiles',
            'singular_name' => 'Rb Duplicate Post Profile',
        );

        $supportArray = array( 'title', 'author' ); //, 'thumbnail'

        $args = array(
            'labels'       => $label,

            'description'  => __('Profile options for duplicate post', 'duplicate-post-rb'),

            'rewrite'      => array( 'slug' => 'rb_duplicate_post', 'with_front' => false ),
            'public'       => false,
            'has_archive'  => false,
            'hierarchical' => false,
            'supports'     => $supportArray,

            'show_in_menu' => false,

            'show_in_rest' => true,
            'rest_base'    => 'rb_duplicate_post',

        );

        register_post_type(RB_DUPLICATE_POST_PROFILE_TYPE_POST, $args);
    }

    /** Refresh rewrite rules after install */
    public static function RefreshAfterInstall()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();

        Profile::initDefaultProfile();

        if (! delete_option(Constants::INSTALL_FLAG_OPTION_NAME)) {
            update_option(Constants::INSTALL_FLAG_OPTION_NAME, false);
        }

        update_option(Constants::INSTALL_FLAG_OPTION_NAME, false);
    }
}
