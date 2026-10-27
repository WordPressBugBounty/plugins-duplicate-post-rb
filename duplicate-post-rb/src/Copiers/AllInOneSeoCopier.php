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

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- AIOSEO keeps its data in its own table ($wpdb->prefix . 'aioseo_posts'); values are prepared

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Contexts\BlogPostContext;
use rbDuplicatePost\Contexts\CopyOperationContext;

/**
 * Copies All in One SEO (AIOSEO) data from a source post to a target post.
 *
 * Supports:
 * - Modern AIOSEO (v4+): data in the custom table `{prefix}aioseo_posts`
 * - Legacy AIOSEO Pack: postmeta keys `_aioseop_*`
 * - Some residual postmeta still used by modern AIOSEO
 *
 * Never copies canonical URL fields so the new post gets a correct default.
 * Multisite-ready via BlogPostContext / CopyOperationContext.
 */
class AllInOneSeoCopier {

    /**
     * Columns / field names in aioseo_posts that must never be copied.
     */
    const NEVER_COPY_COLUMNS = array(
        'id',           // auto-increment PK
        'post_id',      // will be set to target
        'created',
        'updated',
        'canonical_url',
        'pillar_content', // often site-specific
    );

    /**
     * Legacy / residual postmeta prefixes and exact keys to exclude.
     */
    const NEVER_COPY_META = array(
        '_aioseop_canonical',
        '_aioseo_canonical_url',
    );

    const DEFAULT_AIOSEO_PLUGIN_FILE = 'all-in-one-seo-pack/all_in_one_seo_pack.php';

    /**
     * Main entry point.
     *
     * @param CopyOperationContext $context
     * @param array $args {
     *     @type bool $overwrite Default true.
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

        if ( ! self::is_aioseo_active_on_blog( $source ) ) {
            $result['skipped'][] = array( 'field' => null, 'reason' => 'AIOSEO not active on source blog' );
            return $result;
        }

        if ( ! self::is_aioseo_active_on_blog( $target ) ) {
            $result['skipped'][] = array( 'field' => null, 'reason' => 'AIOSEO not active on target blog' );
            return $result;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_before_aioseo_copy', $context, $args );

        $overwrite = array_key_exists( 'overwrite', $args ) ? (bool) $args['overwrite'] : true;

        // Prefer modern custom-table path when available.
        $table_copied = self::copy_aioseo_posts_table( $context, $overwrite, $result );

        // Always also handle legacy / residual postmeta.
        self::copy_aioseo_postmeta( $context, $overwrite, $result );

        if ( ! $table_copied ) {
            // Table path was skipped (old AIOSEO or table missing) — postmeta is enough.
        }

        self::clear_aioseo_cache( $target );

        $result['success'] = empty( $result['errors'] );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_after_aioseo_copy', $context, $result );

        return $result;
    }

    /**
     * Copies a row from {prefix}aioseo_posts for the source post into the target.
     *
     * @return bool True if a row was found and processed (even if skipped).
     */
    private static function copy_aioseo_posts_table(
        CopyOperationContext $context,
        bool $overwrite,
        array &$result
    ): bool {
        global $wpdb;

        $source = $context->get_source();
        $target = $context->get_target();

        $did_switch = $source->maybe_switch_to_blog();
        $table      = $wpdb->prefix . 'aioseo_posts';
        $exists     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table;

        if ( ! $exists ) {
            BlogPostContext::maybe_restore_blog( $did_switch );
            $result['skipped'][] = array( 'field' => 'aioseo_posts', 'reason' => 'table does not exist on source' );
            return false;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE post_id = %d", $source->get_post_id() ),
            ARRAY_A
        );
        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( empty( $row ) || ! is_array( $row ) ) {
            $result['skipped'][] = array( 'field' => 'aioseo_posts', 'reason' => 'no row for source post' );
            return true; // table exists, nothing to copy
        }

        // Build insert data for target.
        $insert = array();
        foreach ( $row as $column => $value ) {
            if ( in_array( $column, self::NEVER_COPY_COLUMNS, true ) ) {
                continue;
            }
            $insert[ $column ] = $value;
        }
        $insert['post_id'] = $target->get_post_id();

        // Timestamps.
        $now = current_time( 'mysql' );
        if ( array_key_exists( 'created', $row ) ) {
            $insert['created'] = $now;
        }
        if ( array_key_exists( 'updated', $row ) ) {
            $insert['updated'] = $now;
        }

        $did_switch = $target->maybe_switch_to_blog();
        $target_table = $wpdb->prefix . 'aioseo_posts';
        $target_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $target_table ) ) === $target_table;

        if ( ! $target_exists ) {
            BlogPostContext::maybe_restore_blog( $did_switch );
            $result['skipped'][] = array( 'field' => 'aioseo_posts', 'reason' => 'table does not exist on target' );
            return false;
        }

        $existing_id = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM {$target_table} WHERE post_id = %d", $target->get_post_id() )
        );

        if ( $existing_id && ! $overwrite ) {
            BlogPostContext::maybe_restore_blog( $did_switch );
            $result['skipped'][] = array( 'field' => 'aioseo_posts', 'reason' => 'target row already exists' );
            return true;
        }

        if ( $existing_id ) {
            $wpdb->update(
                $target_table,
                $insert,
                array( 'id' => (int) $existing_id ),
                null,
                array( '%d' )
            );
            $ok = ( $wpdb->last_error === '' );
        } else {
            $ok = (false !== $wpdb->insert( $target_table, $insert ));
        }

        BlogPostContext::maybe_restore_blog( $did_switch );

        if ( $ok ) {
            $result['copied'][] = 'aioseo_posts';
        } else {
            $result['errors'][] = array(
                'field'  => 'aioseo_posts',
                'reason' => $wpdb->last_error ? $wpdb->last_error : 'insert/update failed',
            );
        }

        return true;
    }

    /**
     * Copies AIOSEO-related postmeta (_aioseop_* and residual _aioseo_* keys).
     */
    private static function copy_aioseo_postmeta(
        CopyOperationContext $context,
        bool $overwrite,
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

        foreach ( $all_meta as $meta_key => $values ) {
            $is_legacy  = ( strpos( $meta_key, '_aioseop_' ) === 0 );
            $is_modern  = ( strpos( $meta_key, '_aioseo_' ) === 0 );

            if ( ! $is_legacy && ! $is_modern ) {
                continue;
            }

            if ( in_array( $meta_key, self::NEVER_COPY_META, true ) ) {
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'never_copy' );
                continue;
            }

            // Skip keys that clearly contain absolute source URLs.
            if ( stripos( $meta_key, 'canonical' ) !== false ) {
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'never_copy_canonical' );
                continue;
            }

            self::write_meta_values( $meta_key, $values, $target, $overwrite, $result );
        }
    }

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
            $value = maybe_unserialize( $raw );
            $added = add_post_meta( $target->get_post_id(), $meta_key, wp_slash( $value ) );
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

    private static function clear_aioseo_cache( BlogPostContext $target ): void {
        $did_switch = $target->maybe_switch_to_blog();
        $post_id    = $target->get_post_id();

        if ( function_exists( 'aioseo' ) ) {
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- the SEO plugin's own hook, fired so it refreshes its data for the copy
            do_action( 'aioseo_post_updated', $post_id );
        }

        wp_cache_delete( $post_id, 'post_meta' );

        BlogPostContext::maybe_restore_blog( $did_switch );
    }

    /**
     * Checks whether AIOSEO is active on the given blog.
     */
    public static function is_aioseo_active_on_blog( BlogPostContext $blog ): bool {
        $plugin_file = apply_filters(
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
            'rb_duplicate_post_aioseo_plugin_file',
            self::DEFAULT_AIOSEO_PLUGIN_FILE
        );

        $did_switch = $blog->maybe_switch_to_blog();

        $active = false;
        if ( function_exists( 'is_plugin_active' ) ) {
            $active = is_plugin_active( $plugin_file );
            // Pro / alternate paths.
            if ( ! $active ) {
                $active = is_plugin_active( 'all-in-one-seo-pack-pro/all_in_one_seo_pack.php' );
            }
        }
        if ( ! $active && ( function_exists( 'aioseo' ) || defined( 'AIOSEO_VERSION' ) || class_exists( '\AIOSEO\Plugin\AIOSEO' ) ) ) {
            $active = true;
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return (bool) $active;
    }
}
