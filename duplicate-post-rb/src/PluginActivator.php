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

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\Log\Logger;

class PluginActivator {

    /**
     * Constructor
     */
    public function __construct() {
        self::hooks();
    }

    /**
     * Register hooks
     */
    public static function hooks() {
        register_activation_hook( RB_DUPLICATE_POST_MAIN_FILE, array( self::class, 'activate' ) );
        register_deactivation_hook( RB_DUPLICATE_POST_MAIN_FILE, array( self::class, 'deactivate' ) );

        // an update does not run the activation hook: what a new version needs is made here
        if ( self::isNeedUpdateVersion() ) {
            Logger::install();
            self::addVersionToDb();
            // what the new version brings: the welcome screen, if it has news not shown yet
            \rbDuplicatePost\AdminUI\WelcomeScreen::mark_pending();
        }
    }

    /**
     * Add version to db
     */
    public static function addVersionToDb() {
        add_option( Constants::INSTALL_FLAG_OPTION_NAME, true );
        update_option( Constants::VERSION_OPTION_NAME, RB_DUPLICATE_POST_VERSION );
    }

    /**
     * Check if plugin need update version
     *
     * @return bool
     */
    public static function isNeedUpdateVersion() {
        $currentVersion = get_option( Constants::VERSION_OPTION_NAME, false );
        if ( $currentVersion == false ) {
            return true;
        }
        if ( version_compare( $currentVersion, RB_DUPLICATE_POST_VERSION, '<' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Activate plugin
     */
    public static function activate() {
        add_option( Constants::INSTALL_FLAG_OPTION_NAME, true );
        Logger::install();
        \rbDuplicatePost\AdminUI\WelcomeScreen::mark_pending();
    }

    /** the plugin's WP-Cron events, the PRO key's too: a deactivated plugin is not there to run them */
    const CRON_HOOKS = array(
        'rb_duplicate_post_log_clean',
        'rb_duplicate_post_auto_cleanup',
        'rb_duplicate_post_auto_copy',
    );

    /**
     * Deactivate plugin
     *
     * @param bool $network_wide deactivated for the whole network: every site has its own events
     */
    public static function deactivate( $network_wide = false ) {
        delete_option( Constants::INSTALL_FLAG_OPTION_NAME );

        $sites = is_multisite() && $network_wide ? get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) : array( get_current_blog_id() );
        foreach ( $sites as $site_id ) {
            \rbDuplicatePost\Helpers\Multisite::with_blog( (int) $site_id, function () {
                foreach ( self::CRON_HOOKS as $hook ) {
                    // every event of the hook, with any arguments (an Auto copy waiting has its post's)
                    wp_unschedule_hook( $hook );
                }
            } );
        }
    }
}