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

namespace rbDuplicatePost\Core;

defined('WPINC') || exit;

use rbDuplicatePost\Contracts\DuplicatorInterface;

class DuplicatorFactory
{
    /** @var DuplicatorInterface[] */
    private static array $providers = array();

    public static function registerProvider(DuplicatorInterface $provider): void
    {
        self::$providers[] = $provider;
    }

    public static function make(string $type)
    {
        foreach (self::$providers as $provider) {
            if ($provider->supports($type)) {
                return $provider;
            }
        }
        return null;
    }
}