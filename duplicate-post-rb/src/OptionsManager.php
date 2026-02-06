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

use rbDuplicatePost\Contracts\PostTransformer;
use rbDuplicatePost\ProfileOptions;
use rbDuplicatePost\Profile\Profile;

class OptionsManager {
    public function getOptions(int $profile_id = 0): array {
        if(!$profile_id) {
            $profile_id = Profile::getDefaultProfileId();
        }
        return ProfileOptions::getOptionsArray($profile_id);
    }
}