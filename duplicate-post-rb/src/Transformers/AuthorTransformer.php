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

namespace rbDuplicatePost\Transformers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contracts\AbstractPostTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\User;
use rbDuplicatePost\Helpers\Multisite;

class AuthorTransformer extends AbstractPostTransformer {

    protected static function get_option_name(): string{
        return 'copyAuthor';
    }

    public function transform( TransformerContext $context,  array $post_data): array {

        if ( ! self::supports( $context) ) {
            return $post_data;
        }

        if ( self::is_copy_enabled( $context->get_options() ) && self::may_keep_author( $context, $post_data ) ) {
            return $post_data;
        }

        $current_user_id = User::get_user_id();
        if( !$current_user_id ) {
            $current_user_id = User::get_admin_user_id() ?: 1; // 1 - for wp-cli if no admin found
        }

        $post_data['post_author'] = $current_user_id;

        return $post_data;
    }

    /**
     * A copy keeps someone else as its author only for a user who may edit others' posts of
     * its type on the site it is made on - as WordPress's editor lets nobody else change the
     * author. Without a user (cron, WP-CLI) the settings decide.
     */
    private static function may_keep_author( TransformerContext $context, array $post_data ): bool {
        $user_id = User::get_user_id();
        if ( ! $user_id || (int) ( $post_data['post_author'] ?? 0 ) === (int) $user_id ) {
            return true;
        }
        $type = (string) ( $post_data['post_type'] ?? 'post' );
        return (bool) Multisite::with_blog( (int) $context->get_target()->get_blog_id(), function () use ( $type ) {
            $object = get_post_type_object( $type );
            return current_user_can( $object ? $object->cap->edit_others_posts : 'edit_others_posts' );
        } );
    }
}
