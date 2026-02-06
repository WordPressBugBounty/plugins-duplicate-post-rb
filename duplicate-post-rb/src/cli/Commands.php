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

namespace rbDuplicatePost\cli;

defined('WPINC') || exit;

use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\cli\CliUtils;
use WP_CLI;

class Commands
{

    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public static function init(){
        if (CliUtils::isWpCli()) {
            self::register();
        }
    }

    public static function register(): void
    {
        WP_CLI::add_command('rb-duplicate-post duplicate', array(self::class, 'duplicate'));
        WP_CLI::add_command('rb-duplicate-post profiles', array(self::class, 'profiles'));
    }
    /**
     * Duplicate one or more posts.
     *
     * ## OPTIONS
     *
     * <ids>
     * : Comma-separated list of post IDs to duplicate.
     *
     * [--profile=<profile_id>]
     * : Set profile of duplicated posts (default: default profile).
     *
     * ## EXAMPLES
     *
     *     wp rb-duplicate-post duplicate 123
     *     wp rb-duplicate-post duplicate "123,456" --profile=1
     * 
     *  Show profiles
     *  wp rb-duplicate-post profiles
     */
    public function duplicate($args, $assoc_args)
    {
        $ids = explode(',', $args[0]);
        $ids = array_map('absint', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            WP_CLI::error('No valid IDs provided.');
        }


        $profile_id = Profile::getDefaultProfileId();
         if (!empty($assoc_args['profile'])) {
             $profile_id_test = absint( $assoc_args['profile'] );
             if($profile_id_test && Profile::isProfileExists($profile_id_test)) {
                 $profile_id = $profile_id_test;
             }
         }

        $duplicator = DuplicatorFactory::make( 'post' );

        foreach ($ids as $id) {
            try {
                $newId = $duplicator->duplicate($id,  $profile_id);
                WP_CLI::log("Use profile id:{$profile_id}");
                WP_CLI::success("Duplicated post ID:{$id} → Created post ID:{$newId}");
            } catch (\Exception $e) {
                WP_CLI::error("Failed to duplicate post ID:{$id}: " . $e->getMessage());
            }
        }
    }

    public function profiles($args, $assoc_args)
    {
        $profiles = Profile::getProfiles(20, '');

        WP_CLI::success("RB Duplicate Post Profiles:");

        if(empty($profiles)) {
            WP_CLI::log(' >> No profiles found');
            return;
        }
        $default_profile = Profile::getDefaultProfileId();

        foreach ($profiles as $profile) {
            WP_CLI::log(' >>  [ID:' . $profile->ID.'] '.$profile->post_title   . ( $default_profile==$profile->ID ? ' - it\'s default profile' : '' ) );
        }
    }
}