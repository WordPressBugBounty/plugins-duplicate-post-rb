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

use rbDuplicatePost\Contracts\PostTransformer;
use rbDuplicatePost\ProfileOptions;
use rbDuplicatePost\Profile\Profile;

class OptionsManager {
    public function getOptions(int $profile_id = 0): array {
        if(!$profile_id) {
            $profile_id = Profile::getDefaultProfileId();
        }
        /**
         * The options one copy is made with. The REST route uses it for the
         * one-off choices of the copy dialog's "Advanced settings".
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        return apply_filters( 'rb_duplicate_post_copy_options', ProfileOptions::getOptionsArray($profile_id), $profile_id );
    }
}