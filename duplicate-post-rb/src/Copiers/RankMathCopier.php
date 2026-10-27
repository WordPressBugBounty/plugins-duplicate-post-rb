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
 * Copies Rank Math SEO postmeta from a source post to a target post.
 *
 * Designed to mirror YoastSeoCopier patterns:
 * - Uses CopyOperationContext (Multisite-ready).
 * - Never copies canonical URL or analysis/score fields.
 * - Remaps primary-term IDs via an optional term_map.
 * - Optionally copies social images (URL + attachment ID).
 *
 * Rank Math stores most SEO data as postmeta with the `rank_math_` prefix.
 * Primary terms use `rank_math_primary_{taxonomy}` (e.g. rank_math_primary_category).
 */
class RankMathCopier {

    /**
     * Bare field names (without the rank_math_ prefix) that must never be copied.
     * Canonical points at the source URL; scores are recalculated by Rank Math.
     */
    const NEVER_COPY_FIELDS = array(
        'canonical_url',
        'seo_score',
        'content_score',
        'readability_score',
        'analytic_stats',
        'internal_links_processed',
        'external_links_processed',
        'link_suggestion',
    );

    /**
     * Meta keys that hold social image URLs (string URL, not attachment ID).
     */
    const SOCIAL_IMAGE_URL_KEYS = array(
        'rank_math_facebook_image',
        'rank_math_twitter_image',
    );

    /**
     * Meta keys that hold social image attachment IDs.
     */
    const SOCIAL_IMAGE_ID_KEYS = array(
        'rank_math_facebook_image_id',
        'rank_math_twitter_image_id',
    );

    const DEFAULT_RANK_MATH_PLUGIN_FILE = 'seo-by-rank-math/rank-math.php';

    /**
     * Main entry point.
     *
     * @param CopyOperationContext $context
     * @param array $args {
     *     @type bool  $overwrite Default true.
     *     @type array $term_map  taxonomy => [ source_term_id => target_term_id ]
     *                            or flat source_term_id => target_term_id.
     * }
     * @return array{success:bool,copied:array,skipped:array,errors:array}
     */
    public static function copy( CopyOperationContext $context, array $args = array() ): array {

        $result = array(
            'success' => true,
            'copied'  => array(),
            'skipped' => array(),
            'errors'  => array(),
        );

        $source = $context->get_source();
        $target = $context->get_target();

        if ( ! $source->get_post() ) {
            $result['success']  = false;
            $result['errors'][] = array( 'field' => null, 'reason' => 'source post does not exist' );
            return $result;
        }

        if ( ! $target->get_post() ) {
            $result['success']  = false;
            $result['errors'][] = array( 'field' => null, 'reason' => 'target post does not exist' );
            return $result;
        }

        if ( ! self::is_rank_math_active_on_blog( $source ) ) {
            $result['skipped'][] = array( 'field' => null, 'reason' => 'Rank Math not active on source blog' );
            return $result;
        }

        if ( ! self::is_rank_math_active_on_blog( $target ) ) {
            $result['skipped'][] = array( 'field' => null, 'reason' => 'Rank Math not active on target blog' );
            return $result;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_before_rank_math_copy', $context, $args );

        $overwrite = array_key_exists( 'overwrite', $args ) ? (bool) $args['overwrite'] : true;
        $term_map  = ( isset( $args['term_map'] ) && is_array( $args['term_map'] ) ) ? $args['term_map'] : array();

        self::copy_all_rank_math_meta( $context, $overwrite, $term_map, $result );
        self::copy_social_images( $context, $overwrite, $result );
        self::clear_rank_math_cache( $target );

        $result['success'] = empty( $result['errors'] );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_after_rank_math_copy', $context, $result );

        return $result;
    }

    /**
     * Copies all rank_math_* postmeta except NEVER_COPY and primary/social-image keys
     * that are handled separately.
     */
    private static function copy_all_rank_math_meta(
        CopyOperationContext $context,
        bool $overwrite,
        array $term_map,
        array &$result
    ): void {
        $source = $context->get_source();
        $target = $context->get_target();

        $did_switch = $source->maybe_switch_to_blog();
        $all_meta   = get_post_meta( $source->get_post_id() );
        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( empty( $all_meta ) || ! is_array( $all_meta ) ) {
            return;
        }

        $never_full = array();
        foreach ( self::NEVER_COPY_FIELDS as $bare ) {
            $never_full[] = 'rank_math_' . $bare;
        }

        // Social image keys are handled in copy_social_images().
        $handled_elsewhere = array_merge(
            self::SOCIAL_IMAGE_URL_KEYS,
            self::SOCIAL_IMAGE_ID_KEYS
        );

        foreach ( $all_meta as $meta_key => $values ) {
            if ( strpos( $meta_key, 'rank_math_' ) !== 0 ) {
                continue;
            }

            if ( in_array( $meta_key, $never_full, true ) ) {
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'never_copy' );
                continue;
            }

            if ( in_array( $meta_key, $handled_elsewhere, true ) ) {
                continue;
            }

            // Primary term: rank_math_primary_{taxonomy}
            if ( strpos( $meta_key, 'rank_math_primary_' ) === 0 ) {
                self::copy_primary_term_key( $meta_key, $values, $context, $term_map, $overwrite, $result );
                continue;
            }

            self::write_meta_values( $meta_key, $values, $target, $overwrite, $result );
        }
    }

    /**
     * Copies a primary-term meta key, remapping the term ID when a map is provided.
     */
    private static function copy_primary_term_key(
        string $meta_key,
        array $values,
        CopyOperationContext $context,
        array $term_map,
        bool $overwrite,
        array &$result
    ): void {
        $taxonomy = substr( $meta_key, strlen( 'rank_math_primary_' ) );
        if ( $taxonomy === '' ) {
            $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'empty taxonomy in primary key' );
            return;
        }

        $raw_value = isset( $values[0] ) ? maybe_unserialize( $values[0] ) : null;
        $source_term_id = absint( $raw_value );

        if ( ! $source_term_id ) {
            $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'empty primary term' );
            return;
        }

        $target_term_id = self::resolve_term_id( $source_term_id, $taxonomy, $term_map );

        if ( ! $target_term_id ) {
            // No map entry and no same-blog term — skip rather than point at a wrong term.
            $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'primary term not in term_map' );
            return;
        }

        $target = $context->get_target();
        $did_switch = $target->maybe_switch_to_blog();

        if ( ! $overwrite ) {
            $existing = get_post_meta( $target->get_post_id(), $meta_key, true );
            if ( $existing !== '' && $existing !== null && $existing !== false ) {
                BlogPostContext::maybe_restore_blog( $did_switch );
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'target already has value' );
                return;
            }
        }

        delete_post_meta( $target->get_post_id(), $meta_key );
        $ok = add_post_meta( $target->get_post_id(), $meta_key, $target_term_id, true );

        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( $ok ) {
            $result['copied'][] = $meta_key;
        } else {
            $result['errors'][] = array( 'field' => $meta_key, 'reason' => 'failed to write primary term' );
        }
    }

    /**
     * Resolves a source term ID to a target term ID using the map.
     * Supports both flat maps and per-taxonomy maps.
     */
    private static function resolve_term_id( int $source_term_id, string $taxonomy, array $term_map ): int {
        if ( empty( $term_map ) ) {
            // Same-site duplication with no map: term IDs stay the same.
            return $source_term_id;
        }

        // Per-taxonomy map: $term_map[ $taxonomy ][ $source_id ] = $target_id
        if ( isset( $term_map[ $taxonomy ] ) && is_array( $term_map[ $taxonomy ] ) ) {
            if ( isset( $term_map[ $taxonomy ][ $source_term_id ] ) ) {
                return absint( $term_map[ $taxonomy ][ $source_term_id ] );
            }
            return 0;
        }

        // Flat map: $term_map[ $source_id ] = $target_id
        if ( isset( $term_map[ $source_term_id ] ) ) {
            return absint( $term_map[ $source_term_id ] );
        }

        return 0;
    }

    /**
     * Copies social image URL and ID fields, optionally duplicating the attachment.
     */
    private static function copy_social_images(
        CopyOperationContext $context,
        bool $overwrite,
        array &$result
    ): void {
        $source = $context->get_source();
        $target = $context->get_target();

        $pairs = array(
            array( 'url' => 'rank_math_facebook_image', 'id' => 'rank_math_facebook_image_id' ),
            array( 'url' => 'rank_math_twitter_image',  'id' => 'rank_math_twitter_image_id' ),
        );

        foreach ( $pairs as $pair ) {
            $did_switch = $source->maybe_switch_to_blog();
            $image_id   = absint( get_post_meta( $source->get_post_id(), $pair['id'], true ) );
            $image_url  = (string) get_post_meta( $source->get_post_id(), $pair['url'], true );
            BlogPostContext::maybe_restore_blog( $did_switch );

            $new_id  = 0;
            $new_url = $image_url;

            if ( $image_id > 0 ) {
                $copied_id = PostMediaCopier::copy_attachment_by_id( $image_id, $context, false );
                if ( $copied_id > 0 ) {
                    $new_id = $copied_id;
                    $did_switch = $target->maybe_switch_to_blog();
                    $new_url = wp_get_attachment_url( $new_id );
                    BlogPostContext::maybe_restore_blog( $did_switch );
                    if ( ! is_string( $new_url ) ) {
                        $new_url = $image_url;
                    }
                }
            }

            if ( $new_id > 0 ) {
                self::write_single_meta( $pair['id'], $new_id, $target, $overwrite, $result );
            } elseif ( $image_id > 0 ) {
                // Fallback: keep original ID if copy failed (same-site usually fine).
                self::write_single_meta( $pair['id'], $image_id, $target, $overwrite, $result );
            }

            if ( $new_url !== '' ) {
                self::write_single_meta( $pair['url'], $new_url, $target, $overwrite, $result );
            }
        }
    }

    /**
     * Writes one or more values for a meta key to the target.
     */
    private static function write_meta_values(
        string $meta_key,
        array $values,
        BlogPostContext $target,
        bool $overwrite,
        array &$result
    ): void {
        $did_switch = $target->maybe_switch_to_blog();

        if ( ! $overwrite ) {
            $existing = get_post_meta( $target->get_post_id(), $meta_key, true );
            if ( $existing !== '' && $existing !== null && $existing !== false ) {
                BlogPostContext::maybe_restore_blog( $did_switch );
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'target already has value' );
                return;
            }
        }

        delete_post_meta( $target->get_post_id(), $meta_key );

        $ok = true;
        foreach ( $values as $raw ) {
            $value  = maybe_unserialize( $raw );
            $added  = add_post_meta( $target->get_post_id(), $meta_key, wp_slash( $value ) );
            if ( ! $added ) {
                $ok = false;
            }
        }

        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( $ok ) {
            $result['copied'][] = $meta_key;
        } else {
            $result['errors'][] = array( 'field' => $meta_key, 'reason' => 'failed to write meta' );
        }
    }

    private static function write_single_meta(
        string $meta_key,
        $value,
        BlogPostContext $target,
        bool $overwrite,
        array &$result
    ): void {
        self::write_meta_values( $meta_key, array( $value ), $target, $overwrite, $result );
    }

    /**
     * Clears Rank Math internal cache / index for the target post when possible.
     */
    private static function clear_rank_math_cache( BlogPostContext $target ): void {
        $did_switch = $target->maybe_switch_to_blog();
        $post_id    = $target->get_post_id();

        if ( class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'update_post_meta' ) ) {
            // No-op: presence check only.
        }

        // Rank Math stores some data in a custom table; invalidate if API exists.
        if ( function_exists( 'rank_math' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the SEO plugin's own hook, fired so it refreshes its data for the copy
            do_action( 'rank_math/post/update', $post_id );
        }

        // Clear object cache for this post's Rank Math meta.
        wp_cache_delete( $post_id, 'post_meta' );

        BlogPostContext::maybe_restore_blog( $did_switch );
    }

    /**
     * Checks whether Rank Math is active on the given blog.
     */
    public static function is_rank_math_active_on_blog( BlogPostContext $blog ): bool {
        $plugin_file = apply_filters(
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
            'rb_duplicate_post_rank_math_plugin_file',
            self::DEFAULT_RANK_MATH_PLUGIN_FILE
        );

        $did_switch = $blog->maybe_switch_to_blog();

        $active = false;
        if ( function_exists( 'is_plugin_active' ) ) {
            $active = is_plugin_active( $plugin_file );
        }
        // Also accept if Rank Math classes are already loaded in this request.
        if ( ! $active && ( class_exists( '\RankMath' ) || class_exists( '\RankMath\Helper' ) || defined( 'RANK_MATH_VERSION' ) ) ) {
            $active = true;
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return (bool) $active;
    }
}
