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
use rbDuplicatePost\Copiers\PostMetaCopier;

class MetaTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string{
        return 'copyAllPostMeta';
    }

    /**
     * Transform the post by copying attachments
     *
     * @param TransformerContext $context
     * @return void
     */
    public function transform( TransformerContext $context ): void {

        if ( ! self::supports( $context ) ) {
            return;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            return;
        }

        $result = PostMetaCopier::copy_post_all_meta( $context->to_operation_context() );
    }
}