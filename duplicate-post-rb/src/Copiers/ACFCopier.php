<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.1
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Copiers;

class ACFCopier
{
    /**
     * Updates the field/group key (post_name) for the specified post.
     * Generates a new unique hash and guarantees its uniqueness in the wp_posts table.
     *
     * @param int $post_id The ID of the post in WordPress
     * @return bool True if successfully updated, false if error or post not found
     */
    public static function updateFieldKey($post_id)
    {
        global $wpdb;

        // 1. Get the current post_name
        $current_key = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_name FROM {$wpdb->posts} WHERE ID = %d",
                $post_id
            )
        );

        // If record not found
        if (!$current_key) {
            return false;
        }

        // 2. Check if the string starts with field_ or group_
        // ^ - anchor to the start of the string
        if (!preg_match('/^(field_|group_)/', $current_key)) {
            return false; // Not a field/group key
        }

        // 3. Generate a new unique hash
        $new_hash = self::generateUniqueHash($wpdb, $current_key, $post_id);

        if (!$new_hash) {
            return false; // Failed to generate a unique hash
        }

        // 4. Update the record in the DB
        $updated = $wpdb->update(
            $wpdb->posts,
            array('post_name' => $new_hash),
            array('ID' => $post_id),
            array('%s'),
            array('%d')
        );

        return ($updated !== false);
    }

    /**
     * Generates a unique hash, checking occupancy in the DB
     *
     * @param wpdb $wpdb WordPress database object
     * @param string $original_key The original key (to preserve the prefix)
     * @param int $current_id The current post ID (to exclude from uniqueness check)
     * @param int $max_attempts Maximum number of generation attempts
     * @return string|false New unique key or false on failure
     */
    private static function generateUniqueHash($wpdb, $original_key, $current_id, $max_attempts = 10)
    {
        // Extract prefix (field_ or group_)
        preg_match('/^(field_|group_)/', $original_key, $matches);
        $prefix = $matches[1];

        $attempts = 0;

        while ($attempts < $max_attempts) {
            // Generate a new hash (ACF style - 13 characters)
            $new_hash = uniqid( $prefix );

            // Check if this key already exists in the posts table
            // Exclude the current post_id from the check
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND ID != %d",
                $new_hash,
                $current_id
            ));

            // If it does not exist, the key is unique
            if (!$exists) {
                return $new_hash;
            }

            $attempts++;
            
            // Small delay so uniqid() might generate a different value (if running too fast)
            //usleep(100);
        }

        return false;
    }

    /**
     * Bulk update keys for multiple post_ids
     *
     * @param array $post_ids Array of post IDs
     * @return array Result for each ID
     */
    public static function updateMultipleFieldKeys($post_ids)
    {
        $results = array();

        foreach ($post_ids as $post_id) {
            $results[$post_id] = self::updateFieldKey($post_id);
        }

        return $results;
    }
}