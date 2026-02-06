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

namespace rbDuplicatePost\Copiers;

use rbDuplicatePost\Contexts\CopyOperationContext;
use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Profile\Profile;

/**
 * Copies child posts associated with a WordPress post.
 * Requires CopyOperationContext for all operations.
 * Fully Multisite-safe and WordPress.com compatible.
 */
class PostChildrenCopier {

    /**
     * Post types to ignore when copying children.
     */
    const IGNORE_TYPES = array('acf-field', 'attachment', 'revision');

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
    public static function copy_children(CopyOperationContext $operation, int $profile_id = 0 ): array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        $child_ids = self::get_child_post_ids( $source );
        if ( empty( $child_ids ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        $copied = array();
        $failed = 0;

        if ( $profile_id === 0 ) {
            $profile_id = Profile::getDefaultProfileId();
        }

        foreach ( $child_ids as $child_id ) {
            $new_id = self::copy_single_child( $child_id, $target, $profile_id );
            if ( $new_id ) {
                $copied[] = $new_id;
            } else {
                $failed++;
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
    private static function copy_single_child( $child_id, $target, $profile_id ) {
        // Create a temporary source context for the child
        $source_blog_id = $target->is_multisite() ? $target->get_blog_id() : get_current_blog_id();
        $child_source = new BlogPostContext( $child_id, $source_blog_id );
        
        // Use the factory to duplicate the child
        $duplicator = DuplicatorFactory::make( 'post' );
        $new_id = $duplicator->duplicate( $child_id, $profile_id );
        
        if ( ! $new_id ) {
            return false;
        }

        // Update the parent of the new child to point to the target post
        $success = self::update_child_parent( $new_id, $target->get_post_id() );
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
    public static function update_child_parent( $child_id, $new_parent_id ) {
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

        $result = \wp_update_post( array(
            'ID'          => $child_id,
            'post_parent' => $new_parent_id,
        ), true );

        return ! \is_wp_error( $result );
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