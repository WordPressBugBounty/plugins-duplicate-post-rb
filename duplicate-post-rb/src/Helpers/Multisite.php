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

namespace rbDuplicatePost\Helpers;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\GeneralOptions;
use rbDuplicatePost\ProCheck;
use rbDuplicatePost\User;

/**
 * Multisite helpers: site listing, permissions, media mode.
 *
 * Cross-site duplication is limited to blogs within the same network
 * (subsite → subsite). Super admins can target any site; regular users
 * only sites they belong to and can create posts on. It is a PRO feature:
 * without the key every copy stays on its own site.
 */
class Multisite {

    /** Media mode: physically copy attachment files to the target site. */
    const MEDIA_MODE_COPY = 'copy';

    /** Media mode: keep references to source media (shared uploads / same-site). */
    const MEDIA_MODE_LINK = 'link';

    /**
     * Whether the install is Multisite, PRO is on and cross-site copy is enabled.
     */
    public static function is_cross_site_enabled(): bool {
        if ( ! is_multisite() || ! ProCheck::isActive() ) {
            return false;
        }
        $val = GeneralOptions::getOptionValue( 'enableCrossSiteCopy' );
        // Default true on Multisite when option not set yet.
        if ( $val === null ) {
            return true;
        }
        return (bool) $val;
    }

    /**
     * Media handling mode for cross-site (and optional same-site) copies.
     *
     * @return string self::MEDIA_MODE_COPY|self::MEDIA_MODE_LINK
     */
    public static function get_media_mode(): string {
        $mode = GeneralOptions::getOptionValue( 'multisiteMediaMode' );
        if ( $mode === self::MEDIA_MODE_LINK ) {
            return self::MEDIA_MODE_LINK;
        }
        return self::MEDIA_MODE_COPY;
    }

    /**
     * Whether media files should be physically duplicated.
     * When false, attachment IDs from the source blog are reused (link mode).
     */
    public static function should_copy_media_files( int $source_blog_id, int $target_blog_id ): bool {
        if ( $source_blog_id === $target_blog_id ) {
            // Same site: default is link (reuse), unless forced copy via mode.
            // Existing PostMediaCopier uses duplicate_on_same_blog flag; we keep
            // same-site behaviour as "link" unless option says copy.
            return self::get_media_mode() === self::MEDIA_MODE_COPY
                && (bool) GeneralOptions::getOptionValue( 'forceCopyMediaOnSameSite' );
        }

        // Cross-site: respect media mode option.
        return self::get_media_mode() === self::MEDIA_MODE_COPY;
    }

    /**
     * Value to pass as $duplicate_on_same_blog / "force physical copy" to PostMediaCopier.
     * true  = always create a new attachment file on the target.
     * false = reuse source ID when same blog; still copy files when blogs differ
     *         (PostMediaCopier copies across blogs when IDs differ).
     */
    public static function get_media_duplicate_flag( int $source_blog_id, int $target_blog_id ): bool {
        if ( $source_blog_id === $target_blog_id ) {
            return self::should_copy_media_files( $source_blog_id, $target_blog_id );
        }
        // Cross-site: PostMediaCopier always creates a new attachment when blogs differ.
        // If mode is LINK, we skip media transformers instead (see transformers).
        return true;
    }

    /**
     * Whether the current user may copy a post onto $target_blog_id.
     */
    public static function can_user_copy_to_blog( int $target_blog_id ): bool {
        if ( ! is_multisite() ) {
            return $target_blog_id === get_current_blog_id() || $target_blog_id === 0;
        }

        $target_blog_id = absint( $target_blog_id );
        if ( $target_blog_id <= 0 ) {
            $target_blog_id = get_current_blog_id();
        }

        if ( ! self::blog_exists( $target_blog_id ) ) {
            return false;
        }

        // on the user's own site as on a single site: the copy's status follows their right to publish
        // (PostProcessor::limitStatus), so a contributor's copies wait for review there too
        if ( $target_blog_id === get_current_blog_id() ) {
            return current_user_can( 'edit_posts' );
        }

        // Super admins can copy anywhere in the network.
        if ( is_super_admin() ) {
            return true;
        }

        // Must be a member of the target site with create/edit capability there.
        if ( ! is_user_member_of_blog( get_current_user_id(), $target_blog_id ) ) {
            return false;
        }

        $switched = false;
        if ( get_current_blog_id() !== $target_blog_id ) {
            switch_to_blog( $target_blog_id );
            $switched = true;
        }

        $can = current_user_can( 'edit_posts' ) && current_user_can( 'publish_posts' );

        if ( $switched ) {
            restore_current_blog();
        }

        return (bool) $can;
    }

    /**
     * Whether the current user may read/copy from $source_blog_id.
     */
    public static function can_user_copy_from_blog( int $source_blog_id ): bool {
        if ( ! is_multisite() ) {
            return true;
        }

        $source_blog_id = absint( $source_blog_id );
        if ( $source_blog_id <= 0 ) {
            $source_blog_id = get_current_blog_id();
        }

        if ( ! self::blog_exists( $source_blog_id ) ) {
            return false;
        }

        if ( is_super_admin() ) {
            return true;
        }

        return is_user_member_of_blog( get_current_user_id(), $source_blog_id );
    }

    /**
     * Whether the current user may edit these posts of $blog_id (0 = current blog).
     * Post IDs repeat across a network, so the check is made on the site the posts
     * come from, with the user's role there - not on the site the request is sent to.
     *
     * @param int[] $ids
     */
    public static function can_user_edit_posts_on_blog( array $ids, int $blog_id ): bool {
        $blog_id = absint( $blog_id );
        if ( ! is_multisite() || ! $blog_id || $blog_id === get_current_blog_id() ) {
            return User::canEditPost( $ids );
        }
        if ( ! self::can_user_copy_from_blog( $blog_id ) ) {
            return false;
        }
        return (bool) self::with_blog( $blog_id, function () use ( $ids ) {
            return User::canEditPost( $ids );
        } );
    }

    /**
     * Runs $callback on $blog_id (0 = current blog) and comes back.
     *
     * @return mixed what $callback returns
     */
    public static function with_blog( int $blog_id, callable $callback ) {
        $switched = is_multisite() && $blog_id > 0 && $blog_id !== get_current_blog_id();
        if ( $switched ) {
            switch_to_blog( $blog_id );
        }
        try {
            return $callback();
        } finally {
            if ( $switched ) {
                restore_current_blog();
            }
        }
    }

    /**
     * List blogs the current user can target for cross-site copy.
     *
     * @return array<int, array{id:int,name:string,url:string,is_current:bool}>
     */
    public static function get_available_target_blogs(): array {
        if ( ! is_multisite() || ! self::is_cross_site_enabled() ) {
            $id = get_current_blog_id();
            return array(
                array(
                    'id'         => $id,
                    'name'       => get_bloginfo( 'name' ),
                    'url'        => home_url( '/' ),
                    'is_current' => true,
                ),
            );
        }

        $current = get_current_blog_id();
        $user_id = get_current_user_id();
        $out     = array();

        if ( is_super_admin() ) {
            $sites = get_sites( array(
                'number'   => 500,
                'archived' => 0,
                'deleted'  => 0,
                'spam'     => 0,
            ) );
        } else {
            $sites = get_blogs_of_user( $user_id );
        }

        if ( empty( $sites ) ) {
            return $out;
        }

        foreach ( $sites as $site ) {
            // get_sites() returns WP_Site; get_blogs_of_user() returns objects with userblog_id.
            if ( is_object( $site ) && isset( $site->blog_id ) ) {
                $blog_id = (int) $site->blog_id;
            } elseif ( is_object( $site ) && isset( $site->userblog_id ) ) {
                $blog_id = (int) $site->userblog_id;
            } else {
                continue;
            }

            if ( ! self::can_user_copy_to_blog( $blog_id ) ) {
                continue;
            }

            $details = get_blog_details( $blog_id );
            $name    = $details ? $details->blogname : (string) $blog_id;
            $url     = $details ? $details->siteurl : '';

            $out[] = array(
                'id'         => $blog_id,
                'name'       => $name,
                'url'        => $url,
                'is_current' => ( $blog_id === $current ),
            );
        }

        // Current blog first.
        usort( $out, function ( $a, $b ) {
            if ( $a['is_current'] === $b['is_current'] ) {
                return strcasecmp( $a['name'], $b['name'] );
            }
            return $a['is_current'] ? -1 : 1;
        } );

        return $out;
    }

    public static function blog_exists( int $blog_id ): bool {
        if ( $blog_id <= 0 ) {
            return false;
        }
        if ( ! is_multisite() ) {
            return $blog_id === get_current_blog_id() || $blog_id === 1;
        }
        $details = get_blog_details( $blog_id );
        return (bool) $details;
    }

    /**
     * Normalize target blog ID: 0 means current blog.
     */
    public static function normalize_target_blog_id( int $target_blog_id ): int {
        $target_blog_id = absint( $target_blog_id );
        if ( $target_blog_id <= 0 ) {
            return get_current_blog_id();
        }
        return $target_blog_id;
    }
}
