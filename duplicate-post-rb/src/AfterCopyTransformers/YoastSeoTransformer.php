<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
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
use rbDuplicatePost\Copiers\YoastSeoCopier;

class YoastSeoTransformer extends AbstractPostAfterCopyTransformer {

    //this transformer  needed all the time
     protected static function get_option_name(): string{
        return 'enableTypeYoastSeo';
    }

    public function transform(TransformerContext $context)  {
        if ( !self::supports( $context ) ) {
            return ;
        }

        // $post_new_id = $context->get_target()->get_post_id();
        // $post_type = $context->get_target()->get_post_type();

        $args = array(
            'overwrite' => true,
            'term_map' => array(),
        );

        YoastSeoCopier::copy($context->to_operation_context(), $args);

    }
}
