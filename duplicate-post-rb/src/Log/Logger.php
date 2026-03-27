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

namespace rbDuplicatePost\Log;

defined('WPINC') || exit;

//use wpdb;
use rbDuplicatePost\Log\Actions;
use rbDuplicatePost\Constants;

/**
 * Logging utility for rbDuplicatePost plugin.
 */
class Logger
{

    private const USER_WPCLI = -77;
    private const USER_GUEST = 0;

    private static  $wpdb = null;
    private static  $table = null;

    /**
     * Cache flag to avoid multiple SHOW TABLES queries per request
     */
    private static $tableExistsCache = null;

    /**
     * Initialize database connection and table name
     */
    public static function init()
    {
        if (self::$wpdb === null) {
            global $wpdb;

            self::$wpdb  = $wpdb;
            self::$table = $wpdb->prefix . Constants::LOG_TABLE_NAME;
        }
    }

    /**
     * Check if log table exists (cached per request)
     */
    public static function tableExists( $without_cache = false ): bool
    {
        self::init();

        if (self::$tableExistsCache !== null && !$without_cache) {
            return self::$tableExistsCache;
        }

        $query = self::$wpdb->prepare(
            "SHOW TABLES LIKE %s",
            self::$table
        );

        self::$tableExistsCache = (bool) self::$wpdb->get_var($query);

        return self::$tableExistsCache;
    }

    /**
     * Create database table on plugin activation
     */
    public static function install()
    {
        self::init();

        $charset = self::$wpdb->get_charset_collate();

        // Create table
        $sql = "CREATE TABLE IF NOT EXISTS " . self::$table . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            date DATETIME NOT NULL,
            user_id BIGINT NOT NULL,
            action_type VARCHAR(50) NOT NULL,
            source_post_id BIGINT UNSIGNED NULL,
            target_post_id BIGINT UNSIGNED NULL,
            resource_count INT DEFAULT 0,
            metadata LONGTEXT NULL,
            PRIMARY KEY (id),
            KEY action_type (action_type),
            KEY user_id (user_id),
            KEY date (date)
        ) $charset;";

        // Check if dbDelta function exists
        if (!function_exists('dbDelta')) {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        }

        // Execute SQL
        dbDelta($sql);

        // Reset cache after installation
        self::$tableExistsCache = true;
    }

    /**
     * Resolve current user identifier.
     *
     * Returns:
     * - real WordPress user ID
     * - -77 for WP-CLI
     * - 0 for guest/unknown user
     */
    private static function getCurrentUserId(): int
    {
        if (defined('WP_CLI') && WP_CLI) {
            return self::USER_WPCLI;
        }

        $user = wp_get_current_user();

        if ($user && $user->exists()) {
            return (int) $user->ID;
        }

        return self::USER_GUEST;
    }

    /**
     * Add new log entry
     */
    public static function add(
        string $actionType,
        int $sourcePostId = 0,
        int $targetPostId = 0,
        int $resourceCount = 0,
        array $metadata = []
    ) {

        self::init();

        if (!self::tableExists()) {
            return false;
        }

        if (!Actions::isValid($actionType)) {
            return false;
        }

        $encodedMetadata = null;

        if (!empty($metadata)) {
            $encodedMetadata = wp_json_encode($metadata);

            if ($encodedMetadata === false) {
                $encodedMetadata = null;
            }
        }

        $data = [
            'date'           => current_time('mysql', true),
            'user_id'        => self::getCurrentUserId(),
            'action_type'    => $actionType,
            'source_post_id' => $sourcePostId,
            'target_post_id' => $targetPostId,
            'resource_count' => $resourceCount,
            'metadata'       => $encodedMetadata
        ];

        $format = ['%s', '%d', '%s', '%d', '%d', '%d', '%s'];

        $result = self::$wpdb->insert(self::$table, $data, $format);

        return $result ? (int) self::$wpdb->insert_id : false;
    }

    /**
     * Update existing log entry
     */
    public static function update(int $id, array $data): bool
    {
        self::init();

        if (!self::tableExists()) {
            return false;
        }

        $allowed = [
            'action_type',
            'source_post_id',
            'target_post_id',
            'resource_count',
            'metadata'
        ];

        $updateData = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            if ($key === 'action_type') {
                if (!Actions::isValid($data[$key])) {
                    continue;
                }
            }

            if ($key === 'metadata' && is_array($data[$key])) {
                $json = wp_json_encode($data[$key]);

                if ($json !== false) {
                    $updateData[$key] = $json;
                }
            } else {
                $updateData[$key] = $data[$key];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        $result = self::$wpdb->update(
            self::$table,
            $updateData,
            ['id' => $id]
        );

        return $result !== false;
    }

    /**
     * Delete log entry by ID
     */
    public static function delete(int $id): bool
    {
        self::init();

        if (!self::tableExists()) {
            return false;
        }

        return (bool) self::$wpdb->delete(
            self::$table,
            ['id' => $id],
            ['%d']
        );
    }

    /**
     * Retrieve single log entry
     */
    public static function get(int $id)
    {
        self::init();

        if (!self::tableExists()) {
            return null;
        }

        $sql = self::$wpdb->prepare(
            "SELECT * FROM " . self::$table . " WHERE id = %d",
            $id
        );

        $row = self::$wpdb->get_row($sql);

        if ($row && !empty($row->metadata)) {
            $row->metadata = json_decode($row->metadata, true);
        }

        return $row;
    }

    /**
     * Retrieve logs with optional filters and pagination
     */
    public static function getAll(array $args = []): array
    {
        self::init();

        if (!self::tableExists()) {
            return [];
        }

        $where  = [];
        $values = [];

        if (isset($args['user_id'])) {
            $where[]  = 'user_id = %d';
            $values[] = (int) $args['user_id'];
        }

        if (!empty($args['action_type']) && Actions::isValid($args['action_type'])) {
            $where[]  = 'action_type = %s';
            $values[] = $args['action_type'];
        }

        $limit  = isset($args['limit']) ? max(1, (int) $args['limit']) : 20;
        $offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

        $sql = "SELECT * FROM " . self::$table;

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY date DESC LIMIT %d OFFSET %d';

        $values[] = $limit;
        $values[] = $offset;

        $sql = self::$wpdb->prepare($sql, $values);

        $results = self::$wpdb->get_results($sql);

        foreach ($results as $row) {
            if (!empty($row->metadata)) {
                $row->metadata = json_decode($row->metadata, true);
            }
        }

        return $results;
    }

    /**
     * Count log entries with optional filters
     */
    public static function count(array $args = []): int
    {
        self::init();

        if (!self::tableExists()) {
            return 0;
        }

        $where  = [];
        $values = [];

        if (isset($args['user_id'])) {
            $where[]  = 'user_id = %d';
            $values[] = (int) $args['user_id'];
        }

        if (!empty($args['action_type']) && Actions::isValid($args['action_type'])) {
            $where[]  = 'action_type = %s';
            $values[] = $args['action_type'];
        }

        $sql = "SELECT COUNT(*) FROM " . self::$table;

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        if ($values) {
            $sql = self::$wpdb->prepare($sql, $values);
        }

        return (int) self::$wpdb->get_var($sql);
    }

    // /**
    //  * Basic metadata search (simple LIKE-based search)
    //  */
    // public static function searchByMetadata(string $key, string $value): array
    // {
    //     self::init();

    //     if (!self::tableExists()) {
    //         return [];
    //     }

    //     $pattern = '%' . self::$wpdb->esc_like('"' . $key . '":"' . $value . '"') . '%';

    //     $sql = self::$wpdb->prepare(
    //         "SELECT * FROM `" . self::$table . "`
    //         WHERE metadata LIKE %s
    //         ORDER BY date DESC",
    //         $pattern
    //     );

    //     $results = self::$wpdb->get_results($sql);

    //     foreach ($results as $row) {
    //         if (!empty($row->metadata)) {
    //             $decoded = json_decode($row->metadata, true);
    //             $row->metadata = is_array($decoded) ? $decoded : [];
    //         }
    //     }

    //     return $results;
    // }

}
