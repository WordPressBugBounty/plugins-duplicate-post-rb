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
use rbDuplicatePost\Copiers\PostMediaCopier;
use rbDuplicatePost\Helpers\Multisite;

class AttachmentsTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'copyAttachments';
    }

    public function transform( TransformerContext $context ) {

        if ( ! self::supports( $context ) ) {
            return;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            return;
        }

        $options = $context->get_options();
        $ms      = isset( $options['_rb_ms'] ) && is_array( $options['_rb_ms'] ) ? $options['_rb_ms'] : array();

        $source_blog = isset( $ms['source_blog_id'] ) ? (int) $ms['source_blog_id'] : $context->get_source()->get_blog_id();
        $target_blog = isset( $ms['target_blog_id'] ) ? (int) $ms['target_blog_id'] : $context->get_target()->get_blog_id();

        // Cross-site link mode: skip physical attachment copy.
        if ( $source_blog !== $target_blog && Multisite::get_media_mode() === Multisite::MEDIA_MODE_LINK ) {
            return;
        }

        $duplicate_flag = ! empty( $ms['media_flag'] );
        PostMediaCopier::copy_all_attachments( $context->to_operation_context(), $duplicate_flag );
    }
}
