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

namespace rbDuplicatePost\cli;

defined('WPINC') || exit;

use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\cli\CliUtils;
use rbDuplicatePost\Helpers\Multisite;
use WP_CLI;

class Commands
{   
    const PROFILES_LIMIT = 20;

    public function __construct()
    {
        add_action('init', array($this, 'init'));
    }

    public static function init(){
        if (CliUtils::isWpCli()) {
            self::register();
        }
    }

    public static function register()
    {
        WP_CLI::add_command('rb-duplicate-post duplicate', array(self::class, 'duplicate'));
        WP_CLI::add_command('rb-duplicate-post profiles', array(self::class, 'profiles'));
        WP_CLI::add_command('rb-duplicate-post sites', array(self::class, 'sites'));
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
     * [--target-blog=<blog_id>]
     * : Multisite: copy posts onto this blog ID (default: current blog).
     *
     * [--source-blog=<blog_id>]
     * : Multisite: read source posts from this blog ID (default: current blog).
     *
     * [--answer=<name=value>]
     * : An answer to a question of the profile's Assistant, e.g. --answer=to=Kazan. Repeat for more;
     * questions not answered take their default.
     *
     * ## EXAMPLES
     *
     *     wp rb-duplicate-post duplicate 123
     *     wp rb-duplicate-post duplicate "123,456" --profile=1
     *     wp rb-duplicate-post duplicate 123 --target-blog=3
     *     wp rb-duplicate-post duplicate 10 --source-blog=2 --target-blog=5
     *     wp rb-duplicate-post duplicate 42 --profile=7 --answer=to=Kazan
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

        $target_blog_id = !empty($assoc_args['target-blog']) ? absint($assoc_args['target-blog']) : 0;
        $source_blog_id = !empty($assoc_args['source-blog']) ? absint($assoc_args['source-blog']) : 0;

        // --answer=name=value, once or more (WP-CLI gives the last one only: read them from argv)
        $answers = array();
        foreach ( (array) ( $GLOBALS['argv'] ?? array() ) as $arg ) {
            if ( strpos( (string) $arg, '--answer=' ) === 0 && strpos( (string) $arg, '=', 9 ) !== false ) {
                list( $name, $value ) = explode( '=', substr( (string) $arg, 9 ), 2 );
                $answers[ $name ] = $value;
            }
        }
        if ( ! $answers && ! empty( $assoc_args['answer'] ) && strpos( (string) $assoc_args['answer'], '=' ) !== false ) {
            list( $name, $value ) = explode( '=', (string) $assoc_args['answer'], 2 );
            $answers[ $name ] = $value;
        }
        \rbDuplicatePost\Scenario\Scenario::set_answers( $answers );

        $duplicator = DuplicatorFactory::make( 'post' );

        foreach ($ids as $id) {
            try {
                $newId = $duplicator->duplicate($id, $profile_id, $target_blog_id, $source_blog_id);
                WP_CLI::log("Use profile id:{$profile_id}");
                if ($target_blog_id) {
                    WP_CLI::log("Target blog:{$target_blog_id}");
                }
                WP_CLI::success("Duplicated post ID:{$id} → Created post ID:{$newId}");
            } catch (\Exception $e) {
                WP_CLI::error("Failed to duplicate post ID:{$id}: " . $e->getMessage());
            }
        }
    }

    public function profiles($args, $assoc_args)
    {
        $profiles = Profile::getProfiles( self::PROFILES_LIMIT, '');

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

    /**
     * List Multisite blogs available as copy targets for the current user.
     *
     * ## EXAMPLES
     *
     *     wp rb-duplicate-post sites
     */
    public function sites($args, $assoc_args)
    {
        if (!is_multisite()) {
            WP_CLI::log('Not a Multisite install. Current blog ID: ' . get_current_blog_id());
            return;
        }

        WP_CLI::log('Cross-site copy enabled: ' . (Multisite::is_cross_site_enabled() ? 'yes' : 'no'));
        WP_CLI::log('Media mode: ' . Multisite::get_media_mode());
        WP_CLI::log('Available target sites:');

        foreach (Multisite::get_available_target_blogs() as $site) {
            $mark = !empty($site['is_current']) ? ' (current)' : '';
            WP_CLI::log(sprintf('  [%d] %s%s — %s', $site['id'], $site['name'], $mark, $site['url']));
        }
    }
}
