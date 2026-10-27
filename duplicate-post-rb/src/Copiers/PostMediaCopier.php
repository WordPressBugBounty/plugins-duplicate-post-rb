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
 * Copies media associated with a WordPress post.
 */
class PostMediaCopier {

    /**
     * Postmeta key written on a copied attachment to record which source
     * attachment it was copied from. Used to detect that a given source
     * attachment has already been copied to the target post within the
     * current operation, so we don't create a second physical copy of the
     * same file (e.g. a file that is both the featured image and referenced
     * by another plugin's postmeta, such as Yoast SEO's OpenGraph image).
     */
    const SOURCE_ATTACHMENT_ID_META_KEY = '_rb_duplicate_post_source_attachment_id';

    /**
     * Postmeta key written alongside SOURCE_ATTACHMENT_ID_META_KEY to record
     * which blog the source attachment came from (Multisite-safe matching).
     */
    const SOURCE_BLOG_ID_META_KEY = '_rb_duplicate_post_source_blog_id';

    /**
     * Copies only the featured image using a copy operation context.
     *
     * @param CopyOperationContext $operation
     * @param bool $duplicate_on_same_blog See copy_attachment_by_id().
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_featured_image( CopyOperationContext $operation, bool $duplicate_on_same_blog = false ): array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        $thumb_id = $source->get_featured_image_id();
        if ( ! $thumb_id ) {
            return self::build_result(true );
        }

        $new_id = self::copy_attachment_by_id( $thumb_id, $operation, $duplicate_on_same_blog );
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
     * @param bool $duplicate_on_same_blog See copy_attachment_by_id().
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_all_attachments( CopyOperationContext $operation, bool $duplicate_on_same_blog = false ) : array {

        $source = $operation->get_source();
        $target = $operation->get_target();

        $attachment_ids = $source->get_child_attachment_ids();
        if ( empty( $attachment_ids ) ) {
            return self::build_result( true );
        }

        $copied = array();
        $failed = 0;

        foreach ( $attachment_ids as $att_id ) {
            $new_id = self::copy_attachment_by_id( $att_id, $operation, $duplicate_on_same_blog );
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
     * @param bool $duplicate_on_same_blog See copy_attachment_by_id().
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     ids: array<int>
     * }
     */
    public static function copy_all_media(CopyOperationContext $operation, bool $duplicate_on_same_blog = false ): array {

        $attachments_result = self::copy_all_attachments( $operation, $duplicate_on_same_blog );

        // Check if source actually had a featured image
        $source = $operation->get_source();

        $total_copied = $attachments_result['copied'];
        $total_failed = $attachments_result['failed'];

         $total_ids =  $attachments_result['ids'] ;


        $has_featured = $source->get_featured_image_id() !== null;
        if( $has_featured ){
            $featured_result = self::copy_featured_image( $operation, $duplicate_on_same_blog );
            $total_copied += $featured_result['copied'];
            $total_failed += $featured_result['failed'];
            if ( ! empty( $featured_result['ids'] ) ) {
                $total_ids = array_merge( $total_ids, $featured_result['ids'] );
            }
        } 
        
        $success = ( $total_failed === 0 );
        return self::build_result( $success, $total_copied, $total_failed, $total_ids );
    }

    /**
     * Copies a single attachment identified by its source ID, reusing an
     * existing copy already created for the target post within this
     * operation instead of duplicating the same file twice.
     *
     * Use this method (rather than any post_parent-based enumeration) when
     * the attachment ID comes from an arbitrary source — e.g. a meta value
     * owned by another plugin, such as Yoast SEO's OpenGraph/Twitter image
     * ID — since that attachment is not guaranteed to be a child attachment
     * of the source post and is not guaranteed to be the featured image.
     *
     * @param int $source_attachment_id Attachment ID on the source blog.
     * @param CopyOperationContext $operation
     * @param bool $duplicate_on_same_blog When true (default), always
     *        creates a new physical copy of the file, even if source and
     *        target are on the same blog — matches the plugin's original
     *        behaviour and is appropriate when the copy is meant to be
     *        independently editable (e.g. a post's own featured image).
     *        When false AND source/target share the same blog, the source
     *        attachment ID is reused as-is — no new file or attachment
     *        post is created. Appropriate for assets that are simply
     *        referenced, not "owned" by the post, such as an SEO plugin's
     *        OpenGraph/Twitter image. Has no effect across blogs — a
     *        Multisite cross-blog copy always requires a physical copy,
     *        since attachment IDs are not shared between blogs.
     * @return int|false Attachment ID on the target blog (new or reused), or false on failure.
     */
    public static function copy_attachment_by_id( int $source_attachment_id, CopyOperationContext $operation, bool $duplicate_on_same_blog = true ) {

        if ( $source_attachment_id <= 0 ) {
            return false;
        }

        $source = $operation->get_source();
        $target = $operation->get_target();

        if ( ! $duplicate_on_same_blog && $source->get_blog_id() === $target->get_blog_id() ) {
            return self::resolve_same_blog_attachment( $source_attachment_id, $source );
        }

        $existing_id = self::find_existing_copied_attachment( $source_attachment_id, $source->get_blog_id(), $target );
        if ( $existing_id ) {
            return $existing_id;
        }

        return self::copy_single_attachment( $source_attachment_id, $source, $target );
    }

    // ─────────────────────── Internal Helpers ───────────────────────

    /**
     * Validates and returns a source attachment ID for direct reuse on the
     * same blog — no physical copy is made, since attachment IDs are valid
     * and shared across posts within one blog. Only confirms the
     * attachment still exists, so callers don't end up with a dangling ID.
     *
     * @param int $source_attachment_id
     * @param BlogPostContext $source
     * @return int|false
     */
    private static function resolve_same_blog_attachment( int $source_attachment_id, BlogPostContext $source ) {

        $did_switch = $source->maybe_switch_to_blog();
        $attachment = \get_post( $source_attachment_id );
        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
            return false;
        }

        return $source_attachment_id;
    }

    /**
     * Looks for an attachment already copied onto the target post for the
     * given source attachment/blog combination, to avoid creating a second
     * physical copy of the same file within one duplication operation.
     *
     * Scoped to attachments whose post_parent is the target post, since
     * that's where copy_single_attachment() always attaches its copies —
     * this keeps the check local to the current operation rather than
     * matching unrelated copies elsewhere on the site.
     *
     * @param int $source_attachment_id
     * @param int $source_blog_id
     * @param BlogPostContext $target
     * @return int|false
     */
    private static function find_existing_copied_attachment( int $source_attachment_id, int $source_blog_id, BlogPostContext $target ) {

        $did_switch = $target->maybe_switch_to_blog();

        $found = \get_posts( array(
            'post_type'      => 'attachment',
            'post_parent'    => $target->get_post_id(),
            'post_status'    => 'any',
            'numberposts'    => 1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- finds one attachment by its source key (numberposts 1, ids only)
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'   => self::SOURCE_ATTACHMENT_ID_META_KEY,
                    'value' => $source_attachment_id,
                ),
                array(
                    'key'   => self::SOURCE_BLOG_ID_META_KEY,
                    'value' => $source_blog_id,
                ),
            ),
        ) );

        BlogPostContext::maybe_restore_blog( $did_switch );

        return ! empty( $found ) ? (int) $found[0] : false;
    }

    /**
     * Copies a single attachment from source to target context.
     * Always creates a new physical copy — callers that want de-duplication
     * within an operation should use copy_attachment_by_id() instead.
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
            wp_delete_file( $new_filepath );
            BlogPostContext::maybe_restore_blog( $did_switch_target );
            return false;
        }

        // Ensure image functions are available
        if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $meta = \wp_generate_attachment_metadata( $new_id, $new_filepath );
        \wp_update_attachment_metadata( $new_id, $meta );

        // Record provenance so future calls within this operation can detect
        // that this source attachment has already been copied (see
        // find_existing_copied_attachment()).
        \update_post_meta( $new_id, self::SOURCE_ATTACHMENT_ID_META_KEY, $attachment_id );
        \update_post_meta( $new_id, self::SOURCE_BLOG_ID_META_KEY, $source->get_blog_id() );

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
