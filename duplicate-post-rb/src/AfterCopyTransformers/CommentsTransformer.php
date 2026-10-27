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
use rbDuplicatePost\Copiers\PostCommentsCopier;

class CommentsTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'copyComments';
    }

    public function transform( TransformerContext $context ) {

        if ( ! self::supports( $context ) ) {
            return;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            return;
        }

        PostCommentsCopier::copy_comments( $context->to_operation_context() );
    }
}
