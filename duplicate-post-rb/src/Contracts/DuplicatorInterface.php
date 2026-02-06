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

namespace rbDuplicatePost\Contracts;

defined('WPINC') || exit;

interface DuplicatorInterface
{
    public function duplicate(int $id, int $profile_id = 0): int;
    public function supports(string $type): bool;
}