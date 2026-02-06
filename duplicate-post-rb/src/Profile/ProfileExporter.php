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

use rbDuplicatePost\Profile\Profile;
use WP_Post;

/** 
 * Exports custom profiles to JSON format with specified meta fields only.
 * Supports multiple profile IDs and structured data preparation.
 */
class ProfileExporter {

    /**
     * Default filename for export.
     */
    const DEFAULT_FILENAME = 'rb_duplicate_post_profiles.txt';

    const PROFILES_SECTION_KEY = 'profiles';

    const ALLOWED_META_FIELDS = array(
        'rb-duplicate-post-options',
    );

    /**
     * Exports multiple profiles and triggers file download.
     *
     * @param array $profile_ids Array of profile IDs to export.
     * @return void
     */
    public static function export_profiles(  array $profile_ids ): void {
        if ( empty( $profile_ids ) ) {
            self::send_error_response( 'No profile IDs provided.' );
            return;
        }

        // Prepare export data
        $export_data = self::prepare_export_data( $profile_ids, self::ALLOWED_META_FIELDS );
        
        if ( empty( $export_data[self::PROFILES_SECTION_KEY] ) ) {
            self::send_error_response( 'No valid profiles found for export.' );
            return;
        }

        // Generate JSON content
        $json_content = self::generate_json_content( $export_data );
        if ( ! $json_content ) {
            self::send_error_response( 'Failed to generate JSON content.' );
            return;
        }

        // Send file for download
        self::send_file_download( $json_content);
    }

    /**
     * Exports ALL profiles from the system and triggers file download.
     *
     * @return void
     */
    public static function export_all_profiles(): void {
        // Get all profile IDs
        $all_profile_ids = self::get_all_profile_ids();
        
        if ( empty( $all_profile_ids ) ) {
            self::send_error_response( 'No profiles found in the system.' );
            return;
        }

        // Reuse existing export logic
        self::export_profiles( $all_profile_ids );
    }

    /**
     * Gets all profile IDs from the system.
     *
     * @return array
     */
    private static function get_all_profile_ids(): array {
        $args = array(
            'post_type'      => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'post_status'    => 'any',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true,
        );

        $profiles = get_posts( $args );
        return ! empty( $profiles ) ? array_map( 'intval', $profiles ) : array();
    }

    /**
     * Prepares complete export data structure.
     *
     * @param array $profile_ids
     * @param array $allowed_meta_keys
     * @return array
     */
    private static function prepare_export_data( array $profile_ids, array $allowed_meta_keys ): array {
        $profiles_data = array();
        $valid_profile_count = 0;

        foreach ( $profile_ids as $profile_id ) {
            $profile = get_post( $profile_id );
            if ( ! $profile instanceof WP_Post ) {
                continue;
            }

            $profile_data = self::collect_profile_data( $profile, $allowed_meta_keys );
            if ( ! empty( $profile_data ) ) {
                $profiles_data[] = $profile_data;
                $valid_profile_count++;
            }
        }

        return array(
            'export_info' => self::get_export_metadata(),
            'total_profiles' => $valid_profile_count,
            self::PROFILES_SECTION_KEY => $profiles_data,
        );
    }

    /**
     * Collects essential profile data and filtered meta fields.
     *
     * @param WP_Post $profile
     * @param array $allowed_meta_keys
     * @return array
     */
    private static function collect_profile_data( WP_Post $profile, array $allowed_meta_keys ): array {
        return array(
            'ID' => (int) $profile->ID,
            'title' => $profile->post_title,
            'slug' => $profile->post_name,
            'date_created' => $profile->post_date,
            'date_updated' => $profile->post_modified,
            'post_type' => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'default_profile' => ( Profile::getDefaultProfileId() === (int) $profile->ID ), 
            'meta' => self::get_filtered_profile_meta( $profile->ID, $allowed_meta_keys ),
        );
    }

    /**
     * Gets filtered profile meta based on allowed keys.
     *
     * @param int $profile_id
     * @param array $allowed_meta_keys
     * @return array
     */
    private static function get_filtered_profile_meta( int $profile_id, array $allowed_meta_keys ): array {
        $all_meta = get_post_meta( $profile_id );
        if ( empty( $all_meta ) || ! is_array( $all_meta ) ) {
            return array();
        }

        $excluded_keys = self::get_excluded_meta_keys();
        $filtered_meta = array();

        foreach ( $all_meta as $key => $values ) {
            // Skip excluded keys
            if ( in_array( $key, $excluded_keys, true ) ) {
                continue;
            }

            // If allowed_keys specified, skip keys not in the list
            if ( ! empty( $allowed_meta_keys ) && ! in_array( $key, $allowed_meta_keys, true ) ) {
                continue;
            }

            // Handle single vs multiple values
            if ( count( $values ) === 1 ) {
                $filtered_meta[ $key ] = maybe_unserialize( $values[0] );
            } else {
                $filtered_meta[ $key ] = array_map( 'maybe_unserialize', $values );
            }
        }

        return $filtered_meta;
    }

    /**
     * Generates JSON content from export data.
     *
     * @param array $export_data
     * @return string|false
     */
    private static function generate_json_content( array $export_data ) {
        $json = wp_json_encode( 
            $export_data, 
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES 
        );
        
        return ( $json !== false ) ? $json : false;
    }

    /**
     * Sends file for download to browser.
     *
     * @param string $content
     * @param string $filename
     * @return void
     */
    private static function send_file_download( string $content ): void {
        if ( ! headers_sent() ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            header( 'Content-Disposition: attachment; filename="' . self::DEFAULT_FILENAME . '"' );
            header( 'Content-Length: ' . strlen( $content ) );
            header( 'Cache-Control: no-cache, no-store, must-revalidate' );
            header( 'Pragma: no-cache' );
            header( 'Expires: 0' );
        }

        echo $content;
        exit;
    }

    /**
     * Gets export metadata information.
     *
     * @return array
     */
    private static function get_export_metadata(): array {
        return array(
            'version' => '1.0',
            'exported_at' => current_time( 'mysql' ),
            'site_url' => home_url(),
            'wordpress_version' => get_bloginfo( 'version' ),
            'exporter' => 'rbDuplicatePost Profile Exporter',
        );
    }

    /**
     * Gets excluded meta keys with filter support.
     *
     * @return array
     */
    private static function get_excluded_meta_keys(): array {
        $keys = array(
            '_edit_last',
            '_edit_lock',
            '_wp_old_slug',
            '_wp_old_date',
            '_wp_attachment_metadata',
            '_wp_attached_file',
            '_thumbnail_id',
            '_wp_page_template',
            // Add more protected keys as needed
        );
        
        return apply_filters( 'rb_duplicate_post_export_excluded_meta_keys', $keys );
    }

    /**
     * Sends error response (for debugging or AJAX).
     *
     * @param string $message
     * @return void
     */
    private static function send_error_response( string $message ): void {
        if ( ! headers_sent() ) {
            header( 'Content-Type: application/json; charset=utf-8' );
            http_response_code( 400 );
        }
        
        echo wp_json_encode( array( 'error' => $message ) );
        exit;
    }
}