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

defined('WPINC') || exit;

class IDsParser
{
    /**
     * Validate string 
     * @param string $str 
     * @return bool true 
     */
    public static function isValid(string $str): bool
    {
        // check for empty string
        if ($str === '') {
            return false;
        }

        // Trim whitespace and split by comma
        $items = array_map('trim', explode(',', $str));

        foreach ($items as $item) {

            if ($item === '') {
                return false;
            }

            // Must consist only of digits (0-9), without signs, dots, etc.
            if (!ctype_digit($item)) {
                return false;
            }

            // Convert and check strictly > 0
            $num = (int)$item;
            if ($num <= 0) {
                return false;
            }
        }

        return true;
    }

    /**
     * Converts a string into an array of integers (> 0).
     * Guarantees that each element:
     *   - has passed validation,
     *   - is sanitized via (int),
     *   - is a positive integer.
     *
     * @param string $str Input string
     * @return array<int> Array of integers (> 0)
     */
    public static function parse(string $str): array
    {
        if (!self::isValid($str)) {
           return array();
        }

        $items = array_map('trim', explode(',', $str));
        $result = array();

        foreach ($items as $item) {
            // Explicit cast to int — ensure purity
            $num = (int)$item;

            // Although isValid() already checked, perform a final safeguard (defensive programming)
            if ($num <= 0) {
                continue ;
            }

            $result[] = $num;
        }

        return $result;
    }
}