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
 * Copies terms (categories, tags, custom taxonomies) between blogs in Multisite.
 * Preserves hierarchy and avoids duplicates.
 */
class PostTermCopier {

    /**
     * List of term meta keys to exclude from copying.
     */
    const EXCLUDED_TERM_META_KEYS = array(
        '_edit_lock',
        '_edit_last',
        // ACF internal
        //'_acf_term',
        // SEO plugins 
        // '_yoast_wpseo_primary_term',
        // '_rank_math_primary_term',
    );

    /**
     * Copies ALL taxonomies from source to target post using CopyOperationContext.
     *
     * @param CopyOperationContext $operation
     * @return array<string, array<int>> Copied terms grouped by taxonomy
     */
    public static function copy_all_taxonomies( CopyOperationContext $operation ): array {
        $source = $operation->get_source();
        $target = $operation->get_target();
        
        // Get all terms from source post
        $source_terms = self::get_post_terms_grouped($source);

        // Copy all taxonomies
        $copied_terms = self::copy_taxonomies( $source_terms, $source, $target );

        // Assign copied terms to target post
        if ( ! empty( $copied_terms ) ) {   
            self::assign_terms_to_post(  $copied_terms, $target );
        }

        return $copied_terms;
    }

    /**
     * Copies ONLY CUSTOM (non-built-in) taxonomies associated with a post.
     */
    public static function copy_custom_taxonomies( CopyOperationContext $operation ): array {
        $source = $operation->get_source();
        $target = $operation->get_target();
        
        // Get all terms from source post
        $source_terms = self::get_post_terms_grouped($source);

        // Filter to custom taxonomies only
        $custom_terms = self::filter_custom_taxonomies( $source_terms, $source );
        
        // Copy all taxonomies
        $copied_terms = self::copy_taxonomies( $custom_terms, $source, $target );

        // Assign copied terms to target post
        if ( ! empty( $copied_terms ) ) {   
            self::assign_terms_to_post(  $copied_terms, $target );
        }

        return $copied_terms;
    }

    /**
     * Copies terms from source to target blog for a specific taxonomy.
     */
    public static function copy_terms(
        array $source_term_ids,
        string $taxonomy,
        BlogPostContext $source,
        BlogPostContext $target
    ): array {
        if ( empty( $source_term_ids ) ) {
            return array();
        }

        // If same blog, return original IDs
        if ( $source->get_blog_id() === $target->get_blog_id() ) {
            return $source_term_ids;
        }

        $new_term_ids = array();
        $copied_cache = array(); // Cache for already copied terms

        foreach ( $source_term_ids as $term_id ) {
           $new_id = self::copy_single_term( $term_id, $taxonomy, $source, $target, $copied_cache );
            if ( $new_id ) {
                $new_term_ids[] = $new_id;
            }
        }

        return $new_term_ids;
    }

    /**
     * Copies a single term with full hierarchy preservation.
     */
    private static function copy_single_term(
        int $term_id,
        string $taxonomy,
        BlogPostContext $source,
        BlogPostContext $target,
        array &$copied_cache
    ) {
        // Return from cache if already copied
        $cache_key = $source->get_blog_id() . '_' . $taxonomy . '_' . $term_id;
        if ( isset( $copied_cache[ $cache_key ] ) ) {
            return $copied_cache[ $cache_key ];
        }

        // Get full term path from source
        $term_path = self::get_term_path( $term_id, $taxonomy, $source );
        if ( empty( $term_path ) ) {
            $copied_cache[ $cache_key ] = false;
            return false;
        }

        // Find or create the term path in target
        $target_term_id = self::sync_term_path( $term_path, $taxonomy, $source, $target );
        $copied_cache[ $cache_key ] = $target_term_id;

        return $target_term_id;
    }

    /**
     * Gets the full path of a term (from root to term) in the source blog.
     * 
     * @param int $term_id
     * @param string $taxonomy
     * @param BlogPostContext $source
     * @return array Array of term data from root to target term
     */
    private static function get_term_path( int $term_id, string $taxonomy, BlogPostContext $source ): array {
        $did_switch = $source->maybe_switch_to_blog();
        $path = array();

        $current_id = $term_id;
        $visited = array();
        while ( $current_id && ! isset( $visited[ $current_id ] )  ) {
            $visited[ $current_id ] = true;
            $term = \get_term( $current_id, $taxonomy );
            if ( ! $term || is_wp_error( $term ) ) {
                break;
            }
            array_unshift( $path, array(
                'term_id' => (int) $term->term_id,
                'name'    => $term->name,
                'slug'    => $term->slug,
                'description' => $term->description,
            ));
            $current_id = (int) $term->parent ?: 0;
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return $path;
    }

    /**
     * Syncs a term path to the target blog, creating missing terms.
     * 
     * @param array $term_path Array of term data from root to target term
     * @param string $taxonomy
     * @param BlogPostContext $source
     * @param BlogPostContext $target
     * @return int ID of the final term in the path on the target blog, or 0 on failure
     */
    private static function sync_term_path(
        array $term_path,
        string $taxonomy,
        BlogPostContext $source,
        BlogPostContext $target
    ): int {
        $did_switch = $target->maybe_switch_to_blog();
        $current_parent = 0;

        foreach ( $term_path as $term_data ) {
            if ( empty( $term_data['name'] ) ) { // Sanity check
                continue;
            }

            // Check if term exists with same slug AND parent
            $existing_term = self::find_term_by_slug_and_parent( 
                $term_data['slug'], 
                $current_parent, 
                $taxonomy,
                $term_data['name']
            );

            if ( $existing_term ) {

                self::copy_term_meta(
                    $term_data['term_id'],
                    (int) $existing_term->term_id,
                    $source,
                    $target
                );

                // Update description if different
                if ( $existing_term->description !== $term_data['description'] ) {
                    \wp_update_term( $existing_term->term_id, $taxonomy, array(
                        'description' => $term_data['description']
                    ));
                }
                $current_parent = (int) $existing_term->term_id;
                continue;
            }

            // if Slug not found, try to create the term
            $result = \wp_insert_term(
                $term_data['name'],
                $taxonomy,
                array(
                    'slug'        => $term_data['slug'],
                    'description' => $term_data['description'],
                    'parent'      => $current_parent,
                )
            );

            if ( \is_wp_error( $result ) ) {
                // Fallback: try without slug (in case of conflict)
                $result = \wp_insert_term(
                    $term_data['name'],
                    $taxonomy,
                    array(
                        'description' => $term_data['description'],
                        'parent'      => $current_parent,
                    )
                );
            }

            if ( \is_wp_error( $result ) ) {
                // Final fallback: try to find by name + parent
                $existing_by_name = self::find_term_by_name_and_parent(
                    $term_data['name'],
                    $current_parent,
                    $taxonomy
                );
                
                if ( $existing_by_name ) {
                    // Update description if different
                    if ( $existing_by_name->description !== $term_data['description'] ) {
                        \wp_update_term( $existing_by_name->term_id, $taxonomy, array(
                            'description' => $term_data['description']
                        ));
                    }
                    $current_parent = (int) $existing_by_name->term_id;
                    continue;
                }
                
                // Unable to create or find term
                BlogPostContext::maybe_restore_blog( $did_switch );
                return 0;
            }

            $created_term_id = (int) $result['term_id'];

            // Copy term meta from source to target
            self::copy_term_meta(
                $term_data['term_id'],
                $created_term_id,
                $source,
                $target
            );

            $current_parent = $created_term_id;
            
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return $current_parent;
    }

    /**
     * Finds a term by slug and parent ID with fallback to name matching.
     */
    private static function find_term_by_slug_and_parent( string $slug, int $parent_id, string $taxonomy, string $expected_name = '' ) {
        // Primary search: by slug + parent
        $terms = \get_terms( array(
            'taxonomy'   => $taxonomy,
            'slug'       => $slug,
            'parent'     => $parent_id,
            'hide_empty' => false,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
            'number'     => 10,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            // Fallback: search by name + parent if expected_name provided
            if ( $expected_name ) {
                $terms_by_name = \get_terms( array(
                    'taxonomy'   => $taxonomy,
                    'name'       => $expected_name,
                    'parent'     => $parent_id,
                    'hide_empty' => false,
                    'orderby'    => 'term_id',
                    'order'      => 'ASC',
                    'number'     => 1,
                ) );

                if ( ! is_wp_error( $terms_by_name ) && ! empty( $terms_by_name ) ) {
                    return $terms_by_name[0];
                }
            }
            return false;
        }

        if ( count( $terms ) === 1 ) {
            return $terms[0];
        }

        // Multiple matches - try to find exact name match
        if ( $expected_name ) {
            foreach ( $terms as $term ) {
                if ( $term->name === $expected_name ) {
                    return $term;
                }
            }
        }

        // Return first match as fallback
        return $terms[0];
    }

    /**
     * Copies ALL taxonomies associated with a post (including built-in).
     */
    public static function copy_taxonomies(
        array $source_terms,
        BlogPostContext $source,
        BlogPostContext $target
    ): array {
        $copied_terms = array();

        foreach ( $source_terms as $taxonomy => $term_ids ) {
            if ( ! is_string( $taxonomy ) || ! is_array( $term_ids ) ) {
                continue;
            }

            // Verify taxonomy exists in target
            $did_switch_target = $target->maybe_switch_to_blog();
            $taxonomy_exists = \taxonomy_exists( $taxonomy );
            BlogPostContext::maybe_restore_blog( $did_switch_target );

            if ( ! $taxonomy_exists ) {
                continue;
            }

            $copied_terms[ $taxonomy ] = self::copy_terms(
                $term_ids,
                $taxonomy,
                $source,
                $target
            );
        }

        return $copied_terms;
    }

    /**
     * Gets all terms of a post grouped by taxonomy.
     * @param BlogPostContext $context
     * @return array<string, array<int>> Terms grouped by taxonomy
     */
    public static function get_post_terms_grouped( BlogPostContext $context ): array {
        $post_id = $context->get_post_id();
        $did_switch = $context->maybe_switch_to_blog();

        $taxonomies = \get_post_taxonomies( $post_id );
        $terms = array();

        foreach ( $taxonomies as $taxonomy ) {
            $term_objects = \get_the_terms( $post_id, $taxonomy );
            if ( ! empty( $term_objects ) && ! \is_wp_error( $term_objects ) ) {
                $terms[ $taxonomy ] = \wp_list_pluck( $term_objects, 'term_id' );
            }
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
        return $terms;
    }

    

    /**
     * Filters out built-in taxonomies (category, post_tag, post_format).
     */
    private static function filter_custom_taxonomies( array $terms_by_taxonomy, BlogPostContext $context ): array {
        $custom_terms = array();
        $did_switch = $context->maybe_switch_to_blog();

        foreach ( $terms_by_taxonomy as $taxonomy => $term_ids ) {
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            $tax_obj = get_taxonomy( $taxonomy );
            if ( ! $tax_obj ) {
                continue;
            }

            // Skip if marked as built-in
            if ( ! empty( $tax_obj->_builtin ) ) {
                continue;
            }

            $custom_terms[ $taxonomy ] = $term_ids;
        }

        if ( $did_switch ) {
            BlogPostContext::maybe_restore_blog( $did_switch );
        }

        return $custom_terms;
    }

    /**
     * Copies all term meta from source term to target term.
     *
     * @param int $source_term_id
     * @param int $target_term_id
     * @param BlogPostContext $source
     * @param BlogPostContext $target
     * @return void
     */
    private static function copy_term_meta(
        int $source_term_id,
        int $target_term_id,
        BlogPostContext $source,
        BlogPostContext $target
    ): void {
        // Same blog — no need to copy
        if ( $source->get_blog_id() === $target->get_blog_id() ) {
            return;
        }

        $excluded_keys = self::get_excluded_term_meta_keys();

        // switch to source
        $did_switch_source = $source->maybe_switch_to_blog();
        $meta = get_term_meta( $source_term_id );
        BlogPostContext::maybe_restore_blog( $did_switch_source );

        if ( empty( $meta ) ) {
            return;
        }

        // switch to target
        $did_switch_target = $target->maybe_switch_to_blog();

        foreach ( $meta as $meta_key => $values ) {

            // Skip excluded keys
            if ( in_array( $meta_key, $excluded_keys, true ) ) {
                continue;
            }

            // Full replacement of meta key
            delete_term_meta( $target_term_id, $meta_key );

            foreach ( $values as $value ) {
                //allow empty values - for example checkbox false value
                //$value = maybe_unserialize( $value ); // get_term_meta already unserializes
                add_term_meta( $target_term_id, $meta_key, $value );
                
            }
        }

        BlogPostContext::maybe_restore_blog( $did_switch_target );
    }


    /**
     * Gets excluded term meta keys (with filter).
     *
     * @return array
     */
    private static function get_excluded_term_meta_keys(): array {
        $keys = self::EXCLUDED_TERM_META_KEYS;

        /**
         * Filters excluded term meta keys.
         *
         * @param array $keys
         */
        return apply_filters( 'rb_duplicate_post_excluded_term_meta_keys', $keys );
    }


    /**
     * Assigns copied terms to the target post.
     * Creates records in wp_term_relationships via WordPress API.
     *
     * @param array<string, array<int>> $terms_by_taxonomy
     * @param BlogPostContext $target
     * @return void
     */
    public static function assign_terms_to_post(
        array $terms_by_taxonomy,
        BlogPostContext $target
    ): void {

        if ( empty( $terms_by_taxonomy ) ) {
            return;
        }

        $target_post_id = $target->get_post_id();
        // no need check post exists - it is created before this copier runs

        $did_switch = $target->maybe_switch_to_blog();

        foreach ( $terms_by_taxonomy as $taxonomy => $term_ids ) {

            if ( empty( $term_ids ) || ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }

            wp_set_object_terms(
                $target_post_id,
                array_map( 'intval', $term_ids ),
                $taxonomy,
                false // replace existing terms - This is necessary for this task - it always happens for a new post
            );
        }

        BlogPostContext::maybe_restore_blog( $did_switch );
    }

}