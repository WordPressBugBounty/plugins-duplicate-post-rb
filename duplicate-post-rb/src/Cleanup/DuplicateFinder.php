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

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- one grouped query over wp_posts; the column expression and the IN (%s,...) list are built in code, every value is prepared and passed as an array

defined( 'WPINC' ) || exit;

/**
 * Finds duplicate posts of one type: posts with the same title, the same
 * title and the same content, or the same content whatever their titles.
 * Each group lists the oldest post first.
 */
class DuplicateFinder {

    public const MATCH_TITLE         = 'title';
    public const MATCH_TITLE_CONTENT = 'title_content';
    public const MATCH_CONTENT       = 'content';

    public const MATCHES = array( self::MATCH_TITLE, self::MATCH_TITLE_CONTENT, self::MATCH_CONTENT );

    public const STATUSES = array( 'publish', 'draft', 'pending', 'private', 'future' );

    /**
     * @param string   $post_type
     * @param string   $match     self::MATCH_*
     * @param string[] $statuses  statuses to look at (subset of self::STATUSES)
     * @param int      $limit     most groups returned
     * @return int[][] groups of post ids, oldest first in each group
     */
    public static function groups( string $post_type, string $match = self::MATCH_TITLE, array $statuses = self::STATUSES, int $limit = 200 ): array {
        global $wpdb;

        $statuses = array_values( array_intersect( $statuses, self::STATUSES ) );
        if ( ! $statuses || ! post_type_exists( $post_type ) ) {
            return array();
        }
        $in    = implode( ',', array_fill( 0, count( $statuses ), '%s' ) );
        if ( $match === self::MATCH_CONTENT ) {
            $key   = 'MD5(post_content)';
            // posts with no content at all are not duplicates of each other
            $empty = "post_content <> ''";
        } else {
            $key   = $match === self::MATCH_TITLE_CONTENT ? "CONCAT(post_title, '\n', MD5(post_content))" : 'post_title';
            // empty titles are not duplicates
            $empty = "post_title <> ''";
        }
        $limit = max( 1, min( 1000, $limit ) );
        // a rewrite in progress (Rewrite & Republish) looks like a duplicate of its original, but is not one
        $not_rewrite = "ID NOT IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_rb_dp_rewrite_of')";

        // the keys shared by more than one post
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $key and $in are built above, values are prepared
        $keys = $wpdb->get_col( $wpdb->prepare(
            "SELECT {$key} AS k FROM {$wpdb->posts}
             WHERE post_type = %s AND post_status IN ({$in}) AND {$empty} AND {$not_rewrite}
             GROUP BY k HAVING COUNT(*) > 1
             ORDER BY MIN(post_date) DESC
             LIMIT %d",
            array_merge( array( $post_type ), $statuses, array( $limit ) )
        ) );
        if ( ! $keys ) {
            return array();
        }

        $groups = array();
        foreach ( $keys as $k ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_type = %s AND post_status IN ({$in}) AND {$key} = %s AND {$not_rewrite}
                 ORDER BY post_date ASC, ID ASC",
                array_merge( array( $post_type ), $statuses, array( $k ) )
            ) );
            if ( count( $ids ) > 1 ) {
                $groups[] = array_map( 'intval', $ids );
            }
        }
        return $groups;
    }

    /**
     * The posts a cleanup would remove: all but one of each group.
     *
     * @param int[][] $groups
     * @param string  $keep        'oldest' or 'newest'
     * @param bool    $drafts_only remove only drafts and pending posts, never anything visible
     * @return int[]
     */
    public static function extras( array $groups, string $keep = 'oldest', bool $drafts_only = false ): array {
        return array_keys( self::extras_kept( $groups, $keep, $drafts_only ) );
    }

    /**
     * The same, with the post each one gives way to: [removed id => id that stays].
     *
     * @return array<int, int>
     */
    public static function extras_kept( array $groups, string $keep = 'oldest', bool $drafts_only = false ): array {
        $out = array();
        foreach ( $groups as $ids ) {
            $ids = array_values( $ids );
            if ( $keep === 'newest' ) {
                $ids = array_reverse( $ids );
            }
            $stays = (int) array_shift( $ids );
            foreach ( $ids as $id ) {
                if ( $drafts_only && ! in_array( get_post_status( $id ), array( 'draft', 'pending', 'auto-draft' ), true ) ) {
                    continue;
                }
                $out[ (int) $id ] = $stays;
            }
        }
        return $out;
    }
}
