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

use rbDuplicatePost\Contexts\TransformerContext;

interface PostAfterCopyTransformer {
    public function transform(TransformerContext $context): void;
    public static function supports(TransformerContext $context): bool;
}