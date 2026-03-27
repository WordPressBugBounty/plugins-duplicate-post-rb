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

namespace rbDuplicatePost\Log;

defined('WPINC') || exit;

class Actions
{
    public const COPY = 'copy';
    public const CREATE = 'create';
    public const DELETE = 'delete';
    public const DELETE_TO_TRASH = 'delete_to_trash';

    public static function all(): array
    {
        return array(
            self::COPY,
            self::CREATE,
            self::DELETE,
            self::DELETE_TO_TRASH
        );
    }

    public static function isValid(string $action): bool
    {
        return in_array($action, self::all(), true);
    }
}
