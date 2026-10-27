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

use rbDuplicatePost\Constants;
use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\TransformerContext;

class HistoryTransformer extends AbstractPostAfterCopyTransformer {

     protected static function get_option_name(): string{
        return '';
    }

    public function transform(TransformerContext $context)  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        $source = $context->get_source();
        $target = $context->get_target();
        $post_source_id = $source->get_post_id();
        $post_new_id = $target->get_post_id();
        $profile_id = $context->get_profile_id();

        // the source's title on its own site, the history on the copy's site (they differ on a network)
        $switched = $source->maybe_switch_to_blog();
        $title = \get_the_title($post_source_id);
        BlogPostContext::maybe_restore_blog( $switched );

        $historyData = Array(
            'post_source_id' => $post_source_id,
            'post_source_title' => $title,
            'post_source_blog_id' => $source->get_blog_id(),
            'profile_id' => $profile_id,
            'date' =>  current_time('mysql', true),
        );

        $switched = $target->maybe_switch_to_blog();
        \update_post_meta( $post_new_id, Constants::HISTORY_META_KEY, $historyData );
        BlogPostContext::maybe_restore_blog( $switched );
    }
}
