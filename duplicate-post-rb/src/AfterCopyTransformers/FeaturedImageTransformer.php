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
use rbDuplicatePost\Copiers\PostMediaCopier;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Helpers\Multisite;

class FeaturedImageTransformer extends AbstractPostAfterCopyTransformer {

    protected static function get_option_name(): string {
        return 'copyFeaturedImage';
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

        // Cross-site + link mode: do not create new files; leave featured image empty
        // or only set if shared uploads make source ID valid on target (rare).
        if ( $source_blog !== $target_blog && Multisite::get_media_mode() === Multisite::MEDIA_MODE_LINK ) {
            // Link mode across sites: try to set the same attachment ID only if
            // the target can resolve it (shared media library). Otherwise skip.
            $src = $context->get_source();
            $did_src = $src->maybe_switch_to_blog();
            $source_thumb = (int) get_post_thumbnail_id( $src->get_post_id() );
            $source_file  = $source_thumb > 0 ? (string) get_post_meta( $source_thumb, '_wp_attached_file', true ) : '';
            \rbDuplicatePost\Contexts\BlogPostContext::maybe_restore_blog( $did_src );
            if ( $source_thumb > 0 ) {
                $did = $context->get_target()->maybe_switch_to_blog();
                // Reuse the ID only when the target site has that very image (a shared
                // media library): the same ID there is otherwise another post entirely.
                if ( 'attachment' === get_post_type( $source_thumb ) && '' !== $source_file
                    && (string) get_post_meta( $source_thumb, '_wp_attached_file', true ) === $source_file ) {
                    set_post_thumbnail( $context->get_target()->get_post_id(), $source_thumb );
                }
                \rbDuplicatePost\Contexts\BlogPostContext::maybe_restore_blog( $did );
            }
            return;
        }

        $duplicate_flag = ! empty( $ms['media_flag'] );
        PostMediaCopier::copy_featured_image( $context->to_operation_context(), $duplicate_flag );
    }
}
