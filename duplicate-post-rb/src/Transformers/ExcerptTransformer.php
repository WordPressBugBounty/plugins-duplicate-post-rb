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

namespace rbDuplicatePost\Transformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostTransformer;
use rbDuplicatePost\Contexts\TransformerContext;

class ExcerptTransformer extends AbstractPostTransformer  {

    protected static function get_option_name(): string{
        return 'copyExcerpt';
    }

    public function transform( TransformerContext $context, array $post_data ): array {

        if ( ! self::supports( $context ) ) {
            return $post_data;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            $post_data[ 'post_excerpt' ] = '';
            return $post_data;
        }

        //$post_data[ 'post_excerpt' ] = $post_data[ 'post_excerpt' ];
        return $post_data;
    }
}
