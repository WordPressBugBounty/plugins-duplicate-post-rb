<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.1
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

class ProCheck
{
    private static bool $isPro = false;
    private static bool $init = false;

    public static function init()
    {
        self::$init = true;

        if (defined('RB_DUPLICATE_POST_PRO')) {
            self::$isPro = true;
        }

        add_action('rb_duplicate_post_pro_loaded', function () {
            self::$isPro = true;
        });
    }

    public static function isActive(): bool
    {
        if(!self::$init) {
            self::init();
        }
        return self::$isPro;
    }
}
