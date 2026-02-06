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

class TagsTransformer  extends AbstractPostTransformer  {

    protected static function get_option_name(): string{
        return 'copyTags';
    }

    public function transform( TransformerContext $context, array $post_data ): array {

        if ( !self::supports( $context ) ) {
            return $post_data;
        }

        if ( ! self::is_copy_enabled( $context->get_options() ) || empty( $post_data[ 'tags_input' ] ) ) {
            $post_data[ 'tags_input' ] = array  ();
            return $post_data;
        }

        //   $prepare_categories = copyCategories($post_data['post_category']);
        //   $post_data['post_category'] = array_values(array_unique(array_merge($prepare_categories))); // need to check if it's correct
        
        return $post_data;
    }

    /**
     * copy_categories - copy and recreate categories
     *
     * TODO : check working with multisite
     * @param  array  $cat_ids   list of categore ids
     * @return array $format_return id or ids of categories
     */
    // private static function copyCategories($cat_ids, $format_return = 'array') { 
    //     if ($format_return == 'array') {
    //       return [ 'ids' => $cat_ids ];
    //     } else return $cat_ids;
    // }
}
