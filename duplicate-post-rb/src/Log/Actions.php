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

namespace rbDuplicatePost\Log;

defined('WPINC') || exit;

class Actions
{
    public const COPY = 'copy';
    public const CREATE = 'create';
    public const DELETE = 'delete';
    public const DELETE_TO_TRASH = 'delete_to_trash';
    public const REPUBLISH = 'republish';

    public static function all(): array
    {
        return array(
            self::COPY,
            self::CREATE,
            self::DELETE,
            self::DELETE_TO_TRASH,
            self::REPUBLISH
        );
    }

    public static function isValid(string $action): bool
    {
        return in_array($action, self::all(), true);
    }
}
