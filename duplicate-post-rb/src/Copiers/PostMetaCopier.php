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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\CopyOperationContext;

/**
 * Copies post meta (custom fields) between posts in Multisite.
 * Handles serialized data safely by relying on WordPress core behavior.
 */
class PostMetaCopier {

    /**
     * List of meta keys to exclude from copying (protected/system keys).
     */
    const EXCLUDED_META_KEYS = array(
        '_edit_last',
        '_edit_lock',
        '_wp_old_slug',
        '_wp_old_date',
        '_wp_attachment_metadata',
        '_wp_attached_file',
        'rb-duplicate-post-history',
        //'_thumbnail_id',
        //'_wp_page_template',
        // Add your plugin-specific protected keys here
    );

    /**
     * Copies ALL post meta from source to target post (excluding protected keys).
     * This is the main method for full meta duplication.
     *
     * @param CopyOperationContext $operation The copy operation context.
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     meta_keys: array<string>
     * }
     */
    public static function copy_post_all_meta(  CopyOperationContext $operation ): array {
        $source = $operation->get_source();
        $target = $operation->get_target();

        // Prevent self-copy
        if ( $source->get_blog_id() === $target->get_blog_id() && 
             $source->get_post_id() === $target->get_post_id() ) {
            return self::build_result( true, 0, 0, array() );
        }

        $source_meta = self::get_all_post_meta( $source );
        if ( empty( $source_meta ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        // Filter out excluded meta keys (no allowed_keys filter - copy all except excluded)
        $meta_to_copy = self::filter_meta_keys_for_full_copy( $source_meta );

        if ( empty( $meta_to_copy ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        $copied_keys = array();
        $failed_count = 0;

        foreach ( $meta_to_copy as $meta_key => $meta_values ) {
            $success = self::copy_single_meta_key( 
                $meta_key, 
                $meta_values, 
                $target
            );
            
            if ( $success ) {
                $copied_keys[] = $meta_key;
            } else {
                $failed_count++;
            }
        }

        $success = ( $failed_count === 0 );
        return self::build_result( $success, count( $copied_keys ), $failed_count, $copied_keys );
    }

    /**
     * Copies specific post meta from source to target post.
     *
     * @param BlogPostContext $source Source post context.
     * @param BlogPostContext $target Target post context.
     * @param array $allowed_keys Optional list of meta keys to copy (if empty, copies all except excluded).
     * @return array{
     *     success: bool,
     *     copied: int,
     *     failed: int,
     *     meta_keys: array<string>
     * }
     */
    public static function copy_post_meta( 
        BlogPostContext $source, 
        BlogPostContext $target,
        array $allowed_keys = array()
    ): array {
        // Prevent self-copy
        if ( $source->get_blog_id() === $target->get_blog_id() && 
             $source->get_post_id() === $target->get_post_id() ) {
            return self::build_result( true, 0, 0, array() );
        }

        $source_meta = self::get_all_post_meta( $source );
        if ( empty( $source_meta ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        // Filter meta keys with allowed_keys restriction
        $meta_to_copy = self::filter_meta_keys( $source_meta, $allowed_keys );

        if ( empty( $meta_to_copy ) ) {
            return self::build_result( true, 0, 0, array() );
        }

        $copied_keys = array();
        $failed_count = 0;

        foreach ( $meta_to_copy as $meta_key => $meta_values ) {
            $success = self::copy_single_meta_key( 
                $meta_key, 
                $meta_values, 
                $target
            );
            
            if ( $success ) {
                $copied_keys[] = $meta_key;
            } else {
                $failed_count++;
            }
        }

        $success = ( $failed_count === 0 );
        return self::build_result( $success, count( $copied_keys ), $failed_count, $copied_keys );
    }

    /**
     * Filters meta keys for full copy (only excludes protected keys, no allowed_keys filter).
     */
    private static function filter_meta_keys_for_full_copy( array $meta ): array {
        $filtered = array();
        $excluded_keys = self::get_excluded_meta_keys();

        foreach ( $meta as $key => $values ) {
            // Skip excluded keys
            if ( in_array( $key, $excluded_keys, true ) ) {
                continue;
            }

            // Skip internal WordPress keys that are likely system-related
            // (optional - you can uncomment if needed)
            // if ( strpos( $key, '_' ) === 0 && ! self::is_allowed_internal_key( $key ) ) {
            //     continue;
            // }

            $filtered[ $key ] = $values;
        }

        return $filtered;
    }

    /**
     * Filters meta keys based on allowed list and exclusions.
     */
    private static function filter_meta_keys( array $meta, array $allowed_keys ): array {
        $filtered = array();

        $allowed_keys = self::prepare_allowed_meta_keys($allowed_keys);

        $excluded_keys = self::get_excluded_meta_keys();

        foreach ( $meta as $key => $values ) {
            // Skip excluded keys
            if ( in_array( $key, $excluded_keys, true ) ) {
                continue;
            }

            // If allowed_keys specified, skip keys not in the list
            if ( ! empty( $allowed_keys ) && ! in_array( $key, $allowed_keys, true ) ) {
                continue;
            }

            // Skip internal WordPress keys (starting with _)
            // Uncomment if you want to exclude ALL internal keys
            // if ( strpos( $key, '_' ) === 0 && ! in_array( $key, self::ALLOWED_INTERNAL_KEYS, true ) ) {
            //     continue;
            // }

            $filtered[ $key ] = $values;
        }

        return $filtered;
    }

    /**
     * Gets all post meta from source context.
     */
    private static function get_all_post_meta( BlogPostContext $source ): array {
        $did_switch = $source->maybe_switch_to_blog();
        $meta = get_post_meta( $source->get_post_id() );
        BlogPostContext::maybe_restore_blog( $did_switch );
        return $meta;
    }

    /**
     * Copies meta values.
     * 
     * @param string $meta_key
     * @param array $meta_values
     * @param BlogPostContext $target
     * @return bool
     */
    private static function copy_single_meta_key( 
        string $meta_key, 
        array $meta_values, 
        BlogPostContext $target
    ): bool {
        $did_switch = $target->maybe_switch_to_blog();

        delete_post_meta( $target->get_post_id(), $meta_key );

        $all_success = true;
        foreach ( $meta_values as $meta_value ) {
            $value = maybe_unserialize( $meta_value );
            $result = add_post_meta( $target->get_post_id(), $meta_key, $value );
            if ( ! $result ) {
                $all_success = false;
                // Continue to try other values for this key
            }
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return $all_success;
    }

    /**
     * Helper to build a standard result array.
     */
    private static function build_result( bool $success, int $copied, int $failed, array $meta_keys ): array {
        return array(
            'success'    => $success,
            'copied'     => $copied,
            'failed'     => $failed,
            'meta_keys'  => $meta_keys,
        );
    }

    /**
     * Gets the list of excluded meta keys.
     */
    private static function get_excluded_meta_keys(): array {
        $keys = self::EXCLUDED_META_KEYS;
        return apply_filters( 'rb_duplicate_post_excluded_meta_keys', $keys );
    }
    /**
     * Prepares the allowed meta keys by applying filters.
     */
    private static function prepare_allowed_meta_keys($keys): array {
        return apply_filters( 'rb_duplicate_post_allowed_meta_keys', $keys );
    }

    /**
     * Copies post meta using CopyOperationContext (convenience method).
     */
    public static function copy_post_meta_from_operation( 
        CopyOperationContext $operation,
        array $allowed_keys = array()
    ): array {
        $source = $operation->get_source();
        $target = $operation->get_target();
        return self::copy_post_meta( $source, $target, $allowed_keys );
    }
}