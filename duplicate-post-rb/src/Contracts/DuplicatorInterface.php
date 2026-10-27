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

namespace rbDuplicatePost\Contracts;

defined('WPINC') || exit;

interface DuplicatorInterface
{
    /**
     * @param int $id Source post ID.
     * @param int $profile_id Duplication profile ID (0 = default).
     * @param int $target_blog_id Target site ID (0 = current blog). Multisite only.
     * @param int $source_blog_id Source site ID (0 = current blog). Multisite only.
     */
    public function duplicate( int $id, int $profile_id = 0, int $target_blog_id = 0, int $source_blog_id = 0 ): int;

    public function create_duplicate( int $profile_id, int $target_blog_id = 0 ): array;

    public function supports( string $type ): bool;

    public function is_allowed_special_post( int $id ): int; // 1 allowed, 0 not, -1 not special
}