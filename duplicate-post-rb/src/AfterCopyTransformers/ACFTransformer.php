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

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Copiers\ACFCopier;

class ACFTransformer extends AbstractPostAfterCopyTransformer {

    //this transformer  needed all the time
     protected static function get_option_name(): string{
        return 'enableTypeACF';
    }

    public function transform(TransformerContext $context)  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        $post_new_id = $context->get_target()->get_post_id();
        $post_type = $context->get_target()->get_post_type();

        if( $post_type !== 'acf-field-group' && $post_type !== 'acf-field' ) {
            return ;
        }

        ACFCopier::updateFieldKey($post_new_id);

    }
}
