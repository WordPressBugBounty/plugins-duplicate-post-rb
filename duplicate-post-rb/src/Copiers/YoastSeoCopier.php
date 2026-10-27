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
use rbDuplicatePost\Copiers\YoastSeo\IndexableRebuilderInterface;
use rbDuplicatePost\Copiers\YoastSeo\CurrentIndexableRebuilder;
use rbDuplicatePost\Copiers\YoastSeo\LegacyIndexableRebuilder;

/**
 * Copies Yoast SEO (Free + Premium) postmeta from a source post to a
 * target post, for use when duplicating a post — including across
 * different custom post types and, in Multisite, across different blogs.
 *
 * Must run AFTER PostTermCopier::copy_all_taxonomies_with_map() (or
 * copy_custom_taxonomies_with_map()) — its 'map' is required in $args to
 * translate the Yoast SEO primary-term ID. Running after PostMediaCopier
 * is not required (this class calls PostMediaCopier::copy_attachment_by_id()
 * itself for OpenGraph/Twitter images), but doesn't hurt either.
 *
 * Deliberately never copies 'canonical' or 'redirect' — those are the only
 * two fields that literally contain the *source* post's own URL; Yoast
 * will generate a correct default for the new post on its own. Deliberately
 * never copies content-analysis fields ('linkdex' SEO score, 'content_score'
 * readability, 'inclusive_language_score', estimated reading time) — these
 * are recalculated by Yoast from the post's own content, and a copied value
 * would misrepresent a post whose content may differ from the source.
 *
 * Field whitelist is built by introspecting Yoast's own field registry
 * (WPSEO_Meta::$meta_fields) rather than a hardcoded Free/Premium list —
 * Premium (and other add-ons) already merge their fields into that
 * registry via Yoast's own 'add_extra_wpseo_meta_fields' filter, so
 * whatever edition is actually installed is what gets copied.
 *
 * Does not check capabilities/nonces — that is expected to already have
 * happened in the calling API layer.
 *
 * Deliberately has no knowledge of TransformerContext or any "profile" /
 * options-resolution concept — this class only ever receives a plain
 * CopyOperationContext + a plain $args array (see copy() below).
 * Translating a TransformerContext into that shape (calling
 * to_operation_context(), reading resolved options/data) is the calling
 * transformer's responsibility, not this class's.
 */
class YoastSeoCopier {

    /**
     * Bare Yoast SEO field names (i.e. without the '_yoast_wpseo_' prefix)
     * that WPSEO_Meta::$meta_fields may register, but that must never be
     * copied — see class docblock for why.
     */
    const NEVER_COPY_FIELDS = array(
        'canonical',
        'redirect',
        'linkdex',
        'content_score',
        'inclusive_language_score',
        'is_content_planner_banner_rendered',
        'is_content_planner_banner_dismissed',
    );

    /**
     * Bare field names known to exist as real postmeta but that are NOT
     * registered in WPSEO_Meta::$meta_fields, so introspection alone won't
     * find them. build_field_whitelist() never sees these (it only ever
     * copies fields it found via introspection) — documented here purely
     * as a guard/reminder for future code, in case a more permissive copy
     * path (e.g. a generic "copy every _yoast_wpseo_* key we can find" helper)
     * is ever added later.
     */
    const KNOWN_UNREGISTERED_RECALCULATED_FIELDS = array(
        'estimated-reading-time-minutes',
    );

    /**
     * Main plugin file used to check per-blog activation state (see
     * is_yoast_active_on_blog()). Filterable via
     // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
     * 'rb_duplicate_post_yoast_seo_plugin_file' in case of a fork/rename.
     */
    // the file WordPress lists as active (wp-seo-main.php is only the loader it includes)
    const DEFAULT_YOAST_PLUGIN_FILE = 'wordpress-seo/wp-seo.php';

    /**
     * Copies Yoast SEO data from the source post to the target post.
     *
     * @param CopyOperationContext $context
     * @param array $args {
     *     @type bool  $overwrite Default false. When false, a field is only
     *                  written if the target doesn't already have a
     *                  non-empty value. When true, always overwritten.
     *     @type array $term_map  Per-taxonomy source_term_id => target_term_id
     *                  map, from PostTermCopier::copy_all_taxonomies_with_map()
     *                  (or copy_custom_taxonomies_with_map())['map']. Required
     *                  for the primary-term field to be translated correctly;
     *                  without it, primary-term keys are skipped, not guessed.
     * }
     * @return array{
     *     success: bool,
     *     copied: array<string>,
     *     skipped: array<array{field: ?string, reason: string}>,
     *     errors: array<array{field: ?string, reason: string}>
     * }
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

        if ( ! self::is_yoast_active_on_blog( $source ) ) {
            $result['success']  = false;
            $result['errors'][] = array( 'field' => null, 'reason' => 'Yoast SEO not active on source blog' );
            return $result;
        }

        if ( ! class_exists( '\WPSEO_Meta' ) ) {
            // Should not normally happen if the check above passed, but
            // 'active per options' and 'class actually loaded in THIS PHP
            // process' are two different things — see is_yoast_active_on_blog().
            $result['success']  = false;
            $result['errors'][] = array( 'field' => null, 'reason' => 'Yoast SEO core class WPSEO_Meta not available in this request' );
            return $result;
        }

        if ( ! self::is_yoast_active_on_blog( $target ) ) {
            // Per design decision: not active on the TARGET blog is an
            // expected Multisite situation (Yoast isn't required to be
            // network-active), not a failure — skip quietly, success stays true.
            $result['skipped'][] = array( 'field' => null, 'reason' => 'Yoast SEO not active on target blog' );
            return $result;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_before_yoast_seo_copy', $context, $args );

        $overwrite = ! empty( $args['overwrite'] );
        $term_map  = ( isset( $args['term_map'] ) && is_array( $args['term_map'] ) ) ? $args['term_map'] : array();

        foreach ( self::build_field_whitelist( $context ) as $meta_key ) {
            self::copy_field( $meta_key, $context, $overwrite, $result );
        }

        self::copy_primary_terms( $context, $term_map, $overwrite, $result );
        self::copy_social_images( $context, $overwrite, $result );
        self::rebuild_target_indexable( $target, $result );

        $result['success'] = empty( $result['errors'] );

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        do_action( 'rb_duplicate_post_after_yoast_seo_copy', $context, $result );

        return $result;
    }

    // ─────────────────────── Field whitelist ───────────────────────

    /**
     * Builds the list of full postmeta keys ('_yoast_wpseo_...') to copy,
     * by introspecting Yoast's own field registry (WPSEO_Meta::$meta_fields)
     * instead of a hardcoded Free/Premium list. Excludes NEVER_COPY_FIELDS.
     *
     * Does NOT include primary-term keys ('_yoast_wpseo_primary_{taxonomy}')
     * — those aren't part of $meta_fields (Yoast registers them dynamically,
     * per taxonomy) and are handled separately by copy_primary_terms().
     *
     * @param CopyOperationContext $context
     * @return array<string>
     */
    private static function build_field_whitelist( CopyOperationContext $context ): array {

        if ( empty( \WPSEO_Meta::$meta_fields ) && method_exists( '\WPSEO_Meta', 'init' ) ) {
            \WPSEO_Meta::init();
        }

        $bare_names = array();

        foreach ( (array) \WPSEO_Meta::$meta_fields as $fields ) {
            if ( ! is_array( $fields ) ) {
                continue;
            }
            foreach ( $fields as $field_name => $definition ) {
                if ( ! is_string( $field_name ) || $field_name === '' ) {
                    continue;
                }
                if ( in_array( $field_name, self::NEVER_COPY_FIELDS, true ) ) {
                    continue;
                }
                $bare_names[ $field_name ] = true;
            }
        }

        $whitelist = array();
        foreach ( array_keys( $bare_names ) as $bare_name ) {
            $whitelist[] = '_yoast_wpseo_' . $bare_name;
        }

        /**
         * Filters the final list of Yoast SEO postmeta keys to copy for a
         * post duplication. Full keys, e.g. '_yoast_wpseo_title'.
         *
         * @param array<string> $whitelist
         * @param CopyOperationContext $context
         */
        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
        return apply_filters( 'rb_duplicate_post_yoast_seo_fields', $whitelist, $context );
    }

    // ─────────────────────── Plain field copy ───────────────────────

    /**
     * Copies a single plain Yoast SEO postmeta field from source to target,
     * respecting the overwrite rule, and records the outcome into $result.
     *
     * @param string $meta_key Full postmeta key, e.g. '_yoast_wpseo_title'.
     * @param CopyOperationContext $context
     * @param bool $overwrite
     * @param array $result Passed by reference; 'copied'/'skipped'/'errors' appended to.
     */
    private static function copy_field( string $meta_key, CopyOperationContext $context, bool $overwrite, array &$result ): void {

        $source = $context->get_source();
        $target = $context->get_target();

        $did_switch_source = $source->maybe_switch_to_blog();
        $has_source_value = metadata_exists( 'post', $source->get_post_id(), $meta_key );
        $source_value      = $has_source_value ? get_post_meta( $source->get_post_id(), $meta_key, true ) : '';
        BlogPostContext::maybe_restore_blog( $did_switch_source );

        if ( ! $has_source_value || $source_value === '' ) {
            $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'no value on source' );
            return;
        }

        $did_switch_target = $target->maybe_switch_to_blog();
        $has_target_value = metadata_exists( 'post', $target->get_post_id(), $meta_key );
        $target_value      = $has_target_value ? get_post_meta( $target->get_post_id(), $meta_key, true ) : '';

        if ( $has_target_value && $target_value !== '' && ! $overwrite ) {
            BlogPostContext::maybe_restore_blog( $did_switch_target );
            $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'target already has a value (overwrite=false)' );
            return;
        }

        $update_result = update_post_meta( $target->get_post_id(), $meta_key, wp_slash( $source_value ) );
        BlogPostContext::maybe_restore_blog( $did_switch_target );

        // update_post_meta() returns false both on genuine failure AND when
        // the new value is identical to the existing one — only treat it
        // as a real failure when the values actually differed.
        if ( $update_result === false && $target_value !== $source_value ) {
            $result['errors'][] = array( 'field' => $meta_key, 'reason' => 'update_post_meta() failed' );
            return;
        }

        $result['copied'][] = $meta_key;
    }

    // ─────────────────────── Primary term ───────────────────────

    /**
     * Copies '_yoast_wpseo_primary_{taxonomy}' for every taxonomy present on
     * the source post, translating the term ID via $term_map (see
     * PostTermCopier::copy_all_taxonomies_with_map()).
     *
     * These keys are discovered directly on the source post's own postmeta
     * (rather than via build_field_whitelist()) since Yoast registers them
     * dynamically per taxonomy — they are not part of WPSEO_Meta::$meta_fields.
     *
     * @param CopyOperationContext $context
     * @param array<string,array<int,int>> $term_map
     * @param bool $overwrite
     * @param array $result Passed by reference.
     */
    private static function copy_primary_terms( CopyOperationContext $context, array $term_map, bool $overwrite, array &$result ): void {

        $source = $context->get_source();
        $target = $context->get_target();

        $did_switch_source = $source->maybe_switch_to_blog();
        $source_meta = get_post_meta( $source->get_post_id() );
        BlogPostContext::maybe_restore_blog( $did_switch_source );

        $did_switch_target = $target->maybe_switch_to_blog();
        $target_post_type = $target->get_post_type();
        BlogPostContext::maybe_restore_blog( $did_switch_target );

        foreach ( (array) $source_meta as $meta_key => $values ) {

            if ( ! preg_match( '/^_yoast_wpseo_primary_(.+)$/', (string) $meta_key, $matches ) ) {
                continue;
            }

            $taxonomy       = $matches[1];
            $source_term_id = isset( $values[0] ) ? (int) maybe_unserialize( $values[0] ) : 0;

            if ( ! $source_term_id ) {
                continue;
            }

            if ( ! $target_post_type || ! is_object_in_taxonomy( $target_post_type, $taxonomy ) ) {
                $result['skipped'][] = array(
                    'field'  => $meta_key,
                    'reason' => "taxonomy '{$taxonomy}' not registered for target post type",
                );
                continue;
            }

            if ( ! isset( $term_map[ $taxonomy ][ $source_term_id ] ) ) {
                $result['skipped'][] = array(
                    'field'  => $meta_key,
                    'reason' => 'source term not found in term_map (terms not copied, or term_map not passed for this taxonomy)',
                );
                continue;
            }

            $target_term_id = (int) $term_map[ $taxonomy ][ $source_term_id ];

            $did_switch = $target->maybe_switch_to_blog();
            $has_existing  = metadata_exists( 'post', $target->get_post_id(), $meta_key );
            $existing_value = $has_existing ? get_post_meta( $target->get_post_id(), $meta_key, true ) : '';

            if ( $has_existing && $existing_value !== '' && ! $overwrite ) {
                BlogPostContext::maybe_restore_blog( $did_switch );
                $result['skipped'][] = array( 'field' => $meta_key, 'reason' => 'target already has a value (overwrite=false)' );
                continue;
            }

            update_post_meta( $target->get_post_id(), $meta_key, $target_term_id );
            BlogPostContext::maybe_restore_blog( $did_switch );

            $result['copied'][] = $meta_key;
        }
    }

    // ─────────────────────── Social images ───────────────────────

    /**
     * Copies the OpenGraph and Twitter image fields.
     *
     * The '-image-id' field is resolved via
     * PostMediaCopier::copy_attachment_by_id( ..., false ) — the `false`
     * (duplicate_on_same_blog) means that on the same blog the source
     * attachment ID is reused as-is (it's a referenced asset, not
     * something that needs an independent copy), while across blogs it is
     * still physically copied, since attachment IDs aren't shared between
     * blogs. The plain '-image' URL field is then rebuilt from the
     * resolved attachment via wp_get_attachment_url() in the target blog's
     * context — never copied as a literal string from source, which would
     * otherwise point at the wrong site/path.
     *
     * @param CopyOperationContext $context
     * @param bool $overwrite
     * @param array $result Passed by reference.
     */
    private static function copy_social_images( CopyOperationContext $context, bool $overwrite, array &$result ): void {

        $image_fields = array(
            array( 'id_key' => '_yoast_wpseo_opengraph-image-id', 'url_key' => '_yoast_wpseo_opengraph-image' ),
            array( 'id_key' => '_yoast_wpseo_twitter-image-id', 'url_key' => '_yoast_wpseo_twitter-image' ),
        );

        $source = $context->get_source();
        $target = $context->get_target();

        foreach ( $image_fields as $field_pair ) {
            $id_key  = $field_pair['id_key'];
            $url_key = $field_pair['url_key'];

            $did_switch_source = $source->maybe_switch_to_blog();
            $source_attachment_id = (int) get_post_meta( $source->get_post_id(), $id_key, true );
            BlogPostContext::maybe_restore_blog( $did_switch_source );

            if ( ! $source_attachment_id ) {
                $result['skipped'][] = array( 'field' => $id_key, 'reason' => 'no value on source' );
                continue;
            }

            $did_switch_target = $target->maybe_switch_to_blog();
            $target_has_id   = metadata_exists( 'post', $target->get_post_id(), $id_key );
            $target_id_value = $target_has_id ? get_post_meta( $target->get_post_id(), $id_key, true ) : '';
            BlogPostContext::maybe_restore_blog( $did_switch_target );

            if ( $target_has_id && $target_id_value !== '' && ! $overwrite ) {
                $result['skipped'][] = array( 'field' => $id_key, 'reason' => 'target already has a value (overwrite=false)' );
                continue;
            }

            $new_attachment_id = PostMediaCopier::copy_attachment_by_id( $source_attachment_id, $context, false );

            if ( ! $new_attachment_id ) {
                $result['errors'][] = array( 'field' => $id_key, 'reason' => 'failed to copy/resolve source attachment' );
                continue;
            }

            $did_switch_target = $target->maybe_switch_to_blog();
            update_post_meta( $target->get_post_id(), $id_key, $new_attachment_id );
            $new_url = \wp_get_attachment_url( $new_attachment_id );
            if ( $new_url ) {
                update_post_meta( $target->get_post_id(), $url_key, $new_url );
            }
            BlogPostContext::maybe_restore_blog( $did_switch_target );

            $result['copied'][] = $id_key;

            if ( $new_url ) {
                $result['copied'][] = $url_key;
            } else {
                $result['skipped'][] = array( 'field' => $url_key, 'reason' => 'wp_get_attachment_url() returned empty for the resolved attachment' );
            }
        }
    }

    // ─────────────────────── Indexable rebuild ───────────────────────

    /**
     * Forces the target post's Yoast indexable to be rebuilt on next
     * access. Best-effort: a failure here does not affect $result['success']
     * — the copied postmeta is still correct either way, only the
     * denormalized cache may lag until Yoast repopulates it on its own.
     *
     * @param BlogPostContext $target
     * @param array $result Passed by reference (a failure is recorded to 'skipped').
     */
    private static function rebuild_target_indexable( BlogPostContext $target, array &$result ): void {

        $did_switch = $target->maybe_switch_to_blog();

        try {
            $rebuilder = self::get_indexable_rebuilder();
            $rebuilder->rebuild( $target->get_post_id() );
        } catch ( \Throwable $e ) {
            $result['skipped'][] = array(
                'field'  => null,
                'reason' => 'indexable rebuild failed: ' . $e->getMessage(),
            );
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
    }

    /**
     * Picks the indexable-rebuild adapter for whichever Yoast SEO version
     * is active on the current (already switched-to) blog.
     *
     * @return IndexableRebuilderInterface
     */
    private static function get_indexable_rebuilder(): IndexableRebuilderInterface {
        if ( class_exists( '\Yoast\WP\SEO\Repositories\Indexable_Repository' ) ) {
            return new CurrentIndexableRebuilder();
        }
        return new LegacyIndexableRebuilder();
    }

    // ─────────────────────── Yoast activation check ───────────────────────

    /**
     * Checks whether Yoast SEO (Free — a prerequisite for Premium in every
     * released version) is active for the given blog.
     *
     * Reads that blog's active-plugins state directly via
     * get_site_option()/get_blog_option(), rather than checking loaded PHP
     * classes. This matters specifically in Multisite: switch_to_blog()
     * does NOT reload plugin files or re-run 'plugins_loaded' for the
     * blog being switched to, so class_exists('WPSEO_Meta') after
     * switching blogs does not reliably reflect whether Yoast is active
     * *there* — it reflects whichever blog's plugins were loaded for the
     * current PHP request. Reading the option directly sidesteps that.
     *
     * @param BlogPostContext $context
     * @return bool
     */
    private static function is_yoast_active_on_blog( BlogPostContext $context ): bool {

        $plugin_file = apply_filters(
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
            'rb_duplicate_post_yoast_seo_plugin_file',
            self::DEFAULT_YOAST_PLUGIN_FILE
        );

        if ( is_multisite() ) {
            // get_blog_option() only exists on Multisite (it lives in
            // wp-includes/ms-blogs.php, which core doesn't load on a
            // single-site install) — must not be called unconditionally.
            $network_active = get_site_option( 'active_sitewide_plugins', array() );
            if ( is_array( $network_active ) && isset( $network_active[ $plugin_file ] ) ) {
                return true;
            }

            $site_active = (array) get_blog_option( $context->get_blog_id(), 'active_plugins', array() );
        } else {
            $site_active = (array) get_option( 'active_plugins', array() );
        }

        return in_array( $plugin_file, $site_active, true );
    }
}
