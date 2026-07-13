<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.7
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
       
        $d = new PostDuplicator();
        return $d->is_allowed_special_post($id);
    }

    public function duplicate(int $id, int $profile_id = 0): int
    {
        $d = new PostDuplicator();
        return $d->duplicate($id, $profile_id);
    }

    public function create_duplicate(int $profile_id): array
    {
        $d = new PostDuplicator();
        return $d->create_duplicate($profile_id);
    }
}