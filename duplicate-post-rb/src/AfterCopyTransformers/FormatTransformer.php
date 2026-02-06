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

class FormatTransformer extends AbstractPostAfterCopyTransformer{

    protected static function get_option_name(): string{
        return 'copyFormat';
    }

    public function transform(TransformerContext $context):void  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            return ;
        }

        $post_new_id = $context->get_target()->get_post_id();
        $post_source_id = $context->get_source()->get_post_id();

        $isReFormat = \set_post_format($post_new_id, get_post_format($post_source_id));
    }
}
