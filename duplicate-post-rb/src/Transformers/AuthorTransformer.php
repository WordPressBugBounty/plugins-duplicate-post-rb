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
use rbDuplicatePost\User;

class AuthorTransformer extends AbstractPostTransformer {

    protected static function get_option_name(): string{
        return 'copyAuthor';
    }

    public function transform( TransformerContext $context,  array $post_data): array {

        if ( ! self::supports( $context) ) {
            return $post_data;
        }

        if ( self::is_copy_enabled( $context->get_options() ) ) {
            return $post_data; 
        }

        $current_user_id = User::get_user_id();
        if( !$current_user_id ) {
            $current_user_id = User::get_admin_user_id() ?: 1; // 1 - for wp-cli if no admin found
        }

        $post_data['post_author'] = $current_user_id;

        return $post_data;
    }
}
