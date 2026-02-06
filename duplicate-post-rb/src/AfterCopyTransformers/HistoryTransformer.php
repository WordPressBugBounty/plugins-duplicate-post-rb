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

use rbDuplicatePost\Constants;
use rbDuplicatePost\Contracts\AbstractPostAfterCopyTransformer;
use rbDuplicatePost\Contexts\TransformerContext;

class HistoryTransformer extends AbstractPostAfterCopyTransformer {

     protected static function get_option_name(): string{
        return '';
    }

    public function transform(TransformerContext $context):void  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        $post_source_id = $context->get_source()->get_post_id();
        $post_new_id = $context->get_target()->get_post_id();
        $blog_id = \get_current_blog_id();
        $profile_id = $context->get_profile_id();
        $title = \get_the_title($post_source_id);

        $historyData = Array(
            'post_source_id' => $post_source_id,
            'post_source_title' => $title,
            'post_source_blog_id' => $blog_id,
            'profile_id' => $profile_id,
            'date' =>  current_time('mysql', true),
        );

        \update_post_meta( $post_new_id, Constants::HISTORY_META_KEY, $historyData );
    }
}
