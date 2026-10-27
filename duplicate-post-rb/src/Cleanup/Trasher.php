<?php
/*
*      RB Duplicate Post
*      Version: 2.0.0
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Cleanup;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Log\Actions;
use rbDuplicatePost\Log\Logger;

/**
 * Removes posts found by a cleanup: to the trash, or for good. Every removal
 * is written to the copy log.
 */
class Trasher {

    /**
     * @param int[] $ids
     * @param bool  $permanently delete for good instead of moving to the trash
     * @param bool  $check_caps  check delete_post for the current user (off for the scheduled cleanup)
     * @param array $context     'keep' => [removed id => id of the post that stays], 'redirect' => bool:
     *                           passed to the rb_duplicate_post_before_remove action (the PRO key
     *                           redirects the removed post's address to the one that stays)
     * @return array{done: int[], failed: int[]}
     */
    public static function remove( array $ids, bool $permanently = false, bool $check_caps = true, array $context = array() ): array {
        $done   = array();
        $failed = array();
        foreach ( array_unique( array_map( 'intval', $ids ) ) as $id ) {
            $post = $id > 0 ? get_post( $id ) : null;
            if ( ! $post || $post->post_type === RB_DUPLICATE_POST_PROFILE_TYPE_POST ) {
                $failed[] = $id;
                continue;
            }
            if ( $check_caps && ! current_user_can( 'delete_post', $id ) ) {
                $failed[] = $id;
                continue;
            }
            $title = $post->post_title;
            $keep  = isset( $context['keep'][ $id ] ) ? (int) $context['keep'][ $id ] : 0;

            /**
             * A duplicate is about to go: its address still works here.
             *
             * @param int   $id       the post being removed
             * @param int   $keep     the post of its group that stays, 0 when unknown
             * @param bool  $redirect whether the person (or the auto cleanup setting) asked for a redirect
             * @param bool  $permanently
             */
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
            do_action( 'rb_duplicate_post_before_remove', $id, $keep, ! empty( $context['redirect'] ), $permanently );

            $result = $permanently ? wp_delete_post( $id, true ) : wp_trash_post( $id );
            if ( $result ) {
                $done[] = $id;
                Logger::add( $permanently ? Actions::DELETE : Actions::DELETE_TO_TRASH, $id, 0, 0, array( 'title' => $title, 'type' => $post->post_type ) );
            } else {
                $failed[] = $id;
            }
        }
        return array( 'done' => $done, 'failed' => $failed );
    }
}
