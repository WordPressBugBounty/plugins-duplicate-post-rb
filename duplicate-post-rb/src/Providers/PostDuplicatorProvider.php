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

namespace rbDuplicatePost\Providers;

defined('WPINC') || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;
use rbDuplicatePost\Core\PostDuplicator;

class PostDuplicatorProvider implements DuplicatorInterface
{
    public function supports(string $type): bool
    {
        return in_array($type, ['post', 'page']) || post_type_exists($type);
    }

    public function duplicate(int $id, int $profile_id = 0): int
    {
        $d = new PostDuplicator();
        return $d->duplicate($id, $profile_id);
    }
}