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
use rbDuplicatePost\Copiers\PostTermCopier;

class CategoriesTransformer extends AbstractPostTransformer {

    protected static function get_option_name(): string{
        return 'copyCategories';
    }

    public function transform( TransformerContext $context, array $post_data  ): array {

        if ( ! self::supports( $context ) ) {
            return $post_data;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) ) {
            $post_data[ 'post_category' ] = array();
            return $post_data;
        }

        if( !isset($post_data['post_category']) || empty($post_data['post_category'])) {
            $post_data[ 'post_category' ] = array();
            return $post_data;
        }

        $prepare_categories = PostTermCopier::copy_terms(
            $post_data['post_category'],
            'category',
            $context->get_source(),
            $context->get_target()
        );

        $post_data['post_category'] = array_values(array_unique($prepare_categories));

        return $post_data;
    }
}
