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

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\GeneralOptions;
use rbDuplicatePost\Utils;

class PluginList {

    public function __construct() {
        self::hooks();
    }

    public static function hooks() {
        add_filter( 'plugin_action_links', array( self::class, 'add_links'), 10, 2 );
    }

    public static function add_links( $links, $file ) {
		static $plugin;

		if( $file == 'duplicate-post-rb/duplicate-post-rb.php' && current_user_can('manage_options') ) {
			array_unshift(
				$links,
				sprintf( '<a href="%s">%s</a>', esc_attr( Utils::getSettingsPageUrl() ), __( 'Settings' ) )
			);
		}

		return $links;
	}
}