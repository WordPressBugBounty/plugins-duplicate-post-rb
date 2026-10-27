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

namespace rbDuplicatePost\Copiers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\CopyOperationContext;

/**
 * Copies the comments of a post to its copy: approved and waiting ones, with
 * their replies kept under the right parent and their comment meta.
 * Spam and trash stay behind.
 */
class PostCommentsCopier {

    /**
     * @return array{success: bool, copied: int, failed: int}
     */
    public static function copy_comments( CopyOperationContext $operation ): array {
        $source = $operation->get_source();
        $target = $operation->get_target();

        $switched = $source->maybe_switch_to_blog();
        $comments = get_comments( array(
            'post_id' => $source->get_post_id(),
            'status'  => array( 'approve', 'hold' ),
            'orderby' => 'comment_ID',
            'order'   => 'ASC',
            'type'    => 'all',
        ) );
        $metas = array();
        foreach ( $comments as $comment ) {
            $metas[ $comment->comment_ID ] = get_comment_meta( $comment->comment_ID );
        }
        BlogPostContext::maybe_restore_blog( $switched );

        if ( empty( $comments ) ) {
            return array( 'success' => true, 'copied' => 0, 'failed' => 0 );
        }

        $map    = array();
        $failed = 0;

        $switched = $target->maybe_switch_to_blog();
        foreach ( $comments as $comment ) {
            $parent = (int) $comment->comment_parent;
            $data   = array(
                'comment_post_ID'      => (int) $target->get_post_id(),
                'comment_author'       => $comment->comment_author,
                'comment_author_email' => $comment->comment_author_email,
                'comment_author_url'   => $comment->comment_author_url,
                'comment_author_IP'    => $comment->comment_author_IP,
                'comment_date'         => $comment->comment_date,
                'comment_date_gmt'     => $comment->comment_date_gmt,
                'comment_content'      => $comment->comment_content,
                'comment_karma'        => (int) $comment->comment_karma,
                'comment_approved'     => $comment->comment_approved,
                'comment_agent'        => $comment->comment_agent,
                'comment_type'         => $comment->comment_type,
                // a reply whose parent was not copied (spam, trash) becomes a top-level comment
                'comment_parent'       => $parent && isset( $map[ $parent ] ) ? $map[ $parent ] : 0,
                'user_id'              => (int) $comment->user_id,
            );
            // wp_insert_comment() unslashes what it gets
            $new_id = wp_insert_comment( wp_slash( $data ) );
            if ( ! $new_id ) {
                ++$failed;
                continue;
            }
            $map[ (int) $comment->comment_ID ] = (int) $new_id;
            foreach ( (array) $metas[ $comment->comment_ID ] as $key => $values ) {
                foreach ( $values as $value ) {
                    add_comment_meta( $new_id, $key, wp_slash( maybe_unserialize( $value ) ) );
                }
            }
        }
        wp_update_comment_count_now( (int) $target->get_post_id() );
        BlogPostContext::maybe_restore_blog( $switched );

        return array( 'success' => 0 === $failed, 'copied' => count( $map ), 'failed' => $failed );
    }
}
