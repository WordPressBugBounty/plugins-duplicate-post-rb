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

namespace rbDuplicatePost\Rewrite;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\AfterCopyTransformers\AllInOneSeoTransformer;
use rbDuplicatePost\AfterCopyTransformers\RankMathTransformer;
use rbDuplicatePost\AfterCopyTransformers\YoastSeoTransformer;
use rbDuplicatePost\Contexts\TransformerContext;
use rbDuplicatePost\Contracts\PostAfterCopyTransformer;
use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Log\Actions;
use rbDuplicatePost\Log\Logger;
use rbDuplicatePost\Profile\Profile;

/**
 * Rewrite & Republish: a published post gets a draft "rewrite" to edit in
 * peace; publishing the rewrite puts its changes into the original (same ID,
 * URL, date, author and comments) and removes the rewrite.
 *
 * Republishing reuses the copy steps the other way round, so SEO data (Yoast,
 * Rank Math, All in One SEO), custom fields (ACF) and the PRO key's WooCommerce
 * and page-builder data come back too. Add-on steps see
 * $context->get_data()['republish'] === true.
 */
class Republisher {

    /** on the rewrite: the id of its original */
    public const META_ORIGINAL = '_rb_dp_rewrite_of';
    /** on the original: the id of its rewrite */
    public const META_REWRITE = '_rb_dp_rewrite_draft';

    /** meta of the original that a republish never touches */
    private const KEEP_META = array(
        '_edit_lock', '_edit_last', '_wp_old_slug', '_wp_old_date', '_wp_trash_meta_status', '_wp_trash_meta_time',
        '_encloseme', '_pingme', 'rb-duplicate-post-history', self::META_ORIGINAL, self::META_REWRITE,
        // WooCommerce: what belongs to the product itself, not to its text
        '_sku', '_global_unique_id', 'total_sales', '_wc_average_rating', '_wc_rating_count', '_wc_review_count',
        // the SEO plugins' own copiers write these
        '_yoast_wpseo_*', 'rank_math_*', '_aioseo_*', '_aioseop_*',
    );

    /** the profile options of a rewrite: everything that is the text of the post, nothing of its life */
    private const REWRITE_OPTIONS = array(
        'copyTitle' => true, 'copyContent' => true, 'copyExcerpt' => true, 'copyStatus' => false, 'copyDate' => true,
        'copySlug' => false, 'copyAuthor' => true, 'copyPassword' => true, 'copyFeaturedImage' => true, 'copyTemplate' => true,
        'copyFormat' => true, 'copyMenuOrder' => true, 'copyCategories' => true, 'copyTags' => true, 'copyTaxonomies' => true,
        'copyAttachments' => false, 'copyChildren' => false, 'copyNavigationMenu' => false, 'copyLinkCategories' => false,
        'copyAllPostMeta' => true, 'copyComments' => false, 'prefixValue' => '', 'suffixValue' => '', 'sourcePostId' => 0,
        'convertTo' => 'same', 'scenario' => array( 'enabled' => false, 'template' => '', 'vars' => array(), 'steps' => array() ),
    );

    /** posts that can get a rewrite */
    public static function can_rewrite( $post ): bool {
        $post = get_post( $post );
        return $post
            && $post->post_status === 'publish'
            && ! self::original_of( $post->ID )
            && current_user_can( 'edit_post', $post->ID );
    }

    /**
     * The original of a rewrite, 0 when the post is not one. Only create() marks
     * both posts, and only for a user who may edit the original: a mark the original
     * does not answer (copied with the meta, or set by hand) makes no rewrite.
     */
    public static function original_of( int $rewrite_id ): int {
        $original_id = (int) get_post_meta( $rewrite_id, self::META_ORIGINAL, true );
        if ( ! $original_id || (int) get_post_meta( $original_id, self::META_REWRITE, true ) !== $rewrite_id ) {
            return 0;
        }
        return $original_id;
    }

    /** the user may put the rewrite into its original: edit both, and publish posts of the original's type */
    public static function can_republish( int $rewrite_id ): bool {
        $original_id = self::original_of( $rewrite_id );
        if ( ! $original_id || ! current_user_can( 'edit_post', $rewrite_id ) || ! current_user_can( 'edit_post', $original_id ) ) {
            return false;
        }
        $type = get_post_type_object( get_post_type( $original_id ) );
        return $type && current_user_can( $type->cap->publish_posts );
    }

    /** the rewrite in progress for a post, 0 when there is none */
    public static function rewrite_of( int $original_id ): int {
        $id = (int) get_post_meta( $original_id, self::META_REWRITE, true );
        // a copy of the original carries its mark too: the rewrite answers only its own original
        if ( $id && ( ! get_post( $id ) || get_post_status( $id ) === 'trash' || (int) get_post_meta( $id, self::META_ORIGINAL, true ) !== $original_id ) ) {
            delete_post_meta( $original_id, self::META_REWRITE );
            return 0;
        }
        return $id;
    }

    /**
     * Makes the rewrite of a published post, or returns the one already in progress.
     *
     * @throws \RuntimeException
     */
    public static function create( int $original_id ): int {
        $original = get_post( $original_id );
        if ( ! $original || ! self::can_rewrite( $original ) ) {
            throw new \RuntimeException( 'Only a published post you may edit can be rewritten.' );
        }
        $existing = self::rewrite_of( $original_id );
        if ( $existing ) {
            return $existing;
        }

        $options = self::REWRITE_OPTIONS;
        $filter  = function ( $profile_options ) use ( $options ) {
            foreach ( $options as $key => $value ) {
                if ( isset( $profile_options[ $key ] ) && is_array( $profile_options[ $key ] ) ) {
                    $profile_options[ $key ]['value'] = $value;
                }
            }
            return $profile_options;
        };
        add_filter( 'rb_duplicate_post_copy_options', $filter );
        try {
            $rewrite_id = DuplicatorFactory::make( 'post' )->duplicate( $original_id, (int) Profile::getDefaultProfileId() );
        } finally {
            remove_filter( 'rb_duplicate_post_copy_options', $filter );
        }
        if ( ! $rewrite_id ) {
            throw new \RuntimeException( 'The rewrite could not be created.' );
        }

        // the copy steps keep the date: a draft with a past date would publish at once; let it float
        wp_update_post( array( 'ID' => $rewrite_id, 'post_status' => 'draft', 'post_date_gmt' => '0000-00-00 00:00:00', 'edit_date' => false ) );
        update_post_meta( $rewrite_id, self::META_ORIGINAL, $original_id );
        update_post_meta( $original_id, self::META_REWRITE, $rewrite_id );
        return (int) $rewrite_id;
    }

    /**
     * Puts the rewrite into its original and removes the rewrite.
     *
     * @param bool $delete_now remove the rewrite at once; false: at the end of the request
     *                         (the block editor still reads it while its save request finishes)
     * @return int the original's id
     * @throws \RuntimeException
     */
    public static function republish( int $rewrite_id, bool $delete_now = true ): int {
        // a scheduled rewrite is republished by cron: the original keeps its embeds and scripts
        return \rbDuplicatePost\Utils::withoutKsesWhenNoUser( function () use ( $rewrite_id, $delete_now ) {
            return self::put_into_original( $rewrite_id, $delete_now );
        } );
    }

    /** republish() itself */
    private static function put_into_original( int $rewrite_id, bool $delete_now ): int {
        $rewrite     = get_post( $rewrite_id );
        $original_id = $rewrite ? self::original_of( $rewrite_id ) : 0;
        $original    = $original_id ? get_post( $original_id ) : null;
        if ( ! $rewrite || ! $original || $original->post_status === 'trash' ) {
            throw new \RuntimeException( 'The original of this rewrite is gone.' );
        }

        // 1. the text of the post; its slug, date, status and author stay
        $update = array(
            'ID'             => $original_id,
            'post_title'     => $rewrite->post_title,
            'post_content'   => $rewrite->post_content,
            'post_excerpt'   => $rewrite->post_excerpt,
            'post_password'  => $rewrite->post_password,
            'menu_order'     => $rewrite->menu_order,
            'comment_status' => $rewrite->comment_status,
            'ping_status'    => $rewrite->ping_status,
        );
        wp_update_post( wp_slash( $update ) );

        // 2. terms, every taxonomy of the type: emptied ones are emptied on the original too
        foreach ( get_object_taxonomies( $original->post_type ) as $taxonomy ) {
            if ( $taxonomy === 'post_translations' ) {
                continue;
            }
            $ids = wp_get_object_terms( $rewrite_id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $ids ) ) {
                wp_set_object_terms( $original_id, array_map( 'intval', $ids ), $taxonomy );
            }
        }

        // 3. meta: the rewrite's, and the original loses what the rewrite dropped
        $rewrite_meta  = get_post_meta( $rewrite_id );
        $original_meta = get_post_meta( $original_id );
        foreach ( array_keys( $original_meta ) as $key ) {
            if ( ! isset( $rewrite_meta[ $key ] ) && ! self::kept( $key ) ) {
                delete_post_meta( $original_id, $key );
            }
        }
        foreach ( $rewrite_meta as $key => $values ) {
            if ( self::kept( $key ) ) {
                continue;
            }
            delete_post_meta( $original_id, $key );
            foreach ( $values as $raw ) {
                add_post_meta( $original_id, $key, wp_slash( maybe_unserialize( $raw ) ) );
            }
        }

        // 4. the plugins' own data: SEO, and whatever add-ons (the PRO key) bring
        $context = TransformerContext::from_current_blog( $rewrite_id, $original_id, array(), 0, array( 'republish' => true, 'term_map' => array() ) );
        $steps   = array( new YoastSeoTransformer(), new RankMathTransformer(), new AllInOneSeoTransformer() );
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        foreach ( (array) apply_filters( 'rb_duplicate_post_after_copy_transformers', array() ) as $step ) {
            if ( $step instanceof PostAfterCopyTransformer ) {
                $steps[] = $step;
            }
        }
        foreach ( $steps as $step ) {
            $step->transform( $context );
        }

        clean_post_cache( $original_id );
        delete_post_meta( $original_id, self::META_REWRITE );
        Logger::add( Actions::REPUBLISH, $rewrite_id, $original_id, 0, array( 'title' => $rewrite->post_title ) );

        /**
         * After a rewrite was put into its original, before the rewrite is removed.
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_republished', $original_id, $rewrite_id );

        if ( $delete_now ) {
            wp_delete_post( $rewrite_id, true );
        } else {
            add_action( 'shutdown', function () use ( $rewrite_id ) {
                wp_delete_post( $rewrite_id, true );
            } );
        }
        return $original_id;
    }

    private static function kept( string $key ): bool {
        foreach ( self::KEEP_META as $pattern ) {
            if ( substr( $pattern, -1 ) === '*' ? strpos( $key, substr( $pattern, 0, -1 ) ) === 0 : $key === $pattern ) {
                return true;
            }
        }
        return false;
    }
}
