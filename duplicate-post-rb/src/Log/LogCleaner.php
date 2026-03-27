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

use rbDuplicatePost\Constants;


/**
 * Cleaner for plugin activity logs.
 */

class LogCleaner
{
    public static function cleanOlderThan(int $days = 30): int
    {
        Logger::init();

        if (!Logger::tableExists()) {
            return 0;
        }

        global $wpdb;

        $table = $wpdb->prefix . Constants::LOG_TABLE_NAME;

        $date = gmdate('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table} WHERE date < %s",
                $date
            )
        );
    }

    public static function keepLast(int $limit = 5000): int
    {
        Logger::init();

        if (!Logger::tableExists()) {
            return 0;
        }

        global $wpdb;

        $table = $wpdb->prefix . 'rb_duplicate_post_log';

        return $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$table}
                 WHERE id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM {$table}
                        ORDER BY id DESC
                        LIMIT %d
                    ) as t
                 )",
                $limit
            )
        );
    }

    /**
     * Schedule daily log cleaning tasks.
     */
    public static function schedule()
    {
        if (!wp_next_scheduled('rb_duplicate_post_log_clean')) {
            wp_schedule_event(time(), 'daily', 'rb_duplicate_post_log_clean');
        }

        add_action('rb_duplicate_post_log_clean', [self::class, 'dailyTask']);
    }

    /**
     * Daily log cleaning task.
     */

    public static function dailyTask()
    {
        self::cleanOlderThan(60);
        self::keepLast(10000);
    }
}
