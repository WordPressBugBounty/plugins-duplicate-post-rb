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

namespace rbDuplicatePost\Providers;

defined('WPINC') || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;
use rbDuplicatePost\Core\PostDuplicator;
use rbDuplicatePost\Helpers\PostTypes;

class PostDuplicatorProvider implements DuplicatorInterface
{
    public function supports(string $type): bool
    {
        return in_array($type, ['post', 'page']) || post_type_exists($type);
    }

    public function is_allowed_special_post(int $id): int { //return 1 if allowed, 0 if not allowed, -1 if not special type
        if(!$id){
            return -1;
        }
        $type = get_post_type($id); 
        if(!$type){
            return -1;
        }
        return PostTypes::is_allowed_special_type($type);
    }

    public function duplicate(int $id, int $profile_id = 0): int
    {
        $d = new PostDuplicator();
        return $d->duplicate($id, $profile_id);
    }
}