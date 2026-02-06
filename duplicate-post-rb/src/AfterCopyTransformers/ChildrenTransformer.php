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

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Copiers\PostChildrenCopier;

class ChildrenTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string{
        return 'copyChildren';
    }

     public function transform(TransformerContext $context):void  {

        if ( !self::supports( $context ) ) {
            return ;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            return ;
        }

        $operation_context = $context->to_operation_context();
        $profile_id = $context->get_profile_id();

        // Copy children using the new copier
        $result = PostChildrenCopier::copy_children( $operation_context, $profile_id );

        // Optionally handle result (logging, errors, etc.)
        //if ( ! $result['success'] ) {
            // Handle failure if needed
        //}
    }

}
