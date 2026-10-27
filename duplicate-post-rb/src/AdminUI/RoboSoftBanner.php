<?php
/*
*      RB Duplicate Post
*      Version: 2.4.0
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

/**
 * The banner above the settings screen: the PRO offer (until PRO is unlocked)
 * and the RoboSoft services, as in our other RB plugins. Only the mount point
 * is printed here: assets/admin/settings.js draws the banner inside its own
 * Shadow DOM, so no other plugin's styles or banners can affect it.
 */
class RoboSoftBanner {

    public static function render(): void {
        // not wider than the settings and the help column together
        echo '<div id="rb-duplicate-post-banner" style="margin:16px 20px 16px 0;max-width:1060px;"></div>';
    }
}
