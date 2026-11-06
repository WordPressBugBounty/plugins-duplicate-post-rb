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

namespace rbDuplicatePost\AfterCopyTransformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Copiers\PostChildrenCopier;

class ChildrenTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string{
        return 'copyChildren';
    }

     public function transform(TransformerContext $context) {

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

        if ( ! empty( $result['failed'] ) ) {
            \rbDuplicatePost\Scenario\Scenario::warn( sprintf(
                /* translators: %d: how many */
                _n( '%d child post was not copied.', '%d child posts were not copied.', (int) $result['failed'], 'duplicate-post-rb' ),
                (int) $result['failed']
            ) );
        }
    }

}
