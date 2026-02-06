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

use WP_CLI;

class CliUtils
{
    public static function isWpCli(){
        return defined( 'WP_CLI' ) && WP_CLI ? true : false;
    }

}