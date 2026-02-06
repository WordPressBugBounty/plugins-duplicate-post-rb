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

namespace rbDuplicatePost\Profile;

use WP_Post;

/**
 * Imports profiles from JSON export file.
 * Handles conflicts by checking ID, title, slug, and meta data.
 * Skips duplicates, appends [imported YYYY-MM-DD HH:MM] to conflicting names.
 */
class ProfileImporter {

    const ALLOWED_META_FIELDS = array(
        'rb-duplicate-post-options',
    );

    /**
     * Imports profiles from JSON string.
     *
     * @param string $json_content JSON content from export file.
     * @return array Import result with statistics.
     */
    public static function import_profiles( string $json_content ): array {
        $data = json_decode( $json_content, true );
        
        if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
            return self::build_result( false, 0, 0, 0, array( 'Invalid JSON format.' ) );
        }

        if ( ! isset( $data['profiles'] ) || ! is_array( $data['profiles'] ) ) {
            return self::build_result( false, 0, 0, 0, array( 'No profiles found in JSON.' ) );
        }

        $profiles = $data['profiles'];
        $imported = 0;
        $skipped = 0;
        $errors = array();

        foreach ( $profiles as $profile_data ) {
            try {
                $result = self::import_single_profile( $profile_data );
                if ( $result['imported'] ) {
                    $imported++;
                } elseif ( $result['skipped'] ) {
                    $skipped++;
                }
                
                if ( ! empty( $result['error'] ) ) {
                    $errors[] = $result['error'];
                }
            } catch ( \Exception $e ) {
                $errors[] = 'Error importing profile: ' . $e->getMessage();
            }
        }

        $success = ( count( $errors ) === 0 && ( $imported > 0 || $skipped > 0 ) );
        return self::build_result( $success, $imported, $skipped, count( $errors ), $errors );
    }

    /**
     * Imports a single profile with conflict resolution.
     *
     * @param array $profile_data
     * @return array Result with imported/skipped/error status.
     */
    private static function import_single_profile( array $profile_data ): array {
        // Validate required fields
        $required_fields = array( 'ID', 'title', 'slug', 'post_type', 'meta' );
        foreach ( $required_fields as $field ) {
            if ( ! isset( $profile_data[ $field ] ) ) {
                return array( 'imported' => false, 'skipped' => false, 'error' => 'Missing required field: ' . $field );
            }
        }

        // Check if profile type matches
        if ( $profile_data['post_type'] !== RB_DUPLICATE_POST_PROFILE_TYPE_POST ) {
            return array( 'imported' => false, 'skipped' => false, 'error' => 'Invalid post type.' );
        }

        // Find existing profile by ID
        $existing_by_id = get_post( $profile_data['ID'] );
        
        // Find existing profile by slug
        $existing_by_slug = self::find_profile_by_slug( $profile_data['slug'] );
        
        // Check for exact match (same ID, title, slug, and meta)
        if ( $existing_by_id && self::is_exact_match( $existing_by_id, $profile_data ) ) {
            return array( 'imported' => false, 'skipped' => true, 'error' => '' );
        }

        // Check for slug conflict (different ID but same slug)
        if ( $existing_by_slug && ( ! $existing_by_id || $existing_by_id->ID !== $existing_by_slug->ID ) ) {
            // Append import timestamp to title and slug
            $import_timestamp = date( 'Y-m-d H:i' );
            $new_title = $profile_data['title'] . ' [imported ' . $import_timestamp . ']';
            $new_slug = sanitize_title( $new_title );
            
            // Ensure unique slug
            $new_slug = wp_unique_post_slug( $new_slug, 0, 'publish', RB_DUPLICATE_POST_PROFILE_TYPE_POST, 0 );
            
            $profile_data['title'] = $new_title;
            $profile_data['slug'] = $new_slug;
        }

        // Create or update profile
        $post_id = self::create_or_update_profile( $profile_data );
        if ( ! $post_id ) {
            return array( 'imported' => false, 'skipped' => false, 'error' => 'Failed to create/update profile.' );
        }

        return array( 'imported' => true, 'skipped' => false, 'error' => '' );
    }

    /**
     * Finds profile by slug.
     *
     * @param string $slug
     * @return WP_Post|null
     */
    private static function find_profile_by_slug( string $slug ) {
        $args = array(
            'post_type'      => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'name'           => $slug,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
        );

        $posts = get_posts( $args );
        return ! empty( $posts ) ? $posts[0] : null;
    }

    /**
     * Checks if existing profile exactly matches the import data.
     *
     * @param WP_Post $existing
     * @param array $import_data
     * @return bool
     */
    private static function is_exact_match( WP_Post $existing, array $import_data ): bool {
        // Check basic fields
        if ( $existing->post_title !== $import_data['title'] ||
             $existing->post_name !== $import_data['slug'] ||
             $existing->post_date !== $import_data['date_created'] ) {
            return false;
        }

        // Check meta fields
        $existing_meta = self::get_filtered_profile_meta_for_comparison( $existing->ID );
        $import_meta = $import_data['meta'];

        // Normalize both arrays
        ksort( $existing_meta );
        ksort( $import_meta );

        return $existing_meta === $import_meta;
    }

    /**
     * Gets filtered meta for comparison (only allowed fields).
     *
     * @param int $profile_id
     * @return array
     */
    private static function get_filtered_profile_meta_for_comparison( int $profile_id ): array {
        $all_meta = get_post_meta( $profile_id );
        if ( empty( $all_meta ) || ! is_array( $all_meta ) ) {
            return array();
        }

        $filtered_meta = array();
        foreach ( self::ALLOWED_META_FIELDS as $allowed_key ) {
            if ( isset( $all_meta[ $allowed_key ] ) ) {
                $values = $all_meta[ $allowed_key ];
                if ( count( $values ) === 1 ) {
                    $filtered_meta[ $allowed_key ] = maybe_unserialize( $values[0] );
                } else {
                    $filtered_meta[ $allowed_key ] = array_map( 'maybe_unserialize', $values );
                }
            }
        }

        return $filtered_meta;
    }

    /**
     * Creates or updates a profile post.
     *
     * @param array $profile_data
     * @return int|false Post ID on success, false on failure.
     */
    private static function create_or_update_profile( array $profile_data ) {
        $post_data = array(
            'post_title'   => $profile_data['title'],
            'post_name'    => $profile_data['slug'],
            'post_type'    => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'post_status'  => 'publish',
            'post_date'    => $profile_data['date_created'],
            'post_modified' => $profile_data['date_updated'],
        );

        // Insert new post
        $post_id = wp_insert_post( $post_data, true );
        if ( is_wp_error( $post_id ) ) {
            return false;
        }

        // Set meta fields
        if ( ! empty( $profile_data['meta'] ) ) {
            foreach ( $profile_data['meta'] as $meta_key => $meta_value ) {
                if ( in_array( $meta_key, self::ALLOWED_META_FIELDS, true ) ) {
                    update_post_meta( $post_id, $meta_key, $meta_value );
                }
            }
        }

        return $post_id;
    }

    /**
     * Builds standardized result array.
     */
    private static function build_result( 
        bool $success, 
        int $imported, 
        int $skipped, 
        int $errors_count, 
        array $errors 
    ): array {
        return array(
            'success' => $success,
            'imported' => $imported,
            'skipped' => $skipped,
            'errors_count' => $errors_count,
            'errors' => $errors,
        );
    }
}