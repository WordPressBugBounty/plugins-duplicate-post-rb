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

use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\CopyOperationContext;

/**
 * Copies media associated with a WordPress post.
 * Requires CopyOperationContext for all operations.
 * Fully Multisite-safe and WordPress.com compatible (no ABSPATH).
 */
class PostMediaCopier {

    /**
     * Copies only the featured image using a copy operation context.
     *
     * @param CopyOperationContext $operation
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_featured_image( CopyOperationContext $operation ): array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        $thumb_id = $source->get_featured_image_id();
        if ( ! $thumb_id ) {
            return self::build_result(true );
        }

        $new_id = self::copy_single_attachment( $thumb_id, $source, $target );
        if ( ! $new_id ) {
            return self::build_result(false );
        }

        // Assign as featured image in target context
        $did_switch = $target->maybe_switch_to_blog();
        \set_post_thumbnail( $target->get_post_id(), $new_id );
        BlogPostContext::maybe_restore_blog( $did_switch );

        return self::build_result( true, 1, 0, array( $new_id ) );
    }

    /**
     * Copies all child attachments (post_parent = source post).
     *
     * @param CopyOperationContext $operation
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_all_attachments( CopyOperationContext $operation ) : array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        $attachment_ids = $source->get_child_attachment_ids();
        if ( empty( $attachment_ids ) ) {
            return self::build_result( true );
        }

        $copied = array();
        $failed = 0;

        foreach ( $attachment_ids as $att_id ) {
            $new_id = self::copy_single_attachment( $att_id, $source, $target );
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
     * Copies all media: featured image + all child attachments.
     *
     * @param CopyOperationContext $operation
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_all_media(CopyOperationContext $operation ): array {

        $attachments_result = self::copy_all_attachments( $operation );

        // Check if source actually had a featured image
        $source = $operation->get_source();

        $total_copied = $attachments_result['copied'];
        $total_failed = $attachments_result['failed'];

         $total_ids =  $attachments_result['ids'] ;


        $has_featured = $source->get_featured_image_id() !== null;
        if( $has_featured ){
            $featured_result = self::copy_featured_image( $operation );
            $total_copied += $featured_result['copied'];
            $total_failed += $featured_result['failed'];
            if ( ! empty( $featured_result['ids'] ) ) {
                $total_ids = array_merge( $total_ids, $featured_result['ids'] );
            }
        } 
        
        $success = ( $total_failed === 0 );
        return self::build_result( $success, $total_copied, $total_failed, $total_ids );
    }

    // ─────────────────────── Internal Helpers ───────────────────────

    /**
     * Copies a single attachment from source to target context.
     *
     * @param int $attachment_id
     * @param BlogPostContext $source
     * @param BlogPostContext $target
     * @return int|false
     */
    private static function copy_single_attachment( $attachment_id, $source, $target ) {

        $did_switch_source = $source->maybe_switch_to_blog();
        $file_path = \get_attached_file( $attachment_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            BlogPostContext::maybe_restore_blog( $did_switch_source );
            return false;
        }

        $data = array(
            'file_path'      => $file_path,
            'mime_type'      => \get_post_mime_type( $attachment_id ),
            'title'          => \get_the_title( $attachment_id ),
            'excerpt'        => \get_post_field( 'post_excerpt', $attachment_id ),
            'content'        => \get_post_field( 'post_content', $attachment_id ),
        );
        BlogPostContext::maybe_restore_blog( $did_switch_source );

        // ——— Create in target ———
        $did_switch_target = $target->maybe_switch_to_blog();
        $upload = wp_upload_dir();
        if ( ! empty( $upload['error'] ) ) {
            BlogPostContext::maybe_restore_blog( $did_switch_target );
            return false;
        }

        $basename = basename( $data['file_path'] );
        $new_filename = \wp_unique_filename( $upload['path'], $basename );
        $new_filepath = $upload['path'] . '/' . $new_filename;

        if ( ! copy( $data['file_path'], $new_filepath ) ) {
            BlogPostContext::maybe_restore_blog( $did_switch_target );
            return false;
        }

        $attachment_args = array(
            'post_title'     => $data['title'],
            'post_excerpt'   => $data['excerpt'],
            'post_content'   => $data['content'],
            'post_status'    => 'inherit',
            'post_mime_type' => $data['mime_type'],
            'guid'           => $upload['url'] . '/' . $new_filename,
        );

        $new_id = \wp_insert_attachment( $attachment_args, $new_filepath, $target->get_post_id(), true );

        if ( \is_wp_error( $new_id ) ) {
            @unlink( $new_filepath );
            BlogPostContext::maybe_restore_blog( $did_switch_target );
            return false;
        }

        // Ensure image functions are available
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        // Generate thumbnails and metadata (safe without ABSPATH)
        $meta = \wp_generate_attachment_metadata( $new_id, $new_filepath );
        \wp_update_attachment_metadata( $new_id, $meta );

        BlogPostContext::maybe_restore_blog( $did_switch_target );
        return (int) $new_id;
    }

    /**
     * Helper to build a standard result array for attachments copy.
     */
    private static function build_result( $success, $copied = 0, $failed = 0, $ids = array() ) {
        return array(
            'success'        => $success,
            'copied'         => $copied,
            'failed'         => $failed,
            'ids' => is_array($ids) ?  $ids : array(),
        );
    }
}