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

namespace rbDuplicatePost\Providers;

defined('WPINC') || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;
use rbDuplicatePost\Core\PostDuplicator;

class PostDuplicatorProvider implements DuplicatorInterface
{
    public function supports( string $type ): bool
    {
        return in_array( $type, array( 'post', 'page' ), true ) || post_type_exists( $type );
    }

    public function is_allowed_special_post( int $id ): int
    {
        $d = new PostDuplicator();
        return $d->is_allowed_special_post( $id );
    }

    public function duplicate( int $id, int $profile_id = 0, int $target_blog_id = 0, int $source_blog_id = 0 ): int
    {
        $d = new PostDuplicator();
        return $d->duplicate( $id, $profile_id, $target_blog_id, $source_blog_id );
    }

    public function create_duplicate( int $profile_id, int $target_blog_id = 0 ): array
    {
        $d = new PostDuplicator();
        return $d->create_duplicate( $profile_id, $target_blog_id );
    }
}
