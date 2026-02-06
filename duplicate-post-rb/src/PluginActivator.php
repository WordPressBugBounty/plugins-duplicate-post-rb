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

class PluginActivator {

    public function __construct() {
        self::hooks();
    }

    public static function hooks() {
        register_activation_hook( RB_DUPLICATE_POST_MAIN_FILE, array( self::class, 'activate' ) );
        register_deactivation_hook( RB_DUPLICATE_POST_MAIN_FILE, array( self::class, 'deactivate' ) );

        if ( self::isNeedUpdateVersion() ) {
            self::addVersionToDb();
        }
    }

    public static function addVersionToDb() {
        add_option( Constants::INSTALL_FLAG_OPTION_NAME, true );
        update_option( Constants::VERSION_OPTION_NAME, RB_DUPLICATE_POST_VERSION );
    }

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

    public static function activate() {
        add_option( Constants::INSTALL_FLAG_OPTION_NAME, true );
    }

    public static function deactivate() {
        delete_option( Constants::INSTALL_FLAG_OPTION_NAME );
    }
}