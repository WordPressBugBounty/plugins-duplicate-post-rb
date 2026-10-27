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

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- reads the children of the copied post once per copy

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contexts\CopyOperationContext;
use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\Helpers\Multisite;

/**
 * Copies child posts associated with a WordPress post.
 * Requires CopyOperationContext for all operations.
 * Fully Multisite-safe and WordPress.com compatible.
 */
class PostChildrenCopier {

    /**
     * Post types to ignore when copying children.
     */
    const IGNORE_TYPES = array( 'attachment', 'revision'); //'acf-field',

    /**
     * Copies all child posts from source to target post.
     *
     * @param CopyOperationContext $operation
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    /** the posts whose children are being copied now, "blog:post": a parent loop stops at them */
    private static $in_progress = array();

    /**
     * the copies this copy has made so far, "blog:post": on the same site a copy keeps its
     * parent, so in a parent loop it shows up among the children still to be copied
     */
    private static $made = array();

    public static function copy_children(CopyOperationContext $operation, int $profile_id = 0 ): array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        // a post among its own descendants (post_parent pointing in a circle) is not copied again
        $key = (int) $source->get_blog_id() . ':' . (int) $source->get_post_id();
        if ( isset( self::$in_progress[ $key ] ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        $child_ids = self::get_child_post_ids( $source );
        if ( empty( $child_ids ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        $copied = array();
        $failed = 0;

        if ( $profile_id === 0 ) {
            $profile_id = Profile::getDefaultProfileId();
        }

        $outermost = ! self::$in_progress;
        self::$in_progress[ $key ] = true;
        self::$made[ (int) $target->get_blog_id() . ':' . (int) $target->get_post_id() ] = true;
        try {
            foreach ( $child_ids as $child_id ) {
                $child_key = (int) $source->get_blog_id() . ':' . (int) $child_id;
                if ( isset( self::$in_progress[ $child_key ] ) || isset( self::$made[ $child_key ] ) ) {
                    continue;
                }
                $new_id = self::copy_single_child( $child_id, $source, $target, $profile_id );
                if ( $new_id ) {
                    $copied[] = $new_id;
                } else {
                    $failed++;
                }
            }
        } finally {
            unset( self::$in_progress[ $key ] );
            if ( $outermost ) {
                self::$made = array();
            }
        }

        $success = ( $failed === 0 );
        return self::build_result( $success, count( $copied ), $failed, $copied );
    }

    /**
     * Retrieves child post IDs for the source context.
     *
     * @param BlogPostContext $source
     * @return array<int>
     */
    private static function get_child_post_ids( $source ) {
        $did_switch = $source->maybe_switch_to_blog();
        
        $post_types = get_post_types();
        $children = get_children( array(
            'post_parent' => $source->get_post_id(),
            'post_type'   => $post_types,
            'post_status' => 'any',
        ) );

        $child_ids = array();
        foreach ( $children as $child ) {
            if ( ! self::should_ignore_child( $child ) ) {
                $child_ids[] = $child->ID;
            }
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return $child_ids;
    }

    /**
     * Copies a single child post using the DuplicatorFactory.
     *
     * @param int $child_id
     * @param BlogPostContext $target
     * @param int $profile_id
     * @return int|false
     */
    private static function copy_single_child( $child_id, BlogPostContext $source, BlogPostContext $target, $profile_id ) {
        // the child comes from the parent's site and goes where the parent's copy is
        // (an ID means another post on each site of a network); one child that fails
        // is counted, the others and the parent's copy go on
        try {
            $new_id = DuplicatorFactory::make( 'post' )->duplicate(
                (int) $child_id,
                (int) $profile_id,
                (int) $target->get_blog_id(),
                (int) $source->get_blog_id()
            );
        } catch ( \Exception $e ) {
            return false;
        }

        if ( ! $new_id ) {
            return false;
        }

        // Update the parent of the new child to point to the target post
        $success = self::update_child_parent( $new_id, (int) $target->get_post_id(), (int) $target->get_blog_id() );
        if ( ! $success ) {
            // Optionally delete the failed child
            return false;
        }

        return $new_id;
    }

    /**
     * Updates the post_parent of a child post.
     *
     * @param int $child_id
     * @param int $new_parent_id
     * @return bool
     */
    /**
     * @param int $blog_id the site both posts are on (0 = current blog)
     */
    public static function update_child_parent( $child_id, $new_parent_id, int $blog_id = 0 ) {
        return (bool) Multisite::with_blog( $blog_id, function () use ( $child_id, $new_parent_id ) {
            global $wpdb;
            $post = \get_post( $child_id );
            if ( ! $post ) {
                return false;
            }

            if ( $new_parent_id !== 0 ) {
                $parent = \get_post( $new_parent_id );
                if ( ! $parent ) {
                    return false;
                }
            }

            // only the parent changes: wp_update_post() would also run the save hooks and filters again
            $result = $wpdb->update(
                $wpdb->posts,
                array('post_parent' => $new_parent_id),
                array('ID' => $child_id),
                array('%d'),
                array('%d')
            );
            // the row is changed under WordPress: a persistent object cache would keep the old parent
            clean_post_cache( (int) $child_id );

            // 0 rows: the parent was already right
            return $result !== false;
        } );
    }

    /**
     * Determines if a child post should be ignored.
     *
     * @param \WP_Post $child
     * @return bool
     */
    private static function should_ignore_child( $child ) {
        if ( in_array( $child->post_type, self::IGNORE_TYPES, true ) ) {
            return true;
        }
        if ( $child->post_status === 'trash' ) {
            return true;
        }
        return false;
    }

    /**
     * Helper to build a standard result array.
     */
    private static function build_result( bool $success, int $copied = 0, int $failed = 0, array $ids = array() ): array {
        return array(
            'success' => $success,
            'copied'  => $copied,
            'failed'  => $failed,
            'ids'     => $ids,
        );
    }
}