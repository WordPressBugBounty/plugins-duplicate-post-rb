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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\PostTransformer;
use rbDuplicatePost\Contexts\TransformerContext;

abstract class AbstractPostTransformer implements PostTransformer {

    /**
     * Returns the option name associated with the transformer.
     */
    abstract protected static function get_option_name(): string;

    /**
     * {@inheritDoc}
     */
    public static function supports( TransformerContext $context ): bool {
        return true; //for now all objects support this transformer
    }

    /**
     * Checks if the transformer should copy/apply the operation.
     */
    protected static function is_copy_enabled( array $options ): bool {
        $option_name = static::get_option_name();
        if(!isset( $options[ $option_name ]['value'] ) ) {
            return false;
        }
        return $options[ $option_name ]['value'] ? true : false;
    }
}